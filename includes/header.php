<?php
/**
 * includes/header.php
 * Page header: DOCTYPE, head, CDN links, sidebar, topbar opening
 * Variables required before include:
 *   $pageTitle   = 'Page Name'
 *   $activePage  = 'key'
 *   $pageSubtitle = '' (optional)
 */
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/db.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> | <?= APP_NAME ?></title>

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏢</text></svg>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar -->
    <?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <div>
                <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
                <?php if (!empty($pageSubtitle)): ?>
                <div class="topbar-subtitle"><?= e($pageSubtitle) ?></div>
                <?php endif; ?>
            </div>
            <div class="topbar-actions">
                <span class="topbar-time"><i class="fa-regular fa-clock me-1"></i><span id="topbarClock"></span></span>
                <span class="topbar-badge">
                    <i class="fa-solid fa-user-shield me-1"></i>
                    <?= ucfirst(currentUser()['role']) ?>
                </span>
            </div>
        </header>

        <!-- Page Body -->
        <div class="page-body">
            <?= getFlash() ?>
