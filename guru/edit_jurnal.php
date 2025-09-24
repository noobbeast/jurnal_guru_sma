<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if (!isset($_GET['id'])) {
    die("ID jurnal tidak ditemukan.");
}

$jurnal_id = $_GET['id'];

// Pastikan jurnal milik guru yang login
$sql = "SELECT j.*, k.nama_kelas, m.nama_mapel
        FROM jurnal j
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE j.id = ? AND j.guru_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$jurnal_id, $_SESSION['guru_id']]);
$jurnal = $stmt->fetch();

if (!$jurnal) {
    die("Jurnal tidak ditemukan atau bukan milik Anda.");
}

// Ambil kelas & mapel yang diampu guru ini
$sql_kelas_mapel = "SELECT gmk.id, k.nama_kelas, m.nama_mapel, m.id as mapel_id, k.id as kelas_id
                    FROM guru_mapel_kelas gmk
                    JOIN kelas k ON gmk.kelas_id = k.id
                    JOIN mata_pelajaran m ON gmk.mapel_id = m.id
                    WHERE gmk.guru_id = ?
                    ORDER BY k.nama_kelas, m.nama_mapel";
$stmt = $conn->prepare($sql_kelas_mapel);
$stmt->execute([$_SESSION['guru_id']]);
$result_kelas_mapel = $stmt->fetchAll();

$content = '
<div class="card">
    <div class="card-header">
        <h3 class="card-title">✏️ Edit Jurnal Mengajar</h3>
    </div>
    <div class="card-body">
        <form action="proses_edit_jurnal.php" method="POST">
            <input type="hidden" name="id" value="' . $jurnal['id'] . '">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="' . $jurnal['tanggal'] . '" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label>Jam ke-</label>
                        <input type="text" name="jam_ke" class="form-control" value="' . ($jurnal['jam_ke'] ?? '') . '" placeholder="Contoh: 1,2,3">
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label>Kelas & Mapel</label>
                <select name="gmk_id" class="form-control" required>
                    <option value="">-- Pilih Kelas & Mapel --</option>
        ';

foreach ($result_kelas_mapel as $row) {
    $selected = ($row['kelas_id'] == $jurnal['kelas_id'] && $row['mapel_id'] == $jurnal['mapel_id']) ? 'selected' : '';
    $content .= '<option value="' . $row['kelas_id'] . '|' . $row['mapel_id'] . '" ' . $selected . '>' .
                htmlspecialchars($row['nama_kelas']) . ' - ' . htmlspecialchars($row['nama_mapel']) .
                '</option>';
}

$content .= '
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Materi</label>
                <textarea name="materi" class="form-control" rows="3" required>' . htmlspecialchars($jurnal['materi']) . '</textarea>
            </div>

            <div class="form-group mb-3">
                <label>Kegiatan</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="kegiatan[]" value="pendahuluan" ' . ($jurnal['kegiatan_pendahuluan'] ? 'checked' : '') . '>
                    <label class="form-check-label">Pendahuluan</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="kegiatan[]" value="inti" ' . ($jurnal['kegiatan_inti'] ? 'checked' : '') . '>
                    <label class="form-check-label">Inti</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="kegiatan[]" value="penutup" ' . ($jurnal['kegiatan_penutup'] ? 'checked' : '') . '>
                    <label class="form-check-label">Penutup</label>
                </div>
            </div>

            <div class="form-group mb-3">
                <label>Catatan Tambahan</label>
                <textarea name="catatan" class="form-control" rows="2">' . htmlspecialchars($jurnal['catatan'] ?? '') . '</textarea>
            </div>

            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            <a href="rekap_jurnal.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
';

$title = "Edit Jurnal";
include 'template.php';
?>