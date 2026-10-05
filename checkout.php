<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$message = "";

/* =========================================
   AMBIL CART USER
========================================= */

$cart_query = mysqli_query($conn, "
    SELECT id_cart
    FROM carts
    WHERE id_user = '$id_user'
    LIMIT 1
");

$cart = mysqli_fetch_assoc($cart_query);

if (!$cart) {
    die("Keranjang belum tersedia.");
}

$id_cart = $cart['id_cart'];

/* =========================================
   PROSES CHECKOUT
========================================= */

if (isset($_POST['checkout'])) {

    $id_address = (int) $_POST['id_address'];

    $payment_method = mysqli_real_escape_string(
        $conn,
        $_POST['payment_method']
    );

    $shipping_method = mysqli_real_escape_string(
        $conn,
        $_POST['shipping_method']
    );

    /* CEK ALAMAT MILIK USER */

    $address_check = mysqli_query($conn, "
        SELECT *
        FROM addresses
        WHERE id_address = '$id_address'
        AND id_user = '$id_user'
        LIMIT 1
    ");

    if (mysqli_num_rows($address_check) == 0) {

        $message = "Alamat pengiriman tidak valid.";

    } else {

        /* =========================================
           ONGKOS KIRIM
        ========================================= */

        $shipping_cost = 10000;

        if ($shipping_method == "JNE") {

            $shipping_cost = 15000;

        } elseif ($shipping_method == "J&T") {

            $shipping_cost = 15000;

        } elseif ($shipping_method == "AnterAja") {

            $shipping_cost = 12000;

        } elseif ($shipping_method == "SiCepat") {

            $shipping_cost = 12000;

        } elseif ($shipping_method == "Gojek") {

            $shipping_cost = 10000;

        } elseif ($shipping_method == "Grab") {

            $shipping_cost = 10000;
        }


        /* =========================================
           AMBIL ITEM CART
        ========================================= */

        $cart_items = mysqli_query($conn, "
            SELECT
                ci.id_cart_item,
                ci.id_product,
                ci.quantity,
                ci.price,
                p.product_name,
                p.stock
            FROM cart_items ci
            JOIN products p
                ON ci.id_product = p.id_product
            WHERE ci.id_cart = '$id_cart'
        ");


        if (mysqli_num_rows($cart_items) == 0) {

            $message = "Keranjang masih kosong.";

        } else {

            $subtotal = 0;
            $items = [];


            while ($item = mysqli_fetch_assoc($cart_items)) {

                /* CEK STOK */

                if ($item['quantity'] > $item['stock']) {

                    $message =
                        "Stok produk " .
                        $item['product_name'] .
                        " tidak mencukupi.";

                    break;
                }


                $item_subtotal =
                    $item['quantity'] * $item['price'];

                $subtotal += $item_subtotal;


                $items[] = [

                    'id_product' => $item['id_product'],

                    'quantity' => $item['quantity'],

                    'price' => $item['price'],

                    'subtotal' => $item_subtotal

                ];
            }


            /* =========================================
               BUAT ORDER
            ========================================= */

            if ($message == "") {

                $total_price =
                    $subtotal + $shipping_cost;


                mysqli_begin_transaction($conn);


                try {

                    /* =================================
                       INSERT ORDER
                    ================================= */

                    $insert_order = mysqli_query($conn, "

                        INSERT INTO orders
                        (
                            id_user,
                            id_address,
                            total_price,
                            shipping_cost,
                            status,
                            shipping_method
                        )

                        VALUES
                        (
                            '$id_user',
                            '$id_address',
                            '$total_price',
                            '$shipping_cost',
                            'Pending',
                            '$shipping_method'
                        )

                    ");


                    if (!$insert_order) {

                        throw new Exception(
                            "Gagal membuat pesanan."
                        );
                    }


                    $id_order =
                        mysqli_insert_id($conn);


                    /* =================================
                       INSERT ORDER ITEMS
                    ================================= */

                    foreach ($items as $item) {

                        $id_product =
                            $item['id_product'];

                        $quantity =
                            $item['quantity'];

                        $price =
                            $item['price'];

                        $item_subtotal =
                            $item['subtotal'];


                        $insert_item = mysqli_query($conn, "

                            INSERT INTO order_items
                            (
                                id_order,
                                id_product,
                                quantity,
                                price,
                                subtotal
                            )

                            VALUES
                            (
                                '$id_order',
                                '$id_product',
                                '$quantity',
                                '$price',
                                '$item_subtotal'
                            )

                        ");


                        if (!$insert_item) {

                            throw new Exception(
                                "Gagal menyimpan item pesanan."
                            );
                        }


                        /* KURANGI STOK */

                        $update_stock = mysqli_query($conn, "

                            UPDATE products

                            SET stock =
                                stock - $quantity

                            WHERE id_product =
                                '$id_product'

                        ");


                        if (!$update_stock) {

                            throw new Exception(
                                "Gagal memperbarui stok."
                            );
                        }
                    }


                    /* =================================
                       INSERT PAYMENT
                    ================================= */

                    $insert_payment = mysqli_query($conn, "

                        INSERT INTO payments
                        (
                            id_order,
                            payment_method,
                            payment_status
                        )

                        VALUES
                        (
                            '$id_order',
                            '$payment_method',
                            'Pending'
                        )

                    ");


                    if (!$insert_payment) {

                        throw new Exception(
                            "Gagal membuat pembayaran."
                        );
                    }


                    /* =================================
                       KOSONGKAN CART
                    ================================= */

                    $clear_cart = mysqli_query($conn, "

                        DELETE FROM cart_items

                        WHERE id_cart =
                            '$id_cart'

                    ");


                    if (!$clear_cart) {

                        throw new Exception(
                            "Gagal mengosongkan keranjang."
                        );
                    }


                    /* =================================
                       NOTIFIKASI PESANAN BARU
                       UNTUK ADMIN
                    ================================= */

                    $admin_query = mysqli_query($conn, "

                        SELECT id_user

                        FROM users

                        WHERE role = 'admin'

                    ");


                    if ($admin_query) {

                        $notif_title =
                            "Pesanan Baru";

                        $notif_message =
                            "Ada pesanan baru dengan nomor Order #" .
                            $id_order .
                            " yang perlu diproses.";


                        while (
                            $admin =
                            mysqli_fetch_assoc($admin_query)
                        ) {

                            $admin_id =
                                $admin['id_user'];


                            $insert_notification =
                                mysqli_query($conn, "

                                INSERT INTO notifications
                                (
                                    id_user,
                                    title,
                                    message,
                                    is_read
                                )

                                VALUES
                                (
                                    '$admin_id',
                                    '$notif_title',
                                    '$notif_message',
                                    0
                                )

                            ");


                            if (!$insert_notification) {

                                throw new Exception(
                                    "Gagal membuat notifikasi admin."
                                );
                            }
                        }
                    }


                    /* =================================
                       SIMPAN SEMUA PERUBAHAN
                    ================================= */

                    mysqli_commit($conn);


                    $_SESSION['last_order_id'] =
                        $id_order;


                    header(
                        "Location: payment.php?id_order=" .
                        $id_order
                    );

                    exit;


                } catch (Exception $e) {

                    mysqli_rollback($conn);

                    $message =
                        $e->getMessage();
                }
            }
        }
    }
}


/* =========================================
   AMBIL ALAMAT
========================================= */

$addresses = mysqli_query($conn, "

    SELECT *

    FROM addresses

    WHERE id_user =
        '$id_user'

    ORDER BY
        is_default DESC,
        id_address DESC

");


/* =========================================
   AMBIL CART UNTUK DITAMPILKAN
========================================= */

$cart_display = mysqli_query($conn, "

    SELECT
        ci.quantity,
        ci.price,
        p.product_name,
        pi.image

    FROM cart_items ci

    JOIN products p
        ON ci.id_product =
            p.id_product

    LEFT JOIN product_images pi
        ON p.id_product =
            pi.id_product

    WHERE ci.id_cart =
        '$id_cart'

");


$subtotal_display = 0;

$cart_products = [];


while (
    $item =
    mysqli_fetch_assoc($cart_display)
) {

    $item_subtotal =
        $item['quantity'] *
        $item['price'];

    $subtotal_display +=
        $item_subtotal;

    $cart_products[] =
        $item;
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

    <title>Checkout - ZCV Petshop</title>

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
            padding: 20px;
            padding-bottom: 40px;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .back {
            font-size: 28px;
            text-decoration: none;
            color: #333;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
        }

        .section {
            background: white;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .section-title {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* =========================================
           PRODUCT
        ========================================= */

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
            width: 65px;
            height: 65px;
            border-radius: 12px;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .product-qty {
            color: #777;
            font-size: 13px;
        }

        .product-price {
            font-weight: bold;
            color: #7548e8;
            font-size: 14px;
            white-space: nowrap;
        }

        /* =========================================
           ALAMAT PENGIRIMAN
        ========================================= */

        .address-option {
            display: block;
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .address-option:hover {
            border-color: #cfc0ff;
        }

        .address-option:has(input:checked) {
            border-color: #7548e8;
            background: #faf8ff;
        }

        .address-header {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            margin-bottom: 8px;
        }

        .address-header input {
            margin: 0;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .recipient {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            line-height: 1.3;
        }

        .default {
            display: inline-block;
            margin-left: 3px;
            background: #eee5ff;
            color: #7548e8;
            padding: 3px 7px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: bold;
            white-space: nowrap;
        }

        .address-text {
            font-size: 13px;
            line-height: 1.6;
            color: #666;
            padding-left: 24px;
        }

        .address-phone {
            margin-bottom: 3px;
            color: #555;
        }

        .address-detail {
            margin-bottom: 3px;
        }

        /* =========================================
           PAYMENT & SHIPPING
        ========================================= */

        .payment-option,
        .shipping-option {
            display: block;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 13px;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .payment-option:has(input:checked),
        .shipping-option:has(input:checked) {
            border-color: #7548e8;
            background: #faf8ff;
        }

        .payment-option input,
        .shipping-option input {
            margin-right: 8px;
        }

        /* =========================================
           RINGKASAN
        ========================================= */

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 17px;
            font-weight: bold;
        }

        .total-price {
            color: #7548e8;
        }

        /* =========================================
           MESSAGE
        ========================================= */

        .message {
            background: #ffe8e8;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* =========================================
           BUTTON
        ========================================= */

        .checkout-btn {
            width: 100%;
            border: none;
            background: #7548e8;
            color: white;
            padding: 15px;
            border-radius: 13px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .checkout-btn:hover {
            opacity: 0.9;
        }

        /* =========================================
           NO ADDRESS
        ========================================= */

        .no-address {
            text-align: center;
            color: #777;
            padding: 10px;
        }

        .add-address {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #7548e8;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

    </style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <a href="cart.php" class="back">
            ‹
        </a>

        <h2>
            Checkout
        </h2>

    </div>


    <?php if ($message != ""): ?>

        <div class="message">
            <?= htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- ITEM YANG DIBELI -->

    <div class="section">

        <div class="section-title">
            Item yang Dibeli
        </div>

        <?php if (count($cart_products) > 0): ?>

            <?php foreach ($cart_products as $item): ?>

                <div class="product">

                    <?php if (!empty($item['image'])): ?>

                        <img
                            src="assets/images/<?= htmlspecialchars($item['image']); ?>"
                            class="product-image"
                            alt="<?= htmlspecialchars($item['product_name']); ?>"
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
                            $item['quantity'] *
                            $item['price'],
                            0,
                            ',',
                            '.'
                        ); ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="no-address">
                Keranjang kosong.
            </div>

        <?php endif; ?>

    </div>


    <!-- FORM CHECKOUT -->

    <form method="POST">


        <!-- ALAMAT -->

        <div class="section">

            <div class="section-title">
                Alamat Pengiriman
            </div>


            <?php if (mysqli_num_rows($addresses) > 0): ?>

                <?php while ($address = mysqli_fetch_assoc($addresses)): ?>

                    <label class="address-option">

                        <div class="address-header">

                            <input
                                type="radio"
                                name="id_address"
                                value="<?= $address['id_address']; ?>"
                                <?= $address['is_default'] == 1
                                    ? 'checked'
                                    : ''; ?>
                                required
                            >

                            <span class="recipient">
                                <?= htmlspecialchars(
                                    $address['recipient_name']
                                ); ?>
                            </span>

                            <?php if ($address['is_default'] == 1): ?>

                                <span class="default">
                                    UTAMA
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="address-text">

                            <div class="address-phone">
                                <?= htmlspecialchars(
                                    $address['phone']
                                ); ?>
                            </div>

                            <div class="address-detail">
                                <?= htmlspecialchars(
                                    $address['address']
                                ); ?>
                            </div>

                            <div>
                                <?= htmlspecialchars(
                                    $address['city']
                                ); ?>,
                                <?= htmlspecialchars(
                                    $address['province']
                                ); ?>
                                <?= htmlspecialchars(
                                    $address['postal_code']
                                ); ?>
                            </div>

                        </div>

                    </label>

                <?php endwhile; ?>


                <a
                    href="address.php"
                    class="add-address"
                >
                    + Kelola Alamat
                </a>


            <?php else: ?>

                <div class="no-address">

                    Belum ada alamat pengiriman.

                </div>

                <a
                    href="address.php"
                    class="add-address"
                >
                    + Tambah Alamat
                </a>

            <?php endif; ?>

        </div>


        <!-- METODE PEMBAYARAN -->

        <div class="section">

            <div class="section-title">
                Metode Pembayaran
            </div>


            <label class="payment-option">

                <input
                    type="radio"
                    name="payment_method"
                    value="Virtual Account"
                    checked
                    required
                >

                Transfer Virtual Account

            </label>


            <label class="payment-option">

                <input
                    type="radio"
                    name="payment_method"
                    value="Cash on Delivery"
                >

                Cash on Delivery

            </label>


            <label class="payment-option">

                <input
                    type="radio"
                    name="payment_method"
                    value="Transfer Bank"
                >

                Transfer Bank

            </label>

        </div>


        <!-- METODE PENGIRIMAN -->

        <div class="section">

            <div class="section-title">
                Metode Pengiriman
            </div>


            <label class="shipping-option">

                <input
                    type="radio"
                    name="shipping_method"
                    value="Gojek"
                    checked
                    required
                >

                Gojek — Rp 10.000

            </label>


            <label class="shipping-option">

                <input
                    type="radio"
                    name="shipping_method"
                    value="Grab"
                >

                Grab — Rp 10.000

            </label>


            <label class="shipping-option">

                <input
                    type="radio"
                    name="shipping_method"
                    value="JNE"
                >

                JNE — Rp 15.000

            </label>


            <label class="shipping-option">

                <input
                    type="radio"
                    name="shipping_method"
                    value="J&T"
                >

                J&T — Rp 15.000

            </label>


            <label class="shipping-option">

                <input
                    type="radio"
                    name="shipping_method"
                    value="AnterAja"
                >

                AnterAja — Rp 12.000

            </label>


            <label class="shipping-option">

                <input
                    type="radio"
                    name="shipping_method"
                    value="SiCepat"
                >

                SiCepat — Rp 12.000

            </label>

        </div>


        <!-- RINGKASAN -->

        <div class="section">

            <div class="section-title">
                Ringkasan Pembayaran
            </div>


            <div class="summary-row">

                <span>
                    Sub Total
                </span>

                <span>
                    Rp <?= number_format(
                        $subtotal_display,
                        0,
                        ',',
                        '.'
                    ); ?>
                </span>

            </div>


            <div class="summary-row">

                <span>
                    Ongkos Kirim
                </span>

                <span>
                    Mulai Rp 10.000
                </span>

            </div>


            <div class="summary-row total">

                <span>
                    Total
                </span>

                <span class="total-price">
                    Dihitung saat pembayaran
                </span>

            </div>

        </div>


        <!-- BAYAR -->

        <?php if (
            count($cart_products) > 0 &&
            mysqli_num_rows($addresses) > 0
        ): ?>

            <button
                type="submit"
                name="checkout"
                class="checkout-btn"
            >
                BAYAR SEKARANG
            </button>

        <?php endif; ?>


    </form>

</div>

</body>

</html>