<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$guru_id = $_SESSION['guru_id'];

// Ambil semua jurnal
$stmt = $conn->prepare("
    SELECT j.tanggal, k.nama_kelas, m.nama_mapel, j.materi, j.foto_kegiatan
    FROM jurnal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    WHERE j.guru_id = ?
    ORDER BY j.tanggal DESC
");
$stmt->execute([$guru_id]);
$semua_jurnal = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Semua Jurnal Saya</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Materi</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($semua_jurnal) > 0): ?>
                    <?php foreach ($semua_jurnal as $jurnal): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($jurnal['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($jurnal['nama_kelas']) ?></td>
                            <td><?= htmlspecialchars($jurnal['nama_mapel']) ?></td>
                            <td><?= htmlspecialchars($jurnal['materi']) ?></td>
                            <td>
                                <?php if (!empty($jurnal['foto_kegiatan'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($jurnal['foto_kegiatan']) ?>" width="80" class="img-thumbnail" alt="Foto Kegiatan">
                                <?php else: ?>
                                    <span class="text-muted">Tidak ada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Belum ada jurnal.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();

$title = "Daftar Jurnal Saya";
include 'template.php';
?>