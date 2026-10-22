# Audit Cases: mister-quiz

This document outlines the standard audit cases to verify the core functional requirements and execution behavior of the `mister-quiz` project. Every web case quotes the matching question of the [official audit checklist](mister-quiz_audit.md). A milestone is done when its cases pass.

**Setup for every web case:** the stack runs (`docker compose up -d`), the 01-edu questions are loaded (`docker compose exec app php artisan db:seed`), and the browser starts logged out at `http://localhost:8000`.

---

### Summary Table of Audit Cases

#### Part 1: Environment & CLI Audit Cases
| Case ID | Description | Input Argument(s) | Expected Behavior / Output |
|:---:|---|---|---|
| C01 | Fresh clone starts with one command | `docker compose up -d --build` | 3 services up, `db` healthy, 7 migrations ran |
| C02 | Question data import | `docker compose exec app php artisan db:seed` | 5 categories × 12 questions |
| C03 | Test suite | `docker compose exec app php artisan test` | All tests pass (from M8) |

#### Part 2: Web & HTTP Audit Cases
| Case ID | Milestone | Description | Request Pattern / Trigger | Expected HTTP Status / Output |
|:---:|:---:|---|---|---|
| W01 | M1 | Register a new user | `POST /register` | `302` → `/`, user logged in |
| W02 | M1 | Log in with email and password | `POST /login` | `302` → `/`, user logged in |
| W03 | M1 | Links between Login and Register, Logout | `GET /login`, `GET /register`, `POST /logout` | Links present; logout → guest |
| W04 | M2 | Guest homepage has 3 buttons | `GET /` (guest) | `200 OK` + Login, Leaderboard, Start Quiz |
| W05 | M2 | Logged-in homepage has 4 buttons | `GET /` (logged in) | `200 OK` + Profile, Leaderboard, Start Quiz, Logout |
| W06 | M3 | Guest clicks Start Quiz | `GET /quiz` (guest) | `302` → `/login` |
| W07 | M4 | Quiz shows questions and answers | `GET /quiz` (logged in) | `200 OK` + every question with its answers |
| W08 | M4 | Submit with a missing answer | `POST /quiz/{quiz}` (incomplete) | Not submitted, error shown |
| W09 | M3 | At least one question per category | `GET /quiz` | Art, History, Geography, Science, Sports all present |
| W10 | M5 | Results page after a full submit | `POST /quiz/{quiz}` (complete) | Right/total + right per category |
| W11 | M6 | Profile page content | `GET /profile` | Username, email, XP, rank, per-category stats |
| W12 | M7 | Leaderboard has the top 10 by XP | `GET /leaderboard` | Max 10 rows, XP descending |
| W13 | M7 | Leaderboard row content | `GET /leaderboard` | Username, XP, total right answers |
| W14 | M3 | Same quiz after refresh or return | `GET /quiz` twice | Same questions both times |

---

## Part 1: Environment & CLI Audit Cases

### Audit Case C01: Fresh Clone Starts with One Command
**Description:**  
Tests that the school audit PC (Linux + Docker) can run the project from a clean clone. No local PHP, Composer or MySQL is needed. The `app` entrypoint installs dependencies, creates `.env`, generates the key and runs the migrations.

---

**Execution Environment:** Linux terminal with Docker Engine and Docker Compose, empty folder.

**Input Argument:**
```bash
git clone https://platform.zone01.gr/git/hmim/mister-quiz.git
cd mister-quiz
docker compose up -d --build
docker compose ps
docker compose exec app php artisan migrate:status
```

**Expected Output:**
```plaintext
NAME                        SERVICE      STATUS
mister-quiz-app-1           app          Up
mister-quiz-db-1            db           Up (healthy)
mister-quiz-phpmyadmin-1    phpmyadmin   Up

| Yes | 2014_10_12_000000_create_users_table          | 1 |
| ... (7 rows, all "Yes")                               |
```

**Validation Rules:**
- **Services:** `app`, `db` and `phpmyadmin` are running; `db` is `healthy`.
- **Migrations:** all 7 migrations (more after M3) show `Yes`.
- **Pages:** `http://localhost:8000/login` returns `200 OK`.
- **File ownership:** files created in the folder (`vendor/`, `.env`) belong to the user who cloned the repo, not `root`.
- **No port clash:** MySQL has no published port, so a MySQL server on the PC does not block the start.

---

### Audit Case C02: Question Data Import
**Description:**  
Tests that the seeder loads the 01-edu questions (saved in `database/data/questions.json`) into the `question` and `answer` tables. The quiz cases need this data.

