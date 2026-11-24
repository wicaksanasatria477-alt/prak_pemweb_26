<?php
// includes/header.php - navbar yang dipakai semua halaman publik
$halaman = basename($_SERVER["SCRIPT_NAME"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul_halaman ?? "MusicReview") ?> - MusicReview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top navbar-mr">
    <div class="container">
        <a class="navbar-brand fw-semibold text-primary" href="index.php">
            <i class="bi bi-disc"></i> MusicReview
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuUtama">
            <ul class="navbar-nav me-3">
                <li class="nav-item"><a class="nav-link <?= $halaman == 'index.php' ? 'active' : '' ?>" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= in_array($halaman, ['artists.php', 'detail_artist.php']) ? 'active' : '' ?>" href="artists.php">Artists</a></li>
                <li class="nav-item"><a class="nav-link <?= $halaman == 'profil.php' ? 'active' : '' ?>" href="profil.php">Favorites</a></li>
            </ul>

            <form class="d-flex flex-grow-1 mx-lg-3 my-2 my-lg-0" action="index.php" method="get">
                <div class="input-group">
                    <input type="text" name="cari" class="form-control" placeholder="Search albums, songs, artists..." value="<?= e($_GET['cari'] ?? '') ?>">
                    <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="d-flex align-items-center gap-2 ms-lg-3">
                <?php if (isset($_SESSION["id_user"])) { ?>
                    <div class="dropdown">
                        <a class="btn btn-light dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($_SESSION["username"]) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php">My Profile</a></li>
                            <?php if ($_SESSION["role"] == "admin") { ?>
                                <li><a class="dropdown-item" href="admin/index.php">Admin Panel</a></li>
                            <?php } ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <a class="btn btn-light" href="login.php">Login</a>
                    <a class="btn btn-primary" href="register.php">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<main class="container konten">
<?php tampil_flash(); ?>
