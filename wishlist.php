<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

/* =========================
   TAMBAH WISHLIST
========================= */

if (isset($_GET['action']) && $_GET['action'] == 'add') {

    $id_product = (int) $_GET['id_product'];

    // Cek apakah user sudah punya wishlist
    $check_wishlist = mysqli_query($conn, "
        SELECT id_wishlist
        FROM wishlists
        WHERE id_user = $id_user
        LIMIT 1
    ");

    if (mysqli_num_rows($check_wishlist) > 0) {

        $wishlist = mysqli_fetch_assoc($check_wishlist);
        $id_wishlist = $wishlist['id_wishlist'];

    } else {

        mysqli_query($conn, "
            INSERT INTO wishlists (id_user)
            VALUES ($id_user)
        ");

        $id_wishlist = mysqli_insert_id($conn);
    }

    // Cek apakah produk sudah ada
    $check_product = mysqli_query($conn, "
        SELECT id_wishlist_item
        FROM wishlist_items
        WHERE id_wishlist = $id_wishlist
        AND id_product = $id_product
    ");

    if (mysqli_num_rows($check_product) == 0) {

        mysqli_query($conn, "
            INSERT INTO wishlist_items
            (id_wishlist, id_product)
            VALUES
            ($id_wishlist, $id_product)
        ");
    }

    header("Location: wishlist.php");
    exit;
}


/* =========================
   HAPUS WISHLIST
========================= */

if (isset($_GET['action']) && $_GET['action'] == 'delete') {

    $id_wishlist_item = (int) $_GET['id'];

    mysqli_query($conn, "
        DELETE FROM wishlist_items
        WHERE id_wishlist_item = $id_wishlist_item
    ");

    header("Location: wishlist.php");
    exit;
}


/* =========================
   AMBIL WISHLIST USER
========================= */

$wishlist = mysqli_query($conn, "
    SELECT
        wi.id_wishlist_item,
        p.id_product,
        p.product_name,
        p.price,
        p.rating,
        pi.image
    FROM wishlist_items wi

    INNER JOIN wishlists w
        ON wi.id_wishlist = w.id_wishlist

    INNER JOIN products p
        ON wi.id_product = p.id_product

    LEFT JOIN product_images pi
        ON p.id_product = pi.id_product

    WHERE w.id_user = $id_user

    ORDER BY wi.id_wishlist_item DESC
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

    <title>Wishlist - ZCY Petshop</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f8f7fc;
            color: #333;
        }

        .app {
            width: 100%;
            max-width: 500px;
            min-height: 100vh;
            margin: auto;
            background: white;
            padding-bottom: 90px;
        }

        .header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .header h2 {
            color: #7548e8;
            font-size: 20px;
        }

        .wishlist {
            padding: 18px;
        }

        .wishlist-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            margin-bottom: 12px;

            border: 1px solid #eee;
            border-radius: 12px;

            background: white;
        }

        .product-image {
            width: 90px;
            height: 90px;

            background: #f7f7f7;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .no-image {
            font-size: 35px;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .rating {
            font-size: 11px;
            color: #f5a623;
            margin-bottom: 6px;
        }

        .price {
            color: #7548e8;
            font-size: 14px;
            font-weight: bold;
        }

        .buttons {
            display: flex;
            gap: 7px;
            margin-top: 10px;
        }

        .button {
            padding: 7px 10px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 10px;
            color: white;
        }

        .cart {
            background: #7548e8;
        }

        .delete {
            background: #e74c3c;
        }

        .empty {
            text-align: center;
            padding: 70px 20px;
            color: #888;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);

            width: 100%;
            max-width: 500px;
            height: 70px;

            background: white;
            border-top: 1px solid #eee;

            display: flex;
            justify-content: space-around;
            align-items: center;

            z-index: 100;
        }

        .nav-item {
            text-decoration: none;
            color: #999;
            font-size: 10px;
            text-align: center;
        }

        .nav-item span {
            display: block;
            font-size: 20px;
            margin-bottom: 3px;
        }

        .nav-item.active {
            color: #7548e8;
        }

    </style>

</head>

<body>

<div class="app">

    <div class="header">
        <h2>♡ My Wishlist</h2>
    </div>


    <div class="wishlist">

        <?php if (mysqli_num_rows($wishlist) > 0): ?>

            <?php while ($item = mysqli_fetch_assoc($wishlist)): ?>

                <div class="wishlist-item">

                    <div class="product-image">

                        <?php if (!empty($item['image'])): ?>

                            <img
                                src="assets/images/<?= htmlspecialchars($item['image']); ?>"
                                alt="<?= htmlspecialchars($item['product_name']); ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                🐱
                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="product-info">

                        <div class="product-name">
                            <?= htmlspecialchars($item['product_name']); ?>
                        </div>

                        <div class="rating">
                            ⭐ <?= number_format($item['rating'], 1); ?>
                        </div>

                        <div class="price">
                            Rp <?= number_format(
                                $item['price'],
                                0,
                                ',',
                                '.'
                            ); ?>
                        </div>


                        <div class="buttons">

                            <a
                                href="cart.php?action=add&id_product=<?= $item['id_product']; ?>"
                                class="button cart"
                            >
                                + CART
                            </a>

                            <a
                                href="wishlist.php?action=delete&id=<?= $item['id_wishlist_item']; ?>"
                                class="button delete"
                                onclick="return confirm('Hapus produk dari wishlist?')"
                            >
                                HAPUS
                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">
                    ♡
                </div>

                <h3>Wishlist masih kosong</h3>

                <p style="margin-top:8px;">
                    Yuk simpan produk favorit kamu!
                </p>

            </div>

        <?php endif; ?>

    </div>


    <!-- BOTTOM NAV -->

    <div class="bottom-nav">

        <a href="index.php" class="nav-item">
            <span>🏠</span>
            Home
        </a>

        <a href="wishlist.php" class="nav-item active">
            <span>♡</span>
            Wishlist
        </a>

        <a href="cart.php" class="nav-item">
            <span>🛒</span>
            Cart
        </a>

        <a href="#" class="nav-item">
            <span>🔔</span>
            Notification
        </a>

        <a href="#" class="nav-item">
            <span>👤</span>
            Profile
        </a>

    </div>

</div>

</body>
</html>