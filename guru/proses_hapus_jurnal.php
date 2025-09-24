<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Pastikan jurnal milik guru yang login
    $sql_check = "SELECT id FROM jurnal WHERE id = ? AND guru_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$id, $_SESSION['guru_id']]);
    if (!$stmt_check->fetch()) {
        $_SESSION['success'] = "Error: Jurnal tidak ditemukan!";
        header("Location: rekap_jurnal.php");
        exit;
    }

    try {
        // Hapus absensi terkait
        $sql = "DELETE FROM absensi WHERE jurnal_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        // Hapus jurnal
        $sql = "DELETE FROM jurnal WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        $_SESSION['success'] = "✅ Jurnal berhasil dihapus!";

    } catch (Exception $e) {
        $_SESSION['success'] = "❌ Gagal menghapus jurnal: " . $e->getMessage();
    }
}

header("Location: daftar_jurnal.php");
exit;
?>