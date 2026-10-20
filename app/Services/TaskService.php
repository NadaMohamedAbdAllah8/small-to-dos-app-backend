<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function getAll(): Collection
    {
        return Task::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $taskData): Task
    {
        return Task::query()->create($taskData);
    }

    public function update(Task $task, array $taskData): Task
    {
        $task->update($taskData);

        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
