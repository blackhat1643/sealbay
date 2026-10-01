<?php
/**
 * Add / edit a how-to guide.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$id  = (int) ($_GET['id'] ?? 0);
$row = $id ? db_one('SELECT * FROM articles WHERE id = ?', [$id]) : null;
if ($id && !$row) {
    redirect(admin_url('articles.php'));
}
$row = $row ?? ['title' => '', 'slug' => '', 'topic' => 'How-to', 'excerpt' => '', 'body' => "<p></p>\n<h2></h2>\n<p></p>", 'faqs' => '',
    'reading_minutes' => 5, 'meta_title' => '', 'meta_description' => '', 'is_published' => 0, 'published_at' => date('Y-m-d')];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $date = a_post('published_at');
    $data = [
        'title'            => mb_substr(a_post('title'), 0, 200),
        'topic'            => mb_substr(a_post('topic'), 0, 80),
        'excerpt'          => a_post('excerpt', true),
        'body'             => a_post('body', true),
        'faqs'             => a_post('faqs', true),
        'reading_minutes'  => max(1, min(60, (int) a_post('reading_minutes'))),
        'meta_title'       => mb_substr(a_post('meta_title'), 0, 200),
        'meta_description' => mb_substr(a_post('meta_description'), 0, 320),
        'is_published'     => isset($_POST['is_published']) ? 1 : 0,
        'published_at'     => (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && strtotime($date) ? $date : date('Y-m-d')) . ' 00:00:00',
        'updated_at'       => db_now(),
    ];
    if ($data['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if (trim(strip_tags($data['body'])) === '') {
        $errors[] = 'The article body is empty.';
    }
    $data['slug'] = a_unique_slug('articles', slugify(a_post('slug') ?: $data['title']), $id);

    if (!$errors) {
        if ($id) {
            $set = implode(', ', array_map(static fn ($c) => "$c = ?", array_keys($data)));
            db_run("UPDATE articles SET $set WHERE id = ?", [...array_values($data), $id]);
        } else {
            db_run('INSERT INTO articles (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
        }
        flash('admin_ok', 'Guide saved.');
        redirect(admin_url('articles.php'));
    }
    $row = $data + $row;
}

admin_header($id ? 'Edit guide' : 'Add guide', 'articles');
?>
<p><a href="<?= e(admin_url('articles.php')) ?>">← All guides</a></p>
<?php if ($errors): ?><div class="a-alert a-alert--err"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form class="a-panel a-form" method="post">
  <?= csrf_field() ?>
  <?= a_field('title', 'Title', $row['title'], ['required' => true, 'maxlength' => 200, 'wide' => true]) ?>
  <?= a_field('slug', 'Address (slug)', $row['slug'], ['hint' => 'Leave empty to create it from the title. Changing it changes the guide’s address.', 'maxlength' => 160]) ?>
  <?= a_field('topic', 'Topic', $row['topic'], ['maxlength' => 80, 'hint' => 'e.g. How-to, Materials, Hydraulic seals, Troubleshooting']) ?>
  <?= a_field('excerpt', 'Summary', $row['excerpt'], ['type' => 'textarea', 'rows' => 3, 'wide' => true, 'hint' => 'One or two sentences shown on cards and under the title.']) ?>
  <?= a_field('body', 'Guide text (HTML)', $row['body'], ['type' => 'textarea', 'rows' => 22, 'wide' => true, 'hint' => 'Allowed tags: p, h2, h3, ul, ol, li, strong, em, a, blockquote, table. Other tags and all attributes except link addresses are removed when the guide is shown. Link to pages on this site with addresses such as /shop/rotary-shaft-seals or /measure-your-seal.php.']) ?>
  <?= a_field('faqs', 'FAQs', $row['faqs'], ['type' => 'textarea', 'rows' => 5, 'wide' => true, 'hint' => 'Optional. One per line as “Question? | Answer”.']) ?>
  <?= a_field('meta_title', 'Search title', $row['meta_title'], ['maxlength' => 200, 'hint' => 'Optional. Around 60 characters.']) ?>
  <?= a_field('meta_description', 'Search description', $row['meta_description'], ['maxlength' => 320, 'hint' => 'Optional. Around 150 characters.']) ?>
  <?= a_field('reading_minutes', 'Reading time (minutes)', (string) $row['reading_minutes'], ['type' => 'number']) ?>
  <?= a_field('published_at', 'Publication date', substr((string) $row['published_at'], 0, 10), ['type' => 'date']) ?>
  <div class="a-field a-field--wide"><label class="a-check"><input type="checkbox" name="is_published" value="1"<?= $row['is_published'] ? ' checked' : '' ?>> Published (visible on the website)</label></div>
  <div class="a-form__foot"><button class="a-btn a-btn--primary" type="submit">Save guide</button></div>
</form>
<?php admin_footer(); ?>
