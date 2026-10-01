<?php
/**
 * Shop categories: list and delete.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $id  = (int) a_post('id');
    $row = db_one('SELECT * FROM categories WHERE id = ?', [$id]);
    if ($row) {
        foreach (db_all('SELECT image FROM products WHERE category_id = ?', [$id]) as $product) {
            a_delete_image($product['image']);
        }
        a_delete_image($row['image']);
        db_run('DELETE FROM products WHERE category_id = ?', [$id]);
        db_run('DELETE FROM categories WHERE id = ?', [$id]);
        flash('admin_ok', 'Category “' . $row['name'] . '” and its products were deleted.');
    }
    redirect(admin_url('categories.php'));
}

$rows = db_all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.sort_order, c.id');

admin_header('Categories', 'categories');
?>
<p><a class="a-btn a-btn--primary" href="<?= e(admin_url('category-edit.php')) ?>">Add category</a></p>
<section class="a-panel">
  <div class="a-table-wrap">
    <table class="a-table">
      <thead><tr><th>Order</th><th>Name</th><th>Page</th><th>Products</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= (int) $row['sort_order'] ?></td>
          <td><a href="<?= e(admin_url('category-edit.php?id=' . (int) $row['id'])) ?>"><?= e($row['name']) ?></a></td>
          <td><a href="<?= e(category_url($row['slug'])) ?>" target="_blank" rel="noopener">View ↗</a></td>
          <td><a href="<?= e(admin_url('products.php?category=' . (int) $row['id'])) ?>"><?= (int) $row['product_count'] ?></a></td>
          <td><?= $row['is_active'] ? 'Active' : '<em>Hidden</em>' ?></td>
          <td class="a-actions">
            <form method="post" data-confirm="Delete “<?= e($row['name']) ?>” and all of its products?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="a-link-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php admin_footer(); ?>
