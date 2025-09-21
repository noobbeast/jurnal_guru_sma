<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil daftar kelas
$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute();
$kelas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil semua siswa
$sql = "SELECT s.id, s.nis, s.nama, s.kelas_id, k.nama_kelas 
        FROM siswa s 
        JOIN kelas k ON s.kelas_id = k.id 
        ORDER BY k.nama_kelas, s.nama";
$stmt = $conn->prepare($sql);
$stmt->execute();
$siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses tambah/edit siswa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $nis = $_POST['nis'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $kelas_id = $_POST['kelas_id'] ?? null;

        if (empty($nis) || empty($nama) || empty($kelas_id)) {
            $_SESSION['success'] = "Error: Semua field wajib diisi!";
            header("Location: data_siswa.php");
            exit;
        }

        $sql = "INSERT INTO siswa (nis, nama, kelas_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nis, $nama, $kelas_id]);

        $_SESSION['success'] = "Siswa berhasil ditambahkan!";
        header("Location: data_siswa.php");
        exit;
    }

    if (isset($_POST['edit'])) {
        $id = $_POST['id'] ?? null;
        $nis = $_POST['nis'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $kelas_id = $_POST['kelas_id'] ?? null;

        if (!$id || empty($nis) || empty($nama) || empty($kelas_id)) {
            $_SESSION['success'] = "Error: Data tidak lengkap!";
            header("Location: data_siswa.php");
            exit;
        }

        $sql = "UPDATE siswa SET nis = ?, nama = ?, kelas_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nis, $nama, $kelas_id, $id]);

        $_SESSION['success'] = "Data siswa berhasil diubah!";
        header("Location: data_siswa.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    $sql = "DELETE FROM absensi WHERE siswa_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $sql = "DELETE FROM siswa WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $_SESSION['success'] = "Siswa berhasil dihapus!";
    header("Location: data_siswa.php");
    exit;
}

ob_start();
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Siswa</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                            <i class="fas fa-plus"></i> Tambah Siswa
                        </button>
                    </div>
                </div>
                <div class="card-tools">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
        <i class="fas fa-plus"></i> Tambah Siswa
    </button>
    <a href="impor_siswa.php" class="btn btn-success ml-2">
        <i class="fas fa-file-excel"></i> Impor Excel
    </a>
</div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswa_list) > 0): ?>
                                <?php foreach ($siswa_list as $siswa): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                        <td><?= htmlspecialchars($siswa['nama']) ?></td>
                                        <td><?= htmlspecialchars($siswa['nama_kelas']) ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit<?= $siswa['id'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="data_siswa.php?hapus=<?= $siswa['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus siswa ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <!-- Modal Edit -->
                                    <div class="modal fade" id="modalEdit<?= $siswa['id'] ?>">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="data_siswa.php">
                                                    <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Edit Siswa</h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>NIS</label>
                                                            <input type="text" name="nis" class="form-control" value="<?= htmlspecialchars($siswa['nis']) ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Nama</label>
                                                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($siswa['nama']) ?>" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Kelas</label>
                                                            <select name="kelas_id" class="form-control" required>
                                                                <?php foreach ($kelas_list as $kelas): ?>
                                                                    <option value="<?= $kelas['id'] ?>" <?= ($kelas['id'] == $siswa['kelas_id']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
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
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada data siswa.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="data_siswa.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Siswa Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>NIS</label>
                        <input type="text" name="nis" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="kelas_id" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>"><?= htmlspecialchars($kelas['nama_kelas']) ?></option>
                            <?php endforeach; ?>
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
<?php
if (isset($_SESSION['success'])) {
    $content = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ' . $_SESSION['success'] . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    ' . $content;
    unset($_SESSION['success']);
}
$title = "Data Siswa";
$content = ob_get_clean();
include 'template.php';
?>