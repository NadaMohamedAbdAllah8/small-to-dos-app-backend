<?php

use App\Enums\TaskPriority;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;

describe('index', function () {
    it('returns an empty task collection', function () {
        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('status', Response::HTTP_OK)
            ->assertJsonPath('message', 'Tasks retrieved successfully.')
            ->assertJsonPath('data', [])
            ->assertJsonCount(3);
    });

    it('returns tasks newest first with a stable id tie breaker', function () {
        $oldestCreatedAt = $this->faker->dateTimeBetween('-3 weeks', '-2 weeks');
        $newestCreatedAt = $this->faker->dateTimeBetween('-1 week', 'now');
        $oldestTask = Task::factory()->create(['created_at' => $oldestCreatedAt]);
        $firstNewestTask = Task::factory()->create(['created_at' => $newestCreatedAt]);
        $secondNewestTask = Task::factory()->create(['created_at' => $newestCreatedAt]);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('status', Response::HTTP_OK)
            ->assertJsonPath('message', 'Tasks retrieved successfully.')
            ->assertJsonPath('data.0.id', $secondNewestTask->id)
            ->assertJsonPath('data.1.id', $firstNewestTask->id)
            ->assertJsonPath('data.2.id', $oldestTask->id)
            ->assertJsonCount(3, 'data');
    });
});

