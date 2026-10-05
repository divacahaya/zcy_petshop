<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}


/* =========================
   HAPUS REVIEW
========================= */
if (isset($_POST['delete_review'])) {

    $id_review = intval($_POST['id_review']);

    $stmt = $conn->prepare(
        "DELETE FROM reviews WHERE id_review = ?"
    );

    $stmt->bind_param("i", $id_review);

    $stmt->execute();

    $stmt->close();

    header("Location: admin_reviews.php");
    exit;
}

/* AMBIL REVIEW */
$sql = "
    SELECT
        r.id_review,
        r.rating,
        r.review,
        r.created_at,
        u.name AS user_name,
        p.product_name
    FROM reviews r
    LEFT JOIN users u
        ON r.id_user = u.id_user
    LEFT JOIN products p
        ON r.id_product = p.id_product
    ORDER BY r.id_review DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Admin Review - ZCY Petshop</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
}

.header {
    background: white;
    padding: 20px 30px;
    border-bottom: 1px solid #ddd;
}

.container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 20px;
}

.box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th {
    background: #f1f1f1;
}

.stars {
    color: orange;
    font-size: 18px;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    color: #333;
}

</style>

</head>

<body>

<div class="header">

    <h2>⭐ Kelola Review</h2>

</div>

<div class="container">

    <a href="admin_dashboard.php" class="back">
        ← Kembali ke Dashboard
    </a>

    <div class="box">

        <?php if ($result->num_rows > 0): ?>

        <table>

            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Produk</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Tanggal</th>
				<th>Aksi</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>

            <tr>

                <td>
                    #<?= $row['id_review'] ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['user_name'] ?? '-') ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['product_name'] ?? '-') ?>
                </td>

                <td>

                    <span class="stars">

                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $row['rating'] ? '★' : '☆';
                    }
                    ?>

                    </span>

                    <br>

                    <?= $row['rating'] ?>/5

                </td>

                <td>

                    <?= nl2br(
                        htmlspecialchars($row['review'] ?? '-')
                    ) ?>

                </td>

                <td>

                    <?= date(
                        'd-m-Y H:i',
                        strtotime($row['created_at'])
                    ) ?>

                </td>
				
				<td>

    <form method="POST"
          onsubmit="return confirm('Yakin ingin menghapus review ini?');">

        <input
            type="hidden"
            name="id_review"
            value="<?= $row['id_review'] ?>"
        >

        <button
            type="submit"
            name="delete_review"
            style="
                background:#e74c3c;
                color:white;
                border:none;
                padding:8px 12px;
                border-radius:6px;
                cursor:pointer;
            "
        >
            🗑️ Hapus
        </button>

    </form>

</td>

            </tr>

            <?php endwhile; ?>

        </table>

        <?php else: ?>

            <p>Belum ada review.</p>

        <?php endif; ?>

    </div>

</div>

</body>

</html>