// modern-redesign/script.js - One-page gallery with lightbox

// Lightbox functionality
const lightbox = document.getElementById('lightbox');
const lightboxImg = document.getElementById('lightbox-img');
const lightboxCaption = document.getElementById('lightbox-caption');
const lightboxClose = document.querySelector('.lightbox-close');

// All gallery images
const galleryImages = document.querySelectorAll('.gallery-card img:not(.featured img)');

// Featured image separately
const featuredImg = document.querySelector('.gallery-card.featured img');

// Open lightbox for regular gallery images
galleryImages.forEach(img => {
  img.addEventListener('click', () => {
    lightbox.style.display = 'flex';
    lightboxImg.src = img.src;
    lightboxCaption.textContent = img.alt || 'Gallery image';
  });
});

// Open lightbox for featured image
if (featuredImg) {
  featuredImg.addEventListener('click', () => {
    lightbox.style.display = 'flex';
    lightboxImg.src = featuredImg.src;
    lightboxCaption.textContent = featuredImg.alt || 'Featured image';
  });
}

// Close lightbox
lightboxClose.addEventListener('click', () => {
  lightbox.style.display = 'none';
});

// Close on background click
lightbox.addEventListener('click', (e) => {
  if (e.target === lightbox) {
    lightbox.style.display = 'none';
  }
});

// Smooth scroll for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const targetId = this.getAttribute('href');
    if (targetId === '#') return;
    
    const targetElement = document.querySelector(targetId);
    if (targetElement) {
      targetElement.scrollIntoView({ behavior: 'smooth' });
    }
  });
});

// Optional: hide header on scroll down, show on scroll up
let lastScroll = 0;
const header = document.querySelector('header.hero');

window.addEventListener('scroll', () => {
  const currentScroll = window.pageYOffset;
  
  if (currentScroll > lastScroll && currentScroll > 100) {
    // Scrolling down - hide header
    header.style.transform = 'translateY(-100%)';
  } else {
    // Scrolling up - show header
    header.style.transform = 'translateY(0)';
  }
  
  lastScroll = currentScroll <= 0 ? 0 : currentScroll;
});