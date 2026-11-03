<?php

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

it('requires authentication for every task route', function (string $method, string $uri) {
    $this->json($method, $uri)
        ->assertUnauthorized()
        ->assertExactJson([
            'status' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Unauthenticated.',
        ]);
})->with([
    'index' => ['GET', '/api/tasks'],
    'store' => ['POST', '/api/tasks'],
    'show' => ['GET', '/api/tasks/1'],
    'replace' => ['PUT', '/api/tasks/1'],
    'update' => ['PATCH', '/api/tasks/1'],
    'destroy' => ['DELETE', '/api/tasks/1'],
]);

it('returns the same unauthorized response for an invalid bearer token', function () {
    $this->withToken('invalid-token')
        ->getJson('/api/tasks')
        ->assertUnauthorized()
        ->assertExactJson([
            'status' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Unauthenticated.',
        ]);
});

it('allows a login token to access tasks', function () {
    $password = $this->faker->password(12);
    $user = User::factory()->create(['password' => $password]);

    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => $password,
    ])
        ->assertOk()
        ->json('data.token');

    $this->withToken($token)
        ->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonPath('status', Response::HTTP_OK)
        ->assertJsonPath('message', 'Tasks retrieved successfully.')
        ->assertJsonPath('data', []);
});

it('allows unauthenticated CORS preflight requests', function () {
    $this->call('OPTIONS', '/api/tasks', server: [
        'HTTP_ORIGIN' => 'http://localhost:5173',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});
