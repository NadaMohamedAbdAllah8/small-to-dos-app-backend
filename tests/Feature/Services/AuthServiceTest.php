<?php

use App\Auth\LoginDTO;
use App\Auth\LoginResponseDTO;
use App\Exceptions\AuthenticationException;
use App\Models\User;
use App\Services\AuthService;
use Laravel\Sanctum\PersonalAccessToken;

it('returns login data and persists a usable token for valid credentials', function () {
    $password = $this->faker->password(12);
    $user = User::factory()->create(['password' => $password]);

    $loginData = (new AuthService)->login(new LoginDTO(
        email: $user->email,
        password: $password,
    ));

    $storedToken = PersonalAccessToken::findToken($loginData->token);

    expect($loginData)->toBeInstanceOf(LoginResponseDTO::class);
    expect($loginData->token)->toBeString()->not->toBeEmpty();
    expect($loginData->tokenType)->toBe('Bearer');
    expect($loginData->user->is($user))->toBeTrue();
    expect($storedToken)->not->toBeNull();
    expect($storedToken->name)->toBe('user-api-token');
    expect($storedToken->tokenable->is($user))->toBeTrue();
});

it('rejects invalid credentials without creating a token', function (string $email, string $password) {
    User::factory()->create([
        'email' => 'known@example.com',
        'password' => 'correct-password',
    ]);

    expect(fn () => (new AuthService)->login(new LoginDTO(
        email: $email,
        password: $password,
    )))->toThrow(
        AuthenticationException::class,
        'The provided credentials are incorrect.',
    );

    expect(PersonalAccessToken::query()->count())->toBe(0);
})->with([
    'unknown email' => ['unknown@example.com', 'correct-password'],
    'incorrect password' => ['known@example.com', 'incorrect-password'],
]);
