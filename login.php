<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Gunakan PDO dengan named placeholder
    $sql = "SELECT u.*, g.id as guru_id FROM users u 
            LEFT JOIN guru g ON u.id = g.user_id 
            WHERE u.email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['guru_id'] = $user['guru_id'] ?? null;

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: guru/dashboard.php");
        }
        exit;
    }

    header("Location: index.php?error=1");
    exit;
}
?>