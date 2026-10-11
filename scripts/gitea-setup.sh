#!/usr/bin/env bash
# Gitea structure for hmim/mister-quiz: branches, collaborator, branch protection,
# labels, milestones and issues.
#
# Idempotent BY TITLE/NAME. Every object is looked up before it is created, so a
# second run creates nothing and exits 0. Safe to re-run after adding issues.
#
# Requires: tea 0.15.1+ with login "ATM", python3 (JSON parsing only).
# Never prints the token: authentication is handled entirely inside tea.
#
# Usage:
#   scripts/gitea-setup.sh            # apply
#   DRY_RUN=1 scripts/gitea-setup.sh  # print what would change, touch nothing
#   SYNC_BODIES=check scripts/gitea-setup.sh  # also list issue bodies that drifted
#   SYNC_BODIES=1 scripts/gitea-setup.sh      # and rewrite them to match this file
set -euo pipefail

LOGIN="ATM"
REPO="hmim/mister-quiz"
OWNER="${REPO%%/*}"
DEFAULT_BRANCH="main"
INTEGRATION_BRANCH="dev"
COLLABORATOR="ltzokas"
COLLABORATOR_PERMISSION="write"
DRY_RUN="${DRY_RUN:-0}"

created=0
skipped=0

say()  { printf '%s\n' "$*"; }
step() { printf '\n=== %s ===\n' "$*"; }
skip() { printf '  = %s\n' "$*"; skipped=$((skipped + 1)); }
add()  { printf '  + %s\n' "$*"; created=$((created + 1)); }

# Every call that CHANGES the remote goes through this, so the whole run can be
# inspected with DRY_RUN=1 before anything is written.
api_write() { # METHOD ENDPOINT [tea api args...]
    local method="$1" endpoint="$2"
    shift 2
    if [ "$DRY_RUN" = "1" ] || [ "${SYNC_BODIES:-0}" = "check" ]; then
        printf '  ~ would %s %s\n' "$method" "$endpoint"
        return 0
    fi
    # tea api exits 0 whatever the server answers, so the status line from -i is
    # the only way to notice a refused write. Without this check a wrong field
    # name looks exactly like success: the script reports 22 dependencies
    # created and the server has none. That happened.
    local out status
    out="$(tea api --login "$LOGIN" -i -X "$method" "$endpoint" "$@" 2>&1)"
    status="$(printf '%s\n' "$out" | head -1)"
    case "$status" in
        *" 2"[0-9][0-9]*) return 0 ;;
    esac
    say "  ! $method $endpoint refused: $status"
    printf '%s\n' "$out" | grep -o '"message":"[^"]*"' | head -1 | sed 's/^/    /'
    return 1
}

api_read() {
    tea api --login "$LOGIN" "$@"
}

# tea api exits 0 even on 404 and prints the error as JSON, so the exit code
# cannot be used to test existence. -i writes the status line to stderr; that is
# the only reliable signal.
api_exists() { # ENDPOINT
    local status
    status="$(tea api --login "$LOGIN" -i "$1" 2>&1 >/dev/null | head -1)"
    case "$status" in
        *" 2"[0-9][0-9]*) return 0 ;;
    esac
    return 1
}

# ---------------------------------------------------------------------------
# 0. Preflight
# ---------------------------------------------------------------------------
step "Preflight"
command -v tea >/dev/null || { say "tea not found. See _docs/Installation-guide.md."; exit 1; }
command -v python3 >/dev/null || { say "python3 not found (needed to read JSON responses)."; exit 1; }

api_exists "/repos/$REPO" || {
    say "Cannot reach $REPO as login $LOGIN. Check 'tea logins list'."
    exit 1
}
say "  ok $REPO reachable as login $LOGIN"
[ "$DRY_RUN" = "1" ] && say "  DRY RUN: nothing will be written"

# ---------------------------------------------------------------------------
# 1. Integration branch
# ---------------------------------------------------------------------------
# dev is where every feature branch is merged. main only ever receives docs
# commits from the team leader, which is why main is the protected one.
step "Branch $INTEGRATION_BRANCH"
if api_exists "/repos/$REPO/branches/$INTEGRATION_BRANCH"; then
    skip "branch $INTEGRATION_BRANCH already on the remote"
else
    if [ "$DRY_RUN" = "1" ]; then
        printf '  ~ would create %s from %s and push it\n' "$INTEGRATION_BRANCH" "$DEFAULT_BRANCH"
    else
        git show-ref --verify --quiet "refs/heads/$INTEGRATION_BRANCH" \
            || git branch "$INTEGRATION_BRANCH" "$DEFAULT_BRANCH"
        git push -u origin "$INTEGRATION_BRANCH"
    fi
    add "branch $INTEGRATION_BRANCH"
fi

