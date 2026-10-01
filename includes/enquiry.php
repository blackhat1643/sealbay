<?php
/**
 * Customer messages: the "custom quote" form (with photo upload) and the contact form.
 * Validation, spam checks, attachment handling, storage (database, with a file
 * fallback) and email notification.
 *
 * Usage on a form page:
 *     $state = handle_enquiry('quote');   // or 'contact'
 *     // $state['errors'] and $state['values'] feed the form when validation fails;
 *     // on success the visitor is redirected back to the page with a confirmation.
 */
defined('ST_APP') || exit;

/** Field definitions: name => [label, max length, required?] per form type. */
function enquiry_fields(string $type): array
{
    if ($type === 'contact') {
        return [
            'name'    => ['Name', 120, true],
            'email'   => ['Email', 190, true],
            'phone'   => ['Phone', 40, false],
            'message' => ['Message', 3000, true],
        ];
    }
    return [
        'name'           => ['Name', 120, true],
        'email'          => ['Email', 190, true],
        'phone'          => ['Phone', 40, false],
        'postcode'       => ['Postcode', 4, false],
        'seal_type'      => ['Seal type', 120, false],
        'inner_diameter' => ['Inner diameter', 60, false],
        'outer_diameter' => ['Outer diameter', 60, false],
        'width'          => ['Width', 60, false],
        'quantity'       => ['Quantity', 60, false],
        'machine'        => ['Machine or application', 250, false],
        'message'        => ['Anything else', 3000, false],
    ];
}

