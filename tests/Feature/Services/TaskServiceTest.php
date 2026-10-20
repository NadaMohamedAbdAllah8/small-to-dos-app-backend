<?php

use App\Enums\TaskPriority;
use App\Models\Task;
use App\Services\TaskService;

it('returns all tasks newest first with a stable id tie breaker', function () {
    $oldestCreatedAt = $this->faker->dateTimeBetween('-3 weeks', '-2 weeks');
    $newestCreatedAt = $this->faker->dateTimeBetween('-1 week', 'now');
    $oldestTask = Task::factory()->create(['created_at' => $oldestCreatedAt]);
    $firstNewestTask = Task::factory()->create(['created_at' => $newestCreatedAt]);
    $secondNewestTask = Task::factory()->create(['created_at' => $newestCreatedAt]);

    $tasks = (new TaskService)->getAll();

    expect($tasks->modelKeys())->toBe([
        $secondNewestTask->id,
        $firstNewestTask->id,
        $oldestTask->id,
    ]);
});

it('creates and returns a persisted task', function () {
    $taskData = [
        'title' => $this->faker->sentence(),
        'description' => $this->faker->paragraph(),
        'priority' => $this->faker->randomElement(TaskPriority::cases())->value,
        'due_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        'is_completed' => $this->faker->boolean(),
    ];

    $task = (new TaskService)->create($taskData);

    $this->assertModelExists($task);
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => $taskData['title'],
        'priority' => $taskData['priority'],
    ]);

    expect($task->description)->toBe($taskData['description'])
        ->and($task->priority->value)->toBe($taskData['priority'])
        ->and($task->due_date->format('Y-m-d'))->toBe($taskData['due_date'])
        ->and($task->is_completed)->toBe($taskData['is_completed']);
});

it('updates only supplied task fields and returns the refreshed task', function () {
    $task = Task::factory()->create();
    $originalDescription = $task->description;
    $originalDueDate = $task->due_date?->format('Y-m-d');
    $originalIsCompleted = $task->is_completed;
    $updatedPriority = $this->faker->randomElement(array_values(array_filter(
        TaskPriority::cases(),
        fn (TaskPriority $priority): bool => $priority !== $task->priority,
    )));
    $updatedTitle = $this->faker->sentence();

    $updatedTask = (new TaskService)->update($task, [
        'title' => $updatedTitle,
        'priority' => $updatedPriority->value,
    ]);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => $updatedTitle,
        'description' => $originalDescription,
        'priority' => $updatedPriority->value,
    ]);

    expect($updatedTask->id)->toBe($task->id)
        ->and($updatedTask->title)->toBe($updatedTitle)
        ->and($updatedTask->description)->toBe($originalDescription)
        ->and($updatedTask->priority)->toBe($updatedPriority)
        ->and($updatedTask->due_date?->format('Y-m-d'))->toBe($originalDueDate)
        ->and($updatedTask->is_completed)->toBe($originalIsCompleted);
});

it('deletes the supplied task', function () {
    $task = Task::factory()->create();

    (new TaskService)->delete($task);

    $this->assertModelMissing($task);
});
