<?php
require_once __DIR__ . '/auth.php';
require_login();

$page_title      = $page_title      ?? 'Dashboard';
$page_body_class = $page_body_class ?? 'app-body';
$page_css        = array_merge(['app.css'], (array)($page_css ?? []));

include __DIR__ . '/header.php';
?>
<a class="skip" href="#main-content">Skip to content</a>

<div class="app">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="app__main">
    <?php include __DIR__ . '/topbar.php'; ?>
    <main class="page" id="main-content">