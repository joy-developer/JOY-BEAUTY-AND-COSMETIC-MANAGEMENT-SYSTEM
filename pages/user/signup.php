<?php
require_once '../../backend/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/dashboard.php');
    } else {
        redirect('../user/dashboard.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../backend/auth.php';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Joy Beauty and Cosmetic</title>
    <link rel="icon" type="image/png" href="../../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #ff85a2, #9b5de5);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .signup-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff85a2, #9b5de5);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            max-width: 64px;
            width: auto;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(255, 133, 162, 0.3);
        }

        .signup-container h1 {
            color: #ff85a2;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            text-align: center;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 1.2rem;
            flex: 1;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff85a2;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(255, 133, 162, 0.1);
        }

        .form-group input:valid {
            border-color: #28a745;
        }

        .signup-btn {
            width: 100%;
            background: linear-gradient(135deg, #ff85a2, #e76f8c);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 133, 162, 0.4);
        }

        .signup-btn:hover {
            background: linear-gradient(135deg, #e76f8c, #d65a7a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 133, 162, 0.6);
        }

        .signup-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #ff85a2;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #e76f8c;
            text-decoration: underline;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.3rem;
            line-height: 1.4;
        }

        .password-match {
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .password-match.valid {
            color: #28a745;
        }

        .password-match.invalid {
            color: #dc3545;
        }

        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container input {
            padding-right: 45px !important;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #ff85a2;
        }

        .password-toggle i {
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .signup-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .signup-container h1 {
                font-size: 1.7rem;
            }
        }

        /* Loading state */
        .signup-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading {
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s ease infinite;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <div class="logo-container">
            <img src="../../images/logo.png" alt="Joy Beauty Logo">
        </div>
        <h1>Create Account</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form id="signupForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required
                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required
                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" required minlength="6">
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </span>
                </div>
                <div class="password-requirements">
                    Password must be at least 6 characters long
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-input-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye" id="confirm_password-eye"></i>
                    </span>
                </div>
                <div id="passwordMatch" class="password-match"></div>
            </div>

            <input type="hidden" name="signup" value="1">
            <button type="submit" class="signup-btn" id="submitBtn">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId + '-eye');

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            // Password confirmation validation
            function checkPasswordMatch() {
                if (confirmPassword.value === '') {
                    passwordMatch.textContent = '';
                    passwordMatch.className = 'password-match';
                    return;
                }

                if (password.value === confirmPassword.value) {
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.className = 'password-match valid';
                } else {
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.className = 'password-match invalid';
                }
            }

            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);

            // Form submission with validation
            form.addEventListener('submit', function(e) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return;
                }

                if (password.value.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return;
                }

                // Add loading state
                submitBtn.disabled = true;
                submitBtn.textContent = '';
                submitBtn.classList.add('loading');
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>

</html>