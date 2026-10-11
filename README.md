# malave.com - Modern Family Gallery

A modern, one-page family gallery website replacing the old table-based HTML design.

## 🌐 Live Site
**https://malave.com**

## 📝 Overview
This project is a complete redesign of the malave.com home website, transforming it from an outdated table-based HTML 4.01 site (with deprecated JavaScript menus from the early 2000s) into a fully responsive, mobile-first web application.

## ✨ Features

| Feature | Description |
|---------|-------------|
| **One-page layout** | All content on a single scrollable page |
| **Responsive gallery** | CSS Grid adapts from 6 columns (desktop) to 1 column (mobile) |
| **Image lightbox** | Click any thumbnail for fullscreen overlay view |
| **Smooth navigation** | Anchor links scroll between sections |
| **Modern styling** | Clean CSS with CSS variables, no deprecated HTML |
| **UTF-8 charset** | Proper character encoding |
| **Lazy-loaded images** | `loading="lazy"` for better performance |
| **Accessible** | Semantic HTML5, alt text, keyboard-friendly |

## 📁 Project Structure

```
/modern-redesign/
├── index.html        ← Main HTML page (hero + gallery + about + contact)
├── styles.css        ← Responsive CSS (Flexbox, Grid, lightbox styles)
└── script.js         ← Lightbox + smooth scroll functionality
```

## 🖼️ Gallery

The gallery features **16 thumbnails** from the original site plus **1 featured large image** (Ellio and dad in the cornfield). All images are sourced from the original `images/` directory.

**Thumbnail categories include:**
- DSC00627 through DSC00797 (original family photos)
- Featured large image: `ellio_and_daddy_in_the_cornfield.jpg`

## 🎵 Music Section
Inline music reference to "Andromeda" track (links to original `andromeda.php`). No separate music page - integrated into the one-page design.

## 📱 Responsive Breakpoints

| Screen Width | Gallery Columns |
|-------------|-----------------|
| 320px - 479px | 1 column |
| 480px - 767px | 2 columns |
| 768px - 1023px | 3 columns |
| 1024px - 1399px | 4 columns |
| 1400px+ | 6 columns |

## 🚀 Local Development

1. **Start OpenCode web interface:**
   ```bash
   cd /home/happytimez/.opencode
   opencode serve --port 8080
   ```
   Then open http://127.0.0.1:8080 in your browser.

2. **Or open index.html directly:**
   ```bash
   open /home/happytimez/modern-redesign/index.html
   ```

3. **Test responsiveness** by resizing the browser window.

## 🛠️ Built With

- **HTML5** - Semantic markup
- **CSS3** - CSS Grid, Flexbox, CSS Variables
- **Vanilla JavaScript** - Lightbox interaction, smooth scroll
- **Responsive Design** - Mobile-first approach
- **No frameworks** - Lightweight, fast loading

## 📦 Deployment

### Option 1: GitHub Actions (Auto-Deploy)
See the `.github/workflows/deploy.yml` file in this repository for automatic deployment.

### Option 2: Manual SFTP
```bash
sftp ellis@malave.com
cd public_html
put -r modern-redesign/* .
exit
```

### Option 3: GitHub Pages
- Set up GitHub Pages to serve from the `main` branch or `/(root)` folder
- Note: This would show the source files, not the deployed site

## 👥 Contact
- **Email:** ellis@malave.com
- **Website:** https://malave.com

## 📄 License
Personal family website - all content original unless otherwise noted.

---

**Last updated:** 2026