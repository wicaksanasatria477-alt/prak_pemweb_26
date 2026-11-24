<?php
require "koneksi.php";
$judul_halaman = "Register";

if (isset($_SESSION["id_user"])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($username == "" || $email == "" || strlen($password) < 6) {
        set_flash("danger", "Please fill all fields. Password must be at least 6 characters.");
    } elseif (ambil_satu("SELECT id_user FROM users WHERE username = ? OR email = ?", [$username, $email])) {
        set_flash("danger", "Username or email is already registered.");
    } else {
        $foto = upload_gambar("foto_profil", "profil");
        if ($foto === false) {
            set_flash("danger", "Profile photo must be a JPG/PNG/WEBP image, max 2MB.");
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // role tidak diisi -> otomatis 'user'
            simpan("INSERT INTO users (username, email, password, foto_profil) VALUES (?, ?, ?, ?)",
                   [$username, $email, $hash, $foto ?: null]);
            set_flash("success", "Registration successful! Please login.");
            header("Location: login.php");
            exit;
        }
    }
}

include "includes/header.php";
?>

<div class="kartu kotak-auth">
    <div class="text-center mb-4">
        <div class="text-primary fs-3 fw-semibold"><i class="bi bi-disc"></i> MusicReview</div>
        <div class="teks-kecil">Create a new reviewer account</div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= e($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="foto_profil" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Optional. JPG or PNG, max 2MB.</div>
        </div>
        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
    </form>

    <p class="text-center teks-kecil mt-3 mb-0">Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php include "includes/footer.php"; ?>
