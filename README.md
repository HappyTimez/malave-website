# malave.com - Malave Family Photos

A photo-first family website. The gallery is the point: photographs load as a
plain masonry grid with no animation or JavaScript required to see them.

## Live site

**https://malave.com**

## Overview

The original site was an HTML 4.01, table-based page from the early 2000s that
only ever displayed 80x60 pixel thumbnails. This rebuild recovers the
higher-resolution originals from the Internet Archive and presents them as a
clean, photo-first gallery.

## How it works

- **Static gallery.** Every photo is written into `index.html` as plain HTML, so
  the page is fully visible even with JavaScript disabled.
- **Progressive enhancement.** Each photo is a normal link to its full image. When
  JavaScript is available, `script.js` intercepts the click and opens an overlay
  lightbox (arrow keys and `Esc` supported). No content depends on JS.
- **Masonry layout.** CSS multi-column keeps the mixed photo aspect ratios tidy
  without any scripting.

## Photo archive

Photos were recovered from the Wayback Machine at their best available sizes:

| Album | Photos | Source size |
| --- | --- | --- |
| Featured - cornfield | 1 | 400x300 |
| Family Favorites | 11 | 400x300 |
| Fishing Trip, Summer 2003 | 20 | 640x480 |
| New Year 2004 | 16 | 448x336 (one 336x448) |
| August & September 2003 | 6 | 640x480 |

The original homepage thumbnails (80x60 GIFs) are kept in
`images/thumbnails/` for reference but are not displayed, since the recovered
JPEGs are much larger.

## Project structure

```
modern-redesign/
├── index.html      Static page + full gallery markup
├── styles.css      Photo-first styling (no opacity/JS-gated visibility)
├── script.js       Optional lightbox enhancement
├── images/
│   ├── ellio-and-daddy-in-the-cornfield.jpg
│   ├── gallery/              Family Favorites (400x300)
│   ├── albums/fishing2003/   640x480
│   ├── albums/NewYear2004/   448x336
│   ├── albums/August-2003/   640x480
│   ├── albums/September-2003/ 640x480
│   └── thumbnails/           original 80x60 GIFs (reference only)
├── .github/workflows/deploy.yml
└── README.md
```

## Local preview

```bash
cd modern-redesign && python3 -m http.server 8000
```

Then open http://localhost:8000.

## Deployment

Pushing to `master` runs `.github/workflows/deploy.yml`, which syncs the site
over SSH rsync (port `21098`) to `/home/malamkte/public_html/` using the
`FTP_SSH_KEY` secret. Dotfiles and this README are excluded from the sync.

## Contact

- Email: ellis@malave.com
- Website: https://malave.com

---

Personal family website - all photos belong to the Malave family.