---

**Execution Environment:** Linux terminal, stack from C01 running.

**Input Argument:**
```bash
docker compose exec app php artisan db:seed
docker compose exec -T db mysql -umister_quiz -psecret -e "select category, count(*) from question group by category order by category" mister_quiz
```

**Expected Output:**
```plaintext
category    count(*)
Art         12
Geography   12
History     12
Science     12
Sports      12
```

**Validation Rules:**
- **Categories:** exactly the 5 subject categories.
- **Answers:** `select count(*) from answer` returns `240` (4 answers per question), with exactly 1 correct answer per question.
- **Run twice:** the seeder uses `updateOrInsert`, so a second `db:seed` changes nothing: still 60 questions and 240 answers, with exactly 1 correct answer per question (checked on a fresh clone). The old SQL import duplicated the rows (see [Error Case E04](error-cases.md#error-case-e04-question-data-imported-twice)).

---

### Audit Case C03: Test Suite
**Description:**  
Tests that the automated tests pass and use a separate test database, not `mister_quiz`.

---

**Execution Environment:** Linux terminal, stack from C01 running.

**Input Argument:**
```bash
docker compose exec app php artisan test
```

**Expected Output:**
```plaintext
   PASS  Tests\Unit\...
   PASS  Tests\Feature\...

  Tests:  N passed
```

**Validation Rules:**
- **Exit Status:** the command exits with status `0`.
- **Isolation:** after the run, the `users` table in `mister_quiz` has the same rows as before.
- **Before M2:** the starter test for `/` fails with `Route [leaderboard] not defined`. This is known (issue #5).

---

## Part 2: Web & HTTP Audit Cases

### Audit Case W01: Register a New User
> *Can you register a new user providing a `username`, an `email`, a `password` and a `password confirmation`?*

**Description:**  
Tests the registration form with valid data. The new user is saved, signed in and sent to the homepage.

---

**Execution Environment:** Browser, logged out.

**Request (Browser):**
```plaintext
1. Open http://localhost:8000/register
2. username: auditor1
   email: auditor1@example.com
   password: secret123
   password confirmation: secret123
3. Click "Register"
```

**Expected Output:**
```http
POST /register  →  HTTP/1.1 302 Found
Location: http://localhost:8000/

GET /  →  HTTP/1.1 200 OK   (homepage with Profile and Logout buttons)
```

**Validation Rules:**
- **Stored:** `users` has a row with `username = auditor1`, a hashed password (starts with `$2y$`), `xp = 0`, and every category column `0/0`.
- **Signed in:** the homepage shows the logged-in buttons (W05).
- **Fields:** the form has exactly the 4 required inputs.

---

### Audit Case W02: Log In with Email and Password
> *Can you log in a user providing its `email` and `password`?*

**Description:**  
Tests login with the correct email and password of an existing user.

---

**Execution Environment:** Browser, logged out, user from W01 exists.

**Request (Browser):**
```plaintext
1. Open http://localhost:8000/login
2. email: auditor1@example.com
   password: secret123
3. Click "Login"
```

**Expected Output:**
```http
POST /login  →  HTTP/1.1 302 Found
Location: http://localhost:8000/
```

**Validation Rules:**
- **Signed in:** the homepage shows 4 buttons (W05).
- **Wrong details:** the same form with a wrong password stays on `/login` and shows `These credentials do not match our records.` (see [Error Case E06](error-cases.md#error-case-e06-wrong-login-details)).
- **Fields:** login asks only for `email` and `password`.

---

### Audit Case W03: Navigation between Login and Register, and Logout
**Description:**  
Tests the subject rule "In the Login page, it must be possible to navigate to the Registration page, and vice versa", and that Logout ends the session.

---

**Execution Environment:** Browser.

**Request (Browser):**
```plaintext
1. On /login click "Don't have an account? Register"
2. On /register click the link back to Login
3. Log in, then click "Logout" on the homepage
```

**Expected Output:**
```http
Step 1 → GET /register  HTTP/1.1 200 OK
Step 2 → GET /login     HTTP/1.1 200 OK
Step 3 → POST /logout   HTTP/1.1 302 Found  Location: /
```

**Validation Rules:**
- **Links:** both pages link to each other.
- **Logout:** after step 3 the homepage shows the 3 guest buttons (W04).
- **Method:** logout is a `POST` form with a CSRF token; `GET /logout` does not log out (issue #8).

---

### Audit Case W04: Guest Homepage Buttons
> *While not logged in, are there three buttons (`Login` button, `Leaderboard` button and `Start Quiz` button) in the main menu?*

**Description:**  
Tests the homepage for a visitor who is not logged in.

---

**Execution Environment:** Browser, logged out.

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/
```

**Expected Output:**
```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8

... Login ... Leaderboard ... Start Quiz ...
```

**Validation Rules:**
- **Status:** `200 OK` (today it is `500`, issue #5).
- **Buttons:** exactly 3: `Login`, `Leaderboard`, `Start Quiz`.
- **Hidden:** no `Profile` and no `Logout` button.

---

### Audit Case W05: Logged-In Homepage Buttons
> *While logged in, are there four buttons (`Profile` button, `Leaderboard` button, `Start Quiz` button and `Logout` button) in the main menu?*

**Description:**  
Tests the homepage for a logged-in player.

---

**Execution Environment:** Browser, logged in as `auditor1`.

**Request (Browser):**
```plaintext
Open http://localhost:8000/
```

**Expected Output:**
```http
HTTP/1.1 200 OK

... Profile ... Leaderboard ... Start Quiz ... Logout ...
```

**Validation Rules:**
- **Buttons:** exactly 4: `Profile`, `Leaderboard`, `Start Quiz`, `Logout`.
- **Hidden:** no `Login` button.
- **Label:** the Profile button text is agreed in issue #22 (the word "Profile" or the username).

---

### Audit Case W06: Guest Clicks Start Quiz
> *Start a quiz clicking on the `Start Quiz` button while not logged in. Were you redirected to the `Login` page?*

**Description:**  
Tests that a guest cannot start a quiz.

---

**Execution Environment:** Browser or terminal, logged out.

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/quiz
```

**Expected Output:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/login
```

**Validation Rules:**
- **Redirect:** the browser ends on the Login page.
- **No quiz:** no new row in `quizzes`.

---

### Audit Case W07: Quiz Shows Questions and Answers
> *Log in and start a new quiz. Were you presented the questions and the available answers for each question?*

**Description:**  
Tests that the quiz page lists every question with all of its answer choices and one Submit button at the bottom.

---

**Execution Environment:** Browser, logged in.

**Request (Browser):**
```plaintext
Click "Start Quiz" on the homepage
```

**Expected Output:**
```http
HTTP/1.1 200 OK

Question 1 text  ( ) answer A  ( ) answer B  ( ) answer C  ( ) answer D
Question 2 text  ...
...
[Submit]
```

**Validation Rules:**
- **Answers:** each question shows its 4 answers as choices where only one can be picked (radio buttons, issue #11).
- **Stored:** a new unfinished row exists in `quizzes` for this user, with its questions linked (issue #10).
- **Submit:** one Submit button below the last question.

---

### Audit Case W08: Submit with a Missing Answer
> *Try to submit the quiz without answering all of the questions. Were you unable to do submit the quiz?*

**Description:**  
Tests that an incomplete quiz is not accepted. The browser blocks it, and the server also rejects it.

---

**Execution Environment:** Browser, logged in, quiz open.

**Request (Browser):**
```plaintext
Answer all questions except one, then click "Submit"
```

**Expected Output:**
```plaintext
Browser: "Please select one of these options." on the missing question (HTML required)
Server (browser check bypassed): HTTP/1.1 302 Found → back to /quiz with an error message
```

**Validation Rules:**
- **Not submitted:** the quiz stays unfinished (`completed = 0`); XP and category scores do not change.
- **Answers kept:** the chosen answers are still selected after the error.
- **Server check:** the rule holds without the browser check (see [Error Case E11](error-cases.md#error-case-e11-quiz-submitted-with-a-missing-answer)).

---

### Audit Case W09: At Least One Question per Category
> *Does the quiz contain at least one question of each category?*

**Description:**  
Tests the question selection. The starter picks 4 random questions from each of the 5 categories (20 questions).

---

**Execution Environment:** Browser, logged in, quiz open; or phpMyAdmin.

**Request (Browser):**
```plaintext
Open the quiz and read the category of each question
```

**Expected Output:**
```plaintext
Art ≥ 1, History ≥ 1, Geography ≥ 1, Science ≥ 1, Sports ≥ 1
```

**Validation Rules:**
- **Coverage:** all 5 categories appear in every new quiz.
- **Visible:** each question shows its category, so the auditor can check without the database.
- **No duplicates:** the same question does not appear twice in one quiz.

---

### Audit Case W10: Results Page
> *After answering all the questions try to submit the quiz. Were you redirected to a results page, which shows how many answers were right, the number of total questions made and how many questions you answered right in each category?*

**Description:**  
Tests the submit of a complete quiz and the results page.

---

**Execution Environment:** Browser, logged in, quiz open.

**Request (Browser):**
```plaintext
Answer every question, then click "Submit"
```

**Expected Output:**
```http
HTTP/1.1 200 OK   (or 302 → results page, then 200 OK)

Right answers: 13 / 20
Art: 3 / 4   History: 2 / 4   Geography: 4 / 4   Science: 1 / 4   Sports: 3 / 4
```

**Validation Rules:**
- **Totals:** the total is the real number of questions in the quiz, not a fixed number (issue #12).
- **XP:** the user's XP grows by the sum of the XP of the right answers, counted once.
- **Category scores:** each `x/y` column grows by the right answers (`x`) and the answered questions (`y`) of that category.
- **Finished:** the quiz is marked `completed = 1`; the next Start Quiz creates a new quiz.

---

### Audit Case W11: Profile Page
> *Navigate to the `Profile` page. Are the following all present? : the user username, the email, the xp, the correct rank (according to the README), the percentage of correct answers for each category, the number of correct answers for each category and the number of total questions answered for each category?*

**Description:**  
Tests the profile of the logged-in player after at least one quiz.

---

**Execution Environment:** Browser, logged in, one quiz submitted.

**Request (Browser):**
```plaintext
Click "Profile" on the homepage
```

**Expected Output:**
```http
HTTP/1.1 200 OK

auditor1 · auditor1@example.com · 1250 XP · Quiz Aprentice
Art        3 right / 4 answered   75%
History    2 right / 4 answered   50%
...
```

**Validation Rules:**
- **Rank:** matches the rank table in the [README](../README.md#profile-page) and the limits in [EC01](edge-cases.md#edge-case-ec01-rank-boundaries).
- **Numbers:** per category, right + answered + percentage match the `x/y` column.
- **Privacy:** the page shows only the logged-in player's data; guests are redirected to `/login` ([Error Case E12](error-cases.md#error-case-e12-guest-opens-a-protected-page)).

---

### Audit Case W12: Leaderboard Top 10
> *Navigate to the `Leaderboard` page. Is there a table consisting of the top 10 players organized by XP amount?*

**Description:**  
Tests the leaderboard with more than 10 users.

---

**Execution Environment:** Browser; at least 11 users with different XP (for example from a seeder or factory).

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/leaderboard
```

**Expected Output:**
```http
HTTP/1.1 200 OK

<table> with 10 rows, XP from highest to lowest
```

**Validation Rules:**
- **Count:** exactly 10 rows when 10 or more users exist.
- **Order:** XP descending; ties follow [EC09](edge-cases.md#edge-case-ec09-equal-xp-on-the-leaderboard).
- **Access:** the page opens for guests and logged-in players.

---

### Audit Case W13: Leaderboard Row Content
> *Does the table contain in each row, the username of the player, the XP amount of the player, and the total correct answers of the player?*

**Description:**  
Tests the columns of each leaderboard row.

---

**Execution Environment:** Browser, same data as W12.

**Request (Browser):**
```plaintext
Open http://localhost:8000/leaderboard
```

**Expected Output:**
```plaintext
| # | Username | XP   | Right answers |
| 1 | auditor1 | 1250 | 13            |
```

**Validation Rules:**
- **Columns:** username, XP, total right answers.
- **Total right answers:** equals the sum of `x` over the 5 category columns (or the stored total, as agreed in issue #3).
- **No private data:** no email and no password hash.

---

### Audit Case W14: Same Quiz after Refresh or Return
**Description:**  
Tests the subject rule: "After starting the quiz, if the user refreshes the page the same questions should pop up. The same must happen if the user goes back to the homepage and start the quiz again." It is not a checklist question, but the subject requires it.

---

**Execution Environment:** Browser, logged in.

**Request (Browser):**
```plaintext
1. Click "Start Quiz" and note the questions
2. Refresh the page (F5)
3. Go to the homepage and click "Start Quiz" again
```

**Expected Output:**
```plaintext
Steps 1, 2 and 3 show the same questions in the same order.
```

**Validation Rules:**
- **One open quiz:** the user has at most one unfinished row in `quizzes`.
- **Survives logout:** the open quiz comes back after logging out and in again ([EC05](edge-cases.md#edge-case-ec05-open-quiz-after-logout-or-session-expiry)).
- **New quiz only after submit:** after W10, Start Quiz creates a new quiz.
