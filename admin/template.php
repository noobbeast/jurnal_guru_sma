<?php
if (!isset($title)) $title = "Dashboard Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Jurnal Mengajar SMA</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="../assets/adminlte/css/adminlte.min.css">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  
    <style>
        .main-sidebar {
            transition: all 0.3s;
        }
        
        /* Dark mode styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #ffffff;
        }
        
        body.dark-mode .content-wrapper {
            background-color: #1a1a1a;
        }
        
        body.dark-mode .card {
            background-color: #2d2d2d;
            color: #ffffff;
        }
        
        body.dark-mode .card-header {
            background-color: #343a40;
            border-bottom: 1px solid #454d55;
        }
        
        body.dark-mode .table {
            color: #ffffff;
        }
        
        body.dark-mode .table td,
        body.dark-mode .table th {
            border-color: #454d55;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed" id="body-theme">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <button class="btn btn-tool" id="theme-toggle" title="Toggle Dark Mode">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </button>
                </li>
                <li class="nav-item">
                    <span class="navbar-text">Halo, Admin</span>
                </li>
                <li class="nav-item ml-2">
                    <a class="btn btn-danger btn-sm" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="dashboard.php" class="brand-link">
                <img src="../assets/adminlte/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">JurnalSMAKARA</span>
            </a>

            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="data_guru.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'data_guru.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                <p>Data Guru</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="data_kelas.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'data_kelas.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-school"></i>
                                <p>Data Kelas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="data_mapel.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'data_mapel.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Data Mapel</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="data_siswa.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'data_siswa.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Data Siswa</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="data_relasi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'data_relasi.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-link"></i>
                                <p>Relasi Guru-Mapel-Kelas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rekap_jurnal.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'rekap_jurnal.php' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-clipboard-list"></i>
                                <p>Rekap Jurnal</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?= htmlspecialchars($title) ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <?= $content ?>
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; 2025 <a href="#">ICT-Jurnal Mengajar SMAN 1 SUKAPURA</a>.</strong>
            All rights reserved.
        </footer>

    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="../assets/adminlte/js/adminlte.min.js"></script>

    <script>
        $(document).ready(function() {
            const savedTheme = localStorage.getItem('admin-theme') || 
                             (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            
            if (savedTheme === 'dark') {
                enableDarkMode();
            }

            $('#theme-toggle').click(function() {
                if ($('body').hasClass('dark-mode')) {
                    disableDarkMode();
                    localStorage.setItem('admin-theme', 'light');
                } else {
                    enableDarkMode();
                    localStorage.setItem('admin-theme', 'dark');
                }
            });

            function enableDarkMode() {
                $('body').addClass('dark-mode');
                $('.main-header').addClass('navbar-dark').removeClass('navbar-light');
                $('.main-sidebar').addClass('sidebar-dark-primary').removeClass('sidebar-light-primary');
                $('#theme-icon').removeClass('fa-moon').addClass('fa-sun');
            }

            function disableDarkMode() {
                $('body').removeClass('dark-mode');
                $('.main-header').addClass('navbar-light').removeClass('navbar-dark');
                $('.main-sidebar').addClass('sidebar-light-primary').removeClass('sidebar-dark-primary');
                $('#theme-icon').removeClass('fa-sun').addClass('fa-moon');
            }
        });
    </script>
</body>
</html>