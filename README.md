# mister-quiz

![PHP Version](https://img.shields.io/badge/PHP-8.0.30-777BB4.svg)
![Laravel](https://img.shields.io/badge/Laravel-8.33.0-FF2D20.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.4.11-4479A1.svg)
![Docker](https://img.shields.io/badge/Docker-Compose_v5.2.0-2496ED.svg)
![License](https://img.shields.io/badge/License-MIT-brightgreen.svg)

[![01](https://img.shields.io/badge/zone01-Athens-916ADE?&labelColor=181717&style=for-the-badge&logo=data:image/svg%2Bxml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTEyIDJMMiA3bDEwIDUgMTAtNS0xMC01eiIvPjxwYXRoIGQ9Ik0yIDE3bDEwIDUgMTAtNU0yIDEybDEwIDUgMTAtNSIvPjwvc3ZnPg==)](https://github.com/01-edu/public/tree/master/subjects/mister-quiz)

A quiz game website built with PHP and Laravel. Players register, answer questions from five categories (Art, History, Geography, Science, Sports), earn XP, climb ranks and compete on a leaderboard. The development completed the 01-edu's starter project with pages and models.

## Features

- **Accounts**: register with a unique email and password confirmation, log in (a clear error on wrong details), log out.
- **Homepage**: 3 buttons for guests (Login, Leaderboard, Start Quiz), 4 for logged-in players (Profile, Leaderboard, Start Quiz, Logout).
- **Quiz**: all questions on one page, at least one from each category. Every question must be answered before **Submit**. The quiz stays the same after a refresh or a new **Start Quiz** click, until it is submitted.
- **Results**: right answers out of the total, and right answers per category.
- **Profile**: username, email, XP, rank, and for each category the `x/y` score and the percentage. Only the owner can see it.
- **Leaderboard**: the top 10 players by XP, with their total right answers.

| XP | Rank |
|---|---|
| less than 1500 | Quiz Aprentice |
| 1500 to 4999 | Average Quizer |
| 5000 to 9999 | Epic Quizer |
| 10000 or more | Quiz Master |

The subject lists 5000 and 10000 in two ranks each; the higher rank wins.

## Requirements

Docker Engine with Docker Compose, or Podman with `podman compose`. Everything else runs in containers: `PHP 8.0.30`, `Composer 2.9.5`, `Laravel 8.33.0`, `MySQL 8.4.11` and `phpMyAdmin 5.2.3`.

The starter uses Laravel 8.33.0, which fails on PHP 8.1 and newer. Do not install a local PHP or MySQL, and do not run `composer update`.

## Installation

### Docker (Linux)

One command builds and prepares everything: it installs the dependencies, creates `.env` and the app key, and runs the migrations.

```bash
git clone https://platform.zone01.gr/git/hmim/mister-quiz.git
cd mister-quiz
docker compose up -d --build

# load the 01-edu questions (safe to rerun)
docker compose exec app php artisan db:seed
```

The first start takes a few minutes. Check the progress with `docker compose logs -f app`.

### Zone01 lab PCs — Podman setup without wrappers

Run these commands from a terminal. PHP and Composer run inside containers; they do not need to be installed on the host.

1. **Enable the Podman socket:**
```
systemctl --user enable --now podman.socket
```

2. **Clone the repository and check out dev:**  
```
git clone --branch dev https://platform.zone01.gr/git/hmim/mister-quiz.git
cd mister-quiz
```
3. **Create .env only if it does not already exist:**
```
[ -f .env ] || cp .env.example .env
```
4. **Build the application image:**
```
podman compose build app
```
5. **Install PHP dependencies:**
```
podman compose run --rm app composer install
```
- If it stops with `HTTP/2 429` and `git was not found in your PATH`, GitHub has rate-limited the lab network (the lab PCs share one public IP). Wait a few minutes and run the command again.
- To avoid the error for good, add `git` to the image. When a zip download is refused, Composer falls back to cloning the package with `git`, which the image does not include:
  1. In `Dockerfile`, change:    
  `apt-get install -y --no-install-recommends unzip libzip-dev`     
  to   
   `apt-get install -y --no-install-recommends git unzip libzip-dev`.
  2. Rebuild the image: `podman compose build app`
  3. Run step 5 again.

6. **Generate the application key — first installation only:**
```
podman compose run --rm app php artisan key:generate
```
7. **Start the services:**
```
podman compose up -d
podman compose ps
```   
- Wait until db is healthy and app is Up, then continue:

8. **Apply database migrations:**
```
podman compose exec app php artisan migrate --force
```
9. **Load the questions and answers:**
```
podman compose exec app php artisan db:seed --force
```
10. **Run the tests:**
```
podman compose exec app php artisan test
```
11. **Open the application using the host port shown by podman compose ps, normally http://localhost:8000.**

- The external Compose provider message is informational.   
- Do not manually import the SQL file, use the seeder.


### Optional: Create test players for the leaderboard
After completing the setup above, with the containers running, create 12 test players with different XP amounts:
```
podman compose exec app php artisan tinker --execute='foreach (range(1, 12) as $i) { App\Models\User::factory()->create(["xp" => $i * 500]); }'
```
- Run this command once: each execution creates 12 additional users.  
There is no need to run the question seeder again.
- Open `Leaderboard` in the browser. It should show the top 10 players ordered by XP, highest first.
- These players have 500–6000 XP. Their category scores remain 0/0, so this checks XP ordering and the top 10 limit, but does not verify different total correct-answer counts.

---

## Usage

| Service | URL | Notes |
|---|---|---|
| Website | http://localhost:8000 | Port set by `APP_PORT` in `.env` |
| phpMyAdmin | http://localhost:8080 | User `mister_quiz`, password `secret` |
| MySQL | inside Docker only | Host `db`, port `3306`, database `mister_quiz` |

```bash
docker compose up -d          # start
docker compose ps             # status
docker compose logs -f app    # app logs
docker compose down           # stop (the database is kept)
```

With Podman, write `podman compose` instead of `docker compose`.

## Testing

```bash
docker compose exec app php artisan test
```

Feature and unit tests cover every page and rule above. They run on SQLite in memory (set in `phpunit.xml`), so they never touch the `mister_quiz` database. The manual audit steps are in [`_docs/audit-cases.md`](_docs/audit-cases.md).

## Error Handling

- Form errors show next to the field; the typed values stay, except passwords.
- Guests who open a protected page go to `/login`. Another player's quiz answers `403`.
- `405` means the address exists, but not for this method (`GET /quiz/c`: `/quiz/<any text>` exists for `POST` only; `POST /profile`: `/profile` exists for `GET` only).
- `404` means the app has no such address at all (`/nonexistent`, `/profile/1`, `/quiz/c/23`, `/quiz/1/answers`).

All cases are listed in [`_docs/error-cases.md`](_docs/error-cases.md).

## Project Structure

```text
mister-quiz
├── app/
│   ├── Http/Controllers/         # Auth/, Questions/, Profile, Leaderboard
│   └── Models/                   # User, Question, Answer, Quiz, Question_Quiz
├── database/
│   ├── data/questions.json       # the 01-edu questions and answers
│   ├── migrations/
│   └── seeders/                  # QuestionSeeder
├── docker/entrypoint.sh          # composer install, .env, key, migrations
├── resources/views/              # Blade pages, components, error pages
├── routes/web.php                # web routes
├── tests/                        # Feature and Unit tests
├── _docs/                        # subject, audit, PRD, guides
├── docker-compose.yml            # app (8000), db (MySQL), phpMyAdmin (8080)
└── Dockerfile                    # PHP 8.0.30 + Composer 2.9.5
```

## Team

- **hmim**
- **ltzokas**

## Documentation

- [Subject](_docs/mister-quiz.md) · [Official audit checklist](_docs/mister-quiz_audit.md)
- [PRD](_docs/PRD.md)
- [Audit Cases](_docs/audit-cases.md) · [Error Cases](_docs/error-cases.md) · [Edge Cases](_docs/edge-cases.md)
- [Installation Guide (Greek)](_docs/Installation-guide.md)

---
*This project is part of the Zone01 Athens curriculum.  
Laravel is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).*
