<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

if (!isset($_GET['id_order'])) {
    die("Order tidak ditemukan.");
}

$id_order = (int) $_GET['id_order'];

/* =========================
   CEK ORDER MILIK USER
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT id_order
    FROM orders
    WHERE id_order = ? AND id_user = ?
");

mysqli_stmt_bind_param($stmt, "ii", $id_order, $id_user);
mysqli_stmt_execute($stmt);
$result_order = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result_order) == 0) {
    die("Order tidak ditemukan atau bukan milik Anda.");
}

/* =========================
   PROSES SIMPAN REVIEW
========================= */
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_product = (int) $_POST['id_product'];
    $rating = (int) $_POST['rating'];
    $review = trim($_POST['review']);

    // Validasi rating
    if ($rating < 1 || $rating > 5) {
        $error = "Rating harus antara 1 sampai 5.";
    } else {

        // Cek apakah produk memang ada di order tersebut
        $stmt = mysqli_prepare($conn, "
            SELECT oi.id_product
            FROM order_items oi
            WHERE oi.id_order = ? AND oi.id_product = ?
        ");

        mysqli_stmt_bind_param($stmt, "ii", $id_order, $id_product);
        mysqli_stmt_execute($stmt);
        $result_product = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result_product) == 0) {

            $error = "Produk tidak ditemukan dalam pesanan.";

        } else {

            // Cek apakah sudah pernah review produk ini pada order ini
            $stmt = mysqli_prepare($conn, "
                SELECT id_review
                FROM reviews
                WHERE id_user = ?
                AND id_product = ?
                AND id_order = ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $id_user,
                $id_product,
                $id_order
            );

            mysqli_stmt_execute($stmt);
            $result_review = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result_review) > 0) {

                $error = "Produk ini sudah Anda beri ulasan.";

            } else {

                // Simpan review
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO reviews
                    (id_user, id_product, id_order, rating, review)
                    VALUES (?, ?, ?, ?, ?)
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "iiiis",
                    $id_user,
                    $id_product,
                    $id_order,
                    $rating,
                    $review
                );

                if (mysqli_stmt_execute($stmt)) {
                    $message = "Rating dan ulasan berhasil disimpan!";
                } else {
                    $error = "Gagal menyimpan ulasan.";
                }
            }
        }
    }
}

/* =========================
   AMBIL PRODUK DARI ORDER
========================= */

$stmt = mysqli_prepare($conn, "
    SELECT
        oi.id_product,
        p.product_name,
        p.price,
        pi.image,
        r.rating AS user_rating,
        r.review AS user_review
    FROM order_items oi

    INNER JOIN products p
        ON oi.id_product = p.id_product

    LEFT JOIN product_images pi
        ON p.id_product = pi.id_product

    LEFT JOIN reviews r
        ON r.id_product = oi.id_product
        AND r.id_order = oi.id_order
        AND r.id_user = ?

    WHERE oi.id_order = ?
");

mysqli_stmt_bind_param($stmt, "ii", $id_user, $id_order);
mysqli_stmt_execute($stmt);

$products = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rating & Ulasan - ZCY Petshop</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f5fb;
            color: #333;
        }

        .container {
            width: 90%;
            max-width: 850px;
            margin: 30px auto;
        }

        h1 {
            color: #7548e8;
            text-align: center;
            margin-bottom: 25px;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .product {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .product img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #ddd;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .price {
            color: #7548e8;
            font-weight: bold;
        }

        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 8px;
        }

        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .button {
            display: inline-block;
            border: none;
            background: #7548e8;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            margin-top: 12px;
        }

        .button:hover {
            opacity: 0.9;
        }

        .back {
            background: #777;
        }

        .already {
            background: #f0ebff;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>⭐ Rating & Ulasan</h1>

    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($products) > 0): ?>

        <?php while ($product = mysqli_fetch_assoc($products)): ?>

            <div class="product-card">

                <div class="product">

                    <?php if (!empty($product['image'])): ?>

                        <img
                            src="assets/images/<?= htmlspecialchars($product['image']); ?>"
                            alt="<?= htmlspecialchars($product['product_name']); ?>"
                        >

                    <?php else: ?>

                        <div style="
                            width:100px;
                            height:100px;
                            background:#eee;
                            border-radius:12px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            No Image
                        </div>

                    <?php endif; ?>

                    <div class="product-info">

                        <div class="product-name">
                            <?= htmlspecialchars($product['product_name']); ?>
                        </div>

                        <div class="price">
                            Rp <?= number_format($product['price'], 0, ',', '.'); ?>
                        </div>

                    </div>

                </div>

                <?php if ($product['user_rating'] !== null): ?>

                    <div class="already">

                        <strong>⭐ Anda sudah memberikan ulasan</strong>

                        <p>
                            Rating:
                            <?= str_repeat("⭐", (int)$product['user_rating']); ?>
                        </p>

                        <?php if (!empty($product['user_review'])): ?>
                            <p>
                                <?= htmlspecialchars($product['user_review']); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="id_product"
                            value="<?= (int)$product['id_product']; ?>"
                        >

                        <label>Rating</label>

                        <select name="rating" required>

                            <option value="">-- Pilih Rating --</option>
                            <option value="5">⭐⭐⭐⭐⭐ - Sangat Baik</option>
                            <option value="4">⭐⭐⭐⭐ - Baik</option>
                            <option value="3">⭐⭐⭐ - Cukup</option>
                            <option value="2">⭐⭐ - Kurang</option>
                            <option value="1">⭐ - Sangat Kurang</option>

                        </select>

                        <label>Ulasan</label>

                        <textarea
                            name="review"
                            placeholder="Tulis pengalaman kamu dengan produk ini..."
                        ></textarea>

                        <button
                            type="submit"
                            class="button"
                        >
                            Kirim Ulasan
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="product-card">
            Tidak ada produk dalam pesanan ini.
        </div>

    <?php endif; ?>

    <a
        href="index.php"
        class="button back"
    >
        ← Kembali ke Shop
    </a>

</div>

</body>
</html>