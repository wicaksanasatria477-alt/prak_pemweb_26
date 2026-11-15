<?php
require "../koneksi.php";
harus_admin();
$halaman_aktif = "artists";
$judul_halaman = "Manage Artists";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST["aksi"];

    if ($aksi == "hapus") {
        simpan("DELETE FROM artists WHERE id_artist = ?", [(int)$_POST["id_artist"]]);
        set_flash("success", "Artist deleted (its albums and songs were deleted too).");
    } else {
        $nama   = trim($_POST["nama_artist"]);
        $negara = trim($_POST["negara"]);
        $bio    = trim($_POST["bio"]);
        $foto   = upload_gambar("foto_artist", "artist");

        if ($nama == "") {
            set_flash("danger", "Artist name is required.");
        } elseif ($foto === false) {
            set_flash("danger", "Photo must be a JPG/PNG/WEBP image, max 2MB.");
        } elseif ($aksi == "tambah") {
            simpan("INSERT INTO artists (nama_artist, bio, negara, foto_artist) VALUES (?, ?, ?, ?)", [$nama, $bio, $negara, $foto ?: null]);
            set_flash("success", "Artist added.");
        } else {
            $id = (int)$_POST["id_artist"];
            simpan("UPDATE artists SET nama_artist = ?, bio = ?, negara = ? WHERE id_artist = ?", [$nama, $bio, $negara, $id]);
            if ($foto != "") {
                simpan("UPDATE artists SET foto_artist = ? WHERE id_artist = ?", [$foto, $id]);
            }
            set_flash("success", "Artist updated.");
        }
    }
    header("Location: artists.php");
    exit;
}

$cari    = trim($_GET["cari"] ?? "");
$hal     = max(1, (int)($_GET["hal"] ?? 1));
$per_hal = 8;
$mulai   = ($hal - 1) * $per_hal;

$where = "WHERE 1=1";
$params = [];
if ($cari != "") {
    $where .= " AND (nama_artist LIKE ? OR negara LIKE ?)";
    $params = ["%$cari%", "%$cari%"];
}
$total   = ambil_satu("SELECT COUNT(*) AS n FROM artists $where", $params)["n"];
$artists = ambil_semua("SELECT ar.*, (SELECT COUNT(*) FROM albums al WHERE al.id_artist = ar.id_artist) AS jml_album
    FROM artists ar $where ORDER BY ar.id_artist DESC LIMIT $mulai, $per_hal", $params);

include "header_admin.php";
?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <div class="teks-kecil">Dashboard / <strong>Artists</strong></div>
        <h1 class="h2 fw-bold mb-0">Manage Artists</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" data-judul-modal="Add New Artist" data-aksi="tambah"><i class="bi bi-plus-lg"></i> Add Artist</button>
</div>

<div class="kartu p-4">
    <form method="get" class="d-flex gap-2 mb-3 ms-auto" style="max-width:380px">
        <input type="text" name="cari" class="form-control" placeholder="Search name or country..." value="<?= e($cari) ?>">
        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
    </form>

    <div class="tabel-mr">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Photo</th><th>Artist</th><th>Country</th><th>Albums</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($artists as $a) { ?>
                        <tr>
                            <td class="teks-kecil">#<?= $a["id_artist"] ?></td>
                            <td><div class="thumb"><?= gambar("artist", $a["foto_artist"], "person", "../") ?></div></td>
                            <td><div class="fw-semibold"><?= e($a["nama_artist"]) ?></div><div class="teks-kecil"><?= e(potong($a["bio"] ?? "", 70)) ?></div></td>
                            <td><?= e($a["negara"]) ?></td>
                            <td><?= $a["jml_album"] ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#formModal"
                                    data-judul-modal="Edit Artist - #<?= $a['id_artist'] ?>" data-aksi="edit"
                                    data-id_artist="<?= $a['id_artist'] ?>" data-nama_artist="<?= e($a['nama_artist']) ?>"
                                    data-negara="<?= e($a['negara']) ?>" data-bio="<?= e($a['bio']) ?>">Edit</button>
                                <form method="post" class="d-inline hapus">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_artist" value="<?= $a['id_artist'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$artists) { ?><tr><td colspan="6" class="text-center teks-kecil py-4">No artists found.</td></tr><?php } ?>
                </tbody>
            </table>
        </div>
        <div class="bawah"><span>Showing <?= $artists ? $mulai + 1 : 0 ?> to <?= $mulai + count($artists) ?> of <?= $total ?> artists</span><?= paginasi($total, $per_hal, $hal) ?></div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Artist</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="id_artist" value="">
                <div class="mb-3"><label class="form-label">Artist Name *</label><input type="text" name="nama_artist" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Country</label><input type="text" name="negara" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Biography</label><textarea name="bio" class="form-control" rows="4"></textarea></div>
                <div class="mb-3"><label class="form-label">Artist Photo</label><input type="file" name="foto_artist" class="form-control" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">JPG/PNG/WEBP, max 2MB. Leave empty to keep the current photo.</div></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Artist</button></div>
        </form>
    </div>
</div>

<?php include "footer_admin.php"; ?>
