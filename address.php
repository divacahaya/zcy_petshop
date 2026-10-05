<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$message = "";

/* =========================
   TAMBAH ALAMAT
========================= */
if (isset($_POST['save_address'])) {

    $recipient_name = mysqli_real_escape_string($conn, $_POST['recipient_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $province = mysqli_real_escape_string($conn, $_POST['province']);
    $postal_code = mysqli_real_escape_string($conn, $_POST['postal_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    /* Kalau dijadikan default, hapus default sebelumnya */
    if ($is_default == 1) {
        mysqli_query($conn, "
            UPDATE addresses
            SET is_default = 0
            WHERE id_user = '$id_user'
        ");
    }

    /* Kalau belum punya alamat sama sekali,
       otomatis jadikan default */
    $check_address = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM addresses
        WHERE id_user = '$id_user'
    ");

    $address_data = mysqli_fetch_assoc($check_address);

    if ($address_data['total'] == 0) {
        $is_default = 1;
    }

    $query = mysqli_query($conn, "
        INSERT INTO addresses
        (
            id_user,
            recipient_name,
            phone,
            address,
            city,
            province,
            postal_code,
            is_default
        )
        VALUES
        (
            '$id_user',
            '$recipient_name',
            '$phone',
            '$address',
            '$city',
            '$province',
            '$postal_code',
            '$is_default'
        )
    ");

    if ($query) {
        header("Location: address.php?success=1");
        exit;
    } else {
        $message = "Gagal menyimpan alamat.";
    }
}

/* =========================
   HAPUS ALAMAT
========================= */
if (isset($_GET['delete'])) {

    $id_address = (int) $_GET['delete'];

    mysqli_query($conn, "
        DELETE FROM addresses
        WHERE id_address = '$id_address'
        AND id_user = '$id_user'
    ");

    header("Location: address.php");
    exit;
}

/* =========================
   AMBIL ALAMAT
========================= */
$addresses = mysqli_query($conn, "
    SELECT *
    FROM addresses
    WHERE id_user = '$id_user'
    ORDER BY is_default DESC, id_address DESC
");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Alamat Pengiriman - ZCV Petshop</title>

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
            margin-bottom: 25px;
        }

        .back {
            text-decoration: none;
            font-size: 25px;
            color: #333;
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
        }

        .form-box {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .form-box h3 {
            margin-top: 0;
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            outline: none;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            border-color: #7548e8;
        }

        .default-check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            margin-bottom: 15px;
        }

        .default-check input {
            width: auto;
            margin: 0;
        }

        .save-btn {
            width: 100%;
            border: none;
            padding: 14px;
            background: #7548e8;
            color: white;
            border-radius: 12px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .success {
            background: #e6f7e9;
            color: #267a35;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .address-card {
            background: white;
            padding: 18px;
            border-radius: 18px;
            margin-bottom: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .address-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .recipient {
            font-size: 16px;
            font-weight: bold;
        }

        .default-label {
            background: #eee5ff;
            color: #7548e8;
            padding: 5px 9px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: bold;
        }

        .phone {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .address-text {
            font-size: 14px;
            line-height: 1.5;
            color: #555;
        }

        .delete-btn {
            display: inline-block;
            margin-top: 12px;
            color: #e53935;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 25px;
            text-align: center;
            border-radius: 18px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <a href="index.php" class="back">‹</a>
        <h2>Alamat Pengiriman</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success">
            Alamat berhasil disimpan! ✅
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH ALAMAT -->
    <div class="form-box">

        <h3>Tambah Alamat</h3>

        <?php if ($message != ""): ?>
            <div class="success" style="background:#ffeaea;color:#d32f2f;">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Nama Penerima</label>
            <input
                type="text"
                name="recipient_name"
                placeholder="Contoh: Adiva"
                required
            >

            <label>Nomor HP</label>
            <input
                type="text"
                name="phone"
                placeholder="08xxxxxxxxxx"
                required
            >

            <label>Alamat Lengkap</label>
            <textarea
                name="address"
                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan..."
                required
            ></textarea>

            <label>Kota</label>
            <input
                type="text"
                name="city"
                placeholder="Contoh: Bekasi"
            >

            <label>Provinsi</label>
            <input
                type="text"
                name="province"
                placeholder="Contoh: Jawa Barat"
            >

            <label>Kode Pos</label>
            <input
                type="text"
                name="postal_code"
                placeholder="Contoh: 17121"
            >

            <label class="default-check">
                <input
                    type="checkbox"
                    name="is_default"
                    value="1"
                >
                Jadikan alamat utama
            </label>

            <button
                type="submit"
                name="save_address"
                class="save-btn"
            >
                SIMPAN ALAMAT
            </button>

        </form>

    </div>


    <!-- DAFTAR ALAMAT -->
    <h3>Alamat Saya</h3>

    <?php if (mysqli_num_rows($addresses) > 0): ?>

        <?php while ($row = mysqli_fetch_assoc($addresses)): ?>

            <div class="address-card">

                <div class="address-top">

                    <div class="recipient">
                        <?= htmlspecialchars($row['recipient_name']); ?>
                    </div>

                    <?php if ($row['is_default'] == 1): ?>
                        <span class="default-label">
                            UTAMA
                        </span>
                    <?php endif; ?>

                </div>

                <div class="phone">
                    <?= htmlspecialchars($row['phone']); ?>
                </div>

                <div class="address-text">
                    <?= htmlspecialchars($row['address']); ?><br>

                    <?php if (!empty($row['city'])): ?>
                        <?= htmlspecialchars($row['city']); ?>,
                    <?php endif; ?>

                    <?php if (!empty($row['province'])): ?>
                        <?= htmlspecialchars($row['province']); ?>
                    <?php endif; ?>

                    <?php if (!empty($row['postal_code'])): ?>
                        <?= htmlspecialchars($row['postal_code']); ?>
                    <?php endif; ?>
                </div>

                <a
                    href="address.php?delete=<?= $row['id_address']; ?>"
                    class="delete-btn"
                    onclick="return confirm('Yakin ingin menghapus alamat ini?')"
                >
                    Hapus Alamat
                </a>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            Belum ada alamat pengiriman.
        </div>

    <?php endif; ?>

</div>

</body>
</html>