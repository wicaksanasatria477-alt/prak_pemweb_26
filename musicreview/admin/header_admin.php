<?php
// admin/header_admin.php - sidebar + topbar halaman admin
// variabel yang diisi tiap halaman: $halaman_aktif, $judul_halaman
$menu = [
    "songs"   => ["index.php",   "music-note-beamed", "Songs"],
    "albums"  => ["albums.php",  "disc",              "Albums"],
    "artists" => ["artists.php", "person",            "Artists"],
    "genres"  => ["genres.php",  "tags",              "Genres"],
    "reviews" => ["reviews.php", "chat-square-text",  "Reviews"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - <?= e($judul_halaman) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<aside class="sidebar-admin">
    <div class="text-primary fs-5 fw-semibold px-2"><i class="bi bi-shield-lock"></i> Admin Panel</div>
    <div class="menu-label">CATALOG MANAGEMENT</div>
    <?php foreach ($menu as $kode => $m) { ?>
        <a class="item <?= $halaman_aktif == $kode ? 'aktif' : '' ?>" href="<?= $m[0] ?>"><i class="bi bi-<?= $m[1] ?>"></i> <?= $m[2] ?></a>
    <?php } ?>
    <div class="mt-auto">
        <a class="item text-danger" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
    </div>
</aside>

<div class="area-admin">
    <div class="topbar-admin">
        <div><strong class="text-primary">Admin MusicReview</strong> <span class="badge text-bg-light">CMS</span></div>
        <div class="d-flex align-items-center gap-2">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm">Live Site</a>
            <span class="teks-kecil"><i class="bi bi-person-circle"></i> <?= e($_SESSION["username"]) ?></span>
        </div>
    </div>
    <div class="isi-admin">
        <?php tampil_flash(); ?>
