# Movie Review System

Platform review film dengan **Autonomous Review Filter** — penyaring otomatis ulasan palsu/spam menggunakan pipeline 8 rule (length, vowel, blacklist keyword, URL detection, cooldown, one-per-movie, content duplicate, hourly quota). Tampilan **cinematic dark theme** ala Letterboxd dengan **score badge ala Metacritic** (0–100, color-coded green/yellow/red).

Lihat detail desain & analisis kelemahan di [`task & konteks/prd.md`](task%20%26%20konteks/prd.md).

## Stack

- **Backend**: Laravel 13 + Inertia 3 + Fortify (auth, 2FA, passkeys)
- **Frontend**: Vue 3 + TypeScript + Tailwind v4 + shadcn-vue (reka-ui)
- **DB**: MySQL/MariaDB (utf8mb4)
- **Test**: Pest 4 (PHP feature tests)
- **PHP**: ≥ 8.3

## Setup

```powershell
# Install dependencies
composer install
npm install

# Copy env dan generate key
cp .env.example .env
php artisan key:generate

# Edit .env: setel DB_DATABASE=movie_review, DB_USERNAME, DB_PASSWORD
# (XAMPP default: root, no password)

# Buat database
mysql -u root -e "CREATE DATABASE movie_review CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE DATABASE movie_review_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrate + seed (15 genre, 12 movie, 56 review, 25 blacklist keyword)
php artisan migrate:fresh --seed

# Storage symlink untuk poster/backdrop upload
php artisan storage:link

# Build assets
npm run build

# Jalankan dev server (terminal terpisah)
php artisan serve            # backend di :8000
npm run dev                  # vite dev di :5173 (HMR)
```

Buka `http://localhost:8000` dan login:
- Admin: `admin@example.com` / `password`
- Member: `test@example.com` / `password`

## Fitur Utama

### Public
- **`/`** — Home dengan hero rotator (auto-cycle 6 detik), section Popular This Week + Recently Reviewed feed.
- **`/movies`** — Grid film dengan filter search/genre/year/sort + ScoreBadge overlay.
- **`/movies/{slug}`** — Movie detail dengan **immersive hero parallax** (backdrop + poster fallback blur) + GlassPanel review section.
- **`/u/{id}`** — Public profile user dengan stats + grid ulasan published.

### Member
- **`/my-reviews`** — Daftar ulasan sendiri (semua status: published/pending/rejected) + soft-delete.
- **ReviewForm** dengan slider 0–100 + live ScoreBadge preview + preset 25/50/75/90 + char/word counter + live filter feedback (URL & keyword detection client-side).
- **Report** ulasan user lain (modal dengan alasan: spam/offensive/misleading/other).

### Admin (`/admin`)
- **Dashboard** — Total counts, distribusi warna histogram, top 5 films, rule trigger counts (30 hari).
- **Movies CRUD** dengan dual mode poster (upload/URL, wajib) + dual mode backdrop (opsional).
- **Genres** inline create/edit/delete.
- **Blacklist Keywords** dengan filter search/category/active + cache 5 menit (auto-invalidate).
- **Moderation Queue** — approve/reject pending reviews dengan audit log.
- **Reports Queue** — hide review (set rejected) atau dismiss laporan.

## Autonomous Review Filter

Pipeline `App\Services\ReviewFilter\Pipeline` menjalankan 8 rule berurutan (first-failure-wins):

