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
// UPDATE STATUS PESANAN
// ===============================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_status'])
) {

    $id_order = (int) $_POST['id_order'];
    $status = $_POST['status'];

    $allowed_status = [
        'Pending',
        'Paid',
        'Processing',
        'Shipped',
        'Completed',
        'Cancelled'
    ];


    if (in_array($status, $allowed_status)) {


        // ===============================
        // AMBIL STATUS LAMA + ID USER
        // ===============================

        $check_order = $conn->prepare("
            SELECT
                id_user,
                status
            FROM orders
            WHERE id_order = ?
            LIMIT 1
        ");

        $check_order->bind_param(
            "i",
            $id_order
        );

        $check_order->execute();

        $order_data = $check_order->get_result()->fetch_assoc();

        $check_order->close();


        if ($order_data) {

            $id_user = $order_data['id_user'];
            $old_status = $order_data['status'];


            // ===============================
            // UPDATE HANYA JIKA STATUS BERUBAH
            // ===============================

            if ($old_status !== $status) {


                $stmt = $conn->prepare("
                    UPDATE orders
                    SET status = ?
                    WHERE id_order = ?
                ");

                $stmt->bind_param(
                    "si",
                    $status,
                    $id_order
                );

                $stmt->execute();

                $update_success = $stmt->affected_rows >= 0;

                $stmt->close();


                // ===============================
                // NOTIFIKASI UNTUK USER
                // ===============================

                if ($update_success) {

                    $notif_title = "";
                    $notif_message = "";


                    // ===============================
                    // PESAN BERDASARKAN STATUS
                    // ===============================

                    switch ($status) {

                        case 'Pending':

                            $notif_title =
                                "Pesanan Menunggu";

                            $notif_message =
                                "Pesanan #" .
                                $id_order .
                                " sedang menunggu proses.";

                            break;


                        case 'Paid':

                            $notif_title =
                                "Pembayaran Dikonfirmasi";

                            $notif_message =
                                "Pembayaran untuk Pesanan #" .
                                $id_order .
                                " telah dikonfirmasi.";

                            break;


                        case 'Processing':

                            $notif_title =
                                "Pesanan Diproses";

                            $notif_message =
                                "Pesanan #" .
                                $id_order .
                                " sedang diproses oleh admin.";

                            break;


                        case 'Shipped':

                            $notif_title =
                                "Pesanan Dikirim";

                            $notif_message =
                                "Pesanan #" .
                                $id_order .
                                " sedang dalam perjalanan.";

                            break;


                        case 'Completed':

                            $notif_title =
                                "Pesanan Selesai";

                            $notif_message =
                                "Pesanan #" .
                                $id_order .
                                " telah selesai. Terima kasih telah berbelanja di ZCY Petshop.";

                            break;


                        case 'Cancelled':

                            $notif_title =
                                "Pesanan Dibatalkan";

                            $notif_message =
                                "Pesanan #" .
                                $id_order .
                                " telah dibatalkan oleh admin.";

                            break;
                    }


                    // ===============================
                    // INSERT NOTIFIKASI
                    // ===============================

                    if (
                        $notif_title !== ""
                        && $notif_message !== ""
                    ) {

                        $notif_stmt = $conn->prepare("
                            INSERT INTO notifications
                            (
                                id_user,
                                title,
                                message,
                                is_read
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                0
                            )
                        ");

                        $notif_stmt->bind_param(
                            "iss",
                            $id_user,
                            $notif_title,
                            $notif_message
                        );

                        $notif_stmt->execute();

                        $notif_stmt->close();
                    }

                }

            }

        }

    }


    // ===============================
    // KEMBALI KE HALAMAN PESANAN
    // ===============================

    header("Location: admin_orders.php");
    exit;
}


// ===============================
// FILTER
// ===============================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$order_status = isset($_GET['order_status'])
    ? $_GET['order_status']
    : '';

$payment_status = isset($_GET['payment_status'])
    ? $_GET['payment_status']
    : '';

$shipping_method = isset($_GET['shipping_method'])
    ? $_GET['shipping_method']
    : '';


// ===============================
// QUERY PESANAN
// ===============================

$sql = "
    SELECT
        o.id_order,
        o.id_user,
        o.order_date,
        o.total_price,
        o.shipping_cost,
        o.status,
        o.shipping_method,

        u.name AS user_name,
        u.email AS user_email,

        p.payment_method,
        p.payment_status

    FROM orders o

    LEFT JOIN users u
        ON o.id_user = u.id_user

    LEFT JOIN payments p
        ON o.id_order = p.id_order

    WHERE 1=1
";

$params = [];
$types = "";


// ===============================
// SEARCH
// ===============================

if ($search !== '') {

    $sql .= "
        AND (
            CAST(o.id_order AS CHAR) LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}


// ===============================
// FILTER STATUS PESANAN
// ===============================

if ($order_status !== '') {

    $sql .= "
        AND o.status = ?
    ";

    $params[] = $order_status;

    $types .= "s";
}


// ===============================
// FILTER STATUS PEMBAYARAN
// ===============================

if ($payment_status !== '') {

    $sql .= "
        AND p.payment_status = ?
    ";

    $params[] = $payment_status;

    $types .= "s";
}


// ===============================
// FILTER SHIPPING
// ===============================

if ($shipping_method !== '') {

    $sql .= "
        AND o.shipping_method = ?
    ";

    $params[] = $shipping_method;

    $types .= "s";
}


$sql .= "
    ORDER BY o.id_order DESC
";


// ===============================
// EXECUTE
// ===============================

$stmt = $conn->prepare($sql);

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}

$stmt->execute();

$orders = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Kelola Pesanan - ZCY Petshop
    </title>


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


        .container {
            max-width: 1400px;
            margin: auto;
            padding: 30px;
        }


        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 20px;
        }


        .top p {
            color: #777;
            margin-top: 5px;
        }


        /* ===============================
           FILTER
        =============================== */

        .filter-box {
            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.06);
        }


        .filter-form {

            display: grid;

            grid-template-columns:
                2fr 1fr 1fr 1fr auto auto;

            gap: 10px;
        }


        .filter-form input,
        .filter-form select {

            width: 100%;

            padding: 11px;

            border: 1px solid #ddd;

            border-radius: 7px;

            font-size: 14px;
        }


        .btn-search {

            border: none;

            background: #0d6efd;

            color: white;

            padding: 11px 18px;

            border-radius: 7px;

            cursor: pointer;
        }


        .btn-reset {

            background: #6c757d;

            color: white;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 7px;
        }


        /* ===============================
           TABLE
        =============================== */

        .table-box {

            background: white;

            border-radius: 10px;

            overflow-x: auto;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.06);
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 1200px;
        }


        th,
        td {

            padding: 13px;

            border-bottom: 1px solid #eee;

            text-align: left;

            vertical-align: middle;
        }


        th {

            background: #f8f9fa;

            font-size: 14px;
        }


        td {

            font-size: 14px;
        }


        .customer strong {
            display: block;
        }


        .customer small {
            color: #777;
        }


        .price {

            font-weight: bold;

            white-space: nowrap;
        }


        /* ===============================
           STATUS
        =============================== */

        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .pending {
            background: #fff3cd;
            color: #856404;
        }


        .paid {
            background: #d1e7dd;
            color: #0f5132;
        }


        .processing {
            background: #cfe2ff;
            color: #084298;
        }


        .shipped {
            background: #cff4fc;
            color: #055160;
        }


        .completed {
            background: #d1e7dd;
            color: #0f5132;
        }


        .cancelled {
            background: #f8d7da;
            color: #842029;
        }


        /* ===============================
           UPDATE
        =============================== */

        .status-form {

            display: flex;

            gap: 5px;

            align-items: center;
        }


        .status-form select {

            padding: 7px;

            border: 1px solid #ddd;

            border-radius: 5px;
        }


        .btn-update {

            border: none;

            background: #198754;

            color: white;

            padding: 7px 10px;

            border-radius: 5px;

            cursor: pointer;
        }


        /* ===============================
           DETAIL
        =============================== */

        .btn-detail {

            display: inline-block;

            background: #0d6efd;

            color: white;

            text-decoration: none;

            padding: 7px 10px;

            border-radius: 5px;

            white-space: nowrap;
        }


        .empty {

            text-align: center;

            padding: 40px;

            color: #777;
        }


        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 1000px) {

            .filter-form {

                grid-template-columns: 1fr 1fr;

            }

        }


        @media (max-width: 600px) {

            .container {
                padding: 20px 15px;
            }


            .filter-form {

                grid-template-columns: 1fr;

            }


            .top {

                flex-direction: column;

                align-items: flex-start;

                gap: 10px;

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
        🐾 ZCY Petshop
    </h1>


    <a
        href="admin_dashboard.php"
        class="back"
    >
        ← Dashboard
    </a>

</div>



<div class="container">


    <!-- ===============================
         TITLE
    =============================== -->

    <div class="top">

        <div>

            <h2>
                Kelola Pesanan
            </h2>

            <p>
                Lihat, cari, filter, dan update status pesanan.
            </p>

        </div>

    </div>



    <!-- ===============================
         FILTER
    =============================== -->

    <div class="filter-box">

        <form
            method="GET"
            action="admin_orders.php"
            class="filter-form"
        >


            <!-- SEARCH -->

            <input
                type="text"
                name="search"
                placeholder="🔍 ID pesanan / nama / email..."
                value="<?= htmlspecialchars($search) ?>"
            >


            <!-- STATUS ORDER -->

            <select name="order_status">

                <option value="">
                    Semua Status Pesanan
                </option>


                <option
                    value="Pending"
                    <?= $order_status === 'Pending'
                        ? 'selected'
                        : '' ?>
                >
                    Pending
                </option>


                <option
                    value="Paid"
                    <?= $order_status === 'Paid'
                        ? 'selected'
                        : '' ?>
                >
                    Paid
                </option>


                <option
                    value="Processing"
                    <?= $order_status === 'Processing'
                        ? 'selected'
                        : '' ?>
                >
                    Processing
                </option>


                <option
                    value="Shipped"
                    <?= $order_status === 'Shipped'
                        ? 'selected'
                        : '' ?>
                >
                    Shipped
                </option>


                <option
                    value="Completed"
                    <?= $order_status === 'Completed'
                        ? 'selected'
                        : '' ?>
                >
                    Completed
                </option>


                <option
                    value="Cancelled"
                    <?= $order_status === 'Cancelled'
                        ? 'selected'
                        : '' ?>
                >
                    Cancelled
                </option>

            </select>



            <!-- STATUS PAYMENT -->

            <select name="payment_status">

                <option value="">
                    Semua Pembayaran
                </option>


                <option
                    value="Pending"
                    <?= $payment_status === 'Pending'
                        ? 'selected'
                        : '' ?>
                >
                    Pending
                </option>


                <option
                    value="Paid"
                    <?= $payment_status === 'Paid'
                        ? 'selected'
                        : '' ?>
                >
                    Paid
                </option>


                <option
                    value="Failed"
                    <?= $payment_status === 'Failed'
                        ? 'selected'
                        : '' ?>
                >
                    Failed
                </option>


                <option
                    value="Refunded"
                    <?= $payment_status === 'Refunded'
                        ? 'selected'
                        : '' ?>
                >
                    Refunded
                </option>

            </select>



            <!-- SHIPPING -->

            <select name="shipping_method">

                <option value="">
                    Semua Pengiriman
                </option>


                <option
                    value="Gojek"
                    <?= $shipping_method === 'Gojek'
                        ? 'selected'
                        : '' ?>
                >
                    Gojek
                </option>


                <option
                    value="Grab"
                    <?= $shipping_method === 'Grab'
                        ? 'selected'
                        : '' ?>
                >
                    Grab
                </option>


                <option
                    value="JNE"
                    <?= $shipping_method === 'JNE'
                        ? 'selected'
                        : '' ?>
                >
                    JNE
                </option>


                <option
                    value="J&T"
                    <?= $shipping_method === 'J&T'
                        ? 'selected'
                        : '' ?>
                >
                    J&T
                </option>


                <option
                    value="AnterAja"
                    <?= $shipping_method === 'AnterAja'
                        ? 'selected'
                        : '' ?>
                >
                    AnterAja
                </option>


                <option
                    value="SiCepat"
                    <?= $shipping_method === 'SiCepat'
                        ? 'selected'
                        : '' ?>
                >
                    SiCepat
                </option>

            </select>



            <button
                type="submit"
                class="btn-search"
            >
                Cari
            </button>


            <a
                href="admin_orders.php"
                class="btn-reset"
            >
                Reset
            </a>

        </form>

    </div>



    <!-- ===============================
         TABLE
    =============================== -->

    <div class="table-box">

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Customer</th>

                    <th>Tanggal</th>

                    <th>Pembayaran</th>

                    <th>Pengiriman</th>

                    <th>Total</th>

                    <th>Status</th>

                    <th>Detail</th>

                    <th>Update Status</th>

                </tr>

            </thead>


            <tbody>


                <?php if ($orders->num_rows > 0): ?>


                    <?php while (
                        $order = $orders->fetch_assoc()
                    ): ?>


                        <tr>


                            <!-- ID -->

                            <td>
                                #<?= $order['id_order'] ?>
                            </td>



                            <!-- CUSTOMER -->

                            <td class="customer">

                                <strong>

                                    <?= htmlspecialchars(
                                        $order['user_name'] ?? '-'
                                    ) ?>

                                </strong>


                                <small>

                                    <?= htmlspecialchars(
                                        $order['user_email'] ?? '-'
                                    ) ?>

                                </small>

                            </td>



                            <!-- DATE -->

                            <td>

                                <?= date(
                                    'd-m-Y H:i',
                                    strtotime(
                                        $order['order_date']
                                    )
                                ) ?>

                            </td>



                            <!-- PAYMENT -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $order['payment_method'] ?? '-'
                                    ) ?>

                                </strong>


                                <br>


                                <span class="status
                                    <?= strtolower(
                                        $order['payment_status']
                                        ?? 'pending'
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $order['payment_status']
                                        ?? 'Pending'
                                    ) ?>

                                </span>

                            </td>



                            <!-- SHIPPING -->

                            <td>

                                <?= htmlspecialchars(
                                    $order['shipping_method']
                                    ?? '-'
                                ) ?>


                                <br>


                                <small>

                                    Rp <?= number_format(
                                        $order['shipping_cost'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                </small>

                            </td>



                            <!-- TOTAL -->

                            <td class="price">

                                Rp <?= number_format(
                                    $order['total_price']
                                    + $order['shipping_cost'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span class="status
                                    <?= strtolower(
                                        $order['status']
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $order['status']
                                    ) ?>

                                </span>

                            </td>



                            <!-- DETAIL -->

                            <td>

                                <a
                                    href="admin_order_detail.php?id=<?= $order['id_order'] ?>"
                                    class="btn-detail"
                                >
                                    👁️ Detail
                                </a>

                            </td>



                            <!-- UPDATE STATUS -->

                            <td>

                                <form
                                    method="POST"
                                    class="status-form"
                                >

                                    <input
                                        type="hidden"
                                        name="id_order"
                                        value="<?= $order['id_order'] ?>"
                                    >


                                    <select name="status">

                                        <option
                                            value="Pending"
                                            <?= $order['status']
                                                === 'Pending'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Pending
                                        </option>


                                        <option
                                            value="Paid"
                                            <?= $order['status']
                                                === 'Paid'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Paid
                                        </option>


                                        <option
                                            value="Processing"
                                            <?= $order['status']
                                                === 'Processing'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Processing
                                        </option>


                                        <option
                                            value="Shipped"
                                            <?= $order['status']
                                                === 'Shipped'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Shipped
                                        </option>


                                        <option
                                            value="Completed"
                                            <?= $order['status']
                                                === 'Completed'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Completed
                                        </option>


                                        <option
                                            value="Cancelled"
                                            <?= $order['status']
                                                === 'Cancelled'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Cancelled
                                        </option>

                                    </select>


                                    <button
                                        type="submit"
                                        name="update_status"
                                        class="btn-update"
                                    >
                                        Update
                                    </button>

                                </form>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="9"
                            class="empty"
                        >

                            🛒 Pesanan tidak ditemukan.

                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>

    </div>

</div>


</body>

</html>