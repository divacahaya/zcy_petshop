<?php
session_start();
require_once "config/database.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Cek input kosong
    if ($name == "" || $email == "" || $password == "" || $confirm == "") {

        $error = "Semua data wajib diisi!";

    // Cek password
    } elseif ($password != $confirm) {

        $error = "Konfirmasi password tidak sama!";

    // Minimal password
    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter!";

    } else {

        $email_safe = mysqli_real_escape_string($conn, $email);
        $name_safe  = mysqli_real_escape_string($conn, $name);

        // Cek email sudah terdaftar atau belum
        $check = mysqli_query(
            $conn,
            "SELECT id_user FROM users WHERE email = '$email_safe'"
        );

        if (!$check) {

            $error = "Database error: " . mysqli_error($conn);

        } elseif (mysqli_num_rows($check) > 0) {

            $error = "Email sudah terdaftar!";

        } else {

            /*
             * Password disimpan menggunakan hash
             * supaya lebih aman.
             */
            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare($conn, "
                INSERT INTO users
                (name, email, password)
                VALUES (?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $name_safe,
                $email_safe,
                $password_hash
            );

            if (mysqli_stmt_execute($stmt)) {

                $success = "Registrasi berhasil! Silakan login.";

            } else {

                $error = "Registrasi gagal: " .
                         mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Register - ZCY Petshop</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {

    min-height: 100vh;

    background: #ffffff;

    display: flex;

    justify-content: center;

    align-items: center;
}

.register-container {

    width: 380px;

    padding: 30px;
}


/* LOGO */

.logo {

    text-align: center;

    margin-bottom: 25px;
}

.logo-icon {

    width: 85px;

    height: 85px;

    margin: auto;

    background: #ffb52e;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 45px;
}

.logo h1 {

    margin-top: 12px;

    color: #673de6;

    font-size: 23px;
}

.logo p {

    margin-top: 5px;

    color: #888;

    font-size: 13px;
}


/* MESSAGE */

.message {

    padding: 11px;

    border-radius: 8px;

    margin-bottom: 15px;

    text-align: center;

    font-size: 13px;
}

.error {

    background: #ffe5e5;

    color: #d60000;
}

.success {

    background: #e5f8ea;

    color: #18863b;
}


/* FORM */

.form-group {

    margin-bottom: 15px;
}

.form-group label {

    display: block;

    margin-bottom: 7px;

    font-size: 13px;

    color: #555;
}

.form-group input {

    width: 100%;

    padding: 13px 15px;

    border: 1px solid #ddd;

    border-radius: 8px;

    outline: none;

    font-size: 13px;
}

.form-group input:focus {

    border-color: #7548e8;
}


/* BUTTON */

.register-btn {

    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 8px;

    background: #7548e8;

    color: white;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;
}

.register-btn:hover {

    background: #6136d2;
}


/* LOGIN */

.login {

    text-align: center;

    margin-top: 20px;

    color: #777;

    font-size: 13px;
}

.login a {

    color: #7548e8;

    font-weight: bold;

    text-decoration: none;
}

</style>

</head>

<body>


<div class="register-container">


    <!-- LOGO -->

    <div class="logo">

        <div class="logo-icon">
            🐱
        </div>

        <h1>
            ZCY Petshop
        </h1>

        <p>
            Create your account
        </p>

    </div>


    <!-- ERROR -->

    <?php if ($error != ""): ?>

        <div class="message error">

            <?= htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- SUCCESS -->

    <?php if ($success != ""): ?>

        <div class="message success">

            <?= htmlspecialchars($success); ?>

            <br><br>

            <a href="login.php">
                Login sekarang
            </a>

        </div>

    <?php endif; ?>


    <!-- FORM -->

    <form method="POST">


        <div class="form-group">

            <label>
                Nama
            </label>

            <input
                type="text"
                name="name"
                placeholder="Masukkan nama"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                placeholder="Masukkan email"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Minimal 6 karakter"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Konfirmasi Password
            </label>

            <input
                type="password"
                name="confirm_password"
                placeholder="Ulangi password"
                required
            >

        </div>


        <button
            type="submit"
            name="register"
            class="register-btn"
        >
            DAFTAR
        </button>


    </form>


    <div class="login">

        Sudah punya akun?

        <a href="login.php">
            Login
        </a>

    </div>


</div>


</body>

</html>