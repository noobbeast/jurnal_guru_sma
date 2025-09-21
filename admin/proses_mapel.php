<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $nama_mapel = $_POST['nama_mapel'];
        $singkatan = $_POST['singkatan'];

        $sql = "INSERT INTO mata_pelajaran (nama_mapel, singkatan) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama_mapel, $singkatan]);

        $_SESSION['success'] = "Mapel berhasil ditambahkan!";
        header("Location: data_mapel.php");
        exit;
    }

    if (isset($_POST['edit'])) {
        $id = $_POST['id'];
        $nama_mapel = $_POST['nama_mapel'];
        $singkatan = $_POST['singkatan'];

        $sql = "UPDATE mata_pelajaran SET nama_mapel = ?, singkatan = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama_mapel, $singkatan, $id]);

        $_SESSION['success'] = "Data mapel berhasil diubah!";
        header("Location: data_mapel.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // Hapus relasi dulu
    $sql = "DELETE FROM guru_mapel_kelas WHERE mapel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Hapus jurnal yang terkait
    $sql = "DELETE FROM jurnal WHERE mapel_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Baru hapus mapel
    $sql = "DELETE FROM mata_pelajaran WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $_SESSION['success'] = "Mapel berhasil dihapus!";
    header("Location: data_mapel.php");
    exit;
}
?>