<?php
/**
 * Products: list, search, filter, quick stock view and delete.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $row = db_one('SELECT * FROM products WHERE id = ?', [(int) a_post('id')]);
    if ($row) {
        a_delete_image($row['image']);
        db_run('DELETE FROM products WHERE id = ?', [$row['id']]);
        flash('admin_ok', 'Product “' . $row['name'] . '” deleted. Past orders keep their own record of it.');
    }
    redirect(admin_url('products.php'));
}

$categories = db_all('SELECT id, name FROM categories ORDER BY sort_order, id');
$categoryId = (int) ($_GET['category'] ?? 0);
$search     = is_string($_GET['q'] ?? null) ? trim(mb_substr($_GET['q'], 0, 80)) : '';
$lowOnly    = ($_GET['stock'] ?? '') === 'low';
$pageNo     = max(1, (int) ($_GET['page'] ?? 1));
$per        = 50;
$threshold  = (int) shop('low_stock_threshold', '5');

$where  = [];
$params = [];
if ($categoryId) {
    $where[]  = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    array_push($params, '%' . $search . '%', '%' . $search . '%');
}
if ($lowOnly) {
    $where[]  = 'p.stock_qty <= ?';
    $params[] = $threshold;
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total    = (int) db_value('SELECT COUNT(*) FROM products p' . $sqlWhere, $params);
$pages    = max(1, (int) ceil($total / $per));
$pageNo   = min($pageNo, $pages);
$rows     = db_all(
    'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id' . $sqlWhere
    . ' ORDER BY c.sort_order, p.sort_order, p.inner_diameter, p.outer_diameter, p.id LIMIT ' . $per . ' OFFSET ' . (($pageNo - 1) * $per),
    $params
);
$query = static fn (array $extra = []): string => http_build_query(array_filter($extra + ['category' => $categoryId ?: '', 'q' => $search, 'stock' => $lowOnly ? 'low' : ''], 'strlen'));

admin_header('Products', 'products');
?>
<form class="a-filter" method="get">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or SKU…" aria-label="Search products">
  <select name="category" aria-label="Category">
    <option value="0">All categories</option>
    <?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id'] ?>"<?= $categoryId === (int) $cat['id'] ? ' selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?>
  </select>
  <select name="stock" aria-label="Stock">
    <option value="">Any stock level</option>
    <option value="low"<?= $lowOnly ? ' selected' : '' ?>>Low or no stock (≤ <?= $threshold ?>)</option>
  </select>
  <button class="a-btn" type="submit">Filter</button>
  <a class="a-btn a-btn--primary" href="<?= e(admin_url('product-edit.php' . ($categoryId ? '?category=' . $categoryId : ''))) ?>">Add product</a>
</form>
<section class="a-panel">
  <p class="a-count"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></p>
  <?php if (!$rows): ?>
  <p class="a-empty">No products match this filter.</p>
  <?php else: ?>
  <div class="a-table-wrap">
    <table class="a-table">
      <thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Size (mm)</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): $qty = (int) $row['stock_qty']; ?>
        <tr>
          <td><?= e($row['sku']) ?></td>
          <td><a href="<?= e(admin_url('product-edit.php?id=' . (int) $row['id'])) ?>"><?= e($row['name']) ?></a></td>
          <td><?= e($row['category_name']) ?></td>
          <td><?= $row['inner_diameter'] !== null ? e(mm($row['inner_diameter']) . ' × ' . mm($row['outer_diameter']) . ' × ' . mm($row['width'])) : '—' ?></td>
          <td><?= e(money((int) $row['price_cents'])) ?></td>
          <td><?= $qty <= 0 ? '<span class="a-badge a-badge--cancelled">0</span>' : ($qty <= $threshold ? '<span class="a-badge a-badge--new">' . $qty . '</span>' : $qty) ?></td>
          <td><?= $row['is_active'] ? 'Active' : '<em>Hidden</em>' ?></td>
          <td class="a-actions">
            <a href="<?= e(product_url($row['slug'])) ?>" target="_blank" rel="noopener">View ↗</a>
            <form method="post" data-confirm="Delete “<?= e($row['name']) ?>”?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="a-link-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="a-pages" aria-label="Pages">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a class="<?= $p === $pageNo ? 'is-active' : '' ?>" href="<?= e(admin_url('products.php?' . $query(['page' => (string) $p]))) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
