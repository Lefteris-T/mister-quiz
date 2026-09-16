# Edge Cases: mister-quiz

This document outlines edge cases specific to `mister-quiz`. These scenarios test the robustness and boundaries of the quiz, scoring, profile and leaderboard logic under unusual conditions. Error cases are not included in this document; see [error-cases.md](error-cases.md).

> **Proposed** = the behavior the team still has to confirm in the linked Gitea issue. Update this file when the decision is made.

---

### Summary Table of Edge Cases

| Case ID | Category | Description | Input Argument(s) | Expected Behavior / Output |
|:---:|---|---|---|---|
| EC01 | Scoring / Data | Rank boundaries (1499, 1500, 4999, 5000, 9999, 10000 XP) | `xp` value on the profile | Half-open ranges; 5000 → Epic Quizer, 10000 → Quiz Master (proposed, #32) |
| EC02 | Scoring / Data | New player with no quiz yet | `GET /profile` (0 XP, all `0/0`) | Quiz Aprentice, `0 / 0`, `0%`, no division by zero |
| EC03 | Scoring / Data | All answers wrong / all answers right | `POST /quiz/{quiz}` | XP +0 / XP + sum of all question XP; every `y` grows |
| EC04 | Scoring / Data | Category with fewer questions than needed | 1–3 questions in one category | Quiz uses all of them; still ≥ 1 per category |
| EC05 | Web / Session | Open quiz after logout or session expiry | Log out, log in, `GET /quiz` | Same unfinished quiz returns |
| EC06 | Web / Concurrency | Quiz submitted twice | Double click, Back + resubmit, 2 tabs | Scored once; second submit changes nothing |
| EC07 | Web / Session | Logged-in player opens Login or Register | `GET /login`, `GET /register` | `302` → `/` |
| EC08 | Web / Data | Leaderboard with fewer than 10 players | 0 or 3 users | 0 users: empty-state text; 3 users: 3 rows |
| EC09 | Web / Data | Equal XP on the leaderboard | Several users with the same XP | Stable order: XP ↓, then username ↑ (proposed, #35) |
| EC10 | Web / Input | HTML or non-Latin text in a username | `<b>x</b>`, `Ελένη` | Shown as plain text; Greek letters display correctly |
| EC11 | Test Regression | Random question selection repeated | 50 new quizzes in a test | Every quiz: 5 categories, no duplicate question |
| EC12 | Test Regression | Category score strings | `"0/0"`, `"7/9"`, `"12/12"` | 0%, 78%, 100%; `x ≤ y` always |

---

## 1. Scoring & Data Boundaries
These cases test the numbers the game stores and shows: XP, ranks, `x/y` category scores and the size of the question pool.

---

### Edge Case EC01: Rank Boundaries
**Description:**  
The subject gives overlapping ranges: `1500 XP - 5000 XP => Average Quizer` and `5000 XP - 10000 XP => Epic Quizer`. A naive implementation with `<=` on both sides gives 5000 XP two ranks, and the result depends on the order of the `if` checks. The first and last rules use `<` and `>=`, so half-open ranges are the most consistent reading.

---

#### Execution Environment: PHPUnit unit test + browser (profile page)

#### Input Argument:
```php
// tests/Unit/RankTest.php (data provider)
[0, 'Quiz Aprentice'], [1499, 'Quiz Aprentice'],
[1500, 'Average Quizer'], [4999, 'Average Quizer'],
[5000, 'Epic Quizer'], [9999, 'Epic Quizer'],
[10000, 'Quiz Master'], [250000, 'Quiz Master'],
```

#### Expected Output:
```plaintext
xp < 1500           → Quiz Aprentice
1500 ≤ xp < 5000    → Average Quizer
5000 ≤ xp < 10000   → Epic Quizer
xp ≥ 10000          → Quiz Master
```

**Validation Rules:**
- **One rank per value:** every XP value maps to exactly one rank (proposed; confirm in issue #32).
- **Single source:** the rank comes from one method (for example in a service class), used by the profile page and the tests.
- **Spelling:** the labels match the subject (`Quiz Aprentice`, `Average Quizer`, `Epic Quizer`) unless issue #32 decides otherwise.

---

### Edge Case EC02: New Player with No Quiz Yet
**Description:**  
A new user has `xp = 0` and every category column is `0/0`. Percentage = `x / y × 100`, so a naive implementation divides by zero and crashes the profile page (`DivisionByZeroError` in PHP 8).

---

#### Execution Environment: Browser, freshly registered user

#### Input Argument:
```plaintext
Register a new user, then open http://localhost:8000/profile
```

#### Expected Output:
```plaintext
newuser · newuser@example.com · 0 XP · Quiz Aprentice
Art        0 right / 0 answered   0%
... (same for the other 4 categories)
```

**Validation Rules:**
- **No crash:** `y = 0` shows `0%` (proposed wording; `—` is also acceptable if agreed in issue #33).
- **Leaderboard:** the new user can appear with `0` XP and `0` right answers when fewer than 10 players have more XP.

---

### Edge Case EC03: All Answers Wrong / All Answers Right
**Description:**  
The two extreme scores. With all answers wrong, XP must not change but `y` must still grow. With all answers right, XP must grow by the sum of all question XP exactly once. The starter code breaks both: it never adds XP and always adds `4` to `y` (issue #12).

---

#### Execution Environment: Browser or feature test, logged in, 20-question quiz

#### Input Argument:
```plaintext
Case A: pick a wrong answer for every question, submit
Case B: pick the right answer for every question, submit
```

#### Expected Output:
```plaintext
Case A: Results 0 / 20 · XP unchanged · each category x unchanged, y + 4
Case B: Results 20 / 20 · XP + (sum of the 20 question XP values) · each category x + 4, y + 4
```

**Validation Rules:**
- **Counted once:** XP is added once per right answer, not twice.
- **Real totals:** `y` grows by the number of questions of that category in this quiz, not a fixed `4`.
- **Invariant:** after any submit, `0 ≤ x ≤ y` in every category column.

---

### Edge Case EC04: Category with Fewer Questions than Needed
**Description:**  
The starter asks for 4 random questions per category with `limit(4)`. If a category has only 1–3 questions (custom data, deleted rows), the query returns fewer. The quiz must still work and still include every category.

---

#### Execution Environment: Feature test with a small seeded question set

#### Input Argument:
```plaintext
Seed: Art 2 questions, other categories 12 questions each
Start a new quiz
```

#### Expected Output:
```plaintext
Quiz has 18 questions: Art 2, History 4, Geography 4, Science 4, Sports 4
```

**Validation Rules:**
- **Coverage kept:** at least 1 question per category.
- **Totals follow the quiz:** results show `/ 18`, not `/ 20`.
- **Zero questions:** a category with no questions at all is an error, not an edge case ([Error Case E05](error-cases.md#error-case-e05-no-questions-in-the-database)).

---

## 2. Web, Session & Concurrency Boundaries
These cases focus on sessions, repeated requests, redirects for logged-in users and data that reaches HTML pages.

---

### Edge Case EC05: Open Quiz after Logout or Session Expiry
**Description:**  
If the open quiz lives only in the session, it disappears after logout or when the session expires, and the player gets new questions. The subject asks for the same questions on refresh and on a new Start Quiz click. Storing the open quiz in the `quizzes` table (`completed = 0`) keeps it across sessions.

---

#### Execution Environment: Browser, logged in

#### Input Argument:
```plaintext
1. Start Quiz, note the questions
2. Logout, then Login again (or delete the session cookie and log in)
3. Start Quiz
```

#### Expected Output:
```plaintext
Step 3 shows the same questions, in the same order, as step 1.
```

**Validation Rules:**
- **One open quiz:** a user never has two rows with `completed = 0`.
- **Stable order:** the order is stored (or derived from stored data), not shuffled on every request.

---

### Edge Case EC06: Quiz Submitted Twice
**Description:**  
A double click on Submit, the browser Back button followed by a new submit, or the same quiz open in two tabs can send the same submit twice. A naive implementation adds the XP and the category scores twice.

---

#### Execution Environment: Browser (2 tabs) or feature test

#### Input Argument:
```plaintext
1. Open the quiz in tab A and tab B
2. Answer everything in both tabs
3. Submit in tab A, then submit in tab B
```

#### Expected Output:
```plaintext
Tab A: results page, XP updated
Tab B: HTTP/1.1 302 Found → homepage (or the tab A results) with a message such as "This quiz is already submitted."
```

**Validation Rules:**
- **Scored once:** XP and category scores change only on the first submit.
- **Atomic update:** marking the quiz `completed` and updating the user happen in one database transaction; the submit only continues if the quiz was still open.
- **No crash:** the second submit never returns `500`.

---

### Edge Case EC07: Logged-In Player Opens Login or Register
**Description:**  
A logged-in player can type `/login` or `/register` in the address bar. Showing the forms again allows a second login or a new account while signed in, which confuses the homepage buttons.

---

#### Execution Environment: Browser, logged in

#### Input Argument:
```bash
# with the session cookie of a logged-in user
curl -i -b cookies.txt http://localhost:8000/login
```

#### Expected Output:
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/
```

**Validation Rules:**
- **Guest-only routes:** `GET` and `POST` of `/login` and `/register` use Laravel's `guest` middleware.
- **Redirect target:** the homepage (`RouteServiceProvider::HOME` must point to `/`).

---

### Edge Case EC08: Leaderboard with Fewer than 10 Players
**Description:**  
A fresh audit database has 0, 1 or a few users. A naive implementation assumes 10 rows, or shows a broken empty table.

---

#### Execution Environment: Browser, database with 0 users, then 3 users

#### Input Argument:
```bash
curl -i http://localhost:8000/leaderboard
```

#### Expected Output:
```plaintext
0 users: HTTP/1.1 200 OK + "No players yet." (proposed text)
3 users: HTTP/1.1 200 OK + table with 3 rows
```

**Validation Rules:**
- **No padding:** no empty or fake rows.
- **No crash:** `200 OK` in both cases.

---

### Edge Case EC09: Equal XP on the Leaderboard
**Description:**  
Several players can have the same XP (for example many new players with `0`). `ORDER BY xp DESC` alone returns ties in any order, so the table can change between reloads and the 10th place can switch players.

---

#### Execution Environment: Browser or feature test, 12 users, 5 of them with 800 XP

#### Input Argument:
```bash
curl -s http://localhost:8000/leaderboard
```

#### Expected Output:
```plaintext
Same table on every reload:
XP descending, then username ascending
```

**Validation Rules:**
- **Deterministic:** the query has a second sort key (proposed: `username` ascending; confirm in issue #35).
- **Still 10 rows:** ties do not add an 11th row.

---

### Edge Case EC10: HTML or Non-Latin Text in a Username
**Description:**  
Usernames appear on the homepage (if agreed in issue #22), the profile and the leaderboard. A username such as `<b>boss</b>` or `<script>` must not change the page. Greek or other UTF-8 letters must display correctly.

---

#### Execution Environment: Browser

#### Input Argument:
```plaintext
Register username: <b>boss</b>
Register username: Ελένη
Open /profile and /leaderboard
```

#### Expected Output:
```plaintext
The page shows the literal text "<b>boss</b>" (not bold)
The page shows "Ελένη"
```

**Validation Rules:**
- **Escaping:** views use `{{ }}`, never `{!! !!}`, for user data.
- **Encoding:** the database uses `utf8mb4` (Laravel 8 default), so non-Latin letters are stored unchanged.
- **Length:** usernames longer than 255 characters are an error ([Error Case E07](error-cases.md#error-case-e07-register-with-missing-or-invalid-fields)).

---

## 3. Automated Test Regressions
These cases are PHPUnit tests that repeat an operation or run a data provider over boundary values. They keep the rules above from breaking again. (The template's Go fuzzing does not apply to PHP; data providers and repeated runs play that role.)

---

### Edge Case EC11: Random Question Selection Repeated
**Description:**  
Question selection is random. One lucky run can hide a bug, such as a missing category or a duplicate question. Running the selection many times catches it.

---

#### Execution Environment: PHPUnit (`tests/Feature`), SQLite in memory, 01-edu question data seeded

#### Input Argument / Test Seed:
```php
for ($i = 0; $i < 50; $i++) {
    $quiz = $this->startNewQuizFor(User::factory()->create());
    // assertions below
}
```

#### Expected Output:
```plaintext
OK (50 iterations, 0 failures)
```

**Validation Rules:**
- **Coverage:** every quiz contains all 5 categories.
- **Unique:** no question id appears twice in one quiz.
- **Size:** every quiz has the agreed size (20 with the 01-edu data).

---

### Edge Case EC12: Category Score Strings
**Description:**  
Category scores are stored as text (`"x/y"`). Parsing and rebuilding the text can go wrong with `0/0`, with two-digit numbers, or with rounding of the percentage.

---

#### Execution Environment: PHPUnit (`tests/Unit`)

#### Input Argument / Test Seed:
```php
// [stored, right in this quiz, answered in this quiz, new stored, percentage]
['0/0',   0, 0, '0/0',   0],
['0/0',   3, 4, '3/4',   75],
['7/9',   0, 0, '7/9',   78],
['8/12',  4, 4, '12/16', 75],
['99/99', 1, 1, '100/100', 100],
```

#### Expected Output:
```plaintext
OK (5 data sets)
```

**Validation Rules:**
- **Round-trip:** parsing then rebuilding a string returns the same string.
- **Rounding:** percentage rounded to a whole number with `round()` (proposed; confirm in issue #33).
- **Invariant:** `x ≤ y`, both non-negative integers.
