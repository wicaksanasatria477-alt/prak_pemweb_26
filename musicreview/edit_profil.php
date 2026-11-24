<?php
// edit_profil.php - ubah username, email, foto profil, dan password
require "koneksi.php";
harus_login();
$judul_halaman = "Edit Profile";

$id_user = $_SESSION["id_user"];
$user = ambil_satu("SELECT * FROM users WHERE id_user = ?", [$id_user]);

if (isset($_POST["simpan"])) {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $pass_lama = $_POST["password_lama"];
    $pass_baru = $_POST["password_baru"];
    $foto      = upload_gambar("foto_profil", "profil");

    if ($username == "" || $email == "") {
        set_flash("danger", "Username and email are required.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash("danger", "Email format is not valid.");
    } elseif (ambil_satu("SELECT id_user FROM users WHERE (username = ? OR email = ?) AND id_user != ?", [$username, $email, $id_user])) {
        // cek duplikat, tapi akun sendiri tidak dihitung (id_user != ?)
        set_flash("danger", "Username or email is already used by another account.");
    } elseif ($foto === false) {
        set_flash("danger", "Profile photo must be a JPG/PNG/WEBP image, max 2MB.");
    } elseif ($pass_baru != "" && strlen($pass_baru) < 6) {
        set_flash("danger", "New password must be at least 6 characters.");
    } elseif ($pass_baru != "" && !password_verify($pass_lama, $user["password"])) {
        set_flash("danger", "Current password is wrong.");
    } else {
        simpan("UPDATE users SET username = ?, email = ? WHERE id_user = ?", [$username, $email, $id_user]);

        if ($pass_baru != "") {
            simpan("UPDATE users SET password = ? WHERE id_user = ?", [password_hash($pass_baru, PASSWORD_DEFAULT), $id_user]);
        }
        if ($foto != "") {
            simpan("UPDATE users SET foto_profil = ? WHERE id_user = ?", [$foto, $id_user]);
            // hapus foto lama dari folder uploads/profil
            if ($user["foto_profil"] && file_exists(__DIR__ . "/uploads/profil/" . basename($user["foto_profil"]))) {
                unlink(__DIR__ . "/uploads/profil/" . basename($user["foto_profil"]));
            }
        }

        $_SESSION["username"] = $username;   // supaya nama di navbar ikut berubah
        set_flash("success", "Profile updated.");
        header("Location: profil.php");
        exit;
    }
    // kalau gagal, isi form tetap memakai yang barusan diketik
    $user["username"] = $username;
    $user["email"]    = $email;
}

include "includes/header.php";
?>

<div class="kartu kotak-auth">
    <h1 class="h4 judul-seksi mb-3"><i class="bi bi-pencil-square"></i> Edit Profile</h1>

    <form method="post" enctype="multipart/form-data">
        <div class="text-center mb-3">
            <div class="avatar avatar-besar mx-auto"><?= $user["foto_profil"] ? gambar("profil", $user["foto_profil"], "person") : strtoupper(substr($user["username"], 0, 2)) ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= e($user['username']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="foto_profil" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Leave empty to keep the current photo. JPG/PNG/WEBP, max 2MB.</div>
        </div>

        <hr>
        <div class="teks-kecil mb-2">Change password (leave empty to keep the current one)</div>
        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="password_lama" class="form-control" autocomplete="current-password">
        </div>
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password_baru" class="form-control" minlength="6" autocomplete="new-password">
        </div>

        <div class="d-flex gap-2">
            <a href="profil.php" class="btn btn-light w-50">Cancel</a>
            <button type="submit" name="simpan" class="btn btn-primary w-50">Save Changes</button>
        </div>
    </form>
</div>

<?php include "includes/footer.php"; ?>
