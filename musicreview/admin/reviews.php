<?php
require "../koneksi.php";
harus_admin();
$halaman_aktif = "reviews";
$judul_halaman = "Moderate Reviews";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["aksi"] == "hapus_review") {
        simpan("DELETE FROM reviews WHERE id_review = ?", [(int)$_POST["id"]]);
        set_flash("success", "Review deleted (its comments were deleted too).");
    }
    if ($_POST["aksi"] == "hapus_comment") {
        simpan("DELETE FROM comments WHERE id_comment = ?", [(int)$_POST["id"]]);
        set_flash("success", "Comment deleted.");
    }
    header("Location: reviews.php?tab=" . urlencode($_POST["tab"]));
    exit;
}

$tab     = ($_GET["tab"] ?? "reviews") == "comments" ? "comments" : "reviews";
$cari    = trim($_GET["cari"] ?? "");
$hal     = max(1, (int)($_GET["hal"] ?? 1));
$per_hal = 8;
$mulai   = ($hal - 1) * $per_hal;
$like    = "%$cari%";

$jml_review  = ambil_satu("SELECT COUNT(*) AS n FROM reviews")["n"];
$jml_comment = ambil_satu("SELECT COUNT(*) AS n FROM comments")["n"];

if ($tab == "reviews") {
    $urutan = ["baru" => "rv.tanggal_review DESC", "lama" => "rv.tanggal_review ASC", "tinggi" => "nilai DESC", "rendah" => "nilai ASC"];
    $urut   = $_GET["urut"] ?? "baru";
    $order  = $urutan[$urut] ?? $urutan["baru"];

    $dari = "FROM reviews rv JOIN users u ON rv.id_user = u.id_user JOIN songs s ON rv.id_song = s.id_song
             WHERE (u.username LIKE ? OR s.judul_lagu LIKE ? OR rv.isi_review LIKE ?)";
    $total = ambil_satu("SELECT COUNT(*) AS n $dari", [$like, $like, $like])["n"];
    $data  = ambil_semua("SELECT rv.id_review, rv.isi_review, rv.tanggal_review, u.username, s.judul_lagu,
            (SELECT nilai_rating FROM ratings r WHERE r.id_user = rv.id_user AND r.id_song = rv.id_song) AS nilai
        $dari ORDER BY $order LIMIT $mulai, $per_hal", [$like, $like, $like]);
} else {
    $dari = "FROM comments c JOIN users u ON c.id_user = u.id_user JOIN reviews rv ON c.id_review = rv.id_review
             JOIN songs s ON rv.id_song = s.id_song
             WHERE (u.username LIKE ? OR s.judul_lagu LIKE ? OR c.isi_comment LIKE ?)";
    $total = ambil_satu("SELECT COUNT(*) AS n $dari", [$like, $like, $like])["n"];
    $data  = ambil_semua("SELECT c.id_comment, c.isi_comment, c.tanggal_comment, c.id_review, u.username, s.judul_lagu
        $dari ORDER BY c.tanggal_comment DESC LIMIT $mulai, $per_hal", [$like, $like, $like]);
}

include "header_admin.php";
?>

<div class="mb-4">
    <div class="teks-kecil">Dashboard / <strong>Reviews</strong></div>
    <h1 class="h2 fw-bold mb-0">Moderate Reviews</h1>
    <div class="teks-kecil">Delete reviews or comments that break the rules.</div>
</div>

<div class="kartu p-4">
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab == 'reviews' ? 'active' : '' ?>" href="reviews.php?tab=reviews">All Reviews (<?= $jml_review ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab == 'comments' ? 'active' : '' ?>" href="reviews.php?tab=comments">Comments (<?= $jml_comment ?>)</a></li>
    </ul>

    <form method="get" class="d-flex gap-2 mb-3">
        <input type="hidden" name="tab" value="<?= $tab ?>">
        <input type="text" name="cari" class="form-control" placeholder="Search username, song, or text..." value="<?= e($cari) ?>">
        <?php if ($tab == "reviews") { ?>
            <select name="urut" class="form-select" style="max-width:200px" onchange="this.form.submit()">
                <option value="baru" <?= $urut == "baru" ? "selected" : "" ?>>Newest First</option>
                <option value="lama" <?= $urut == "lama" ? "selected" : "" ?>>Oldest First</option>
                <option value="tinggi" <?= $urut == "tinggi" ? "selected" : "" ?>>Highest Rated</option>
                <option value="rendah" <?= $urut == "rendah" ? "selected" : "" ?>>Lowest Rated</option>
            </select>
        <?php } ?>
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </form>

    <div class="tabel-mr">
        <div class="table-responsive">
            <table class="table">
                <?php if ($tab == "reviews") { ?>
                    <thead><tr><th>ID</th><th>Username</th><th>Song Title</th><th>Rating</th><th>Review Text</th><th>Date</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($data as $d) { ?>
                            <tr>
                                <td class="teks-kecil">#<?= $d["id_review"] ?></td>
                                <td><?= e($d["username"]) ?></td>
                                <td><?= e($d["judul_lagu"]) ?></td>
                                <td class="text-nowrap"><?= $d["nilai"] ? bintang($d["nilai"]) : "-" ?></td>
                                <td style="max-width:320px"><?= e($d["isi_review"]) ?></td>
                                <td class="teks-kecil text-nowrap"><?= date("Y-m-d H:i", strtotime($d["tanggal_review"])) ?></td>
                                <td class="text-end">
                                    <form method="post" class="hapus">
                                        <input type="hidden" name="aksi" value="hapus_review"><input type="hidden" name="tab" value="reviews">
                                        <input type="hidden" name="id" value="<?= $d['id_review'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php } else { ?>
                    <thead><tr><th>ID</th><th>Username</th><th>Song Title</th><th>On Review</th><th>Comment</th><th>Date</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($data as $d) { ?>
                            <tr>
                                <td class="teks-kecil">#<?= $d["id_comment"] ?></td>
                                <td><?= e($d["username"]) ?></td>
                                <td><?= e($d["judul_lagu"]) ?></td>
                                <td class="teks-kecil">#<?= $d["id_review"] ?></td>
                                <td style="max-width:320px"><?= e($d["isi_comment"]) ?></td>
                                <td class="teks-kecil text-nowrap"><?= date("Y-m-d H:i", strtotime($d["tanggal_comment"])) ?></td>
                                <td class="text-end">
                                    <form method="post" class="hapus">
                                        <input type="hidden" name="aksi" value="hapus_comment"><input type="hidden" name="tab" value="comments">
                                        <input type="hidden" name="id" value="<?= $d['id_comment'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                <?php } ?>
                <?php if (!$data) { ?><tbody><tr><td colspan="7" class="text-center teks-kecil py-4">No records found.</td></tr></tbody><?php } ?>
            </table>
        </div>
        <div class="bawah"><span>Showing <?= $data ? $mulai + 1 : 0 ?> to <?= $mulai + count($data) ?> of <?= $total ?> records</span><?= paginasi($total, $per_hal, $hal) ?></div>
    </div>
</div>

<?php include "footer_admin.php"; ?>
