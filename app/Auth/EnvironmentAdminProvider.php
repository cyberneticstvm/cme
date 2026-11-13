<?php

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use SensitiveParameter;

class EnvironmentAdminProvider implements UserProvider
{
    public function configured(): bool
    {
        return trim((string) config('admin.username')) !== '' && (string) config('admin.password') !== '';
    }

    public function identifier(): string
    {
        return hash_hmac('sha256', (string) config('admin.username')."\0".(string) config('admin.password'), (string) config('app.key'));
    }

    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        if (! $this->configured() || ! is_string($identifier) || ! hash_equals($this->identifier(), $identifier)) {
            return null;
        }

        return new GenericUser(['id' => $this->identifier(), 'username' => (string) config('admin.username'), 'password' => '', 'remember_token' => null]);
    }

    public function retrieveByToken(mixed $identifier, #[SensitiveParameter] mixed $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, #[SensitiveParameter] mixed $token): void {}

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(#[SensitiveParameter] array $credentials): ?Authenticatable
    {
        $username = $credentials['username'] ?? null;
        if (! $this->configured() || ! is_string($username) || ! hash_equals(hash('sha256', (string) config('admin.username')), hash('sha256', $username))) {
            return null;
        }

        return $this->retrieveById($this->identifier());
    }

    /** @param array<string, mixed> $credentials */
    public function validateCredentials(Authenticatable $user, #[SensitiveParameter] array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        return $this->configured() && is_string($password)
            && hash_equals($this->identifier(), (string) $user->getAuthIdentifier())
            && hash_equals(hash('sha256', (string) config('admin.password')), hash('sha256', $password));
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[SensitiveParameter] array $credentials, bool $force = false): void {}
}
