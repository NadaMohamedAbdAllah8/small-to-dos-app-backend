<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Task::factory()
            ->lowPriority()
            ->incomplete()
            ->withoutDescription()
            ->withoutDueDate()
            ->create(['title' => 'Review the task list']);

        Task::factory()
            ->mediumPriority()
            ->completed()
            ->create(['title' => 'Configure the Laravel backend']);

        Task::factory()
            ->highPriority()
            ->incomplete()
            ->create(['title' => 'Connect React to Laravel']);
    }
}
