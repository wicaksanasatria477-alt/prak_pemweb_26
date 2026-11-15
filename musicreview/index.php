<?php
require "koneksi.php";
$judul_halaman = "Home";

$cari     = trim($_GET["cari"] ?? "");
$id_genre = (int)($_GET["genre"] ?? 0);
$hal      = max(1, (int)($_GET["hal"] ?? 1));
$per_hal  = 8;

$genres = ambil_semua("SELECT * FROM genres ORDER BY nama_genre");

// 4 album terbaru
$albums = ambil_semua("SELECT al.*, ar.nama_artist,
        (SELECT COUNT(*) FROM songs s WHERE s.id_album = al.id_album) AS jml_lagu
    FROM albums al JOIN artists ar ON al.id_artist = ar.id_artist
    ORDER BY al.tanggal_rilis DESC, al.id_album DESC LIMIT 4");

// daftar lagu (bisa dicari dan difilter genre)
$dari  = "FROM songs s
          JOIN albums al ON s.id_album = al.id_album
          JOIN artists ar ON al.id_artist = ar.id_artist
          JOIN genres g ON s.id_genre = g.id_genre";
$where  = "WHERE 1=1";
$params = [];
if ($cari != "") {
    $where .= " AND (s.judul_lagu LIKE ? OR ar.nama_artist LIKE ? OR al.nama_album LIKE ?)";
    $like = "%" . $cari . "%";
    $params = [$like, $like, $like];
}
if ($id_genre > 0) {
    $where .= " AND s.id_genre = ?";
    $params[] = $id_genre;
}

$total = ambil_satu("SELECT COUNT(*) AS n $dari $where", $params)["n"];
$mulai = ($hal - 1) * $per_hal;
$lagu  = ambil_semua("SELECT s.id_song, s.judul_lagu, s.durasi, ar.nama_artist, al.nama_album, g.nama_genre,
        (SELECT ROUND(AVG(nilai_rating), 1) FROM ratings r WHERE r.id_song = s.id_song) AS rata,
        (SELECT COUNT(*) FROM ratings r WHERE r.id_song = s.id_song) AS jml_rating
    $dari $where ORDER BY s.id_song DESC LIMIT $mulai, $per_hal", $params);

$favorit = daftar_favorit_user();

// link filter genre (tetap membawa kata pencarian)
function link_genre($id, $cari) {
    $q = [];
    if ($cari != "") $q["cari"] = $cari;
    if ($id > 0) $q["genre"] = $id;
    return "index.php" . ($q ? "?" . http_build_query($q) : "");
}

include "includes/header.php";
?>

<div class="banner d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary text-white rounded p-2"><i class="bi bi-mortarboard fs-5"></i></div>
        <div>
            <div class="fw-semibold">Music Catalog &amp; Review System</div>
            <div class="teks-kecil">Browse albums, rate songs, and write your own reviews.</div>
        </div>
    </div>
    <div class="teks-kecil"><i class="bi bi-circle-fill text-primary" style="font-size:8px"></i> PHP Engine Active &bull; MySQL Connected</div>
</div>

<div class="d-flex justify-content-between flex-wrap gap-2 mb-4">
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= link_genre(0, $cari) ?>" class="pill <?= $id_genre == 0 ? 'aktif' : '' ?>">All</a>
        <?php foreach ($genres as $g) { ?>
            <a href="<?= link_genre($g['id_genre'], $cari) ?>" class="pill <?= $id_genre == $g['id_genre'] ? 'aktif' : '' ?>"><?= e($g["nama_genre"]) ?></a>
        <?php } ?>
    </div>
    <div class="teks-kecil">Showing <strong><?= count($albums) ?></strong> featured albums &bull; <strong><?= $total ?></strong> indexed tracks</div>
</div>

<h2 class="h4 judul-seksi mb-0">Featured Albums</h2>
<p class="teks-kecil mb-3">Latest albums in the catalog</p>

<div class="row g-4 mb-5">
    <?php foreach ($albums as $a) { ?>
        <div class="col-6 col-lg-3">
            <div class="kartu kartu-hover h-100 d-flex flex-column overflow-hidden">
                <div class="kotak-gambar">
                    <?= gambar("cover", $a["cover_album"], "disc") ?>
                    <?php if ($a["tanggal_rilis"]) { ?><span class="tag-tahun"><?= date("Y", strtotime($a["tanggal_rilis"])) ?></span><?php } ?>
                </div>
                <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                    <div>
                        <div class="fw-semibold text-truncate"><?= e($a["nama_album"]) ?></div>
                        <div class="teks-kecil text-truncate"><?= e($a["nama_artist"]) ?></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="teks-kecil"><?= $a["jml_lagu"] ?> Tracks</span>
                        <a href="detail_album.php?id=<?= $a['id_album'] ?>" class="btn btn-primary btn-sm">See Songs <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php if (!$albums) { ?><div class="col-12 text-center teks-kecil py-4">No albums yet.</div><?php } ?>
</div>

<h2 class="h4 judul-seksi mb-0">All Songs</h2>
<p class="teks-kecil mb-3">
    <?php if ($cari != "") { ?>Search results for "<strong><?= e($cari) ?></strong>" <a href="index.php">(reset)</a><?php } else { ?>Song list with artist, album, genre and average rating<?php } ?>
</p>

<div class="tabel-mr mb-5">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th class="text-center" style="width:50px">#</th>
                    <th>Song Title</th><th>Artist</th><th>Album</th><th>Genre</th><th>Duration</th><th>Rating</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lagu as $i => $l) { ?>
                    <tr>
                        <td class="text-center teks-kecil"><?= $mulai + $i + 1 ?></td>
                        <td class="fw-semibold"><?= e($l["judul_lagu"]) ?></td>
                        <td class="teks-kecil"><?= e($l["nama_artist"]) ?></td>
                        <td class="teks-kecil"><?= e($l["nama_album"]) ?></td>
                        <td><span class="badge rounded-pill text-bg-light"><?= e($l["nama_genre"]) ?></span></td>
                        <td class="teks-kecil"><?= format_durasi($l["durasi"]) ?></td>
                        <td>
                            <?php if ($l["jml_rating"] > 0) { ?>
                                <i class="bi bi-star-fill text-warning"></i> <strong><?= $l["rata"] ?></strong>
                                <span class="teks-kecil">(<?= $l["jml_rating"] ?>)</span>
                            <?php } else { ?><span class="teks-kecil">No ratings</span><?php } ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="detail_lagu.php?id=<?= $l['id_song'] ?>" class="btn btn-sm btn-light text-primary"><i class="bi bi-eye"></i> Detail</a>
                            <?= tombol_favorit($l["id_song"], in_array($l["id_song"], $favorit)) ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if (!$lagu) { ?><tr><td colspan="8" class="text-center teks-kecil py-4">No songs found.</td></tr><?php } ?>
            </tbody>
        </table>
    </div>
    <div class="bawah">
        <span>Displaying <?= $lagu ? $mulai + 1 : 0 ?> to <?= $mulai + count($lagu) ?> of <?= $total ?> songs</span>
        <?= paginasi($total, $per_hal, $hal) ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>
