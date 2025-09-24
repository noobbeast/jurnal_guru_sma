<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$guru_id = $_SESSION['guru_id'];

// Ambil daftar kelas yang diampu guru ini
$sql_kelas = "SELECT DISTINCT k.id, k.nama_kelas
              FROM guru_mapel_kelas gmk
              JOIN kelas k ON gmk.kelas_id = k.id
              WHERE gmk.guru_id = ?
              ORDER BY k.nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute([$guru_id]);
$kelas_list = $stmt->fetchAll();

// Ambil filter & data jurnal
$filter_kelas = $_GET['kelas_id'] ?? '';

$sql = "SELECT j.id, j.tanggal, j.jam_ke, k.nama_kelas, m.nama_mapel, j.materi
        FROM jurnal j
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE j.guru_id = ?";

$params = [$guru_id];

if ($filter_kelas) {
    $sql .= " AND j.kelas_id = ?";
    $params[] = $filter_kelas;
}

$sql .= " ORDER BY j.tanggal DESC, k.nama_kelas, m.nama_mapel";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$semua_jurnal = $stmt->fetchAll();

$content = '
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📊 Daftar Jurnal Saya</h3>
        <div class="card-tools">
            <a href="export_jurnal_pdf.php' . ($filter_kelas ? '?kelas_id=' . $filter_kelas : '') . '" class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Form Filter -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Filter Kelas</label>
                <select name="kelas_id" class="form-control">
                    <option value="">Semua Kelas</option>
';

foreach ($kelas_list as $kelas) {
    $selected = ($kelas['id'] == $filter_kelas) ? 'selected' : '';
    $content .= '<option value="' . $kelas['id'] . '" ' . $selected . '>' . htmlspecialchars($kelas['nama_kelas']) . '</option>';
}

$content .= '
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <a href="daftar_jurnal.php" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>

        <!-- Tabel Data -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam ke-</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Materi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
';

if (count($semua_jurnal) > 0) {
    foreach ($semua_jurnal as $jurnal) {
        $content .= '
                    <tr>
                        <td>' . format_tanggal_indonesia($jurnal['tanggal']) . '</td>
                        <td>' . ($jurnal['jam_ke'] ? $jurnal['jam_ke'] : '-') . '</td>
                        <td>' . htmlspecialchars($jurnal['nama_kelas']) . '</td>
                        <td>' . htmlspecialchars($jurnal['nama_mapel']) . '</td>
                        <td>' . htmlspecialchars($jurnal['materi']) . '</td>
                        <td>
                            <a href="edit_jurnal.php?id=' . $jurnal['id'] . '" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="proses_hapus_jurnal.php?id=' . $jurnal['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus jurnal ini?\')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
        ';
    }
} else {
    $content .= '
                    <tr>
                        <td colspan="6" class="text-center">Belum ada jurnal.</td>
                    </tr>
        ';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>
';

// Fungsi format tanggal
function format_tanggal_indonesia($tanggal_mysql) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $timestamp = strtotime($tanggal_mysql);
    $nama_hari = $hari[date('w', $timestamp)];
    $tanggal = date('j', $timestamp);
    $nama_bulan = $bulan[date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    return "$nama_hari, $tanggal $nama_bulan $tahun";
}

$title = "Daftar Jurnal Saya";
include 'template.php';
?>