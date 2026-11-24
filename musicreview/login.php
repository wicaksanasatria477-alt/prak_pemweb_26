<?php
require "koneksi.php";
$judul_halaman = "Login";

// sudah login -> langsung ke home
if (isset($_SESSION["id_user"])) {
    header("Location: index.php");
    exit;
}

// email diingat lewat cookie (fitur "Remember me")
$email_cookie = $_COOKIE["email_ingat"] ?? "";

if (isset($_POST["login"])) {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    $user = ambil_satu("SELECT * FROM users WHERE email = ?", [$email]);

    if ($user && password_verify($password, $user["password"])) {
        // simpan data user ke session
        $_SESSION["id_user"]  = $user["id_user"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"]     = $user["role"];

        // cookie remember me (7 hari)
        if (isset($_POST["ingat"])) {
            setcookie("email_ingat", $email, time() + 60 * 60 * 24 * 7, "/");
        } else {
            setcookie("email_ingat", "", time() - 3600, "/");
        }

        // admin ke panel admin, user biasa ke home
        if ($user["role"] == "admin") {
            header("Location: admin/index.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        set_flash("danger", "Wrong email or password.");
        $email_cookie = $email;
    }
}

include "includes/header.php";
?>

<div class="kartu kotak-auth">
    <div class="text-center mb-4">
        <div class="text-primary fs-3 fw-semibold"><i class="bi bi-disc"></i> MusicReview</div>
        <div class="teks-kecil">Sign in to your account</div>
    </div>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($email_cookie) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="ingat" id="ingat" <?= $email_cookie ? "checked" : "" ?>>
            <label class="form-check-label" for="ingat">Remember me</label>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
    </form>

    <p class="text-center teks-kecil mt-3 mb-0">Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php include "includes/footer.php"; ?>
