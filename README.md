# mister-quiz

![PHP Version](https://img.shields.io/badge/PHP-8.0.30-777BB4.svg)
![Laravel](https://img.shields.io/badge/Laravel-8.33.0-FF2D20.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.4.11-4479A1.svg)
![Docker](https://img.shields.io/badge/Docker-Compose_v5.2.0-2496ED.svg)
![License](https://img.shields.io/badge/License-MIT-brightgreen.svg)
![Build Status](https://img.shields.io/badge/Build-InProcess-yellow.svg)
![Test Coverage](https://img.shields.io/badge/Coverage-0%25-darkred.svg)

[![01](https://img.shields.io/badge/zone01-Athens-916ADE?&labelColor=181717&style=for-the-badge&logo=data:image/svg%2Bxml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTEyIDJMMiA3bDEwIDUgMTAtNS0xMC01eiIvPjxwYXRoIGQ9Ik0yIDE3bDEwIDUgMTAtNU0yIDEybDEwIDUgMTAtNSIvPjwvc3ZnPg==)](https://github.com/01-edu/public/tree/master/subjects/mister-quiz)

A quiz game website built with PHP and Laravel. Players register, answer questions from five categories, earn XP, climb ranks and compete on a leaderboard.

> 🚧 **Status: in development.** This README is the **temporary team guideline**. It will become the final project README at Milestone 8.

## Description

We finish a quiz website that 01-edu started for us (the **starter**). The starter has pages and models, but the main features are missing or broken.

`mister-quiz` has five parts:

1. **Accounts**: register, log in, log out.
2. **Homepage**: shows different buttons for guests and for logged-in players.
3. **Quiz**: a list of questions with answer choices, submitted all at once.
4. **Results and Profile**: right answers, XP, rank and scores per category.
5. **Leaderboard**: the top 10 players by XP.

## Subject Understanding

> Short version of [the subject](_docs/mister-quiz.md). One idea per line. **Bold** = a word you will see in code.

### The game in 6 steps

| Step | The player… | Page |
|:---:|---|---|
| 1 | creates an account or logs in | Register / Login |
| 2 | sees the buttons on the homepage | Homepage |
| 3 | clicks **Start Quiz** and answers every question | Quiz |
| 4 | clicks **Submit** and sees the score | Results |
| 5 | checks their XP, rank and statistics | Profile |
| 6 | compares themselves with other players | Leaderboard |

### Accounts

- **Register** needs 4 things: `username`, `email`, `password`, `password confirmation`.
- **Login** needs 2 things: `email`, `password`.
- Wrong login details → the Login page shows an **error message**.
- Login page has a link to Register. Register page has a link to Login.
- After login → the player goes to the **homepage**.

### Homepage buttons

| Button | Guest (not logged in) | Logged in |
|---|:---:|:---:|
| Login | ✅ | ❌ |
| Profile | ❌ | ✅ |
| Leaderboard | ✅ | ✅ |
| Start Quiz | ✅ | ✅ |
| Logout | ❌ | ✅ |
| **Total** | **3** | **4** |

### Quiz rules

- A guest clicks **Start Quiz** → goes to the **Login** page. No quiz starts.
- A logged-in player clicks **Start Quiz** → sees all questions and their answers on one page.
- Every question has 3 things:
  - the question text
  - a **category**: `Art`, `History`, `Geography`, `Science` or `Sports`
  - an **XP** value
- The quiz has **at least 1 question from each category**.
- The player must answer **every** question. Otherwise **Submit** does not work.
- The quiz **stays the same** until it is submitted:
  - after a page refresh → same questions
  - after going back to the homepage and clicking **Start Quiz** again → same questions

### Scoring

- Right answer → player **XP** goes up by that question's **XP**.
- Wrong answer → XP does not change.
- Each category has a score written as **`x/y`**:
  - `x` = right answers in that category
  - `y` = all answered questions in that category
- Example: `3/4` in Art = 4 Art questions answered, 3 of them right.

### Results page

Shows, after **Submit**:

- right answers and total questions (example: `14/20`)
- right answers in each category

### Profile page

- Only the player can see **their own** profile.
- It shows:
  - username
  - email
  - XP
  - rank (table below)
  - for each category: right answers, total answered, percentage right

| XP | Rank (spelling from the subject) |
|---|---|
| less than 1500 | Quiz Aprentice |
| 1500 to 5000 | Average Quizer |
| 5000 to 10000 | Epic Quizer |
| 10000 or more | Quiz Master |

> ⚠️ The subject lists 5000 and 10000 in **two** ranks each. The team decides the exact limits in issue **#32**. See [Edge Case EC01](_docs/edge-cases.md#edge-case-ec01-rank-boundaries).

### Leaderboard page

- A table with the **top 10** players, highest XP first.
- Each row: **username**, **XP**, **total right answers**.

## Team & Roles

| Member | Area | Main work |
|---|---|---|
| **hmim** | To be agreed in M0 | Environment (Docker), Gitea, documentation |
| **ltzokas** | To be agreed in M0 | — |

The work splits into two areas. Each Gitea issue has the label of its area.

- **Frontend** (label `frontend`): Blade views, forms, buttons, error messages, tables, CSS.
- **Backend** (label `backend`): routes, controllers, migrations, models, validation, quiz and XP logic.

Both members build the **database schema together** first (issue #3). Nobody changes it alone later.

Inside a milestone, we **split by module**, not by issue, so we never edit the same files in the same week. Every issue title starts with its module, like `[auth]`. How we choose and claim work: [`MILESTONES.md`](_docs/MILESTONES.md#how-we-pick-and-work-issues).

## Installation

> 📖 The full, step-by-step guide is [`_docs/Installation-guide.md`](_docs/Installation-guide.md) (in Greek). **Follow it exactly.** Use only the versions below.

### Required versions

| Tool | Version | Where it runs |
|---|---|---|
| Ubuntu | 26.04 LTS | the PC (Linux) or WSL2 (Windows) |
| Docker Engine | 29.6.1 | Linux: `docker-ce` · Windows: Docker Desktop 4.81.0 |
| Docker Compose | v5.2.0 | with Docker |
| Git | 2.53.0 | the PC / WSL2 |
| PHP | **8.0.30** | Docker only |
| Composer | 2.9.5 | Docker only |
| Laravel Framework | **8.33.0** | locked in `composer.lock` |
| MySQL | 8.4.11 | Docker only |
| phpMyAdmin | 5.2.3 | Docker only |

**Why PHP 8.0.30?** The starter uses Laravel 8.33.0. It fails on PHP 8.1 and newer. PHP 8.0 no longer installs on current Ubuntu, so it runs in Docker for everyone.

❌ **Do not** install XAMPP, a local PHP or a local MySQL. **Do not** run `composer update`.

### Team setup (short version)

```bash
git clone https://platform.zone01.gr/git/hmim/mister-quiz.git
cd mister-quiz
git switch dev
cp .env.example .env
docker compose build app

# php and composer commands that run inside Docker
mkdir -p ~/.local/bin
ln -s "$PWD/docker/bin/php" ~/.local/bin/php
ln -s "$PWD/docker/bin/composer" ~/.local/bin/composer
# open a new terminal, then:
composer install
php artisan key:generate
docker compose up -d
```

Then run `php artisan db:seed` (step 8 of the guide) and post the verification checklist in issue **#2**. The seeder can be rerun without creating duplicates.

### Audit / fresh clone (Linux + Docker)

One command builds and prepares everything. It installs dependencies, creates `.env` and the app key, and runs the migrations.

```bash
git clone https://platform.zone01.gr/git/hmim/mister-quiz.git
cd mister-quiz
docker compose up -d --build

# load the 01-edu questions (safe to rerun)
php artisan db:seed
```

The first start takes a few minutes. Check the progress with `docker compose logs -f app`.

## Usage

| Service | URL | Notes |
|---|---|---|
| Website | http://localhost:8000 | Port set by `APP_PORT` in `.env` |
| phpMyAdmin | http://localhost:8080 | User `mister_quiz`, password `secret` |
| MySQL | inside Docker only | Host `db`, port `3306`, database `mister_quiz` |

Everyday commands:

```bash
docker compose up -d          # start
docker compose ps             # status
docker compose logs -f app    # app logs
docker compose down           # stop (the database is kept)
php artisan route:list        # list the routes
php artisan migrate           # run new migrations
php artisan test              # run the tests
```

## Features

- **Authentication**: register with password confirmation and a unique email; login with a clear error on wrong details; logout.
- **Dynamic homepage**: 3 buttons for guests, 4 buttons for logged-in players.
- **Persistent quiz**: the unfinished quiz is saved in the database. A refresh or a new **Start Quiz** click shows the same questions.
- **Complete submit only**: the server rejects a quiz with a missing answer.
- **Scoring**: XP and the `x/y` category scores update after every submit.
- **Profile**: rank from XP and per-category statistics, visible only to the owner.
- **Leaderboard**: top 10 players by XP with their total right answers.

## Architecture

The project uses Laravel's **MVC** pattern. Scoring rules live in a small service class, so they can be tested without views.

- **`routes/web.php`**: all web routes. The `auth` middleware protects the quiz and profile routes.
- **`app/Http/Controllers/`**:
  - `Auth/`: `RegisterController`, `LoginController`, `LogoutController`
  - `Questions/QuestionController`: start or resume a quiz, submit it, show the results
  - `ProfileController`, `LeaderboardController`
- **`app/Models/`**: `User`, `Question`, `Answer`, `Quiz`, `Question_Quiz`.
- **`app/Services/`** (planned): quiz picking, XP and rank calculation, for example a `QuizScoringService`.
- **`database/migrations/`**: `users` (+ `xp` and the five `x/y` category columns), `question`, `answer`, `quizzes`. The quiz ↔ question table is still missing (issue #10).
- **`resources/views/`**: Blade pages (`home`, `auth/*`, `questions/*`, `profile`, `leaderboard`) and the `components/question` component.

### Planned routes

| Method | Path | Access | Purpose |
|---|---|---|---|
| GET | `/` | everyone | Homepage |
| GET / POST | `/register` | guests | Register form / create account |
| GET / POST | `/login` | guests | Login form / sign in |
| POST | `/logout` | logged in | Sign out |
| GET | `/quiz` | logged in (guests → `/login`) | Start or resume the quiz |
| POST | `/quiz/{quiz}` | owner of the quiz | Submit answers → results |
| GET | `/profile` | logged in (guests → `/login`) | Own profile |
| GET | `/leaderboard` | everyone | Top 10 players |

Final paths and names are fixed in issues **#20** and **#25**. The full contract is in the [PRD](_docs/PRD.md#3-system-contract).

## Error Handling

- **Form errors** (register, login, quiz submit): the page reloads and shows a message next to the field. The typed values stay, except passwords.
- **Guests on protected pages**: redirect to `/login`.
- **Unknown pages**: `404 Not Found`. **Wrong HTTP method**: `405 Method Not Allowed`. **Expired form**: `419 Page Expired`.
- **Server errors**: `500` page. Stack traces appear only when `APP_DEBUG=true` (local development).
- **Never** a crash on known input, for example a duplicate email (issue #7).

All cases are listed in [`_docs/error-cases.md`](_docs/error-cases.md).

### 404 or 405? Why `/quiz/c` is not a 404

Laravel asks two questions, in this order:

1. **Is there a route with this address?** If not: `404 Not Found`.
2. **Does that route accept the method of the request?** If not: `405 Method Not Allowed`. The reply has an `Allow` header that lists the methods that would work.

The address `/quiz/{quiz}` exists, but only for `POST` (the Submit button of the quiz). `{quiz}` matches any text, also `c`. A browser address bar sends `GET`, so `GET /quiz/c` passes question 1 and fails question 2. `/profile/1` has no route at all, so it fails question 1.

| Request | Status | Why |
|---|---|---|
| `GET /nonexistent`, `/profile/1`, `/leaderboard/1`, `/quiz/1/results` | `404` | no route has this address |
| `GET /quiz/c`, `GET /quiz/1` | `405`, `Allow: POST` | `/quiz/{quiz}` exists, for `POST` only |
| `PUT` or `DELETE /quiz/1` | `405` | the address exists, the method is not allowed |
| `POST /`, `/quiz`, `/profile`, `/leaderboard` | `405` | these pages only read (`GET`) |
| `POST /quiz/c`, `/quiz/0`, `/quiz/999999` (logged in) | `404` | the route matches, but no quiz has this id |
| `POST /quiz/{quiz of another user}` | `403` | the quiz exists, but it is not yours |

We keep `405` on purpose: it is the correct HTTP answer, and it tells the client which method works. The `405` page looks like the `404` page.

## Testing

- **Feature tests** (`tests/Feature/`): register, login, homepage buttons, quiz start, submit rules, results, profile, leaderboard.
- **Unit tests** (`tests/Unit/`): rank limits, `x/y` updates, percentage with `0/0`.
- **Test database**: tests must not touch `mister_quiz`. Issue #14 enables the in-memory SQLite lines in `phpunit.xml`.
- **Manual audit**: the [audit cases](_docs/audit-cases.md) follow the official checklist line by line.

```bash
php artisan test
```

> ℹ️ Today the starter test for `/` fails (`Route [leaderboard] not defined`). This is expected until Milestone 2.

## Milestones

Every milestone matches a part of the audit checklist. A milestone is **done** when its audit cases pass.

| # | Milestone | Gitea issues | Audit cases |
|:---:|---|---|---|
| M0 | Environment & shared schema | #1 ✅, #2, #3, #4 | C01–C03 |
| M1 | Auth (register, login, logout) | #15–#19 · bugs #6, #7, #8 | W01–W03 |
| M2 | Homepage buttons | #20–#22 · bug #5 | W04, W05 |
| M3 | Start quiz & persistence | #23–#25 · bugs #9, #10 | W06, W09, W14 |
| M4 | Question screen & submit rules | #26–#28 · bug #11 | W07, W08 |
| M5 | Results page | #29–#31 · bug #12 | W10 |
| M6 | Profile page | #32–#34 | W11 |
| M7 | Leaderboard | #35, #36 | W12, W13 |
| M8 | Testing & polish | #37–#39 · bugs #13, #14 | all |

Issues **#5–#14** are bugs we found in the starter code. Each one links to the exact lines.

Every issue by milestone and by module: [`_docs/MILESTONES.md`](_docs/MILESTONES.md).

## Gitea Workflow

- **Repository**: https://platform.zone01.gr/git/hmim/mister-quiz (private).
- **Branches**:

  | Branch | Purpose | Rules |
  |---|---|---|
  | `main` | Stable, audited code | Protected. Only `hmim` pushes, and only docs. Everything else by PR with 1 approval |
  | `dev` | Integration of finished issues | Target of every feature PR |
  | `feat/<module>` | One module | Created from `dev`, example `feat/auth` |

  The modules are `env`, `db`, `auth`, `home`, `quiz`, `results`, `profile`, `leaderboard`, `test`, `docs`. Every issue title starts with one.

- **Flow for one module**:
  1. Assign the issue to yourself **before** you start.
  2. `git switch dev && git pull`
  3. `git switch -c feat/auth`
  4. Commit small steps. Push the branch.
  5. Open a PR into `dev`. Write `Closes #6, #16` in the description. One PR can close several issues.
  6. The other member reviews. Merge after approval.
- **Blocked issues** show what they wait for in Gitea. An issue with no blocker is ready to start. The list is in [`MILESTONES.md`](_docs/MILESTONES.md#4-blocked-issues-announce-themselves).
- **End of a milestone**: PR `dev` → `main` with 1 approval (issue #39).
- **Commit messages**: [Conventional Commits](https://www.conventionalcommits.org/).

  ```text
  feat(auth): show an error on wrong login details
  fix(quiz): add XP once per right answer
  docs: update the installation guide
  test(profile): cover rank limits
  ```

- **Labels**: `frontend`, `backend`, `bug`, `good-first-task`.
- **Changes to the environment** (`Dockerfile`, `docker-compose.yml`, `docker/`, `composer.*`, `.env.example`): issue + PR + approval from **both** members.

## Project Structure

```text
mister-quiz
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/                 # Register, Login, Logout
│   │   ├── Questions/            # QuestionController (quiz + results)
│   │   ├── LeaderboardController.php
│   │   └── ProfileController.php
│   └── Models/                   # User, Question, Answer, Quiz, Question_Quiz
├── database/
│   ├── factories/                # UserFactory
│   ├── migrations/               # users, question, answer, quizzes
│   └── seeders/                  # DatabaseSeeder (question seeder: issue #4)
├── docker/
│   ├── bin/                      # php and composer wrappers (PHP 8.0.30 in Docker)
│   └── entrypoint.sh             # composer install, .env, key, migrations
├── public/                       # CSS, JS, images
├── resources/
│   ├── lang/en/                  # validation and auth messages
│   └── views/                    # Blade pages and components
├── routes/web.php                # web routes
├── scripts/
│   └── gitea-setup.sh            # labels, milestones and issues (idempotent)
├── tests/                        # Feature and Unit tests
├── _docs/                        # subject, audit, guides, PRD, test cases
├── .env.example                  # shared settings (DB_HOST=db)
├── docker-compose.yml            # app (8000), db (MySQL 8.4.11), phpMyAdmin (8080)
├── Dockerfile                    # php:8.0.30-cli-bullseye + Composer 2.9.5
└── README.md
```

## Project Documentation References

- [PRD](_docs/PRD.md)
- [Milestones and Issues](_docs/MILESTONES.md)
- [Audit Cases](_docs/audit-cases.md)
- [Error Cases](_docs/error-cases.md)
- [Edge Cases](_docs/edge-cases.md)
- [Installation Guide (Greek)](_docs/Installation-guide.md)
- [Subject](_docs/mister-quiz.md) · [Official audit checklist](_docs/mister-quiz_audit.md)
- [PHP Best Practices](_docs/PHP-best-practices.md)

---
*This project is part of the Zone01 Athens curriculum. It is built and maintained according to the guidelines in the `_docs/` directory. Laravel is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).*
