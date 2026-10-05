<?php
session_start();
require_once "config/database.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_product = (int) $_GET['id'];

/* =========================
   AMBIL DATA PRODUK
========================= */

$query = mysqli_query($conn, "
    SELECT 
        p.id_product,
        p.product_name,
        p.description,
        p.price,
        p.stock,
        p.rating,
        pi.image
    FROM products p
    LEFT JOIN product_images pi
        ON p.id_product = pi.id_product
    WHERE p.id_product = $id_product
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) == 0) {
    die("Produk tidak ditemukan.");
}

$product = mysqli_fetch_assoc($query);

$image = $product['image'] ?? '';


/* =========================
   RATING & ULASAN PRODUK
========================= */

$stmt_review = mysqli_prepare($conn, "
    SELECT
        r.rating,
        r.review,
        r.created_at,
        u.name
    FROM reviews r
    INNER JOIN users u
        ON r.id_user = u.id_user
    WHERE r.id_product = ?
    ORDER BY r.created_at DESC
");

mysqli_stmt_bind_param(
    $stmt_review,
    "i",
    $id_product
);

mysqli_stmt_execute($stmt_review);

$reviews = mysqli_stmt_get_result($stmt_review);


/* =========================
   HITUNG RATING RATA-RATA
========================= */

$stmt_rating = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total_review,
        AVG(rating) AS average_rating
    FROM reviews
    WHERE id_product = ?
");

mysqli_stmt_bind_param(
    $stmt_rating,
    "i",
    $id_product
);

mysqli_stmt_execute($stmt_rating);

$rating_result = mysqli_stmt_get_result($stmt_rating);

$rating_data = mysqli_fetch_assoc($rating_result);

$total_review = (int) $rating_data['total_review'];

$average_rating = $rating_data['average_rating']
    ? round($rating_data['average_rating'], 1)
    : 0;

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
        Detail Produk - ZCY Petshop
    </title>


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
        }


        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #333;
            text-decoration: none;
        }


        .product-image {
            width: 100%;
            height: 280px;
            object-fit: contain;
            background: #f7f2ea;
            border-radius: 16px;
        }


        h1 {
            font-size: 25px;
            margin-bottom: 8px;
        }


        .price {
            font-size: 24px;
            font-weight: bold;
            color: #e78346;
        }


        .info {
            color: #777;
            font-size: 14px;
        }


        .description {
            line-height: 1.7;
            margin: 20px 0;
        }


        .button {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: #e78346;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }


        .button:hover {
            background: #cf6c32;
        }


        /* =========================
           RATING & ULASAN
        ========================= */

        .review-section {
            margin-top: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #eee;
        }


        .review-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 21px;
        }


        .rating-summary {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }


        .rating-number {
            font-size: 36px;
            font-weight: bold;
            color: #e78346;
        }


        .rating-stars {
            color: #f5b301;
            font-size: 18px;
        }


        .rating-count {
            color: #777;
            font-size: 13px;
            margin-top: 4px;
        }


        .review-item {
            border-bottom: 1px solid #eee;
            padding: 18px 0;
        }


        .review-item:last-child {
            border-bottom: none;
        }


        .review-user {
            font-weight: bold;
            margin-bottom: 6px;
        }


        .review-date {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
            margin-bottom: 10px;
        }


        .review-text {
            color: #444;
            line-height: 1.6;
            margin-top: 8px;
        }


        .no-review {
            color: #777;
            padding: 10px 0;
        }

    </style>

</head>


<body>

<div class="container">


    <!-- =========================
         KEMBALI
    ========================== -->

    <a
        href="index.php"
        class="back"
    >
        ← Kembali ke Beranda
    </a>


    <!-- =========================
         GAMBAR PRODUK
    ========================== -->

    <?php if (!empty($image)): ?>

        <img
            class="product-image"
            src="assets/images/<?= htmlspecialchars($image); ?>"
            alt="<?= htmlspecialchars($product['product_name']); ?>"
        >

    <?php else: ?>

        <div
            class="product-image"
            style="
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:90px;
            "
        >
            🐱
        </div>

    <?php endif; ?>


    <!-- =========================
         NAMA PRODUK
    ========================== -->

    <h1>
        <?= htmlspecialchars($product['product_name']); ?>
    </h1>


    <!-- =========================
         HARGA
    ========================== -->

    <div class="price">

        Rp<?= number_format(
            (float)$product['price'],
            0,
            ',',
            '.'
        ); ?>

    </div>


    <!-- =========================
         INFO PRODUK
    ========================== -->

    <p class="info">

        Stok:
        <?= (int)$product['stock']; ?>

        &nbsp; | &nbsp;

        ⭐
        <?= htmlspecialchars(
            (string)($product['rating'] ?? '0')
        ); ?>

    </p>


    <!-- =========================
         DESKRIPSI
    ========================== -->

    <h3>
        Deskripsi Produk
    </h3>


    <div class="description">

        <?= nl2br(
            htmlspecialchars(
                $product['description']
                ?? 'Belum ada deskripsi produk.'
            )
        ); ?>

    </div>


    <!-- =========================
         BUTTON CART & WISHLIST
    ========================== -->

    <?php if ((int)$product['stock'] > 0): ?>

        <a
            class="button"
            href="cart.php?action=add&id_product=<?= (int)$product['id_product']; ?>"
        >
            + Tambahkan ke Keranjang
        </a>


        <a
            class="button"
            href="wishlist.php?action=add&id_product=<?= (int)$product['id_product']; ?>"
            style="
                margin-top:10px;
                background:#7548e8;
            "
        >
            ♡ Tambahkan ke Wishlist
        </a>

    <?php else: ?>

        <button
            class="button"
            disabled
            style="background:#999;"
        >
            Stok Habis
        </button>

    <?php endif; ?>


    <!-- =========================
         RATING & ULASAN
    ========================== -->

    <div class="review-section">

        <h2>
            ⭐ Rating & Ulasan
        </h2>


        <!-- RATING RATA-RATA -->

        <div class="rating-summary">

            <div class="rating-number">

                <?= number_format(
                    $average_rating,
                    1
                ); ?>

            </div>


            <div>

                <div class="rating-stars">

                    <?php

                    $rounded_rating =
                        round($average_rating);

                    if ($rounded_rating > 0) {

                        echo str_repeat(
                            "⭐",
                            $rounded_rating
                        );

                    } else {

                        echo "Belum ada rating";

                    }

                    ?>

                </div>


                <div class="rating-count">

                    <?= $total_review; ?>
                    ulasan

                </div>

            </div>

        </div>


        <!-- DAFTAR ULASAN -->

        <?php if ($total_review > 0): ?>


            <?php while (
                $review = mysqli_fetch_assoc($reviews)
            ): ?>

                <div class="review-item">


                    <div class="review-user">

                        <?= htmlspecialchars(
                            $review['name']
                        ); ?>

                    </div>


                    <div class="rating-stars">

                        <?= str_repeat(
                            "⭐",
                            (int)$review['rating']
                        ); ?>

                    </div>


                    <div class="review-date">

                        <?= date(
                            'd M Y',
                            strtotime(
                                $review['created_at']
                            )
                        ); ?>

                    </div>


                    <?php if (
                        !empty($review['review'])
                    ): ?>

                        <div class="review-text">

                            <?= nl2br(
                                htmlspecialchars(
                                    $review['review']
                                )
                            ); ?>

                        </div>

                    <?php endif; ?>


                </div>

            <?php endwhile; ?>


        <?php else: ?>


            <div class="no-review">

                Belum ada ulasan untuk produk ini.

            </div>


        <?php endif; ?>


    </div>


</div>

</body>

</html>