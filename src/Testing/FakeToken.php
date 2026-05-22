<?php

namespace Firevel\FirebaseAuthentication\Testing;

use Kreait\Firebase\JWT\Contract\Token;

class FakeToken implements Token
{
    public function __construct(
        private readonly array $payload,
        private readonly string $raw = 'fake-token',
        private readonly array $headers = ['alg' => 'fake', 'typ' => 'JWT'],
    ) {
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function toString(): string
    {
        return $this->raw;
    }

    public function __toString(): string
    {
        return $this->raw;
    }
}
