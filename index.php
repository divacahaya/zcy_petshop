<?php
session_start();
require_once "config/database.php";

// Cek apakah user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Ambil data kategori
$categories = mysqli_query($conn, "
    SELECT * FROM categories
    ORDER BY id_category ASC
");

// Ambil data produk + gambar
$products = mysqli_query($conn, "
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
    ORDER BY p.id_product DESC
");
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ZCY Petshop</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f8f7fc;
            color: #333;
        }

        /* =========================
           CONTAINER
        ========================= */

        .app {
            width: 100%;
            max-width: 500px;
            min-height: 100vh;
            margin: auto;
            background: white;
            padding-bottom: 85px;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            padding: 20px 18px 10px;
        }

        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-icon {
            width: 35px;
            height: 35px;
            background: #7548e8;
            color: white;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        .brand h2 {
            color: #7548e8;
            font-size: 18px;
        }

        .notification {
            font-size: 22px;
            text-decoration: none;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-box {
            margin-top: 18px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 13px 45px 13px 15px;

            border: 1px solid #ddd;
            border-radius: 12px;

            outline: none;
            background: #fafafa;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 11px;

            font-size: 18px;
        }

        /* =========================
           CATEGORY
        ========================= */

        .section {
            padding: 18px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 13px;
        }

        .section-title h3 {
            font-size: 17px;
        }

        .section-title a {
            color: #7548e8;
            font-size: 12px;
            text-decoration: none;
        }

        .categories {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .categories::-webkit-scrollbar {
            display: none;
        }

        .category {
            flex-shrink: 0;

            background: #f0eaff;
            color: #7548e8;

            padding: 9px 15px;

            border-radius: 20px;

            font-size: 12px;
        }

        /* =========================
           BANNER
        ========================= */

        .banner {
            margin: 5px 18px 10px;

            background: linear-gradient(
                135deg,
                #7548e8,
                #9a73f5
            );

            border-radius: 15px;

            padding: 20px;

            color: white;

            position: relative;
            overflow: hidden;
        }

        .banner h2 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .banner p {
            font-size: 12px;
            width: 65%;
            line-height: 1.5;
        }

        .banner .paw {
            position: absolute;
            right: 20px;
            bottom: 5px;

            font-size: 65px;
        }

        /* =========================
           PRODUCTS
        ========================= */

        .product-grid {
            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 13px;
        }

        .product {
            border: 1px solid #eeeeee;

            border-radius: 13px;

            overflow: hidden;

            background: white;

            position: relative;
        }

        .product-image {
            height: 150px;

            background: #f7f7f7;

            display: flex;

            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;

            object-fit: contain;
        }

        .no-image {
            font-size: 45px;
        }

        .product-info {
            padding: 11px;
        }

        .product-name {
            font-size: 13px;

            font-weight: bold;

            height: 34px;

            overflow: hidden;
        }

        .rating {
            margin-top: 5px;

            font-size: 11px;

            color: #f5a623;
        }

        .price {
            margin-top: 7px;

            color: #7548e8;

            font-size: 14px;

            font-weight: bold;
        }

        .stock {
            margin-top: 4px;

            font-size: 10px;

            color: #888;
        }

        .add-cart {
            width: 100%;

            margin-top: 9px;

            padding: 8px;

            border: none;

            border-radius: 7px;

            background: #7548e8;

            color: white;

            font-size: 11px;

            cursor: pointer;
        }

        .add-cart:hover {
            background: #6136d2;
        }

        /* =========================
           BOTTOM NAVIGATION
        ========================= */

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

        /* =========================
           RESPONSIVE
        ========================= */

        @media (min-width: 501px) {

            body {
                background: #eeeeee;
            }

            .app {
                box-shadow: 0 0 20px rgba(0,0,0,0.08);
            }

        }

    </style>

</head>


<body>


<div class="app">


    <!-- ================= HEADER ================= -->

    <div class="header">

        <div class="top-header">

            <div class="brand">

                <div class="brand-icon">
                    🐾
                </div>

                <h2>ZCY Petshop</h2>

            </div>


            <a href="notification.php" class="notification">
                🔔
            </a>

        </div>


        <!-- SEARCH -->

        <div class="search-box">

            <input
                type="text"
                id="searchInput"
                placeholder="Search products..."
            >

            <span class="search-icon">
                🔍
            </span>

        </div>

    </div>



    <!-- ================= CATEGORY ================= -->

    <div class="section">

        <div class="section-title">

            <h3>Categories</h3>

            <a href="#">
                See All
            </a>

        </div>


        <div class="categories">

            <?php while ($category = mysqli_fetch_assoc($categories)): ?>

                <div class="category">

                    <?= htmlspecialchars($category['category_name']); ?>

                </div>

            <?php endwhile; ?>

        </div>

    </div>



    <!-- ================= BANNER ================= -->

    <div class="banner">

        <h2>
            Pet Lovers
        </h2>

        <p>
            Everything your lovely pet needs
            in one place.
        </p>

        <div class="paw">
            🐱
        </div>

    </div>



    <!-- ================= PRODUCTS ================= -->

    <div class="section">

        <div class="section-title">

            <h3>
                New Products
            </h3>

            <a href="#">
                See All
            </a>

        </div>


        <div class="product-grid" id="productGrid">


            <?php if (mysqli_num_rows($products) > 0): ?>


                <?php while ($product = mysqli_fetch_assoc($products)): ?>


                    <div
                        class="product"
                        data-name="<?= strtolower(htmlspecialchars($product['product_name'])); ?>"
                    >


                        <!-- IMAGE -->

                        <div class="product-image">

                            <?php if (!empty($product['image'])): ?>

                                <img
                                    src="assets/images/<?= htmlspecialchars($product['image']); ?>"
                                    alt="<?= htmlspecialchars($product['product_name']); ?>"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    🐱
                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- INFORMATION -->

                        <div class="product-info">


                            <a
    href="product_detail.php?id=<?= $product['id_product']; ?>"
    style="color: inherit; text-decoration: none;"
>
    <div class="product-name">
        <?= htmlspecialchars($product['product_name']); ?>
    </div>
</a>


                            <div class="rating">

                                ⭐
                                <?= number_format($product['rating'], 1); ?>

                            </div>


                            <div class="price">

                                Rp <?= number_format(
                                    $product['price'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                            </div>


                            <div class="stock">

                                Stok:
                                <?= $product['stock']; ?>

                            </div>


                            <a
    href="cart.php?action=add&id_product=<?= $product['id_product']; ?>"
    class="add-cart"
    style="display:block;text-align:center;text-decoration:none;"
>
    + ADD TO CART
</a>


                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <p>
                    Belum ada produk.
                </p>


            <?php endif; ?>


        </div>

    </div>



</div>



<!-- ================= BOTTOM NAV ================= -->

<div class="bottom-nav">


    <a href="index.php" class="nav-item active">

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


    <a href="profile.php" class="nav-item">

        <span>👤</span>

        Profile

    </a>


</div>



<script>

    // =========================
    // SEARCH PRODUCT
    // =========================

    const searchInput =
        document.getElementById("searchInput");

    searchInput.addEventListener("keyup", function () {

        const keyword =
            this.value.toLowerCase();

        const products =
            document.querySelectorAll(".product");

        products.forEach(function(product) {

            const name =
                product.getAttribute("data-name");

            if (name.includes(keyword)) {

                product.style.display = "";

            } else {

                product.style.display = "none";

            }

        });

    });


    // =========================
    // ADD CART
    // =========================

    function addCart(idProduct) {

        alert(
            "Produk berhasil dipilih!\\nID Produk: "
            + idProduct
        );

    }

</script>


</body>

</html>