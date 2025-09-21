<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil daftar kelas & mapel yang diampu
$sql_kelas_mapel = "SELECT gmk.id, k.nama_kelas, m.nama_mapel, m.id as mapel_id, k.id as kelas_id
                    FROM guru_mapel_kelas gmk
                    JOIN kelas k ON gmk.kelas_id = k.id
                    JOIN mata_pelajaran m ON gmk.mapel_id = m.id
                    WHERE gmk.guru_id = ?";
$stmt = $conn->prepare($sql_kelas_mapel);
$stmt->execute([$_SESSION['guru_id']]);
$result_kelas_mapel = $stmt->fetchAll();

$content = '
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📝 Input Nilai Siswa</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="proses_simpan_nilai.php">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Kelas & Mapel</label>
                        <select name="gmk_id" class="form-control" required onchange="loadSiswaNilai(this.value)">
                            <option value="">-- Pilih Kelas & Mapel --</option>
';

foreach ($result_kelas_mapel as $row) {
    $content .= '<option value="' . $row['kelas_id'] . '|' . $row['mapel_id'] . '">' .
                htmlspecialchars($row['nama_kelas']) . ' - ' . htmlspecialchars($row['nama_mapel']) .
                '</option>';
}

$content .= '
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Jenis Penilaian</label>
                        <select name="jenis_penilaian" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Harian">Penilaian Harian</option>
                            <option value="PTS">PTS (Penilaian Tengah Semester)</option>
                            <option value="PAS">PAS (Penilaian Akhir Semester)</option>
                            <option value="Tugas">Tugas</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="' . date('Y-m-d') . '" required>
            </div>

            <div class="form-group mb-3">
                <label>Input Nilai Siswa</label>
                <div id="daftar_siswa_nilai">
                    <div class="alert alert-info">Pilih kelas terlebih dahulu</div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label>Catatan (Opsional)</label>
                <textarea name="catatan" class="form-control" rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">💾 Simpan Nilai</button>
            <a href="dashboard.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<script>
function loadSiswaNilai(value) {
    if (!value) return;
    const [kelas_id, mapel_id] = value.split("|");
    fetch(`get_siswa_nilai.php?kelas_id=${kelas_id}&mapel_id=${mapel_id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById("daftar_siswa_nilai").innerHTML = html;
        });
}
</script>
';

$title = "Input Nilai Siswa";
include 'template.php';
?>