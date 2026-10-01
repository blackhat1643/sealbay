<?php
/**
 * Add / edit a shop category.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$id  = (int) ($_GET['id'] ?? 0);
$row = $id ? db_one('SELECT * FROM categories WHERE id = ?', [$id]) : null;
if ($id && !$row) {
    redirect(admin_url('categories.php'));
}
$row    = $row ?? ['name' => '', 'slug' => '', 'headline' => '', 'summary' => '', 'description' => '', 'illustration' => 'reference', 'image' => null, 'sort_order' => 0, 'is_active' => 1];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $data = [
        'name'         => mb_substr(a_post('name'), 0, 160),
        'headline'     => mb_substr(a_post('headline'), 0, 200),
        'summary'      => a_post('summary', true),
        'description'  => a_post('description', true),
        'illustration' => isset(illustration_keys()[a_post('illustration')]) ? a_post('illustration') : 'reference',
        'sort_order'   => (int) a_post('sort_order'),
        'is_active'    => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($data['name'] === '') {
        $errors[] = 'Name is required.';
    }
    // The slug is the page address; it is fixed once a category exists so links do not break.
    $data['slug'] = $id ? $row['slug'] : a_unique_slug('categories', slugify(a_post('slug') ?: $data['name']));

    $upload = a_image_upload('image');
    if ($upload['error']) {
        $errors[] = $upload['error'];
    }
    $data['image'] = $row['image'];
    if (!$errors) {
        if ($upload['path'] || isset($_POST['remove_image'])) {
            a_delete_image($row['image']);
            $data['image'] = $upload['path'];
        }
        $data['updated_at'] = db_now();
        if ($id) {
            $set = implode(', ', array_map(static fn ($c) => "$c = ?", array_keys($data)));
            db_run("UPDATE categories SET $set WHERE id = ?", [...array_values($data), $id]);
        } else {
            db_run('INSERT INTO categories (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
        }
        flash('admin_ok', 'Category saved.');
        redirect(admin_url('categories.php'));
    }
    $row = $data + $row;
}

admin_header($id ? 'Edit category' : 'Add category', 'categories');
?>
<p><a href="<?= e(admin_url('categories.php')) ?>">← All categories</a></p>
<?php if ($errors): ?><div class="a-alert a-alert--err"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form class="a-panel a-form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?= a_field('name', 'Name', $row['name'], ['required' => true, 'maxlength' => 160]) ?>
  <?php if ($id): ?>
  <div class="a-field"><label>Page address</label><p class="a-static"><?= e(category_url($row['slug'])) ?></p></div>
  <?php else: ?>
  <?= a_field('slug', 'Page address (slug)', $row['slug'], ['hint' => 'Leave empty to create it from the name. Cannot be changed later.', 'maxlength' => 120]) ?>
  <?php endif; ?>
  <?= a_field('headline', 'Page headline', $row['headline'], ['wide' => true, 'maxlength' => 200, 'hint' => 'Main heading of the category page.']) ?>
  <?= a_field('summary', 'Short description', $row['summary'], ['type' => 'textarea', 'rows' => 3, 'wide' => true, 'hint' => 'Shown on the category card and under the page heading.']) ?>
  <?= a_field('description', 'About this range', $row['description'], ['type' => 'textarea', 'rows' => 8, 'wide' => true, 'hint' => 'Shown below the products. Leave an empty line between paragraphs.']) ?>
  <?= a_field('illustration', 'Drawing', $row['illustration'], ['type' => 'select', 'options' => illustration_keys(), 'hint' => 'Used on the category card when no photo is uploaded.']) ?>
  <div class="a-field">
    <label for="a-image">Card photo <span>(optional — JPG, PNG or WebP)</span></label>
    <?php if ($row['image'] && ($img = uploaded_image($row['image']))): ?>
    <img class="a-thumb" src="<?= e($img['src']) ?>" alt="">
    <label class="a-check"><input type="checkbox" name="remove_image" value="1"> Remove current photo</label>
    <?php endif; ?>
    <input id="a-image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
  </div>
  <?= a_field('sort_order', 'Display order', (string) $row['sort_order'], ['type' => 'number']) ?>
  <div class="a-field"><label class="a-check"><input type="checkbox" name="is_active" value="1"<?= $row['is_active'] ? ' checked' : '' ?>> Show on the website</label></div>
  <div class="a-form__foot"><button class="a-btn a-btn--primary" type="submit">Save category</button></div>
</form>
<?php admin_footer(); ?>
