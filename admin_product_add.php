<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require_once "config/database.php";

$error = "";
$success = "";

$product_name = "";
$id_category = "";
$price = "";
$stock = "";
$description = "";

/* =========================
   AMBIL KATEGORI
========================= */

$categories = mysqli_query(
    $conn,
    "SELECT id_category, category_name
     FROM categories
     ORDER BY category_name ASC"
);

if (!$categories) {
    die("Gagal mengambil kategori: " . mysqli_error($conn));
}

/* =========================
   PROSES TAMBAH PRODUK
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name = trim($_POST['product_name'] ?? '');
    $id_category = (int) ($_POST['id_category'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

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
    }

    /* =========================
       UPLOAD GAMBAR
    ========================= */

    $image_name = null;

    if ($error === "" && isset($_FILES['image'])) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

                $error = "Gambar gagal diupload.";

            } else {

                $allowed_types = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                $file_type = $_FILES['image']['type'];
                $file_size = $_FILES['image']['size'];

                if (!in_array($file_type, $allowed_types)) {

                    $error = "Format gambar harus JPG, PNG, atau WEBP.";

                } elseif ($file_size > 2 * 1024 * 1024) {

                    $error = "Ukuran gambar maksimal 2 MB.";

                } else {

                    $extension = strtolower(
                        pathinfo(
                            $_FILES['image']['name'],
                            PATHINFO_EXTENSION
                        )
                    );

                    $image_name =
                        "product_" .
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $extension;

                    $upload_dir = "assets/images/";

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $upload_path = $upload_dir . $image_name;

                    if (!move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $upload_path
                    )) {

                        $error = "Gambar gagal disimpan.";
                        $image_name = null;
                    }
                }
            }
        }
    }

    /* =========================
       INSERT PRODUK
    ========================= */

    if ($error === "") {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO products
            (
                id_category,
                product_name,
                description,
                price,
                stock
            )
            VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {

            $error =
                "Gagal membuat query: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "issdi",
                $id_category,
                $product_name,
                $description,
                $price,
                $stock
            );

            if (mysqli_stmt_execute($stmt)) {

                $id_product = mysqli_insert_id($conn);

                /* =========================
                   INSERT GAMBAR
                ========================= */

                if ($image_name !== null) {

                    $stmt_image = mysqli_prepare(
                        $conn,
                        "INSERT INTO product_images
                        (
                            id_product,
                            image
                        )
                        VALUES (?, ?)"
                    );

                    if ($stmt_image) {

                        mysqli_stmt_bind_param(
                            $stmt_image,
                            "is",
                            $id_product,
                            $image_name
                        );

                        mysqli_stmt_execute($stmt_image);

                        mysqli_stmt_close($stmt_image);
                    }
                }

                $success = "Produk berhasil ditambahkan.";

                $product_name = "";
                $id_category = "";
                $price = "";
                $stock = "";
                $description = "";

            } else {

                $error =
                    "Produk gagal ditambahkan: " .
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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Tambah Produk - ZCY Petshop</title>

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
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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

        .note {
            font-size: 13px;
            color: #777;
            margin-top: 7px;
        }

    </style>

</head>

<body>

<div class="header">

    <h2>🐾 ZCY Petshop - Tambah Produk</h2>

    <a href="admin_products.php">
        ← Kembali
    </a>

</div>

<div class="container">

    <div class="card">

        <h3>➕ Tambah Produk Baru</h3>

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

        <form method="POST"
              enctype="multipart/form-data">

            <div class="form-group">

                <label>Nama Produk</label>

                <input
                    type="text"
                    name="product_name"
                    value="<?= htmlspecialchars($product_name) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label>Kategori</label>

                <select name="id_category" required>

                    <option value="">
                        -- Pilih Kategori --
                    </option>

                    <?php while ($category = mysqli_fetch_assoc($categories)): ?>

                        <option
                            value="<?= $category['id_category'] ?>"
                            <?= ($id_category == $category['id_category']) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($category['category_name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Harga</label>

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

                <label>Stok</label>

                <input
                    type="number"
                    name="stock"
                    value="<?= htmlspecialchars($stock) ?>"
                    min="0"
                    required
                >

            </div>

            <div class="form-group">

                <label>Deskripsi</label>

                <textarea
                    name="description"
                ><?= htmlspecialchars($description) ?></textarea>

            </div>

            <div class="form-group">

                <label>Gambar Produk</label>

                <input
                    type="file"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <div class="note">
                    JPG, PNG, atau WEBP. Maksimal 2 MB.
                </div>

            </div>

            <button
                type="submit"
                class="btn btn-save"
            >
                💾 Simpan Produk
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