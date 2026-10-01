<?php
/**
 * How-to guides: list and delete.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $row = db_one('SELECT id, title FROM articles WHERE id = ?', [(int) a_post('id')]);
    if ($row) {
        db_run('DELETE FROM articles WHERE id = ?', [$row['id']]);
        flash('admin_ok', 'Guide “' . $row['title'] . '” deleted.');
    }
    redirect(admin_url('articles.php'));
}

$rows = db_all('SELECT id, slug, title, topic, is_published, published_at FROM articles ORDER BY published_at DESC, id');

admin_header('Guides', 'articles');
?>
<p><a class="a-btn a-btn--primary" href="<?= e(admin_url('article-edit.php')) ?>">Add guide</a></p>
<section class="a-panel">
  <?php if (!$rows): ?>
  <p class="a-empty">No guides yet.</p>
  <?php else: ?>
  <div class="a-table-wrap">
    <table class="a-table">
      <thead><tr><th>Title</th><th>Topic</th><th>Published</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><a href="<?= e(admin_url('article-edit.php?id=' . (int) $row['id'])) ?>"><?= e($row['title']) ?></a></td>
          <td><?= e($row['topic']) ?></td>
          <td><?= e(format_date($row['published_at'])) ?></td>
          <td><?= $row['is_published'] ? 'Published' : '<em>Draft</em>' ?></td>
          <td class="a-actions">
            <?php if ($row['is_published']): ?><a href="<?= e(article_url($row['slug'])) ?>" target="_blank" rel="noopener">View ↗</a><?php endif; ?>
            <form method="post" data-confirm="Delete “<?= e($row['title']) ?>”?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="a-link-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
