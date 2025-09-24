<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil daftar kelas untuk filter
$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute();
$kelas_list = $stmt->fetchAll();

// Ambil filter
$filter_kelas = $_GET['kelas_id'] ?? '';

// Query rekap absensi
$sql = "SELECT 
            s.id as siswa_id,
            s.nis,
            s.nama,
            k.nama_kelas,
            COUNT(CASE WHEN a.status = 'H' THEN 1 END) as hadir,
            COUNT(CASE WHEN a.status = 'S' THEN 1 END) as sakit,
            COUNT(CASE WHEN a.status = 'I' THEN 1 END) as izin,
            COUNT(CASE WHEN a.status = 'A' THEN 1 END) as alfa,
            COUNT(a.id) as total_jurnal
        FROM siswa s
        JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN absensi a ON s.id = a.siswa_id
        WHERE 1=1";

$params = [];

if ($filter_kelas) {
    $sql .= " AND k.id = ?";
    $params[] = $filter_kelas;
}

$sql .= " GROUP BY s.id, s.nis, s.nama, k.nama_kelas
          ORDER BY k.nama_kelas, s.nama";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rekap_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Rekap Absensi Siswa</h3>
                    <div class="card-tools">
                        <a href="export_absensi_excel.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '') . '" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="export_absensi_pdf.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '') . '" class="btn btn-danger btn-sm" target="_blank">
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
                            <a href="rekap_absensi.php" class="btn btn-secondary ms-2">Reset</a>
                        </div>
                    </form>

                    <!-- Tabel Rekap -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Hadir</th>
                                    <th>Sakit</th>
                                    <th>Izin</th>
                                    <th>Alfa</th>
                                    <th>Total Jurnal</th>
                                </tr>
                            </thead>
                            <tbody>
        ';

if (count($rekap_list) > 0) {
    foreach ($rekap_list as $siswa) {
        $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($siswa['nis']) . '</td>
                                    <td>' . htmlspecialchars($siswa['nama']) . '</td>
                                    <td>' . htmlspecialchars($siswa['nama_kelas']) . '</td>
                                    <td class="text-center bg-success text-white">' . $siswa['hadir'] . '</td>
                                    <td class="text-center bg-warning">' . $siswa['sakit'] . '</td>
                                    <td class="text-center bg-info text-white">' . $siswa['izin'] . '</td>
                                    <td class="text-center bg-danger text-white">' . $siswa['alfa'] . '</td>
                                    <td class="text-center fw-bold">' . $siswa['total_jurnal'] . '</td>
                                </tr>
        ';
    }
} else {
    $content .= '
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada data absensi.</td>
                                </tr>
        ';
}

$content .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$title = "Rekap Absensi Siswa";
include 'template.php';
?>