<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];


/* =====================================================
   AMBIL / BUAT CART USER
===================================================== */

$stmt = mysqli_prepare($conn, "
    SELECT id_cart
    FROM carts
    WHERE id_user = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $id_cart = $row['id_cart'];

} else {

    $stmt = mysqli_prepare($conn, "
        INSERT INTO carts (id_user)
        VALUES (?)
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    $id_cart = mysqli_insert_id($conn);
}


/* =====================================================
   TAMBAH PRODUK KE CART
===================================================== */

if (isset($_GET['action']) && $_GET['action'] == 'add') {

    $id_product = intval($_GET['id_product']);

    // Cek produk
    $stmt = mysqli_prepare($conn, "
        SELECT id_product, price, stock
        FROM products
        WHERE id_product = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_product);
    mysqli_stmt_execute($stmt);

    $product_result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($product_result);

    if ($product) {

        // Cek apakah produk sudah ada di cart
        $stmt = mysqli_prepare($conn, "
            SELECT id_cart_item, quantity
            FROM cart_items
            WHERE id_cart = ?
            AND id_product = ?
            LIMIT 1
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $id_cart,
            $id_product
        );

        mysqli_stmt_execute($stmt);

        $item_result = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($item_result);


        if ($item) {

            // Tambah quantity
            $new_quantity = $item['quantity'] + 1;

            if ($new_quantity <= $product['stock']) {

                $stmt = mysqli_prepare($conn, "
                    UPDATE cart_items
                    SET quantity = ?
                    WHERE id_cart_item = ?
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "ii",
                    $new_quantity,
                    $item['id_cart_item']
                );

                mysqli_stmt_execute($stmt);
            }

        } else {

            // Produk belum ada → masukkan
            $quantity = 1;
            $price = $product['price'];

            $stmt = mysqli_prepare($conn, "
                INSERT INTO cart_items
                (id_cart, id_product, quantity, price)
                VALUES (?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iiid",
                $id_cart,
                $id_product,
                $quantity,
                $price
            );

            mysqli_stmt_execute($stmt);
        }
    }

    header("Location: cart.php");
    exit;
}


/* =====================================================
   TAMBAH QUANTITY
===================================================== */

if (isset($_GET['action']) && $_GET['action'] == 'plus') {

    $id_cart_item = intval($_GET['id']);

    $stmt = mysqli_prepare($conn, "
        UPDATE cart_items
        SET quantity = quantity + 1
        WHERE id_cart_item = ?
        AND id_cart = ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_cart_item,
        $id_cart
    );

    mysqli_stmt_execute($stmt);

    header("Location: cart.php");
    exit;
}


/* =====================================================
   KURANGI QUANTITY
===================================================== */

if (isset($_GET['action']) && $_GET['action'] == 'minus') {

    $id_cart_item = intval($_GET['id']);

    $stmt = mysqli_prepare($conn, "
        UPDATE cart_items
        SET quantity = quantity - 1
        WHERE id_cart_item = ?
        AND id_cart = ?
        AND quantity > 1
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_cart_item,
        $id_cart
    );

    mysqli_stmt_execute($stmt);

    header("Location: cart.php");
    exit;
}


/* =====================================================
   HAPUS ITEM
===================================================== */

if (isset($_GET['action']) && $_GET['action'] == 'delete') {

    $id_cart_item = intval($_GET['id']);

    $stmt = mysqli_prepare($conn, "
        DELETE FROM cart_items
        WHERE id_cart_item = ?
        AND id_cart = ?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_cart_item,
        $id_cart
    );

    mysqli_stmt_execute($stmt);

    header("Location: cart.php");
    exit;
}


/* =====================================================
   AMBIL ISI CART
===================================================== */

$stmt = mysqli_prepare($conn, "
    SELECT
        ci.id_cart_item,
        ci.quantity,
        ci.price,
        p.product_name,
        p.stock,
        pi.image
    FROM cart_items ci

    JOIN products p
        ON ci.id_product = p.id_product

    LEFT JOIN product_images pi
        ON p.id_product = pi.id_product

    WHERE ci.id_cart = ?

    ORDER BY ci.id_cart_item DESC
");

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_cart
);

mysqli_stmt_execute($stmt);

$cart_result = mysqli_stmt_get_result($stmt);


/* =====================================================
   TOTAL
===================================================== */

$total = 0;

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Cart - ZCY Petshop</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #eeeeee;
}

.app {
    width: 100%;
    max-width: 500px;
    min-height: 100vh;
    margin: auto;
    background: #ffffff;
    padding-bottom: 100px;
}


/* HEADER */

.header {
    padding: 20px;

    display: flex;
    align-items: center;
    gap: 15px;

    border-bottom: 1px solid #eeeeee;
}

.back {
    text-decoration: none;
    font-size: 24px;
    color: #333;
}

.header h2 {
    font-size: 19px;
}


/* CART */

.cart-container {
    padding: 18px;
}


/* ITEM */

.cart-item {

    display: flex;

    gap: 12px;

    padding: 12px;

    margin-bottom: 12px;

    border: 1px solid #eeeeee;

    border-radius: 12px;

    background: white;
}


.product-image {

    width: 80px;
    height: 80px;

    flex-shrink: 0;

    border-radius: 10px;

    background: #f5f5f5;

    display: flex;

    align-items: center;
    justify-content: center;

    overflow: hidden;
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

    margin-bottom: 7px;
}

.price {

    color: #7548e8;

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 10px;
}


/* QUANTITY */

.quantity {

    display: flex;

    align-items: center;

    gap: 8px;
}

.quantity a {

    width: 27px;
    height: 27px;

    border-radius: 7px;

    background: #f0eaff;

    color: #7548e8;

    display: flex;

    align-items: center;
    justify-content: center;

    text-decoration: none;

    font-weight: bold;
}

.quantity span {

    min-width: 20px;

    text-align: center;

    font-size: 13px;
}


/* DELETE */

.delete {

    text-decoration: none;

    color: #ff5c5c;

    font-size: 18px;
}


/* EMPTY */

.empty {

    text-align: center;

    padding: 80px 20px;

    color: #888;
}

.empty-icon {

    font-size: 60px;

    margin-bottom: 15px;
}


/* SUMMARY */

.summary {

    position: fixed;

    bottom: 0;

    left: 50%;

    transform: translateX(-50%);

    width: 100%;

    max-width: 500px;

    background: white;

    border-top: 1px solid #eeeeee;

    padding: 15px 18px;

    z-index: 100;
}

.total-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 12px;
}