/** Trim, strip control characters and collapse whitespace (newlines kept only for the message). */
function clean_input(mixed $value, bool $multiline = false): string
{
    if (!is_string($value)) {
        return '';
    }
    $value = str_replace("\0", '', $value);
    if ($multiline) {
        $value = preg_replace('/[^\P{C}\n\r\t]/u', '', $value) ?? '';
        $value = preg_replace('/\R/u', "\n", $value) ?? '';
        return trim($value);
    }
    $value = preg_replace('/\p{C}+/u', ' ', $value) ?? '';
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

/**
 * Process a submitted form.
 * Returns ['errors' => [field => message], 'values' => [...], 'general' => ?string, 'sent' => ?reference].
 */
function handle_enquiry(string $type): array
{
    $fields = enquiry_fields($type);
    $state  = ['errors' => [], 'values' => [], 'general' => null, 'sent' => null];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        $state['sent'] = flash('enquiry_sent_' . $type);
        return $state;
    }

    $values = [];
    foreach ($fields as $name => [$label, $max, $required]) {
        $values[$name] = clean_input($_POST[$name] ?? '', $name === 'message');
    }
    $state['values'] = $values;

    // post_max_size exceeded: PHP discards the whole body.
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $state['general'] = 'That upload was too large. Please attach a smaller photo (max ' . format_bytes((int) config('uploads.max_bytes')) . ').';
        return $state;
    }
    if (!csrf_valid()) {
        $state['general'] = 'Your session has expired. Please send the form again.';
        return $state;
    }
    $here = url(current_path());
    // Bots: answer exactly like a success so they learn nothing, but store nothing.
    if (spam_trap_triggered($type)) {
        app_log('Enquiry blocked by spam trap from ' . client_ip());
        flash('enquiry_sent_' . $type, 'received');
        redirect($here . '#form');
    }

    $errors = [];
    foreach ($fields as $name => [$label, $max, $required]) {
        if ($required && $values[$name] === '') {
            $errors[$name] = $label . ' is required.';
        } elseif (mb_strlen($values[$name]) > $max) {
            $errors[$name] = $label . ' must be ' . $max . ' characters or fewer.';
        }
    }
    if (!isset($errors['email']) && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($values['phone'] !== '' && !isset($errors['phone']) && !preg_match('/^\+?[0-9][0-9 ()\-]{6,24}$/', $values['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number (digits, spaces, + and - only).';
    }
    if (($values['postcode'] ?? '') !== '' && postcode_state($values['postcode']) === null) {
        $errors['postcode'] = 'Please enter a valid Australian postcode.';
    }
    if ($type === 'quote' && !$errors) {
        $hasFile    = isset($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $hasDetails = $values['inner_diameter'] . $values['outer_diameter'] . $values['width'] . $values['machine'] . $values['message'] !== '';
        if (!$hasFile && !$hasDetails) {
            $errors['message'] = 'Please attach a photo or tell us something about the seal — a measurement, markings or the machine it came from.';
        }
    }

    // Only complete submissions count towards the per-IP limit.
    if (!$errors && !rate_limit_allow('enquiry:' . client_ip(), (int) config('security.rate_limit_max', 5), (int) config('security.rate_limit_window', 600))) {
        $state['general'] = 'We have received several messages from your connection in a short time. Please wait a few minutes and try again.';
        return $state;
    }

    $upload = ['file' => null, 'original' => null];
    if ($type === 'quote' && isset($_FILES['attachment'])) {
        $fileError = $_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($fileError !== UPLOAD_ERR_NO_FILE && !$errors) {
            $upload = handle_upload($_FILES['attachment'], (array) config('uploads.enquiry_types'), (int) config('uploads.max_bytes'), ST_STORAGE . '/uploads');
            if (!$upload['ok']) {
                $errors['attachment'] = $upload['error'];
            }
        } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
            $errors['attachment'] = 'Please choose your photo again after correcting the fields above.';
        }
    }

    if ($errors) {
        $state['errors'] = $errors;
        return $state;
    }

    $enquiry = $values + [
        'type'            => $type,
        'reference'       => 'Q' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
        'attachment_path' => $upload['file'],
        'attachment_name' => $upload['original'],
        'ip_address'      => client_ip(),
        'user_agent'      => mb_substr(clean_input($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at'      => db_now(),
    ];

    $stored = store_enquiry($enquiry);
    $mailed = send_enquiry_notification($enquiry);

    if (!$stored && !$mailed) {
        app_log('Enquiry ' . $enquiry['reference'] . ' could not be stored or emailed.');
        $state['general'] = 'Sorry, your message could not be saved because of a technical problem. Please try again shortly.';
        return $state;
    }

    unset($_SESSION['form_time'][$type]);
    flash('enquiry_sent_' . $type, $enquiry['reference']);
    redirect($here . '#form');
}

/** Save to the database; if that is not available, append to a private JSON-lines file. */
function store_enquiry(array $enquiry): bool
{
    $columns = [
        'reference', 'type', 'name', 'email', 'phone', 'postcode', 'seal_type', 'inner_diameter', 'outer_diameter', 'width',
        'quantity', 'machine', 'message', 'attachment_path', 'attachment_name', 'ip_address', 'user_agent', 'created_at',
    ];
    $row = [];
    foreach ($columns as $column) {
        $value        = $enquiry[$column] ?? null;
        $row[$column] = ($value === '' ? null : $value);
    }
    $row['name']  = (string) $enquiry['name'];
    $row['email'] = (string) $enquiry['email'];

    if (db_ready()) {
        try {
            db_run('INSERT INTO enquiries (' . implode(', ', $columns) . ", status) VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ", 'new')", array_values($row));
            return true;
        } catch (PDOException $ex) {
            app_log('store_enquiry(): ' . $ex->getMessage());
        }
    }

    $dir = ST_STORAGE . '/enquiries';
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
        return false;
    }
    $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $line !== false && @file_put_contents($dir . '/enquiries.jsonl', $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}

/** Human-readable field list used in the notification email and the admin panel. */
function enquiry_labels(): array
{
    return [
        'reference' => 'Reference', 'type' => 'Form', 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'postcode' => 'Postcode',
        'seal_type' => 'Seal type', 'inner_diameter' => 'Inner diameter (mm)', 'outer_diameter' => 'Outer diameter (mm)', 'width' => 'Width (mm)',
        'quantity' => 'Quantity', 'machine' => 'Machine / application', 'message' => 'Message',
        'attachment_name' => 'Photo', 'created_at' => 'Received',
    ];
}

/** Email the message to the address in config('mail.to'). Returns false if mail is not configured. */
function send_enquiry_notification(array $enquiry): bool
{
    $body = "A new message has been sent through the website.\n\n";
    foreach (enquiry_labels() as $key => $label) {
        $value = trim((string) ($enquiry[$key] ?? ''));
        if ($value !== '') {
            $body .= str_pad($label . ':', 26) . $value . "\n";
        }
    }
    if (!empty($enquiry['attachment_path'])) {
        $body .= "\nThe photo can be downloaded from the admin panel (Enquiries).\n";
    }
    $subject = '[' . $enquiry['reference'] . '] ' . ($enquiry['type'] === 'contact' ? 'Contact message' : 'Custom quote request') . ' from ' . $enquiry['name'];
    return send_mail((string) config('mail.to', ''), $subject, $body, $enquiry['email']);
}

/* ---------- Form rendering helpers ---------- */

/** Text / email / tel input with label and error message. */
function form_input(string $name, string $label, array $state, array $opt = []): string
{
    $id       = 'f-' . $name;
    $value    = $state['values'][$name] ?? ($opt['value'] ?? '');
    $error    = $state['errors'][$name] ?? null;
    $required = !empty($opt['required']);
    $attrs    = '';
    foreach (['type' => 'text', 'autocomplete' => null, 'inputmode' => null, 'placeholder' => null, 'maxlength' => null, 'list' => null, 'pattern' => null] as $attr => $default) {
        $v = $opt[$attr] ?? $default;
        if ($v !== null) {
            $attrs .= ' ' . $attr . '="' . e($v) . '"';
        }
    }
    return '<div class="field' . ($error ? ' has-error' : '') . (!empty($opt['full']) ? ' field--full' : '') . '">'
        . '<label for="' . $id . '">' . e($label) . ($required ? '<span class="req" aria-hidden="true">*</span>' : '') . '</label>'
        . '<input class="input" id="' . $id . '" name="' . e($name) . '" value="' . e($value) . '"' . $attrs
        . ($required ? ' required' : '') . ($error ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '') . '>'
        . ($error ? '<p class="field__error" id="' . $id . '-err">' . e($error) . '</p>' : '')
        . (!empty($opt['hint']) ? '<p class="field__hint">' . e($opt['hint']) . '</p>' : '')
        . '</div>';
}

/** $options: list of values, or [value => label]. */
function form_select(string $name, string $label, array $options, array $state, array $opt = []): string
{
    $id       = 'f-' . $name;
    $value    = (string) ($state['values'][$name] ?? ($opt['value'] ?? ''));
    $error    = $state['errors'][$name] ?? null;
    $required = !empty($opt['required']);
    $html     = '<div class="field' . ($error ? ' has-error' : '') . '">'
        . '<label for="' . $id . '">' . e($label) . ($required ? '<span class="req" aria-hidden="true">*</span>' : '') . '</label>'
        . '<select class="select" id="' . $id . '" name="' . e($name) . '"' . ($required ? ' required' : '')
        . (isset($opt['autocomplete']) ? ' autocomplete="' . e($opt['autocomplete']) . '"' : '')
        . ($error ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '') . '>'
        . '<option value="">' . e($opt['placeholder'] ?? 'Select…') . '</option>';
    foreach ($options as $key => $option) {
        $optionValue = is_int($key) ? (string) $option : (string) $key;
        $html .= '<option value="' . e($optionValue) . '"' . ($optionValue === $value ? ' selected' : '') . '>' . e($option) . '</option>';
    }
    return $html . '</select>' . ($error ? '<p class="field__error" id="' . $id . '-err">' . e($error) . '</p>' : '') . '</div>';
}

function form_textarea(string $name, string $label, array $state, array $opt = []): string
{
    $id       = 'f-' . $name;
    $value    = $state['values'][$name] ?? ($opt['value'] ?? '');
    $error    = $state['errors'][$name] ?? null;
    $required = !empty($opt['required']);
    return '<div class="field field--full' . ($error ? ' has-error' : '') . '">'
        . '<label for="' . $id . '">' . e($label) . ($required ? '<span class="req" aria-hidden="true">*</span>' : '') . '</label>'
        . '<textarea class="textarea" id="' . $id . '" name="' . e($name) . '" rows="' . (int) ($opt['rows'] ?? 5) . '" maxlength="3000"'
        . (!empty($opt['placeholder']) ? ' placeholder="' . e($opt['placeholder']) . '"' : '')
        . ($required ? ' required' : '') . ($error ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '') . '>' . e($value) . '</textarea>'
        . ($error ? '<p class="field__error" id="' . $id . '-err">' . e($error) . '</p>' : '')
        . '</div>';
}

/** Error summary / confirmation shown above a form. */
function form_alert(array $state, string $successText = 'Thanks — your message has been sent. We will get back to you soon.'): string
{
    if (!empty($state['sent'])) {
        $ref = $state['sent'] !== 'received' ? ' Reference: ' . e($state['sent']) . '.' : '';
        return '<div class="alert alert--success" role="status"><strong>' . e($successText) . '</strong>' . $ref . '</div>';
    }
    if (empty($state['general']) && empty($state['errors'])) {
        return '';
    }
    $html = '<div class="alert alert--error" role="alert">';
    if (!empty($state['general'])) {
        $html .= '<strong>' . e($state['general']) . '</strong>';
    } else {
        $html .= '<strong>Please check the highlighted fields:</strong><ul>';
        foreach ($state['errors'] as $message) {
            $html .= '<li>' . e($message) . '</li>';
        }
        $html .= '</ul>';
    }
    return $html . '</div>';
}
