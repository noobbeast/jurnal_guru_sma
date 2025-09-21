<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $nama_kelas = $_POST['nama_kelas'];
        $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? $_POST['wali_kelas_id'] : null;

        $sql = "INSERT INTO kelas (nama_kelas, wali_kelas_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama_kelas, $wali_kelas_id]);

        $_SESSION['success'] = "Kelas berhasil ditambahkan!";
        header("Location: data_kelas.php");
        exit;
    }

    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $nama_kelas = $_POST['nama_kelas'];
        $wali_kelas_id = !empty($_POST['wali_kelas_id']) ? $_POST['wali_kelas_id'] : null;

        $sql = "UPDATE kelas SET nama_kelas = ?, wali_kelas_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama_kelas, $wali_kelas_id, $id]);

        $_SESSION['success'] = "Data kelas berhasil diubah!";
        header("Location: data_kelas.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Hapus relasi dulu di tabel guru_mapel_kelas & siswa agar tidak error
    $sql = "DELETE FROM guru_mapel_kelas WHERE kelas_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $sql = "DELETE FROM siswa WHERE kelas_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Baru hapus kelas
    $sql = "DELETE FROM kelas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $_SESSION['success'] = "Kelas berhasil dihapus!";
    header("Location: data_kelas.php");
    exit;
}
?>