| # | Rule | Severity | Mitigasi PRD weakness |
|---|---|---|---|
| 1 | LengthRule | reject | min 30 char + 5 kata bermakna (#4) |
| 2 | VowelRule | reject | wajib vokal + rasio < 10% (#3) |
| 3 | UrlDetectionRule | reject | http://, www., bare domain (#6) |
| 4 | BlacklistKeywordRule | reject | word-boundary + multi-word + skip negation (#1, #2, #5, #10) |
| 5 | OnePerMovieRule | reject | published+pending only, allow resubmit setelah rejected |
| 6 | CooldownRule | reject | 60 detik antar submit |
| 7 | ContentDuplicateRule | reject | canonical hash 30 hari, mitigasi copy-paste |
| 8 | HourlyQuotaRule | **flag** | ≥ 5 review/jam → pending (bukan reject) |

### TextNormalizer
Normalisasi sebelum keyword check:
- Lowercase
- Leetspeak: 0→o, 1→i, 3→e, 4→a, 5→s, 7→t, @→a, $→s
- Collapse pengulangan karakter ≥3 (`jjjjjelek` → `jelek`)
- Canonical hash strip semua non-alphanumerik untuk duplicate detection

### Audit Log
Setiap keputusan filter ditulis ke `review_audit_logs` (user_id, movie_id, review_id, rule_triggered, action, reason, payload_excerpt 200 char, ip).

### Demo via Artisan
```powershell
php artisan review:test-filter "Film yang sangat menjijikkan dan tidak layak ditonton sama sekali."
php artisan review:test-filter "Film ini tidak menjijikkan kok, justru bagus dan layak."
php artisan review:test-filter "kunjungi www.spam.com untuk download lebih bagus dari ini"
php artisan review:test-filter "j3l3k jjjelek banget mubazir tidak masuk akal sama sekali"
```

## Hardening (Task 14)

- **Rate limiter** `throttle:review-submit` (5/menit per user+IP) di route `POST /reviews`
- **Honeypot** field `website` di `ReviewForm.vue` — submission bot otomatis di-reject + logged warning
- **CSRF** default Laravel
- **XSS** Vue auto-escape via `{{ }}` interpolation
- **SQL injection** Eloquent prepared statements

## Tests

```powershell
# Drop & recreate test DB jika kena race condition (seringkali di MariaDB)
mysql -u root -e "DROP DATABASE IF EXISTS movie_review_test; CREATE DATABASE movie_review_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run all tests
vendor\bin\pest

# Run by test file
vendor\bin\pest --filter=ReviewFilterPipeline
vendor\bin\pest --filter=AdminCrud
vendor\bin\pest --filter=E2EHappyPath
```

Test suite: ≥ 130 tests, ≥ 450 assertions.

## Struktur Direktori Penting

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                  # Admin area (Movies, Genres, Keywords, Moderation, Reports, Dashboard)
│   │   ├── HomeController.php
│   │   ├── MoviePublicController.php
│   │   ├── MyReviewsController.php
│   │   ├── ProfileController.php
│   │   ├── ReviewController.php
│   │   └── ReviewReportController.php
│   ├── Middleware/EnsureAdmin.php
│   └── Requests/                   # FormRequest validation
├── Models/                         # User, Genre, Movie, Review, ReviewAuditLog, BlockedKeyword, ReviewReport
├── Providers/
│   ├── AppServiceProvider.php      # rate limiters
│   └── ReviewFilterServiceProvider.php  # pipeline DI
└── Services/ReviewFilter/
    ├── Contracts/ReviewRule.php
    ├── Pipeline.php
    ├── ReviewContext.php
    ├── RuleResult.php
    ├── TextNormalizer.php
    └── Rules/                      # 8 rule implementations

resources/
├── css/app.css                     # cinematic dark theme tokens
└── js/
    ├── app.ts                      # Inertia layout switcher
    ├── components/
    │   ├── admin/MovieForm.vue
    │   └── cinema/                 # ScoreBadge, MovieCard, GlassPanel, HeroBackdrop, ReviewForm
    ├── layouts/
    │   ├── PublicLayout.vue
    │   └── AdminLayout.vue
    └── pages/
        ├── home/Index.vue
        ├── movies/{Index,Show}.vue
        ├── profile/{Show,MyReviews}.vue
        ├── admin/
        │   ├── dashboard/Index.vue
        │   ├── movies/{Index,Create,Edit}.vue
        │   ├── genres/Index.vue
        │   ├── keywords/Index.vue
        │   ├── moderation/Index.vue
        │   └── reports/Index.vue
        └── dev/Design.vue          # /dev/design (non-prod only)

database/
├── factories/                      # Genre, Movie, Review, BlockedKeyword, ReviewReport, User
├── migrations/                     # 11 migrations (users, role, genres, movies, pivot, reviews, keywords, audit, reports)
└── seeders/                        # DatabaseSeeder, BlockedKeywordSeeder
```

## Troubleshooting

**MariaDB test DB corrupt** (RefreshDatabase race condition):
```powershell
mysql -u root -e "DROP DATABASE movie_review_test; CREATE DATABASE movie_review_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Vite manifest error saat run test**: rebuild assets `npm run build`.

**npm.cmd vs npm**: Pada PowerShell dengan execution policy restricted, gunakan `npm.cmd run dev` bukan `npm run dev`.

## Default Credentials

```
Admin Utama:   admin@example.com    / password
Admin Cadang:  admin2@example.com   / password
Test User:     test@example.com     / password
+ 5 random members (hashed dari faker, password = bcrypt('password') juga via UserFactory default)
```

## Lisensi

MIT — bebas dipakai untuk tugas kuliah maupun proyek pribadi.