describe('show', function () {
    it('returns the complete task resource', function () {
        $title = $this->faker->sentence();
        $priority = $this->faker->randomElement(TaskPriority::cases());
        $dueDate = $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d');
        $createdAt = $this->faker->dateTimeBetween('-2 weeks', '-1 week');
        $updatedAt = (clone $createdAt)->modify('+1 hour');
        $task = Task::factory()->create([
            'title' => $title,
            'description' => null,
            'priority' => $priority,
            'due_date' => $dueDate,
            'is_completed' => $this->faker->boolean(),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('status', Response::HTTP_OK)
            ->assertJsonPath('message', 'Task retrieved successfully.')
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', $title)
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.priority', $priority->value)
            ->assertJsonPath('data.due_date', $dueDate)
            ->assertJsonPath('data.is_completed', $task->is_completed)
            ->assertJsonPath('data.created_at', $task->created_at->toISOString())
            ->assertJsonPath('data.updated_at', $task->updated_at->toISOString())
            ->assertJsonCount(3)
            ->assertJsonCount(8, 'data');
    });

    it('returns only the required 404 structure when the task does not exist', function () {
        $missingTaskId = $this->faker->randomNumber();

        $this->getJson("/api/tasks/{$missingTaskId}")
            ->assertNotFound()
            ->assertJsonPath('status', Response::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Task not found.')
            ->assertJsonCount(2);
    });
});

it('returns the model name when any route-bound model does not exist', function () {
    $missingUserId = $this->faker->randomNumber();

    Route::get('/api/testing/users/{user}', fn (User $user): User => $user)
        ->middleware('api');

    $this->getJson("/api/testing/users/{$missingUserId}")
        ->assertNotFound()
        ->assertJsonPath('status', Response::HTTP_NOT_FOUND)
        ->assertJsonPath('message', 'User not found.')
        ->assertJsonCount(2);
});

describe('store', function () {
    it('creates a task while ignoring client controlled fields', function () {
        $currentTime = $this->faker->dateTimeBetween('-1 month', 'now');
        $clientControlledId = $this->faker->randomNumber(6, true);
        $title = $this->faker->sentence();
        $description = $this->faker->paragraph();
        $priority = $this->faker->randomElement(TaskPriority::cases());
        $dueDate = $this->faker->dateTimeBetween($currentTime, '+1 month')->format('Y-m-d');
        $isCompleted = $this->faker->boolean();
        $clientTimestamp = $this->faker->dateTimeBetween('-2 years', '-1 year')->format('Y-m-d H:i:s');
        $this->travelTo($currentTime);

        $response = $this->postJson('/api/tasks', [
            'id' => $clientControlledId,
            'title' => "  {$title}  ",
            'description' => $description,
            'priority' => $priority->value,
            'due_date' => $dueDate,
            'is_completed' => $isCompleted,
            'created_at' => $clientTimestamp,
            'updated_at' => $clientTimestamp,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('status', Response::HTTP_CREATED)
            ->assertJsonPath('message', 'Task created successfully.')
            ->assertJsonPath('data.title', $title)
            ->assertJsonPath('data.description', $description)
            ->assertJsonPath('data.priority', $priority->value)
            ->assertJsonPath('data.due_date', $dueDate)
            ->assertJsonPath('data.is_completed', $isCompleted)
            ->assertJsonPath('data.created_at', now()->toISOString())
            ->assertJsonPath('data.updated_at', now()->toISOString())
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'priority',
                    'due_date',
                    'is_completed',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $task = Task::query()->sole();

        expect($task->id)->not->toBe($clientControlledId)
            ->and($task->title)->toBe($title)
            ->and($task->description)->toBe($description)
            ->and($task->priority)->toBe($priority)
            ->and($task->due_date->format('Y-m-d'))->toBe($dueDate)
            ->and($task->is_completed)->toBe($isCompleted)
            ->and($task->created_at->toISOString())->toBe(now()->toISOString())
            ->and($task->updated_at->toISOString())->toBe(now()->toISOString());

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $title,
            'priority' => $priority->value,
        ]);
    });

    it('returns only the first validation error', function (Closure $invalidData, string $message) {
        $response = $this->postJson('/api/tasks', array_merge([
            'title' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'priority' => $this->faker->randomElement(TaskPriority::cases())->value,
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'is_completed' => $this->faker->boolean(),
        ], $invalidData($this->faker)));

        $response
            ->assertUnprocessable()
            ->assertJsonPath('status', Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', $message)
            ->assertJsonCount(2);

        expect(Task::query()->count())->toBe(0);
    })->with([
        'blank title' => [fn ($faker): array => [
            'title' => str_repeat(' ', $faker->numberBetween(1, 10)),
        ], 'The title field is required.'],
        'invalid priority' => [fn ($faker): array => [
            'priority' => $faker->sentence(),
        ], 'The selected priority is invalid.'],
        'invalid date' => [fn ($faker): array => [
            'due_date' => $faker->date('d/m/Y'),
        ], 'The due date field must match the format Y-m-d.'],
        'non-boolean completion' => [fn ($faker): array => [
            'is_completed' => $faker->sentence(),
        ], 'The is completed field must be true or false.'],
    ]);
});

describe('update', function () {
    it('updates every supplied task field', function () {
        $task = Task::factory()->create();
        $updatedData = [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(TaskPriority::cases()),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'is_completed' => $this->faker->boolean(),
        ];

        $response = $this->patchJson("/api/tasks/{$task->id}", [
            ...$updatedData,
            'priority' => $updatedData['priority']->value,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonPath('message', 'Task updated successfully.')
            ->assertJsonPath('data.title', $updatedData['title'])
            ->assertJsonPath('data.description', $updatedData['description'])
            ->assertJsonPath('data.priority', $updatedData['priority']->value)
            ->assertJsonPath('data.due_date', $updatedData['due_date'])
            ->assertJsonPath('data.is_completed', $updatedData['is_completed']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $updatedData['title'],
            'description' => $updatedData['description'],
            'priority' => $updatedData['priority']->value,
        ]);

        $task->refresh();

        expect($task->due_date->format('Y-m-d'))->toBe($updatedData['due_date'])
            ->and($task->is_completed)->toBe($updatedData['is_completed']);
    });

    it('updates only supplied fields during a partial update', function () {
        $task = Task::factory()->create();
        $originalTitle = $task->title;
        $originalDescription = $task->description;
        $originalPriority = $task->priority;
        $originalDueDate = $task->due_date?->format('Y-m-d');
        $updatedIsCompleted = ! $task->is_completed;

        $this->patchJson("/api/tasks/{$task->id}", ['is_completed' => $updatedIsCompleted])
            ->assertOk()
            ->assertJsonPath('status', 200)
            ->assertJsonPath('message', 'Task updated successfully.')
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', $originalTitle)
            ->assertJsonPath('data.description', $originalDescription)
            ->assertJsonPath('data.priority', $originalPriority->value)
            ->assertJsonPath('data.due_date', $originalDueDate)
            ->assertJsonPath('data.is_completed', $updatedIsCompleted);

        $task->refresh();

        expect($task->title)->toBe($originalTitle)
            ->and($task->description)->toBe($originalDescription)
            ->and($task->priority)->toBe($originalPriority)
            ->and($task->due_date?->format('Y-m-d'))->toBe($originalDueDate)
            ->and($task->is_completed)->toBe($updatedIsCompleted);
    });

    it('returns 422 for invalid supplied values', function () {
        $task = Task::factory()->create();
        $originalPriority = $task->priority;
        $invalidPriority = $this->faker->sentence();

        $this->patchJson("/api/tasks/{$task->id}", ['priority' => $invalidPriority])
            ->assertUnprocessable()
            ->assertJsonPath('status', Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonPath('message', 'The selected priority is invalid.')
            ->assertJsonCount(2);

        expect($task->refresh()->priority)->toBe($originalPriority);
    });
});

describe('destroy', function () {
    it('deletes the task and returns the success envelope', function () {
        $task = Task::factory()->create();

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertModelMissing($task);
    });

    it('returns 404 when the task does not exist', function () {
        $missingTaskId = $this->faker->randomNumber();

        $this->deleteJson("/api/tasks/{$missingTaskId}")
            ->assertNotFound()
            ->assertJsonPath('status', Response::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Task not found.')
            ->assertJsonCount(2);
    });
});

it('returns task values with the required JSON types and formats', function () {
    $dueDate = $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d');
    $task = Task::factory()->create([
        'due_date' => $dueDate,
        'is_completed' => $this->faker->boolean(),
    ]);

    $response = $this->getJson("/api/tasks/{$task->id}");

    $response
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->whereType('status', 'integer')
            ->whereType('message', 'string')
            ->whereType('data.id', 'integer')
            ->whereType('data.title', 'string')
            ->whereType('data.priority', 'string')
            ->whereType('data.due_date', 'string')
            ->whereType('data.is_completed', 'boolean')
            ->whereType('data.created_at', 'string')
            ->whereType('data.updated_at', 'string')
            ->etc());

    expect($response->json('data.due_date'))->toMatch('/^\d{4}-\d{2}-\d{2}$/')
        ->and($response->json('data.created_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T.*Z$/')
        ->and($response->json('data.updated_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T.*Z$/');
});

it('allows CORS preflight requests from the configured frontend origin', function () {
    $this->call('OPTIONS', '/api/tasks', server: [
        'HTTP_ORIGIN' => 'http://localhost:5173',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});

it('returns only the required 500 structure for unexpected API errors', function () {
    $exceptionMessage = $this->faker->sentence();

    Route::get('/api/testing/server-error', function () use ($exceptionMessage): never {
        throw new RuntimeException($exceptionMessage);
    });

    $this->getJson('/api/testing/server-error')
        ->assertInternalServerError()
        ->assertJsonPath('status', Response::HTTP_INTERNAL_SERVER_ERROR);
});
