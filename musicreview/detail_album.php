<?php
require "koneksi.php";

$id = (int)($_GET["id"] ?? 0);
$album = ambil_satu("SELECT al.*, ar.nama_artist FROM albums al JOIN artists ar ON al.id_artist = ar.id_artist WHERE al.id_album = ?", [$id]);
if (!$album) {
    set_flash("danger", "Album not found.");
    header("Location: index.php");
    exit;
}
$judul_halaman = $album["nama_album"];

$lagu = ambil_semua("SELECT s.*, g.nama_genre,
        (SELECT ROUND(AVG(nilai_rating), 1) FROM ratings r WHERE r.id_song = s.id_song) AS rata,
        (SELECT COUNT(*) FROM ratings r WHERE r.id_song = s.id_song) AS jml_rating
    FROM songs s JOIN genres g ON s.id_genre = g.id_genre
    WHERE s.id_album = ? ORDER BY s.id_song", [$id]);

// rating dan durasi seluruh album
$ringkas = ambil_satu("SELECT ROUND(AVG(r.nilai_rating), 1) AS rata, COUNT(r.id_rating) AS jml
    FROM ratings r JOIN songs s ON r.id_song = s.id_song WHERE s.id_album = ?", [$id]);
$detik = ambil_satu("SELECT COALESCE(SUM(TIME_TO_SEC(durasi)), 0) AS d FROM songs WHERE id_album = ?", [$id])["d"];

$favorit = daftar_favorit_user();

include "includes/header.php";
?>

<nav class="teks-kecil mb-3">
    <a href="index.php">Home</a> / <a href="detail_artist.php?id=<?= $album['id_artist'] ?>"><?= e($album["nama_artist"]) ?></a> / <?= e($album["nama_album"]) ?>
</nav>

<div class="kartu p-4 mb-4">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="kotak-gambar rounded"><?= gambar("cover", $album["cover_album"], "disc") ?></div>
        </div>
        <div class="col-md-9">
            <span class="badge text-bg-primary mb-2">Album</span>
            <h1 class="h2 judul-seksi"><?= e($album["nama_album"]) ?></h1>
            <div class="teks-kecil mb-3">
                <i class="bi bi-person"></i> Artist: <a href="detail_artist.php?id=<?= $album['id_artist'] ?>"><?= e($album["nama_artist"]) ?></a>
                &bull; <i class="bi bi-calendar"></i> Release Date: <?= format_tanggal($album["tanggal_rilis"]) ?>
            </div>
            <p><?= nl2br(e($album["deskripsi"] ?: "No description yet.")) ?></p>
            <div class="d-flex flex-wrap gap-4 teks-kecil">
                <span><i class="bi bi-star-fill text-warning"></i> <strong><?= $ringkas["jml"] > 0 ? $ringkas["rata"] : "-" ?></strong> / 5.0 (<?= $ringkas["jml"] ?> ratings)</span>
                <span><i class="bi bi-stopwatch"></i> Total Duration: <strong><?= floor($detik / 60) ?> min <?= $detik % 60 ?> sec</strong></span>
                <span><i class="bi bi-list-ol"></i> Track Count: <strong><?= count($lagu) ?> Tracks</strong></span>
            </div>
        </div>
    </div>
</div>

<h2 class="h4 judul-seksi mb-3">Songs in this Album</h2>
<div class="tabel-mr mb-4">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr><th class="text-center" style="width:50px">#</th><th>Song Title</th><th>Genre</th><th>Duration</th><th>Average Rating</th><th class="text-center">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($lagu as $i => $l) { ?>
                    <tr>
                        <td class="text-center teks-kecil"><?= str_pad($i + 1, 2, "0", STR_PAD_LEFT) ?></td>
                        <td class="fw-semibold"><a href="detail_lagu.php?id=<?= $l['id_song'] ?>" class="text-decoration-none text-reset"><?= e($l["judul_lagu"]) ?></a></td>
                        <td><span class="badge rounded-pill text-bg-light"><?= e($l["nama_genre"]) ?></span></td>
                        <td class="teks-kecil"><?= format_durasi($l["durasi"]) ?></td>
                        <td>
                            <?php if ($l["jml_rating"] > 0) { ?>
                                <i class="bi bi-star-fill text-warning"></i> <strong><?= $l["rata"] ?></strong> <span class="teks-kecil">(<?= $l["jml_rating"] ?>)</span>
                            <?php } else { ?><span class="teks-kecil">-</span><?php } ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="detail_lagu.php?id=<?= $l['id_song'] ?>" class="btn btn-sm btn-light text-primary">Review</a>
                            <?= tombol_favorit($l["id_song"], in_array($l["id_song"], $favorit)) ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$lagu) { ?><tr><td colspan="6" class="text-center teks-kecil py-4">No songs in this album yet.</td></tr><?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "includes/footer.php"; ?>
