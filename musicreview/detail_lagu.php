<?php
require "koneksi.php";

$id = (int)($_GET["id"] ?? 0);
$lagu = ambil_satu("SELECT s.*, al.nama_album, al.cover_album, al.id_artist, ar.nama_artist, g.nama_genre
    FROM songs s
    JOIN albums al ON s.id_album = al.id_album
    JOIN artists ar ON al.id_artist = ar.id_artist
    JOIN genres g ON s.id_genre = g.id_genre
    WHERE s.id_song = ?", [$id]);
if (!$lagu) {
    set_flash("danger", "Song not found.");
    header("Location: index.php");
    exit;
}
$judul_halaman = $lagu["judul_lagu"];

/* ---------- proses form (review & komentar) ---------- */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    harus_login();
    $id_user = $_SESSION["id_user"];

    if ($_POST["aksi"] == "review") {
        $nilai = (int)$_POST["nilai_rating"];
        $isi   = trim($_POST["isi_review"]);

        if ($nilai < 1 || $nilai > 5) {
            set_flash("danger", "Please choose a rating from 1 to 5.");
        } elseif (strlen($isi) < 20 || strlen($isi) > 500) {
            set_flash("danger", "Review must be 20 - 500 characters.");
        } else {
            // 1 user hanya punya 1 rating per lagu -> kalau sudah ada, nilainya diperbarui
            simpan("INSERT INTO ratings (id_user, id_song, nilai_rating) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE nilai_rating = VALUES(nilai_rating), tanggal_rating = NOW()",
                   [$id_user, $id, $nilai]);
            simpan("INSERT INTO reviews (id_user, id_song, isi_review) VALUES (?, ?, ?)", [$id_user, $id, $isi]);
            set_flash("success", "Your review has been posted.");
        }
    }

    if ($_POST["aksi"] == "komentar") {
        $id_review = (int)$_POST["id_review"];
        $isi       = trim($_POST["isi_comment"]);
        if ($isi == "" || strlen($isi) > 300) {
            set_flash("danger", "Comment must be 1 - 300 characters.");
        } elseif (ambil_satu("SELECT id_review FROM reviews WHERE id_review = ? AND id_song = ?", [$id_review, $id])) {
            simpan("INSERT INTO comments (id_review, id_user, isi_comment) VALUES (?, ?, ?)", [$id_review, $id_user, $isi]);
            set_flash("success", "Comment added.");
        }
    }

    header("Location: detail_lagu.php?id=$id#review");
    exit;
}

/* ---------- ambil data ---------- */
$rating = ambil_satu("SELECT ROUND(AVG(nilai_rating), 1) AS rata, COUNT(*) AS jml FROM ratings WHERE id_song = ?", [$id]);

$urut = $_GET["urut"] ?? "baru";
$urutan = ["baru" => "rv.tanggal_review DESC", "lama" => "rv.tanggal_review ASC", "tinggi" => "nilai DESC, rv.tanggal_review DESC"];
$order = $urutan[$urut] ?? $urutan["baru"];

