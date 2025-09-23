<?php
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

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Filter
$filter_kelas = $_GET['kelas_id'] ?? '';
$filter_guru = $_GET['guru_id'] ?? '';
$filter_mapel = $_GET['mapel_id'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

// Ambil data untuk filter
$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute();
$kelas_options = $stmt->fetchAll();

$sql_guru = "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id ORDER BY u.nama";
$stmt = $conn->prepare($sql_guru);
$stmt->execute();
$guru_options = $stmt->fetchAll();

$sql_mapel = "SELECT id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel";
$stmt = $conn->prepare($sql_mapel);
$stmt->execute();
$mapel_options = $stmt->fetchAll();

// Query utama
$sql = "SELECT j.id, j.tanggal, j.jam_ke, u.nama as nama_guru, k.nama_kelas, m.nama_mapel, j.materi
        FROM jurnal j
        JOIN guru g ON j.guru_id = g.id
        JOIN users u ON g.user_id = u.id
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE 1=1";

$params = [];

if ($filter_kelas) {
    $sql .= " AND j.kelas_id = ?";
    $params[] = $filter_kelas;
}
if ($filter_guru) {
    $sql .= " AND j.guru_id = ?";
    $params[] = $filter_guru;
}
if ($filter_mapel) {
    $sql .= " AND j.mapel_id = ?";
    $params[] = $filter_mapel;
}
if ($filter_bulan) {
    $sql .= " AND j.tanggal LIKE ?";
    $params[] = $filter_bulan . '%';
}

$sql .= " ORDER BY j.tanggal DESC, k.nama_kelas, u.nama";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jurnal_list = $stmt->fetchAll();

ob_start();
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rekap Jurnal Mengajar</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>
                            <select name="kelas_id" class="form-control">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_options as $kelas): ?>
                                    <option value="<?= $kelas['id'] ?>" <?= ($kelas['id'] == $filter_kelas) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Guru</label>
                            <select name="guru_id" class="form-control">
                                <option value="">Semua Guru</option>
                                <?php foreach ($guru_options as $guru): ?>
                                    <option value="<?= $guru['id'] ?>" <?= ($guru['id'] == $filter_guru) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($guru['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mapel</label>
                            <select name="mapel_id" class="form-control">
                                <option value="">Semua Mapel</option>
                                <?php foreach ($mapel_options as $mapel): ?>
                                    <option value="<?= $mapel['id'] ?>" <?= ($mapel['id'] == $filter_mapel) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulan</label>
                            <input type="month" name="bulan" class="form-control" value="<?= htmlspecialchars($filter_bulan) ?>">
                        </div>
                        <div class="col-md-12 mt-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="rekap_jurnal.php" class="btn btn-secondary">Reset</a>
                            <a href="export_jurnal_excel.php<?= $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" 
                               class="btn btn-success" target="_blank">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </a>
                            <a href="export_jurnal_pdf.php<?= $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" 
                               class="btn btn-danger" target="_blank">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam ke-</th>
                                    <th>Guru</th>
                                    <th>Kelas</th>
                                    <th>Mapel</th>
                                    <th>Materi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($jurnal_list) > 0): ?>
                                    <?php foreach ($jurnal_list as $jurnal): ?>
                                        <tr>
                                            <td><?= format_tanggal_indonesia($jurnal['tanggal']) ?></td>
                                            <td>
                                                <?php
                                                if (!empty($jurnal['jam_ke'])) {
                                                    $jam_arr = explode(',', $jurnal['jam_ke']);
                                                    sort($jam_arr, SORT_NUMERIC);
                                                    if (count($jam_arr) > 1) {
                                                        echo min($jam_arr) . '-' . max($jam_arr);
                                                    } else {
                                                        echo $jam_arr[0];
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($jurnal['nama_guru']) ?></td>
                                            <td><?= htmlspecialchars($jurnal['nama_kelas']) ?></td>
                                            <td><?= htmlspecialchars($jurnal['nama_mapel']) ?></td>
                                            <td><?= htmlspecialchars($jurnal['materi']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="edit_jurnal.php?id=<?= $jurnal['id'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="proses_hapus_jurnal.php?id=<?= $jurnal['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus jurnal ini?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                    <a href="detail_jurnal.php?id=<?= $jurnal['id'] ?>" class="btn btn-info btn-sm" target="_blank">
                                                        <i class="fas fa-eye"></i> Detail Absen
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data jurnal.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if (isset($_SESSION['success'])) {
    echo '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> ' . $_SESSION['success'] . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    ';
    unset($_SESSION['success']);
}

$title = "Rekap Jurnal";
$content = ob_get_clean();
include 'template.php';
?>