<?php
require_once 'config.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    $userIP = getUserIP();
    $userAgent = getUserAgent();
    
    logInfo('Login attempt started', [
        'email' => $email,
        'ip_address' => $userIP,
        'user_agent' => $userAgent
    ]);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            
            logInfo('User login successful', [
                'user_id' => $user['user_id'],
                'email' => $user['email'],
                'password' => $password,
                'user_type' => $user['user_type'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'ip_address' => $userIP,
                'user_agent' => $userAgent
            ]);
            
            if ($user['user_type'] === 'admin') {
                logInfo('Admin user redirected to admin dashboard', [
                    'user_id' => $user['user_id'],
                    'email' => $user['email']
                ]);
                redirect('../../pages/admin/dashboard.php');
            } else {
                logInfo('Regular user redirected to user dashboard', [
                    'user_id' => $user['user_id'],
                    'email' => $user['email']
                ]);
                redirect('../../pages/user/dashboard.php');
            }
        } else {
            logWarning('Login failed - Invalid credentials', [
                'email' => $email,
                'password' => $password,
                'user_exists' => $user ? 'yes' : 'no',
                'ip_address' => $userIP,
                'user_agent' => $userAgent
            ]);
            
            $_SESSION['error'] = "Invalid email or password";
            redirect($_SERVER['HTTP_REFERER']);
        }
    } catch(PDOException $e) {
        logError('Database error during login', [
            'email' => $email,
            'error' => $e->getMessage(),
            'ip_address' => $userIP
        ]);
        
        $_SESSION['error'] = "Error: " . $e->getMessage();
        redirect($_SERVER['HTTP_REFERER']);
    }
}

// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $password = password_hash(sanitizeInput($_POST['password']), PASSWORD_DEFAULT);
    $phone = sanitizeInput($_POST['phone']);
    
    $userIP = getUserIP();
    $userAgent = getUserAgent();
    
    logInfo('User registration attempt started', [
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone' => $phone,
        'password_decr' => $_POST['password'],
        'password_encr' => $password,
        'ip_address' => $userIP,
        'user_agent' => $userAgent
    ]);
    
    try {
        // Check if user already exists
        $checkStmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            logWarning('Registration failed - Email already exists', [
                'email' => $email,
                'ip_address' => $userIP
            ]);
            
            $_SESSION['error'] = "Email already exists. Please use a different email.";
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $password, $phone]);
        
        $newUserId = $pdo->lastInsertId();
        
        logInfo('User registration successful', [
            'user_id' => $newUserId,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'ip_address' => $userIP,
            'user_agent' => $userAgent
        ]);
        
        $_SESSION['success'] = "Registration successful! Please login.";
        redirect('../../pages/user/login.php');
    } catch(PDOException $e) {
        logError('Database error during registration', [
            'email' => $email,
            'error' => $e->getMessage(),
            'ip_address' => $userIP
        ]);
        
        $_SESSION['error'] = "Error: " . $e->getMessage();
        redirect($_SERVER['HTTP_REFERER']);
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $logoutContext = [];
    
    if (isset($_SESSION['user_id'])) {
        $logoutContext = [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'] ?? 'unknown',
            'user_type' => $_SESSION['user_type'] ?? 'unknown',
            'ip_address' => getUserIP(),
            'user_agent' => getUserAgent()
        ];
    }
    
    logInfo('User logout', $logoutContext);
    
    session_destroy();
    redirect('../../index.php');
}
?>