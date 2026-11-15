<?php
require "koneksi.php";

$id = (int)($_GET["id"] ?? 0);
$artist = ambil_satu("SELECT * FROM artists WHERE id_artist = ?", [$id]);
if (!$artist) {
    set_flash("danger", "Artist not found.");
    header("Location: artists.php");
    exit;
}
$judul_halaman = $artist["nama_artist"];

$albums = ambil_semua("SELECT al.*, (SELECT COUNT(*) FROM songs s WHERE s.id_album = al.id_album) AS jml_lagu
    FROM albums al WHERE al.id_artist = ? ORDER BY al.tanggal_rilis DESC", [$id]);

$total_lagu = ambil_satu("SELECT COUNT(*) AS n FROM songs s JOIN albums al ON s.id_album = al.id_album WHERE al.id_artist = ?", [$id])["n"];
$genres = ambil_semua("SELECT DISTINCT g.nama_genre FROM songs s
    JOIN albums al ON s.id_album = al.id_album JOIN genres g ON s.id_genre = g.id_genre
    WHERE al.id_artist = ?", [$id]);
$tahun = ambil_satu("SELECT MIN(YEAR(tanggal_rilis)) AS awal, MAX(YEAR(tanggal_rilis)) AS akhir FROM albums WHERE id_artist = ?", [$id]);

include "includes/header.php";
?>

<nav class="teks-kecil mb-3">
    <a href="index.php">Home</a> / <a href="artists.php">Artists</a> / <?= e($artist["nama_artist"]) ?>
</nav>

<div class="kartu p-4 mb-4">
    <div class="row g-4">
        <div class="col-md-3 col-lg-2">
            <div class="kotak-gambar rounded"><?= gambar("artist", $artist["foto_artist"], "person") ?></div>
        </div>
        <div class="col-md-9 col-lg-10">
            <h1 class="h2 judul-seksi"><?= e($artist["nama_artist"]) ?></h1>
            <div class="mb-2 teks-kecil">
                <i class="bi bi-globe"></i> <?= e($artist["negara"] ?: "-") ?>
                <?php foreach ($genres as $g) { ?> &bull; <span class="badge text-bg-light"><?= e($g["nama_genre"]) ?></span><?php } ?>
            </div>
            <p><?= nl2br(e($artist["bio"] ?: "No biography yet.")) ?></p>
            <div class="row g-3 teks-kecil">
                <div class="col-auto"><i class="bi bi-disc"></i> Total Albums: <strong><?= count($albums) ?></strong></div>
                <div class="col-auto"><i class="bi bi-music-note-list"></i> Total Songs: <strong><?= $total_lagu ?></strong></div>
                <?php if ($tahun["awal"]) { ?>
                    <div class="col-auto"><i class="bi bi-calendar-event"></i> Albums released: <strong><?= $tahun["awal"] ?><?= $tahun["akhir"] != $tahun["awal"] ? " - " . $tahun["akhir"] : "" ?></strong></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="h4 judul-seksi mb-0"><i class="bi bi-collection"></i> Albums by this Artist</h2>
    <span class="teks-kecil">Displaying <?= count($albums) ?> Records</span>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($albums as $a) { ?>
        <div class="col-md-6 col-lg-3">
            <div class="kartu kartu-hover h-100 overflow-hidden d-flex flex-column">
                <div class="kotak-gambar"><?= gambar("cover", $a["cover_album"], "disc") ?></div>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <div class="fw-semibold"><?= e($a["nama_album"]) ?></div>
                    <div class="teks-kecil">Release Year: <?= $a["tanggal_rilis"] ? date("Y", strtotime($a["tanggal_rilis"])) : "-" ?></div>
                    <div class="teks-kecil mb-3">Tracks: <?= $a["jml_lagu"] ?> songs</div>
                    <a href="detail_album.php?id=<?= $a['id_album'] ?>" class="btn btn-primary btn-sm mt-auto">View Album <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php if (!$albums) { ?><div class="col-12 teks-kecil">This artist has no albums yet.</div><?php } ?>
</div>

<a href="artists.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to Artists</a>

<?php include "includes/footer.php"; ?>
