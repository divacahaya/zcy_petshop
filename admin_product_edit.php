<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require_once "config/database.php";

$error = "";
$success = "";

/* =========================
   CEK ID PRODUK
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_products.php");
    exit;
}

$id_product = (int) $_GET['id'];

/* =========================
   AMBIL DATA PRODUK
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id_product,
        id_category,
        product_name,
        description,
        price,
        stock
     FROM products
     WHERE id_product = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_product
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$product) {
    header("Location: admin_products.php");
    exit;
}

/* =========================
   DATA PRODUK
========================= */

$product_name = $product['product_name'];
$id_category = $product['id_category'];
$price = $product['price'];
$stock = $product['stock'];
$description = $product['description'];

/* =========================
   AMBIL KATEGORI
========================= */

$categories = mysqli_query(
    $conn,
    "SELECT
        id_category,
        category_name
     FROM categories
     ORDER BY category_name ASC"
);

if (!$categories) {
    die(
        "Gagal mengambil kategori: " .
        mysqli_error($conn)
    );
}

/* =========================
   PROSES UPDATE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name = trim(
        $_POST['product_name'] ?? ''
    );

    $id_category = (int) (
        $_POST['id_category'] ?? 0
    );

    $price = (float) (
        $_POST['price'] ?? 0
    );

    $stock = (int) (
        $_POST['stock'] ?? 0
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    /* =========================
       VALIDASI
    ========================= */

    if ($product_name === "") {

        $error = "Nama produk wajib diisi.";

    } elseif ($id_category <= 0) {

        $error = "Kategori wajib dipilih.";

    } elseif ($price <= 0) {

        $error = "Harga harus lebih dari 0.";

    } elseif ($stock < 0) {

        $error = "Stok tidak boleh kurang dari 0.";

    } else {

        /* =========================
           UPDATE PRODUK
        ========================= */

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE products
             SET
                id_category = ?,
                product_name = ?,
                description = ?,
                price = ?,
                stock = ?
             WHERE id_product = ?"
        );

        if (!$stmt) {

            $error =
                "Gagal membuat query: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "issdii",
                $id_category,
                $product_name,
                $description,
                $price,
                $stock,
                $id_product
            );

            if (mysqli_stmt_execute($stmt)) {

                $success =
                    "Produk berhasil diperbarui.";

            } else {

                $error =
                    "Produk gagal diperbarui: " .
                    mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Produk - ZCY Petshop</title>

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

        .container {
            width: 90%;
            max-width: 850px;
            margin: 40px auto;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;

            border: 1px solid #ccc;
            border-radius: 8px;

            font-size: 15px;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .btn {
            border: none;
            padding: 12px 20px;

            border-radius: 8px;

            cursor: pointer;
            font-size: 15px;
        }

        .btn-save {
            background: #222;
            color: white;
        }

        .btn-back {
            background: #ddd;
            color: #333;

            text-decoration: none;
            display: inline-block;

            margin-left: 8px;
        }

        .success {
            background: #dff5e5;
            color: #187a36;

            padding: 12px;
            border-radius: 8px;

            margin-bottom: 20px;
        }

        .error {
            background: #ffe0e0;
            color: #b00020;

            padding: 12px;
            border-radius: 8px;

            margin-bottom: 20px;
        }

        .product-id {
            background: #f1f1f1;

            padding: 10px 12px;

            border-radius: 8px;

            margin-bottom: 20px;

            color: #666;
        }

    </style>

</head>

<body>

<div class="header">

    <h2>🐾 ZCY Petshop - Edit Produk</h2>

    <a href="admin_products.php">
        ← Kembali
    </a>

</div>

<div class="container">

    <div class="card">

        <h3>✏️ Edit Produk</h3>

        <div class="product-id">

            ID Produk:
            <strong>
                <?= $id_product ?>
            </strong>

        </div>

        <?php if ($success !== ""): ?>

            <div class="success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
        >

            <div class="form-group">

                <label>
                    Nama Produk
                </label>

                <input
                    type="text"
                    name="product_name"
                    value="<?= htmlspecialchars($product_name) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Kategori
                </label>

                <select
                    name="id_category"
                    required
                >

                    <option value="">
                        -- Pilih Kategori --
                    </option>

                    <?php while (
                        $category =
                        mysqli_fetch_assoc($categories)
                    ): ?>

                        <option
                            value="<?= $category['id_category'] ?>"
                            <?= (
                                $id_category ==
                                $category['id_category']
                            ) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars(
                                $category['category_name']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label>
                    Harga
                </label>

                <input
                    type="number"
                    name="price"
                    value="<?= htmlspecialchars($price) ?>"
                    min="1"
                    step="0.01"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Stok
                </label>

                <input
                    type="number"
                    name="stock"
                    value="<?= htmlspecialchars($stock) ?>"
                    min="0"
                    required
                >

            </div>

            <div class="form-group">

                <label>
                    Deskripsi
                </label>

                <textarea
                    name="description"
                ><?= htmlspecialchars($description) ?></textarea>

            </div>

            <button
                type="submit"
                class="btn btn-save"
            >
                💾 Simpan Perubahan
            </button>

            <a
                href="admin_products.php"
                class="btn btn-back"
            >
                Batal
            </a>

        </form>

    </div>

</div>

</body>

</html>