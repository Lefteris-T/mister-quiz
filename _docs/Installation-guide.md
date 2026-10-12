# 📖 Οδηγός Εγκατάστασης - Mister Quiz Project

***Έκδοση*:** 2.0  
***Ημερομηνία*:** Σεπτέμβριος 2026  
***Για*:** Ομάδα 2 ατόμων  
***Tutorial Series:*** [Laravel Tutorials for Beginners - Youtube](https://www.youtube.com/playlist?list=PL0eyrZgxdwhy7Woo2VRRDMmTXXYT_iaYO)  
***Τεκμηρίωση Laravel 8:*** [laravel.com/docs/8.x](https://laravel.com/docs/8.x)

---

> ⚠️ **ΥΠΟΧΡΕΩΤΙΚΟ**
>
> - Ακολουθείς τα βήματα **με τη σειρά** και **ακριβώς** όπως είναι γραμμένα.
> - Εγκαθιστάς **μόνο** τις εκδόσεις του πίνακα [Εκδόσεις Ομάδας](#-εκδόσεις-ομάδας).
> - Αν μια εντολή δεν δείχνει το αναμενόμενο αποτέλεσμα: **σταματάς** και ρωτάς την ομάδα. Δεν «διορθώνεις» μόνος/μόνη σου με άλλη έκδοση ή άλλο εργαλείο.

---

## 📋 Περιεχόμενα

1. [Κανόνες Ομάδας](#-κανόνες-ομάδας)
2. [Εκδόσεις Ομάδας](#-εκδόσεις-ομάδας)
3. [Γιατί Αυτές οι Εκδόσεις](#-γιατί-αυτές-οι-εκδόσεις)
4. [Linux (Ubuntu 26.04 LTS) - Εγκατάσταση](#-linux-ubuntu-2604-lts---εγκατάσταση)
5. [Windows (WSL2 + Docker Desktop) - Εγκατάσταση](#-windows-wsl2--docker-desktop---εγκατάσταση)
6. [Ρύθμιση Mister Quiz Project](#-ρύθμιση-mister-quiz-project)
7. [Έλεγχος Εγκατάστασης](#-έλεγχος-εγκατάστασης)
8. [Επίλυση Προβλημάτων](#-επίλυση-προβλημάτων)
9. [Χρήσιμες Εντολές](#-χρήσιμες-εντολές)
10. [Σημειώσεις](#-σημειώσεις)

---

## 🚦 Κανόνες Ομάδας

### ✅ Επιτρέπεται

- PHP, Composer, MySQL και phpMyAdmin τρέχουν **μόνο μέσα από Docker**.
- Στο terminal, οι εντολές `php` και `composer` είναι τα **wrappers** του project (`docker/bin/php`, `docker/bin/composer`). Τρέχουν την ίδια PHP με το Docker.
- `composer install` (εγκαθιστά ακριβώς ό,τι γράφει το `composer.lock`).

### ❌ Απαγορεύεται

- XAMPP, WAMP, Laragon, MAMP ή οποιαδήποτε PHP εγκατεστημένη στον υπολογιστή (apt, php.net, Homebrew) **για αυτό το project**.
- MariaDB ή άλλη MySQL εκτός από το Docker service `db`.
- `composer update`, `composer require`, `laravel new`, αναβάθμιση Laravel ή PHP.
- Αλλαγές στα `Dockerfile`, `docker-compose.yml`, `docker/`, `composer.json`, `composer.lock`, `.env.example` **χωρίς** Gitea issue και PR εγκεκριμένο και από τα 2 μέλη.
- Αλλαγή των τιμών `DB_*` στο `.env`.
- Update του Docker (Linux: τα πακέτα είναι «κλειδωμένα» με `apt-mark hold` · Windows: αγνοείς τις ειδοποιήσεις update του Docker Desktop).

### 📨 Πριν γράψεις κώδικα

Κάνεις τον [Έλεγχο Εγκατάστασης](#-έλεγχος-εγκατάστασης) και στέλνεις την έξοδο των εντολών στην ομάδα (σχόλιο στο Gitea issue **#2** του **M0 — Environment & shared schema**).

---

## 📌 Εκδόσεις Ομάδας

| Εργαλείο | Ακριβής έκδοση | Πού τρέχει | Έλεγχος | Αναμενόμενη έξοδος |
|---|---|---|---|---|
| Ubuntu | **26.04 LTS** | Linux: ο υπολογιστής · Windows: WSL2 distro `Ubuntu-26.04` | `lsb_release -ds` | `Ubuntu 26.04 LTS` |
| Docker Engine | **29.6.1** | Linux: `docker-ce` · Windows: Docker Desktop **4.81.0** | `docker --version` | `Docker version 29.6.1, build …` |
| Docker Compose | **v5.2.0** | Linux: `docker-compose-plugin` · Windows: Docker Desktop **4.81.0** | `docker compose version` | `Docker Compose version v5.2.0` |
| containerd.io | **2.2.5** | Linux | `dpkg -s containerd.io \| grep Version` | `Version: 2.2.5-1~ubuntu.26.04~resolute` |
| Docker Buildx | **0.35.0** | Linux: `docker-buildx-plugin` · Windows: Docker Desktop **4.81.0** | `docker buildx version` | `github.com/docker/buildx v0.35.0 …` |
| Git | **2.53.0** | Linux / WSL | `git --version` | `git version 2.53.0` |
| PHP | **8.0.30** | Docker image `php:8.0.30-cli-bullseye` | `php -v` | `PHP 8.0.30 (cli) (built: Nov 21 2023 16:10:15) ( NTS )` |
| PHP extensions | `pdo_mysql`, `zip` + ενσωματωμένα (`mbstring`, `xml`, `dom`, `curl`, `tokenizer`, `json`, `openssl`, `ctype`, `fileinfo`, `pdo_sqlite`) | Docker image | `php -m` | Περιέχει όλα τα ονόματα |
| Composer | **2.9.5** | Docker image `composer:2.9.5` | `composer --version` | `Composer version 2.9.5 2026-01-29 11:40:53` |
| Laravel Framework | **8.33.0** | Κλειδωμένο στο `composer.lock` | `php artisan --version` | `Laravel Framework 8.33.0` |
| MySQL | **8.4.11** | Docker image `mysql:8.4.11` | `docker compose exec db mysql --version` | `mysql  Ver 8.4.11 for Linux on x86_64 (MySQL Community Server - GPL)` (`aarch64` αντί για `x86_64` σε επεξεργαστή ARM, π.χ. Mac M1) |
| phpMyAdmin | **5.2.3** | Docker image `phpmyadmin:5.2.3` | Άνοιξε `http://localhost:8080` | Σελίδα σύνδεσης phpMyAdmin |
| tea (Gitea CLI) | **0.15.1** | Linux / WSL · μόνο όποιος διαχειρίζεται το Gitea | `tea --version` | `Version: 0.15.1 …` |
| VS Code | Τελευταία έκδοση | Windows: + extension **WSL** | — | — |

---

## ❓ Γιατί Αυτές οι Εκδόσεις

1. Ο κώδικας που πήραμε (starter) έχει κλειδωμένο το **Laravel 8.33.0**.
2. Το Laravel 8.33.0 δουλεύει **μέχρι PHP 8.0**. Το δοκιμάσαμε:
   - PHP 8.0.30 → `Laravel Framework 8.33.0` ✅
   - PHP 8.1 → 21 μηνύματα `Deprecated: Return type of Illuminate\…` και τερματισμός με σφάλμα ❌
3. Η PHP 8.0 **δεν εγκαθίσταται πια** σε σύγχρονα Ubuntu (λείπουν βιβλιοθήκες που χρειάζεται).
4. Γι' αυτό η PHP 8.0.30 τρέχει στο **Docker** και για τα 2 μέλη. Έτσι όλοι έχουμε ακριβώς το ίδιο περιβάλλον.

> 🔒 Η PHP 8.0 δεν παίρνει πια ενημερώσεις ασφαλείας. Το project τρέχει **μόνο τοπικά** για ανάπτυξη. Δεν το ανεβάζουμε ποτέ σε δημόσιο server.

---

## 🐧 Linux (Ubuntu 26.04 LTS) - Εγκατάσταση

### Βήμα L1: Έλεγχος έκδοσης Ubuntu

```bash
lsb_release -ds
```

Πρέπει να δεις:
```
Ubuntu 26.04 LTS
```

Αν δεις κάτι άλλο: **σταμάτα** και ρώτα την ομάδα.

---

### Βήμα L2: Αφαίρεση παλιών πακέτων Docker

Αφαιρεί πακέτα που συγκρούονται με το επίσημο Docker (αν δεν υπάρχουν, απλά δεν κάνει τίποτα):

```bash
for pkg in docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc; do sudo apt-get remove -y $pkg; done
```

---

### Βήμα L3: Προσθήκη του επίσημου Docker repository

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
```

---

### Βήμα L4: Εγκατάσταση Docker στις ακριβείς εκδόσεις

```bash
sudo apt-get install -y \
  docker-ce=5:29.6.1-1~ubuntu.26.04~resolute \
  docker-ce-cli=5:29.6.1-1~ubuntu.26.04~resolute \
  containerd.io=2.2.5-1~ubuntu.26.04~resolute \
  docker-buildx-plugin=0.35.0-1~ubuntu.26.04~resolute \
  docker-compose-plugin=5.2.0-1~ubuntu.26.04~resolute
```

«Κλείδωσε» τις εκδόσεις, ώστε το `apt upgrade` να μην τις αλλάξει:

```bash
sudo apt-mark hold docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

---

### Βήμα L5: Docker χωρίς `sudo`

```bash
sudo usermod -aG docker $USER
```

Μετά κάνε **αποσύνδεση και ξανά σύνδεση** (ή restart τον υπολογιστή).

Έλεγχος:
```bash
docker --version
docker compose version
docker run --rm hello-world
```

Πρέπει να δεις `Docker version 29.6.1`, `Docker Compose version v5.2.0` και το μήνυμα `Hello from Docker!`.

---

### Βήμα L6: Εγκατάσταση Git

```bash
sudo apt-get install -y git
git --version
```

Πρέπει να δεις:
```
git version 2.53.0
```

---

### Βήμα L7: VS Code

Εγκατάσταση από **https://code.visualstudio.com/**.

✅ Τέλος εγκατάστασης για Linux. Συνέχισε στη [Ρύθμιση Mister Quiz Project](#-ρύθμιση-mister-quiz-project).

---

## 🪟 Windows (WSL2 + Docker Desktop) - Εγκατάσταση

Στα Windows **δεν** εγκαθιστάς PHP, Composer, XAMPP ή MySQL. Δουλεύεις μέσα σε Ubuntu 26.04 (WSL2), με το Docker Desktop.

### Βήμα W1: Προϋποθέσεις

- Windows 11 ή Windows 10 έκδοση 22H2.
- Virtualization ενεργό στο BIOS (Task Manager → Performance → CPU → `Virtualization: Enabled`).

---

### Βήμα W2: Εγκατάσταση WSL2 με Ubuntu 26.04

1. Άνοιξε **PowerShell ως Administrator**.
2. Τρέξε:
   ```powershell
   wsl --install -d Ubuntu-26.04
   ```
3. Κάνε **restart** τον υπολογιστή.
4. Άνοιξε **Ubuntu 26.04** από το Start menu. Φτιάξε username και password για το Linux.
5. Έλεγχος στο PowerShell:
   ```powershell
   wsl -l -v
   ```
   Πρέπει να δεις `Ubuntu-26.04` με `VERSION` **2**.

---

### Βήμα W3: Εγκατάσταση Docker Desktop 4.81.0

1. Πήγαινε στο **https://docs.docker.com/desktop/release-notes/#4810** και κατέβασε το **Docker Desktop 4.81.0 για Windows**.
2. Κάνε install. Κράτα επιλεγμένο το **Use WSL 2 instead of Hyper-V**.
3. Άνοιξε το Docker Desktop → **Settings** → **Resources** → **WSL integration** → ενεργοποίησε το **Ubuntu-26.04** → **Apply & restart**.
4. **Μην** κάνεις update το Docker Desktop (αγνόησε τις ειδοποιήσεις).
5. Έλεγχος στο terminal **Ubuntu 26.04**:
   ```bash
   docker --version
   docker compose version
   docker run --rm hello-world
   ```
   Πρέπει να δεις `Docker version 29.6.1`, `Docker Compose version v5.2.0` και `Hello from Docker!`.

---

### Βήμα W4: Εγκατάσταση Git μέσα στο Ubuntu 26.04

```bash
sudo apt-get update
sudo apt-get install -y git
git --version
```

Πρέπει να δεις:
```
git version 2.53.0
```

---

### Βήμα W5: VS Code

1. Εγκατάσταση VS Code στα Windows από **https://code.visualstudio.com/**.
2. Εγκατάσταση του extension **WSL** (`ms-vscode-remote.remote-wsl`).

> ⚠️ Από εδώ και πέρα, **όλες** οι εντολές τρέχουν στο terminal **Ubuntu 26.04**, όχι στο PowerShell.
> Ο φάκελος του project μπαίνει μέσα στο Linux (π.χ. `~/projects`), **όχι** στο `C:\` ή στο `/mnt/c`.

✅ Τέλος εγκατάστασης για Windows. Συνέχισε στη [Ρύθμιση Mister Quiz Project](#-ρύθμιση-mister-quiz-project).

---

## ⚙️ Ρύθμιση Mister Quiz Project

Ίδια βήματα για Linux και Windows (στο terminal Ubuntu 26.04).

### Βήμα 1: Clone από το Gitea

Το repo είναι **private**. Χρειάζεσαι πρόσβαση collaborator.

```bash
mkdir -p ~/projects
cd ~/projects
git clone https://platform.zone01.gr/git/hmim/mister-quiz.git
cd mister-quiz
git switch dev
```

---

### Βήμα 2: Δημιουργία `.env`

```bash
cp .env.example .env
```

**Μην αλλάξεις** τις τιμές `DB_*`. Είναι ίδιες για όλη την ομάδα.

Το container τρέχει αυτόματα με τον δικό σου Linux χρήστη. Δεν χρειάζεται ρύθμιση `UID`/`GID`.

---

### Βήμα 3: Build του Docker image

```bash
docker compose build app
```

> ⏳ Την πρώτη φορά παίρνει μερικά λεπτά.

---

### Βήμα 4: Εντολές `php` και `composer` (wrappers)

Συνδέεις τα wrappers του project στο `~/.local/bin`:

```bash
mkdir -p ~/.local/bin
ln -s "$PWD/docker/bin/php" ~/.local/bin/php
ln -s "$PWD/docker/bin/composer" ~/.local/bin/composer
```

**Κλείσε το terminal και άνοιξε νέο.** Μετά:

```bash
cd ~/projects/mister-quiz
type php
php -v
composer --version
```

Πρέπει να δεις:
```
php is /home/<το-username-σου>/.local/bin/php
PHP 8.0.30 (cli) (built: Nov 21 2023 16:10:15) ( NTS )
Composer version 2.9.5 2026-01-29 11:40:53
```

Στο macOS ο φάκελος είναι `/Users/<το-username-σου>/…` αντί για `/home/…`.

Αν το `type php` δείχνει κάτι άλλο (π.χ. `/usr/bin/php`), δες [«Το php -v δείχνει άλλη έκδοση»](#-το-php--v-δείχνει-άλλη-έκδοση).

---

### Βήμα 5: Εγκατάσταση PHP dependencies

```bash
composer install
```

Πρέπει να τελειώσει με:
```
Package manifest generated successfully.
```

> ❌ **Ποτέ** `composer update`.

---

### Βήμα 6: Application key

```bash
php artisan key:generate
```

Πρέπει να δεις:
```
Application key set successfully.
```

---

### Βήμα 7: Εκκίνηση του project

```bash
docker compose up -d
```

Το `app` container τρέχει αυτόματα τα migrations πριν ξεκινήσει.

Έλεγχος (περίμενε ~30 δευτερόλεπτα την πρώτη φορά):
```bash
docker compose ps
php artisan migrate:status
```

- Το `db` πρέπει να είναι `(healthy)`. Τα `app` και `phpmyadmin` πρέπει να είναι `Up`.
- Και τα **7** migrations πρέπει να έχουν `Yes`.

---

### Βήμα 8: Φόρτωση ερωτήσεων

```bash
php artisan db:seed
```

Ο seeder φορτώνει τις 60 ερωτήσεις και τις 240 απαντήσεις. Μπορείς να τρέξεις την εντολή ξανά χωρίς να δημιουργηθούν διπλές εγγραφές.

Έλεγχος:
```bash
docker compose exec db mysql -umister_quiz -psecret -e "select category, count(*) from question group by category" mister_quiz
```

Πρέπει να δεις **5** κατηγορίες (`Art`, `Geography`, `History`, `Science`, `Sports`) με **12** ερωτήσεις η καθεμία.

---

### Βήμα 9: Άνοιγμα στον browser

| Σελίδα | URL | Τι πρέπει να δεις |
|---|---|---|
| Login | **http://localhost:8000/login** | Τη φόρμα «Login form» |
| Register | **http://localhost:8000/register** | Τη φόρμα «Register form» |
| phpMyAdmin | **http://localhost:8080** | Σύνδεση με `mister_quiz` / `secret` |

> ℹ️ Η αρχική σελίδα `http://localhost:8000/` δείχνει σφάλμα `Route [leaderboard] not defined`. **Είναι γνωστό θέμα του starter** και διορθώνεται στο **Milestone 2**. Δεν είναι λάθος εγκατάστασης.

✅ Η εγκατάσταση ολοκληρώθηκε. Κάνε τον [Έλεγχο Εγκατάστασης](#-έλεγχος-εγκατάστασης).

---

## ✅ Έλεγχος Εγκατάστασης

Τρέξε **όλες** τις εντολές μέσα στον φάκελο του project και στείλε την έξοδο στην ομάδα.

```bash
uname -srm
[ -r /etc/os-release ] && (. /etc/os-release; echo "$PRETTY_NAME")
docker --version
docker compose version
git --version
type php
php -v
composer --version
php artisan --version
docker compose exec db mysql --version
docker compose ps
php artisan migrate:status
php artisan test
```

> ℹ️ Οι εντολές τρέχουν σε κάθε σύστημα με terminal τύπου Unix: Linux (οποιαδήποτε διανομή), WSL2 στα Windows και macOS. Στα Windows τρέχουν στο terminal **Ubuntu 26.04**, όχι στο PowerShell.

| Εντολή | Αναμενόμενο αποτέλεσμα |
|---|---|
| `uname -srm` | Windows: πρέπει να περιέχει `WSL2`, π.χ. `Linux 6.6.x-microsoft-standard-WSL2 x86_64`. Linux: π.χ. `Linux 7.0.0-38-generic x86_64` (ο αριθμός του kernel διαφέρει). Άλλο σύστημα, π.χ. Mac: `Darwin 25.x arm64`: στείλ' το στην ομάδα, οι υπόλοιπες γραμμές πρέπει να δώσουν τα ίδια αποτελέσματα |
| `[ -r /etc/os-release ] && …` | Linux και WSL2: `Ubuntu 26.04 LTS`. Στο macOS δεν τυπώνει τίποτα, είναι φυσιολογικό |
| `docker --version` | Αρχίζει με `Docker version 29.6.1` |
| `docker compose version` | `Docker Compose version v5.2.0` |
| `git --version` | `git version 2.53.0` |
| `type php` | `php is <το-home-σου>/.local/bin/php`. Linux: `/home/<username>/…`, macOS: `/Users/<username>/…` |
| `php -v` | `PHP 8.0.30 (cli) …` |
| `composer --version` | `Composer version 2.9.5 …` |
| `php artisan --version` | `Laravel Framework 8.33.0` |
| `docker compose exec db mysql --version` | `mysql  Ver 8.4.11 …` (στο τέλος `x86_64` ή `aarch64`, ανάλογα με τον επεξεργαστή) |
| `docker compose ps` | `db` `(healthy)`, `app` `Up`, `phpmyadmin` `Up` |
| `php artisan migrate:status` | 7 migrations με `Yes` |
| `php artisan test` | `Tests: 1 failed, 1 passed` — το failed είναι το test της αρχικής σελίδας `/` (Milestone 2) |

---

## 🆘 Επίλυση Προβλημάτων

### ❌ Το `php -v` δείχνει άλλη έκδοση

Υπάρχει άλλη PHP πριν από το wrapper στο `PATH`.

```bash
type -a php
```

Πρέπει η **πρώτη** γραμμή να είναι `~/.local/bin/php`. Αν όχι:

```bash
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
```

Κλείσε και άνοιξε νέο terminal. Ξαναέλεγξε με `type php`.

---

### ❌ `php wrapper: image 'mister-quiz-app:dev' not found`

Δεν έχει γίνει build το image:

```bash
docker compose build app
```

---

### ❌ `Deprecated: Return type of Illuminate\…` ή `Implicitly marking parameter … as nullable is deprecated`

Το project τρέχει με **λάθος PHP** (8.1 ή νεότερη). Δες [«Το php -v δείχνει άλλη έκδοση»](#-το-php--v-δείχνει-άλλη-έκδοση).

---

### ❌ Composer: `requires php … <8.1 -> your php version (8.x) does not satisfy that requirement`

Ίδια αιτία: δεν χρησιμοποιείς το wrapper με PHP 8.0.30. **Μην** τρέξεις `composer update`.

---

### ❌ `permission denied while trying to connect to the Docker daemon socket`

- **Linux:** δεν έγινε αποσύνδεση/σύνδεση μετά το [Βήμα L5](#βήμα-l5-docker-χωρίς-sudo).
- **Windows:** το Docker Desktop δεν τρέχει ή λείπει το **WSL integration** για το `Ubuntu-26.04` ([Βήμα W3](#βήμα-w3-εγκατάσταση-docker-desktop-4810)).

---

### ❌ `Bind for 0.0.0.0:8000 failed: port is already allocated`

Κάποιο άλλο πρόγραμμα χρησιμοποιεί την port (π.χ. παλιό XAMPP ή άλλο project).

1. Σταμάτα το άλλο πρόγραμμα. Αυτή είναι η προτιμώμενη λύση.
2. Αν δεν γίνεται, άλλαξε την port **μόνο** στο δικό σου `.env`:

| Port σε χρήση | Γραμμή στο `.env` | Τι αλλάζει μετά |
|---|---|---|
| 8000 (app) | `APP_PORT=8001` | Ανοίγεις `http://localhost:8001` |
| 8080 (phpMyAdmin) | `PHPMYADMIN_PORT=8081` | Ανοίγεις `http://localhost:8081` |

Η MySQL **δεν** χρησιμοποιεί port του υπολογιστή (μένει μέσα στο Docker). Άρα δεν συγκρούεται με άλλη MySQL.

Μετά:
```bash
docker compose up -d
```

---

### ❌ `SQLSTATE[HY000] [2002]` (`Connection refused` ή `getaddrinfo failed`)

Η βάση δεν τρέχει ή δεν έχει ξεκινήσει ακόμα. Οι εντολές `php artisan …` που χρειάζονται βάση δουλεύουν **μόνο** όταν τρέχει το project:

```bash
docker compose up -d
docker compose ps
```

Το `db` πρέπει να είναι `(healthy)`.

Αν συνεχίζει: έλεγξε ότι το `.env` έχει `DB_HOST=db`, όπως το `.env.example`.

---

### ❌ `SQLSTATE[HY000] [1045] Access denied for user 'mister_quiz'`

1. Επανάφερε τις τιμές `DB_*` στο `.env` ώστε να είναι ίδιες με το `.env.example`.
2. Αν συνεχίζει, η βάση δημιουργήθηκε κάποτε με άλλους κωδικούς. Σβήσε την και ξαναφτιάξ' την (⚠️ χάνονται τα δεδομένα):
   ```bash
   docker compose down -v
   docker compose up -d
   ```
3. Ξανακάνε το [Βήμα 8](#βήμα-8-εισαγωγή-ερωτήσεων-μία-φορά-μόνο).

---

### ❌ Διπλές ερωτήσεις

```bash
php artisan migrate:fresh
```

⚠️ Σβήνει **όλους** τους πίνακες (και τους χρήστες). Μετά ξανακάνε το [Βήμα 8](#βήμα-8-εισαγωγή-ερωτήσεων-μία-φορά-μόνο) **μία** φορά.

---

### ❌ `Route [leaderboard] not defined` στην αρχική σελίδα

Γνωστό θέμα του starter. Δεν είναι λάθος εγκατάστασης. Διορθώνεται στο **Milestone 2**.

---

### ❌ `E: Version '5:29.6.1-1~ubuntu.26.04~resolute' for 'docker-ce' was not found`

- Δεν είσαι σε Ubuntu 26.04 ([Βήμα L1](#βήμα-l1-έλεγχος-έκδοσης-ubuntu)), ή
- δεν προστέθηκε το Docker repository ([Βήμα L3](#βήμα-l3-προσθήκη-του-επίσημου-docker-repository)).

---

### ❌ Windows: όλα είναι πολύ αργά ή βγαίνουν σφάλματα δικαιωμάτων

Ο φάκελος του project είναι στο `C:\` ή στο `/mnt/c`. Κάνε clone ξανά μέσα στο `~/projects` του Ubuntu 26.04.

---

## 📞 Χρήσιμες Εντολές

| Εντολή | Τι κάνει | Επιτρέπεται; |
|---|---|---|
| `docker compose up -d` | Ξεκινά app, βάση, phpMyAdmin | ✅ |
| `docker compose ps` | Δείχνει την κατάσταση των services | ✅ |
| `docker compose logs -f app` | Δείχνει τα logs του app (έξοδος: `Ctrl+C`) | ✅ |
| `docker compose down` | Σταματά τα services (η βάση **μένει**) | ✅ |
| `docker compose down -v` | Σταματά τα services και **σβήνει τη βάση** | ⚠️ Μόνο αν το χρειάζεσαι |
| `php artisan migrate` | Τρέχει νέα migrations | ✅ |
| `php artisan migrate:fresh` | Σβήνει όλους τους πίνακες και τους ξαναφτιάχνει | ⚠️ Χάνονται δεδομένα |
| `php artisan route:list` | Δείχνει όλα τα routes | ✅ |
| `php artisan tinker` | Interactive PHP shell | ✅ |
| `php artisan test` | Τρέχει τα tests | ✅ |
| `composer install` | Εγκαθιστά dependencies από το `composer.lock` | ✅ |
| `composer update` / `composer require` | Αλλάζει εκδόσεις πακέτων | ❌ Απαγορεύεται |
| `docker compose exec db mysql -umister_quiz -psecret mister_quiz` | MySQL shell | ✅ |

---

## 📝 Σημειώσεις

- Μη δημοσιοποιείς το `.env` αρχείο. Είναι ήδη στο `.gitignore`.
- Οι κωδικοί `mister_quiz` / `secret` είναι **μόνο** για τοπική ανάπτυξη.
- Αλλαγή σε έκδοση εργαλείου = Gitea issue + PR + έγκριση και από τα 2 μέλη + ενημέρωση **αυτού** του οδηγού.
- Τεκμηρίωση:
  - Laravel 8: https://laravel.com/docs/8.x
  - Docker: https://docs.docker.com/
  - WSL: https://learn.microsoft.com/windows/wsl/

---

**Καλή επιτυχία! 🚀**

Αν κάτι δεν ταιριάζει με αυτόν τον οδηγό: **σταμάτα και ρώτα την ομάδα**.
