<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require_once "config/database.php";

/* =========================
   CEK ID PESANAN
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID pesanan tidak valid.");
}

$id_order = (int) $_GET['id'];

/* =========================
   AMBIL DATA PESANAN
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        o.id_order,
        o.id_user,
        o.id_address,
        o.order_date,
        o.total_price,
        o.shipping_cost,
        o.status,
        o.shipping_method,

        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,

        a.recipient_name,
        a.phone AS recipient_phone,
        a.address,
        a.city,
        a.province,
        a.postal_code,

        p.payment_method,
        p.payment_status,
        p.payment_proof,
        p.payment_date

     FROM orders o

     LEFT JOIN users u
        ON o.id_user = u.id_user

     LEFT JOIN addresses a
        ON o.id_address = a.id_address

     LEFT JOIN payments p
        ON o.id_order = p.id_order

     WHERE o.id_order = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_order
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}


/* =========================
   AMBIL PRODUK PESANAN
========================= */

$stmt_items = mysqli_prepare(
    $conn,
    "SELECT
        oi.id_order_item,
        oi.id_product,
        oi.quantity,
        oi.price,
        oi.subtotal,
        p.product_name

     FROM order_items oi

     LEFT JOIN products p
        ON oi.id_product = p.id_product

     WHERE oi.id_order = ?

     ORDER BY oi.id_order_item ASC"
);

mysqli_stmt_bind_param(
    $stmt_items,
    "i",
    $id_order
);

mysqli_stmt_execute($stmt_items);

$result_items = mysqli_stmt_get_result($stmt_items);

?>


