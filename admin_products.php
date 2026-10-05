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
// HAPUS PRODUK
// ===============================
if (isset($_GET['delete'])) {

    $id_product = (int) $_GET['delete'];

    // Hapus gambar produk terlebih dahulu
    $stmt = $conn->prepare("
        SELECT image
        FROM product_images
        WHERE id_product = ?
    ");
    $stmt->bind_param("i", $id_product);
    $stmt->execute();

    $images = $stmt->get_result();

    while ($img = $images->fetch_assoc()) {

        $file = "assets/images/" . $img['image'];

        if (file_exists($file)) {
            unlink($file);
        }
    }

    $stmt->close();

    // Hapus data gambar
    $stmt = $conn->prepare("
        DELETE FROM product_images
        WHERE id_product = ?
    ");
    $stmt->bind_param("i", $id_product);
    $stmt->execute();
    $stmt->close();

    // Hapus produk
    $stmt = $conn->prepare("
        DELETE FROM products
        WHERE id_product = ?
    ");
    $stmt->bind_param("i", $id_product);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_products.php");
    exit;
}

// ===============================
// FILTER
// ===============================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$category = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

$stock = isset($_GET['stock'])
    ? $_GET['stock']
    : '';

// ===============================
// KATEGORI
// ===============================

$categories = $conn->query("
    SELECT id_category, category_name
    FROM categories
    ORDER BY category_name ASC
");

// ===============================
// QUERY PRODUK
// ===============================

$sql = "
    SELECT
        p.id_product,
        p.product_name,
        p.price,
        p.stock,
        p.description,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.id_category = c.id_category
    WHERE 1=1
";

$params = [];
$types = "";

// FILTER NAMA
if ($search !== '') {

    $sql .= " AND p.product_name LIKE ?";

    $params[] = "%" . $search . "%";
    $types .= "s";
}

// FILTER KATEGORI
if ($category > 0) {

    $sql .= " AND p.id_category = ?";

    $params[] = $category;
    $types .= "i";
}

// FILTER STOK
if ($stock === 'available') {

    $sql .= " AND p.stock > 0";

} elseif ($stock === 'empty') {

    $sql .= " AND p.stock = 0";
}

$sql .= "
    ORDER BY p.id_product DESC
";

// ===============================
// EXECUTE QUERY
// ===============================

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$products = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Kelola Produk - ZCY Petshop</title>

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
            max-width: 1300px;
            margin: auto;
            padding: 30px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 20px;
        }

        .top h2 {
            margin-bottom: 5px;
        }

        .top p {
            color: #777;
        }

        .btn-add {
            background: #198754;
            color: white;

            text-decoration: none;

            padding: 11px 17px;

            border-radius: 7px;
        }

        /* ===============================
           FILTER
        =============================== */

        .filter-box {
            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .filter-form {
            display: grid;

            grid-template-columns:
                2fr 1fr 1fr auto auto;

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

            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 900px;
        }

        th,
        td {

            padding: 14px;

            border-bottom: 1px solid #eee;

            text-align: left;

            vertical-align: top;
        }

        th {

            background: #f8f9fa;

            font-size: 14px;
        }

        td {

            font-size: 14px;
        }

        .price {

            font-weight: bold;

            white-space: nowrap;
        }

        .stock {

            font-weight: bold;
        }

        .stock-empty {

            color: #dc3545;
        }

        .stock-available {

            color: #198754;
        }

        .description {

            max-width: 250px;

            color: #666;
        }

        /* ===============================
           BUTTON
        =============================== */

        .action {

            display: flex;

            gap: 7px;

            flex-wrap: wrap;
        }

        .btn-edit {

            background: #ffc107;

            color: #222;

            text-decoration: none;

            padding: 7px 10px;

            border-radius: 5px;
        }

        .btn-delete {

            background: #dc3545;

            color: white;

            text-decoration: none;

            padding: 7px 10px;

            border-radius: 5px;
        }

        .empty {

            text-align: center;

            padding: 40px;

            color: #777;
        }

        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 900px) {

            .filter-form {

                grid-template-columns: 1fr;

            }

            .top {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }

        }

    </style>

</head>

<body>

<!-- ===============================
     HEADER
=============================== -->

<div class="header">

    <h1>🐾 ZCY Petshop</h1>

    <a href="admin_dashboard.php" class="back">
        ← Dashboard
    </a>

</div>


<div class="container">

    <!-- ===============================
         TITLE
    =============================== -->

    <div class="top">

        <div>

            <h2>Kelola Produk</h2>

            <p>
                Tambah, edit, hapus, dan cari produk.
            </p>

        </div>

        <a href="admin_product_add.php"
           class="btn-add">

            + Tambah Produk

        </a>

    </div>


    <!-- ===============================
         FILTER
    =============================== -->

    <div class="filter-box">

        <form method="GET"
              action="admin_products.php"
              class="filter-form">

            <!-- SEARCH -->

            <input
                type="text"
                name="search"
                placeholder="🔍 Cari nama produk..."
                value="<?= htmlspecialchars($search) ?>"
            >


            <!-- CATEGORY -->

            <select name="category">

                <option value="0">
                    Semua Kategori
                </option>

                <?php while ($cat = $categories->fetch_assoc()): ?>

                    <option
                        value="<?= $cat['id_category'] ?>"
                        <?= $category == $cat['id_category'] ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($cat['category_name']) ?>

                    </option>

                <?php endwhile; ?>

            </select>


            <!-- STOCK -->

            <select name="stock">

                <option value="">
                    Semua Stok
                </option>

                <option
                    value="available"
                    <?= $stock === 'available' ? 'selected' : '' ?>
                >
                    Stok Tersedia
                </option>

                <option
                    value="empty"
                    <?= $stock === 'empty' ? 'selected' : '' ?>
                >
                    Stok Habis
                </option>

            </select>


            <button
                type="submit"
                class="btn-search"
            >
                Cari
            </button>


            <a
                href="admin_products.php"
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

                    <th>Produk</th>

                    <th>Kategori</th>

                    <th>Harga</th>

                    <th>Stok</th>

                    <th>Deskripsi</th>

                    <th>Aksi</th>

                </tr>

            </thead>


            <tbody>

                <?php if ($products->num_rows > 0): ?>

                    <?php while ($product = $products->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= $product['id_product'] ?>
                            </td>


                            <td>

                                <strong>
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </strong>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $product['category_name'] ?? '-'
                                ) ?>

                            </td>


                            <td class="price">

                                Rp <?= number_format(
                                    $product['price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </td>


                            <td>

                                <?php if ($product['stock'] > 0): ?>

                                    <span class="stock stock-available">

                                        <?= $product['stock'] ?>

                                    </span>

                                <?php else: ?>

                                    <span class="stock stock-empty">

                                        Habis

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td class="description">

                                <?= htmlspecialchars(
                                    $product['description'] ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <div class="action">

                                    <a
                                        href="admin_product_edit.php?id=<?= $product['id_product'] ?>"
                                        class="btn-edit"
                                    >
                                        ✏️ Edit
                                    </a>


                                    <a
                                        href="admin_products.php?delete=<?= $product['id_product'] ?>"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                    >
                                        🗑️ Hapus
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                        >

                            📦 Produk tidak ditemukan.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>