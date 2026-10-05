<?php
session_start();
require_once "config/database.php";

/* =========================
   CEK ADMIN
========================= */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

/* =========================
   TANDAI SEMUA SUDAH DIBACA
========================= */
if (isset($_POST['mark_read'])) {

    $id_admin = $_SESSION['admin_id'];

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id_user = ?
    ");

    $stmt->bind_param("i", $id_admin);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_notifications.php");
    exit;
}

/* =========================
   AMBIL NOTIFIKASI
========================= */

$id_admin = $_SESSION['admin_id'];

$stmt = $conn->prepare("
    SELECT
        id_notification,
        title,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE id_user = ?
    ORDER BY id_notification DESC
");

$stmt->bind_param("i", $id_admin);
$stmt->execute();

$result = $stmt->get_result();

$notifications = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notifikasi Admin - ZCY Petshop</title>

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

/* HEADER */

.header {
    background: white;
    padding: 20px 30px;
    border-bottom: 1px solid #ddd;
}

.header h1 {
    margin: 0;
    font-size: 24px;
}

/* CONTAINER */

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    color: #555;
}

/* BUTTON */

.mark-read {
    margin-bottom: 20px;
}

.mark-read button {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
}

.mark-read button:hover {
    background: #2980b9;
}

/* NOTIFICATION */

.notification-box {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.notification {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    gap: 15px;
}

.notification:last-child {
    border-bottom: none;
}

/* BELUM DIBACA */

.notification.unread {
    background: #eef6ff;
}

/* ICON */

.icon {
    width: 45px;
    height: 45px;
    background: #f0f2ff;
    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 22px;
    flex-shrink: 0;
}

/* CONTENT */

.notification h3 {
    margin: 0 0 7px 0;
    font-size: 16px;
}

.notification p {
    margin: 0 0 8px 0;
    color: #666;
    line-height: 1.5;
}

.date {
    font-size: 12px;
    color: #999;
}

.badge {
    display: inline-block;
    background: #3498db;
    color: white;
    font-size: 11px;
    padding: 3px 7px;
    border-radius: 5px;
    margin-left: 5px;
}

/* EMPTY */

.empty {
    background: white;
    padding: 60px 20px;
    text-align: center;
    border-radius: 12px;
}

.empty-icon {
    font-size: 50px;
    margin-bottom: 10px;
}

</style>

</head>

<body>

<div class="header">

    <h1>🔔 Notifikasi Admin</h1>

</div>


<div class="container">

    <a href="admin_dashboard.php" class="back">
        ← Kembali ke Dashboard
    </a>


    <?php if (count($notifications) > 0): ?>

        <!-- TOMBOL BACA SEMUA -->

        <form method="POST" class="mark-read">

            <button type="submit" name="mark_read">
                ✓ Tandai Semua Sudah Dibaca
            </button>

        </form>


        <!-- DAFTAR NOTIFIKASI -->

        <div class="notification-box">

            <?php foreach ($notifications as $notification): ?>

                <div
                    class="notification
                    <?= $notification['is_read'] == 0 ? 'unread' : '' ?>"
                >

                    <div class="icon">

                        <?= $notification['is_read'] == 0
                            ? '🔔'
                            : '✓'
                        ?>

                    </div>


                    <div>

                        <h3>

                            <?= htmlspecialchars(
                                $notification['title']
                            ) ?>

                            <?php if ($notification['is_read'] == 0): ?>

                                <span class="badge">
                                    Baru
                                </span>

                            <?php endif; ?>

                        </h3>


                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    $notification['message']
                                )
                            ) ?>

                        </p>


                        <div class="date">

                            <?= date(
                                'd-m-Y H:i',
                                strtotime(
                                    $notification['created_at']
                                )
                            ) ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <div class="empty-icon">
                🔔
            </div>

            <h3>
                Belum ada notifikasi
            </h3>

            <p>
                Notifikasi admin akan muncul di sini.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>

</html>