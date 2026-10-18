# Error Cases: mister-quiz

This document outlines the expected error handling behavior and validation rules for the `mister-quiz` application. It ensures the program handles invalid inputs, missing data, unexpected states, and incorrect HTTP requests gracefully without crashing or exposing internal architecture.

> **How Laravel reports form errors:** a browser form with invalid data does **not** get `400 Bad Request`. Laravel answers `302 Found`, sends the user back to the form, and shows the messages from `resources/lang/en/validation.php` and `auth.php`. The old input is kept, except passwords. A JSON request gets `422 Unprocessable Entity` instead.
>
> **Status column:** "today" = the starter code before our fixes; the expected behavior is the target after the linked issue.

---

### Summary Table of Error Cases

#### Part 1: Environment & Data Error Cases
| Case ID | Description | Input Argument(s) | Expected Output / Behavior |
|:---:|---|---|---|
| E01 | Wrong PHP version on the host | `php artisan --version` with PHP ≥ 8.1 | Deprecation errors, exit 255 → use the wrappers |
| E02 | Port already in use | `docker compose up -d` with port 8000 taken | `port is already allocated` → set `APP_PORT` |
| E03 | Database not reachable | `php artisan migrate:status` with `db` stopped | `SQLSTATE[HY000] [2002]` → start the stack |
| E04 | Question data imported twice | SQL import run 2 times | 120 questions → `migrate:fresh` + import once |
| E05 | No questions in the database | `GET /quiz` with an empty `question` table | `200 OK` + clear message, no empty quiz |

#### Part 2: Web & HTTP Error Cases
| Case ID | Description | Request Pattern / Trigger | Expected HTTP Status |
|:---:|---|---|---|
| E06 | Wrong login details | `POST /login` (wrong password) | `302` → `/login` + `These credentials do not match our records.` |
| E07 | Register with missing or invalid fields | `POST /register` (empty / bad email / too long) | `302` → `/register` + field messages |
| E08 | Password confirmation does not match | `POST /register` | `302` → `/register` + `The password confirmation does not match.` |
| E09 | Email already registered | `POST /register` (existing email) | `302` → `/register` + `The email has already been taken.` (today: `500`) |
| E10 | Login with empty fields | `POST /login` (empty) | `302` → `/login` + `The email field is required.` |
| E11 | Quiz submitted with a missing answer | `POST /quiz/{quiz}` (incomplete) | `302` → `/quiz` + error; nothing scored |
| E12 | Guest opens a protected page | `GET /quiz`, `GET /profile`, `POST /logout` | `302` → `/login` |
| E13 | Tampered quiz submit | `POST /quiz/{other user's or unknown quiz}`, foreign answer id | `403` / `404` / `302` + error |
| E14 | Unknown page | `GET /non-existent-page` | `404 Not Found` |
| E15 | Wrong HTTP method | `POST /`, `GET /logout` (after fix) | `405 Method Not Allowed` |
| E16 | Form without CSRF token | `POST /login` without `_token` | `419 Page Expired` |
| E17 | Internal server error | Database stopped, `GET /leaderboard` | `500 Server Error`, no stack trace when `APP_DEBUG=false` |
| E18 | Wrong method on the quiz submit address | `GET /quiz/c`, `GET /quiz/1` | `405 Method Not Allowed` (`Allow: POST`) |
| E19 | Unknown quiz id on the submit address | `POST /quiz/c`, `/quiz/0`, `/quiz/-1`, `/quiz/1.5`, `/quiz/999999` (logged in) | `404 Not Found`, nothing scored |
| E20 | Method that the submit address never accepts | `PUT /quiz/1`, `DELETE /quiz/1` | `405 Method Not Allowed` (`Allow: POST`) |
| E21 | Write method on a page that only reads | `POST /`, `/quiz`, `/profile`, `/leaderboard` | `405 Method Not Allowed` (`Allow: GET, HEAD`) |
| E22 | Address with an extra part or other letter case | `GET /profile/2`, `/leaderboard/1`, `/quiz/1/answers`, `/Quiz` | `404 Not Found` |

---

## Part 1: Environment & Data Error Cases

### Error Case E01: Wrong PHP Version on the Host
**Description:**  
A teammate or auditor runs `php` from a local PHP 8.1+ install instead of the project wrapper (PHP 8.0.30 in Docker). Laravel 8.33.0 is not compatible with PHP 8.1+.