# ---------------------------------------------------------------------------
# 2. Collaborator
# ---------------------------------------------------------------------------
step "Collaborator"
current_perm="$(api_read "/repos/$REPO/collaborators/$COLLABORATOR/permission" 2>/dev/null \
    | python3 -c 'import json,sys
try: print(json.load(sys.stdin).get("permission",""))
except Exception: print("")' || true)"

if [ "$current_perm" = "$COLLABORATOR_PERMISSION" ] || [ "$current_perm" = "admin" ]; then
    skip "$COLLABORATOR already has $current_perm access"
else
    api_write PUT "/repos/$REPO/collaborators/$COLLABORATOR" \
        -f "permission=$COLLABORATOR_PERMISSION"
    add "$COLLABORATOR with $COLLABORATOR_PERMISSION access"
fi

# ---------------------------------------------------------------------------
# 3. Branch protection on main
# ---------------------------------------------------------------------------
# enable_push + a whitelist of hmim: the team leader still pushes docs commits
# straight to main, everyone else has to open a pull request.
# 1 approval + dismiss_stale_approvals: a re-push invalidates the old review.
step "Branch protection on $DEFAULT_BRANCH"
if api_exists "/repos/$REPO/branch_protections/$DEFAULT_BRANCH"; then
    skip "protection rule for $DEFAULT_BRANCH already exists"
else
    api_write POST "/repos/$REPO/branch_protections" \
        -f "rule_name=$DEFAULT_BRANCH" \
        -F "enable_push=true" \
        -F "enable_push_whitelist=true" \
        -F "push_whitelist_usernames=[\"$OWNER\"]" \
        -F "push_whitelist_deploy_keys=false" \
        -F "required_approvals=1" \
        -F "enable_approvals_whitelist=false" \
        -F "dismiss_stale_approvals=true" \
        -F "block_on_rejected_reviews=false" \
        -F "block_on_official_review_requests=false" \
        -F "require_signed_commits=false"
    add "protection rule for $DEFAULT_BRANCH"
fi

# ---------------------------------------------------------------------------
# 4. Labels
# ---------------------------------------------------------------------------
step "Labels"
existing_labels="$(api_read "/repos/$REPO/labels?limit=100" \
    | python3 -c 'import json,sys
for l in json.load(sys.stdin): print(l["name"])')"

label() { # name color description
    if printf '%s\n' "$existing_labels" | grep -Fxq "$1"; then
        skip "label $1"
    else
        api_write POST "/repos/$REPO/labels" \
            -f "name=$1" -f "color=$2" -f "description=$3"
        add "label $1"
    fi
}

label "frontend"        "1d76db" "UI: Blade views, forms, layout"
label "backend"         "0e8a16" "Routes, controllers, models, migrations, validation"
label "bug"             "d73a4a" "Something in the code does not work as the subject asks"
label "good-first-task" "fbca04" "Small task, good for a visual check"

# ---------------------------------------------------------------------------
# 5. Milestones
# ---------------------------------------------------------------------------
step "Milestones"
existing_milestones="$(api_read "/repos/$REPO/milestones?state=all&limit=100" \
    | python3 -c 'import json,sys
for m in json.load(sys.stdin): print(m["title"])')"

milestone() { # title description
    if printf '%s\n' "$existing_milestones" | grep -Fxq "$1"; then
        skip "milestone $1"
    else
        api_write POST "/repos/$REPO/milestones" \
            -f "title=$1" -f "description=$2"
        add "milestone $1"
    fi
}

milestone "M0 — Environment & shared schema" \
    "Same Docker environment for both teammates (PHP 8.0.30, Laravel 8.33.0, MySQL 8.4.11), shared database schema review, question data. Must be done before feature work."
milestone "M1 — Auth (register, login, logout)" \
    "Audit: register with confirmation and unique email, login with an error on wrong credentials, logout."
milestone "M2 — Homepage buttons" \
    "Audit: 3 buttons for guests (Login, Leaderboard, Start Quiz); 4 buttons for logged-in users (Profile, Leaderboard, Start Quiz, Logout)."
milestone "M3 — Start quiz & persistence" \
    "Audit: guests are sent to login; at least 1 question per category; the same quiz survives a refresh or a return to the homepage."
milestone "M4 — Question screen & submit rules" \
    "Audit: questions show their answers; the quiz cannot be submitted while an answer is missing, and an error message is shown."
milestone "M5 — Results page" \
    "Audit: correct/wrong answers in total and per category; user XP and category scores (x/y) are updated."
milestone "M6 — Profile page" \
    "Audit: rank from XP, correct-answer percentage per category, only the user can see their own profile."
milestone "M7 — Leaderboard" \
    "Audit: top 10 users by XP with username, XP and total correct answers."
milestone "M8 — Testing & polish" \
    "Feature tests, a visual check against the audit checklist, code review before merging dev into main."

