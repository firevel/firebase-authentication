<?php

namespace Firevel\FirebaseAuthentication\Tests\Unit;

use Firevel\FirebaseAuthentication\Filters\ArrayClaimFilter;
use Firevel\FirebaseAuthentication\Filters\BooleanClaimFilter;
use Firevel\FirebaseAuthentication\Filters\IntegerClaimFilter;
use Firevel\FirebaseAuthentication\Filters\StringClaimFilter;
use Firevel\FirebaseAuthentication\Filters\UrlClaimFilter;
use Firevel\FirebaseAuthentication\Tests\Fixtures\User;
use Firevel\FirebaseAuthentication\Tests\Fixtures\UserWithClaimFilters;
use Firevel\FirebaseAuthentication\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ClaimFilterTest extends TestCase
{
    #[Test]
    public function url_filter_accepts_http_and_https()
    {
        $filter = new UrlClaimFilter;

        $this->assertEquals('https://example.com/a.jpg', $filter->filter('picture', 'https://example.com/a.jpg', []));
        $this->assertEquals('http://example.com/a.jpg', $filter->filter('picture', 'http://example.com/a.jpg', []));
    }

    #[Test]
    public function url_filter_rejects_data_blob_and_other_schemes()
    {
        $filter = new UrlClaimFilter;

        $this->assertNull($filter->filter('picture', 'data:image/png;base64,AAAA', []));
        $this->assertNull($filter->filter('picture', 'ftp://example.com/a.jpg', []));
        $this->assertNull($filter->filter('picture', 'javascript:alert(1)', []));
        $this->assertNull($filter->filter('picture', 'not a url', []));
        $this->assertNull($filter->filter('picture', ['array'], []));
    }

    #[Test]
    public function integer_filter_coerces_integer_like_values_and_rejects_others()
    {
        $filter = new IntegerClaimFilter;

        $this->assertSame(42, $filter->filter('age', 42, []));
        $this->assertSame(42, $filter->filter('age', '42', []));
        $this->assertSame(0, $filter->filter('age', '0', []));
        $this->assertNull($filter->filter('age', '1.5', []));
        $this->assertNull($filter->filter('age', 'abc', []));
        $this->assertNull($filter->filter('age', true, []));
        $this->assertNull($filter->filter('age', ['x'], []));
    }

    #[Test]
    public function boolean_filter_parses_truthy_and_falsy_values()
    {
        $filter = new BooleanClaimFilter;

        $this->assertTrue($filter->filter('admin', true, []));
        $this->assertTrue($filter->filter('admin', 'true', []));
        $this->assertTrue($filter->filter('admin', '1', []));
        $this->assertFalse($filter->filter('admin', false, []));
        $this->assertFalse($filter->filter('admin', 'false', []));
        $this->assertNull($filter->filter('admin', 'maybe', []));
        $this->assertNull($filter->filter('admin', ['x'], []));
    }

    #[Test]
    public function array_filter_accepts_non_empty_arrays_only()
    {
        $filter = new ArrayClaimFilter;

        $this->assertSame(['a', 'b'], $filter->filter('roles', ['a', 'b'], []));
        $this->assertNull($filter->filter('roles', [], []));
        $this->assertNull($filter->filter('roles', 'a,b', []));
    }

    #[Test]
    public function string_filter_accepts_scalars_and_rejects_arrays()
    {
        $filter = new StringClaimFilter;

        $this->assertSame('hello', $filter->filter('phone_number', 'hello', []));
        $this->assertSame('42', $filter->filter('phone_number', 42, []));
        $this->assertNull($filter->filter('phone_number', '', []));
        $this->assertNull($filter->filter('phone_number', ['x'], []));
    }

    #[Test]
    public function default_user_url_filters_avatar_and_drops_blobs()
    {
        $user = new User;

        $valid = $user->transformClaims([
            'email' => 'a@example.com',
            'name' => 'A',
            'picture' => 'https://example.com/a.jpg',
        ]);
        $this->assertEquals('https://example.com/a.jpg', $valid['avatar_url']);

        $blob = $user->transformClaims([
            'email' => 'a@example.com',
            'name' => 'A',
            'picture' => 'data:image/png;base64,AAAAAAAA',
        ]);
        $this->assertArrayNotHasKey('avatar_url', $blob);
        $this->assertEquals('a@example.com', $blob['email']);
    }

    #[Test]
    public function model_can_configure_typed_filters()
    {
        $user = new UserWithClaimFilters;

        $attributes = $user->transformClaims([
            'email' => 'filter@example.com',
            'name' => '  Spaced Name  ',
            'picture' => 'https://example.com/pic.jpg',
            'age' => '37',
            'admin' => 'true',
            'roles' => ['editor', 'reviewer'],
            'phone_number' => 1234567890,
        ]);

        $this->assertSame('filter@example.com', $attributes['email']);
        $this->assertSame('Spaced Name', $attributes['name']); // closure trim filter
        $this->assertSame('https://example.com/pic.jpg', $attributes['avatar_url']);
        $this->assertSame(37, $attributes['age']);             // integer
        $this->assertTrue($attributes['is_admin']);            // boolean
        $this->assertSame(['editor', 'reviewer'], $attributes['roles']); // array
        $this->assertSame('1234567890', $attributes['phone']); // string
    }

    #[Test]
    public function invalid_typed_claims_are_skipped()
    {
        $user = new UserWithClaimFilters;

        $attributes = $user->transformClaims([
            'email' => 'filter@example.com',
            'name' => '   ',                 // closure returns null
            'picture' => 'data:image/png;base64,AAAA', // not http(s)
            'age' => 'not-a-number',         // integer rejects
            'admin' => 'sometimes',          // boolean rejects
            'roles' => 'editor',             // not an array
        ]);

        $this->assertSame(['email' => 'filter@example.com'], $attributes);
    }

    #[Test]
    public function unknown_filter_name_throws()
    {
        $user = new class extends User
        {
            protected $firebaseClaimFilters = ['email' => 'nope'];
        };

        $this->expectException(\InvalidArgumentException::class);

        $user->transformClaims(['email' => 'a@example.com']);
    }
}
