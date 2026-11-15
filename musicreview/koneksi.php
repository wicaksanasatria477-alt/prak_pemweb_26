<?php
// koneksi.php
// Koneksi database + fungsi-fungsi bantu yang dipakai semua halaman
session_start();
mysqli_report(MYSQLI_REPORT_OFF);

$host     = "localhost";
$user     = "root";
$pass     = "";
$nama_db  = "musicreview";

$koneksi = mysqli_connect($host, $user, $pass, $nama_db);
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($koneksi, "utf8mb4");

/* ---------- Fungsi query (pakai prepared statement) ---------- */

// jalankan query, kembalikan statement (atau false kalau gagal)
function jalankan($sql, $params = []) {
    global $koneksi;
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) return false;
    if ($params) {
        mysqli_stmt_bind_param($stmt, str_repeat("s", count($params)), ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) return false;
    return $stmt;
}

// ambil banyak baris (array of array)
function ambil_semua($sql, $params = []) {
    $stmt = jalankan($sql, $params);
    if (!$stmt) return [];
    $hasil = mysqli_stmt_get_result($stmt);
    return $hasil ? mysqli_fetch_all($hasil, MYSQLI_ASSOC) : [];
}

// ambil satu baris saja (atau null)
function ambil_satu($sql, $params = []) {
    $data = ambil_semua($sql, $params);
    return $data ? $data[0] : null;
}

// untuk INSERT / UPDATE / DELETE, true kalau berhasil
function simpan($sql, $params = []) {
    return jalankan($sql, $params) ? true : false;
}

/* ---------- Fungsi tampilan ---------- */

// amankan teks sebelum ditampilkan (cegah XSS)
function e($teks) {
    return htmlspecialchars($teks ?? "", ENT_QUOTES, "UTF-8");
}

// 00:04:34 -> 4:34
function format_durasi($waktu) {
    if (!$waktu) return "-";
    list($j, $m, $d) = explode(":", $waktu);
    return ((int)$j * 60 + (int)$m) . ":" . $d;
}

// potong teks panjang, contoh: potong("abcdefgh", 5) -> "abcde..."
function potong($teks, $panjang) {
    $teks = $teks ?? "";
    if (function_exists("mb_strimwidth")) {
        return mb_strimwidth($teks, 0, $panjang, "...");
    }
    return strlen($teks) > $panjang ? substr($teks, 0, $panjang) . "..." : $teks;
}

function format_tanggal($tgl) {
    return $tgl ? date("d M Y", strtotime($tgl)) : "-";
}

// tampilkan gambar dari folder uploads, kalau kosong tampilkan kotak ikon
function gambar($folder, $file, $ikon = "music-note-beamed", $base = "") {
    if ($file && file_exists(__DIR__ . "/uploads/$folder/$file")) {
        return '<img src="' . $base . 'uploads/' . $folder . '/' . e($file) . '" alt="">';
    }
    return '<div class="img-kosong"><i class="bi bi-' . $ikon . '"></i></div>';
}

// bintang rating, contoh: 4 -> ★★★★☆
function bintang($nilai) {
    $out = "";
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="bi bi-star' . ($i <= $nilai ? '-fill' : '') . ' text-warning"></i>';
    }
    return $out;
}

// tombol love (favorit) untuk satu lagu
function tombol_favorit($id_song, $sudah_favorit, $base = "") {
    $ikon = $sudah_favorit ? "heart-fill" : "heart";
    return '<form method="post" action="' . $base . 'favorit.php" class="d-inline">'
         . '<input type="hidden" name="id_song" value="' . (int)$id_song . '">'
         . '<input type="hidden" name="kembali" value="' . e($_SERVER["REQUEST_URI"]) . '">'
         . '<button class="btn btn-sm btn-light text-danger" title="Favorite"><i class="bi bi-' . $ikon . '"></i></button>'
         . '</form>';
}

// id lagu yang sudah difavoritkan user yang sedang login
function daftar_favorit_user() {
    $hasil = [];
    if (isset($_SESSION["id_user"])) {
        foreach (ambil_semua("SELECT id_song FROM favorites WHERE id_user = ?", [$_SESSION["id_user"]]) as $f) {
            $hasil[] = $f["id_song"];
        }
    }
    return $hasil;
}

/* ---------- Pesan (flash message lewat session) ---------- */

function set_flash($tipe, $pesan) {
    $_SESSION["flash"] = ["tipe" => $tipe, "pesan" => $pesan];
}

function tampil_flash() {
    if (isset($_SESSION["flash"])) {
        $f = $_SESSION["flash"];
        echo '<div class="alert alert-' . $f["tipe"] . ' alert-dismissible fade show" role="alert">'
           . e($f["pesan"])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION["flash"]);
    }
}

/* ---------- Cek login ---------- */

function harus_login($base = "") {
    if (!isset($_SESSION["id_user"])) {
        set_flash("warning", "Please login first.");
        header("Location: " . $base . "login.php");
        exit;
    }
}

function harus_admin() {
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        set_flash("danger", "Admin access only.");
        header("Location: ../login.php");
        exit;
    }
}

/* ---------- Upload gambar ---------- */
// return: nama file baru, "" kalau tidak ada file, false kalau file tidak valid
function upload_gambar($field, $folder) {
    if (!isset($_FILES[$field]) || $_FILES[$field]["error"] === UPLOAD_ERR_NO_FILE) return "";
    if ($_FILES[$field]["error"] !== UPLOAD_ERR_OK) return false;

    $ext = strtolower(pathinfo($_FILES[$field]["name"], PATHINFO_EXTENSION));
    if (!in_array($ext, ["jpg", "jpeg", "png", "webp"])) return false;
    if ($_FILES[$field]["size"] > 2 * 1024 * 1024) return false;
    if (getimagesize($_FILES[$field]["tmp_name"]) === false) return false;

    $dir = __DIR__ . "/uploads/$folder/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $nama_baru = time() . "_" . rand(100, 999) . "." . $ext;
    move_uploaded_file($_FILES[$field]["tmp_name"], $dir . $nama_baru);
    return $nama_baru;
}

/* ---------- Pagination ---------- */
function paginasi($total, $per_halaman, $hal) {
    $jml_hal = (int)ceil($total / $per_halaman);
    if ($jml_hal <= 1) return "";

    $link = function ($p, $label, $nonaktif = false, $aktif = false) {
        $q = $_GET;
        $q["hal"] = $p;
        $kelas = "page-item" . ($nonaktif ? " disabled" : "") . ($aktif ? " active" : "");
        return '<li class="' . $kelas . '"><a class="page-link" href="?' . e(http_build_query($q)) . '">' . $label . '</a></li>';
    };

    $out = '<ul class="pagination pagination-sm mb-0">';
    $out .= $link($hal - 1, "Previous", $hal <= 1);
    for ($i = 1; $i <= $jml_hal; $i++) {
        $out .= $link($i, $i, false, $i == $hal);
    }
    $out .= $link($hal + 1, "Next", $hal >= $jml_hal);
    return $out . "</ul>";
}
