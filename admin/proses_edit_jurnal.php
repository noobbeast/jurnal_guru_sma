<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $tanggal = $_POST['tanggal'];
    $jam_ke = $_POST['jam_ke'] ?? null;
    $guru_id = $_POST['guru_id'];
    $kelas_id = $_POST['kelas_id'];
    $mapel_id = $_POST['mapel_id'];
    $materi = $_POST['materi'];
    $catatan = $_POST['catatan'] ?? '';
    $kegiatan = $_POST['kegiatan'] ?? [];

    $pendahuluan = in_array('pendahuluan', $kegiatan) ? 1 : 0;
    $inti = in_array('inti', $kegiatan) ? 1 : 0;
    $penutup = in_array('penutup', $kegiatan) ? 1 : 0;

    try {
        $sql = "UPDATE jurnal SET 
                tanggal = ?, 
                jam_ke = ?, 
                guru_id = ?, 
                kelas_id = ?, 
                mapel_id = ?, 
                materi = ?, 
                catatan = ?, 
                kegiatan_pendahuluan = ?, 
                kegiatan_inti = ?, 
                kegiatan_penutup = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $tanggal,
            $jam_ke,
            $guru_id,
            $kelas_id,
            $mapel_id,
            $materi,
            $catatan,
            $pendahuluan,
            $inti,
            $penutup,
            $id
        ]);

        $_SESSION['success'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
            ✅ Jurnal berhasil diupdate.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';

    } catch (Exception $e) {
        $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            ❌ Gagal mengupdate jurnal: ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }

    header("Location: rekap_jurnal.php");
    exit;
}
?>