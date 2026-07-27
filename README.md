# Golf Scoring API

Laravel 12 backend for the [Golf Scoring app](../../WEB/golf-scoring). Handles auth, courses, players, rounds, tournaments, and password reset over transactional email.

## What it does

- **Auth** — phone-number login with Sanctum tokens; register via phone or 6-digit invite code.
- **Password reset** — request via email, single-use token stored hashed, expires in 60 min. Email is themed HTML and links back into the app via a `golfscoring://` deep link.
- **Courses** — CRUD for courses, their tees, and per-tee hole configurations (par + stroke index).
- **Friends & players** — friend graph, guest players, per-user handicaps.
- **Rounds** — 10 scoring methods (Stroke, Stableford + variants with Pink/Animals, Match Play, Medal, Four/Two Ball Alliance, Better/Worst Ball). Individual, team, and alliance formats. Full per-hole score storage.
- **Tournaments** — invite-code lookup, multi-round tournaments, per-method leaderboard calculations (individual, team, alliance best-N-by-par).

## Tech stack

- Laravel 12 (PHP 8.4)
- PostgreSQL (with Postgres-specific columns like `jsonb` for `scoring_config`)
- Laravel Sanctum for API tokens
- Brevo SMTP for transactional email (300/day free tier)
- Blade for email templates

## Getting started

Prerequisites: **PHP 8.4+**, **Composer**, **PostgreSQL 14+**.

```bash
git clone <this-repo>
cd golf_scoring_api
composer install
cp .env.example .env
php artisan key:generate
# Edit .env — DB connection, mail credentials (see below)
php artisan migrate --seed
php artisan serve
```

The API is now on `http://127.0.0.1:8000` and the app (`VITE_API_BASE_URL=http://127.0.0.1:8000/api/`) can talk to it.

### Environment variables to set

Copy `.env.example` and fill in at minimum:

**Database:**
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=golf_scoring
DB_USERNAME=your_username
DB_PASSWORD=
```

**Mail (Brevo SMTP — free tier):**
```
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=<your-brevo-smtp-login>
MAIL_PASSWORD=<your-brevo-smtp-key>   # starts with "xsmtpsib-…"
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Test the mail pipeline any time with:
```bash
php artisan mail:test you@example.com
```

## API surface (public)

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/api/register` | Register with phone + password + name + surname + email |
| `POST` | `/api/register/invite` | Complete registration via 6-digit invite code |
| `POST` | `/api/login` | Phone + password → Sanctum token |
| `POST` | `/api/password/forgot` | Request reset link by email |
| `POST` | `/api/password/reset` | Submit new password with the emailed token |

## API surface (auth via `Authorization: Bearer <token>`)

| Method | Path | Purpose |
|---|---|---|
| `GET`/`PUT` | `/api/user` | Get / update profile |
| `PUT` | `/api/user/password` | Change password (current + new) |
| `GET`/`POST`/`DELETE` | `/api/friends` | Friend list & invites |
| `GET`/`POST` | `/api/players` | Guest / registered player search |
| `GET`/`POST` | `/api/courses` | Courses + tees + holes |
| `GET`/`POST` | `/api/rounds` | Rounds + per-hole scoring |
| `GET`/`POST` | `/api/tournaments` | Tournaments + rounds/players/results |
| `GET` | `/api/tournaments/lookup/{code}` | Resolve a tournament by invite code |
| `GET` | `/api/scoring-methods` | List available scoring methods |

## Data model highlights

- `scoring_methods` — 11 rows (Stroke Play through Two Ball Alliance).
- `courses` → `course_tees` → `course_holes` — per-tee hole config.
- `rounds` → `round_users`, `round_teams`, `round_holes`, `hole_scores` — a full snapshot of the played round with per-hole strokes + points, plus a jsonb `scoring_config` (e.g. alliance best-N-by-par).
- `tournaments` → `tournament_rounds` → `rounds`.
- `password_reset_tokens` — email-keyed, hashed token, 60-min TTL.

## Local dev helpers

Run one-off PHP scripts via tinker with the file arg — but **always append `< /dev/null`** so PsySH exits after the file finishes:

```bash
php artisan tinker path/to/script.php < /dev/null
```

## Related

The companion React + Capacitor client lives at [`Golf_scoring_app`](https://github.com/xanderhattingh/Golf_scoring_app).
