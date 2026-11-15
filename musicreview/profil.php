<?php
require "koneksi.php";
harus_login();
$judul_halaman = "My Profile";

$id_user = $_SESSION["id_user"];
$user = ambil_satu("SELECT * FROM users WHERE id_user = ?", [$id_user]);

$jml_review   = ambil_satu("SELECT COUNT(*) AS n FROM reviews WHERE id_user = ?", [$id_user])["n"];
$jml_favorit  = ambil_satu("SELECT COUNT(*) AS n FROM favorites WHERE id_user = ?", [$id_user])["n"];

$hal     = max(1, (int)($_GET["hal"] ?? 1));
$per_hal = 5;
$mulai   = ($hal - 1) * $per_hal;

$favorit = ambil_semua("SELECT f.tanggal_favorite, s.id_song, s.judul_lagu, al.nama_album, ar.nama_artist
    FROM favorites f
    JOIN songs s ON f.id_song = s.id_song
    JOIN albums al ON s.id_album = al.id_album
    JOIN artists ar ON al.id_artist = ar.id_artist
    WHERE f.id_user = ? ORDER BY f.tanggal_favorite DESC LIMIT $mulai, $per_hal", [$id_user]);

include "includes/header.php";
?>

<div class="kartu p-4 mb-4 d-flex flex-column flex-md-row gap-4 align-items-md-center">
    <div class="avatar avatar-besar"><?= $user["foto_profil"] ? gambar("profil", $user["foto_profil"], "person") : strtoupper(substr($user["username"], 0, 2)) ?></div>
    <div class="flex-grow-1">
        <h1 class="h3 judul-seksi mb-1">
            <?= e($user["username"]) ?>
            <span class="badge text-bg-primary fs-6"><i class="bi bi-patch-check"></i> <?= $user["role"] == "admin" ? "Administrator" : "Registered Reviewer" ?></span>
        </h1>
        <div class="teks-kecil"><i class="bi bi-envelope"></i> <?= e($user["email"]) ?></div>
        <div class="teks-kecil"><i class="bi bi-calendar-check"></i> Member since: <?= format_tanggal($user["created_at"]) ?></div>
    </div>
    <div class="d-flex gap-3">
        <div class="banner text-center px-4"><div class="fs-3 fw-semibold"><?= $jml_review ?></div><div class="teks-kecil">Reviews Written</div></div>
        <div class="banner text-center px-4"><div class="fs-3 fw-semibold"><?= $jml_favorit ?></div><div class="teks-kecil">Favorited Songs</div></div>
    </div>
</div>

<h2 class="h4 judul-seksi mb-0"><i class="bi bi-bookmark"></i> My Favorite Songs</h2>
<p class="teks-kecil mb-3">Showing <?= count($favorit) ?> of <?= $jml_favorit ?> saved songs</p>

<div class="tabel-mr mb-4">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th class="text-center" style="width:50px">#</th><th>Song Title</th><th>Artist</th><th>Album</th><th>Date Saved</th><th class="text-center">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($favorit as $i => $f) { ?>
                    <tr>
                        <td class="text-center teks-kecil"><?= $mulai + $i + 1 ?></td>
                        <td class="fw-semibold"><a href="detail_lagu.php?id=<?= $f['id_song'] ?>" class="text-decoration-none text-reset"><?= e($f["judul_lagu"]) ?></a></td>
                        <td class="teks-kecil"><?= e($f["nama_artist"]) ?></td>
                        <td class="teks-kecil"><?= e($f["nama_album"]) ?></td>
                        <td class="teks-kecil"><?= format_tanggal($f["tanggal_favorite"]) ?></td>
                        <td class="text-center">
                            <form method="post" action="favorit.php" class="hapus">
                                <input type="hidden" name="id_song" value="<?= $f['id_song'] ?>">
                                <input type="hidden" name="kembali" value="profil.php">
                                <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i> Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$favorit) { ?><tr><td colspan="6" class="text-center teks-kecil py-4">No favorite songs yet. Click the heart icon on any song!</td></tr><?php } ?>
            </tbody>
        </table>
    </div>
    <div class="bawah"><span>Table: favorites</span><?= paginasi($jml_favorit, $per_hal, $hal) ?></div>
</div>

<?php include "includes/footer.php"; ?>
