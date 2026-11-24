<?php
require "koneksi.php";
$judul_halaman = "Artists";

$cari   = trim($_GET["cari_artist"] ?? "");
$negara = trim($_GET["negara"] ?? "");

$daftar_negara = ambil_semua("SELECT DISTINCT negara FROM artists WHERE negara IS NOT NULL AND negara <> '' ORDER BY negara");

$where = "WHERE 1=1";
$params = [];
if ($cari != "") {
    $where .= " AND (nama_artist LIKE ? OR bio LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}
if ($negara != "") {
    $where .= " AND negara = ?";
    $params[] = $negara;
}
$artists = ambil_semua("SELECT * FROM artists $where ORDER BY nama_artist", $params);

include "includes/header.php";
?>

<form method="get" class="row g-2 mb-4">
    <div class="col-md-3">
        <select name="negara" class="form-select">
            <option value="">All Countries</option>
            <?php foreach ($daftar_negara as $n) { ?>
                <option value="<?= e($n['negara']) ?>" <?= $negara == $n["negara"] ? "selected" : "" ?>><?= e($n["negara"]) ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-7">
        <input type="text" name="cari_artist" class="form-control" placeholder="Search artist name..." value="<?= e($cari) ?>">
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button></div>
</form>

<div class="d-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="h3 judul-seksi mb-0">Artists Directory</h1>
        <div class="teks-kecil">Repository of cataloged musicians, composers, and recording artists.</div>
    </div>
    <div class="teks-kecil">Showing <strong><?= count($artists) ?></strong> Artists</div>
</div>

<?php if ($artists) { ?>
<div class="row g-4">
    <?php foreach ($artists as $a) { ?>
        <div class="col-md-6 col-lg-3">
            <div class="kartu kartu-hover h-100 overflow-hidden d-flex flex-column">
                <div class="kotak-gambar"><?= gambar("artist", $a["foto_artist"], "person") ?></div>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <div class="fw-semibold"><?= e($a["nama_artist"]) ?></div>
                    <div class="teks-kecil mb-2"><i class="bi bi-geo-alt"></i> <?= e($a["negara"] ?: "-") ?></div>
                    <p class="teks-kecil flex-grow-1"><?= e(potong($a["bio"] ?? "", 110)) ?></p>
                    <a href="detail_artist.php?id=<?= $a['id_artist'] ?>" class="btn btn-outline-primary btn-sm">View Profile</a>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<?php } else { ?>
    <div class="kartu text-center p-5">
        <i class="bi bi-person-x fs-1 text-secondary"></i>
        <h5 class="mt-2">No artists matched your criteria</h5>
        <p class="teks-kecil">Try adjusting your search or resetting the country filter.</p>
        <a href="artists.php" class="btn btn-primary">Reset Filters</a>
    </div>
<?php } ?>

<?php include "includes/footer.php"; ?>
