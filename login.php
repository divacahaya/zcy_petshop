<?php
session_start();
require_once "config/database.php";

$message = "";

/* =========================================
   CEK JIKA SUDAH LOGIN
========================================= */

if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}


/* =========================================
   PROSES LOGIN
========================================= */

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email == "" || $password == "") {

        $message = "Email dan password wajib diisi.";

    } else {

        $email_safe = mysqli_real_escape_string(
            $conn,
            $email
        );

        $query = mysqli_query($conn, "
            SELECT
                id_user,
                name,
                email,
                password,
                role
            FROM users
            WHERE email = '$email_safe'
            LIMIT 1
        ");


        if ($query && mysqli_num_rows($query) > 0) {

            $user = mysqli_fetch_assoc($query);

            $login_success = false;


            /* =========================================
               CEK PASSWORD
            ========================================= */

            if (
                password_verify(
                    $password,
                    $user['password']
                )
            ) {

                $login_success = true;

            } elseif (
                $password === $user['password']
            ) {

                /*
                 * Untuk password lama yang masih
                 * tersimpan dalam bentuk plaintext.
                 */

                $login_success = true;
            }


            /* =========================================
               JIKA LOGIN BERHASIL
            ========================================= */

            if ($login_success) {

                $_SESSION['id_user'] =
                    $user['id_user'];

                $_SESSION['name'] =
                    $user['name'];

                $_SESSION['email'] =
                    $user['email'];


                /*
                 * Jika akun admin login melalui
                 * halaman user, arahkan ke dashboard admin.
                 */

                if (
                    isset($user['role']) &&
                    $user['role'] === 'admin'
                ) {

                    $_SESSION['admin_id'] =
                        $user['id_user'];

                    $_SESSION['admin_name'] =
                        $user['name'];

                    $_SESSION['admin_email'] =
                        $user['email'];

                    header(
                        "Location: admin_dashboard.php"
                    );

                    exit;
                }


                /*
                 * Jika user biasa
                 */

                header(
                    "Location: index.php"
                );

                exit;

            } else {

                $message =
                    "Email atau password salah.";
            }

        } else {

            $message =
                "Email atau password salah.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - ZCY Petshop</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                sans-serif;

            background: #ffffff;

            color: #333;

        }


        .login-container {

            width: 100%;

            max-width: 400px;

            min-height: 100vh;

            margin: auto;

            padding: 40px 25px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        /* =========================================
           LOGO
        ========================================= */

        .logo {

            text-align: center;

            margin-bottom: 28px;

        }


        .logo-icon {

            width: 96px;

            height: 96px;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: #ffb52e;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 50px;

        }


        .logo h1 {

            margin: 0;

            color: #7548e8;

            font-size: 25px;

        }


        .logo p {

            margin: 5px 0 0;

            color: #999;

            font-size: 14px;

        }


        /* =========================================
           FORM
        ========================================= */

        .form-group {

            margin-bottom: 17px;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            color: #555;

        }


        .form-group input {

            width: 100%;

            padding: 13px 14px;

            border: 1px solid #ddd;

            border-radius: 7px;

            font-size: 14px;

            outline: none;

            transition: 0.2s;

        }


        .form-group input:focus {

            border-color: #7548e8;

            box-shadow:
                0 0 0 2px
                rgba(117, 72, 232, 0.08);

        }


        .form-group input::placeholder {

            color: #aaa;

        }


        /* =========================================
           LUPA PASSWORD
        ========================================= */

        .forgot-password {

            display: block;

            text-align: right;

            margin-top: -5px;

            margin-bottom: 20px;

            color: #7548e8;

            text-decoration: none;

            font-size: 13px;

        }


        .forgot-password:hover {

            text-decoration: underline;

        }


        /* =========================================
           MESSAGE
        ========================================= */

        .message {

            background: #ffe8e8;

            color: #c62828;

            border-radius: 8px;

            padding: 11px 13px;

            margin-bottom: 15px;

            font-size: 13px;

            text-align: center;

        }


        /* =========================================
           LOGIN BUTTON
        ========================================= */

        .login-btn {

            width: 100%;

            border: none;

            background: #7548e8;

            color: white;

            padding: 13px;

            border-radius: 7px;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;

        }


        .login-btn:hover {

            background: #6438d3;

        }


        /* =========================================
           REGISTER
        ========================================= */

        .register-text {

            text-align: center;

            margin-top: 20px;

            color: #777;

            font-size: 13px;

        }


        .register-text a {

            color: #7548e8;

            text-decoration: none;

            font-weight: bold;

        }


        .register-text a:hover {

            text-decoration: underline;

        }


        /* =========================================
           ADMIN LOGIN
        ========================================= */

        .admin-login {

            text-align: center;

            margin-top: 18px;

            padding-top: 18px;

            border-top: 1px solid #eee;

        }


        .admin-login p {

            margin: 0 0 8px;

            color: #888;

            font-size: 13px;

        }


        .admin-login a {

            color: #7548e8;

            text-decoration: none;

            font-weight: bold;

            font-size: 13px;

        }


        .admin-login a:hover {

            text-decoration: underline;

        }


    </style>

</head>


<body>


<div class="login-container">


    <!-- =========================================
         LOGO
    ========================================= -->

    <div class="logo">

        <div class="logo-icon">
            🐱
        </div>


        <h1>
            ZCY Petshop
        </h1>


        <p>
            Healthy Pets, Happy Life
        </p>

    </div>



    <!-- =========================================
         PESAN ERROR
    ========================================= -->

    <?php if ($message != ""): ?>

        <div class="message">

            <?= htmlspecialchars(
                $message
            ); ?>

        </div>

    <?php endif; ?>



    <!-- =========================================
         FORM LOGIN
    ========================================= -->

    <form
        method="POST"
        action=""
    >


        <!-- EMAIL -->

        <div class="form-group">

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                placeholder="Masukkan email"
                value="<?= isset($_POST['email'])
                    ? htmlspecialchars($_POST['email'])
                    : ''; ?>"
                required
            >

        </div>



        <!-- PASSWORD -->

        <div class="form-group">

            <label>
                Password
            </label>

            <input
                type="password"
                name="password"
                placeholder="Masukkan password"
                required
            >

        </div>



        <!-- LUPA PASSWORD -->

        <a
            href="#"
            class="forgot-password"
            onclick="
                alert('Fitur lupa password belum tersedia.');
                return false;
            "
        >
            Lupa Password?
        </a>



        <!-- LOGIN -->

        <button
            type="submit"
            name="login"
            class="login-btn"
        >
            LOGIN
        </button>


    </form>



    <!-- =========================================
         REGISTER
    ========================================= -->

    <div class="register-text">

        Belum punya akun?

        <a href="register.php">
            Daftar sekarang
        </a>

    </div>



    <!-- =========================================
         ADMIN LOGIN
    ========================================= -->

    <div class="admin-login">

        <p>
            Login sebagai admin?
        </p>

        <a href="admin_login.php">
            🔐 Login Admin
        </a>

    </div>


</div>


</body>

</html>