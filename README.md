# malave.com - Malave Family

A photo-first family website. The photographs are the point: they render as
plain HTML with no animation and no JavaScript required to see them.

## Live site

**https://malave.com**

## Design

The layout and look are modelled on the Wix
[Family Portrait Photographer (Classic)](https://www.wix.com/website-template/view/html/5590)
template (live preview: `wix.com/sgtemplates/l-williams`) and rebuilt from
scratch as static HTML/CSS with the Malave photographs:

- Warm cream ground (`#fbf5ed`) and near-black brown ink (`#261a14`).
- Geometric **Questrial** headings with tight letter-spacing; Helvetica Neue body.
- A five-photo **collage hero** with an overlaid headline, "Explore" button.
- An **About** split: a staggered photo collage beside "Our Story" / "Our Mission".
- **Our Albums** - four feature cards, one per album.
- A dark **feature band** for the Fishing, Summer 2003 album.
- A **Portfolio** masonry grid containing every photo.
- A centred pull-quote, a **Get In Touch** section with a contact form, and a slim footer.

## How it works

- **Static content.** Every photo and all text are written into `index.html` as
  plain HTML, so the page is fully visible even with JavaScript disabled.
- **Progressive enhancement.** Each photo is a normal link to its full image. When
  JavaScript is available, `script.js` intercepts the click and opens an overlay
  lightbox (arrow keys and `Esc` supported). Nothing is hidden without JS.
- **Masonry layout.** CSS multi-column keeps the mixed photo aspect ratios tidy
  without any scripting.
- **Contact form.** Posts via a `mailto:` action, so it needs no backend; the
  email address is also shown as a plain link.

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
├── index.html      Static page (hero, about, albums, feature, portfolio, contact)
├── styles.css      Cream/brown editorial styling (no JS-gated visibility)
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
