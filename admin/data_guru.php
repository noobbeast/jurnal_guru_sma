<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$sql = "SELECT u.id as user_id, u.nama, u.email, u.role, g.id as guru_id, g.nip 
        FROM users u 
        LEFT JOIN guru g ON u.id = g.user_id 
        WHERE u.role = 'guru' OR u.role = 'admin'
        ORDER BY u.nama ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$guru_list = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fix: ensure associative array

ob_start(); // Start output buffering
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Data Guru</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                <i class="fas fa-plus"></i> Tambah Guru
            </button>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>NIP</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guru_list as $guru): ?>
                <tr>
                    <td><?= htmlspecialchars($guru['nama']) ?></td>
                    <td><?= htmlspecialchars($guru['email']) ?></td>
                    <td><?= htmlspecialchars($guru['nip'] ?? '-') ?></td>
                    <td><?= ($guru['role'] == 'admin' ? 'Admin' : 'Guru') ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit<?= $guru['user_id'] ?>">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if ($guru['role'] != 'admin'): ?>
                        <a href="proses_guru.php?hapus=<?= $guru['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Modal Edit -->
                <div class="modal fade" id="modalEdit<?= $guru['user_id'] ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="proses_guru.php">
                                <input type="hidden" name="user_id" value="<?= $guru['user_id'] ?>">
                                <div class="modal-header">
                                    <h4 class="modal-title">Edit Guru</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Nama</label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($guru['nama']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($guru['email']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password (kosongkan jika tidak diubah)</label>
                                        <input type="password" name="password" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>NIP</label>
                                        <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($guru['nip'] ?? '') ?>">
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
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="proses_guru.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Guru Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" class="form-control">
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
$content = ob_get_clean();

$title = "Data Guru";
include 'template.php';
?>