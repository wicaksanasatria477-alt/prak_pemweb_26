<?php
require "../koneksi.php";
harus_admin();
$halaman_aktif = "genres";
$judul_halaman = "Manage Genres";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST["aksi"];

    if ($aksi == "hapus") {
        $id = (int)$_POST["id_genre"];
        $dipakai = ambil_satu("SELECT COUNT(*) AS n FROM songs WHERE id_genre = ?", [$id])["n"];
        if ($dipakai > 0) {
            // di database id_genre pada songs dibuat RESTRICT, jadi genre yang dipakai tidak boleh dihapus
            set_flash("danger", "Genre cannot be deleted because it is used by $dipakai song(s).");
        } else {
            simpan("DELETE FROM genres WHERE id_genre = ?", [$id]);
            set_flash("success", "Genre deleted.");
        }
    } else {
        $nama = trim($_POST["nama_genre"]);
        $desk = trim($_POST["deskripsi"]);

        if ($nama == "") {
            set_flash("danger", "Genre name is required.");
        } elseif ($aksi == "tambah") {
            if (simpan("INSERT INTO genres (nama_genre, deskripsi) VALUES (?, ?)", [$nama, $desk])) {
                set_flash("success", "Genre added.");
            } else {
                set_flash("danger", "Genre name already exists.");
            }
        } else {
            if (simpan("UPDATE genres SET nama_genre = ?, deskripsi = ? WHERE id_genre = ?", [$nama, $desk, (int)$_POST["id_genre"]])) {
                set_flash("success", "Genre updated.");
            } else {
                set_flash("danger", "Genre name already exists.");
            }
        }
    }
    header("Location: genres.php");
    exit;
}

$genres = ambil_semua("SELECT g.*, (SELECT COUNT(*) FROM songs s WHERE s.id_genre = g.id_genre) AS jml_lagu FROM genres g ORDER BY g.nama_genre");

include "header_admin.php";
?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <div class="teks-kecil">Dashboard / <strong>Genres</strong></div>
        <h1 class="h2 fw-bold mb-0">Manage Genres</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" data-judul-modal="Add New Genre" data-aksi="tambah"><i class="bi bi-plus-lg"></i> Add Genre</button>
</div>

<div class="kartu p-4">
    <div class="tabel-mr">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Genre</th><th>Description</th><th>Songs</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($genres as $g) { ?>
                        <tr>
                            <td class="teks-kecil">#<?= $g["id_genre"] ?></td>
                            <td class="fw-semibold"><?= e($g["nama_genre"]) ?></td>
                            <td class="teks-kecil"><?= e($g["deskripsi"]) ?></td>
                            <td><?= $g["jml_lagu"] ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#formModal"
                                    data-judul-modal="Edit Genre - #<?= $g['id_genre'] ?>" data-aksi="edit"
                                    data-id_genre="<?= $g['id_genre'] ?>" data-nama_genre="<?= e($g['nama_genre']) ?>"
                                    data-deskripsi="<?= e($g['deskripsi']) ?>">Edit</button>
                                <form method="post" class="d-inline hapus">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_genre" value="<?= $g['id_genre'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (!$genres) { ?><tr><td colspan="5" class="text-center teks-kecil py-4">No genres yet.</td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Genre</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="id_genre" value="">
                <div class="mb-3"><label class="form-label">Genre Name *</label><input type="text" name="nama_genre" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Genre</button></div>
        </form>
    </div>
</div>

<?php include "footer_admin.php"; ?>
