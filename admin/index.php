<?php
declare(strict_types=1);

/*
 * Malave Family - admin dashboard.
 */

define('MALAVE_APP', true);
require dirname(__DIR__) . '/library/bootstrap.php';

malave_session_start();
malave_require_login();

$settings = malave_settings();
$albums = malave_albums();
$allPhotos = malave_photos();
$counts = malave_photo_counts_by_album();

$albumTitles = [];
foreach ($albums as $album) {
    $albumTitles[(int) $album['id']] = (string) $album['title'];
}

/** Options for a photo picker. */
function malave_option_list(array $photos, array $albumTitles, array $selected): string
{
    $out = '';
    foreach ($photos as $photo) {
        $album = $albumTitles[(int) $photo['album_id']] ?? 'Album';
        $label = $album . ' — ' . ($photo['title'] !== '' ? $photo['title'] : basename((string) $photo['file']));
        $isSelected = in_array((int) $photo['id'], $selected, true) ? ' selected' : '';
        $out .= '<option value="' . (int) $photo['id'] . '"' . $isSelected . '>' . h($label) . '</option>';
    }
    return $out;
}

$heroIds = malave_json_ids($settings['hero_photos'] ?? '');
$aboutIds = malave_json_ids($settings['about_photos'] ?? '');
$featureId = (int) ($settings['feature_photo'] ?? 0);

$activeAlbumId = (int) ($_GET['album'] ?? 0);
if ($activeAlbumId <= 0 || !isset($albumTitles[$activeAlbumId])) {
    $activeAlbumId = (int) ($albums[0]['id'] ?? 0);
}
$activeAlbum = $activeAlbumId > 0 ? malave_album($activeAlbumId) : null;
$activeCoverId = (int) ($activeAlbum['cover_photo_id'] ?? 0);
$activePhotos = $activeAlbumId > 0 ? malave_photos($activeAlbumId) : [];

$settingsFields = [
    'site_title'          => ['Page title', 'text'],
    'brand'               => ['Site name', 'text'],
    'hero_headline'       => ['Hero heading', 'text'],
    'hero_tagline'        => ['Hero sub-text', 'text'],
    'hero_button'         => ['Hero button label', 'text'],
    'about_title'         => ['About heading', 'text'],
    'about_story_title'   => ['Story heading', 'text'],
    'about_story'         => ['Story text', 'textarea'],
    'about_mission_title' => ['Mission heading', 'text'],
    'about_mission'       => ['Mission text', 'textarea'],
    'albums_title'        => ['Albums heading', 'text'],
    'feature_eyebrow'     => ['Feature eyebrow', 'text'],
    'feature_title'       => ['Feature heading', 'text'],
    'feature_body'        => ['Feature text', 'textarea'],
    'feature_button'      => ['Feature button label', 'text'],
    'portfolio_title'     => ['Portfolio heading', 'text'],
    'quote_text'          => ['Quote', 'textarea'],
    'quote_author'        => ['Quote attribution', 'text'],
    'contact_title'       => ['Contact heading', 'text'],
    'contact_email'       => ['Contact email', 'text'],
    'contact_note'        => ['Footer note / domain', 'text'],
];

malave_admin_header('Dashboard');
?>
<nav class="jump">
  <a href="#text">Text &amp; images</a>
  <a href="#albums">Albums</a>
  <a href="#photos">Photos</a>
  <a href="#account">Account</a>
</nav>

<section id="text" class="panel">
  <h2>Text &amp; images</h2>
  <form method="post" action="actions.php">
    <?= malave_csrf_field() ?>
    <input type="hidden" name="action" value="settings_save">
    <div class="grid-2">
      <?php foreach ($settingsFields as $key => [$label, $type]): ?>
        <label class="<?= $type === 'textarea' ? 'wide' : '' ?>">
          <?= h($label) ?>
          <?php if ($type === 'textarea'): ?>
            <textarea name="<?= h($key) ?>" rows="4"><?= h((string) ($settings[$key] ?? '')) ?></textarea>
          <?php else: ?>
            <input type="text" name="<?= h($key) ?>" value="<?= h((string) ($settings[$key] ?? '')) ?>">
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="grid-3 pickers">
      <label>Hero collage photos <small>up to 5</small>
        <select name="hero_photos[]" multiple size="7">
          <?= malave_option_list($allPhotos, $albumTitles, $heroIds) ?>
        </select>
      </label>
      <label>About collage photos <small>up to 4</small>
        <select name="about_photos[]" multiple size="7">
          <?= malave_option_list($allPhotos, $albumTitles, $aboutIds) ?>
        </select>
      </label>
      <label>Featured photo
        <select name="feature_photo" size="7">
          <option value="">— none —</option>
          <?= malave_option_list($allPhotos, $albumTitles, $featureId ? [$featureId] : []) ?>
        </select>
      </label>
    </div>
    <button type="submit" class="btn">Save text &amp; images</button>
  </form>
</section>

