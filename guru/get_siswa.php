<?php
include '../koneksi.php';

if (isset($_GET['kelas_id'])) {
    $kelas_id = $_GET['kelas_id'];
    $sql = "SELECT id, nama FROM siswa WHERE kelas_id = ? ORDER BY nama ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kelas_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($result) > 0) {
        // Tombol Pilih Semua Hadir & Alfa
        echo '<div class="mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="selectAllHadir()">
                <i class="fas fa-check-circle"></i> Pilih Semua (Hadir)
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="selectAllAlfa()">
                <i class="fas fa-times-circle"></i> Tandai Semua Alfa
            </button>
        </div>';

        foreach ($result as $siswa) {
            echo '<div class="mb-2">';
            echo '<label class="form-label mb-0">' . htmlspecialchars($siswa['nama']) . '</label>';
            echo '<select name="absen_status[' . $siswa['id'] . ']" class="form-select form-select-sm absen-select">';
            echo '<option value="H">Hadir</option>';
            echo '<option value="S">Sakit</option>';
            echo '<option value="I">Izin</option>';
            echo '<option value="A">Alpha</option>';
            echo '</select>';
            echo '</div>';
        }

        // Script JS untuk Pilih Semua
        echo '<script>
        function selectAllHadir() {
            document.querySelectorAll(".absen-select").forEach(select => {
                select.value = "H";
            });
        }
        function selectAllAlfa() {
            document.querySelectorAll(".absen-select").forEach(select => {
                select.value = "A";
            });
        }
        </script>';
    } else {
        echo '<div class="alert alert-warning">Belum ada siswa di kelas ini.</div>';
    }
}
?>