---

**Input Argument:**
```bash
/usr/bin/php artisan --version     # any PHP 8.1 or newer
```

**Expected Output:**
```bash
PHP Deprecated:  Return type of Illuminate\Container\Container::offsetExists($key) should either be compatible with ArrayAccess::offsetExists(mixed $offset): bool, ...
... (about 21 similar lines)
```

**Validation Rules:**
- **Exit Status:** the command fails (exit `255`). This is expected: the project supports only PHP 8.0.30.
- **Fix:** `type php` must print `~/.local/bin/php` ([Installation guide, step 4](Installation-guide.md#βήμα-4-εντολές-php-και-composer-wrappers)). The audit route `docker compose up -d --build` never uses the host PHP.
- **Forbidden fix:** `composer update` or a Laravel upgrade.

---

### Error Case E02: Port Already in Use
**Description:**  
Another program (XAMPP, another project) already listens on port 8000 or 8080, so Docker cannot publish the port.

---

**Input Argument:**
```bash
docker compose up -d
```

**Expected Output:**
```bash
Error response from daemon: ... Bind for 0.0.0.0:8000 failed: port is already allocated
```

**Validation Rules:**
- **Fix:** stop the other program, or set `APP_PORT=8001` / `PHPMYADMIN_PORT=8081` in `.env` and run `docker compose up -d` again.
- **MySQL:** port 3306 is never published, so a local MySQL never causes this error.

---

### Error Case E03: Database Not Reachable
**Description:**  
An `artisan` command or a page needs the database while the `db` service is stopped or still starting.

---

**Input Argument:**
```bash
docker compose stop db
php artisan migrate:status
```

**Expected Output:**
```bash
SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: ...
```

**Validation Rules:**
- **Fix:** `docker compose up -d`, then wait until `docker compose ps` shows `db` as `(healthy)`.
- **Config:** `.env` must keep `DB_HOST=db`. The `app` service forces `DB_HOST=db` anyway.
- **Web pages:** the same situation in the browser is [E17](#error-case-e17-internal-server-error).

---

### Error Case E04: Question Data Imported Twice
**Description:**  
The 01-edu SQL file has plain `INSERT` statements. Running it a second time duplicates every question and answer, so a quiz can show the same question text twice.

---

**Input Argument:**
```bash
docker compose exec -T db mysql -umister_quiz -psecret mister_quiz < /tmp/questions_and_answers.sql   # second time
docker compose exec -T db mysql -umister_quiz -psecret -e "select count(*) from question" mister_quiz
```

**Expected Output:**
```bash
count(*)
120
```

**Validation Rules:**
- **Detection:** the count must be `60`. Anything else means a double import.
- **Fix:** `php artisan migrate:fresh` (⚠️ deletes all users), then import **once**.
- **Prevention:** the question seeder from issue #4 must be safe to run twice (for example `updateOrInsert` or a check on existing rows).

---

### Error Case E05: No Questions in the Database
**Description:**  
The migrations ran but the question data was never imported. The starter would show an empty quiz with only a Submit button, or crash later on results.

---

**Input Argument:**
```bash
php artisan migrate:fresh      # no import afterwards
# log in, then open http://localhost:8000/quiz
```

**Expected Output:**
```http
HTTP/1.1 200 OK

No questions are available yet. Please try again later.   (proposed text)
```

**Validation Rules:**
- **No empty quiz:** no row is added to `quizzes`.
- **Missing category:** if any of the 5 categories has no questions, the same message appears, because the rule "at least one question per category" cannot be met.
- **No crash:** never `500`.

---

## Part 2: Web & HTTP Error Cases

### Error Case E06: Wrong Login Details
**Description:**  
The user enters an email that does not exist, or the wrong password. The subject requires an error message. Today the starter redirects to the homepage without checking anything (issue #6).

---

**Request (cURL or Browser):**
```plaintext
Browser: open /login, email auditor1@example.com, password wrong-password, click Login
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/login

(page) These credentials do not match our records.
```

**Validation Rules:**
- **Same message:** unknown email and wrong password show the same text, so the page does not reveal which emails exist.
- **Old input:** the email field keeps its value; the password field is empty.
- **Not signed in:** the homepage still shows the guest buttons.

---

### Error Case E07: Register with Missing or Invalid Fields
**Description:**  
The user submits the register form with empty fields, an invalid email, or a value longer than 255 characters.

---

**Request (cURL or Browser):**
```plaintext
Browser: open /register
  a) leave every field empty → Register
  b) email "not-an-email" → Register
  c) username of 256 characters → Register
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/register

a) The username field is required. / The email field is required. / The password field is required.
b) The email must be a valid email address.
c) The username must not be greater than 255 characters.
```

**Validation Rules:**
- **No user created:** the `users` table does not change.
- **Messages:** each message appears under its own field (the starter views already have `@error` blocks).
- **Old input:** username and email keep their values.

---

### Error Case E08: Password Confirmation Does Not Match
**Description:**  
The two password fields differ.

---

**Request (cURL or Browser):**
```plaintext
Browser: /register with password "secret123" and confirmation "secret124"
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/register

The password confirmation does not match.
```

**Validation Rules:**
- **Rule:** `password` uses Laravel's `confirmed` rule (already in the starter).
- **No user created.**

---

### Error Case E09: Email Already Registered
**Description:**  
The user registers with an email that already exists. The `users.email` column is `unique`, but the starter validation has no `unique:users` rule. **Today** the insert fails and the page shows `500` with `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry` (issue #7).

---

**Request (cURL or Browser):**
```plaintext
Browser: /register with email auditor1@example.com (already used in W01)
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/register

The email has already been taken.
```

**Validation Rules:**
- **Status:** never `500`.
- **No change:** the existing user is not modified and nobody is signed in.

---

### Error Case E10: Login with Empty Fields
**Description:**  
The user clicks Login with one or both fields empty. The server must validate before it tries to sign in.

---

**Request (cURL or Browser):**
```plaintext
Browser: /login, leave both fields empty, click Login
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/login

The email field is required.
The password field is required.
```

**Validation Rules:**
- **Validation first:** `required` (and `email` for the email field) run before `Auth::attempt()`.

---

### Error Case E11: Quiz Submitted with a Missing Answer
**Description:**  
The browser check (`required` on the radio buttons) can be skipped, for example by removing it in DevTools or by sending the form with cURL. The server must still reject the submit. This is the server side of [Audit Case W08](audit-cases.md#audit-case-w08-submit-with-a-missing-answer).

---

**Request (cURL or Browser):**
```plaintext
Browser: open the quiz, remove the "required" attribute of one question in DevTools,
leave that question empty, click Submit
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/quiz

Please answer every question before you submit.   (proposed text)
```

**Validation Rules:**
- **Nothing scored:** XP, category scores and `completed` stay unchanged.
- **Complete set:** the server checks that the submitted answers cover every question of **this** quiz, not only that the submitted fields are valid.
- **Answers kept:** the answers already chosen are still selected.

---

### Error Case E12: Guest Opens a Protected Page
**Description:**  
A visitor who is not logged in opens a page that needs an account. `/quiz` is also an audit case ([W06](audit-cases.md#audit-case-w06-guest-clicks-start-quiz)). The subject says: "Only the user should be able to see his own profile."

---

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/quiz
curl -i http://localhost:8000/profile
```

**Expected Output / Behavior:**
```http
HTTP/1.1 302 Found
Location: http://localhost:8000/login
```

**Validation Rules:**
- **Middleware:** these routes use Laravel's `auth` middleware.
- **No profile by id:** there is no `/profile/{id}` route, so one user cannot open another user's profile (`GET /profile/2` → `404`).
- **Logout as guest:** `POST /logout` without a session also ends on `/login` (or `/`), never `500`.

---

### Error Case E13: Tampered Quiz Submit
**Description:**  
A logged-in user edits the form or the URL: submits another user's quiz, a quiz id that does not exist, or an answer id that does not belong to the question.

---

**Request (cURL or Browser):**
```plaintext
a) POST /quiz/{id of another user's quiz}
b) POST /quiz/99999
c) POST /quiz/{own quiz} with question 5 → answer id of question 7
```

**Expected Output / Behavior:**
```http
a) HTTP/1.1 403 Forbidden
b) HTTP/1.1 404 Not Found
c) HTTP/1.1 302 Found → /quiz  +  The selected answer is invalid.
```

**Validation Rules:**
- **Ownership:** the controller (or a policy) checks `quiz.user_id === auth()->id()`.
- **Answer check:** each answer is checked against the answers of its own question; answer **ids** are compared, not answer texts.
- **Nothing scored** in all three cases.
- **Already submitted quiz:** handled as [Edge Case EC06](edge-cases.md#edge-case-ec06-quiz-submitted-twice).

---

### Error Case E14: Unknown Page
**Description:**  
A request to a path that no route defines.

---

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/non-existent-page
curl -i http://localhost:8000/../../etc/passwd
```

**Expected Output / Behavior:**
```http
HTTP/1.1 404 Not Found
```

**Validation Rules:**
- **Status Code:** exactly `404 Not Found` for every undefined path.
- **No file access:** only files inside `public/` are served; path traversal never returns files from outside it.
- **More addresses:** an address with an extra part (`/profile/2`, `/quiz/1/answers`) is [E22](#error-case-e22-address-with-an-extra-part-or-other-letter-case). Why some wrong addresses give `405` and not `404`: see [E18](#error-case-e18-wrong-method-on-the-quiz-submit-address).

---

### Error Case E15: Wrong HTTP Method
**Description:**  
A route exists but does not accept this method. After issue #8, logout accepts only `POST`.

---

**Request (cURL or Browser):**
```bash
curl -i -X POST http://localhost:8000/
curl -i http://localhost:8000/logout
```

**Expected Output / Behavior:**
```http
HTTP/1.1 405 Method Not Allowed
```

**Validation Rules:**
- **Status Code:** exactly `405 Method Not Allowed`.
- **Logout by link:** a plain link or an image tag pointing to `/logout` cannot sign the user out.
- **The quiz submit address and the read-only pages:** see [E18](#error-case-e18-wrong-method-on-the-quiz-submit-address), [E20](#error-case-e20-method-that-the-submit-address-never-accepts) and [E21](#error-case-e21-write-method-on-a-page-that-only-reads).

---

### Error Case E16: Form without CSRF Token
**Description:**  
A `POST` without a valid `_token`, for example a form posted from another site or an expired session.

---

**Request (cURL or Browser):**
```bash
curl -i -X POST -d "email=a@b.c&password=x" http://localhost:8000/login
```

**Expected Output / Behavior:**
```http
HTTP/1.1 419 unknown status

Page Expired
```

**Validation Rules:**
- **Every form** (`login`, `register`, `logout`, quiz submit) contains `@csrf`.
- **No action:** nothing is created, signed in or scored.

---

### Error Case E17: Internal Server Error
**Description:**  
The server meets an error it cannot recover from. With `APP_DEBUG=true` (local development) Laravel shows a detailed Ignition page. With `APP_DEBUG=false` the user must see only a generic page.

---

**Execution Environment / Trigger:**
Set `APP_DEBUG=false` in `.env`, run `docker compose restart app`, then stop the database with `docker compose stop db`. (Before M2, `GET /` also returns `500` because of issue #5.)

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/leaderboard
```

**Expected Output / Behavior:**
```http
HTTP/1.1 500 Internal Server Error

Server Error
```

**Validation Rules:**
- **Status Code:** exactly `500`.
- **Data Security:** no SQL, stack trace, file path, `.env` value or password in the response.
- **Log:** the details are written to `storage/logs/laravel.log`.
- **After the test:** set `APP_DEBUG=true` again and run `docker compose up -d`.

---

### Error Case E18: Wrong Method on the Quiz Submit Address
**Description:**  
A visitor types `http://localhost:8000/quiz/c` (or `/quiz/1`) in the address bar. The address `/quiz/{quiz}` exists, but only for `POST` (the Submit button of the quiz), and a browser address bar sends `GET`. Laravel asks two questions in this order: *is there a route with this address?* (yes) and *does it accept this method?* (no). So the answer is `405`, not `404`. `/profile/1`, which no route has, is a `404` ([E22](#error-case-e22-address-with-an-extra-part-or-other-letter-case)). The same explanation is in the [README](../README.md#error-handling), section "404 or 405? Why `/quiz/c` is not a 404".

---

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/quiz/c
curl -i http://localhost:8000/quiz/1
```

**Expected Output / Behavior:**
```http
HTTP/1.1 405 Method Not Allowed
Allow: POST

405 | Method Not Allowed
```

**Validation Rules:**
- **Status Code:** exactly `405`, also for a guest (the route is matched before the `auth` middleware runs). Never `404` and never `500`.
- **Allow header:** it lists `POST`, the only method that works.
- **Nothing changes:** no quiz is read, created or scored.
- **Page:** the `405` page looks like the `404` page, and no developer page is shown when `APP_DEBUG=true` (issue #51).
- **Test:** issue #50.

---

### Error Case E19: Unknown Quiz Id on the Submit Address
**Description:**  
A logged-in user posts the quiz form to an id that is not a quiz: text, zero, a negative or decimal number, or a number that no quiz has. The address matches `/quiz/{quiz}` and the method is right, but the quiz cannot be loaded. The same as [E13](#error-case-e13-tampered-quiz-submit) case b), with more kinds of ids.

---

**Request (Browser):**
```plaintext
Log in, open /quiz, change the action of the form in DevTools to /quiz/c, click Submit
(then the same with /quiz/0, /quiz/-1, /quiz/1.5 and /quiz/999999)
```

**Expected Output / Behavior:**
```http
HTTP/1.1 404 Not Found
```

**Validation Rules:**
- **Status Code:** exactly `404` for every id above. Never `500` (no database error from a text id).
- **Nothing scored:** XP, category scores and `completed` of every quiz stay unchanged.
- **Another user's quiz:** an id that exists but belongs to someone else is `403`, see E13.
- **Page:** the `404` page.
- **Test:** issue #50.

---

### Error Case E20: Method That the Submit Address Never Accepts
**Description:**  
A request with `PUT` or `DELETE` to the quiz submit address. Only `POST` is allowed there.

---

**Request (cURL):**
```bash
curl -i -X PUT http://localhost:8000/quiz/1
curl -i -X DELETE http://localhost:8000/quiz/1
```

**Expected Output / Behavior:**
```http
HTTP/1.1 405 Method Not Allowed
Allow: POST
```

**Validation Rules:**
- **Status Code:** exactly `405`, before any token check (no `419`) and before any login check.
- **Nothing changes:** no quiz is read, created or deleted.
- **Page:** the `405` page (issue #51).
- **Test:** issue #50.

---

### Error Case E21: Write Method on a Page That Only Reads
**Description:**  
A `POST` to the homepage, the quiz page, the profile or the leaderboard. These four addresses answer `GET` only.

---

**Request (cURL):**
```bash
curl -i -X POST http://localhost:8000/
curl -i -X POST http://localhost:8000/quiz
curl -i -X POST http://localhost:8000/profile
curl -i -X POST http://localhost:8000/leaderboard
```

**Expected Output / Behavior:**
```http
HTTP/1.1 405 Method Not Allowed
Allow: GET, HEAD
```

**Validation Rules:**
- **Status Code:** exactly `405` for all four, also for a guest (never `302` to the login page and never `419`).
- **Nothing changes:** no data is created or changed.
- **Page:** the `405` page (issue #51).
- **Test:** issue #50.

---

### Error Case E22: Address with an Extra Part or Other Letter Case
**Description:**  
A path that looks like a real page but that no route defines: an id after a page that has no id (`/profile/2`, `/leaderboard/1`), a sub-page that does not exist (`/quiz/1/answers`), or the wrong letter case (`/Quiz`). Addresses are case sensitive. Because no route has this address, the first question of Laravel already fails, so the answer is `404` and not `405` (see [E18](#error-case-e18-wrong-method-on-the-quiz-submit-address)).

---

**Request (cURL or Browser):**
```bash
curl -i http://localhost:8000/profile/2
curl -i http://localhost:8000/leaderboard/1
curl -i http://localhost:8000/quiz/1/answers
curl -i http://localhost:8000/Quiz
```

**Expected Output / Behavior:**
```http
HTTP/1.1 404 Not Found

404 | Not Found
```

**Validation Rules:**
- **Status Code:** exactly `404` for all four, also for a guest (no redirect to the login page).
- **No profile by id:** `/profile/2` never shows the profile of another user (see also [E12](#error-case-e12-guest-opens-a-protected-page)).
- **Page:** the `404` page.
- **Test:** issue #50.
