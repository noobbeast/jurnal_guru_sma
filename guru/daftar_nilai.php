<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$guru_id = $_SESSION['guru_id'];

// Cek apakah tabel nilai_siswa ada, jika tidak tampilkan pesan error yang ramah
try {
    $sql = "SELECT ns.tanggal, ns.jenis_penilaian, ns.nilai, ns.catatan, 
                   s.nama as nama_siswa, k.nama_kelas, m.nama_mapel
            FROM nilai_siswa ns
            JOIN siswa s ON ns.siswa_id = s.id
            JOIN kelas k ON ns.kelas_id = k.id
            JOIN mata_pelajaran m ON ns.mapel_id = m.id
            WHERE ns.guru_id = ?
            ORDER BY ns.tanggal DESC, k.nama_kelas, s.nama";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$guru_id]);
    $nilai_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db_error = false;
} catch (PDOException $e) {
    $nilai_list = [];
    $db_error = true;
}

ob_start();
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📊 Daftar Nilai Siswa</h3>
    </div>
    <div class="card-body">
        <a href="input_nilai.php" class="btn btn-primary mb-3">
            <i class="fas fa-plus"></i> Input Nilai Baru
        </a>
        <div class="mb-3">  
            <a href="export_nilai_excel.php" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="export_nilai_pdf.php" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
        <div class="table-responsive">
            <?php if ($db_error): ?>
                <div class="alert alert-danger">
                    <b>Error:</b> Tabel <code>nilai_siswa</code> belum tersedia di database.<br>
                    Silakan hubungi admin untuk membuat tabel <code>nilai_siswa</code> terlebih dahulu.
                </div>
            <?php else: ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis Penilaian</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Siswa</th>
                        <th>Nilai</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($nilai_list) > 0): ?>
                    <?php foreach ($nilai_list as $nilai): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($nilai['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($nilai['jenis_penilaian']) ?></td>
                            <td><?= htmlspecialchars($nilai['nama_kelas']) ?></td>
                            <td><?= htmlspecialchars($nilai['nama_mapel']) ?></td>
                            <td><?= htmlspecialchars($nilai['nama_siswa']) ?></td>
                            <td class="text-center"><strong><?= number_format($nilai['nilai'], 2) ?></strong></td>
                            <td><?= htmlspecialchars($nilai['catatan'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Belum ada data nilai.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

$title = "Daftar Nilai Saya";
include 'template.php';
?>