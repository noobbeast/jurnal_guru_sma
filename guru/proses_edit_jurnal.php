<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $tanggal = $_POST['tanggal'];
    $jam_ke = $_POST['jam_ke'] ?? null;
    [$kelas_id, $mapel_id] = explode('|', $_POST['gmk_id']);
    $materi = $_POST['materi'];
    $catatan = $_POST['catatan'] ?? '';
    $kegiatan = $_POST['kegiatan'] ?? [];

    // Pastikan jurnal milik guru yang login
    $sql_check = "SELECT id FROM jurnal WHERE id = ? AND guru_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$id, $_SESSION['guru_id']]);
    if (!$stmt_check->fetch()) {
        $_SESSION['success'] = "Error: Jurnal tidak ditemukan!";
        header("Location: rekap_jurnal.php");
        exit;
    }

    $pendahuluan = in_array('pendahuluan', $kegiatan) ? 1 : 0;
    $inti = in_array('inti', $kegiatan) ? 1 : 0;
    $penutup = in_array('penutup', $kegiatan) ? 1 : 0;

    try {
        $sql = "UPDATE jurnal SET 
                tanggal = ?, 
                jam_ke = ?, 
                kelas_id = ?, 
                mapel_id = ?, 
                materi = ?, 
                catatan = ?, 
                kegiatan_pendahuluan = ?, 
                kegiatan_inti = ?, 
                kegiatan_penutup = ?
                WHERE id = ? AND guru_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $tanggal,
            $jam_ke,
            $kelas_id,
            $mapel_id,
            $materi,
            $catatan,
            $pendahuluan,
            $inti,
            $penutup,
            $id,
            $_SESSION['guru_id']
        ]);

        $_SESSION['success'] = "✅ Jurnal berhasil diupdate!";

    } catch (Exception $e) {
        $_SESSION['success'] = "❌ Gagal mengupdate jurnal: " . $e->getMessage();
    }

    header("Location: daftar_jurnal.php");
    exit;
}
?>