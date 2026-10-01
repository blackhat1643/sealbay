<?php
/**
 * Add / edit a product.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$categories = array_column(db_all('SELECT id, name FROM categories ORDER BY sort_order, id'), 'name', 'id');
if (!$categories) {
    flash('admin_err', 'Create a category first.');
    redirect(admin_url('categories.php'));
}

$id  = (int) ($_GET['id'] ?? 0);
$row = $id ? db_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$row) {
    redirect(admin_url('products.php'));
}
$row = $row ?? [
    'category_id' => (int) ($_GET['category'] ?? array_key_first($categories)), 'name' => '', 'slug' => '', 'sku' => '', 'seal_type' => 'rotary',
    'style' => '', 'material' => '', 'inner_diameter' => '', 'outer_diameter' => '', 'width' => '', 'temp_range' => '', 'price_cents' => 0,
    'stock_qty' => 0, 'allow_backorder' => 1, 'summary' => '', 'fitment' => '', 'kit_contents' => '', 'illustration' => 'tc', 'image' => null,
    'is_featured' => 0, 'sort_order' => 0, 'is_active' => 1,
];
$row['price'] = $row['price'] ?? number_format(((int) $row['price_cents']) / 100, 2, '.', '');
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $dim = static function (string $name) use (&$errors): ?float {
        $value = a_post($name);
        if ($value === '') {
            return null;
        }
        $mm = parse_mm($value);
        if ($mm === null) {
            $errors[] = ucfirst(str_replace('_', ' ', $name)) . ' must be a number in millimetres.';
        }
        return $mm;
    };
    $price = to_cents(a_post('price'));
    $data  = [
        'category_id'     => isset($categories[(int) a_post('category_id')]) ? (int) a_post('category_id') : 0,
        'name'            => mb_substr(a_post('name'), 0, 200),
        'sku'             => mb_substr(strtoupper(a_post('sku')), 0, 60),
        'seal_type'       => isset(SEAL_TYPES[a_post('seal_type')]) ? a_post('seal_type') : 'rotary',
        'style'           => mb_substr(a_post('style'), 0, 120),
        'material'        => mb_substr(a_post('material'), 0, 60),
        'inner_diameter'  => $dim('inner_diameter'),
        'outer_diameter'  => $dim('outer_diameter'),
        'width'           => $dim('width'),
        'temp_range'      => mb_substr(a_post('temp_range'), 0, 80),
        'price_cents'     => $price ?? 0,
        'stock_qty'       => max(0, (int) a_post('stock_qty')),
        'allow_backorder' => isset($_POST['allow_backorder']) ? 1 : 0,
        'summary'         => a_post('summary', true),
        'fitment'         => a_post('fitment', true),
        'kit_contents'    => a_post('kit_contents', true),
        'illustration'    => isset(illustration_keys()[a_post('illustration')]) ? a_post('illustration') : 'reference',
        'is_featured'     => isset($_POST['is_featured']) ? 1 : 0,
        'sort_order'      => (int) a_post('sort_order'),
        'is_active'       => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($data['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if (!$data['category_id']) {
        $errors[] = 'Choose a category.';
    }
    if ($price === null || $price <= 0) {
        $errors[] = 'Enter the price in dollars including GST, for example 8.90.';
    }
    if ($data['sku'] === '' || !preg_match('/^[A-Z0-9._-]+$/', $data['sku'])) {
        $errors[] = 'SKU is required (letters, numbers, dot, dash and underscore only).';
    } elseif (db_value('SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?', [$data['sku'], $id]) > 0) {
        $errors[] = 'Another product already uses that SKU.';
    }
    // The slug is the page address; it is fixed once the product exists so links and search results do not break.
    $data['slug'] = $id ? $row['slug'] : a_unique_slug('products', slugify(str_replace('.', 'p', a_post('slug') ?: $data['name'] . ($data['material'] !== '' ? ' ' . $data['material'] : ''))));

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
            db_run("UPDATE products SET $set WHERE id = ?", [...array_values($data), $id]);
        } else {
            db_run('INSERT INTO products (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
        }
        flash('admin_ok', 'Product saved.');
        redirect(admin_url('products.php?category=' . $data['category_id']));
    }
    $row = ['price' => a_post('price'), 'inner_diameter' => a_post('inner_diameter'), 'outer_diameter' => a_post('outer_diameter'), 'width' => a_post('width')] + $data + $row;
}

$dimValue = static fn ($v): string => ($v === null || $v === '') ? '' : (is_numeric($v) ? mm((float) $v) : (string) $v);

admin_header($id ? 'Edit product' : 'Add product', 'products');
?>
<p><a href="<?= e(admin_url('products.php')) ?>">← All products</a></p>
<?php if ($errors): ?><div class="a-alert a-alert--err"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <section class="a-panel a-form">
    <h2 class="a-field--wide">Product</h2>
    <?= a_field('name', 'Product name', $row['name'], ['required' => true, 'maxlength' => 200, 'wide' => true, 'hint' => 'Include the size, e.g. “TC 35x52x7 Rotary Shaft Seal”. This is the page title people find in search.']) ?>
    <?= a_field('sku', 'SKU', $row['sku'], ['required' => true, 'maxlength' => 60]) ?>
    <?= a_field('category_id', 'Category', (string) $row['category_id'], ['type' => 'select', 'options' => $categories]) ?>
    <?= a_field('seal_type', 'Seal type (size finder)', $row['seal_type'], ['type' => 'select', 'options' => SEAL_TYPES]) ?>
    <?= a_field('style', 'Seal style', $row['style'], ['maxlength' => 120, 'hint' => 'e.g. TC double-lip with spring']) ?>
    <?php if ($id): ?>
    <div class="a-field a-field--wide"><label>Page address</label><p class="a-static"><?= e(product_url($row['slug'])) ?></p></div>
    <?php else: ?>
    <?= a_field('slug', 'Page address (slug)', $row['slug'], ['wide' => true, 'maxlength' => 160, 'hint' => 'Leave empty to create it from the name and material. Cannot be changed later.']) ?>
    <?php endif; ?>
  </section>

  <section class="a-panel a-form a-form--3">
    <h2 class="a-field--wide">Size and material</h2>
    <?= a_field('inner_diameter', 'Inner diameter (mm)', $dimValue($row['inner_diameter'])) ?>
    <?= a_field('outer_diameter', 'Outer diameter (mm)', $dimValue($row['outer_diameter'])) ?>
    <?= a_field('width', 'Width (mm)', $dimValue($row['width'])) ?>
    <?= a_field('material', 'Material', $row['material'], ['maxlength' => 60, 'hint' => 'e.g. NBR, FKM, Polyurethane']) ?>
    <?= a_field('temp_range', 'Temperature range', $row['temp_range'], ['maxlength' => 80, 'hint' => 'From your supplier’s data sheet, e.g. -30 °C to +100 °C']) ?>
    <div class="a-field"><small>Leave the three sizes empty for kits. The size finder matches on these numbers.</small></div>
  </section>

  <section class="a-panel a-form a-form--3">
    <h2 class="a-field--wide">Price and stock</h2>
    <?= a_field('price', 'Price (AUD, incl. GST)', (string) $row['price'], ['required' => true, 'hint' => 'e.g. 8.90']) ?>
    <?= a_field('stock_qty', 'Stock on hand', (string) $row['stock_qty'], ['type' => 'number']) ?>
    <?= a_field('sort_order', 'Display order', (string) $row['sort_order'], ['type' => 'number', 'hint' => 'Lower numbers first; equal numbers sort by size.']) ?>
    <div class="a-field a-field--wide">
      <label class="a-check"><input type="checkbox" name="allow_backorder" value="1"<?= $row['allow_backorder'] ? ' checked' : '' ?>> Can be ordered when out of stock (shown as “On backorder”)</label>
      <label class="a-check"><input type="checkbox" name="is_featured" value="1"<?= $row['is_featured'] ? ' checked' : '' ?>> Show under “Popular sizes” on the homepage</label>
      <label class="a-check"><input type="checkbox" name="is_active" value="1"<?= $row['is_active'] ? ' checked' : '' ?>> Show on the website</label>
    </div>
  </section>

  <section class="a-panel a-form">
    <h2 class="a-field--wide">Description</h2>
    <?= a_field('summary', 'Description', $row['summary'], ['type' => 'textarea', 'rows' => 4, 'wide' => true]) ?>
    <?= a_field('fitment', 'Fitment notes', $row['fitment'], ['type' => 'textarea', 'rows' => 4, 'wide' => true, 'hint' => 'Common machines or applications this size is used on. Only list fitments you have confirmed.']) ?>
    <?= a_field('kit_contents', 'Kit contents', is_array($row['kit_contents']) ? implode("\n", $row['kit_contents']) : $row['kit_contents'], ['type' => 'textarea', 'rows' => 4, 'hint' => 'Kits only. One item per line.']) ?>
    <div>
      <?= a_field('illustration', 'Drawing', $row['illustration'], ['type' => 'select', 'options' => illustration_keys(), 'hint' => 'Shown until a photo is uploaded.']) ?>
      <div class="a-field" style="margin-top:16px">
        <label for="a-image">Photo <span>(with a ruler or coin for scale — JPG, PNG or WebP)</span></label>
        <?php if ($row['image'] && ($img = uploaded_image($row['image']))): ?>
        <img class="a-thumb" src="<?= e($img['src']) ?>" alt="">
        <label class="a-check"><input type="checkbox" name="remove_image" value="1"> Remove current photo</label>
        <?php endif; ?>
        <input id="a-image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
      </div>
    </div>
  </section>
  <p><button class="a-btn a-btn--primary" type="submit">Save product</button></p>
</form>
<?php admin_footer(); ?>
