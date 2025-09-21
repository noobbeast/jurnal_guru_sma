<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$sql = "SELECT * FROM mata_pelajaran ORDER BY nama_mapel";
$stmt = $conn->prepare($sql);
$stmt->execute();
$mapel_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Mata Pelajaran</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                            <i class="fas fa-plus"></i> Tambah Mapel
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>Nama Mapel</th>
                                <th>Singkatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
';

if (count($mapel_list) > 0) {
    foreach ($mapel_list as $mapel) {
        $content .= '
                            <tr>
                                <td>' . htmlspecialchars($mapel['nama_mapel']) . '</td>
                                <td>' . htmlspecialchars($mapel['singkatan']) . '</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit' . $mapel['id'] . '">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="proses_mapel.php?hapus=' . $mapel['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus mapel ini?\')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>

                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit' . $mapel['id'] . '">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="proses_mapel.php">
                                            <input type="hidden" name="id" value="' . $mapel['id'] . '">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Edit Mapel</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Nama Mapel</label>
                                                    <input type="text" name="nama_mapel" class="form-control" value="' . htmlspecialchars($mapel['nama_mapel']) . '" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Singkatan</label>
                                                    <input type="text" name="singkatan" class="form-control" value="' . htmlspecialchars($mapel['singkatan']) . '" required maxlength="10">
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
        ';
    }
} else {
    $content .= '
                            <tr>
                                <td colspan="3" class="text-center">Belum ada data mapel.</td>
                            </tr>
    ';
}

$content .= '
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
            <form method="POST" action="proses_mapel.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Mapel Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Mapel</label>
                        <input type="text" name="nama_mapel" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Singkatan</label>
                        <input type="text" name="singkatan" class="form-control" required maxlength="10">
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
';

if (isset($_SESSION['success'])) {
    $content = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> ' . $_SESSION['success'] . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    ' . $content;
    unset($_SESSION['success']);
}

$title = "Data Mata Pelajaran";
include 'template.php';
?>