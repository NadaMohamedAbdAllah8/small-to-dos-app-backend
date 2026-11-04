# Small To-Dos API

A Laravel REST API for managing a shared task list. Users log in with email and password, receive a Laravel Sanctum bearer token, and use that token to access the task endpoints.

## Features

- Sanctum bearer-token authentication
- Login throttling at five attempts per minute for each email and IP combination
- Create, read, update, and delete task endpoints
- Task validation with consistent JSON error responses
- SQLite configuration for local development
- Pest feature and service tests

## Technology

- PHP 8.3+
- Laravel 13
- Laravel Sanctum 4
- SQLite
- Pest 4

## Local Setup

Install PHP 8.3 or newer, Composer, and the PHP SQLite extension. Node.js is only required if you want to build the included frontend assets.

From the project directory, install the PHP dependencies:

```bash
composer install
```

Create the environment file. On macOS or Linux:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Create the SQLite database if it does not already exist, generate the application key, and migrate the database:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan key:generate
php artisan migrate --seed
```

The seeder creates three example tasks and this local account:

| Field | Value |
| --- | --- |
| Email | `test@example.com` |
| Password | `password` |

Start the API on the port configured by `.env.example`:

```bash
php artisan serve --host=127.0.0.1 --port=8081
```

The API base URL is `http://127.0.0.1:8081/api`.

To install and build the optional frontend assets:

```bash
npm install
npm run build
```

## Authentication

Send the seeded credentials to the login endpoint:

```bash
curl --request POST http://127.0.0.1:8081/api/login \
    --header "Accept: application/json" \
    --header "Content-Type: application/json" \
    --data '{"email":"test@example.com","password":"password"}'
```

A successful login returns a new non-expiring Sanctum token:

```json
{
  "status": 200,
  "message": "Logged in successfully",
  "data": {
    "token": "1|plain-text-token",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com"
    }
  }
}
```

Pass the token to every task request:

```text
Authorization: Bearer 1|plain-text-token
```

Log out by sending the current bearer token to the logout endpoint:

```bash
curl --request POST http://127.0.0.1:8081/api/logout \
    --header "Accept: application/json" \
    --header "Authorization: Bearer 1|plain-text-token"
```

Logout revokes only the token used for that request. Other tokens issued to the same user remain valid. There is currently no registration endpoint, and authenticated users access the same shared task collection.

## API Endpoints

| Method | Endpoint | Authentication | Description |
| --- | --- | --- | --- |
| `POST` | `/api/login` | No | Validate credentials and issue a token |
| `POST` | `/api/logout` | Bearer token | Revoke the current token |
| `GET` | `/api/tasks` | Bearer token | List tasks, newest first |
| `POST` | `/api/tasks` | Bearer token | Create a task |
| `GET` | `/api/tasks/{task}` | Bearer token | Retrieve a task |
| `PUT/PATCH` | `/api/tasks/{task}` | Bearer token | Update supplied task fields |
| `DELETE` | `/api/tasks/{task}` | Bearer token | Delete a task |

### Task Fields

| Field | Create | Update | Rules |
| --- | --- | --- | --- |
| `title` | Required | Optional | String, maximum 255 characters; cannot be null when supplied |
| `description` | Optional | Optional | Nullable string |
| `priority` | Required | Optional | `low`, `medium`, or `high`; cannot be null when supplied |
| `due_date` | Optional | Optional | Nullable date in `YYYY-MM-DD` format |
| `is_completed` | Optional | Optional | Boolean; defaults to `false` and cannot be null when supplied |

### Create a Task

```bash
curl --request POST http://127.0.0.1:8081/api/tasks \
    --header "Accept: application/json" \
    --header "Authorization: Bearer 1|plain-text-token" \
    --header "Content-Type: application/json" \
    --data '{
      "title": "Review the API documentation",
      "description": "Check the authentication examples",
      "priority": "high",
      "due_date": "2026-11-10",
      "is_completed": false
    }'
```

### List Tasks

```bash
curl http://127.0.0.1:8081/api/tasks \
    --header "Accept: application/json" \
    --header "Authorization: Bearer 1|plain-text-token"
```

### Update a Task

```bash
curl --request PATCH http://127.0.0.1:8081/api/tasks/1 \
    --header "Accept: application/json" \
    --header "Authorization: Bearer 1|plain-text-token" \
    --header "Content-Type: application/json" \
    --data '{"is_completed":true}'
```

### Delete a Task

```bash
curl --request DELETE http://127.0.0.1:8081/api/tasks/1 \
    --header "Accept: application/json" \
    --header "Authorization: Bearer 1|plain-text-token"
```

Successful deletion returns `204 No Content`.

## Response Format

Successful responses other than deletion use this envelope:

```json
{
  "status": 200,
  "message": "Tasks retrieved successfully.",
  "data": []
}
```

A task resource contains:

```json
{
  "id": 1,
  "title": "Review the task list",
  "description": null,
  "priority": "low",
  "due_date": null,
  "is_completed": false,
  "created_at": "2026-11-03T17:00:00.000000Z",
  "updated_at": "2026-11-03T17:00:00.000000Z"
}
```

Errors use a status and message without a data field:

```json
{
  "status": 401,
  "message": "Unauthenticated."
}
```

Common API status codes are:

| Status | Meaning |
| --- | --- |
| `200` | Request succeeded |
| `201` | Task created |
| `204` | Task deleted; response has no body |
| `401` | Credentials or bearer token are invalid |
| `404` | Task was not found |
| `422` | Request validation failed |
| `429` | Login rate limit exceeded |
| `500` | Unexpected server error |

## CORS

API requests are accepted from the origin configured by `FRONTEND_URL`. The default is `http://localhost:5173`. CORS preflight requests remain public, while task operations require a bearer token.

## Testing and Code Style

The test suite uses an in-memory SQLite database:

```bash
php artisan test --compact
```

Run a focused test file:

```bash
php artisan test --compact tests/Feature/Http/Controllers/Api/TaskAuthenticationTest.php
```

Format changed PHP files with Laravel Pint:

```bash
vendor/bin/pint --dirty --format agent
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
