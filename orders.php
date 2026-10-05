<?php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

$id_user = $_SESSION['id_user'];

/* =========================
   AMBIL DATA PESANAN
========================= */

$query = "
    SELECT 
        o.id_order,
        o.order_date,
        o.total_price,
        o.shipping_cost,
        o.status,
        o.shipping_method,
        COUNT(oi.id_order_item) AS total_items
    FROM orders o
    LEFT JOIN order_items oi 
        ON o.id_order = oi.id_order
    WHERE o.id_user = ?
    GROUP BY 
        o.id_order,
        o.order_date,
        o.total_price,
        o.shipping_cost,
        o.status,
        o.shipping_method
    ORDER BY o.order_date DESC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* =========================
   FUNGSI STATUS
========================= */

function statusClass($status)
{
    switch ($status) {
        case "Pending":
            return "pending";

        case "Paid":
            return "paid";

        case "Processing":
            return "processing";

        case "Shipped":
            return "shipped";

        case "Completed":
            return "completed";

        case "Cancelled":
            return "cancelled";

        default:
            return "";
    }
}

function statusText($status)
{
    switch ($status) {
        case "Pending":
            return "Menunggu Pembayaran";

        case "Paid":
            return "Sudah Dibayar";

        case "Processing":
            return "Sedang Diproses";

        case "Shipped":
            return "Sedang Dikirim";

        case "Completed":
            return "Selesai";

        case "Cancelled":
            return "Dibatalkan";

        default:
            return $status;
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pesanan Saya - ZCY Petshop</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            color: #333;
            padding-bottom: 90px;
        }

        .container {
            width: 90%;
            max-width: 800px;
            margin: auto;
        }

        .header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back {
            text-decoration: none;
            font-size: 24px;
            color: #333;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
        }

        .order-card {
            background: white;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: bold;
            font-size: 16px;
        }

        .order-date {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .status {
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .processing {
            background: #ffe5b4;
            color: #8a4b08;
        }

        .shipped {
            background: #dbeafe;
            color: #1e40af;
        }

        .completed {
            background: #d4edda;
            color: #155724;
        }

        .cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-info {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 13px 0;
            margin-bottom: 13px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .label {
            color: #777;
        }

        .value {
            font-weight: bold;
            text-align: right;
        }

        .total {
            font-size: 17px;
        }

        .button {
            display: inline-block;
            width: 100%;
            text-align: center;
            padding: 11px;
            background: #222;
            color: white;
            text-decoration: none;
            border-radius: 9px;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 40px 20px;
            border-radius: 14px;
            text-align: center;
        }

        .empty-icon {
            font-size: 45px;
            margin-bottom: 10px;
        }

        .empty p {
            color: #777;
        }

        .shop-button {
            display: inline-block;
            margin-top: 10px;
            padding: 11px 20px;
            background: #222;
            color: white;
            text-decoration: none;
            border-radius: 9px;
        }

        /* BOTTOM NAV */

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: 100;
        }

        .nav-item {
            text-decoration: none;
            color: #777;
            font-size: 11px;
            text-align: center;
        }

        .nav-item span {
            display: block;
            font-size: 21px;
            margin-bottom: 3px;
        }

        .nav-item.active {
            color: #111;
            font-weight: bold;
        }

    </style>

</head>

<body>


<!-- HEADER -->

<div class="header">

    <div class="container">

        <div class="header-content">

            <a href="profile.php" class="back">←</a>

            <h2>Pesanan Saya</h2>

        </div>

    </div>

</div>


<!-- CONTENT -->

<div class="container">

<?php if (mysqli_num_rows($result) == 0): ?>

    <div class="empty">

        <div class="empty-icon">📦</div>

        <h3>Belum Ada Pesanan</h3>

        <p>
            Kamu belum melakukan pembelian.
        </p>

        <a href="index.php" class="shop-button">
            Belanja Sekarang
        </a>

    </div>

<?php else: ?>


    <?php while ($order = mysqli_fetch_assoc($result)): ?>

        <div class="order-card">


            <!-- ORDER HEADER -->

            <div class="order-top">

                <div>

                    <div class="order-id">

                        Pesanan #<?= $order['id_order']; ?>

                    </div>

                    <div class="order-date">

                        <?= date(
                            "d M Y H:i",
                            strtotime($order['order_date'])
                        ); ?>

                    </div>

                </div>


                <div class="status <?= statusClass($order['status']); ?>">

                    <?= statusText($order['status']); ?>

                </div>

            </div>


            <!-- ORDER INFO -->

            <div class="order-info">


                <div class="info-row">

                    <span class="label">
                        Jumlah Produk
                    </span>

                    <span class="value">

                        <?= $order['total_items']; ?> item

                    </span>

                </div>


                <div class="info-row">

                    <span class="label">
                        Pengiriman
                    </span>

                    <span class="value">

                        <?= htmlspecialchars(
                            $order['shipping_method'] ?? '-'
                        ); ?>

                    </span>

                </div>


                <div class="info-row">

                    <span class="label">
                        Ongkir
                    </span>

                    <span class="value">

                        Rp <?= number_format(
                            $order['shipping_cost'],
                            0,
                            ',',
                            '.'
                        ); ?>

                    </span>

                </div>


                <div class="info-row total">

                    <span class="label">
                        Total
                    </span>

                    <span class="value">

                        Rp <?= number_format(
                            $order['total_price'],
                            0,
                            ',',
                            '.'
                        ); ?>

                    </span>

                </div>


            </div>


            <!-- DETAIL -->

            <a
                href="order_success.php?id_order=<?= $order['id_order']; ?>"
                class="button"
            >
                Lihat Detail Pesanan
            </a>


        </div>

    <?php endwhile; ?>


<?php endif; ?>

</div>


<!-- BOTTOM NAV -->

<div class="bottom-nav">

    <a href="index.php" class="nav-item">

        <span>🏠</span>

        Home

    </a>


    <a href="wishlist.php" class="nav-item">

        <span>♡</span>

        Wishlist

    </a>


    <a href="cart.php" class="nav-item">

        <span>🛒</span>

        Cart

    </a>


    <a href="notification.php" class="nav-item">

        <span>🔔</span>

        Notification

    </a>


    <a href="profile.php" class="nav-item active">

        <span>👤</span>

        Profile

    </a>

</div>


</body>

</html>