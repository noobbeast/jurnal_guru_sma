<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil data untuk dropdown
$sql_guru = "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id ORDER BY u.nama";
$stmt = $conn->prepare($sql_guru);
$stmt->execute();
$guru_list = $stmt->fetchAll();

$sql_mapel = "SELECT id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel";
$stmt = $conn->prepare($sql_mapel);
$stmt->execute();
$mapel_list = $stmt->fetchAll();

$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute();
$kelas_list = $stmt->fetchAll();

// Ambil filter & pagination
$filter_guru = $_GET['filter_guru'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Query total data (untuk pagination)
$sql_total = "SELECT COUNT(*) FROM guru_mapel_kelas gmk WHERE 1=1";
$params_total = [];

if ($filter_guru) {
    $sql_total .= " AND gmk.guru_id = ?";
    $params_total[] = $filter_guru;
}

$stmt_total = $conn->prepare($sql_total);
$stmt_total->execute($params_total);
$total_data = $stmt_total->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Query utama dengan limit & offset
$sql = "SELECT gmk.id, u.nama as nama_guru, m.nama_mapel, k.nama_kelas
        FROM guru_mapel_kelas gmk
        JOIN guru g ON gmk.guru_id = g.id
        JOIN users u ON g.user_id = u.id
        JOIN mata_pelajaran m ON gmk.mapel_id = m.id
        JOIN kelas k ON gmk.kelas_id = k.id
        WHERE 1=1";

$params = [];

if ($filter_guru) {
    $sql .= " AND gmk.guru_id = ?";
    $params[] = $filter_guru;
}

$sql .= " ORDER BY u.nama, k.nama_kelas, m.nama_mapel LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$relasi_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Relasi Guru - Mapel - Kelas</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                            <i class="fas fa-plus"></i> Tambah Relasi
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Form Filter -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Filter Guru</label>
                            <select name="filter_guru" class="form-control">
                                <option value="">Semua Guru</option>
        ';

foreach ($guru_list as $guru) {
    $selected = ($guru['id'] == $filter_guru) ? 'selected' : '';
    $content .= '<option value="' . $guru['id'] . '" ' . $selected . '>' . htmlspecialchars($guru['nama']) . '</option>';
}

$content .= '
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a href="data_relasi.php" class="btn btn-secondary ms-2">Reset</a>
                        </div>
                    </form>

                    <!-- Info Pagination -->
                    <div class="mb-3">
                        <small class="text-muted">
                            Menampilkan ' . (($offset + 1) > $total_data ? $total_data : ($offset + 1)) . ' - ' . min($offset + $limit, $total_data) . ' dari ' . $total_data . ' data
                        </small>
                    </div>

                    <!-- Tabel Data -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Guru</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Kelas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
        ';

if (count($relasi_list) > 0) {
    foreach ($relasi_list as $relasi) {
        $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($relasi['nama_guru']) . '</td>
                                    <td>' . htmlspecialchars($relasi['nama_mapel']) . '</td>
                                    <td>' . htmlspecialchars($relasi['nama_kelas']) . '</td>
                                    <td>
                                        <a href="proses_relasi.php?hapus=' . $relasi['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus relasi ini?\')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
        ';
    }
} else {
    $content .= '
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data relasi.</td>
                                </tr>
        ';
}

$content .= '
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center">
        ';

// Tombol Previous
if ($page > 1) {
    $prev_page = $page - 1;
    $query_string = "page=" . $prev_page;
    if ($filter_guru) $query_string .= "&filter_guru=" . $filter_guru;
    $content .= '<li class="page-item">
                    <a class="page-link" href="?' . $query_string . '" tabindex="-1">Previous</a>
                 </li>';
} else {
    $content .= '<li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                 </li>';
}

// Nomor halaman (maks 5 halaman ditampilkan)
$start_page = max(1, $page - 2);
$end_page = min($total_pages, $page + 2);

for ($i = $start_page; $i <= $end_page; $i++) {
    $query_string = "page=" . $i;
    if ($filter_guru) $query_string .= "&filter_guru=" . $filter_guru;
    $active = ($i == $page) ? 'active' : '';
    $content .= '<li class="page-item ' . $active . '">
                    <a class="page-link" href="?' . $query_string . '">' . $i . '</a>
                 </li>';
}

// Tombol Next
if ($page < $total_pages) {
    $next_page = $page + 1;
    $query_string = "page=" . $next_page;
    if ($filter_guru) $query_string .= "&filter_guru=" . $filter_guru;
    $content .= '<li class="page-item">
                    <a class="page-link" href="?' . $query_string . '">Next</a>
                 </li>';
} else {
    $content .= '<li class="page-item disabled">
                    <a class="page-link" href="#">Next</a>
                 </li>';
}

$content .= '
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="proses_relasi.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Relasi Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Guru</label>
                        <select name="guru_id" class="form-control" required>
                            <option value="">-- Pilih Guru --</option>
        ';

foreach ($guru_list as $guru) {
    $content .= '<option value="' . $guru['id'] . '">' . htmlspecialchars($guru['nama']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mata Pelajaran</label>
                        <select name="mapel_id" class="form-control" required>
                            <option value="">-- Pilih Mapel --</option>
        ';

foreach ($mapel_list as $mapel) {
    $content .= '<option value="' . $mapel['id'] . '">' . htmlspecialchars($mapel['nama_mapel']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="kelas_id" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
        ';

foreach ($kelas_list as $kelas) {
    $content .= '<option value="' . $kelas['id'] . '">' . htmlspecialchars($kelas['nama_kelas']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan Relasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
';

if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    unset($_SESSION['success']);
}

$title = "Relasi Guru-Mapel-Kelas";
include 'template.php';
?>