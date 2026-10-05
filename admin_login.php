<?php
session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email === "" || $password === "") {

        $error = "Email dan password wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_user, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            if (
                $user["role"] === "admin" &&
                (
                    password_verify($password, $user["password"]) ||
                    $password === $user["password"]
                )
            ) {

                $_SESSION["admin_id"] = $user["id_user"];
                $_SESSION["admin_name"] = $user["name"];
                $_SESSION["admin_email"] = $user["email"];

                header("Location: admin_dashboard.php");
                exit;

            } else {

                $error = "Akun ini bukan akun admin atau password salah.";

            }

        } else {

            $error = "Email atau password salah.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login - ZCY Petshop</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;

            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            width: 90%;
            max-width: 400px;

            background: white;

            padding: 30px;

            border-radius: 16px;

            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .logo {
            text-align: center;
            font-size: 45px;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            margin: 0;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            margin-bottom: 16px;

            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #555;
        }

        button {
            width: 100%;

            padding: 12px;

            border: none;

            border-radius: 8px;

            background: #222;

            color: white;

            font-size: 15px;

            font-weight: bold;

            cursor: pointer;
        }

        button:hover {
            background: #444;
        }

        .error {
            background: #f8d7da;

            color: #721c24;

            padding: 10px;

            border-radius: 8px;

            margin-bottom: 15px;

            font-size: 14px;

            text-align: center;
        }

        .back {
            display: block;

            text-align: center;

            margin-top: 18px;

            color: #555;

            text-decoration: none;

            font-size: 14px;
        }

    </style>

</head>

<body>


<div class="login-box">

    <div class="logo">🐾</div>

    <h2>ZCY Petshop</h2>

    <div class="subtitle">
        Admin Panel
    </div>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <label>Email Admin</label>

        <input
            type="email"
            name="email"
            placeholder="admin@zcy.com"
            required
        >


        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
        >


        <button type="submit">
            LOGIN ADMIN
        </button>

    </form>


    <a href="login.php" class="back">
        ← Kembali ke Login User
    </a>

</div>


</body>

</html>