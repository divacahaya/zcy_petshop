<?php
session_start();
require_once "config/database.php";

// ===============================
// CEK LOGIN ADMIN
// ===============================
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ===============================
// CEK ID USER
// ===============================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_users.php");
    exit;
}

$id_user = (int) $_GET['id'];

// ===============================
// AMBIL DATA USER
// ===============================
$stmt = $conn->prepare("
    SELECT
        id_user,
        name,
        email,
        phone,
        role,
        created_at
    FROM users
    WHERE id_user = ?
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Jika user tidak ditemukan
if (!$user) {
    header("Location: admin_users.php");
    exit;
}

// ===============================
// JUMLAH PESANAN
// ===============================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE id_user = ?
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$total_orders = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ===============================
// JUMLAH REVIEW
// ===============================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM reviews
    WHERE id_user = ?
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$total_reviews = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ===============================
// JUMLAH WISHLIST
// ===============================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM wishlist_items wi
    INNER JOIN wishlists w
        ON wi.id_wishlist = w.id_wishlist
    WHERE w.id_user = ?
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$total_wishlist = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ===============================
// JUMLAH ALAMAT
// ===============================
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM addresses
    WHERE id_user = ?
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$total_addresses = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Detail Pengguna - ZCY Petshop</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        /* ===============================
           HEADER
        =============================== */

        .header {
            background: white;

            padding: 20px 30px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            border-bottom: 1px solid #ddd;
        }

        .header h1 {
            font-size: 24px;
        }

        .back {
            text-decoration: none;
            color: #333;
        }

        /* ===============================
           CONTAINER
        =============================== */

        .container {
            max-width: 1100px;

            margin: auto;

            padding: 30px;
        }

        .title {
            margin-bottom: 25px;
        }

        .title h2 {
            margin-bottom: 5px;
        }

        .title p {
            color: #777;
        }

        /* ===============================
           PROFILE
        =============================== */

        .profile-card {

            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.06);

            margin-bottom: 25px;
        }

        .profile-top {

            display: flex;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }

        .avatar {

            width: 75px;
            height: 75px;

            border-radius: 50%;

            background: #e9ecef;

            display: flex;

            justify-content: center;

            align-items: center;

            font-size: 35px;
        }

        .profile-name h3 {

            font-size: 23px;

            margin-bottom: 7px;
        }

        .profile-name p {

            color: #777;
        }

        /* ===============================
           ROLE
        =============================== */

        .role {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            margin-top: 7px;
        }

        .role-user {

            background: #cfe2ff;

            color: #084298;
        }

        .role-admin {

            background: #f8d7da;

            color: #842029;
        }

        /* ===============================
           DATA
        =============================== */

        .data-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;
        }

        .data-item {

            padding: 15px;

            background: #f8f9fa;

            border-radius: 8px;
        }

        .data-label {

            color: #777;

            font-size: 13px;

            margin-bottom: 5px;
        }

        .data-value {

            font-weight: bold;

            word-break: break-word;
        }

        /* ===============================
           STATISTIK
        =============================== */

        .section-title {

            margin-bottom: 15px;
        }

        .stats {

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 15px;

            margin-bottom: 25px;
        }

        .stat-card {

            background: white;

            padding: 22px;

            border-radius: 10px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.06);

            text-align: center;
        }

        .stat-icon {

            font-size: 30px;

            margin-bottom: 8px;
        }

        .stat-number {

            font-size: 26px;

            font-weight: bold;

            margin-bottom: 5px;
        }

        .stat-label {

            color: #777;

            font-size: 13px;
        }

        /* ===============================
           BUTTON
        =============================== */

        .actions {

            display: flex;

            gap: 10px;

            margin-top: 10px;
        }

        .btn {

            text-decoration: none;

            padding: 10px 16px;

            border-radius: 7px;

            display: inline-block;
        }

        .btn-back {

            background: #6c757d;

            color: white;
        }

        .btn-orders {

            background: #0d6efd;

            color: white;
        }

        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 800px) {

            .stats {

                grid-template-columns: 1fr 1fr;

            }

            .data-grid {

                grid-template-columns: 1fr;

            }

        }

        @media (max-width: 500px) {

            .header {

                padding: 15px;

            }

            .container {

                padding: 20px 15px;

            }

            .stats {

                grid-template-columns: 1fr;

            }

            .profile-top {

                flex-direction: column;

                align-items: flex-start;

            }

        }

    </style>

