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
// HAPUS USER
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {

    $id_user = (int) $_POST['id_user'];

    // Jangan izinkan admin menghapus dirinya sendiri
    if ($id_user != $_SESSION['admin_id']) {

        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id_user = ?
        ");

        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_users.php");
    exit;
}

// ===============================
// FILTER
// ===============================

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$role = isset($_GET['role'])
    ? $_GET['role']
    : '';

// ===============================
// QUERY USER
// ===============================

$sql = "
    SELECT
        id_user,
        name,
        email,
        phone,
        role,
        created_at
    FROM users
    WHERE 1=1
";

$params = [];
$types = "";

// SEARCH
if ($search !== '') {

    $sql .= "
        AND (
            name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}

// ROLE
if ($role !== '') {

    $sql .= " AND role = ?";

    $params[] = $role;
    $types .= "s";
}

$sql .= "
    ORDER BY id_user DESC
";

// ===============================
// EXECUTE
// ===============================

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$users = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Kelola Pengguna - ZCY Petshop</title>

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
            margin-bottom: 20px;
        }

        .top h2 {
            margin-bottom: 5px;
        }

        .top p {
            color: #777;
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
                2fr 1fr auto auto;

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

            vertical-align: middle;
        }

        th {

            background: #f8f9fa;

            font-size: 14px;
        }

        td {

            font-size: 14px;
        }

        /* ===============================
           ROLE
        =============================== */

        .role {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }

        .role-user {

            background: #cfe2ff;

            color: #084298;
        }

        .role-admin {

            background: #f8d7da;

            color: #842029;
        }

        /* ===============================
           ACTION
        =============================== */

        .action {

            display: flex;

            gap: 7px;

            flex-wrap: wrap;
        }

        .btn-detail {

            background: #0d6efd;

            color: white;

            text-decoration: none;

            padding: 7px 10px;

            border-radius: 5px;
        }

        .btn-delete {

            background: #dc3545;

            color: white;

            border: none;

            padding: 7px 10px;

            border-radius: 5px;

            cursor: pointer;
        }

        .btn-disabled {

            background: #adb5bd;

            color: white;

            padding: 7px 10px;

            border-radius: 5px;

            cursor: not-allowed;
        }

        .empty {

            text-align: center;

            padding: 40px;

            color: #777;
        }

        /* ===============================
           RESPONSIVE
        =============================== */

        @media (max-width: 800px) {

            .filter-form {

                grid-template-columns: 1fr;

            }

            .container {

                padding: 20px 15px;

            }

            .header {

                padding: 15px;

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

    <a
        href="admin_dashboard.php"
        class="back"
    >
        ← Dashboard
    </a>

</div>


<div class="container">

    <!-- ===============================
         TITLE
    =============================== -->

    <div class="top">

        <h2>Kelola Pengguna</h2>

        <p>
            Lihat, cari, filter, dan kelola pengguna ZCY Petshop.
        </p>

    </div>


    <!-- ===============================
         FILTER
    =============================== -->

    <div class="filter-box">

        <form
            method="GET"
            action="admin_users.php"
            class="filter-form"
        >

            <input
                type="text"
                name="search"
                placeholder="🔍 Cari nama, email, atau nomor HP..."
                value="<?= htmlspecialchars($search) ?>"
            >


            <select name="role">

                <option value="">
                    Semua Role
                </option>

                <option
                    value="user"
                    <?= $role === 'user' ? 'selected' : '' ?>
                >
                    User
                </option>

                <option
                    value="admin"
                    <?= $role === 'admin' ? 'selected' : '' ?>
                >
                    Admin
                </option>

            </select>


            <button
                type="submit"
                class="btn-search"
            >
                Cari
            </button>


            <a
                href="admin_users.php"
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

                    <th>Nama</th>

                    <th>Email</th>

                    <th>No. HP</th>

                    <th>Role</th>

                    <th>Tanggal Daftar</th>

                    <th>Aksi</th>

                </tr>

            </thead>


            <tbody>

                <?php if ($users->num_rows > 0): ?>

                    <?php while ($user = $users->fetch_assoc()): ?>

                        <tr>

                            <!-- ID -->

                            <td>
                                #<?= $user['id_user'] ?>
                            </td>


                            <!-- NAMA -->

                            <td>

                                <strong>
                                    <?= htmlspecialchars(
                                        $user['name']
                                    ) ?>
                                </strong>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['email']
                                ) ?>

                            </td>


                            <!-- PHONE -->

                            <td>

                                <?= htmlspecialchars(
                                    $user['phone'] ?? '-'
                                ) ?>

                            </td>


                            <!-- ROLE -->

                            <td>

                                <?php if ($user['role'] === 'admin'): ?>

                                    <span class="role role-admin">
                                        👑 Admin
                                    </span>

                                <?php else: ?>

                                    <span class="role role-user">
                                        👤 User
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?= date(
                                    'd-m-Y H:i',
                                    strtotime($user['created_at'])
                                ) ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <div class="action">

                                    <a
                                        href="admin_user_detail.php?id=<?= $user['id_user'] ?>"
                                        class="btn-detail"
                                    >
                                        👁️ Detail
                                    </a>


                                    <?php if (
                                        $user['id_user']
                                        !=
                                        $_SESSION['admin_id']
                                    ): ?>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')"
                                        >

                                            <input
                                                type="hidden"
                                                name="id_user"
                                                value="<?= $user['id_user'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_user"
                                                class="btn-delete"
                                            >
                                                🗑️ Hapus
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span class="btn-disabled">
                                            🔒 Akun Anda
                                        </span>

                                    <?php endif; ?>

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

                            👥 Pengguna tidak ditemukan.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>