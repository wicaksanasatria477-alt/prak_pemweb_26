<?php
require "../koneksi.php";
harus_admin();
$halaman_aktif = "albums";
$judul_halaman = "Manage Albums";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST["aksi"];

    if ($aksi == "hapus") {
        simpan("DELETE FROM albums WHERE id_album = ?", [(int)$_POST["id_album"]]);
        set_flash("success", "Album deleted (its songs were deleted too).");
    } else {
        $nama   = trim($_POST["nama_album"]);
        $artist = (int)$_POST["id_artist"];
        $tgl    = ($_POST["tanggal_rilis"] ?? "") ?: null;
        $desk   = trim($_POST["deskripsi"]);
        $cover  = upload_gambar("cover_album", "cover");

        if ($nama == "" || !$artist) {
            set_flash("danger", "Album name and artist are required.");
        } elseif ($cover === false) {
            set_flash("danger", "Cover must be a JPG/PNG/WEBP image, max 2MB.");
        } elseif ($aksi == "tambah") {
            simpan("INSERT INTO albums (id_artist, nama_album, tanggal_rilis, cover_album, deskripsi) VALUES (?, ?, ?, ?, ?)",
                   [$artist, $nama, $tgl, $cover ?: null, $desk]);
            set_flash("success", "Album added.");
        } else {
            $id = (int)$_POST["id_album"];
            simpan("UPDATE albums SET id_artist = ?, nama_album = ?, tanggal_rilis = ?, deskripsi = ? WHERE id_album = ?", [$artist, $nama, $tgl, $desk, $id]);
            if ($cover != "") {   // cover diganti hanya kalau ada file baru
                simpan("UPDATE albums SET cover_album = ? WHERE id_album = ?", [$cover, $id]);
            }
            set_flash("success", "Album updated.");
        }
    }
    header("Location: albums.php");
    exit;
}

$cari    = trim($_GET["cari"] ?? "");
$hal     = max(1, (int)($_GET["hal"] ?? 1));
$per_hal = 8;
$mulai   = ($hal - 1) * $per_hal;

$where = "WHERE 1=1";
$params = [];
if ($cari != "") {
    $where .= " AND (al.nama_album LIKE ? OR ar.nama_artist LIKE ?)";
    $params = ["%$cari%", "%$cari%"];
}
$dari  = "FROM albums al JOIN artists ar ON al.id_artist = ar.id_artist";
$total = ambil_satu("SELECT COUNT(*) AS n $dari $where", $params)["n"];
$albums = ambil_semua("SELECT al.*, ar.nama_artist, (SELECT COUNT(*) FROM songs s WHERE s.id_album = al.id_album) AS jml_lagu
    $dari $where ORDER BY al.id_album DESC LIMIT $mulai, $per_hal", $params);
$daftar_artist = ambil_semua("SELECT id_artist, nama_artist FROM artists ORDER BY nama_artist");

include "header_admin.php";
?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <div class="teks-kecil">Dashboard / <strong>Albums</strong></div>
        <h1 class="h2 fw-bold mb-0">Manage Albums</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" data-judul-modal="Add New Album" data-aksi="tambah"><i class="bi bi-plus-lg"></i> Add Album</button>
</div>

<div class="kartu p-4">
    <form method="get" class="d-flex gap-2 mb-3 ms-auto" style="max-width:380px">
        <input type="text" name="cari" class="form-control" placeholder="Search album or artist..." value="<?= e($cari) ?>">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </form>

    <div class="tabel-mr">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Cover</th><th>Album</th><th>Artist</th><th>Release Date</th><th>Tracks</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($albums as $a) { ?>
                        <tr>
                            <td class="teks-kecil">#<?= $a["id_album"] ?></td>
                            <td><div class="thumb"><?= gambar("cover", $a["cover_album"], "disc", "../") ?></div></td>
                            <td><div class="fw-semibold"><?= e($a["nama_album"]) ?></div><div class="teks-kecil"><?= e(potong($a["deskripsi"] ?? "", 60)) ?></div></td>
                            <td><?= e($a["nama_artist"]) ?></td>
                            <td><?= format_tanggal($a["tanggal_rilis"]) ?></td>
                            <td><?= $a["jml_lagu"] ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#formModal"
                                    data-judul-modal="Edit Album - #<?= $a['id_album'] ?>" data-aksi="edit"
                                    data-id_album="<?= $a['id_album'] ?>" data-nama_album="<?= e($a['nama_album']) ?>"
                                    data-id_artist="<?= $a['id_artist'] ?>" data-tanggal_rilis="<?= e($a['tanggal_rilis']) ?>"
                                    data-deskripsi="<?= e($a['deskripsi']) ?>">Edit</button>
                                <form method="post" class="d-inline hapus">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_album" value="<?= $a['id_album'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$albums) { ?><tr><td colspan="7" class="text-center teks-kecil py-4">No albums found.</td></tr><?php } ?>
                </tbody>
            </table>
        </div>
        <div class="bawah"><span>Showing <?= $albums ? $mulai + 1 : 0 ?> to <?= $mulai + count($albums) ?> of <?= $total ?> albums</span><?= paginasi($total, $per_hal, $hal) ?></div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Album</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="id_album" value="">
                <div class="mb-3"><label class="form-label">Album Name *</label><input type="text" name="nama_album" class="form-control" required></div>
                <div class="mb-3">
                    <label class="form-label">Artist *</label>
                    <select name="id_artist" class="form-select" required>
                        <option value="">-- Select Artist --</option>
                        <?php foreach ($daftar_artist as $ar) { ?><option value="<?= $ar['id_artist'] ?>"><?= e($ar["nama_artist"]) ?></option><?php } ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Release Date</label><input type="date" name="tanggal_rilis" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
                <div class="mb-3"><label class="form-label">Album Cover</label><input type="file" name="cover_album" class="form-control" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">JPG/PNG/WEBP, max 2MB. Leave empty to keep the current cover.</div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Album</button></div>
        </form>
    </div>
</div>

<?php include "footer_admin.php"; ?>
