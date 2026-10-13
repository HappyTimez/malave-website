/* =========================================================
   The Malave Family — interactions
   Vanilla JS, no dependencies.
   ========================================================= */

(function () {
  "use strict";

  /* ---------- Photo album data (single source of truth) ---------- */
  // Captions are a work in progress; they currently use the original
  // archive filenames so nothing is mislabelled.
  const PHOTOS = [
    { src: "images/ellio-and-daddy-in-the-cornfield.jpg", caption: "Ellio & dad, the cornfield", wide: true },
    { src: "images/thumbnails/DSC00007.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00077.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00483.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00485.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00513.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00519.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00524.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00591.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00593.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00619.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00627.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00691.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00692.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00789.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00797.gif", caption: "Archive photo" },
    { src: "images/thumbnails/DSC00807.gif", caption: "Archive photo" }
  ];

  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- Build the album ---------- */
  function buildAlbum() {
    const album = $("#album");
    if (!album) return;

    const frag = document.createDocumentFragment();
    PHOTOS.forEach((photo, index) => {
      const card = document.createElement("figure");
      card.className = "print-card" + (photo.wide ? " is-wide" : "");
      card.setAttribute("role", "button");
      card.setAttribute("tabindex", "0");
      card.dataset.index = String(index);
      card.setAttribute("aria-label", `View photo: ${photo.caption}`);

      const img = document.createElement("img");
      img.src = photo.src;
      img.alt = photo.caption;
      img.loading = "lazy";
      img.decoding = "async";

      const cap = document.createElement("figcaption");
      cap.textContent = photo.caption;

      card.append(img, cap);
      frag.append(card);
    });
    album.append(frag);
  }

  /* ---------- Lightbox ---------- */
  function initLightbox() {
    const lb = $("#lightbox");
    const lbImg = $("#lb-img");
    const lbCap = $("#lb-caption");
    if (!lb || !lbImg) return;

    let current = 0;
    let lastFocused = null;

    function render(index) {
      current = (index + PHOTOS.length) % PHOTOS.length;
      const photo = PHOTOS[current];
      lbImg.src = photo.src;
      lbImg.alt = photo.caption;
      lbCap.textContent = photo.caption;
    }

    function open(index) {
      lastFocused = document.activeElement;
      render(index);
      lb.hidden = false;
      // allow the transition to run
      requestAnimationFrame(() => lb.classList.add("is-open"));
      document.body.style.overflow = "hidden";
      $(".lb-close").focus();
    }

    function close() {
      lb.classList.remove("is-open");
      document.body.style.overflow = "";
      const done = () => { lb.hidden = true; lb.removeEventListener("transitionend", done); };
      lb.addEventListener("transitionend", done);
      if (lastFocused) lastFocused.focus();
    }

    // Wire up album interactions
    const album = $("#album");
    album.addEventListener("click", (e) => {
      const card = e.target.closest(".print-card");
      if (card) open(Number(card.dataset.index));
    });
    album.addEventListener("keydown", (e) => {
      const card = e.target.closest(".print-card");
      if (card && (e.key === "Enter" || e.key === " ")) {
        e.preventDefault();
        open(Number(card.dataset.index));
      }
    });

    $(".lb-close").addEventListener("click", close);
    $(".lb-prev").addEventListener("click", () => render(current - 1));
    $(".lb-next").addEventListener("click", () => render(current + 1));

    // Click the backdrop to close
    lb.addEventListener("click", (e) => {
      if (e.target === lb) close();
    });

    // Keyboard controls
    document.addEventListener("keydown", (e) => {
      if (lb.hidden) return;
      if (e.key === "Escape") close();
      else if (e.key === "ArrowLeft") render(current - 1);
      else if (e.key === "ArrowRight") render(current + 1);
    });
  }

  /* ---------- Mobile navigation ---------- */
  function initNav() {
    const toggle = $(".nav-toggle");
    const nav = $("#site-nav");
    if (!toggle || !nav) return;

    toggle.addEventListener("click", () => {
      const open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", String(open));
      toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    });

    nav.addEventListener("click", (e) => {
      if (e.target.tagName === "A") {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* ---------- Sticky header state ---------- */
  function initHeader() {
    const header = $(".site-header");
    if (!header) return;
    const onScroll = () => header.classList.toggle("is-stuck", window.scrollY > 24);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  /* ---------- Reveal on scroll ---------- */
  function initReveal() {
    const items = $$(".reveal");
    if (!items.length) return;

    if (prefersReduced || !("IntersectionObserver" in window)) {
      items.forEach((el) => el.classList.add("is-visible"));
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: "0px 0px -10% 0px", threshold: 0.1 });

    items.forEach((el) => io.observe(el));
  }

  /* ---------- Footer year ---------- */
  function initYear() {
    const el = $("#year");
    if (el) el.textContent = String(new Date().getFullYear());
  }

  /* ---------- Boot ---------- */
  document.addEventListener("DOMContentLoaded", () => {
    buildAlbum();
    initLightbox();
    initNav();
    initHeader();
    initReveal();
    initYear();
  });
})();