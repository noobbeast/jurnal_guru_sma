<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$guru_id = $_SESSION['guru_id'];

// Ambil daftar kelas & mapel yang diampu (untuk filter)
$sql_kelas = "SELECT DISTINCT k.id, k.nama_kelas
              FROM guru_mapel_kelas gmk
              JOIN kelas k ON gmk.kelas_id = k.id
              WHERE gmk.guru_id = ?
              ORDER BY k.nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute([$guru_id]);
$kelas_list = $stmt->fetchAll();

$sql_mapel = "SELECT DISTINCT m.id, m.nama_mapel
              FROM guru_mapel_kelas gmk
              JOIN mata_pelajaran m ON gmk.mapel_id = m.id
              WHERE gmk.guru_id = ?
              ORDER BY m.nama_mapel";
$stmt = $conn->prepare($sql_mapel);
$stmt->execute([$guru_id]);
$mapel_list = $stmt->fetchAll();

// Ambil filter dari GET
$filter_kelas = $_GET['kelas_id'] ?? '';
$filter_mapel = $_GET['mapel_id'] ?? '';

// Query untuk rekap nilai (dengan filter)
$sql_kelas_mapel = "SELECT DISTINCT k.id as kelas_id, k.nama_kelas, m.id as mapel_id, m.nama_mapel
                    FROM guru_mapel_kelas gmk
                    JOIN kelas k ON gmk.kelas_id = k.id
                    JOIN mata_pelajaran m ON gmk.mapel_id = m.id
                    WHERE gmk.guru_id = ?";

$params = [$guru_id];

if ($filter_kelas) {
    $sql_kelas_mapel .= " AND k.id = ?";
    $params[] = $filter_kelas;
}

if ($filter_mapel) {
    $sql_kelas_mapel .= " AND m.id = ?";
    $params[] = $filter_mapel;
}

$sql_kelas_mapel .= " ORDER BY k.nama_kelas, m.nama_mapel";

$stmt = $conn->prepare($sql_kelas_mapel);
$stmt->execute($params);
$kelas_mapel_list = $stmt->fetchAll();

// Inisialisasi data rekap
$rekap_nilai = [];

foreach ($kelas_mapel_list as $km) {
    $kelas_id = $km['kelas_id'];
    $mapel_id = $km['mapel_id'];

    // Ambil semua siswa di kelas ini
    $sql_siswa = "SELECT id, nama FROM siswa WHERE kelas_id = ? ORDER BY nama";
    $stmt_siswa = $conn->prepare($sql_siswa);
    $stmt_siswa->execute([$kelas_id]);
    $siswa_list = $stmt_siswa->fetchAll();

    foreach ($siswa_list as $siswa) {
        // Ambil nilai per jenis penilaian
        $sql_nilai = "SELECT jenis_penilaian, AVG(nilai) as rata_rata
                      FROM nilai_siswa
                      WHERE guru_id = ? AND kelas_id = ? AND mapel_id = ? AND siswa_id = ?
                      GROUP BY jenis_penilaian";
        $stmt_nilai = $conn->prepare($sql_nilai);
        $stmt_nilai->execute([$guru_id, $kelas_id, $mapel_id, $siswa['id']]);
        $nilai_list = $stmt_nilai->fetchAll();

        // Hitung nilai per jenis
        $nilai_harian = 0;
        $nilai_pts = 0;
        $nilai_pas = 0;
        $nilai_tugas = 0;

        foreach ($nilai_list as $n) {
            switch ($n['jenis_penilaian']) {
                case 'Harian': $nilai_harian = $n['rata_rata']; break;
                case 'PTS': $nilai_pts = $n['rata_rata']; break;
                case 'PAS': $nilai_pas = $n['rata_rata']; break;
                case 'Tugas': $nilai_tugas = $n['rata_rata']; break;
            }
        }

        // Hitung nilai akhir (bobot: harian 40%, pts 20%, pas 30%, tugas 10%)
        $nilai_akhir = ($nilai_harian * 0.4) + ($nilai_pts * 0.2) + ($nilai_pas * 0.3) + ($nilai_tugas * 0.1);

        // Simpan ke array rekap
        $rekap_nilai[] = [
            'kelas' => $km['nama_kelas'],
            'mapel' => $km['nama_mapel'],
            'siswa' => $siswa['nama'],
            'harian' => number_format($nilai_harian, 2),
            'pts' => number_format($nilai_pts, 2),
            'pas' => number_format($nilai_pas, 2),
            'tugas' => number_format($nilai_tugas, 2),
            'akhir' => number_format($nilai_akhir, 2),
            'grade' => getGrade($nilai_akhir)
        ];
    }
}

// Fungsi konversi nilai ke grade
function getGrade($nilai) {
    if ($nilai >= 85) return 'A';
    if ($nilai >= 75) return 'B';
    if ($nilai >= 65) return 'C';
    if ($nilai >= 55) return 'D';
    return 'E';
}

ob_start();
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Rekap Nilai Akhir per Siswa</h3>
                    <div class="card-tools">
                        <a href="export_nilai_akhir_excel.php<?= 
                            ($filter_kelas ? '?kelas_id=' . $filter_kelas : '') . 
                            ($filter_mapel ? ($filter_kelas ? '&' : '?') . 'mapel_id=' . $filter_mapel : '') 
                        ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel"></i> Export Excel
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
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id'] ?>" <?= ($kelas['id'] == $filter_kelas) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Filter Mapel</label>
                            <select name="mapel_id" class="form-control">
                                <option value="">Semua Mapel</option>
                                <?php foreach ($mapel_list as $mapel): ?>
                                    <option value="<?= $mapel['id'] ?>" <?= ($mapel['id'] == $filter_mapel) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a href="rekap_nilai_akhir.php" class="btn btn-secondary ms-2">Reset</a>
                        </div>
                    </form>

                    <!-- Tabel Rekap -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Mapel</th>
                                    <th>Siswa</th>
                                    <th>Harian<br>(40%)</th>
                                    <th>PTS<br>(20%)</th>
                                    <th>PAS<br>(30%)</th>
                                    <th>Tugas<br>(10%)</th>
                                    <th>Nilai Akhir</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rekap_nilai) > 0): ?>
                                    <?php foreach ($rekap_nilai as $data): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($data['kelas']) ?></td>
                                            <td><?= htmlspecialchars($data['mapel']) ?></td>
                                            <td><?= htmlspecialchars($data['siswa']) ?></td>
                                            <td class="text-center"><?= $data['harian'] ?></td>
                                            <td class="text-center"><?= $data['pts'] ?></td>
                                            <td class="text-center"><?= $data['pas'] ?></td>
                                            <td class="text-center"><?= $data['tugas'] ?></td>
                                            <td class="text-center fw-bold"><?= $data['akhir'] ?></td>
                                            <td class="text-center fw-bold"><?= $data['grade'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Belum ada data nilai.</td>
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
$content = ob_get_clean();

$title = "Rekap Nilai Akhir";
include 'template.php';
?>