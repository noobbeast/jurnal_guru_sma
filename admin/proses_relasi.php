<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $guru_id = $_POST['guru_id'];
        $mapel_id = $_POST['mapel_id'];
        $kelas_id = $_POST['kelas_id'];

        try {
            $sql = "INSERT INTO guru_mapel_kelas (guru_id, mapel_id, kelas_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$guru_id, $mapel_id, $kelas_id]);
            $_SESSION['success'] = "Relasi berhasil ditambahkan!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['success'] = "Relasi ini sudah ada!";
            } else {
                $_SESSION['success'] = "Error: " . $e->getMessage();
            }
        }
        header("Location: data_relasi.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $sql = "DELETE FROM guru_mapel_kelas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $_SESSION['success'] = "Relasi berhasil dihapus!";
    header("Location: data_relasi.php");
    exit;
}
?>