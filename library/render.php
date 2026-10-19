<?php
declare(strict_types=1);

/*
 * Malave Family - public page renderer.
 * Produces the same markup the site shipped as static HTML, but from the database.
 */

if (!defined('MALAVE_APP')) {
    http_response_code(403);
    exit('Forbidden');
}

function malave_img_tag(array $photo, string $loading = 'lazy', string $alt = '', bool $thumb = false): string
{
    $path = ($thumb && !empty($photo['thumb'])) ? (string) $photo['thumb'] : (string) $photo['file'];
    $src = h(malave_url($path));
    $width = (int) ($photo['width'] ?? 0);
    $height = (int) ($photo['height'] ?? 0);
    $dims = ($width > 0 && $height > 0) ? " width=\"{$width}\" height=\"{$height}\"" : '';
    return "<img src=\"{$src}\"{$dims} alt=\"" . h($alt) . "\" loading=\"{$loading}\" decoding=\"async\">";
}

function malave_render_page(): string
{
    $s = malave_settings();
    $albums = malave_albums(true);
    $portfolio = malave_photos(null, true);

    $photosById = [];
    $firstByAlbum = [];
    foreach ($portfolio as $p) {
        $pid = (int) $p['id'];
        $aid = (int) $p['album_id'];
        $photosById[$pid] = $p;
        if (!isset($firstByAlbum[$aid])) {
            $firstByAlbum[$aid] = $p;
        }
    }

    $heroPhotos = array_slice(malave_photos_by_ids(malave_json_ids($s['hero_photos'] ?? '')), 0, 5);
    if (!$heroPhotos) {
        $heroPhotos = array_slice($portfolio, 0, 5);
    }
    $aboutPhotos = array_slice(malave_photos_by_ids(malave_json_ids($s['about_photos'] ?? '')), 0, 4);
    if (!$aboutPhotos) {
        $aboutPhotos = array_slice($portfolio, 0, 4);
    }
    $featureId = (int) ($s['feature_photo'] ?? 0);
    $featurePhoto = $featureId > 0 ? ($photosById[$featureId] ?? malave_photo($featureId)) : null;
    if (!$featurePhoto) {
        $featurePhoto = $portfolio[0] ?? null;
    }

    // Editable copy.
    $title = h((string) ($s['site_title'] ?? 'Malave Family'));
    $brand = h((string) ($s['brand'] ?? 'Malave Family'));
    $heroHeadline = h((string) ($s['hero_headline'] ?? ''));
    $heroTagline = h((string) ($s['hero_tagline'] ?? ''));
    $heroButton = h((string) ($s['hero_button'] ?? 'Explore'));
    $aboutTitle = h((string) ($s['about_title'] ?? ''));
    $storyTitle = h((string) ($s['about_story_title'] ?? ''));
    $story = nl2br(h((string) ($s['about_story'] ?? '')));
    $missionTitle = h((string) ($s['about_mission_title'] ?? ''));
    $mission = nl2br(h((string) ($s['about_mission'] ?? '')));
    $albumsTitle = h((string) ($s['albums_title'] ?? 'Our Albums'));
    $featureEyebrow = h((string) ($s['feature_eyebrow'] ?? ''));
    $featureTitle = h((string) ($s['feature_title'] ?? ''));
    $featureBody = h((string) ($s['feature_body'] ?? ''));
    $featureButton = h((string) ($s['feature_button'] ?? ''));
    $portfolioTitle = h((string) ($s['portfolio_title'] ?? 'Portfolio'));
    $quoteText = h((string) ($s['quote_text'] ?? ''));
    $quoteAuthor = h((string) ($s['quote_author'] ?? ''));
    $contactTitle = h((string) ($s['contact_title'] ?? ''));
    $contactEmail = (string) ($s['contact_email'] ?? '');
    $contactEmailHtml = h($contactEmail);
    $contactNote = h((string) ($s['contact_note'] ?? ''));
    $year = gmdate('Y');

    // Hero collage.
    $heroImgs = '';
    foreach ($heroPhotos as $photo) {
        $heroImgs .= '        ' . malave_img_tag($photo, 'eager') . "\n";
    }

    // About collage.
    $aboutImgs = '';
    foreach ($aboutPhotos as $photo) {
        $aboutImgs .= '        ' . malave_img_tag($photo) . "\n";
    }

    // Album cards.
    $cards = '';
    foreach ($albums as $album) {
        $aid = (int) $album['id'];
        $coverId = (int) ($album['cover_photo_id'] ?? 0);
        $cover = $coverId > 0 ? ($photosById[$coverId] ?? malave_photo($coverId)) : null;
        if (!$cover) {
            $cover = $firstByAlbum[$aid] ?? null;
        }
        $media = $cover ? malave_img_tag($cover, 'lazy', (string) $album['title'], true) : '';
        $cards .= '        <a class="card" href="#portfolio">' . "\n"
            . '          <span class="card-media">' . $media . "</span>\n"
            . '          <h3>' . h((string) $album['title']) . "</h3>\n"
            . '          <p>' . h((string) $album['description']) . "</p>\n"
            . '          <span class="card-link">View album &rarr;</span>' . "\n"
            . "        </a>\n";
    }

    // Portfolio tiles.
    $tiles = '';
    foreach ($portfolio as $photo) {
        $tiles .= '          <a href="' . h(malave_url((string) $photo['file'])) . '" data-lb data-album="'
            . h((string) ($photo['title'] ?: 'Malave family photo')) . '">'
            . malave_img_tag($photo, 'lazy', (string) ($photo['caption'] ?: 'Malave family photo'), true) . "</a>\n";
    }

    // Feature band (only when a photo is available).
    $featureHtml = '';
    if ($featurePhoto) {
        $featureRel = h(malave_url((string) $featurePhoto['file']));
        $featureAlt = h((string) ($featurePhoto['title'] ?: ($s['feature_title'] ?? 'Featured photo')));
        $featureImage = malave_img_tag($featurePhoto, 'lazy', '');
        $featureHtml = <<<FEATURE
    <section class="feature">
      <div class="feature-media">
        <a href="{$featureRel}" data-lb data-album="{$featureAlt}">{$featureImage}</a>
      </div>
      <div class="feature-text">
        <p class="eyebrow">{$featureEyebrow}</p>
        <h2>{$featureTitle}</h2>
        <p>{$featureBody}</p>
        <a class="btn btn-dark" href="#portfolio">{$featureButton}</a>
      </div>
    </section>
FEATURE;
    } else {
        $featureHtml = '';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="The Malave family photographs - a lasting record of the love, laughter, and connection we share.">
  <title>{$title}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Questrial&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body id="top">

  <header class="site-header">
    <div class="wrap bar">
      <a class="brand" href="#top">{$brand}</a>
      <nav class="nav" aria-label="Primary">
        <a href="#about">About</a>
        <a href="#portfolio">Portfolio</a>
        <a href="#contact">Contact</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-collage" aria-hidden="true">
{$heroImgs}      </div>
      <div class="hero-panel">
        <h1>{$heroHeadline}</h1>
        <p>{$heroTagline}</p>
        <a class="btn" href="#portfolio">{$heroButton}</a>
      </div>
    </section>

    <section class="about wrap" id="about">
      <div class="about-media">
{$aboutImgs}      </div>
      <div class="about-text">
        <h2>{$aboutTitle}</h2>
        <h3>{$storyTitle}</h3>
        <p>{$story}</p>
        <h3>{$missionTitle}</h3>
        <p>{$mission}</p>
      </div>
    </section>

    <section class="albums wrap" id="albums">
      <h2 class="section-title">{$albumsTitle}</h2>
      <div class="cards">
{$cards}      </div>
    </section>

{$featureHtml}

    <section class="portfolio wrap" id="portfolio">
      <h2 class="section-title">{$portfolioTitle}</h2>
      <div class="grid">
{$tiles}      </div>
    </section>

    <section class="quote">
      <div class="wrap">
        <blockquote>
          <p>&ldquo;{$quoteText}&rdquo;</p>
          <cite>{$quoteAuthor}</cite>
        </blockquote>
      </div>
    </section>

    <section class="contact wrap" id="contact">
      <h2 class="section-title">{$contactTitle}</h2>
      <div class="contact-grid">
        <form class="contact-form" action="mailto:{$contactEmailHtml}" method="post" enctype="text/plain">
          <label>First name*<input type="text" name="First name" required></label>
          <label>Last name<input type="text" name="Last name"></label>
          <label class="full">Email*<input type="email" name="Email" required></label>
          <label class="full">Message<textarea name="Message" rows="5"></textarea></label>
          <button type="submit" class="btn btn-dark">Submit</button>
        </form>
        <div class="contact-info">
          <p class="eyebrow">{$brand}</p>
          <p><a href="mailto:{$contactEmailHtml}">{$contactEmailHtml}</a></p>
          <p>{$contactNote}</p>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="wrap footer-inner">
      <p class="footer-brand">{$brand}</p>
      <p>&copy; {$year} {$contactNote}</p>
    </div>
  </footer>

  <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer" hidden>
    <button class="lb-btn lb-close" aria-label="Close">&times;</button>
    <button class="lb-btn lb-prev" aria-label="Previous photo">&#8249;</button>
    <button class="lb-btn lb-next" aria-label="Next photo">&#8250;</button>
    <figure class="lb-figure">
      <img class="lb-img" id="lb-img" alt="">
      <figcaption class="lb-cap" id="lb-cap"></figcaption>
    </figure>
  </div>

  <script src="script.js"></script>
</body>
</html>
HTML;
}