# Titles are stable, IDs are not: resolve both maps after creation.
if [ "$DRY_RUN" = "1" ]; then
    say "  (dry run: skipping issues, they need real label and milestone ids)"
    printf '\n=== Summary ===\n  would create: %d\n  already present: %d\n' "$created" "$skipped"
    exit 0
fi

label_ids="$(api_read "/repos/$REPO/labels?limit=100" \
    | python3 -c 'import json,sys
for l in json.load(sys.stdin): print(l["name"] + "\t" + str(l["id"]))')"
milestone_ids="$(api_read "/repos/$REPO/milestones?state=all&limit=100" \
    | python3 -c 'import json,sys
for m in json.load(sys.stdin): print(m["title"] + "\t" + str(m["id"]))')"

label_id()     { printf '%s\n' "$label_ids"     | awk -F'\t' -v n="$1" '$1==n {print $2; exit}'; }
milestone_id() { printf '%s\n' "$milestone_ids" | awk -F'\t' -v n="$1" '$1==n {print $2; exit}'; }

# ---------------------------------------------------------------------------
# 6. Issues
# ---------------------------------------------------------------------------
# Titles carry a [module] prefix so the issue list groups by area of the code
# without spending labels on it. See _docs/MILESTONES.md for the full mapping.
#
# SYNC_BODIES brings the body of an issue that already exists back in line with
# the text below, so a correction made here reaches Gitea too:
#   SYNC_BODIES=check  list the issues whose body differs, change nothing
#   SYNC_BODIES=1      rewrite those bodies
# Titles, labels, milestone and state are never touched by the sync. An issue is
# still found by its title, so renaming one means renaming it on Gitea first.
step "Issues"
SYNC_BODIES="${SYNC_BODIES:-0}"
synced=0

# Every issue with its number, title and body, fetched once. A file rather than
# a variable, because bodies span many lines.
issues_json="$(mktemp)"
trap 'rm -f "$issues_json"' EXIT
page=1
printf '[' > "$issues_json"
while :; do
    batch="$(api_read "/repos/$REPO/issues?state=all&type=issues&limit=50&page=$page")"
    [ "$batch" = "[]" ] && break
    [ "$page" -gt 1 ] && printf ',' >> "$issues_json"
    printf '%s' "${batch:1:${#batch}-2}" >> "$issues_json"
    page=$((page + 1))
done
printf ']' >> "$issues_json"

existing_issues="$(python3 -c 'import json,sys
for i in json.load(open(sys.argv[1])): print(i["title"])' "$issues_json")"

# Prints the issue number when the remote body differs from stdin, else nothing.
# Line endings and trailing whitespace are ignored: Gitea may normalise them.
body_differs() { # TITLE < BODY
    python3 -c 'import json,sys
norm = lambda s: "\n".join(l.rstrip() for l in (s or "").replace("\r\n", "\n").strip().split("\n"))
want = sys.stdin.read()
for i in json.load(open(sys.argv[1])):
    if i["title"] == sys.argv[2]:
        if norm(i["body"]) != norm(want): print(i["number"])
        break' "$issues_json" "$1"
}

issue() { # milestone-prefix  labels-csv  [state]  title  body
    # state defaults to open, so only the handful of issues already delivered
    # on main have to spell "closed" out.
    local ms_prefix="$1" labels_csv="$2" state title body
    local ms_title ms label ids closed

    if [ "$#" -eq 5 ]; then
        state="$3"; title="$4"; body="$5"
    else
        state="open"; title="$3"; body="$4"
    fi

    if printf '%s\n' "$existing_issues" | grep -Fxq "$title"; then
        local number
        number=""
        [ "$SYNC_BODIES" != "0" ] && number="$(printf '%s' "$body" | body_differs "$title")"
        if [ -z "$number" ]; then
            skip "$title"
        elif [ "$SYNC_BODIES" = "check" ]; then
            printf '  ~ #%s body differs: %s\n' "$number" "$title"
        else
            api_write PATCH "/repos/$REPO/issues/$number" -f "body=$body"
            printf '  ~ #%s body synced: %s\n' "$number" "$title"
            synced=$((synced + 1))
        fi
        return 0
    fi

    if [ "$SYNC_BODIES" = "check" ]; then
        printf '  ~ would create: %s\n' "$title"
        return 0
    fi

    ms_title="$(printf '%s\n' "$milestone_ids" | awk -F'\t' -v p="$ms_prefix" \
        'index($1, p) == 1 {print $1; exit}')"
    ms="$(milestone_id "$ms_title")"
    [ -n "$ms" ] || { say "  ! no milestone matching $ms_prefix"; return 1; }

    ids=""
    while IFS= read -r label; do
        [ -n "$label" ] || continue
        local id
        id="$(label_id "$label")"
        [ -n "$id" ] || { say "  ! no label $label"; return 1; }
        ids="${ids:+$ids,}$id"
    done <<< "$(printf '%s' "$labels_csv" | tr ',' '\n')"

    closed=false
    [ "$state" = "closed" ] && closed=true

    tea api --login "$LOGIN" -X POST "/repos/$REPO/issues" \
        -f "title=$title" \
        -f "body=$body" \
        -F "labels=[$ids]" \
        -F "milestone=$ms" \
        -F "closed=$closed" >/dev/null

    if [ "$closed" = true ]; then
        add "$title (closed)"
    else
        add "$title"
    fi
}


