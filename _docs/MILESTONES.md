# Milestones and Issues

Index of every issue on [platform.zone01.gr/git/hmim/mister-quiz](https://platform.zone01.gr/git/hmim/mister-quiz/issues), grouped by milestone and by module.

Issues are created by [`scripts/gitea-setup.sh`](../scripts/gitea-setup.sh). It matches issues by title, so running it again creates nothing new.

**39 issues · 1 closed · 38 open · 9 milestones · 10 modules**

> **In short**
>
> - Every issue title starts with its **module**, like `[auth]`.
> - A **milestone** says *when*. A **module** says *where in the code*.
> - **One peer per module.** Two people never work in the same module at once.
> - Branch name = module: `feat/auth`, `feat/quiz`. Details in [Workflow](#workflow).
> - **34 blocked-by links** are set in Gitea. An issue with no blocker is ready to start. See [Blocked issues](#4-blocked-issues-announce-themselves).

---

## Modules

Every issue title starts with its module. The issue list then groups by area of the code, and no label is spent on it.

| Module | Where it lives |
|---|---|
| `env` | `Dockerfile`, `docker-compose.yml`, `docker/`, `composer.lock`, `.env.example` |
| `db` | `database/migrations/`, `database/factories/`, `database/seeders/`, the table side of `app/Models/` |
| `auth` | `app/Http/Controllers/Auth/`, `resources/views/auth/`, `app/Models/User.php` |
| `home` | `routes/web.php` (route names), `resources/views/home.blade.php` |
| `quiz` | `app/Http/Controllers/Questions/QuestionController.php` (start, show, submit), `Quiz`, `Question`, `Answer`, `resources/views/questions/list.blade.php`, `resources/views/components/question.blade.php` |
| `results` | counting right answers, XP and category scores, `resources/views/questions/results.blade.php` |
| `profile` | `app/Http/Controllers/ProfileController.php`, `resources/views/profile.blade.php` |
| `leaderboard` | `app/Http/Controllers/LeaderboardController.php`, `resources/views/leaderboard.blade.php` |
| `test` | `tests/`, `phpunit.xml` |
| `docs` | `README.md`, `_docs/` |

Labels stay at four: `frontend` · `backend` · `bug` · `good-first-task`.

---

## M0 — Environment & shared schema

Same Docker environment for both teammates (PHP 8.0.30, Laravel 8.33.0, MySQL 8.4.11), shared database schema review, question data. Must be done before feature work.

| # | Issue | Module | Labels |
|---|---|---|---|
| [1](https://platform.zone01.gr/git/hmim/mister-quiz/issues/1) | Docker dev environment (PHP 8.0.30, Laravel 8.33.0, MySQL 8.4.11) ✅ | `env` | — |
| [2](https://platform.zone01.gr/git/hmim/mister-quiz/issues/2) ✅ | Set up the environment on both PCs and post the checklist | `env` | — |
| [3](https://platform.zone01.gr/git/hmim/mister-quiz/issues/3) ✅ | Review the shared database schema before feature work | `db` | backend |
| [4](https://platform.zone01.gr/git/hmim/mister-quiz/issues/4) | Question data: seeder for the 01-edu questions ✅ implementation/docs; PR review pending | `db` | backend |

Issues **#3** and **#4** are the base for everything else. #3 blocks **#10**, **#30** and **#35** in Gitea: agree on the schema before anybody writes a migration.

## M1 — Auth (register, login, logout)

Audit: register with confirmation and unique email, login with an error on wrong credentials, logout.

| # | Issue | Module | Labels |
|---|---|---|---|
| [6](https://platform.zone01.gr/git/hmim/mister-quiz/issues/6) | Login does not sign the user in and never shows an error | `auth` | bug · backend |
| [7](https://platform.zone01.gr/git/hmim/mister-quiz/issues/7) | Registering with an email that already exists crashes | `auth` | bug · backend |
| [8](https://platform.zone01.gr/git/hmim/mister-quiz/issues/8) | Logout works with a plain GET link | `auth` | bug · backend |
| [15](https://platform.zone01.gr/git/hmim/mister-quiz/issues/15) | Register: username, email, password and confirmation | `auth` | backend |
| [16](https://platform.zone01.gr/git/hmim/mister-quiz/issues/16) | Login with email and password, error on wrong credentials | `auth` | backend |
| [17](https://platform.zone01.gr/git/hmim/mister-quiz/issues/17) | Logout | `auth` | backend |
| [18](https://platform.zone01.gr/git/hmim/mister-quiz/issues/18) | Register page: form and link to Login | `auth` | frontend · good-first-task |
| [19](https://platform.zone01.gr/git/hmim/mister-quiz/issues/19) | Login page: form, link to Register, error message | `auth` | frontend · good-first-task |

**#6**, **#7** and **#8** are bugs in the starter. #18 and #19 are small and good to start with.

## M2 — Homepage buttons

Audit: 3 buttons for guests (Login, Leaderboard, Start Quiz); 4 buttons for logged-in users (Profile, Leaderboard, Start Quiz, Logout).

| # | Issue | Module | Labels |
|---|---|---|---|
| [5](https://platform.zone01.gr/git/hmim/mister-quiz/issues/5) | Homepage returns 500: routes profile, leaderboard and quiz are not defined | `home` | bug · backend |
| [20](https://platform.zone01.gr/git/hmim/mister-quiz/issues/20) | Routes for Profile, Leaderboard and Start Quiz | `home` | backend |
| [21](https://platform.zone01.gr/git/hmim/mister-quiz/issues/21) | Homepage for guests: Login, Leaderboard, Start Quiz | `home` | frontend |
| [22](https://platform.zone01.gr/git/hmim/mister-quiz/issues/22) | Homepage when logged in: Profile, Leaderboard, Start Quiz, Logout | `home` | frontend |

**#5** is the first thing to fix: the homepage returns a 500 until the three routes exist (#20).

## M3 — Start quiz & persistence

Audit: guests are sent to login; at least 1 question per category; the same quiz survives a refresh or a return to the homepage.

| # | Issue | Module | Labels |
|---|---|---|---|
| [9](https://platform.zone01.gr/git/hmim/mister-quiz/issues/9) | Start Quiz: questions are picked but never shown or saved | `quiz` | bug · backend |
| [10](https://platform.zone01.gr/git/hmim/mister-quiz/issues/10) | Quiz ↔ question table is missing and uses wrong column names | `db` | bug · backend |
| [23](https://platform.zone01.gr/git/hmim/mister-quiz/issues/23) | Start Quiz redirects guests to Login | `quiz` | backend |
| [24](https://platform.zone01.gr/git/hmim/mister-quiz/issues/24) | Pick quiz questions with at least one per category | `quiz` | backend |
| [25](https://platform.zone01.gr/git/hmim/mister-quiz/issues/25) | Keep the same quiz after a refresh or a return to the homepage | `quiz` | backend |

**#10** adds the missing quiz ↔ question table. It needs the schema decision in #3.

## M4 — Question screen & submit rules

Audit: questions show their answers; the quiz cannot be submitted while an answer is missing, and an error message is shown.

| # | Issue | Module | Labels |
|---|---|---|---|
| [11](https://platform.zone01.gr/git/hmim/mister-quiz/issues/11) | Question component shows no answer choices | `quiz` | bug · frontend |
| [26](https://platform.zone01.gr/git/hmim/mister-quiz/issues/26) | Show every question with its answers | `quiz` | frontend |
| [27](https://platform.zone01.gr/git/hmim/mister-quiz/issues/27) | Reject a quiz submit when an answer is missing | `quiz` | backend |
| [28](https://platform.zone01.gr/git/hmim/mister-quiz/issues/28) | Show an error message when an answer is missing | `quiz` | frontend |

**#11** is why no answer choices appear on the quiz page.

## M5 — Results page

Audit: correct/wrong answers in total and per category; user XP and category scores (x/y) are updated.

| # | Issue | Module | Labels |
|---|---|---|---|
| [12](https://platform.zone01.gr/git/hmim/mister-quiz/issues/12) | XP stays 0, category scores never change, totals are hardcoded | `results` | bug · backend |
| [29](https://platform.zone01.gr/git/hmim/mister-quiz/issues/29) | Count right answers in total and per category | `results` | backend |
| [30](https://platform.zone01.gr/git/hmim/mister-quiz/issues/30) | Update XP and category scores after a quiz | `results` | backend |
| [31](https://platform.zone01.gr/git/hmim/mister-quiz/issues/31) | Page layout | `results` | frontend |

**#12** is the reason XP and category scores never change.

## M6 — Profile page

Audit: rank from XP, correct-answer percentage per category, only the user can see their own profile.

| # | Issue | Module | Labels |
|---|---|---|---|
| [32](https://platform.zone01.gr/git/hmim/mister-quiz/issues/32) | Rank from XP | `profile` | backend |
| [33](https://platform.zone01.gr/git/hmim/mister-quiz/issues/33) | Right answers, total and percentage per category | `profile` | backend |
| [34](https://platform.zone01.gr/git/hmim/mister-quiz/issues/34) | Page layout and access | `profile` | frontend |

**#32** and **#33** wait for team decisions. See [Decisions before code](#7-decisions-before-code).

## M7 — Leaderboard

Audit: top 10 users by XP with username, XP and total correct answers.

| # | Issue | Module | Labels |
|---|---|---|---|
| [35](https://platform.zone01.gr/git/hmim/mister-quiz/issues/35) | Top 10 users by XP | `leaderboard` | backend |
| [36](https://platform.zone01.gr/git/hmim/mister-quiz/issues/36) | Table: username, XP, total right answers | `leaderboard` | frontend |

**#35** waits for #3 (how total right answers are stored) and for the tie-order decision.

## M8 — Testing & polish

Feature tests, a visual check against the audit checklist, code review before merging dev into main.

| # | Issue | Module | Labels |
|---|---|---|---|
| [13](https://platform.zone01.gr/git/hmim/mister-quiz/issues/13) | UserFactory uses a name column that does not exist | `db` | bug · backend |
| [14](https://platform.zone01.gr/git/hmim/mister-quiz/issues/14) | Tests would run against the development database | `test` | bug · backend |
| [37](https://platform.zone01.gr/git/hmim/mister-quiz/issues/37) | Feature tests: register, login, quiz, results | `test` | backend |
| [38](https://platform.zone01.gr/git/hmim/mister-quiz/issues/38) | Visual check against the audit checklist | `docs` | frontend · good-first-task |
| [39](https://platform.zone01.gr/git/hmim/mister-quiz/issues/39) | Code review before merging dev into main | `docs` | — |

**#13** and **#14** are bugs that block the tests. **#39** is the release: PR `dev` → `main`.

---

## By module

The same issues ordered by module, for picking up a whole area on one branch.

| Module | Issues | Count |
|---|---|---|
| `env` | 1, 2 | 2 |
| `db` | 3, 4, 10, 13 | 4 |
| `auth` | 6, 7, 8, 15, 16, 17, 18, 19 | 8 |
| `home` | 5, 20, 21, 22 | 4 |
| `quiz` | 9, 11, 23, 24, 25, 26, 27, 28 | 8 |
| `results` | 12, 29, 30, 31 | 4 |
| `profile` | 32, 33, 34 | 3 |
| `leaderboard` | 35, 36 | 2 |
| `test` | 14, 37 | 2 |
| `docs` | 38, 39 | 2 |

**Good first tasks:** 18, 19, 38.

---

## Workflow

| Branch | Rule |
|---|---|
| `main` | Protected. Only `hmim` may push, and only docs. Everything else arrives by pull request with 1 approval. A re-push dismisses a stale approval. |
| `dev` | Integration branch. Every feature branch merges here. |
| `feat/<module>` | One branch per module, named after the module column above: `feat/auth`, `feat/quiz`, `feat/profile`. PR into `dev`, closing the issues it covers. |

Commit messages: Conventional Commits, one line, no body and no footer.

---

## How we pick and work issues

### 1. One milestone at a time

Milestones are sequential. We do not open M3 while M2 is still red.

Inside the current milestone, **pick freely**. Across milestones, do not.

### 2. Claim by assigning

Gitea issues are only open or closed, so "someone is working on this" has nowhere else to live.

| Action | What it means |
|---|---|
| Assign yourself | I am on this now |
| Leave unassigned | Free to take |
| Unassign yourself | I stopped, anyone can take it |

Assign **before** you start, not after.

### 3. Split by module, not by issue

Two people picking from the same module means two people editing the same files in the same week.

Take a module each. The split below is a **proposal**, not a rule. Roles are agreed in M0.

| Half | Modules | Issues | Files |
|---|---|---|---|
| Pages | `auth`, `home`, `profile`, `leaderboard` | 17 | each page has its own controller and view |
| Quiz engine | `db`, `quiz`, `results` | 16 | `QuestionController`, models, migrations |

`env`, `test` and `docs` are shared. Anything in `env` needs both members to agree.

### 4. Blocked issues announce themselves

The blocking order is recorded in Gitea. A blocked issue shows what it waits for and cannot be closed before its blocker.

| Blocked | Waits for | Why |
|---|---|---|
| 9 | 23, 25, 26 | umbrella report: closes after guests are redirected, the quiz is saved and the questions are shown |
| 10 | 3 | one table and one set of names, agreed in the schema review |
| 12 | 29, 30, 31 | umbrella report: closes after counting, XP and scores, and the results page |
| 15 | 7 | the register checklist includes the duplicate-email fix |
| 16 | 6 | login must authenticate before the feature can be checked |
| 17 | 8 | logout must be a POST route first |
| 18 | 15 | the register page shows the errors the backend produces |
| 19 | 16 | the login page shows the error the backend produces |
| 20 | 5 | the homepage bug adds the missing route names first |
| 21, 22 | 20 | the buttons call `route('leaderboard')`, `route('quiz')`, `route('profile')` |
| 23 | 20 | the `auth` middleware goes on the quiz route |
| 24 | 4 | category names must match the seeded data |
| 25 | 10 | saving a quiz needs the quiz ↔ question table |
| 26 | 11, 25 | the page needs answer choices and a saved quiz to show |
| 27 | 25 | the check compares the answers with the saved quiz |
| 28 | 27 | the message shows the rejection from the submit check |
| 29 | 25 | counts the answers of the saved quiz |
| 30 | 3, 29 | needs the counts, and how totals are stored |
| 31 | 29 | shows the counts |
| 34 | 32, 33 | the page shows the rank and the percentages |
| 35 | 3 | how total right answers are stored is decided in the review |
| 36 | 35 | the table shows what the query returns |
| 37 | 13, 14 | no tests without a working factory and a test database |
| 38 | 37 | the visual check runs after the tests are green |
| 39 | 38 | the release PR `dev` → `main` comes after the visual check |

Two rules explain the direction of the arrows:

- A **starter bug** (#5, #6, #7, #8, #10, #11) is fixed first. The feature issue that says "Starter: #N" follows it.
- **#9** and **#12** are umbrella reports. They end with "Expected: see the M-issues", so they close **last**.

Not listed on purpose: anything the milestone order already guarantees.

Two pairs touch the same code, so one pull request can cover both: **#5 with #20** (the routes) and **#7 with #15** (the register controller). Gitea refuses to close a blocked issue while its blocker is open. If one of them stays open after the merge, close it by hand.

If an issue shows no blocker, it is ready to start. Today these are: **#2, #3, #4, #5, #6, #7, #8, #11, #13, #14, #32, #33** (and #1 is done).

### 5. Start here

The `good-first-task` issues are small and self-contained: **#18**, **#19** and **#38**.

### 6. One PR can close several issues

Write `Closes #6, #16` in the PR description (the blocker first). Open a PR when a batch of issues is ready.

### 7. Decisions before code

Some issues settle a rule that other issues depend on. Write the answer in the issue thread first. Then write the code.

| Issue | Decides |
|---|---|
| #3 | the schema: the quiz ↔ question table, the `score` field, how total right answers are stored, whether the chosen answers are kept |
| #22 | the text of the Profile button: the starter shows the username, the audit says "Profile" |
| #32 | the rank limits at 5000 and 10000, and the spelling of the ranks |
| #33 | the rounding of the percentage |
| #35 | the order when two users have the same XP |

#3 is the expensive one. It blocks #10, #30 and #35 directly, and everything after them.

### 8. Definition of done

An issue closes when all of it is true:

- every checkbox in the issue body is ticked
- the audit case it names still passes, if it names one
- the pull request into `dev` is approved
- nothing else broke: the homepage still loads after a fresh `docker compose up -d`
