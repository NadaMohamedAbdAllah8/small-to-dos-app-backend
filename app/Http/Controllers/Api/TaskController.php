<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\RespondsWithJson;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    use RespondsWithJson;

    public function __construct(private readonly TaskService $taskService) {}

    public function index(): JsonResponse
    {
        return $this->respondWithJson(
            TaskResource::collection($this->taskService->getAll()),
            'Tasks retrieved successfully.',
            Response::HTTP_OK,
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated());

        return $this->respondWithJson(
            new TaskResource($task),
            'Task created successfully.',
            Response::HTTP_CREATED,
        );
    }

    public function show(Task $task): JsonResponse
    {
        return $this->respondWithJson(
            new TaskResource($task),
            'Task retrieved successfully.',
            Response::HTTP_OK,
        );
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->update($task, $request->validated());

        return $this->respondWithJson(
            new TaskResource($task),
            'Task updated successfully.',
            Response::HTTP_OK,
        );
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->delete($task);

        return $this->respondWithJson(
            null,
            'Task deleted successfully.',
            Response::HTTP_OK,
        );
    }
}
