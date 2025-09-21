<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guru_id = $_SESSION['guru_id'];
    [$kelas_id, $mapel_id] = explode('|', $_POST['gmk_id']);
    $jenis_penilaian = $_POST['jenis_penilaian'];
    $tanggal = $_POST['tanggal'];
    $catatan = $_POST['catatan'] ?? '';
    $nilai_input = $_POST['nilai'] ?? [];

    if (empty($nilai_input)) {
        $_SESSION['success'] = "Error: Tidak ada nilai yang diinput!";
        header("Location: input_nilai.php");
        exit;
    }

    try {
        $conn->beginTransaction();

        foreach ($nilai_input as $siswa_id => $nilai) {
            if ($nilai === '' || !is_numeric($nilai)) continue;

            $nilai = (float) $nilai;
            if ($nilai < 0 || $nilai > 100) continue;

            $sql = "INSERT INTO nilai_siswa (guru_id, kelas_id, mapel_id, siswa_id, nilai, jenis_penilaian, tanggal, catatan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$guru_id, $kelas_id, $mapel_id, $siswa_id, $nilai, $jenis_penilaian, $tanggal, $catatan]);
        }

        $conn->commit();
        $_SESSION['success'] = "✅ Nilai berhasil disimpan!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['success'] = "Error: " . $e->getMessage();
    }

    header("Location: daftar_nilai.php");
    exit;
}
?>