<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Detail Pesanan - ZCY Petshop</title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #333;
        }


        /* =========================
           HEADER
        ========================= */

        .header {
            background: #222;
            color: white;
            padding: 20px 40px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
        }

        .header a {
            color: white;
            text-decoration: none;
        }


        /* =========================
           CONTAINER
        ========================= */

        .container {
            width: 95%;
            max-width: 1100px;
            margin: 35px auto;
        }


        /* =========================
           TOP
        ========================= */

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .top h3 {
            margin: 0;
        }

        .back-btn {
            background: #222;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
        }


        /* =========================
           CARD
        ========================= */

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 12px;
        }


        /* =========================
           INFO
        ========================= */

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
        }

        .info-item {
            line-height: 1.6;
        }

        .label {
            color: #777;
            font-size: 13px;
        }

        .value {
            font-weight: bold;
        }


        /* =========================
           STATUS
        ========================= */

        .status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 20px;
            background: #eee;
            font-size: 13px;
            font-weight: bold;
        }


        /* =========================
           TABLE PRODUK
        ========================= */

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .number {
            text-align: right;
        }

        .product-name {
            font-weight: bold;
        }


        /* =========================
           TOTAL
        ========================= */

        .total-box {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .grand-total {
            font-size: 20px;
            font-weight: bold;
            margin-top: 15px;
        }


        /* =========================
           PAYMENT PROOF
        ========================= */

        .proof {
            margin-top: 15px;
        }

        .proof img {
            max-width: 350px;
            max-height: 450px;
            border-radius: 10px;
            border: 1px solid #ddd;
            display: block;
            margin-top: 10px;
        }

        .no-proof {
            color: #777;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 700px) {

            .header {
                padding: 18px 20px;
            }

            .container {
                width: 92%;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .top {
                gap: 15px;
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<div class="header">

    <h2>📦 Detail Pesanan</h2>

    <a href="admin_orders.php">
        ← Kembali
    </a>

</div>


<div class="container">


    <!-- =========================
         TOP
    ========================= -->

    <div class="top">

        <h3>
            Pesanan #<?= $order['id_order'] ?>
        </h3>

        <a
            href="admin_orders.php"
            class="back-btn"
        >
            ← Daftar Pesanan
        </a>

    </div>


    <!-- =========================
         INFORMASI PESANAN
    ========================= -->

    <div class="card">

        <h3>📋 Informasi Pesanan</h3>

        <div class="info-grid">

            <div class="info-item">

                <div class="label">
                    Nomor Pesanan
                </div>

                <div class="value">
                    #<?= $order['id_order'] ?>
                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Tanggal Pesanan
                </div>

                <div class="value">

                    <?= date(
                        'd-m-Y H:i',
                        strtotime(
                            $order['order_date']
                        )
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Status Pesanan
                </div>

                <div class="value">

                    <span class="status">

                        <?= htmlspecialchars(
                            $order['status']
                        ) ?>

                    </span>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Metode Pengiriman
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['shipping_method'] ?? '-'
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         DATA PELANGGAN
    ========================= -->

    <div class="card">

        <h3>👤 Data Pelanggan</h3>

        <div class="info-grid">

            <div class="info-item">

                <div class="label">
                    Nama
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['user_name'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Email
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['user_email'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Nomor HP
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['user_phone'] ?? '-'
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         ALAMAT PENGIRIMAN
    ========================= -->

    <div class="card">

        <h3>📍 Alamat Pengiriman</h3>

        <div class="info-grid">

            <div class="info-item">

                <div class="label">
                    Nama Penerima
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['recipient_name'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Nomor HP
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['recipient_phone'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Kota
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['city'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Provinsi
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['province'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Kode Pos
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['postal_code'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Alamat Lengkap
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['address'] ?? '-'
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         PRODUK
    ========================= -->

    <div class="card">

        <h3>🛒 Produk yang Dibeli</h3>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Produk</th>

                        <th>Harga</th>

                        <th>Jumlah</th>

                        <th>Subtotal</th>

                    </tr>

                </thead>

                <tbody>

                    <?php

                    $no = 1;

                    $subtotal_produk = 0;

                    ?>

                    <?php if (mysqli_num_rows($result_items) > 0): ?>

                        <?php while (
                            $item = mysqli_fetch_assoc(
                                $result_items
                            )
                        ): ?>

                            <?php

                            $subtotal_produk +=
                                $item['subtotal'];

                            ?>

                            <tr>

                                <td>
                                    <?= $no++ ?>
                                </td>

                                <td class="product-name">

                                    <?= htmlspecialchars(
                                        $item['product_name'] ?? '-'
                                    ) ?>

                                </td>

                                <td>

                                    Rp
                                    <?= number_format(
                                        $item['price'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>

                                <td>

                                    <?= $item['quantity'] ?>

                                </td>

                                <td class="number">

                                    Rp
                                    <?= number_format(
                                        $item['subtotal'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="5"
                                style="text-align:center;"
                            >

                                Tidak ada produk.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- TOTAL -->

        <div class="total-box">

            <div class="total-row">

                <span>
                    Subtotal Produk
                </span>

                <strong>

                    Rp
                    <?= number_format(
                        $subtotal_produk,
                        0,
                        ',',
                        '.'
                    ) ?>

                </strong>

            </div>


            <div class="total-row">

                <span>
                    Biaya Pengiriman
                </span>

                <strong>

                    Rp
                    <?= number_format(
                        $order['shipping_cost'],
                        0,
                        ',',
                        '.'
                    ) ?>

                </strong>

            </div>


            <div class="total-row grand-total">

                <span>
                    Total Pesanan
                </span>

                <span>

                    Rp
                    <?= number_format(
                        $order['total_price'],
                        0,
                        ',',
                        '.'
                    ) ?>

                </span>

            </div>

        </div>

    </div>


    <!-- =========================
         PEMBAYARAN
    ========================= -->

    <div class="card">

        <h3>💳 Informasi Pembayaran</h3>

        <div class="info-grid">

            <div class="info-item">

                <div class="label">
                    Metode Pembayaran
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['payment_method'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Status Pembayaran
                </div>

                <div class="value">

                    <?= htmlspecialchars(
                        $order['payment_status'] ?? '-'
                    ) ?>

                </div>

            </div>


            <div class="info-item">

                <div class="label">
                    Tanggal Pembayaran
                </div>

                <div class="value">

                    <?php if (!empty($order['payment_date'])): ?>

                        <?= date(
                            'd-m-Y H:i',
                            strtotime(
                                $order['payment_date']
                            )
                        ) ?>

                    <?php else: ?>

                        -

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- BUKTI PEMBAYARAN -->

        <div class="proof">

            <div class="label">
                Bukti Pembayaran
            </div>


            <?php if (!empty($order['payment_proof'])): ?>

                <img
                    src="assets/images/payment/<?= htmlspecialchars(
                        $order['payment_proof']
                    ) ?>"
                    alt="Bukti Pembayaran"
                >

            <?php else: ?>

                <div class="no-proof">
                    Belum ada bukti pembayaran.
                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =========================
         KEMBALI
    ========================= -->

    <div style="text-align:center; margin-bottom:40px;">

        <a
            href="admin_orders.php"
            class="back-btn"
        >
            ← Kembali ke Daftar Pesanan
        </a>

    </div>


</div>


</body>

</html>

<?php

mysqli_stmt_close($stmt_items);

?>