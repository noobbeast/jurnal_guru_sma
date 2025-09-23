<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil daftar guru untuk wali kelas
$sql_guru = "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id ORDER BY u.nama";
$stmt = $conn->prepare($sql_guru);
$stmt->execute();
$guru_list = $stmt->fetchAll();

// Ambil filter & pagination
$filter_kelas = $_GET['filter_kelas'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Query total data (untuk pagination)
$sql_total = "SELECT COUNT(*) FROM kelas WHERE 1=1";
$params_total = [];

if ($filter_kelas) {
    $sql_total .= " AND id = ?";
    $params_total[] = $filter_kelas;
}

$stmt_total = $conn->prepare($sql_total);
$stmt_total->execute($params_total);
$total_data = $stmt_total->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Query data dengan limit & offset
$sql = "SELECT k.id, k.nama_kelas, u.nama as wali_kelas 
        FROM kelas k 
        LEFT JOIN guru g ON k.wali_kelas_id = g.id 
        LEFT JOIN users u ON g.user_id = u.id 
        WHERE 1=1";

$params = [];

if ($filter_kelas) {
    $sql .= " AND k.id = ?";
    $params[] = $filter_kelas;
}

$sql .= " ORDER BY k.nama_kelas LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$kelas_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Kelas</h3>
                    <div class="card-tools">
                        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                            <i class="fas fa-plus"></i> Tambah Kelas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Form Filter -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Filter Kelas</label>
                            <select name="filter_kelas" class="form-control">
                                <option value="">Semua Kelas</option>
        ';

// Ambil daftar kelas untuk filter
$sql_filter = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt_filter = $conn->prepare($sql_filter);
$stmt_filter->execute();
$kelas_filter = $stmt_filter->fetchAll();

foreach ($kelas_filter as $kelas) {
    $selected = ($kelas['id'] == $filter_kelas) ? 'selected' : '';
    $content .= '<option value="' . $kelas['id'] . '" ' . $selected . '>' . htmlspecialchars($kelas['nama_kelas']) . '</option>';
}

$content .= '
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a href="data_kelas.php" class="btn btn-secondary ms-2">Reset</a>
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
                                    <th>Nama Kelas</th>
                                    <th>Wali Kelas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
        ';

if (count($kelas_list) > 0) {
    foreach ($kelas_list as $kelas) {
        $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($kelas['nama_kelas']) . '</td>
                                    <td>' . htmlspecialchars($kelas['wali_kelas'] ?? '-') . '</td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit' . $kelas['id'] . '">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="proses_kelas.php?hapus=' . $kelas['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus kelas ini?\')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Edit -->
                                <div class="modal fade" id="modalEdit' . $kelas['id'] . '">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="proses_kelas.php">
                                                <input type="hidden" name="id" value="' . $kelas['id'] . '">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">Edit Kelas</h4>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Nama Kelas</label>
                                                        <input type="text" name="nama_kelas" class="form-control" value="' . htmlspecialchars($kelas['nama_kelas']) . '" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Wali Kelas</label>
                                                        <select name="wali_kelas_id" class="form-control">
                                                            <option value="">-- Pilih Wali Kelas --</option>
        ';

        foreach ($guru_list as $guru) {
            $selected = ($guru['id'] == ($kelas['wali_kelas_id'] ?? '')) ? 'selected' : '';
            $content .= '<option value="' . $guru['id'] . '" ' . $selected . '>' . htmlspecialchars($guru['nama']) . '</option>';
        }

        $content .= '
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer justify-content-between">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                                    <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
        ';
    }
} else {
    $content .= '
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada data kelas.</td>
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
    $content .= '<li class="page-item">
                    <a class="page-link" href="?page=' . $prev_page . ($filter_kelas ? '&filter_kelas=' . $filter_kelas : '') . '" tabindex="-1">Previous</a>
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
    $active = ($i == $page) ? 'active' : '';
    $content .= '<li class="page-item ' . $active . '">
                    <a class="page-link" href="?page=' . $i . ($filter_kelas ? '&filter_kelas=' . $filter_kelas : '') . '">' . $i . '</a>
                 </li>';
}

// Tombol Next
if ($page < $total_pages) {
    $next_page = $page + 1;
    $content .= '<li class="page-item">
                    <a class="page-link" href="?page=' . $next_page . ($filter_kelas ? '&filter_kelas=' . $filter_kelas : '') . '">Next</a>
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
            <form method="POST" action="proses_kelas.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Kelas Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Wali Kelas</label>
                        <select name="wali_kelas_id" class="form-control">
                            <option value="">-- Pilih Wali Kelas --</option>
        ';

foreach ($guru_list as $guru) {
    $content .= '<option value="' . $guru['id'] . '">' . htmlspecialchars($guru['nama']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
';

if (isset($_SESSION['success'])) {
    $content = $_SESSION['success'] . $content;
    unset($_SESSION['success']);
}

$title = "Data Kelas";
include 'template.php';
?>