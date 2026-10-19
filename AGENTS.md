# Project instructions - malave.com

Family photo site: server-rendered PHP + SQLite with a self-hosted admin.
Live at <https://malave.com> (Public) and <https://malave.com/admin/> (Admin CMS).
Repo `HappyTimez/malave-website`, branch `master`.
Local workspace: `/home/happytimez/modern-redesign` (always work here, NOT `/home/happytimez/malave-website`).

## Architecture

- `index.php` - public entry point. Renders the entire page server-side from SQLite using the Wix "Family Portrait Photographer (Classic)" (`l-williams`) layout (`#fbf5ed` cream ground, `#261a14` dark brown ink, `Questrial` headings).
- `library/` - application code. Denied by `.htaccess` (HTTP 403) and guarded by the `MALAVE_APP` constant, so every file must be reached through an entry point.
  - `bootstrap.php` - paths, config loading, session, CSRF, auth, throttling.
  - `db.php` - SQLite schema, migration, and first-run seed (scans `images/`).
  - `repo.php` - data access (settings, albums, photos).
  - `image.php` - upload validation and derivative generation (Imagick, GD fallback).
  - `render.php` - public HTML.
  - `admin_layout.php` - admin chrome.
- `admin/` - `setup.php` (first run only), `login.php`, `logout.php`, `index.php` (dashboard), `actions.php` (every POST), `backup.php`, `admin.css`. Uses Argon2id hashes, `HttpOnly`/`Secure`/`SameSite=Strict` cookies, CSRF tokens on all POSTs, and IP-hashed login throttling.
- `styles.css`, `script.js` - public styling and the progressive-enhancement lightbox.
- `images/` - seeded archival JPEGs (`gallery/`, `albums/August-2003/`, `albums/September-2003/`, `albums/fishing2003/`, `albums/NewYear2004/`, `ellio-and-daddy-in-the-cornfield.jpg`) plus runtime uploads in `images/uploads/` (`original/`, `thumbs/`, and web derivatives).

## Runtime state (not in the repo - never delete)

- Database: `/home/malamkte/malave-data/site.sqlite`, mode `0600` (outside the web root). Auto-migrations and initial seed run if empty.
- Login throttle state: `/home/malamkte/malave-data/throttle.json` (outside the web root).
- Admin password hash + secret: `/home/malamkte/malave-config/config.php`, mode `0600` (outside the web root). If this file is missing, `/admin/setup.php` becomes active again and lets the first visitor set the password.
- Uploads: `/home/malamkte/public_html/images/uploads/` - server-owned and web-accessible, with script execution disabled via `images/uploads/.htaccess`.

## Rules

- Static content must render with JavaScript disabled. Never gate content behind JS or hide elements with `opacity: 0`.
- Preserve the existing markup and class names. The design is template-matched and has been diff-verified against the previous static page.
- Escape all output with `h()`; use prepared statements for every query.
- Keep secrets out of the repo and out of the web root.

## Deploy

Push to `master` runs `.github/workflows/deploy.yml` (or trigger manually via `gh workflow run deploy.yml --ref master`), which SSH-rsyncs (port `21098`) with `--delete` into `/home/malamkte/public_html/`.

`--delete` will erase anything on the server that is not in the repo, so the workflow **must** keep these exact order-sensitive rules (`--include` rules MUST come before `--exclude 'images/uploads/*'` so `.htaccess` deploys without wiping uploaded photos):

    rsync -avz --delete \
      --exclude '.git' \
      --exclude '.github' \
      --exclude '.opencode' \
      --exclude 'AGENTS.md' \
      --exclude 'README.md' \
      --include 'images/uploads/.htaccess' \
      --include 'images/uploads/.gitkeep' \
      --exclude 'images/uploads/*' \
      -e "ssh -p 21098" \
      ./ malamkte@209.74.67.64:/home/malamkte/public_html/

If `AGENTS.md`, `README.md`, or `.git` are ever committed without those excludes, they become publicly readable at `https://malave.com/AGENTS.md`.

Workflow files cannot be updated by `git push` (the OAuth credential lacks `workflow` scope for pushes); update them through the GitHub API and immediately rebase:

    SHA=$(gh api /repos/HappyTimez/malave-website/contents/.github/workflows/deploy.yml --jq '.sha')
    gh api --method PUT /repos/HappyTimez/malave-website/contents/.github/workflows/deploy.yml \
      -f message="Update workflow" -f content="$(base64 -w0 .github/workflows/deploy.yml)" \
      -f sha="$SHA" -f branch=master
    git fetch origin && git rebase origin/master

## Verification (no PHP on the dev machine)

Lint and test over SSH instead of locally:

1. `rsync -az --exclude .git ./ malamkte@209.74.67.64:~/malave-test/ -e "ssh -p 21098"`
2. `php -l` every PHP file over SSH.
3. Run `php -S 127.0.0.1:8199 -t ~/malave-test` with `MALAVE_DATA_DIR` and `MALAVE_CONFIG_FILE` pointed at throwaway directories, then curl the flows (setup, login, upload, settings, CSRF rejection, anonymous access).
4. Stop the test server with `fuser -k 8199/tcp` (never run `pkill -f "pattern"` in a one-line subshell containing the same pattern, as it kills the subshell itself) and delete the `~/malave-test*` directories when done.

Headless Chrome is available at `/usr/bin/google-chrome` for screenshots, but if the current model cannot view images, prefer text-level checks.

Habits that catch real regressions:

- Sweep every asset the page references and confirm HTTP 200.
- Upload a photo through the admin, run a deploy, then confirm it survived.
- Diff rendered markup against the previous static page to prove no visual change.

One-command post-deploy verification check:

    bash -c 'fail=0; for pair in "https://malave.com/:200" "http://malave.com/:301" "https://malave.com/admin/:302" "https://malave.com/library/:403" "https://malave.com/images/uploads/.htaccess:403" "https://malave.com/AGENTS.md:404" "https://malave.com/README.md:404" "https://malave.com/.opencode:404"; do url="${pair\%:*}"; exp="${pair##*:}"; code=$(curl -s -o /dev/null -w "\%{http_code}" "$url"); if [ "$code" = "$exp" ]; then echo "PASS [$code]$url"; else echo "FAIL [$code !=$exp] $url"; fail=1; fi; done; count=$(curl -s "https://malave.com/" | grep -c "data-lb"); [ "$count" -ge 54 ] && echo "PASS [$count photos] Gallery" || { echo "FAIL [$count photos] Gallery"; fail=1; }; exit $fail'

## Host facts

- SSH: `malamkte@209.74.67.64` port `21098`, key `~/.ssh/id_ed25519`, Actions secret `FTP_SSH_KEY`.
- Server environment: LiteSpeed on cPanel; PHP 8.1.34 runs as `malamkte`; `imagick`, `gd`, `pdo_sqlite`, `sqlite3`, `fileinfo`, and `exif` are available.
- Web root: `/home/malamkte/public_html/`.
