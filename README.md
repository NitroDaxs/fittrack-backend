# FitTrack — API

The REST API behind FitTrack, a fitness tracking web app. It serves the exercise
library, training routines and articles, stores each member's workouts and body
measurements, computes dashboard statistics, and powers the admin console and
the chat assistant.

The React front end lives in
[fittrack-frontend](https://github.com/NitroDaxs/fittrack-frontend).

## Stack

PHP 8.3+ · Laravel 13 · Laravel Sanctum · MySQL · Scramble (OpenAPI docs)

## Features

- **Token authentication** with Sanctum: register, log in, log out
- **Two roles**, `member` and `admin`, with admin routes behind their own
  middleware
- **Exercise library** of around 300 seeded exercises, each with muscle groups,
  equipment, difficulty, instructions and a video
- **Routines** with day-by-day workouts, and **articles** on nutrition,
  recovery and mindset
- **Workout logging** with per-set weight and reps, plus body measurements
- **Dashboard metrics**: consistency heatmap, per-exercise strength progression
  and estimated one-rep max
- **Favourites** for exercises and routines
- **Admin CRUD** for exercises, routines, articles and users, with overview
  stats and an activity feed
- **Chat assistant** that answers questions from the database (see below)

## Getting started

Requires PHP 8.3 or newer, Composer and a MySQL server.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database and set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`
in `.env`. Then create the tables, load the seed data and start the server:

```bash
php artisan migrate --seed
php artisan serve
```

The API is served at http://localhost:8000/api.

MySQL is required: one migration changes an `ENUM` column with MySQL-specific
syntax, so SQLite and PostgreSQL will not run the migrations as they stand.

### Seeded accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@test.com` | `password123` |
| Member | `member@test.com` | `password123` |

To give the member account a realistic training history, so the dashboard and
history screens have data to show:

```bash
php artisan db:seed --class=MemberDataSeeder
```

## API overview

All routes are prefixed with `/api`.

| Area | Routes | Access |
| --- | --- | --- |
| Auth | `POST /register`, `POST /login`, `POST /logout` | Public (logout needs a token) |
| Catalog | `GET /exercises`, `/routines`, `/articles`, each with `/{key}` | Public |
| Taxonomies | `GET /taxonomies` | Public |
| Chat | `POST /chat` | Public, 10 requests per minute per IP |
| Profile | `GET /me`, `PUT /me`, `GET /me/favorites` | Member |
| Favourites | `POST` and `DELETE` on `/exercises/{id}/save` and `/routines/{id}/save` | Member |
| Dashboard | `GET /me/dashboard`, `GET /me/exercises/{id}/progression` | Member |
| Workouts | `GET /me/workouts`, `POST /me/workouts`, `GET /me/workouts/{id}` | Member |
| Measurements | `GET /me/measurements`, `POST /me/measurements` | Member |
| Admin | `/admin/exercises`, `/admin/routines`, `/admin/articles`, `/admin/users`, `/admin/stats`, `/admin/activity` | Admin |

Authenticated requests send the token from `/login` or `/register` as
`Authorization: Bearer <token>`.

Interactive documentation is generated from the code by Scramble. With the
server running locally, open http://localhost:8000/docs/api.

## Chat assistant

The language model never answers from its own knowledge. Its only job is to
classify the question into a topic and a few values (muscle group, equipment,
goal and so on) as JSON. Every exercise, routine, article and number in the
reply is then looked up in the database, so the assistant cannot invent an
exercise that does not exist or a body weight the user never logged. Questions
about a member's own data require a token.

The model host is configurable:

| `CHAT_PROVIDER` | Host | Settings |
| --- | --- | --- |
| `ollama` (default) | A local [Ollama](https://ollama.com) server | `OLLAMA_URL`, `OLLAMA_MODEL` |
| `openai` | Any OpenAI-compatible API, for example Groq | `CHAT_API_URL`, `CHAT_API_KEY`, `CHAT_MODEL` |

For local use, install Ollama and pull the default model:

```bash
ollama pull llama3.1:8b
```

## Configuration

Beyond the standard Laravel settings, `.env` supports:

| Variable | Purpose |
| --- | --- |
| `CORS_ALLOWED_ORIGINS` | Comma-separated front-end origins allowed to call the API. Defaults to `http://localhost:5173` |
| `CHAT_PROVIDER` and the chat variables above | Which model host the chat assistant uses |

## Project structure

```
app/
  Enums/                    Roles, difficulty, exercise and article categories
  Http/Controllers/Api/     Public and member endpoints
  Http/Controllers/Api/Admin/   Admin endpoints
  Http/Middleware/          EnsureUserIsAdmin
  Http/Resources/           Response shaping for articles
  Models/                   Eloquent models
database/
  migrations/               Schema
  seeders/                  Seeders, with exercise data in seeders/data/exercises
routes/api.php              All API routes
```

Exercise seed data is one file per body part; its format is documented in
`database/seeders/data/exercises/README.md`.

## Deployment

`vercel.json` and `api/index.php` configure the app for Vercel's community PHP
runtime. That setup is experimental and has not been verified in production.
