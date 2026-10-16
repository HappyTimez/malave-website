# Project instructions - malave.com

Family photo site: server-rendered PHP + SQLite with a self-hosted admin.
Live at <https://malave.com>. Repo `HappyTimez/malave-website`, branch `master`.

## Architecture

- `index.php` - public entry point. Renders the entire page server-side from SQLite.
- `library/` - application code. Denied by `.htaccess` and guarded by the
  `MALAVE_APP` constant, so every file must be reached through an entry point.
  - `bootstrap.php` - paths, config loading, session, CSRF, auth, throttling.
  - `db.php` - SQLite schema, migration, and first-run seed (scans `images/`).
  - `repo.php` - data access (settings, albums, photos).
  - `image.php` - upload validation and derivative generation (Imagick, GD fallback).
  - `render.php` - public HTML.
  - `admin_layout.php` - admin chrome.
- `admin/` - `setup.php` (first run only), `login.php`, `logout.php`,
  `index.php` (dashboard), `actions.php` (every POST), `backup.php`.
- `styles.css`, `script.js` - public styling and the lightbox. JS is progressive
  enhancement only.

## Runtime state (not in the repo - never delete)

- Database: `~/malave-data/site.sqlite` (outside the web root).
- Admin password hash + secret: `~/malave-config/config.php`, mode `0600`
  (outside the web root). If this file is missing, `/admin/setup.php` becomes
  active again and lets the first visitor set the password.
- Uploads: `public_html/images/uploads/` - server-owned and web-accessible.

## Rules

- Static content must render with JavaScript disabled. Never gate content behind JS.
- Preserve the existing markup and class names. The design is template-matched and
  has been diff-verified against the previous static page.
- Escape all output with `h()`; use prepared statements for every query.
- Keep secrets out of the repo and out of the web root.

## Deploy

Push to `master` runs `.github/workflows/deploy.yml`, which SSH-rsyncs (port
`21098`) with `--delete` into `/home/malamkte/public_html/`.

`--delete` will erase anything on the server that is not in the repo, so the
workflow **must** exclude server-owned paths:

```
--exclude 'images/uploads/*'   # uploaded photos
--exclude 'AGENTS.md'          # project notes: never serve these
--exclude '.opencode'
```

If `AGENTS.md` is ever committed without that exclude, it becomes publicly
readable at `https://malave.com/AGENTS.md`.

Workflow files cannot be updated by `git push` (the credential lacks `workflow`
scope for pushes); update them through the API instead:

```sh
SHA=$(gh api /repos/HappyTimez/malave-website/contents/.github/workflows/deploy.yml --jq '.sha')
gh api --method PUT /repos/HappyTimez/malave-website/contents/.github/workflows/deploy.yml \
  -f message="..." -f content="$(base64 -w0 .github/workflows/deploy.yml)" \
  -f sha="$SHA" -f branch=master
```

## Verification (no PHP on the dev machine)

Lint and test over SSH instead of locally:

1. `rsync -az --exclude .git ./ malamkte@209.74.67.64:~/malave-test/`
2. `php -l` every PHP file.
3. Run `php -S 127.0.0.1:PORT -t ~/malave-test` with `MALAVE_DATA_DIR` and
   `MALAVE_CONFIG_FILE` pointed at throwaway directories, then curl the flows
   (setup, login, upload, settings, CSRF rejection, anonymous access).
4. Delete the `~/malave-test*` directories when done.

Headless Chrome is available at `/usr/bin/google-chrome` for screenshots, but the
current model cannot view images - prefer text-level checks.

Habits that catch real regressions:

- Sweep every asset the page references and confirm HTTP 200.
- Upload a photo through the admin, run a deploy, then confirm it survived.
- Diff rendered markup against the previous static page to prove no visual change.

## Host facts

- SSH: `malamkte@209.74.67.64` port `21098`, key `~/.ssh/id_ed25519`,
  Actions secret `FTP_SSH_KEY`.
- PHP 8.1 runs as `malamkte`; `imagick`, `gd` and `pdo_sqlite` are available.
- Web root: `/home/malamkte/public_html`.
