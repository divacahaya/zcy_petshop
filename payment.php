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

$message = "";
$success = "";


/* =========================================
   PROSES KONFIRMASI PEMBAYARAN
========================================= */

if (isset($_POST['confirm_payment'])) {

    $id_payment = (int) $_POST['id_payment'];

    /* CEK PAYMENT MILIK USER */
    $payment_check = mysqli_query($conn, "
        SELECT
            p.id_payment,
            p.id_order,
            p.payment_method,
            p.payment_status
        FROM payments p
        JOIN orders o
            ON p.id_order = o.id_order
        WHERE p.id_payment = '$id_payment'
        AND p.id_order = '$id_order'
        AND o.id_user = '$id_user'
        LIMIT 1
    ");

    if (mysqli_num_rows($payment_check) == 0) {

        $message = "Data pembayaran tidak ditemukan.";

    } else {

        $payment = mysqli_fetch_assoc($payment_check);

        /* =====================================
           CEK FILE
        ===================================== */

        if (
            !isset($_FILES['payment_proof']) ||
            $_FILES['payment_proof']['error'] != 0
        ) {

            $message = "Silakan upload bukti pembayaran.";

        } else {

            $file = $_FILES['payment_proof'];

            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];

            $extension = strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );

            /* FORMAT YANG DIIZINKAN */

            $allowed = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (!in_array($extension, $allowed)) {

                $message =
                    "Format file harus JPG, JPEG, PNG, atau WEBP.";

            } elseif ($file_size > 2 * 1024 * 1024) {

                $message =
                    "Ukuran file maksimal 2 MB.";

            } else {

                /* =====================================
                   BUAT FOLDER UPLOAD
                ===================================== */

                $upload_dir = "assets/images/payment/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                /* =====================================
                   NAMA FILE BARU
                ===================================== */

                $new_name =
                    "payment_" .
                    $id_order .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $upload_path =
                    $upload_dir . $new_name;

                /* =====================================
                   PINDAHKAN FILE
                ===================================== */

                if (move_uploaded_file(
                    $file_tmp,
                    $upload_path
                )) {

                    /* =================================
                       UPDATE PAYMENT
                    ================================= */

                    $update_payment = mysqli_query($conn, "
                        UPDATE payments
                        SET
                            payment_proof = '$new_name',
                            payment_status = 'Paid',
                            payment_date = NOW()
                        WHERE id_payment = '$id_payment'
                    ");

                    /* =================================
                       UPDATE ORDER
                    ================================= */

                    $update_order = mysqli_query($conn, "
                        UPDATE orders
                        SET status = 'Paid'
                        WHERE id_order = '$id_order'
                        AND id_user = '$id_user'
                    ");


                    /* =================================
                       CEK UPDATE
                    ================================= */

                    if (
                        $update_payment &&
                        $update_order
                    ) {

                        /* =================================
                           NOTIFIKASI PEMBAYARAN KE ADMIN
                        ================================= */

                        $admin_query = mysqli_query($conn, "
                            SELECT id_user
                            FROM users
                            WHERE role = 'admin'
                        ");

                        if ($admin_query) {

                            $notif_title = "Pembayaran Baru";

                            $notif_message =
                                "Ada pembayaran baru untuk Order #" .
                                $id_order .
                                " yang perlu diverifikasi.";

                            while (
                                $admin = mysqli_fetch_assoc(
                                    $admin_query
                                )
                            ) {

                                $admin_id =
                                    $admin['id_user'];

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
                            }
                        }


                        /* =================================
                           PESAN BERHASIL
                        ================================= */

                        $success =
                            "Pembayaran berhasil dikonfirmasi!";

                    } else {

                        $message =
                            "Bukti berhasil diupload, tetapi status pembayaran gagal diperbarui.";
                    }

                } else {

                    $message =
                        "Gagal mengupload bukti pembayaran.";
                }
            }
        }
    }
}


/* =========================================
   AMBIL DATA ORDER
========================================= */

$order_query = mysqli_query($conn, "
    SELECT
        o.id_order,
        o.total_price,
        o.shipping_cost,
        o.status,
        o.shipping_method,
        o.order_date,

        p.id_payment,
        p.payment_method,
        p.payment_status,
        p.payment_proof

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
   ITEM ORDER
========================================= */

$order_items = mysqli_query($conn, "
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

    <title>Konfirmasi Pembayaran - ZCV Petshop</title>

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
            font-size: 21px;
        }

        .box {
            background: white;
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .order-number {
            background: #f1ecff;
            color: #7548e8;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            margin-top: 15px;
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
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .product-qty {
            font-size: 13px;
            color: #777;
        }

        .product-price {
            font-weight: bold;
            color: #7548e8;
            font-size: 13px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .row.total {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 17px;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            background: #fff2cc;
            color: #9a6b00;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
        }

        .file-box {
            border: 2px dashed #cfc4ee;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            background: #faf8ff;
            margin-top: 15px;
        }

        .file-box input {
            width: 100%;
            margin-top: 12px;
        }

        .file-info {
            font-size: 12px;
            color: #888;
            margin-top: 10px;
        }

        .confirm-btn {
            width: 100%;
            border: none;
            background: #7548e8;
            color: white;
            padding: 15px;
            border-radius: 13px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }

        .message {
            background: #ffe8e8;
            color: #c62828;
            padding: 13px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .success {
            background: #e5f7e8;
            color: #267a35;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 18px;
            text-align: center;
            font-weight: bold;
        }

        .home-btn {
            display: block;
            text-align: center;
            background: #7548e8;
            color: white;
            text-decoration: none;
            padding: 14px;
            border-radius: 12px;
            margin-top: 15px;
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
            ‹
        </a>

        <h2>
            Konfirmasi Pembayaran
        </h2>

    </div>


    <?php if ($message != ""): ?>

        <div class="message">
            <?= htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if ($success != ""): ?>

        <div class="success">

            <?= htmlspecialchars($success); ?>

            <a
                href="order_success.php?id_order=<?= $id_order; ?>"
                class="home-btn"
            >
                LIHAT PESANAN
            </a>

        </div>

    <?php endif; ?>


    <!-- INFORMASI -->

    <div class="box">

        <div class="title">
            Konfirmasi Pembayaran
        </div>

        <div class="description">

            Setelah kami menerima bukti
            pembayaran anda, pesanan anda
            akan segera kami kirim.

        </div>

        <div class="order-number">

            Pesanan #<?= $id_order; ?>

        </div>

    </div>


    <!-- DETAIL PESANAN -->

    <div class="box">

        <div class="title">
            Detail Pesanan
        </div>


        <?php while ($item = mysqli_fetch_assoc($order_items)): ?>

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


    <!-- PEMBAYARAN -->

    <div class="box">

        <div class="title">
            Pembayaran
        </div>


        <div class="row">

            <span>
                Metode
            </span>

            <strong>
                <?= htmlspecialchars(
                    $order['payment_method']
                ); ?>
            </strong>

        </div>


        <div class="row">

            <span>
                Status
            </span>

            <span class="status">

                <?= htmlspecialchars(
                    $order['payment_status']
                ); ?>

            </span>

        </div>


        <div class="row">

            <span>
                Total
            </span>

            <strong>

                Rp <?= number_format(
                    $order['total_price'],
                    0,
                    ',',
                    '.'
                ); ?>

            </strong>

        </div>

    </div>


    <?php if (
        $order['payment_status'] != 'Paid'
    ): ?>


        <!-- UPLOAD BUKTI -->

        <div class="box">

            <div class="title">
                Upload Bukti Transfer
            </div>

            <div class="description">

                Silakan upload screenshot atau
                foto bukti pembayaran Anda.

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="id_payment"
                    value="<?= $order['id_payment']; ?>"
                >


                <div class="file-box">

                    📄

                    <br><br>

                    Pilih Bukti Pembayaran

                    <input
                        type="file"
                        name="payment_proof"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >

                    <div class="file-info">

                        JPG, JPEG, PNG, WEBP
                        <br>
                        Maksimal 2 MB

                    </div>

                </div>


                <button
                    type="submit"
                    name="confirm_payment"
                    class="confirm-btn"
                >
                    KONFIRMASI
                </button>

            </form>

        </div>


    <?php else: ?>


        <div class="box">

            <div class="success">

                Pembayaran sudah dikonfirmasi! ✅

            </div>

            <a
                href="order_success.php?id_order=<?= $id_order; ?>"
                class="home-btn"
            >
                LIHAT PESANAN
            </a>

        </div>


    <?php endif; ?>


</div>

</body>

</html>