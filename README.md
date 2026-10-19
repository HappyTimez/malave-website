# malave.com - Malave Family

A photo-first family website with a small self-hosted admin for adding photos and
editing the page, running on ordinary PHP hosting (no framework, no database
server).

## Live site

**https://malave.com** - public page
**https://malave.com/admin/** - password-protected admin

## How it works

- **Server-rendered page.** `index.php` builds the whole page from the database on
  every request, so visitors get plain HTML. It still works with JavaScript
  disabled; the lightbox is pure progressive enhancement.
- **SQLite database.** All text, albums and photo records live in a single SQLite
  file kept **outside the web root** (`../malave-data/site.sqlite`), so it can
  never be downloaded.
- **Admin area.** `/admin/` handles sign-in, photo uploads (with automatic resize
  and thumbnailing via Imagick), album and photo management, the page text, a
  password change and a JSON backup.
- **Design.** Layout and typography follow the Wix "Family Portrait Photographer
  (Classic)" template: warm cream ground (`#fbf5ed`), near-black brown ink
  (`#261a14`), geometric Questrial headings.

## Admin guide

Sign in at `/admin/` (or run the one-time `/admin/setup.php` if no password is
set yet). The dashboard has four sections:

- **Text & images** - edit every heading and paragraph, and choose the photos for
  the hero collage, the About collage and the featured band.
- **Albums** - add, rename, describe, reorder, hide/show or delete albums.
- **Photos** - pick an album, upload photos (multiple at once), edit titles and
  captions, reorder, and delete.
- **Account** - change the password and download a JSON backup.

Uploads are validated (real image type, size cap), the original is kept under
`images/uploads/original/`, and a web version plus a thumbnail are generated.

## Security

- Password stored as an Argon2id hash in `../malave-config/config.php`, outside
  the web root and readable only by the account (`0600`).
- Session cookie is `HttpOnly`, `Secure` and `SameSite=Strict`; sessions are
  regenerated on sign-in.
- CSRF token on every form; sign-in is throttled after repeated failures.
- `library/` is denied by `.htaccess`; uploaded files can never execute PHP.
- HTTPS is forced by `.htaccess`.

## Photo archive

Photos were recovered from the Wayback Machine at their best available sizes:

| Album | Photos | Source size |
| --- | --- | --- |
| Featured - cornfield | 1 | 400x300 |
| Family Favorites | 11 | 400x300 |
| Fishing Trip, Summer 2003 | 20 | 640x480 |
| New Year 2004 | 16 | 448x336 (one 336x448) |
| August & September 2003 | 6 | 640x480 |

## Project structure

```
modern-redesign/
├── index.php                  Public entry point (server-rendered)
├── .htaccess                  HTTPS, security headers, dotfile deny
├── styles.css, script.js      Public styling + lightbox enhancement
├── library/                   Application code (denied by .htaccess)
│   ├── bootstrap.php          Config, session, CSRF, auth, throttling
│   ├── db.php                 SQLite schema + first-run seed
│   ├── repo.php               Data access
│   ├── image.php              Upload validation + resizing
│   ├── render.php             Public HTML
│   └── admin_layout.php       Admin chrome
├── admin/                     Password-protected admin
│   ├── index.php  actions.php  login.php  logout.php
│   ├── setup.php  backup.php   admin.css
├── images/                    Photos (gallery/, albums/, uploads/)
└── .github/workflows/deploy.yml
```

## Local preview

Requires PHP 8.1+ with `gd`/`imagick` and `pdo_sqlite`:

```bash
cd modern-redesign
MALAVE_DATA_DIR=/tmp/malave-data MALAVE_CONFIG_FILE=/tmp/malave-config/config.php \
  php -S localhost:8000
```

Then open http://localhost:8000 (and `/admin/setup.php` to set a password).

## Deployment

Pushing to `master` runs `.github/workflows/deploy.yml`, which syncs the site over
SSH rsync (port `21098`) to `/home/malamkte/public_html/` using the `FTP_SSH_KEY`
secret.

The following paths are **excluded** from the sync so a deploy never deletes
live content or exposes repository metadata:

- `images/uploads/*` - photos added through the admin (while `images/uploads/.htaccess` and `.gitkeep` are re-included)
- `.git`, `.github`, `.opencode`, `AGENTS.md`, `README.md`

The database and config live outside `public_html`, so they are untouched by
deploys. Back them up:

```bash
scp -P 21098 malamkte@209.74.67.64:'~/malave-data/site.sqlite ~/malave-config/config.php' ./backup/
```

## Contact

- Email: ellis@malave.com
- Website: https://malave.com

---

Personal family website - all photos belong to the Malave family.