$reviews = ambil_semua("SELECT rv.*, u.username, u.role, u.foto_profil,
        (SELECT nilai_rating FROM ratings r WHERE r.id_user = rv.id_user AND r.id_song = rv.id_song) AS nilai
    FROM reviews rv JOIN users u ON rv.id_user = u.id_user
    WHERE rv.id_song = ? ORDER BY $order", [$id]);

$favorit = in_array($id, daftar_favorit_user());
$rating_saya = isset($_SESSION["id_user"])
    ? ambil_satu("SELECT nilai_rating FROM ratings WHERE id_user = ? AND id_song = ?", [$_SESSION["id_user"], $id])
    : null;

include "includes/header.php";
?>

<nav class="teks-kecil mb-3">
    <a href="index.php">Catalog</a> / <a href="detail_artist.php?id=<?= $lagu['id_artist'] ?>"><?= e($lagu["nama_artist"]) ?></a> / <?= e($lagu["judul_lagu"]) ?>
</nav>

<div class="kartu p-4 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-5 col-md-2">
            <div class="kotak-gambar rounded"><?= gambar("cover", $lagu["cover_album"], "music-note-beamed") ?></div>
        </div>
        <div class="col-md-10">
            <div class="mb-2">
                <span class="badge text-bg-primary"><?= e($lagu["nama_genre"]) ?></span>
                <span class="ms-2 teks-kecil">
                    <i class="bi bi-star-fill text-warning"></i>
                    <strong><?= $rating["jml"] > 0 ? $rating["rata"] : "-" ?></strong> / 5.0 (<?= $rating["jml"] ?> ratings)
                </span>
            </div>
            <h1 class="display-6 fw-bold"><?= e($lagu["judul_lagu"]) ?></h1>
            <div class="teks-kecil mb-3">
                Artist: <a href="detail_artist.php?id=<?= $lagu['id_artist'] ?>" class="fw-semibold"><?= e($lagu["nama_artist"]) ?></a> &bull;
                Album: <a href="detail_album.php?id=<?= $lagu['id_album'] ?>" class="fw-semibold"><?= e($lagu["nama_album"]) ?></a> &bull;
                <i class="bi bi-calendar"></i> <?= format_tanggal($lagu["tanggal_rilis"]) ?> &bull;
                <i class="bi bi-clock"></i> <?= format_durasi($lagu["durasi"]) ?>
            </div>
            <form method="post" action="favorit.php" class="d-inline">
                <input type="hidden" name="id_song" value="<?= $id ?>">
                <input type="hidden" name="kembali" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <button class="btn btn-primary"><i class="bi bi-heart<?= $favorit ? '-fill' : '' ?>"></i> <?= $favorit ? "Saved to Favorites" : "Save to Favorites" ?></button>
            </form>
            <a href="#form-review" class="btn btn-light"><i class="bi bi-chat-square-text"></i> Add Review</a>
            <a href="https://open.spotify.com/search/<?= rawurlencode($lagu['judul_lagu'] . ' ' . $lagu['nama_artist']) ?>" target="_blank" rel="noopener" class="btn btn-success"><i class="bi bi-spotify"></i> Listen on Spotify</a>
        </div>
    </div>
</div>

<div class="kartu p-4 mb-4" id="form-review">
    <div class="banner fw-semibold mb-3"><i class="bi bi-pencil-square"></i> Write a Review &amp; Rating</div>

    <?php if (isset($_SESSION["id_user"])) { ?>
        <form method="post">
            <input type="hidden" name="aksi" value="review">
            <div class="mb-3" style="max-width:380px">
                <label class="form-label">Overall Score <span class="text-danger">*</span></label>
                <select name="nilai_rating" class="form-select" required>
                    <option value="">Choose Rating...</option>
                    <?php for ($n = 5; $n >= 1; $n--) { ?>
                        <option value="<?= $n ?>" <?= ($rating_saya["nilai_rating"] ?? 0) == $n ? "selected" : "" ?>><?= $n ?> - <?= ["", "Poor", "Fair", "Good", "Very Good", "Excellent"][$n] ?></option>
                    <?php } ?>
                </select>
                <?php if ($rating_saya) { ?><div class="form-text">You already rated this song. Submitting again updates your rating.</div><?php } ?>
            </div>
            <div class="mb-1">
                <label class="form-label">Review <span class="text-danger">*</span> <span class="teks-kecil float-end">Minimum 20 characters</span></label>
                <textarea name="isi_review" id="isi_review" class="form-control" rows="4" minlength="20" maxlength="500" placeholder="Write your review here..." required></textarea>
            </div>
            <div class="teks-kecil mb-3"><span id="hitung">0</span> / 500</div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="teks-kecil"><i class="bi bi-shield-check"></i> Logged in as: <strong><?= e($_SESSION["username"]) ?></strong></span>
                <button class="btn btn-primary"><i class="bi bi-send"></i> Post Review</button>
            </div>
        </form>
    <?php } else { ?>
        <p class="mb-0">Please <a href="login.php">login</a> or <a href="register.php">register</a> to write a review.</p>
    <?php } ?>
</div>

<div class="kartu p-4 mb-4" id="review">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h2 class="h5 judul-seksi mb-0">Community Reviews (<?= count($reviews) ?>)</h2>
        <form method="get" class="d-flex align-items-center gap-2">
            <input type="hidden" name="id" value="<?= $id ?>">
            <label class="teks-kecil">Sort by:</label>
            <select name="urut" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="baru" <?= $urut == "baru" ? "selected" : "" ?>>Most Recent</option>
                <option value="lama" <?= $urut == "lama" ? "selected" : "" ?>>Oldest</option>
                <option value="tinggi" <?= $urut == "tinggi" ? "selected" : "" ?>>Highest Rating</option>
            </select>
        </form>
    </div>

    <?php foreach ($reviews as $rv) {
        $komentar = ambil_semua("SELECT c.*, u.username, u.role FROM comments c JOIN users u ON c.id_user = u.id_user
                                 WHERE c.id_review = ? ORDER BY c.tanggal_comment", [$rv["id_review"]]); ?>
        <div class="kotak-review">
            <div class="d-flex justify-content-between mb-2">
                <div class="d-flex gap-2 align-items-center">
                    <div class="avatar"><?= $rv["foto_profil"] ? gambar("profil", $rv["foto_profil"], "person") : strtoupper(substr($rv["username"], 0, 2)) ?></div>
                    <div>
                        <div class="fw-semibold"><?= e($rv["username"]) ?> <?= $rv["role"] == "admin" ? '<span class="badge text-bg-secondary">Admin</span>' : "" ?></div>
                        <div class="teks-kecil">Reviewed on <?= format_tanggal($rv["tanggal_review"]) ?></div>
                    </div>
                </div>
                <?php if ($rv["nilai"]) { ?><div><span class="badge badge-rating"><i class="bi bi-star-fill"></i> <?= $rv["nilai"] ?>/5</span></div><?php } ?>
            </div>
            <p><?= nl2br(e($rv["isi_review"])) ?></p>

            <?php foreach ($komentar as $k) { ?>
                <div class="kotak-komentar">
                    <strong class="text-primary"><?= e($k["username"]) ?></strong>
                    <?= $k["role"] == "admin" ? '<span class="badge text-bg-secondary">Admin</span>' : "" ?>
                    <span class="teks-kecil">&bull; <?= format_tanggal($k["tanggal_comment"]) ?></span><br>
                    <?= nl2br(e($k["isi_comment"])) ?>
                </div>
            <?php } ?>

            <?php if (isset($_SESSION["id_user"])) { ?>
                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="aksi" value="komentar">
                    <input type="hidden" name="id_review" value="<?= $rv['id_review'] ?>">
                    <input type="text" name="isi_comment" class="form-control form-control-sm" maxlength="300" placeholder="Write a comment..." required>
                    <button class="btn btn-sm btn-secondary"><i class="bi bi-reply"></i> Comment</button>
                </form>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!$reviews) { ?><p class="teks-kecil text-center py-3 mb-0">No reviews yet. Be the first to write one!</p><?php } ?>
</div>

<?php include "includes/footer.php"; ?>
