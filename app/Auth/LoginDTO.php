<?php

namespace App\Auth;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public static function from(array $credentials): self
    {
        return new self(
            email: $credentials['email'],
            password: $credentials['password'],
        );
    }
}