</head>

<body>

<!-- ===============================
     HEADER
=============================== -->

<div class="header">

    <h1>🐾 ZCY Petshop</h1>

    <a
        href="admin_users.php"
        class="back"
    >
        ← Pengguna
    </a>

</div>


<div class="container">

    <!-- ===============================
         TITLE
    =============================== -->

    <div class="title">

        <h2>Detail Pengguna</h2>

        <p>
            Informasi lengkap akun pengguna ZCY Petshop.
        </p>

    </div>


    <!-- ===============================
         PROFILE
    =============================== -->

    <div class="profile-card">

        <div class="profile-top">

            <div class="avatar">
                👤
            </div>


            <div class="profile-name">

                <h3>
                    <?= htmlspecialchars($user['name']) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($user['email']) ?>
                </p>


                <?php if ($user['role'] === 'admin'): ?>

                    <span class="role role-admin">
                        👑 Admin
                    </span>

                <?php else: ?>

                    <span class="role role-user">
                        👤 User
                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- DATA AKUN -->

        <div class="data-grid">

            <div class="data-item">

                <div class="data-label">
                    ID Pengguna
                </div>

                <div class="data-value">
                    #<?= $user['id_user'] ?>
                </div>

            </div>


            <div class="data-item">

                <div class="data-label">
                    Nama
                </div>

                <div class="data-value">
                    <?= htmlspecialchars($user['name']) ?>
                </div>

            </div>


            <div class="data-item">

                <div class="data-label">
                    Email
                </div>

                <div class="data-value">
                    <?= htmlspecialchars($user['email']) ?>
                </div>

            </div>


            <div class="data-item">

                <div class="data-label">
                    Nomor HP
                </div>

                <div class="data-value">

                    <?= !empty($user['phone'])
                        ? htmlspecialchars($user['phone'])
                        : '-'
                    ?>

                </div>

            </div>


            <div class="data-item">

                <div class="data-label">
                    Role
                </div>

                <div class="data-value">

                    <?= htmlspecialchars(
                        ucfirst($user['role'])
                    ) ?>

                </div>

            </div>


            <div class="data-item">

                <div class="data-label">
                    Tanggal Daftar
                </div>

                <div class="data-value">

                    <?= date(
                        'd-m-Y H:i',
                        strtotime($user['created_at'])
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- ===============================
         STATISTIK USER
    =============================== -->

    <h3 class="section-title">
        Aktivitas Pengguna
    </h3>


    <div class="stats">

        <!-- PESANAN -->

        <div class="stat-card">

            <div class="stat-icon">
                🛒
            </div>

            <div class="stat-number">
                <?= $total_orders ?>
            </div>

            <div class="stat-label">
                Total Pesanan
            </div>

        </div>


        <!-- REVIEW -->

        <div class="stat-card">

            <div class="stat-icon">
                ⭐
            </div>

            <div class="stat-number">
                <?= $total_reviews ?>
            </div>

            <div class="stat-label">
                Total Review
            </div>

        </div>


        <!-- WISHLIST -->

        <div class="stat-card">

            <div class="stat-icon">
                ❤️
            </div>

            <div class="stat-number">
                <?= $total_wishlist ?>
            </div>

            <div class="stat-label">
                Wishlist
            </div>

        </div>


        <!-- ALAMAT -->

        <div class="stat-card">

            <div class="stat-icon">
                📍
            </div>

            <div class="stat-number">
                <?= $total_addresses ?>
            </div>

            <div class="stat-label">
                Alamat
            </div>

        </div>

    </div>


    <!-- ===============================
         ACTION
    =============================== -->

    <div class="actions">

        <a
            href="admin_users.php"
            class="btn btn-back"
        >
            ← Kembali
        </a>


        <a
            href="admin_orders.php?search=<?= urlencode($user['name']) ?>"
            class="btn btn-orders"
        >
            🛒 Lihat Pesanan
        </a>

    </div>

</div>

</body>

</html>