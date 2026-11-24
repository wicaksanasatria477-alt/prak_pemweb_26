<?php
// favorit.php - tambah / hapus lagu dari favorit (tombol love)
require "koneksi.php";
harus_login();

$id_user = $_SESSION["id_user"];
$id_song = (int)($_POST["id_song"] ?? 0);
$kembali = $_POST["kembali"] ?? "index.php";
if (preg_match('#^(https?:)?//#i', $kembali)) {
    $kembali = "index.php";   // cegah redirect ke website lain
}

if ($id_song > 0 && ambil_satu("SELECT id_song FROM songs WHERE id_song = ?", [$id_song])) {
    $ada = ambil_satu("SELECT id_favorite FROM favorites WHERE id_user = ? AND id_song = ?", [$id_user, $id_song]);
    if ($ada) {
        simpan("DELETE FROM favorites WHERE id_favorite = ?", [$ada["id_favorite"]]);
        set_flash("secondary", "Song removed from favorites.");
    } else {
        simpan("INSERT INTO favorites (id_user, id_song) VALUES (?, ?)", [$id_user, $id_song]);
        set_flash("success", "Song added to favorites.");
    }
}

header("Location: " . $kembali);
exit;
