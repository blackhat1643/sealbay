<?php
/**
 * Single message / quote request: details, status and notes, delete.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$id      = (int) ($_GET['id'] ?? 0);
$enquiry = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if (!$enquiry) {
    flash('admin_err', 'That message could not be found.');
    redirect(admin_url('enquiries.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    if (a_post('action') === 'delete') {
        if ($enquiry['attachment_path'] && preg_match('/^[a-f0-9]{32}\.[a-z0-9]{3,4}$/', $enquiry['attachment_path'])) {
            @unlink(ST_STORAGE . '/uploads/' . $enquiry['attachment_path']);
        }
        db_run('DELETE FROM enquiries WHERE id = ?', [$id]);
        flash('admin_ok', 'Message ' . $enquiry['reference'] . ' deleted.');
        redirect(admin_url('enquiries.php'));
    }
    $status = isset(a_statuses()[a_post('status')]) ? a_post('status') : $enquiry['status'];
    db_run('UPDATE enquiries SET status = ?, admin_notes = ? WHERE id = ?', [$status, mb_substr(a_post('admin_notes', true), 0, 5000), $id]);
    flash('admin_ok', 'Saved.');
    redirect(admin_url('enquiry-view.php?id=' . $id));
}

$labels = enquiry_labels();
unset($labels['attachment_name']);
$isImage = $enquiry['attachment_path'] && preg_match('/\.(jpg|png|webp)$/', $enquiry['attachment_path']);

admin_header(($enquiry['type'] === 'contact' ? 'Message ' : 'Quote request ') . $enquiry['reference'], 'enquiries');
?>
<p><a href="<?= e(admin_url('enquiries.php')) ?>">← All messages</a></p>
<div class="a-cols">
  <section class="a-panel">
    <h2>Details <?= a_status_label($enquiry['status']) ?></h2>
    <dl class="a-dl">
      <?php foreach ($labels as $key => $label): $value = trim((string) ($enquiry[$key] ?? '')); if ($value === '') { continue; } ?>
      <dt><?= e($label) ?></dt>
      <dd>
        <?php if ($key === 'email'): ?><a href="mailto:<?= e($value) ?>"><?= e($value) ?></a>
        <?php elseif ($key === 'phone'): ?><a href="<?= e(tel_href($value)) ?>"><?= e($value) ?></a>
        <?php elseif ($key === 'created_at'): ?><?= e(format_date($value, 'j M Y, g:i a')) ?>
        <?php else: ?><?= nl2br(e($value)) ?><?php endif; ?>
      </dd>
      <?php endforeach; ?>
      <?php if ($enquiry['attachment_path']): ?>
      <dt>Photo</dt>
      <dd>
        <?php if ($isImage): ?><img class="a-photo" src="<?= e(admin_url('download.php?id=' . $id . '&inline=1')) ?>" alt="Photo sent by the customer"><br><?php endif; ?>
        <a class="a-btn a-btn--sm" href="<?= e(admin_url('download.php?id=' . $id)) ?>">Download <?= e($enquiry['attachment_name'] ?: 'file') ?></a>
      </dd>
      <?php endif; ?>
    </dl>
  </section>

  <div>
    <form class="a-panel" method="post">
      <h2>Follow-up</h2>
      <?= csrf_field() ?>
      <?= a_field('status', 'Status', $enquiry['status'], ['type' => 'select', 'options' => a_statuses()]) ?>
      <?= a_field('admin_notes', 'Internal notes', $enquiry['admin_notes'], ['type' => 'textarea', 'rows' => 7, 'hint' => 'Visible to administrators only.']) ?>
      <button class="a-btn a-btn--primary" type="submit">Save</button>
    </form>
    <form class="a-panel a-panel--danger" method="post" data-confirm="Delete this message permanently? This cannot be undone.">
      <h2>Delete</h2>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <p>Removes the message and its photo permanently.</p>
      <button class="a-btn a-btn--danger" type="submit">Delete</button>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