# --- M0: Environment & shared schema ----------

issue M0 '' closed '[env] Docker dev environment (PHP 8.0.30, Laravel 8.33.0, MySQL 8.4.11)' \
'The starter locks **Laravel 8.33.0**, which only runs on **PHP 8.0.x** (PHP 8.1+ turns deprecations into errors). We keep the starter code and match the tools to it.

Done in commit 7ef067f:
- [Dockerfile](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/Dockerfile): PHP 8.0.30, `pdo_mysql`, `zip`, Composer 2.9.5
- [docker-compose.yml](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/docker-compose.yml): app on port 8000, MySQL 8.4.11 (no host port), phpMyAdmin 5.2.3 on port 8080
- [docker/entrypoint.sh](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/docker/entrypoint.sh): `composer install`, `.env`, app key and migrations on first start; runs as the owner of the project folder
- [docker/bin/php](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/docker/bin/php) and [docker/bin/composer](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/docker/bin/composer): `php` and `composer` commands on the host, backed by the image
- [.env.example](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/.env.example): `DB_HOST=db`

Checked with a fresh-clone simulation (other user id, port 3306 busy): build, automatic setup, `/login` 200, registration saves a user.'

issue M0 '' '[env] Set up the environment on both PCs and post the checklist' \
'Each teammate follows `_docs/Installation-guide.md` **step by step, with the exact versions**. Do not install XAMPP or a local PHP.

When you finish, post the output of the guide'\''s verification checklist as a comment here.

- [ ] hmim posted the checklist
- [ ] ltzokas posted the checklist

Audit PC (school, Linux + Docker): a fresh clone must start with `docker compose up -d --build`.'

issue M0 'backend' '[db] Review the shared database schema before feature work' \
'Both of us build on the same tables, so we agree on the schema first.

