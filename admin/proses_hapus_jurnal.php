<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Hapus absensi terkait dulu
        $sql = "DELETE FROM absensi WHERE jurnal_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        // Hapus jurnal
        $sql = "DELETE FROM jurnal WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        $_SESSION['success'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
            ✅ Jurnal berhasil dihapus.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';

    } catch (Exception $e) {
        $_SESSION['success'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            ❌ Gagal menghapus jurnal: ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }
}

header("Location: rekap_jurnal.php");
exit;
?>