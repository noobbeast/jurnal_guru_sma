<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $nis = $_POST['nis'];
        $nama = $_POST['nama'];
        $kelas_id = $_POST['kelas_id'];

        $sql = "INSERT INTO siswa (nis, nama, kelas_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nis, $nama, $kelas_id]);

        $_SESSION['success'] = "Siswa berhasil ditambahkan!";
        header("Location: data_siswa.php");
        exit;
    }

    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $nis = $_POST['nis'];
        $nama = $_POST['nama'];
        $kelas_id = $_POST['kelas_id'];

        $sql = "UPDATE siswa SET nis = ?, nama = ?, kelas_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nis, $nama, $kelas_id, $id]);

        $_SESSION['success'] = "Data siswa berhasil diubah!";
        header("Location: data_siswa.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Hapus absensi yang terkait
    $sql = "DELETE FROM absensi WHERE siswa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Hapus siswa
    $sql = "DELETE FROM siswa WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $_SESSION['success'] = "Siswa berhasil dihapus!";
    header("Location: data_siswa.php");
    exit;
}
?>