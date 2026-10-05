<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

if (!isset($_GET['id_order'])) {
    header("Location: index.php");
    exit;
}

$id_order = (int) $_GET['id_order'];


/* =========================================
   AMBIL DATA PESANAN
========================================= */

$order_query = mysqli_query($conn, "
    SELECT
        o.id_order,
        o.order_date,
        o.total_price,
        o.shipping_cost,
        o.status,
        o.shipping_method,
        p.payment_method,
        p.payment_status
    FROM orders o

    LEFT JOIN payments p
        ON o.id_order = p.id_order

    WHERE o.id_order = '$id_order'
    AND o.id_user = '$id_user'

    LIMIT 1
");

if (mysqli_num_rows($order_query) == 0) {
    die("Pesanan tidak ditemukan.");
}

$order = mysqli_fetch_assoc($order_query);


/* =========================================
   AMBIL ITEM PESANAN
========================================= */

$items_query = mysqli_query($conn, "
    SELECT
        oi.quantity,
        oi.price,
        oi.subtotal,
        p.product_name,
        pi.image
    FROM order_items oi

    JOIN products p
        ON oi.id_product = p.id_product

    LEFT JOIN product_images pi
        ON p.id_product = pi.id_product

    WHERE oi.id_order = '$id_order'
");

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pesanan Berhasil - ZCV Petshop</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f5ff;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: auto;
            padding: 25px 20px 40px;
        }

        .success-box {
            background: white;
            border-radius: 22px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #eee5ff;
            color: #7548e8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin: 0 auto 20px;
        }

        .success-box h1 {
            font-size: 24px;
            margin: 0 0 12px;
        }

        .success-box p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .order-number {
            margin-top: 20px;
            padding: 12px;
            background: #f7f4ff;
            color: #7548e8;
            border-radius: 10px;
            font-weight: bold;
        }

        .box {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .title {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .product {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .product:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            background: #eee;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .product-qty {
            font-size: 13px;
            color: #777;
        }

        .product-price {
            font-size: 13px;
            font-weight: bold;
            color: #7548e8;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 11px;
            font-size: 14px;
        }

        .row:last-child {
            margin-bottom: 0;
        }

        .status {
            color: #7548e8;
            font-weight: bold;
        }

        .total {
            border-top: 1px solid #ddd;
            padding-top: 14px;
            margin-top: 14px;
            font-size: 17px;
            font-weight: bold;
        }

        .button {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
        }

        .primary {
            background: #7548e8;
            color: white;
        }

        .secondary {
            background: white;
            color: #7548e8;
            border: 2px solid #7548e8;
        }

    </style>

</head>

<body>

<div class="container">

    <!-- PESANAN BERHASIL -->

    <div class="success-box">

        <div class="success-icon">
            ✓
        </div>

        <h1>
            Pesanan Berhasil!
        </h1>

        <p>
            Wahh terimakasih telah berbelanja
            di ZCV Petshop. 🐾
        </p>

        <p style="margin-top:8px;">
            Pesananmu sedang diproses.
            Kami akan segera mengirimkannya.
        </p>

        <div class="order-number">
            Pesanan #<?= $order['id_order']; ?>
        </div>

    </div>


    <!-- ITEM -->

    <div class="box">

        <div class="title">
            Pesananmu
        </div>

        <?php while ($item = mysqli_fetch_assoc($items_query)): ?>

            <div class="product">

                <?php if (!empty($item['image'])): ?>

                    <img
                        src="assets/images/<?= htmlspecialchars(
                            $item['image']
                        ); ?>"
                        class="product-image"
                    >

                <?php else: ?>

                    <div
                        class="product-image"
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:25px;
                        "
                    >
                        🐱
                    </div>

                <?php endif; ?>


                <div class="product-info">

                    <div class="product-name">
                        <?= htmlspecialchars(
                            $item['product_name']
                        ); ?>
                    </div>

                    <div class="product-qty">
                        <?= $item['quantity']; ?> ×
                        Rp <?= number_format(
                            $item['price'],
                            0,
                            ',',
                            '.'
                        ); ?>
                    </div>

                </div>

                <div class="product-price">

                    Rp <?= number_format(
                        $item['subtotal'],
                        0,
                        ',',
                        '.'
                    ); ?>

                </div>

            </div>

        <?php endwhile; ?>

    </div>


    <!-- DETAIL PEMBAYARAN -->

    <div class="box">

        <div class="title">
            Detail Pesanan
        </div>

        <div class="row">

            <span>
                Status
            </span>

            <span class="status">
                <?= htmlspecialchars(
                    $order['status']
                ); ?>
            </span>

        </div>

        <div class="row">

            <span>
                Pembayaran
            </span>

            <span>
                <?= htmlspecialchars(
                    $order['payment_method']
                ); ?>
            </span>

        </div>

        <div class="row">

            <span>
                Pengiriman
            </span>

            <span>
                <?= htmlspecialchars(
                    $order['shipping_method']
                ); ?>
            </span>

        </div>

        <div class="row">

            <span>
                Ongkos Kirim
            </span>

            <span>
                Rp <?= number_format(
                    $order['shipping_cost'],
                    0,
                    ',',
                    '.'
                ); ?>
            </span>

        </div>

        <div class="row total">

            <span>
                Total
            </span>

            <span style="color:#7548e8;">
                Rp <?= number_format(
                    $order['total_price'],
                    0,
                    ',',
                    '.'
                ); ?>
            </span>

        </div>

    </div>


    <!-- TOMBOL -->

    <a
        href="review.php?id_order=<?= $order['id_order']; ?>"
        class="button primary"
    >
        ⭐ BERI RATING & ULASAN
    </a>

    <a
        href="index.php"
        class="button secondary"
    >
        KEMBALI KE SHOP
    </a>

</div>

</body>

</html>