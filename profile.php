<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

/* Ambil data user */
$query = mysqli_query($conn, "
    SELECT id_user, name, email
    FROM users
    WHERE id_user = $id_user
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) == 0) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$user = mysqli_fetch_assoc($query);

/* Hitung jumlah pesanan */
$order_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total_order
    FROM orders
    WHERE id_user = $id_user
");

$order_data = mysqli_fetch_assoc($order_query);
$total_order = (int) $order_data['total_order'];

/* Hitung jumlah wishlist */
$wishlist_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total_wishlist
    FROM wishlist_items wi
    INNER JOIN wishlists w
        ON wi.id_wishlist = w.id_wishlist
    WHERE w.id_user = $id_user
");

$wishlist_data = mysqli_fetch_assoc($wishlist_query);
$total_wishlist = (int) $wishlist_data['total_wishlist'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Profile - ZCY Petshop</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8f5f0;
            color: #333;
        }

        .container {
            max-width: 480px;
            min-height: 100vh;
            margin: auto;
            background: white;
            padding: 20px;
            padding-bottom: 100px;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .back {
            text-decoration: none;
            color: #333;
            font-size: 22px;
            margin-right: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .profile-card {
            background: linear-gradient(
                135deg,
                #e78346,
                #f0a66e
            );

            color: white;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .profile-top {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: white;
            color: #e78346;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .profile-name {
            font-size: 20px;
            font-weight: bold;
        }

        .profile-email {
            margin-top: 5px;
            font-size: 13px;
            opacity: .9;
        }

        .stats {
            display: flex;
            margin-top: 22px;
            border-top: 1px solid rgba(255,255,255,.3);
            padding-top: 15px;
        }

        .stat {
            width: 50%;
            text-align: center;
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
        }

        .stat-label {
            font-size: 12px;
            margin-top: 3px;
        }

        .menu {
            background: white;
            border-radius: 15px;
            border: 1px solid #eee;
            overflow: hidden;
        }

        .menu-title {
            padding: 16px 18px 8px;
            font-size: 14px;
            color: #999;
            font-weight: bold;
        }

        .menu-item {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            padding: 16px 18px;
            border-bottom: 1px solid #eee;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-icon {
            width: 35px;
            font-size: 20px;
        }

        .menu-text {
            flex: 1;
        }

        .menu-arrow {
            color: #aaa;
        }

        .logout {
            color: #e74c3c;
        }

        /* BOTTOM NAV */

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: white;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-around;
            padding: 12px 5px;
            z-index: 100;
        }

        .nav-item {
            text-decoration: none;
            color: #777;
            text-align: center;
            font-size: 12px;
        }

        .nav-icon {
            display: block;
            font-size: 21px;
            margin-bottom: 3px;
        }

        .active {
            color: #7548e8;
            font-weight: bold;
        }

    </style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <a
            href="index.php"
            class="back"
        >
            ←
        </a>

        <h1>
            Profile
        </h1>

    </div>


    <!-- PROFILE CARD -->

    <div class="profile-card">

        <div class="profile-top">

            <div class="avatar">
                👤
            </div>

            <div>

                <div class="profile-name">
                    <?= htmlspecialchars($user['name']); ?>
                </div>

                <div class="profile-email">
                    <?= htmlspecialchars($user['email']); ?>
                </div>

            </div>

        </div>


        <div class="stats">

            <div class="stat">

                <div class="stat-number">
                    <?= $total_order; ?>
                </div>

                <div class="stat-label">
                    Pesanan
                </div>

            </div>


            <div class="stat">

                <div class="stat-number">
                    <?= $total_wishlist; ?>
                </div>

                <div class="stat-label">
                    Wishlist
                </div>

            </div>

        </div>

    </div>


    <!-- ACCOUNT -->

    <div class="menu">

        <div class="menu-title">
            AKUN
        </div>


        <a
            href="address.php"
            class="menu-item"
        >

            <div class="menu-icon">
                📍
            </div>

            <div class="menu-text">
                Alamat Pengiriman
            </div>

            <div class="menu-arrow">
                →
            </div>

        </a>


        <a
            href="wishlist.php"
            class="menu-item"
        >

            <div class="menu-icon">
                ♡
            </div>

            <div class="menu-text">
                Wishlist
            </div>

            <div class="menu-arrow">
                →
            </div>

        </a>

    </div>


    <!-- PESANAN -->

    <div
        class="menu"
        style="margin-top:15px;"
    >

        <div class="menu-title">
            PESANAN
        </div>


        <a
            href="orders.php"
            class="menu-item"
        >

            <div class="menu-icon">
                📦
            </div>

            <div class="menu-text">
                Pesanan Saya
            </div>

            <div class="menu-arrow">
                →
            </div>

        </a>


        <a
            href="orders.php"
            class="menu-item"
        >

            <div class="menu-icon">
                🚚
            </div>

            <div class="menu-text">
                Lacak Pesanan
            </div>

            <div class="menu-arrow">
                →
            </div>

        </a>

    </div>


    <!-- LAINNYA -->

    <div
        class="menu"
        style="margin-top:15px;"
    >

        <div class="menu-title">
            LAINNYA
        </div>


        <a
            href="notification.php"
            class="menu-item"
        >

            <div class="menu-icon">
                🔔
            </div>

            <div class="menu-text">
                Notifikasi
            </div>

            <div class="menu-arrow">
                →
            </div>

        </a>


        <a
            href="logout.php"
            class="menu-item logout"
        >

            <div class="menu-icon">
                🚪
            </div>

            <div class="menu-text">
                Logout
            </div>

            <div class="menu-arrow">
                →
            </div>

        </a>

    </div>

</div>


<!-- BOTTOM NAV -->

<div class="bottom-nav">

    <a
        href="index.php"
        class="nav-item"
    >
        <span class="nav-icon">🏠</span>
        Home
    </a>


    <a
        href="wishlist.php"
        class="nav-item"
    >
        <span class="nav-icon">♡</span>
        Wishlist
    </a>


    <a
        href="cart.php"
        class="nav-item"
    >
        <span class="nav-icon">🛒</span>
        Cart
    </a>


    <a
        href="notification.php"
        class="nav-item"
    >
        <span class="nav-icon">🔔</span>
        Notification
    </a>


    <a
        href="profile.php"
        class="nav-item active"
    >
        <span class="nav-icon">👤</span>
        Profile
    </a>

</div>

</body>

</html>