What the starter has:
- `users`: `username`, `email` (unique), `password`, `xp`, and `art`, `geography`, `history`, `science`, `sports` as `x/y` strings ([database/migrations/2021_03_16_160502_add_attributes_to_users_table.php:16-23](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/database/migrations/2021_03_16_160502_add_attributes_to_users_table.php#L16-L23))
- `question`: `question`, `xp`, `category`; `answer`: `answer`, `correct`, `question_id`
- `quizzes`: `completed`, `user_id` ([database/migrations/2021_03_22_103501_create_quiz_table.php:17-23](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/database/migrations/2021_03_22_103501_create_quiz_table.php#L17-L23))

Open points:
- [ ] The quiz ↔ question table is missing (see #10)
- [ ] `Quiz` lists a `score` field with no column: remove it or add it
- [ ] Leaderboard "total correct answers": sum of the 5 `x` values, or a new column?
- [ ] Do we store the chosen answers (needed only if the results page must be shown again later)?

Write the decision as a comment here. Each migration change goes in its own PR.'

issue M0 'backend' '[db] Question data: seeder for the 01-edu questions' \
'[database/seeders/DatabaseSeeder.php:14-17](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/database/seeders/DatabaseSeeder.php#L14-L17) is empty. Today the questions come from the 01-edu SQL file (60 questions, 240 answers), imported by hand.

A seeder gives every PC, and the auditor, the same data with one command.

- [ ] `php artisan db:seed` loads all questions and answers
- [ ] Running it twice does not create duplicates
- [ ] Every category (Art, History, Geography, Science, Sports) has questions, and each question has exactly one correct answer
- [ ] The Installation guide and the README use the seeder instead of the manual import'


# --- M2: Homepage buttons ----------

issue M2 'backend,bug' '[home] Homepage returns 500: routes profile, leaderboard and quiz are not defined' \
'**Steps:** start the stack and open http://localhost:8000/

**Result:** error page `Route [leaderboard] not defined` (for a logged-in user, `Route [profile] not defined`).

**Cause:** [resources/views/home.blade.php:6](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/home.blade.php#L6), [resources/views/home.blade.php:14](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/home.blade.php#L14) and [resources/views/home.blade.php:24](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/home.blade.php#L24) call `route('\''profile'\'')`, `route('\''leaderboard'\'')` and `route('\''quiz'\'')`. [routes/web.php:23-33](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/routes/web.php#L23-L33) only defines `home`, `login`, `register` and `logout`. The controllers exist and are already imported.

The same missing names are used in `questions/results.blade.php` and `questions/list.blade.php`. The starter test `tests/Feature/ExampleTest.php` fails for this reason.

**Expected:** `/` returns 200 for guests and for logged-in users.'


# --- M1: Auth (register, login, logout) ----------

issue M1 'backend,bug' '[auth] Login does not sign the user in and never shows an error' \
'**Steps:** open `/login`, enter a wrong password, submit. Then try the right password.

**Result:** both times you are sent to the homepage as a guest, with no error message.

**Cause:** [app/Http/Controllers/Auth/LoginController.php:17-20](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/Auth/LoginController.php#L17-L20) only redirects. It never validates the form and never calls `Auth::attempt`.

**Expected:** right credentials → logged in, homepage. Wrong credentials → back to the login form with an error. The view already shows `@error` and `session('\''status'\'')`.'

issue M1 'backend,bug' '[auth] Registering with an email that already exists crashes' \
'**Steps:** register a user, log out, register again with the same email.

**Result:** SQL error 1062 (duplicate entry) instead of a form error.

**Cause:** [app/Http/Controllers/Auth/RegisterController.php:21-25](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/Auth/RegisterController.php#L21-L25) has no `unique:users,email` rule, but the column is unique in [database/migrations/2014_10_12_000000_create_users_table.php:19](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/database/migrations/2014_10_12_000000_create_users_table.php#L19).

**Expected:** the form shows "The email has already been taken." and keeps the typed values.'

issue M1 'backend,bug' '[auth] Logout works with a plain GET link' \
'**Cause:** [routes/web.php:33](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/routes/web.php#L33) is a GET route, linked from [resources/views/home.blade.php:17](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/home.blade.php#L17).

**Risk:** any link, image or browser prefetch of `/logout` signs the user out. There is no CSRF check.

**Expected:** a POST route with `@csrf`, and a small form whose button looks like the current red button. The audit only asks for a Logout button, so the look stays the same.'


# --- M3: Start quiz & persistence ----------

issue M3 'backend,bug' '[quiz] Start Quiz: questions are picked but never shown or saved' \
'**Cause:** [app/Http/Controllers/Questions/QuestionController.php:14-33](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/Questions/QuestionController.php#L14-L33)
- builds `$questions`, but returns the view without any data
- the guest branch is empty, so guests are not redirected
- no `Quiz` row is saved, so a refresh gives new questions
- [resources/views/questions/list.blade.php:5-12](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/questions/list.blade.php#L5-L12) expects `$quiz` → undefined variable
- no route in `routes/web.php` points to `index` or `results`

**Expected:** see the M3 and M4 issues.'

issue M3 'backend,bug' '[db] Quiz ↔ question table is missing and uses wrong column names' \
'**Cause:**
- `Quiz::questions()` and `Question::quizes()` use `belongsToMany`, which expects a table `question_quiz` with `question_id` and `quiz_id`. No migration creates it.
- [app/Models/Question_Quiz.php:10-11](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Models/Question_Quiz.php#L10-L11) and [app/Models/Quiz.php:19-27](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Models/Quiz.php#L19-L27) use `quizzes_id`, a third name.
- [app/Models/Quiz.php:12](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Models/Quiz.php#L12) lists `score`, but [database/migrations/2021_03_22_103501_create_quiz_table.php:17-23](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/database/migrations/2021_03_22_103501_create_quiz_table.php#L17-L23) has no `score` column.

**Expected:** one migration and one set of names, agreed in #3.'


# --- M4: Question screen & submit rules ----------

issue M4 'bug,frontend' '[quiz] Question component shows no answer choices' \
'**Cause:** the loop in [resources/views/components/question.blade.php:7-9](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/components/question.blade.php#L7-L9) is empty, so only the question text appears.

**Expected:** one radio button per answer. `results()` reads each numeric form key as a question id and compares the value with the answer text, so the input name and value must match what the backend reads (or both change together).'


# --- M5: Results page ----------

issue M5 'backend,bug' '[results] XP stays 0, category scores never change, totals are hardcoded' \
'In [app/Http/Controllers/Questions/QuestionController.php:38-80](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/Questions/QuestionController.php#L38-L80):
- line 47: `$xp = 0` is never increased
- lines 61 and 72: XP is added twice
- line 54: `$question` is loaded but not used, so the per-category counts stay 0
- line 67: every category total grows by a hardcoded `4`
- line 41: an unknown quiz id gives `null`, and line 76 crashes on `save()`
- line 52: a numeric key that is not a question id crashes
- no check that the quiz belongs to the user or is still open, so submitting again adds the score again

In [resources/views/questions/results.blade.php:14-37](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/questions/results.blade.php#L14-L37): `/ 20` and `/ 4` are hardcoded.

**Expected:** see the M5 issues.'


# --- M8: Testing & polish ----------

issue M8 'backend,bug' '[db] UserFactory uses a name column that does not exist' \
'**Cause:** [database/factories/UserFactory.php:26](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/database/factories/UserFactory.php#L26) sets `name`, but the `users` table has `username`.

**Result:** `User::factory()->create()` fails with an unknown column error, so tests cannot create users.

**Expected:** the factory sets `username`.'

issue M8 'backend,bug' '[test] Tests would run against the development database' \
'**Cause:** [phpunit.xml:24-25](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/phpunit.xml#L24-L25) are commented out, so tests use the `mister_quiz` database from `.env`.

**Risk:** a test with `RefreshDatabase` wipes our users and the imported questions.

**Expected:** tests use their own database. Pick one in the PR:
- SQLite in memory (`pdo_sqlite` is in the Docker image), or
- a separate `mister_quiz_testing` database in the `db` container'


# --- M1: Auth (register, login, logout) ----------

issue M1 'backend' '[auth] Register: username, email, password and confirmation' \
'Subject: to register, a user enters a username, an email, a password and a password confirmation.

Starter: the form exists; validation is partial (#7).

- [ ] Audit: "Can you register a new user providing a username, an email, a password and a password confirmation?"
- [ ] Each field shows its error; typed values stay after an error
- [ ] Duplicate email → form error, no crash
- [ ] After registering, the user is logged in and sent to the homepage'

issue M1 'backend' '[auth] Login with email and password, error on wrong credentials' \
'Subject: to log in, the user gives email and password. Wrong credentials → an error message. After login → homepage.

Starter: #6.

- [ ] Audit: "Can you log in a user providing its email and password?"
- [ ] Wrong email or password → one error message, email kept in the field
- [ ] Right credentials → homepage, logged in'

issue M1 'backend' '[auth] Logout' \
'Starter: #8.

- [ ] The Logout button appears only for logged-in users
- [ ] After logout, the homepage shows the guest buttons'

issue M1 'frontend,good-first-task' '[auth] Register page: form and link to Login' \
'Starter view: [resources/views/auth/register.blade.php](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/auth/register.blade.php).

- [ ] 4 fields: username, email, password, password confirmation
- [ ] Every field shows its error message
- [ ] "Already have an account? Login" opens the Login page
- [ ] "Back" returns to the homepage'

issue M1 'frontend,good-first-task' '[auth] Login page: form, link to Register, error message' \
'Starter view: [resources/views/auth/login.blade.php](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/auth/login.blade.php).

- [ ] 2 fields: email, password
- [ ] The wrong-credentials error is visible
- [ ] "Don'\''t have an account? Register" opens the Register page
- [ ] "Back" returns to the homepage'


# --- M2: Homepage buttons ----------

issue M2 'backend' '[home] Routes for Profile, Leaderboard and Start Quiz' \
'Starter: #5.

- [ ] Routes named `profile` (logged-in users only), `leaderboard` (everyone) and `quiz`
- [ ] `/` returns 200 for guests and for logged-in users
- [ ] `tests/Feature/ExampleTest.php` passes'

issue M2 'frontend' '[home] Homepage for guests: Login, Leaderboard, Start Quiz' \
'- [ ] Audit: "While not logged in, are there three buttons (Login, Leaderboard, Start Quiz) in the main menu?"
- [ ] Profile and Logout are hidden'

issue M2 'frontend' '[home] Homepage when logged in: Profile, Leaderboard, Start Quiz, Logout' \
'- [ ] Audit: "While logged in, are there four buttons (Profile, Leaderboard, Start Quiz, Logout) in the main menu?"
- [ ] Login is hidden
- [ ] Decide the Profile button text: the starter shows the username, the audit says "Profile"'


# --- M3: Start quiz & persistence ----------

issue M3 'backend' '[quiz] Start Quiz redirects guests to Login' \
'Subject: when a guest clicks Start Quiz, go to the Login page instead of starting a quiz.

- [ ] Quiz routes use the `auth` middleware (it already redirects to the route named `login`)
- [ ] Audit: "Start a quiz while not logged in. Were you redirected to the Login page?"'

issue M3 'backend' '[quiz] Pick quiz questions with at least one per category' \
'Subject: at least one question of each category (Art, History, Geography, Science, Sports).

Starter: [app/Http/Controllers/Questions/QuestionController.php:17-28](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/Questions/QuestionController.php#L17-L28) picks 4 random questions per category (20), then shuffles.

- [ ] Category names in the data match the list exactly
- [ ] Every quiz has ≥ 1 question per category
- [ ] A category with fewer questions than asked does not crash
- [ ] Audit: "Does the quiz contain at least one question of each category?"'

issue M3 'backend' '[quiz] Keep the same quiz after a refresh or a return to the homepage' \
'Subject: after starting a quiz, a refresh shows the same questions. Going back home and clicking Start Quiz again shows them too.

Starter: #9, #10.

- [ ] Starting a quiz saves it (user, questions, order) with `completed = 0`
- [ ] Start Quiz with an open quiz returns that quiz
- [ ] After submit (`completed = 1`), Start Quiz creates a new quiz
- [ ] Refresh shows the same questions in the same order'


# --- M4: Question screen & submit rules ----------

issue M4 'frontend' '[quiz] Show every question with its answers' \
'Starter: #11.

- [ ] Each question shows its text and all its answers
- [ ] One answer per question can be chosen
- [ ] Submit button at the bottom
- [ ] Audit: "Were you presented the questions and the available answers for each question?"'

issue M4 'backend' '[quiz] Reject a quiz submit when an answer is missing' \
'Subject: all questions must be answered before the quiz can be submitted.

- [ ] Server-side check: every question of the saved quiz has an answer, and that answer belongs to the question (a browser `required` attribute can be removed, so it is not enough)
- [ ] On error: back to the quiz, already chosen answers stay selected, the quiz stays open
- [ ] Audit: "Try to submit the quiz without answering all of the questions. Were you unable to submit the quiz?"'

issue M4 'frontend' '[quiz] Show an error message when an answer is missing' \
'- [ ] A clear message on the quiz page when the submit is rejected
- [ ] Unanswered questions are easy to find (for example, highlighted)'


# --- M5: Results page ----------

issue M5 'backend' '[results] Count right answers in total and per category' \
'Starter: #12.

- [ ] Only questions of this quiz are counted
- [ ] Totals come from the real numbers (no hardcoded 20 or 4)
- [ ] Results = right answers, total questions, and right answers per category'

issue M5 'backend' '[results] Update XP and category scores after a quiz' \
'Subject: each right answer adds that question'\''s XP to the user. The category score `x/y` is updated: `x` = right answers, `y` = questions answered.

Starter: #12.

- [ ] XP += `question.xp` for each right answer
- [ ] Each category: `x` += right answers, `y` += questions asked in that category
- [ ] Saved once per quiz: submitting the same quiz again changes nothing'

issue M5 'frontend' '[results] Page layout' \
'Starter view: [resources/views/questions/results.blade.php](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/questions/results.blade.php).

- [ ] Right answers / total questions
- [ ] Right answers for each of the 5 categories, with the real totals
- [ ] Links to Home and Profile work
- [ ] Audit: "Were you redirected to a results page, which shows how many answers were right, the number of total questions made and how many questions you answered right in each category?"'


# --- M6: Profile page ----------

issue M6 'backend' '[profile] Rank from XP' \
'Subject ranks:

| XP | Rank |
|---|---|
| < 1500 | Quiz Aprentice |
| 1500 – 5000 | Average Quizer |
| 5000 – 10000 | Epic Quizer |
| ≥ 10000 | Quiz Master |

The subject ranges overlap at 5000 and 10000. Proposal: lower bounds are inclusive, like `≥ 10000`. So 1500–4999, 5000–9999.

- [ ] Agree on the boundaries and on the spelling (the auditor compares with the subject)
- [ ] 1499, 1500, 4999, 5000, 9999, 10000 give the agreed ranks'

issue M6 'backend' '[profile] Right answers, total and percentage per category' \
'Starter: [app/Http/Controllers/ProfileController.php:13-22](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/ProfileController.php#L13-L22) passes 5 empty arrays.

- [ ] For each category: right answers (`x`), questions answered (`y`), percentage
- [ ] `y = 0` shows 0% (no division by zero)
- [ ] Agree on rounding (for example, whole numbers)'

issue M6 'frontend' '[profile] Page layout and access' \
'Starter view: [resources/views/profile.blade.php](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/profile.blade.php).

- [ ] Username, email, XP, rank
- [ ] For each category: percentage, right answers, total answered
- [ ] Route behind `auth`. The page shows only the logged-in user and never takes a user id from the URL.
- [ ] Guests who open `/profile` go to Login
- [ ] Audit: "Are the following all present: username, email, XP, correct rank, percentage of correct answers for each category, number of correct answers for each category and number of total questions answered for each category?"'


# --- M7: Leaderboard ----------

issue M7 'backend' '[leaderboard] Top 10 users by XP' \
'Starter: [app/Http/Controllers/LeaderboardController.php:9-12](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/app/Http/Controllers/LeaderboardController.php#L9-L12) only returns the view.

- [ ] Top 10 users, highest XP first
- [ ] Total right answers per user (as agreed in #3)
- [ ] Agree on the order when XP is equal
- [ ] Open to guests (the homepage shows Leaderboard to guests)'

issue M7 'frontend' '[leaderboard] Table: username, XP, total right answers' \
'Starter view: [resources/views/leaderboard.blade.php](https://platform.zone01.gr/git/hmim/mister-quiz/src/branch/dev/resources/views/leaderboard.blade.php) has an empty table.

- [ ] Header row and one row per player: username, XP, total right answers
- [ ] Fewer than 10 users → only those rows; no users → a short message
- [ ] Link back to the homepage
- [ ] Audit: "Is there a table of the top 10 players organized by XP amount?" and "Does each row contain the username, the XP amount and the total correct answers?"'


# --- M8: Testing & polish ----------

issue M8 'backend' '[test] Feature tests: register, login, quiz, results' \
'Before: #13, #14.

- [ ] Register: success, duplicate email, confirmation mismatch
- [ ] Login: success, wrong credentials; logout
- [ ] Homepage buttons for guests and for logged-in users
- [ ] Start Quiz as guest → Login; ≥ 1 question per category; same quiz after reload
- [ ] Submit with a missing answer is rejected
- [ ] Results, XP and category scores; submitting twice adds nothing
- [ ] Profile rank boundaries; leaderboard top 10 order
- [ ] `php artisan test` is green'

issue M8 'frontend,good-first-task' '[docs] Visual check against the audit checklist' \
'On a fresh clone, run `docker compose up -d --build`, load the question data, then go through every question in `_docs/mister-quiz_audit.md`.

- [ ] Post one comment with each audit question and ✅ or ❌
- [ ] Open a `bug` issue for each ❌'

issue M8 '' '[docs] Code review before merging dev into main' \
'- [ ] Every feature PR into `dev` is reviewed by the other teammate
- [ ] `main` is protected: no direct pushes, 1 approval needed
- [ ] Final PR `dev` → `main` before the audit, after the visual check'

# ---------------------------------------------------------------------------
# 7. Issue dependencies
# ---------------------------------------------------------------------------
# Encodes the order the work actually has to happen in, so a blocked issue says
# so on its own page and cannot be closed early. This is what makes it safe to
# let either of us pick freely inside a milestone.
#
# Only hard blockers are listed. Anything the milestone order already
# guarantees is left out. Two rules explain the direction of the arrows:
#   - a starter bug (#5, #6, #7, #8, #10, #11) is fixed first; the feature issue
#     that cites it ("Starter: #N") follows it
#   - #9 and #12 are umbrella reports that end with "Expected: see the M-issues";
#     they close last, after the issues they list
step "Issue dependencies"

depends_on() { # BLOCKED_INDEX BLOCKER_INDEX...
    local blocked="$1"
    shift
    local existing blocker
    existing="$(api_read "/repos/$REPO/issues/$blocked/dependencies" \
        | python3 -c 'import json,sys
d = json.load(sys.stdin)
for i in (d if isinstance(d, list) else []): print(i["number"])')"
    for blocker in "$@"; do
        if printf '%s\n' "$existing" | grep -Fxq "$blocker"; then
            skip "#$blocked blocked by #$blocker"
        else
            # The body is a Gitea IssueMeta. Its repository field is tagged
            # "repo", not "name": with "name" the server looks up the empty
            # repository and answers 404.
            api_write POST "/repos/$REPO/issues/$blocked/dependencies" \
                -F "index=$blocker" \
                -f "owner=$OWNER" \
                -f "repo=${REPO#*/}"
            add "#$blocked blocked by #$blocker"
        fi
    done
}

depends_on 9 23 25 26  # umbrella: shown (26), saved (25), guests redirected (23)
depends_on 10 3        # one table and one set of names, agreed in the schema review
depends_on 12 29 30 31 # umbrella: counting, XP and category scores, results page
depends_on 15 7        # the register checklist includes the duplicate-email fix
depends_on 16 6        # login must authenticate before the feature can be checked
depends_on 17 8        # logout must be a POST route first
depends_on 18 15       # the register page shows the errors the backend produces
depends_on 19 16       # the login page shows the error the backend produces
depends_on 20 5        # the homepage bug adds the missing route names first
depends_on 21 20       # the guest buttons call route('leaderboard') and route('quiz')
depends_on 22 20       # the logged-in buttons also call route('profile')
depends_on 23 20       # the auth middleware goes on the quiz route
depends_on 24 4        # category names must match the seeded data
depends_on 25 10       # saving a quiz needs the quiz <-> question table
depends_on 26 11 25    # the page needs answer choices and a saved quiz to show
depends_on 27 25       # checks the answers against the saved quiz
depends_on 28 27       # shows the rejection from the submit check
depends_on 29 25       # counts the answers of the saved quiz
depends_on 30 3 29     # needs the counts, and how totals are stored (schema review)
depends_on 31 29       # shows the counts
depends_on 34 32 33    # the page shows the rank and the percentages
depends_on 35 3        # total right answers: how it is stored is decided in the review
depends_on 36 35       # the table shows what the query returns
depends_on 37 13 14    # no tests without a working factory and a test database
depends_on 38 37       # the visual check is run after the tests are green
depends_on 39 38       # the release PR dev -> main comes after the visual check

printf '\n=== Summary ===\n  created: %d\n  already present: %d\n' "$created" "$skipped"
[ "$SYNC_BODIES" = "1" ] && printf '  bodies synced: %d\n' "$synced"
say "Re-running this script creates nothing."
