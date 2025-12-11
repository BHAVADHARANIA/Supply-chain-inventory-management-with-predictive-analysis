<?php
session_start();
include 'db.php';

// --- Authentication Logic ---
$error = '';
if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    // Basic SQL Injection protection using prepared statements (improved security practice)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $u, $p);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['user'] = $u;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid Username or Password. Please try again.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCM Pro Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            /* Use a simple background color or gradient */
            background: linear-gradient(135deg, #1f283d, #3c4a63);
        }
        .login-card {
            width: 100%;
            max-width: 400px; /* Standard login card width */
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            border-color: #007bff;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

    <div class="card login-card">
        <div class="card-body">
            <h2 class="card-title text-center mb-4 text-primary">SCM Pro Login</h2>
            <p class="text-center text-muted mb-4">Supply Chain & Inventory System</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?= $error ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                </div>

                <div class="form-floating mb-4 password-container">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <span class="toggle-password" onclick="togglePasswordVisibility()">
                        <i id="eye-icon" class="fas fa-eye"></i>
                    </span>
                </div>

                <button type="submit" name="login" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>