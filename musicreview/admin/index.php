<?php
require "../koneksi.php";
harus_admin();
$halaman_aktif = "songs";
$judul_halaman = "Manage Songs";

// mm:ss -> HH:MM:SS (format TIME di MySQL)
function ke_time($mmss) {
    if (!preg_match('/^(\d+):([0-5]\d)$/', trim($mmss), $m)) return null;
    $menit = (int)$m[1];
    return sprintf("%02d:%02d:%02d", intdiv($menit, 60), $menit % 60, $m[2]);
}

/* ---------- proses tambah / edit / hapus ---------- */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST["aksi"];

    if ($aksi == "hapus") {
        simpan("DELETE FROM songs WHERE id_song = ?", [(int)$_POST["id_song"]]);
        set_flash("success", "Song deleted.");
    } else {
        $judul  = trim($_POST["judul_lagu"]);
        $durasi = ke_time($_POST["durasi"]);
        $tgl    = ($_POST["tanggal_rilis"] ?? "") ?: null;
        $album  = (int)$_POST["id_album"];
        $genre  = (int)$_POST["id_genre"];

        if ($judul == "" || !$album || !$genre || $durasi === null) {
            set_flash("danger", "Please fill all fields. Duration format is mm:ss (example 3:45).");
        } elseif ($aksi == "tambah") {
            simpan("INSERT INTO songs (id_album, id_genre, judul_lagu, durasi, tanggal_rilis) VALUES (?, ?, ?, ?, ?)",
                   [$album, $genre, $judul, $durasi, $tgl]);
            set_flash("success", "Song added.");
        } else {
            simpan("UPDATE songs SET id_album = ?, id_genre = ?, judul_lagu = ?, durasi = ?, tanggal_rilis = ? WHERE id_song = ?",
                   [$album, $genre, $judul, $durasi, $tgl, (int)$_POST["id_song"]]);
            set_flash("success", "Song updated.");
        }
    }
    header("Location: index.php");
    exit;
}

/* ---------- ambil data ---------- */
$cari    = trim($_GET["cari"] ?? "");
$hal     = max(1, (int)($_GET["hal"] ?? 1));
$per_hal = 8;
$mulai   = ($hal - 1) * $per_hal;

$dari = "FROM songs s JOIN albums al ON s.id_album = al.id_album
         JOIN artists ar ON al.id_artist = ar.id_artist JOIN genres g ON s.id_genre = g.id_genre";
$where = "WHERE 1=1";
$params = [];
if ($cari != "") {
    $where .= " AND (s.judul_lagu LIKE ? OR al.nama_album LIKE ? OR ar.nama_artist LIKE ?)";
    $params = ["%$cari%", "%$cari%", "%$cari%"];
}
$total = ambil_satu("SELECT COUNT(*) AS n $dari $where", $params)["n"];
$lagu  = ambil_semua("SELECT s.*, al.nama_album, al.cover_album, ar.nama_artist, g.nama_genre
    $dari $where ORDER BY s.id_song DESC LIMIT $mulai, $per_hal", $params);

$daftar_album = ambil_semua("SELECT al.id_album, al.nama_album, ar.nama_artist FROM albums al JOIN artists ar ON al.id_artist = ar.id_artist ORDER BY al.nama_album");
$daftar_genre = ambil_semua("SELECT * FROM genres ORDER BY nama_genre");

$stat = [
    ["Total Songs",   ambil_satu("SELECT COUNT(*) AS n FROM songs")["n"],   "music-note-beamed"],
    ["Total Albums",  ambil_satu("SELECT COUNT(*) AS n FROM albums")["n"],  "disc"],
    ["Total Artists", ambil_satu("SELECT COUNT(*) AS n FROM artists")["n"], "person"],
    ["Total Users",   ambil_satu("SELECT COUNT(*) AS n FROM users")["n"],   "people"],
];

include "header_admin.php";
?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <div class="teks-kecil">Dashboard / <strong>Songs</strong></div>
        <h1 class="h2 fw-bold mb-0">Manage Songs</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" data-judul-modal="Add New Song" data-aksi="tambah"><i class="bi bi-plus-lg"></i> Add Song</button>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stat as $s) { ?>
        <div class="col-6 col-xl-3"><div class="stat"><div><div class="label"><?= $s[0] ?></div><div class="angka"><?= $s[1] ?></div></div><i class="bi bi-<?= $s[2] ?>"></i></div></div>
    <?php } ?>
