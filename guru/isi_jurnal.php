<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$sql_kelas_mapel = "SELECT gmk.id, k.nama_kelas, m.nama_mapel, m.id as mapel_id, k.id as kelas_id
                    FROM guru_mapel_kelas gmk
                    JOIN kelas k ON gmk.kelas_id = k.id
                    JOIN mata_pelajaran m ON gmk.mapel_id = m.id
                    WHERE gmk.guru_id = ?";
$stmt = $conn->prepare($sql_kelas_mapel);
$stmt->execute([$_SESSION['guru_id']]);
$result_kelas_mapel = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📝 Isi Jurnal Mengajar Baru</h3>
    </div>
    <div class="card-body">
        <form action="proses_simpan_jurnal.php" method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label>Kelas & Mapel</label>
                <select name="gmk_id" class="form-control" required onchange="loadSiswa(this.value)">
                    <option value="">-- Pilih Kelas & Mapel --</option>
                    <?php foreach ($result_kelas_mapel as $row): ?>
                        <option value="<?= $row['kelas_id'] . '|' . $row['mapel_id'] ?>">
                            <?= htmlspecialchars($row['nama_kelas']) . ' - ' . htmlspecialchars($row['nama_mapel']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label>Materi</label>
                <textarea name="materi" class="form-control" rows="3" required></textarea>
            </div>
            <div class="form-group mb-3">
                <label>Kegiatan</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="kegiatan[]" value="pendahuluan" id="kegiatan_pendahuluan">
                    <label class="form-check-label" for="kegiatan_pendahuluan">Pendahuluan</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="kegiatan[]" value="inti" id="kegiatan_inti">
                    <label class="form-check-label" for="kegiatan_inti">Inti</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="kegiatan[]" value="penutup" id="kegiatan_penutup">
                    <label class="form-check-label" for="kegiatan_penutup">Penutup</label>
                </div>
            </div>
            <div class="form-group mb-3">
                <label>Absensi Siswa</label>
                <div id="daftar_siswa" class="border p-3 rounded bg-light">
                    <div class="text-info">Pilih kelas terlebih dahulu</div>
                </div>
            </div>
            <div class="form-group mb-3">
                <label>Upload Foto Kegiatan</label>
                <input type="file" name="foto_kegiatan" class="form-control" accept="image/*">
                <small class="text-muted">Format: JPG, PNG. Maks: 2MB.</small>
            </div>
            <div class="form-group mb-3">
                <label>Catatan Tambahan</label>
                <textarea name="catatan" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">💾 Simpan Jurnal</button>
            <a href="dashboard.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
<style>
    select[name^="absen_status"] {
        transition: all 0.2s;
    }
    select[name^="absen_status"]:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>
<script>
function loadSiswa(value) {
    if (!value) {
        document.getElementById("daftar_siswa").innerHTML = '<div class="text-info">Pilih kelas terlebih dahulu</div>';
        return;
    }
    const [kelas_id, mapel_id] = value.split("|");
    fetch(`get_siswa.php?kelas_id=${kelas_id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById("daftar_siswa").innerHTML = html;
        });
}

// Tambahkan fungsi berikut agar tombol Pilih Semua bisa dipakai setelah AJAX
function selectAllHadir() {
    document.querySelectorAll("#daftar_siswa .absen-select").forEach(select => {
        select.value = "H";
    });
}
function selectAllAlfa() {
    document.querySelectorAll("#daftar_siswa .absen-select").forEach(select => {
        select.value = "A";
    });
}
</script>
<?php
$content = ob_get_clean();

$title = "Isi Jurnal Baru";
include 'template.php';
?>