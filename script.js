/* =========================================================
   Malave Family Photos - lightbox (progressive enhancement)

   Every photo is a normal link, so it works with JavaScript
   disabled. When JS is available, this intercepts the click
   and shows the photo in an overlay instead.
   ========================================================= */

(function () {
  "use strict";

  var links = Array.prototype.slice.call(document.querySelectorAll("a[data-lb]"));
  var lb = document.getElementById("lightbox");
  if (!links.length || !lb) return;

  var lbImg = document.getElementById("lb-img");
  var lbCap = document.getElementById("lb-cap");
  var btnClose = lb.querySelector(".lb-close");
  var btnPrev = lb.querySelector(".lb-prev");
  var btnNext = lb.querySelector(".lb-next");
  var current = 0;
  var lastFocused = null;

  function render(index) {
    current = (index + links.length) % links.length;
    var link = links[current];
    var img = link.querySelector("img");
    lbImg.src = link.getAttribute("href");
    lbImg.alt = (img && img.alt) || "";
    lbCap.textContent = link.getAttribute("data-album") || "";
  }

  function open(index) {
    lastFocused = document.activeElement;
    render(index);
    lb.hidden = false;
    document.body.style.overflow = "hidden";
    btnClose.focus();
  }

  function close() {
    lb.hidden = true;
    document.body.style.overflow = "";
    if (lastFocused && lastFocused.focus) lastFocused.focus();
  }

  links.forEach(function (link, index) {
    link.addEventListener("click", function (e) {
      // Let modified clicks (new tab, download, etc.) behave normally.
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
      e.preventDefault();
      open(index);
    });
  });

  btnClose.addEventListener("click", close);
  btnPrev.addEventListener("click", function () { render(current - 1); });
  btnNext.addEventListener("click", function () { render(current + 1); });

  lb.addEventListener("click", function (e) {
    if (e.target === lb) close();
  });

  document.addEventListener("keydown", function (e) {
    if (lb.hidden) return;
    if (e.key === "Escape") close();
    else if (e.key === "ArrowLeft") render(current - 1);
    else if (e.key === "ArrowRight") render(current + 1);
  });

  var year = document.getElementById("year");
  if (year) year.textContent = String(new Date().getFullYear());
})();