.total-label {
    font-size: 13px;
}

.total-price {

    color: #7548e8;

    font-size: 19px;

    font-weight: bold;
}

.checkout {

    display: block;

    width: 100%;

    padding: 13px;

    background: #7548e8;

    color: white;

    border-radius: 10px;

    text-align: center;

    text-decoration: none;

    font-size: 14px;

    font-weight: bold;
}

</style>

</head>


<body>


<div class="app">


    <!-- HEADER -->

    <div class="header">

        <a href="index.php" class="back">
            ←
        </a>

        <h2>
            My Cart
        </h2>

    </div>


    <!-- CART -->

    <div class="cart-container">


        <?php if (mysqli_num_rows($cart_result) > 0): ?>


            <?php while ($item = mysqli_fetch_assoc($cart_result)): ?>


                <?php

                $subtotal =
                    $item['price'] * $item['quantity'];

                $total += $subtotal;

                ?>


                <div class="cart-item">


                    <!-- IMAGE -->

                    <div class="product-image">

                        <?php if (!empty($item['image'])): ?>

                            <img
                                src="assets/images/<?= htmlspecialchars($item['image']); ?>"
                                alt=""
                            >

                        <?php else: ?>

                            <div class="no-image">
                                🐱
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- INFO -->

                    <div class="product-info">


                        <div class="product-name">

                            <?= htmlspecialchars(
                                $item['product_name']
                            ); ?>

                        </div>


                        <div class="price">

                            Rp <?= number_format(
                                $item['price'],
                                0,
                                ',',
                                '.'
                            ); ?>

                        </div>


                        <div class="quantity">


                            <a href="cart.php?action=minus&id=<?= $item['id_cart_item']; ?>">
                                −
                            </a>


                            <span>
                                <?= $item['quantity']; ?>
                            </span>


                            <a href="cart.php?action=plus&id=<?= $item['id_cart_item']; ?>">
                                +
                            </a>


                        </div>


                    </div>


                    <!-- DELETE -->

                    <a
                        href="cart.php?action=delete&id=<?= $item['id_cart_item']; ?>"
                        class="delete"
                        onclick="return confirm('Hapus produk ini dari cart?')"
                    >
                        🗑
                    </a>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty">

                <div class="empty-icon">
                    🛒
                </div>

                <h3>
                    Cart masih kosong
                </h3>

                <p>
                    Yuk pilih produk untuk hewan kesayanganmu.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


<?php if ($total > 0): ?>

    <div class="summary">

        <div class="total-row">

            <span class="total-label">
                Total
            </span>

            <span class="total-price">

                Rp <?= number_format(
                    $total,
                    0,
                    ',',
                    '.'
                ); ?>

            </span>

        </div>


        <a
            href="checkout.php"
            class="checkout"
        >
            CHECKOUT
        </a>

    </div>

<?php endif; ?>


</body>

</html>