<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if (!isset($_GET['id'])) {
    die("ID jurnal tidak ditemukan.");
}

$jurnal_id = $_GET['id'];

// Ambil data jurnal
$sql = "SELECT j.*, u.nama as nama_guru, k.nama_kelas, m.nama_mapel
        FROM jurnal j
        JOIN guru g ON j.guru_id = g.id
        JOIN users u ON g.user_id = u.id
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$jurnal_id]);
$jurnal = $stmt->fetch();

if (!$jurnal) {
    die("Jurnal tidak ditemukan.");
}

// Ambil daftar guru, kelas, mapel untuk dropdown
$sql_guru = "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id ORDER BY u.nama";
$stmt = $conn->prepare($sql_guru);
$stmt->execute();
$guru_list = $stmt->fetchAll();

$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute();
$kelas_list = $stmt->fetchAll();

$sql_mapel = "SELECT id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel";
$stmt = $conn->prepare($sql_mapel);
$stmt->execute();
$mapel_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
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

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Guru</label>
                                    <select name="guru_id" class="form-control" required>
                                        <option value="">-- Pilih Guru --</option>
        ';

foreach ($guru_list as $guru) {
    $selected = ($guru['id'] == $jurnal['guru_id']) ? 'selected' : '';
    $content .= '<option value="' . $guru['id'] . '" ' . $selected . '>' . htmlspecialchars($guru['nama']) . '</option>';
}

$content .= '
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Kelas</label>
                                    <select name="kelas_id" class="form-control" required>
                                        <option value="">-- Pilih Kelas --</option>
        ';

foreach ($kelas_list as $kelas) {
    $selected = ($kelas['id'] == $jurnal['kelas_id']) ? 'selected' : '';
    $content .= '<option value="' . $kelas['id'] . '" ' . $selected . '>' . htmlspecialchars($kelas['nama_kelas']) . '</option>';
}

$content .= '
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Mapel</label>
                                    <select name="mapel_id" class="form-control" required>
                                        <option value="">-- Pilih Mapel --</option>
        ';

foreach ($mapel_list as $mapel) {
    $selected = ($mapel['id'] == $jurnal['mapel_id']) ? 'selected' : '';
    $content .= '<option value="' . $mapel['id'] . '" ' . $selected . '>' . htmlspecialchars($mapel['nama_mapel']) . '</option>';
}

$content .= '
                                    </select>
                                </div>
                            </div>
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
        </div>
    </div>
</div>
';

$title = "Edit Jurnal";
include 'template.php';
?>