</div>

<div class="kartu p-4">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h5 judul-seksi mb-0">All Songs Catalog</h2>
            <div class="teks-kecil">Browse and modify song entries in the database.</div>
        </div>
        <form method="get" class="d-flex gap-2">
            <input type="text" name="cari" class="form-control" placeholder="Search title, album, artist..." value="<?= e($cari) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="tabel-mr">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Cover</th><th>Song Title</th><th>Album</th><th>Genre</th><th>Duration</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($lagu as $l) { ?>
                        <tr>
                            <td class="teks-kecil">#<?= $l["id_song"] ?></td>
                            <td><div class="thumb"><?= gambar("cover", $l["cover_album"], "music-note-beamed", "../") ?></div></td>
                            <td><div class="fw-semibold"><?= e($l["judul_lagu"]) ?></div><div class="teks-kecil">Artist: <?= e($l["nama_artist"]) ?></div></td>
                            <td><?= e($l["nama_album"]) ?></td>
                            <td><span class="badge rounded-pill text-bg-light"><?= e($l["nama_genre"]) ?></span></td>
                            <td><?= format_durasi($l["durasi"]) ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#formModal"
                                    data-judul-modal="Edit Song - #<?= $l['id_song'] ?>" data-aksi="edit"
                                    data-id_song="<?= $l['id_song'] ?>" data-judul_lagu="<?= e($l['judul_lagu']) ?>"
                                    data-id_album="<?= $l['id_album'] ?>" data-id_genre="<?= $l['id_genre'] ?>"
                                    data-durasi="<?= format_durasi($l['durasi']) ?>" data-tanggal_rilis="<?= e($l['tanggal_rilis']) ?>">Edit</button>
                                <form method="post" class="d-inline hapus">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_song" value="<?= $l['id_song'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$lagu) { ?><tr><td colspan="7" class="text-center teks-kecil py-4">No songs found.</td></tr><?php } ?>
                </tbody>
            </table>
        </div>
        <div class="bawah"><span>Showing <?= $lagu ? $mulai + 1 : 0 ?> to <?= $mulai + count($lagu) ?> of <?= $total ?> songs</span><?= paginasi($total, $per_hal, $hal) ?></div>
    </div>
</div>

<!-- Modal tambah / edit -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Song</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="id_song" value="">
                <div class="mb-3"><label class="form-label">Song Title *</label><input type="text" name="judul_lagu" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label">Album *</label>
                    <select name="id_album" class="form-select" required>
                        <option value="">-- Select Album --</option>
                        <?php foreach ($daftar_album as $a) { ?><option value="<?= $a['id_album'] ?>"><?= e($a["nama_album"]) ?> - <?= e($a["nama_artist"]) ?></option><?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Genre *</label>
                    <select name="id_genre" class="form-select" required>
                        <option value="">-- Select Genre --</option>
                        <?php foreach ($daftar_genre as $g) { ?><option value="<?= $g['id_genre'] ?>"><?= e($g["nama_genre"]) ?></option><?php } ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label class="form-label">Duration (mm:ss) *</label><input type="text" name="durasi" class="form-control" placeholder="3:45" pattern="\d+:[0-5]\d" required></div>
                    <div class="col-6 mb-3"><label class="form-label">Release Date</label><input type="date" name="tanggal_rilis" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Song</button></div>
        </form>
    </div>
</div>

<?php include "footer_admin.php"; ?>
