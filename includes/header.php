<?php
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'QIP';
$page_body_class = $page_body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> — QIP</title>
<script>document.documentElement.classList.add('js');</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link rel="stylesheet" href="<?= e(url('assets/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
<?php if (!empty($page_css)): foreach ((array)$page_css as $css): ?>
  <link rel="stylesheet" href="<?= e(url('assets/css/' . $css)) ?>">
<?php endforeach; endif; ?>
</head>
<body class="<?= e($page_body_class) ?>">