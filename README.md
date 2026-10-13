# malave.com - The Malave Family

A modern, one-page family website replacing the old table-based HTML design.

## Live site

**https://malave.com**

## Overview

This project redesigns the malave.com family site. The original was an HTML 4.01
Transitional page built with fixed-width tables and early-2000s JavaScript menus.
It is now a single, responsive page with a warm, editorial "family archive"
aesthetic: serif display type, framed photo prints, and a keyboard-accessible
lightbox.

## Features

| Feature | Description |
| --- | --- |
| **Single continuous page** | Hero, Story, Photo Album, Journal, Contact - all on one scroll |
| **Family archive look** | Paper tones, serif display type, framed "print" photographs |
| **Responsive album** | CSS Grid contact sheet that reflows to two columns on phones |
| **Lightbox** | Click or press Enter on any print; arrow keys and `Esc` supported |
| **Scroll reveal** | Sections fade in via `IntersectionObserver` |
| **Accessible** | Semantic HTML5, skip link, focus states, `prefers-reduced-motion` |
| **No dependencies** | Hand-written HTML, CSS, and vanilla JavaScript |

## Project structure

```
modern-redesign/
├── index.html                 Main page: hero, story, album, journal, contact
├── styles.css                 Design tokens, layout, gallery, lightbox
├── script.js                  Album generation, lightbox, nav, reveal
├── images/
│   ├── ellio-and-daddy-in-the-cornfield.jpg   Featured photo (400x300)
│   ├── fallback.svg                            Placeholder graphic
│   └── thumbnails/DSC*.gif                     16 archival thumbnails (80x60)
├── .github/workflows/deploy.yml   Auto-deploy on push to master
└── README.md
```

## Photo archive

The album contains **16 thumbnails** plus **1 featured photo**. The originals were
only ever stored as small thumbnails (80x60 px), so the design presents them as a
contact sheet of physical prints rather than enlarging them. The wide featured
photo is capped at 34rem to limit upscaling.

## Local development

Open `index.html` directly in a browser, or serve the folder:

```bash
cd modern-redesign && python3 -m http.server 8000
```

Then visit http://localhost:8000.

## Deployment

Pushing to `master` triggers `.github/workflows/deploy.yml`, which deploys over
SSH rsync (port `21098`) to `/home/malamkte/public_html/` using the
`FTP_SSH_KEY` repository secret.

> **Note:** the workflow uses `rsync --delete`. Everything served by the site must
> live in this repository, and dotfiles are excluded from the sync.

To deploy manually:

```bash
rsync -avz --delete \
  --exclude '.git' --exclude '.github' --exclude 'README.md' \
  -e "ssh -p 21098" \
  ./ malamkte@209.74.67.64:/home/malamkte/public_html/
```

## Contact

- Email: ellis@malave.com
- Website: https://malave.com

---

Personal family website - all photos and text belong to the Malave family.
