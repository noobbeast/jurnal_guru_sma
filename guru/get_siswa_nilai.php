<?php
include '../koneksi.php';

if (isset($_GET['kelas_id']) && isset($_GET['mapel_id'])) {
    $kelas_id = $_GET['kelas_id'];
    $mapel_id = $_GET['mapel_id'];

    $sql = "SELECT s.id, s.nama 
            FROM siswa s 
            WHERE s.kelas_id = ? 
            ORDER BY s.nama ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$kelas_id]);
    $result = $stmt->fetchAll();

    if (count($result) > 0) {
        echo '<div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Nilai (0-100)</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($result as $siswa) {
            echo '<tr>
                    <td>' . htmlspecialchars($siswa['nama']) . '</td>
                    <td>
                        <input type="number" name="nilai[' . $siswa['id'] . ']" 
                               class="form-control" min="0" max="100" step="0.01" required>
                    </td>
                  </tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="alert alert-warning">Belum ada siswa di kelas ini.</div>';
    }
}
?>