<?php
require_once '../../backend/config.php';

if (!isLoggedIn()) {
    redirect('../user/login.php');
}

// Get client data
$userId = $_SESSION['user_id'];

// Handle form submission and logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['logout'])) {
    require_once '../../backend/auth.php';
}

// Determine active tab
$activeTab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'dashboard';

// Initialize variables
$upcomingAppointments = $pastAppointments = $recommendedProducts = $allProducts = [];
$error = $success = '';

try {
    // Dashboard Tab
    if ($activeTab === 'dashboard') {
        // Upcoming appointments
        $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.price, s.image_url
                              FROM appointments a
                              JOIN services s ON a.service_id = s.service_id
                              WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
                              ORDER BY a.appointment_date, a.start_time
                              LIMIT 3");
        $stmt->execute([$userId]);
        $upcomingAppointments = $stmt->fetchAll();
        
        // Count upcoming appointments
        $upcomingCount = count($upcomingAppointments);
        
        // Get recommended products
        $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = TRUE ORDER BY RAND() LIMIT 3");
        $stmt->execute();
        $recommendedProducts = $stmt->fetchAll();
    }
    
    // Book Appointment Tab
    elseif ($activeTab === 'book') {
        // Handle appointment booking
        if (isset($_POST['book_appointment'])) {
            $serviceId = (int)$_POST['service_id'];
            $appointmentDate = sanitizeInput($_POST['appointment_date']);
            $startTime = sanitizeInput($_POST['start_time']);
            $notes = sanitizeInput($_POST['notes']);
            
            // Get service duration
            $stmt = $pdo->prepare("SELECT duration_minutes FROM services WHERE service_id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();
            $duration = $service['duration_minutes'];
            
            // Calculate end time
            $endTime = date('H:i:s', strtotime("+$duration minutes", strtotime($startTime)));
            
            // Check for existing appointment at this time
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments 
                                  WHERE appointment_date = ? 
                                  AND ((start_time <= ? AND end_time > ?) 
                                  OR (start_time < ? AND end_time >= ?)
                                  OR (start_time >= ? AND end_time <= ?))");
            $stmt->execute([$appointmentDate, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);
            $conflictingAppointments = $stmt->fetchColumn();
            
            if ($conflictingAppointments > 0) {
                $error = "There's already an appointment scheduled at this time. Please choose a different time.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO appointments 
                                      (user_id, service_id, appointment_date, start_time, end_time, notes, status)
                                      VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$userId, $serviceId, $appointmentDate, $startTime, $endTime, $notes]);
                $success = "Appointment booked successfully! Our staff will confirm shortly.";
            }
        }
        
        // Get all active services
        $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = TRUE ORDER BY name");
        $stmt->execute();
        $services = $stmt->fetchAll();
    }
    
    // Appointments Tab
    elseif ($activeTab === 'appointments') {
        // Handle appointment cancellation
        if (isset($_GET['cancel_appointment'])) {
            $appointmentId = (int)$_GET['cancel_appointment'];
            
            // Verify the appointment belongs to this user
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?");
            $stmt->execute([$appointmentId, $userId]);
            $appointment = $stmt->fetch();
            
            if ($appointment) {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
                $stmt->execute([$appointmentId]);
                $success = "Appointment cancelled successfully!";
            } else {
                $error = "Appointment not found or you don't have permission to cancel it.";
            }
        }
        
        // Handle review submission
        if (isset($_POST['submit_review'])) {
            $appointmentId = (int)$_POST['appointment_id'];
            $rating = (int)$_POST['rating'];
            $review = sanitizeInput($_POST['review']);
            
            // Verify the appointment belongs to this user and is completed
            $stmt = $pdo->prepare("SELECT * FROM appointments 
                                  WHERE appointment_id = ? AND user_id = ? AND status = 'completed'");
            $stmt->execute([$appointmentId, $userId]);
            $appointment = $stmt->fetch();
            
            if ($appointment) {
                // Insert review
                $stmt = $pdo->prepare("INSERT INTO testimonials 
                                      (quote, client_name, client_title, client_image_url)
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $review,
                    $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
                    'Client',
                    '../../images/user.png'
                ]);
                $success = "Thank you for your review!";
            } else {
                $error = "Invalid appointment or appointment not completed yet.";
            }
        }
        
        // Get upcoming appointments
        $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.price, s.image_url
                              FROM appointments a
                              JOIN services s ON a.service_id = s.service_id
                              WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
                              ORDER BY a.appointment_date, a.start_time");
        $stmt->execute([$userId]);
        $upcomingAppointments = $stmt->fetchAll();
        
        // Get past appointments
        $stmt = $pdo->prepare("SELECT a.*, s.name as service_name, s.price, s.image_url
                              FROM appointments a
                              JOIN services s ON a.service_id = s.service_id
                              WHERE a.user_id = ? AND a.appointment_date < CURDATE()
                              ORDER BY a.appointment_date DESC, a.start_time DESC");
        $stmt->execute([$userId]);
        $pastAppointments = $stmt->fetchAll();
    }
    
    // Products Tab
    elseif ($activeTab === 'products') {
        // Handle product purchase
        if (isset($_POST['add_to_cart'])) {
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            // Check product availability
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if ($product && $product['stock_quantity'] >= $quantity) {
                // Initialize cart if not exists
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Add product to cart or update quantity
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] += $quantity;
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }
                
                $success = "Product added to cart!";
            } else {
                $error = "Product not available in the requested quantity.";
            }
        }
        
        // Handle checkout
        if (isset($_POST['checkout'])) {
            if (!empty($_SESSION['cart'])) {
                // Calculate total amount
                $totalAmount = 0;
                $items = [];
                
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    $stmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $totalAmount += $product['price'] * $quantity;
                        $items[] = [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'unit_price' => $product['price']
                        ];
                    }
                }
                
                if ($totalAmount > 0) {
                    // Create sale record
                    $stmt = $pdo->prepare("INSERT INTO sales 
                                          (user_id, total_amount, payment_method, payment_status)
                                          VALUES (?, ?, 'cash', 'completed')");
                    $stmt->execute([$userId, $totalAmount]);
                    $saleId = $pdo->lastInsertId();
                    
                    // Create sale items
                    foreach ($items as $item) {
                        $stmt = $pdo->prepare("INSERT INTO sale_items 
                                              (sale_id, product_id, quantity, unit_price)
                                              VALUES (?, ?, ?, ?)");
                        $stmt->execute([$saleId, $item['product_id'], $item['quantity'], $item['unit_price']]);
                        
                        // Update product stock
                        $stmt = $pdo->prepare("UPDATE products 
                                              SET stock_quantity = stock_quantity - ? 
                                              WHERE product_id = ?");
                        $stmt->execute([$item['quantity'], $item['product_id']]);
                    }
                    
                    // Clear cart
                    unset($_SESSION['cart']);
                    $success = "Checkout completed successfully! Thank you for your purchase.";
                }
            } else {
                $error = "Your cart is empty.";
            }
        }
        
        // Handle cart item removal
        if (isset($_GET['remove_from_cart'])) {
            $productId = (int)$_GET['remove_from_cart'];
            
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $success = "Product removed from cart.";
            }
        }
        
        // Get all active products
        $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = TRUE ORDER BY name");
        $stmt->execute();
        $allProducts = $stmt->fetchAll();
        
        // Get recommended products
        $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = TRUE ORDER BY RAND() LIMIT 3");
        $stmt->execute();
        $recommendedProducts = $stmt->fetchAll();
    }
    
    // Profile Tab
    elseif ($activeTab === 'profile') {
        // Handle profile update
        if (isset($_POST['update_profile'])) {
            $firstName = sanitizeInput($_POST['first_name']);
            $lastName = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $address = sanitizeInput($_POST['address']);
            
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $userId]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                $error = "Email address is already in use by another account.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
                $stmt->execute([$firstName, $lastName, $email, $phone, $address, $userId]);
                
                // Update session
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;
                
                $success = "Profile updated successfully!";
            }
        }
        
        // Handle password change
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (password_verify($currentPassword, $user['password'])) {
                if ($newPassword === $confirmPassword) {
                    if (strlen($newPassword) >= 8) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $stmt->execute([$hashedPassword, $userId]);
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Password must be at least 8 characters long.";
                    }
                } else {
                    $error = "New passwords don't match!";
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
        
        // Get current user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Joy Beauty and Cosmetic</title>
    <link rel="icon" type="image/png" href="../../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-pink: #ff85a2;
            --dark-pink: #e76f8c;
            --light-pink: #fff0f3;
            --teal: #2ec4b6;
            --purple: #9b5de5;
            --gold: #ffd166;
            --dark-blue: #26547c;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }
        
        body {
            font-family: 'Poppins', 'Arial', sans-serif;
            background-color: var(--light-gray);
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        header {
            background: linear-gradient(135deg, var(--primary-pink), var(--purple));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .logout-btn {
            background-color: white;
            color: var(--primary-pink);
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-decoration: none;
        }
        
        .logout-btn:hover {
            background-color: transparent;
            color: white;
            border-color: white;
        }
        
        .sidebar {
            width: 250px;
            background-color: white;
            height: calc(100vh - 70px);
            position: fixed;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar ul {
            list-style: none;
            padding: 1rem 0;
        }
        
        .sidebar ul li {
            padding: 0.8rem 1.5rem;
        }
        
        .sidebar ul li a {
            color: #555;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .sidebar ul li a:hover {
            color: var(--primary-pink);
        }
        
        .sidebar ul li a i {
            margin-right: 0.8rem;
            font-size: 1.2rem;
        }
        
        .sidebar ul li.active {
            background-color: var(--light-pink);
            border-left: 4px solid var(--primary-pink);
        }
        
        .sidebar ul li.active a {
            color: var(--primary-pink);
            font-weight: 500;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-section h1 {
            color: var(--primary-pink);
            margin-bottom: 0.5rem;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .card h3 {
            color: var(--primary-pink);
            margin-bottom: 1rem;
        }
        
        .btn-pink {
            background-color: var(--primary-pink);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-pink:hover {
            background-color: var(--dark-pink);
            transform: translateY(-2px);
        }
        
        .badge-pending {
            background-color: #6c757d;
        }
        
        .badge-confirmed {
            background-color: #0d6efd;
        }
        
        .badge-completed {
            background-color: #198754;
        }
        
        .badge-cancelled {
            background-color: #dc3545;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .appointment-card {
            border-left: 4px solid var(--primary-pink);
            transition: all 0.3s ease;
        }
        
        .appointment-card:hover {
            transform: translateX(5px);
        }
        
        .form-control:focus {
            border-color: var(--primary-pink);
            box-shadow: 0 0 0 0.25rem rgba(255, 133, 162, 0.25);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            header {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .user-info {
                margin-top: 1rem;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header>
        <div class="logo">
            <img src="../../images/logo.png" alt="Joy Beauty Logo">
            Joy Beauty & Cosmetic
        </div>
        <div class="user-info">
            <img src="../../images/user.png" alt="User">
            <span>Welcome, <?php echo $_SESSION['first_name']; ?></span>
            <a href="?logout" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul>
            <li class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                <a href="?tab=dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo $activeTab === 'book' ? 'active' : ''; ?>">
                <a href="?tab=book">
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </a>
            </li>
            <li class="<?php echo $activeTab === 'appointments' ? 'active' : ''; ?>">
                <a href="?tab=appointments">
                    <i class="fas fa-history"></i> My Appointments
                </a>
            </li>
            <li class="<?php echo $activeTab === 'products' ? 'active' : ''; ?>">
                <a href="?tab=products">
                    <i class="fas fa-shopping-bag"></i> Products
                </a>
            </li>
            <li class="<?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                <a href="?tab=profile">
                    <i class="fas fa-user"></i> Profile
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Dashboard Tab -->
        <?php if ($activeTab === 'dashboard'): ?>
        <div class="welcome-section">
            <h1>Welcome Back, <?php echo $_SESSION['first_name']; ?>!</h1>
            <p>Manage your appointments, view products, and update your profile.</p>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Upcoming Appointments</h3>
                <p>You have <?php echo count($upcomingAppointments); ?> upcoming appointment<?php echo count($upcomingAppointments) != 1 ? 's' : ''; ?></p>
                <?php if (!empty($upcomingAppointments)): ?>
                <ul class="list-unstyled mt-3">
                    <?php foreach ($upcomingAppointments as $appointment): ?>
                    <li class="mb-3 p-2 appointment-card">
                        <strong><?php echo $appointment['service_name']; ?></strong><br>
                        <small class="text-muted">
                            <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at 
                            <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>
                        </small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <a href="?tab=appointments" class="btn btn-pink">View All Appointments</a>
            </div>
            <div class="card">
                <h3>Recommended Products</h3>
                <?php if (!empty($recommendedProducts)): ?>
                <div class="mt-3">
                    <?php foreach ($recommendedProducts as $product): ?>
                    <div class="mb-3 p-2 product-card">
                        <strong><?php echo $product['name']; ?></strong><br>
                        <span class="text-primary">Ksh<?php echo number_format($product['price'], 2); ?></span>
                        <a href="?tab=products" class="btn btn-sm btn-pink mt-2">View Details</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <a href="?tab=products" class="btn btn-pink">Browse All Products</a>
            </div>
            <div class="card">
                <h3>Special Offers</h3>
                <div class="mb-3">
                    <strong>20% Off Spa Treatments</strong><br>
                    <small class="text-muted">Valid until <?php echo date('M j, Y', strtotime('+1 week')); ?></small>
                </div>
                <div class="mb-3">
                    <strong>Free Hair Treatment</strong><br>
                    <small class="text-muted">With any hair service purchase</small>
                </div>
                <a href="?tab=book" class="btn btn-pink">Book Now</a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Book Appointment Tab -->
        <?php if ($activeTab === 'book'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Book an Appointment</h1>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Select a Service</option>
                                    <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['service_id']; ?>">
                                        <?php echo $service['name']; ?> ($<?php echo number_format($service['price'], 2); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" min="09:00" max="17:00" required>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Special Requests</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <button type="submit" name="book_appointment" class="btn btn-pink">Book Appointment</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Available Services</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($services as $service): ?>
                            <div class="list-group-item mb-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo $service['name']; ?></h5>
                                    <small>Ksh<?php echo number_format($service['price'], 2); ?></small>
                                </div>
                                <p class="mb-1"><?php echo $service['description']; ?></p>
                                <small>Duration: <?php echo floor($service['duration_minutes']/60); ?>h <?php echo $service['duration_minutes']%60; ?>m</small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Appointments Tab -->
        <?php if ($activeTab === 'appointments'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">My Appointments</h1>
        </div>
        
        <ul class="nav nav-tabs mb-4" id="appointmentsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                    Upcoming (<?php echo count($upcomingAppointments); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab">
                    Past (<?php echo count($pastAppointments); ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="appointmentsTabContent">
            <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                <?php if (!empty($upcomingAppointments)): ?>
                <div class="row">
                    <?php foreach ($upcomingAppointments as $appointment): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo $appointment['service_name']; ?></h5>
                                    <span class="badge bg-<?php 
                                        switch(strtolower($appointment['status'])) {
                                            case 'confirmed': echo 'success'; break;
                                            case 'pending': echo 'warning'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('g:i A', strtotime($appointment['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($appointment['end_time'])); ?>
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    $<?php echo number_format($appointment['price'], 2); ?>
                                </div>
                                <?php if ($appointment['notes']): ?>
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <p><?php echo $appointment['notes']; ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($appointment['status'] !== 'cancelled'): ?>
                                <a href="?tab=appointments&cancel_appointment=<?php echo $appointment['appointment_id']; ?>" 
                                   class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    You don't have any upcoming appointments. <a href="?tab=book" class="alert-link">Book one now!</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-pane fade" id="past" role="tabpanel">
                <?php if (!empty($pastAppointments)): ?>
                <div class="row">
                    <?php foreach ($pastAppointments as $appointment): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo $appointment['service_name']; ?></h5>
                                    <span class="badge bg-<?php 
                                        switch(strtolower($appointment['status'])) {
                                            case 'completed': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?>
                                </div>
                                <div class="mb-2">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo date('g:i A', strtotime($appointment['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($appointment['end_time'])); ?>
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-dollar-sign me-2"></i>
                                    $<?php echo number_format($appointment['price'], 2); ?>
                                </div>
                                <?php if ($appointment['notes']): ?>
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <p><?php echo $appointment['notes']; ?></p>
                                </div>
                                <?php endif; ?>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal" 
                                    data-service="<?php echo $appointment['service_name']; ?>"
                                    data-appointment-id="<?php echo $appointment['appointment_id']; ?>">
                                    <i class="fas fa-star me-1"></i> Leave Review
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    You don't have any past appointments yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Review Modal -->
        <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reviewModalLabel">Leave a Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="appointment_id" name="appointment_id">
                            <div class="mb-3">
                                <label class="form-label">Service</label>
                                <input type="text" class="form-control" id="service_name" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <select class="form-select" id="rating" name="rating" required>
                                    <option value="">Select Rating</option>
                                    <option value="5">★★★★★ Excellent</option>
                                    <option value="4">★★★★ Very Good</option>
                                    <option value="3">★★★ Good</option>
                                    <option value="2">★★ Fair</option>
                                    <option value="1">★ Poor</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="review" class="form-label">Review</label>
                                <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="submit_review" class="btn btn-pink">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Products Tab -->
        <?php if ($activeTab === 'products'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Our Products</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <a href="#" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fas fa-shopping-cart me-1"></i> Cart (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recommended For You</h5>
                        <div class="row">
                            <?php foreach ($recommendedProducts as $product): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 product-card">
                                    <?php if ($product['image_url']): ?>
                                    <img src="<?php echo $product['image_url']; ?>" class="card-img-top product-img" alt="<?php echo $product['name']; ?>">
                                    <?php else: ?>
                                    <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                        <p class="card-text text-muted"><?php echo substr($product['description'], 0, 100); ?>...</p>
                                        <h6 class="text-primary">Ksh<?php echo number_format($product['price'], 2); ?></h6>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <div class="input-group mb-2">
                                                <input type="number" class="form-control" name="quantity" value="1" min="1">
                                                <button type="submit" name="add_to_cart" class="btn btn-pink">
                                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">All Products</h5>
                        <div class="row">
                            <?php foreach ($allProducts as $product): ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100 product-card">
                                    <?php if ($product['image_url']): ?>
                                    <img src="<?php echo $product['image_url']; ?>" class="card-img-top product-img" alt="<?php echo $product['name']; ?>">
                                    <?php else: ?>
                                    <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                        <p class="card-text text-muted"><?php echo substr($product['description'], 0, 100); ?>...</p>
                                        <h6 class="text-primary">Ksh<?php echo number_format($product['price'], 2); ?></h6>
                                        <small class="text-muted">In Stock: <?php echo $product['stock_quantity']; ?></small>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-pink">
                                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cart Modal -->
        <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cartModalLabel">Your Shopping Cart</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $cartTotal = 0;
                                    foreach ($_SESSION['cart'] as $productId => $quantity): 
                                        $stmt = $pdo->prepare("SELECT name, price FROM products WHERE product_id = ?");
                                        $stmt->execute([$productId]);
                                        $product = $stmt->fetch();
                                        $productTotal = $product['price'] * $quantity;
                                        $cartTotal += $productTotal;
                                    ?>
                                    <tr>
                                        <td><?php echo $product['name']; ?></td>
                                        <td>Ksh<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $quantity; ?></td>
                                        <td>Ksh<?php echo number_format($productTotal, 2); ?></td>
                                        <td>
                                            <a href="?tab=products&remove_from_cart=<?php echo $productId; ?>" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td colspan="2"><strong>Ksh<?php echo number_format($cartTotal, 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            Your cart is empty.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                        <form method="POST">
                            <button type="submit" name="checkout" class="btn btn-pink">Proceed to Checkout</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Profile Tab -->
        <?php if ($activeTab === 'profile'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">My Profile</h1>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-pink">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Password must be at least 8 characters long.</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-pink">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize the review modal with data
        document.addEventListener('DOMContentLoaded', function() {
            var reviewModal = document.getElementById('reviewModal');
            if (reviewModal) {
                reviewModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var serviceName = button.getAttribute('data-service');
                    var appointmentId = button.getAttribute('data-appointment-id');
                    
                    var modalTitle = reviewModal.querySelector('.modal-title');
                    var serviceInput = reviewModal.querySelector('#service_name');
                    var appointmentInput = reviewModal.querySelector('#appointment_id');
                    
                    modalTitle.textContent = 'Review for ' + serviceName;
                    serviceInput.value = serviceName;
                    appointmentInput.value = appointmentId;
                });
            }
            
            // Set minimum date for appointment booking to today - only if element exists
            var appointmentDateInput = document.getElementById("appointment_date");
            if (appointmentDateInput) {
                var today = new Date().toISOString().split('T')[0];
                appointmentDateInput.min = today;
            }
        });
    </script>
</body>
</html>