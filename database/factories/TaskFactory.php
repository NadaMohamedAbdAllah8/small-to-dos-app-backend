<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional()->date('Y-m-d', '+1 month'),
            'is_completed' => fake()->boolean(),
        ];
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TaskPriority::Low,
        ]);
    }

    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TaskPriority::Medium,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TaskPriority::High,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_completed' => true,
        ]);
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_completed' => false,
        ]);
    }

    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes): array => [
            'description' => null,
        ]);
    }

    public function withoutDueDate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_date' => null,
        ]);
    }
}
