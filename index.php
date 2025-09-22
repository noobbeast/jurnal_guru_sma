<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- ✅ WAJIB ADA! -->
    <title>Login - Jurnal Mengajar SMA</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <style>
        /* Divider style seperti MDB */
        .divider:after,
        .divider:before {
            content: "";
            flex: 1;
            height: 1px;
            background: #eee;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }
        .divider span {
            padding: 0 15px;
            color: #777;
            font-weight: bold;
        }

        /* Tinggi konten */
        .h-custom {
            height: calc(100vh - 73px);
        }

        @media (max-width: 450px) {
            .h-custom {
                height: 100%;
            }
        }

        /* Form label animation */
        .form-outline .form-label {
            margin-left: 0.75rem;
            transition: all 0.2s;
        }
        .form-outline .form-control:focus ~ .form-label,
        .form-outline .form-control:not(:placeholder-shown) ~ .form-label {
            margin-left: 0;
            font-size: 0.85rem;
            color: #0d6efd;
        }

        /* Tombol floating */
        .btn-floating {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .btn-floating:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Animasi tombol login */
        .btn-primary {
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
            background-color: #0b5ed7 !important;
            border-color: #0a58ca !important;
        }

        /* Animasi link */
        a.text-primary,
        a.text-danger {
            transition: all 0.2s ease;
        }
        a.text-primary:hover,
        a.text-danger:hover {
            color: #0d6efd !important;
            transform: translateX(3px);
        }

        /* Logo sekolah */
        .logo-sekolah {
            max-width: 120px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .logo-sekolah:hover {
            transform: scale(1.05);
        }

        /* Footer */
        footer.bg-primary {
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        /* Alert error */
        .alert {
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .alert:hover {
            transform: translateX(5px);
        }

        /* Responsive improvement */
        .btn {
            padding: 0.5rem 1rem;
            font-size: 1rem;
        }
        .form-control {
            font-size: 1rem;
            padding: 0.5rem;
        }
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            .divider {
                margin: 1.5rem 0;
            }
            .logo-sekolah {
                max-width: 80px;
                margin-bottom: 1rem;
            }
            .card-title, h3, h4 {
                font-size: 1.1rem !important;
            }
            .vh-100, .h-custom {
                height: auto !important;
                min-height: 100vh;
            }
            .footer {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <section class="vh-100">
        <div class="container-fluid h-custom">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-md-9 col-lg-6 col-xl-5 d-none d-md-block">
                    <img src="./assets/logo/cok.png"
                        class="img-fluid" alt="Ilustrasi Login">
                </div>
                <div class="col-12 col-md-8 col-lg-6 col-xl-4 offset-xl-1">
                    <!-- Logo Sekolah -->
                    <div class="text-center mb-4">
                        <img src="assets/logo/logo.png" 
                             alt="Logo Sekolah" 
                             class="logo-sekolah rounded-circle border border-3 border-white shadow mx-auto d-block">
                        <h3 class="mt-3 fw-bold mb-0">JURNAL MENGAJAR</h3>
                        <h4 class="mt-3 fw-bold mb-0">SMAN 1 SUKAPURA</h4>
                    </div>

                    <form action="login.php" method="POST">
                        <!-- Email input -->
                        <div class="form-outline mb-4">
                            <input type="email" name="email" id="form3Example3" class="form-control form-control-lg"
                                placeholder="Masukkan email Anda" required />
                            <label class="form-label" for="form3Example3">Email</label>
                        </div>

                        <!-- Password input -->
                        <div class="form-outline mb-3">
                            <input type="password" name="password" id="form3Example4" class="form-control form-control-lg"
                                placeholder="Masukkan password" required />
                            <label class="form-label" for="form3Example4">Password</label>
                        </div>

                        <!-- Remember me & Forgot password -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-2">
                            <div class="form-check">
                                <input class="form-check-input me-2" type="checkbox" value="" id="form2Example3" />
                                <label class="form-check-label" for="form2Example3">
                                    Ingat saya
                                </label>
                            </div>
                            <a href="#!" class="text-primary small">Lupa password?</a>
                        </div>

                        <!-- Submit button -->
                        <div class="text-center text-lg-start">
                            <button type="submit" class="btn btn-primary btn-lg px-5 w-100">
                                Login
                            </button>
                            <p class="small fw-bold mt-2 pt-1 mb-0 text-center">
                                Belum punya akun? 
                                <a href="#!" class="text-danger">Daftar</a>
                            </p>
                        </div>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger mt-3">
                                Email atau password salah!
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-primary text-white d-flex flex-column flex-md-row text-center text-md-start justify-content-between py-3 px-4 px-xl-5 footer">
            <div class="mb-3 mb-md-0">
                Copyright © 2025 ICT-SMAN 1 SUKAPURA. All rights reserved.
            </div>
            <div>
                <a href="#!" class="text-white me-4">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#!" class="text-white me-4">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#!" class="text-white me-4">
                    <i class="fab fa-google"></i>
                </a>
                <a href="#!" class="text-white">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </footer>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>