<section id="albums" class="panel">
  <h2>Albums</h2>
  <?php foreach ($albums as $album): $id = (int) $album['id']; ?>
    <form method="post" action="actions.php" class="row-form album-row">
      <?= malave_csrf_field() ?>
      <input type="hidden" name="album_id" value="<?= $id ?>">
      <div class="row-fields">
        <label>Title<input type="text" name="title" value="<?= h((string) $album['title']) ?>" required></label>
        <label class="grow">Description<input type="text" name="description" value="<?= h((string) $album['description']) ?>"></label>
        <label class="inline">
          <input type="checkbox" name="visible" value="1"<?= ((int) $album['visible'] === 1) ? ' checked' : '' ?>>
          Visible
        </label>
        <span class="muted small"><?= (int) ($counts[$id] ?? 0) ?> photos</span>
      </div>
      <div class="row-actions">
        <button name="action" value="album_save" class="btn small">Save</button>
        <button name="action" value="album_up" class="btn small ghost" title="Move up">&uarr;</button>
        <button name="action" value="album_down" class="btn small ghost" title="Move down">&darr;</button>
        <button name="action" value="album_delete" class="btn small danger"
                onclick="return confirm('Delete this album and all of its photos?')">Delete</button>
        <a class="btn small ghost" href="index.php?album=<?= $id ?>#photos">Photos</a>
      </div>
    </form>
  <?php endforeach; ?>

  <h3>Add an album</h3>
  <form method="post" action="actions.php" class="row-form">
    <?= malave_csrf_field() ?>
    <div class="row-fields">
      <label>Title<input type="text" name="title" required placeholder="Summer 2005"></label>
      <label class="grow">Description<input type="text" name="description" placeholder="Optional"></label>
      <label class="inline"><input type="checkbox" name="visible" value="1" checked> Visible</label>
    </div>
    <div class="row-actions">
      <button name="action" value="album_create" class="btn small">Add album</button>
    </div>
  </form>
</section>

<section id="photos" class="panel">
  <h2>Photos</h2>
  <?php if (!$albums): ?>
    <p class="muted">Create an album first.</p>
  <?php else: ?>
    <form method="get" action="index.php#photos" class="row-form">
      <div class="row-fields">
        <label>Album
          <select name="album" onchange="this.form.submit()">
            <?php foreach ($albums as $album): $id = (int) $album['id']; ?>
              <option value="<?= $id ?>"<?= $id === $activeAlbumId ? ' selected' : '' ?>>
                <?= h((string) $album['title']) ?> (<?= (int) ($counts[$id] ?? 0) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <noscript><button class="btn small">Open</button></noscript>
    </form>

    <form method="post" action="actions.php" enctype="multipart/form-data" class="row-form upload-row">
      <?= malave_csrf_field() ?>
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="album_id" value="<?= $activeAlbumId ?>">
      <div class="row-fields">
        <label class="grow">Add photos to “<?= h($albumTitles[$activeAlbumId] ?? '') ?>”
          <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
        </label>
      </div>
      <div class="row-actions">
        <button type="submit" class="btn small">Upload</button>
      </div>
    </form>

    <div class="photo-grid">
      <?php foreach ($activePhotos as $photo): $pid = (int) $photo['id']; ?>
        <form method="post" action="actions.php" class="photo-card">
          <?= malave_csrf_field() ?>
          <input type="hidden" name="photo_id" value="<?= $pid ?>">
          <img src="<?= h(malave_url((string) $photo['thumb'])) ?>" alt="" loading="lazy">
          <input type="text" name="title" value="<?= h((string) $photo['title']) ?>" placeholder="Title">
          <input type="text" name="caption" value="<?= h((string) $photo['caption']) ?>" placeholder="Caption">
          <div class="row-actions">
            <button name="action" value="photo_save" class="btn tiny">Save</button>
            <button name="action" value="photo_cover" class="btn tiny ghost" title="Set as album cover"<?= $pid === $activeCoverId ? ' disabled' : '' ?>><?= $pid === $activeCoverId ? 'Cover ✓' : 'Cover' ?></button>
            <button name="action" value="photo_up" class="btn tiny ghost" title="Move up">&uarr;</button>
            <button name="action" value="photo_down" class="btn tiny ghost" title="Move down">&darr;</button>
            <button name="action" value="photo_delete" class="btn tiny danger"
                    onclick="return confirm('Delete this photo?')">Delete</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
    <?php if (!$activePhotos): ?><p class="muted">No photos in this album yet.</p><?php endif; ?>
  <?php endif; ?>
</section>

<section id="account" class="panel">
  <h2>Account</h2>
  <form method="post" action="actions.php" class="row-form">
    <?= malave_csrf_field() ?>
    <input type="hidden" name="action" value="password_change">
    <div class="row-fields">
      <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
      <label>New password<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
      <label>Confirm new password<input type="password" name="confirm" required minlength="10" autocomplete="new-password"></label>
    </div>
    <div class="row-actions">
      <button type="submit" class="btn small">Change password</button>
    </div>
  </form>
  <form method="post" action="actions.php" enctype="multipart/form-data" class="row-form">
    <?= malave_csrf_field() ?>
    <input type="hidden" name="action" value="backup_restore">
    <div class="row-fields">
      <label class="grow">Backups: <a href="backup.php">download a JSON copy of all text and photo records</a>, or restore records from a JSON backup (image files must remain on the server)
        <input type="file" name="backup" accept="application/json,.json" required>
      </label>
    </div>
    <div class="row-actions">
      <button type="submit" class="btn small ghost" onclick="return confirm('Restore text, albums, and photo records from this backup file?')">Restore backup</button>
    </div>
  </form>
</section>
<?php
malave_admin_footer();
