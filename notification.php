<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

/* =========================
   TANDAI SEMUA SUDAH DIBACA
========================= */

if (isset($_POST['mark_all_read'])) {

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE notifications
         SET is_read = 1
         WHERE id_user = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: notification.php");
    exit;
}


/* =========================
   TANDAI SATU NOTIFIKASI
========================= */

if (isset($_GET['read'])) {

    $id_notification = (int) $_GET['read'];

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE notifications
         SET is_read = 1
         WHERE id_notification = ?
         AND id_user = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_notification,
        $id_user
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    /*
     * Kalau ada order ID di notifikasi,
     * arahkan ke detail pesanan.
     */

    if (isset($_GET['id_order'])) {

        $id_order = (int) $_GET['id_order'];

        header(
            "Location: order_success.php?id_order=" .
            $id_order
        );

    } else {

        header("Location: notification.php");
    }

    exit;
}


/* =========================
   HITUNG NOTIFIKASI BELUM DIBACA
========================= */

$stmt_unread = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE id_user = ?
     AND is_read = 0"
);

mysqli_stmt_bind_param(
    $stmt_unread,
    "i",
    $id_user
);

mysqli_stmt_execute($stmt_unread);

$result_unread = mysqli_stmt_get_result($stmt_unread);

$data_unread = mysqli_fetch_assoc($result_unread);

$total_unread = (int) $data_unread['total'];

mysqli_stmt_close($stmt_unread);


/* =========================
   AMBIL SEMUA NOTIFIKASI
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_notification,
        title,
        message,
        is_read,
        created_at
     FROM notifications
     WHERE id_user = ?
     ORDER BY created_at DESC"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_user
);

mysqli_stmt_execute($stmt);

$query = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Notifikasi - ZCY Petshop</title>

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

        /* HEADER */

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
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

        /* UNREAD INFO */

        .notification-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .unread-count {
            font-size: 13px;
            color: #7548e8;
            font-weight: bold;
        }

        .read-all {
            border: none;
            background: #7548e8;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
        }

        .read-all:hover {
            opacity: 0.9;
        }

        /* NOTIFICATION */

        .notification {
            position: relative;
            display: block;
            text-decoration: none;
            color: inherit;

            background: #fff;

            border: 1px solid #eee;

            border-radius: 14px;

            padding: 16px;

            margin-bottom: 12px;

            transition: 0.2s;
        }

        .notification:hover {
            transform: translateY(-1px);
            border-color: #d8ccff;
        }

        /* NOTIFICATION BELUM DIBACA */

        .notification.unread {
            background: #f3efff;
            border: 1px solid #d9ccff;
        }

        .notification.unread::before {
            content: "";

            position: absolute;

            left: 0;
            top: 14px;
            bottom: 14px;

            width: 4px;

            background: #7548e8;

            border-radius: 0 5px 5px 0;
        }

        .notification-title {
            font-weight: bold;
            color: #7548e8;
            font-size: 16px;
            margin-bottom: 7px;
            padding-right: 50px;
        }

        .notification-message {
            line-height: 1.5;
            color: #555;
        }

        .notification-date {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }

        /* BADGE BARU */

        .badge-new {
            position: absolute;

            top: 14px;
            right: 14px;

            background: #7548e8;
            color: white;

            font-size: 10px;

            padding: 4px 7px;

            border-radius: 10px;

            font-weight: bold;
        }

        /* EMPTY */

        .empty {
            text-align: center;
            color: #888;
            padding: 50px 20px;
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
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
            position: relative;

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

        /* BADGE NAV */

        .nav-badge {
            position: absolute;

            top: -5px;
            right: 8px;

            min-width: 16px;
            height: 16px;

            background: #e53935;

            color: white;

            border-radius: 50%;

            font-size: 9px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-weight: bold;

            padding: 0 4px;
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
            Notifikasi
        </h1>

    </div>


    <!-- NOTIFICATION TOP -->

    <?php if ($total_unread > 0): ?>

        <div class="notification-top">

            <div class="unread-count">

                <?= $total_unread; ?>
                notifikasi belum dibaca

            </div>

            <form method="POST">

                <button
                    type="submit"
                    name="mark_all_read"
                    class="read-all"
                >
                    Tandai Semua Dibaca
                </button>

            </form>

        </div>

    <?php endif; ?>


    <!-- LIST NOTIFICATION -->

    <?php if (
        $query &&
        mysqli_num_rows($query) > 0
    ): ?>

        <?php while (
            $notification = mysqli_fetch_assoc($query)
        ): ?>

            <?php

            $id_notification =
                (int) $notification['id_notification'];

            $is_read =
                (int) $notification['is_read'];

            /*
             * Cari nomor order dari isi pesan.
             * Contoh:
             * "Order #12"
             */

            $id_order = 0;

            if (
                preg_match(
                    '/Order\s*#(\d+)/i',
                    $notification['message'],
                    $match
                )
            ) {

                $id_order = (int) $match[1];

            }

            /*
             * Kalau ada nomor order,
             * klik menuju detail order.
             */

            if ($id_order > 0) {

                $notification_link =
                    "notification.php?read=" .
                    $id_notification .
                    "&id_order=" .
                    $id_order;

            } else {

                $notification_link =
                    "notification.php?read=" .
                    $id_notification;
            }

            ?>

            <a
                href="<?= htmlspecialchars($notification_link); ?>"
                class="notification <?= $is_read == 0 ? 'unread' : ''; ?>"
            >

                <?php if ($is_read == 0): ?>

                    <span class="badge-new">
                        Baru
                    </span>

                <?php endif; ?>


                <div class="notification-title">

                    <?= htmlspecialchars(
                        $notification['title']
                    ); ?>

                </div>


                <div class="notification-message">

                    <?= nl2br(
                        htmlspecialchars(
                            $notification['message']
                        )
                    ); ?>

                </div>


                <div class="notification-date">

                    <?= date(
                        'd M Y H:i',
                        strtotime(
                            $notification['created_at']
                        )
                    ); ?>

                </div>

            </a>

        <?php endwhile; ?>


    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">
                🔔
            </div>

            <div>
                Belum ada notifikasi.
            </div>

        </div>

    <?php endif; ?>

</div>


<!-- BOTTOM NAV -->

<div class="bottom-nav">

    <a
        href="index.php"
        class="nav-item"
    >

        <span class="nav-icon">
            🏠
        </span>

        Home

    </a>


    <a
        href="wishlist.php"
        class="nav-item"
    >

        <span class="nav-icon">
            ♡
        </span>

        Wishlist

    </a>


    <a
        href="cart.php"
        class="nav-item"
    >

        <span class="nav-icon">
            🛒
        </span>

        Cart

    </a>


    <a
        href="notification.php"
        class="nav-item active"
    >

        <span class="nav-icon">
            🔔
        </span>

        <?php if ($total_unread > 0): ?>

            <span class="nav-badge">
                <?= $total_unread > 99 ? '99+' : $total_unread; ?>
            </span>

        <?php endif; ?>

        Notification

    </a>


    <a
        href="profile.php"
        class="nav-item"
    >

        <span class="nav-icon">
            👤
        </span>

        Profile

    </a>

</div>

</body>

</html>