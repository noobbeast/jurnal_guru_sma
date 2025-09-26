<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Fungsi auto-detect tahun ajaran & semester
function getTahunAjaranSemesterOtomatis() {
    $bulan = date('n'); // 1-12
    $tahun = date('Y');
    $tanggal_sekarang = date('d/m/Y');
    
    if ($bulan >= 7) {
        return [
            'tahun_ajaran' => "$tahun/" . ($tahun + 1),
            'semester' => 'Ganjil',
            'tanggal' => $tanggal_sekarang
        ];
    } else {
        return [
            'tahun_ajaran' => ($tahun - 1) . "/$tahun",
            'semester' => 'Genap',
            'tanggal' => $tanggal_sekarang
        ];
    }
}

// Dapatkan setting otomatis
$setting_otomatis = getTahunAjaranSemesterOtomatis();

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
        <!-- Tampilkan info tahun ajaran otomatis -->
        <div class="alert alert-info mb-3">
            <i class="fas fa-calendar-alt"></i> 
            <strong>Tahun Ajaran Otomatis:</strong> <?= htmlspecialchars($setting_otomatis['tahun_ajaran']) ?> | 
            <strong>Semester:</strong> <?= htmlspecialchars($setting_otomatis['semester']) ?>
            <br>
            <small class="text-muted">Berdasarkan tanggal hari ini: <?= $setting_otomatis['tanggal'] ?></small>
        </div>

        <form action="proses_simpan_jurnal.php" method="POST" enctype="multipart/form-data">
            <!-- Hidden input untuk tahun ajaran dan semester -->
            <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($setting_otomatis['tahun_ajaran']) ?>">
            <input type="hidden" name="semester" value="<?= htmlspecialchars($setting_otomatis['semester']) ?>">
            
            <div class="form-group mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" id="input_tanggal" class="form-control" required>
                <div id="tampilan_hari" class="mt-2 text-primary fw-bold"></div>
            </div>

            <div class="mb-3">
                <label><i class="fas fa-clock"></i> Jam ke-</label>
                <select name="jam_ke[]" id="jam_ke_select" class="form-control" multiple size="5" style="height: auto;">
                    <option value="1">Jam ke-1</option>
                    <option value="2">Jam ke-2</option>
                    <option value="3">Jam ke-3</option>
                    <option value="4">Jam ke-4</option>
                    <option value="5">Jam ke-5</option>
                    <option value="6">Jam ke-6</option>
                    <option value="7">Jam ke-7</option>
                    <option value="8">Jam ke-8</option>
                    <option value="9">Jam ke-9</option>
                    <option value="10">Jam ke-10</option>
                </select>
                <style>
                #jam_ke_select option:checked {
                    background-color: #0d6efd;
                    color: white;
                }
                </style>
                <small class="text-muted d-block mt-1">
                    ✅ Klik saja untuk pilih/deselect jam. Tidak perlu tekan Ctrl.
                </small>
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
document.getElementById('input_tanggal').addEventListener('change', function() {
    const tanggal = this.value;
    if (tanggal) {
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const d = new Date(tanggal);
        const hari = days[d.getDay()];
        document.getElementById('tampilan_hari').innerText = '📅 Hari: ' + hari;
    } else {
        document.getElementById('tampilan_hari').innerText = '';
    }
});

// Memudahkan multi-select jam_ke tanpa Ctrl
document.addEventListener('DOMContentLoaded', function() {
    var jamSelect = document.getElementById('jam_ke_select');
    if (jamSelect) {
        jamSelect.addEventListener('mousedown', function(e) {
            if (e.target.tagName === 'OPTION') {
                e.preventDefault();
                e.target.selected = !e.target.selected;
            }
        });
    }
});

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

// Fungsi global agar tombol Pilih Semua bisa dipakai setelah AJAX
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