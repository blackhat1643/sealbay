<?php
/**
 * Messages and custom-quote requests: list, filter and search.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$status = isset(a_statuses()[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$type   = in_array($_GET['type'] ?? '', ['quote', 'contact'], true) ? $_GET['type'] : '';
$search = is_string($_GET['q'] ?? null) ? trim(mb_substr($_GET['q'], 0, 80)) : '';
$pageNo = max(1, (int) ($_GET['page'] ?? 1));
$per    = 25;

$where  = [];
$params = [];
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
}
if ($type !== '') {
    $where[]  = 'type = ?';
    $params[] = $type;
}
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR reference LIKE ? OR machine LIKE ? OR message LIKE ?)';
    $like    = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$total  = (int) db_value('SELECT COUNT(*) FROM enquiries' . $sqlWhere, $params);
$pages  = max(1, (int) ceil($total / $per));
$pageNo = min($pageNo, $pages);
$rows   = db_all('SELECT * FROM enquiries' . $sqlWhere . ' ORDER BY created_at DESC, id DESC LIMIT ' . $per . ' OFFSET ' . (($pageNo - 1) * $per), $params);
$query  = static fn (array $extra = []): string => http_build_query(array_filter($extra + ['status' => $status, 'type' => $type, 'q' => $search], 'strlen'));

admin_header('Messages & quotes', 'enquiries');
?>
<form class="a-filter" method="get">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, email, reference, machine…" aria-label="Search">
  <select name="status" aria-label="Status">
    <option value="">All statuses</option>
    <?php foreach (a_statuses() as $key => $label): ?><option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
  </select>
  <select name="type" aria-label="Form">
    <option value="">All forms</option>
    <option value="quote"<?= $type === 'quote' ? ' selected' : '' ?>>Custom quote requests</option>
    <option value="contact"<?= $type === 'contact' ? ' selected' : '' ?>>Contact messages</option>
  </select>
  <button class="a-btn" type="submit">Filter</button>
</form>

<section class="a-panel">
  <p class="a-count"><?= $total ?> message<?= $total === 1 ? '' : 's' ?></p>
  <?php if (!$rows): ?>
  <p class="a-empty">Nothing matches this filter.</p>
  <?php else: ?>
  <div class="a-table-wrap">
    <table class="a-table">
      <thead><tr><th>Reference</th><th>Received</th><th>Type</th><th>Name</th><th>Size (ID × OD × W)</th><th>Photo</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr<?= $row['status'] === 'new' ? ' class="is-new"' : '' ?>>
          <td><a href="<?= e(admin_url('enquiry-view.php?id=' . (int) $row['id'])) ?>"><?= e($row['reference']) ?></a></td>
          <td><?= e(format_date($row['created_at'], 'j M Y, g:i a')) ?></td>
          <td><?= $row['type'] === 'contact' ? 'Contact' : 'Quote' ?></td>
          <td><?= e($row['name']) ?></td>
          <td><?= e(trim(implode(' × ', array_filter([$row['inner_diameter'], $row['outer_diameter'], $row['width']], static fn ($v) => (string) $v !== '')))) ?: '—' ?></td>
          <td><?= $row['attachment_path'] ? 'Yes' : '—' ?></td>
          <td><?= a_status_label($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="a-pages" aria-label="Pages">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a class="<?= $p === $pageNo ? 'is-active' : '' ?>" href="<?= e(admin_url('enquiries.php?' . $query(['page' => (string) $p]))) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
