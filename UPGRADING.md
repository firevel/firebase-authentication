# Upgrading

## From v2.x to v3.x

Version 3 changes how Firebase identity is stored on the user model. **The default schema now uses a separate `firebase_id` column instead of repurposing `users.id` as a string.** This makes the package drop-in compatible with Laravel's default `users` table and avoids destructive migrations.

### What changed

| | v2 (old) | v3 (new) |
| --- | --- | --- |
| Default `$firebaseResolveBy` | `['sub' => 'id']` | `['sub' => 'firebase_id']` |
| Default `picture` claim mapping | `picture` → `picture` | `avatar_url` → `picture` |
| `users.id` | String (Firebase UID) | Integer auto-increment (Laravel default) |
| Trait sets `$incrementing` / `$keyType` | Yes (`false` / `'string'`) | No — uses Laravel defaults |

### Option A: Keep v2 behavior (no schema change)

Pin the old defaults explicitly on your User model:

```php
class User extends Authenticatable
{
    use FirebaseAuthenticable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $firebaseResolveBy = ['sub' => 'id'];

    protected $firebaseClaimsMapping = [
        'email' => 'email',
        'name' => 'name',
        'picture' => 'picture',
    ];

    protected $fillable = ['id', 'name', 'email', 'picture'];
}
```

This keeps existing rows working with zero migration.

### Option B: Move to the v3 schema

The v3 schema keeps Laravel's default integer auto-increment `id` and stores the Firebase UID in a separate `firebase_id` column. How you get there depends on whether your v2 `id` column already holds Firebase UIDs as strings.

#### B1. Fresh install / `id` is already integer

Your v2 setup never wrote Firebase UIDs into `id` (e.g. you only just configured the package, or you've been storing the UID elsewhere). This is the easy path:

1. Publish and run the additive migration:
   ```bash
   php artisan vendor:publish --tag=firebase-authentication-migrations
   php artisan migrate
   ```
   It adds `firebase_id` (unique, nullable) and `avatar_url`, and makes `password` nullable.

2. Remove `public $incrementing = false;` and `protected $keyType = 'string';` from your User model.

3. Update `$fillable` to include `firebase_id` and `avatar_url` (drop `picture` if it was there).

Existing rows will get `firebase_id` populated on the next sign-in. No data migration needed.

#### B2. `id` is a string PK holding Firebase UIDs

This is genuinely a DBA-level migration: you need to convert the primary key column's type, possibly rebuild foreign keys, and backfill `firebase_id` from the old `id`. **Back up first.** The exact SQL varies per engine and is not something the package can ship as a generic migration. As a sketch:

```sql
-- 1. Add the new columns (this is the additive migration above)
ALTER TABLE users ADD COLUMN firebase_id VARCHAR(255) UNIQUE NULL;
ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL;

-- 2. Copy UIDs into firebase_id
UPDATE users SET firebase_id = id;

-- 3. Drop dependent foreign keys, drop the PK,
--    add an integer auto-increment id, re-add the PK,
--    re-create the foreign keys against the new id.
--    The SQL for this step differs by engine (MySQL: MODIFY COLUMN;
--    Postgres: USING and a sequence; SQLite: rebuild the table).
```

If you have production data in this shape, treat this as a one-off scripted migration with downtime planned, not something to run blindly. If you can afford to drop and recreate (dev environments, soft-launched apps), that's almost always simpler.

After the SQL is done:

4. Remove `public $incrementing = false;` and `protected $keyType = 'string';` from your User model.
5. Update `$fillable` to include `firebase_id` and `avatar_url`.

#### B3. Don't want to migrate yet?

Stick with [Option A](#option-a-keep-v2-behavior-no-schema-change). It's a fully supported path — v3 didn't remove anything, it just changed defaults.

### FirebaseIdentity (microservice mode)

In v3.0.0, `FirebaseIdentity` still used the Firebase UID as `id`. As of the next release this changes: the Firebase UID lives on `$identity->firebase_id`, mirroring the `User` model. See the Unreleased section of [CHANGELOG.md](CHANGELOG.md) for details and the [Microservice Setup](README.md#microservice-setup-without-database) section of the README for the new pattern (and how to expose `user_id` / `organization_id` from custom claims).

The `picture` → `avatar_url` claim rename from v2 → v3 still applies.
