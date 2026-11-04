<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

it('returns a bearer token and public user data for valid credentials', function () {
    $password = $this->faker->password(12);
    $user = User::factory()->create(['password' => $password]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', Response::HTTP_OK)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.name', $user->name)
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ],
        ])
        ->assertJsonCount(3)
        ->assertJsonCount(3, 'data')
        ->assertJsonCount(3, 'data.user');

    $accessToken = PersonalAccessToken::query()->sole();

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    expect($accessToken->name)->toBe('user-api-token');
    expect($accessToken->tokenable->is($user))->toBeTrue();
});

it('issues a new token for each successful login', function () {
    $password = $this->faker->password(12);
    $user = User::factory()->create(['password' => $password]);
    $credentials = [
        'email' => $user->email,
        'password' => $password,
    ];

    $firstResponse = $this->postJson('/api/login', $credentials)->assertOk();
    $secondResponse = $this->postJson('/api/login', $credentials)->assertOk();

    expect($firstResponse->json('data.token'))
        ->not->toBe($secondResponse->json('data.token'));
    expect(PersonalAccessToken::query()->count())->toBe(2);
});

it('returns the same 401 response for unknown emails and incorrect passwords', function (array $credentials) {
    User::factory()->create([
        'email' => 'known@example.com',
        'password' => 'correct-password',
    ]);

    $this->postJson('/api/login', $credentials)
        ->assertUnauthorized();

    expect(PersonalAccessToken::query()->count())->toBe(0);
})->with([
    'unknown email' => [[
        'email' => 'unknown@example.com',
        'password' => 'correct-password',
    ]],
    'incorrect password' => [[
        'email' => 'known@example.com',
        'password' => 'incorrect-password',
    ]],
]);

it('returns 422 for invalid login input', function (array $credentials, string $message) {
    $this->postJson('/api/login', $credentials)
        ->assertUnprocessable()
        ->assertExactJson([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => $message,
        ]);

    expect(PersonalAccessToken::query()->count())->toBe(0);
})->with([
    'missing email' => [[
        'password' => 'password',
    ], 'The email field is required.'],
    'invalid email' => [[
        'email' => 'not-an-email',
        'password' => 'password',
    ], 'The email field must be a valid email address.'],
    'missing password' => [[
        'email' => 'user@example.com',
    ], 'The password field is required.'],
    'non-string password' => [[
        'email' => 'user@example.com',
        'password' => ['password'],
    ], 'The password field must be a string.'],
]);

it('returns 429 after five login attempts for the same email and IP address', function () {
    User::factory()->create([
        'email' => 'limited@example.com',
        'password' => 'correct-password',
    ]);
    $credentials = [
        'email' => 'limited@example.com',
        'password' => 'incorrect-password',
    ];

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', $credentials)->assertUnauthorized();
    }

    $this->postJson('/api/login', $credentials)
        ->assertTooManyRequests()
        ->assertJsonPath('status', Response::HTTP_TOO_MANY_REQUESTS)
        ->assertJsonPath('message', 'Too Many Requests.');

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('returns 401 when no token is provided to logout', function () {
    $this->postJson('/api/logout')
        ->assertUnauthorized()
        ->assertExactJson([
            'status' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Unauthenticated.',
        ]);
});

it('revokes only the bearer token used to logout', function () {
    $password = $this->faker->password(12);
    $user = User::factory()->create(['password' => $password]);
    $credentials = [
        'email' => $user->email,
        'password' => $password,
    ];

    $firstToken = $this->postJson('/api/login', $credentials)
        ->assertOk()
        ->json('data.token');
    $secondToken = $this->postJson('/api/login', $credentials)
        ->assertOk()
        ->json('data.token');
    $firstAccessToken = PersonalAccessToken::findToken($firstToken);
    $secondAccessToken = PersonalAccessToken::findToken($secondToken);

    $this->withToken($firstToken)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertExactJson([
            'status' => Response::HTTP_OK,
            'message' => 'Logged out successfully',
            'data' => null,
        ]);

    $this->assertModelMissing($firstAccessToken);
    $this->assertModelExists($secondAccessToken);

    Auth::forgetGuards();

    $this->withToken($firstToken)
        ->getJson('/api/tasks')
        ->assertUnauthorized();
    $this->withToken($secondToken)
        ->getJson('/api/tasks')
        ->assertOk();
});
