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
// STATISTIK
// ===============================

// Total Produk
$result = $conn->query("SELECT COUNT(*) AS total FROM products");
$total_produk = $result->fetch_assoc()['total'];

// Total Pesanan
$result = $conn->query("SELECT COUNT(*) AS total FROM orders");
$total_pesanan = $result->fetch_assoc()['total'];

// Total Pengguna
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
$total_pengguna = $result->fetch_assoc()['total'];

// Total Review
$result = $conn->query("SELECT COUNT(*) AS total FROM reviews");
$total_review = $result->fetch_assoc()['total'];

// Total Pendapatan
$result = $conn->query("
    SELECT COALESCE(SUM(total_price + shipping_cost), 0) AS total
    FROM orders
    WHERE status IN ('Paid', 'Processing', 'Shipped', 'Completed')
");
$total_pendapatan = $result->fetch_assoc()['total'];

// Pesanan Pending
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE status = 'Pending'
");
$total_pending = $result->fetch_assoc()['total'];


// ===============================
// NOTIFIKASI ADMIN
// ===============================

$admin_id = $_SESSION['admin_id'];

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE id_user = '$admin_id'
    AND is_read = 0
");

$total_notifikasi = $result->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard - ZCY Petshop</title>

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

        .header {
            background: #ffffff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-name {
            font-weight: bold;
        }

        .logout {
            text-decoration: none;
            background: #dc3545;
            color: white;
            padding: 9px 15px;
            border-radius: 7px;
            font-size: 14px;
        }

        .container {
            padding: 30px;
            max-width: 1300px;
            margin: auto;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            margin-bottom: 7px;
        }

        .welcome p {
            color: #777;
        }


        /* ===============================
           STATISTIK
        =============================== */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-title {
            color: #777;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }


        /* ===============================
           MENU
        =============================== */

        .section-title {
            margin-bottom: 15px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: 0.2s;
            position: relative;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }

        .menu-icon {
            font-size: 35px;
            margin-bottom: 12px;
        }

        .menu-card h3 {
            margin-bottom: 7px;
        }

        .menu-card p {
            color: #777;
            font-size: 14px;
        }


        /* ===============================
           BADGE NOTIFIKASI
        =============================== */

        .notification-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #dc3545;
            color: white;
            min-width: 25px;
            height: 25px;
            padding: 4px 7px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 1100px) {

            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
            }

        }


        @media (max-width: 900px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 600px) {

            .header {
                padding: 15px;
            }

            .container {
                padding: 20px 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .admin-info {
                flex-direction: column;
                align-items: flex-end;
                gap: 5px;
            }

        }

    </style>

</head>


<body>


<!-- ===============================
     HEADER
=============================== -->

<div class="header">

    <h1>
        🐾 ZCY Petshop Admin
    </h1>

    <div class="admin-info">

        <span class="admin-name">

            👤 <?= htmlspecialchars(
                $_SESSION['admin_name']
            ) ?>

        </span>

        <a
            href="admin_logout.php"
            class="logout"
        >
            Logout
        </a>

    </div>

</div>



<div class="container">


    <!-- ===============================
         WELCOME
    =============================== -->

    <div class="welcome">

        <h2>
            Dashboard Admin
        </h2>

        <p>
            Selamat datang di halaman admin ZCY Petshop.
        </p>

    </div>



    <!-- ===============================
         STATISTIK
    =============================== -->

    <div class="stats">


        <!-- TOTAL PRODUK -->

        <div class="stat-card">

            <div class="stat-icon">
                📦
            </div>

            <div class="stat-title">
                Total Produk
            </div>

            <div class="stat-number">
                <?= $total_produk ?>
            </div>

        </div>



        <!-- TOTAL PESANAN -->

        <div class="stat-card">

            <div class="stat-icon">
                🛒
            </div>

            <div class="stat-title">
                Total Pesanan
            </div>

            <div class="stat-number">
                <?= $total_pesanan ?>
            </div>

        </div>



        <!-- TOTAL PENGGUNA -->

        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div class="stat-title">
                Total Pengguna
            </div>

            <div class="stat-number">
                <?= $total_pengguna ?>
            </div>

        </div>



        <!-- TOTAL REVIEW -->

        <div class="stat-card">

            <div class="stat-icon">
                ⭐
            </div>

            <div class="stat-title">
                Total Review
            </div>

            <div class="stat-number">
                <?= $total_review ?>
            </div>

        </div>



        <!-- TOTAL PENDAPATAN -->

        <div class="stat-card">

            <div class="stat-icon">
                💰
            </div>

            <div class="stat-title">
                Total Pendapatan
            </div>

            <div class="stat-number">

                Rp <?= number_format(
                    $total_pendapatan,
                    0,
                    ',',
                    '.'
                ) ?>

            </div>

        </div>



        <!-- PESANAN PENDING -->

        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div class="stat-title">
                Pesanan Pending
            </div>

            <div class="stat-number">
                <?= $total_pending ?>
            </div>

        </div>


    </div>



    <!-- ===============================
         MENU ADMIN
    =============================== -->

    <h2 class="section-title">
        Menu Admin
    </h2>



    <div class="menu-grid">


        <!-- PRODUK -->

        <a
            href="admin_products.php"
            class="menu-card"
        >

            <div class="menu-icon">
                📦
            </div>

            <h3>
                Produk
            </h3>

            <p>
                Kelola produk dan stok barang.
            </p>

        </a>



        <!-- PESANAN -->

        <a
            href="admin_orders.php"
            class="menu-card"
        >

            <div class="menu-icon">
                🛒
            </div>

            <h3>
                Pesanan
            </h3>

            <p>
                Lihat dan kelola pesanan pelanggan.
            </p>

        </a>



        <!-- PENGGUNA -->

        <a
            href="admin_users.php"
            class="menu-card"
        >

            <div class="menu-icon">
                👥
            </div>

            <h3>
                Pengguna
            </h3>

            <p>
                Kelola data pengguna.
            </p>

        </a>



        <!-- REVIEW -->

        <a
            href="admin_reviews.php"
            class="menu-card"
        >

            <div class="menu-icon">
                ⭐
            </div>

            <h3>
                Review
            </h3>

            <p>
                Lihat dan kelola review pelanggan.
            </p>

        </a>



        <!-- ===============================
             NOTIFIKASI
        =============================== -->

        <a
            href="admin_notifications.php"
            class="menu-card"
        >

            <div class="menu-icon">
                🔔
            </div>

            <?php if ($total_notifikasi > 0): ?>

                <div class="notification-badge">
                    <?= $total_notifikasi ?>
                </div>

            <?php endif; ?>


            <h3>
                Notifikasi
            </h3>

            <p>
                Lihat pesanan dan pembayaran baru.
            </p>

        </a>


    </div>

</div>


</body>

</html>