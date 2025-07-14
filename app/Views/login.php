<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color:rgb(255, 255, 255);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('/assets/images/logo1.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(10px);
            z-index: -1;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 1rem;
        }
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 0.25rem;

        }

        .input-border-green {
            border: 2px solid #28a745;
        }

        .input-border-green:focus {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            border-color: #28a745;
        }

        .btn-custom-green {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-custom-green:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .form-control {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="/assets/images/sakipLogo.png" 
                 alt="Logo SAKIP" 
                 class="logo img-fluid">
        </div>
        
        <h5 class="text-center mb-4">Selamat Datang di e-SAKIP Kabupaten Pringsewu!</h5>
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= base_url('login/authenticate') ?>">
            <div class="mb-3 position-relative">
                <input type="text" name="username" class="form-control ps-4 input-border-green" placeholder="Username" required>
                <i class="fas fa-user position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
            </div>
            <div class="mb-3 position-relative">
                <input type="password" name="password" class="form-control ps-4 input-border-green" placeholder="Password" required>
                <i class="fas fa-lock position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
            </div>
            <button type="submit" class="btn btn-success btn-login">Log In</button>
        </form>
        
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

