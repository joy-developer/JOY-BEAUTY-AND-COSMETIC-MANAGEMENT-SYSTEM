<?php
require_once '../../backend/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../user/login.php');
}

// Handle form submission and logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['logout'])) {
    require_once '../../backend/auth.php';
}

// Determine active tab
$activeTab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'dashboard';

// Initialize variables
$clients = $appointments = $products = $sales = $reports = [];
$error = $success = '';

// Handle CRUD operations based on active tab
try {
    // Dashboard Tab
    if ($activeTab === 'dashboard') {
        // Get stats for dashboard
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
        $stmt->execute();
        $todayAppointments = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'client' AND MONTH(created_at) = MONTH(CURDATE())");
        $stmt->execute();
        $newClients = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity < 5");
        $stmt->execute();
        $lowStock = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE())");
        $stmt->execute();
        $monthlyRevenue = $stmt->fetchColumn() ?? 0;
        
        $stmt = $pdo->prepare("SELECT a.*, u.first_name, u.last_name, s.name as service_name 
                              FROM appointments a
                              JOIN users u ON a.user_id = u.user_id
                              JOIN services s ON a.service_id = s.service_id
                              ORDER BY a.appointment_date DESC, a.start_time DESC
                              LIMIT 5");
        $stmt->execute();
        $recentAppointments = $stmt->fetchAll();
    }
    
    // Clients Tab
    elseif ($activeTab === 'clients') {
        // Handle client deletion
        if (isset($_GET['delete_client'])) {
            $clientId = (int)$_GET['delete_client'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND user_type = 'client'");
            $stmt->execute([$clientId]);
            $success = "Client deleted successfully!";
        }
        
        // Get all clients
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'client' ORDER BY created_at DESC");
        $stmt->execute();
        $clients = $stmt->fetchAll();
    }
    
    // Appointments Tab
    elseif ($activeTab === 'appointments') {
        // Handle appointment status change
        if (isset($_POST['update_status'])) {
            $appointmentId = (int)$_POST['appointment_id'];
            $status = sanitizeInput($_POST['status']);
            
            $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
            $stmt->execute([$status, $appointmentId]);
            $success = "Appointment status updated!";
        }
        
        // Handle appointment deletion
        if (isset($_GET['delete_appointment'])) {
            $appointmentId = (int)$_GET['delete_appointment'];
            $stmt = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $stmt->execute([$appointmentId]);
            $success = "Appointment deleted successfully!";
        }
        
        // Get all appointments
        $stmt = $pdo->prepare("SELECT a.*, u.first_name, u.last_name, s.name as service_name, s.price
                              FROM appointments a
                              JOIN users u ON a.user_id = u.user_id
                              JOIN services s ON a.service_id = s.service_id
                              ORDER BY a.appointment_date DESC, a.start_time DESC");
        $stmt->execute();
        $appointments = $stmt->fetchAll();
    }
    
    // Products Tab
    elseif ($activeTab === 'products') {
        // Handle product add/edit
        if (isset($_POST['save_product'])) {
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            $category = sanitizeInput($_POST['category']);
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if ($productId) {
                // Update existing product
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, category = ?, 
                                      price = ?, stock_quantity = ?, is_active = ? WHERE product_id = ?");
                $stmt->execute([$name, $description, $category, $price, $stock, $isActive, $productId]);
                $success = "Product updated successfully!";
            } else {
                // Add new product
                $stmt = $pdo->prepare("INSERT INTO products (name, description, category, price, stock_quantity, is_active)
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $category, $price, $stock, $isActive]);
                $success = "Product added successfully!";
            }
        }
        
        // Handle product deletion
        if (isset($_GET['delete_product'])) {
            $productId = (int)$_GET['delete_product'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);
            $success = "Product deleted successfully!";
        }
        
        // Get all products
        $stmt = $pdo->prepare("SELECT * FROM products ORDER BY name");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        // Get product for editing
        $editProduct = null;
        if (isset($_GET['edit_product'])) {
            $productId = (int)$_GET['edit_product'];
            $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$productId]);
            $editProduct = $stmt->fetch();
        }
    }
    
    // Inventory Tab
    elseif ($activeTab === 'inventory') {
        // Handle inventory update
        if (isset($_POST['update_inventory'])) {
            $productId = (int)$_POST['product_id'];
            $stock = (int)$_POST['stock'];
            
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
            $stmt->execute([$stock, $productId]);
            $success = "Inventory updated successfully!";
        }
        
        // Get inventory with low stock
        $stmt = $pdo->prepare("SELECT * FROM products WHERE stock_quantity < 5 ORDER BY stock_quantity ASC");
        $stmt->execute();
        $lowStockItems = $stmt->fetchAll();
        
        // Get all inventory
        $stmt = $pdo->prepare("SELECT * FROM products ORDER BY name");
        $stmt->execute();
        $inventory = $stmt->fetchAll();
    }
    
    // Reports Tab
    elseif ($activeTab === 'reports') {
        // Get sales report data
        $stmt = $pdo->prepare("SELECT 
                                DATE_FORMAT(sale_date, '%Y-%m') as month,
                                COUNT(*) as total_sales,
                                SUM(total_amount) as total_revenue
                              FROM sales
                              GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
                              ORDER BY month DESC
                              LIMIT 12");
        $stmt->execute();
        $salesReport = $stmt->fetchAll();
        
        // Get popular services
        $stmt = $pdo->prepare("SELECT 
                                s.name,
                                COUNT(*) as total_appointments,
                                SUM(s.price) as total_revenue
                              FROM appointments a
                              JOIN services s ON a.service_id = s.service_id
                              WHERE a.status = 'completed'
                              GROUP BY s.name
                              ORDER BY total_appointments DESC
                              LIMIT 5");
        $stmt->execute();
        $popularServices = $stmt->fetchAll();
        
        // Get popular products
        $stmt = $pdo->prepare("SELECT 
                                p.name,
                                SUM(si.quantity) as total_sold,
                                SUM(si.quantity * si.unit_price) as total_revenue
                              FROM sale_items si
                              JOIN products p ON si.product_id = p.product_id
                              GROUP BY p.name
                              ORDER BY total_sold DESC
                              LIMIT 5");
        $stmt->execute();
        $popularProducts = $stmt->fetchAll();
    }
    
    // Settings Tab
    elseif ($activeTab === 'settings') {
        // Handle password change
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (password_verify($currentPassword, $user['password'])) {
                if ($newPassword === $confirmPassword) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                    $success = "Password changed successfully!";
                } else {
                    $error = "New passwords don't match!";
                }
            } else {
                $error = "Current password is incorrect!";
            }
        }
        
        // Handle profile update
        if (isset($_POST['update_profile'])) {
            $firstName = sanitizeInput($_POST['first_name']);
            $lastName = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$firstName, $lastName, $email, $phone, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            
            $success = "Profile updated successfully!";
        }
        
        // Get current user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
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
    <title>Joy Beauty & Cosmetic Management System</title>
    <link rel="icon" type="image/png" href="../../images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b9d;
            --secondary-color: #ffd6e5;
            --dark-color: #333;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff9fb;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #ff6b9d, #ff8fab);
            color: white;
            height: 100vh;
            position: fixed;
        }
        
        .sidebar .nav-link {
            color: white;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-link.active {
            background-color: white;
            color: var(--primary-color) !important;
            font-weight: 600;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 157, 0.25);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .nav-tabs .nav-link:hover {
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../../images/logo.png" alt="Logo" style="width:48px; object-fit:contain; margin-right:8px; vertical-align:middle;">Joy Beauty & Cosmetics
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['first_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?tab=settings"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar col-md-3 col-lg-2 d-md-block p-3">
        <div class="text-center mb-4">
            <img src="../../images/user.png" width="64" class="rounded-circle mb-2" alt="Profile">
            <h6 class="mb-0"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h6>
            <small class="text-white-50">Administrator</small>
        </div>
        
        <hr class="bg-white">
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>" href="?tab=dashboard">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'clients' ? 'active' : ''; ?>" href="?tab=clients">
                    <i class="fas fa-users me-2"></i> Clients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'appointments' ? 'active' : ''; ?>" href="?tab=appointments">
                    <i class="fas fa-calendar-alt me-2"></i> Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'products' ? 'active' : ''; ?>" href="?tab=products">
                    <i class="fas fa-shopping-bag me-2"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'inventory' ? 'active' : ''; ?>" href="?tab=inventory">
                    <i class="fas fa-box-open me-2"></i> Inventory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'reports' ? 'active' : ''; ?>" href="?tab=reports">
                    <i class="fas fa-chart-line me-2"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'settings' ? 'active' : ''; ?>" href="?tab=settings">
                    <i class="fas fa-cog me-2"></i> Settings
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
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Dashboard</h1>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Today's Appointments</h6>
                                <h3 class="mb-0"><?php echo $todayAppointments; ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-calendar-check text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">New Clients</h6>
                                <h3 class="mb-0"><?php echo $newClients; ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-user-plus text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Products Low Stock</h6>
                                <h3 class="mb-0"><?php echo $lowStock; ?></h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Monthly Revenue</h6>
                                <h3 class="mb-0">$<?php echo number_format($monthlyRevenue, 2); ?></h3>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-dollar-sign text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Appointments</h5>
                    <a href="?tab=appointments" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAppointments as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                                <td><?php echo $appointment['service_name']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($appointment['start_time'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?tab=appointments&edit=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <a href="?tab=appointments&edit=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Clients Tab -->
        <?php if ($activeTab === 'clients'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Client Management</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="fas fa-plus me-1"></i> Add Client
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo $client['user_id']; ?></td>
                                <td><?php echo $client['first_name'] . ' ' . $client['last_name']; ?></td>
                                <td><?php echo $client['email']; ?></td>
                                <td><?php echo $client['phone'] ?? 'N/A'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($client['created_at'])); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                    <a href="?tab=clients&delete_client=<?php echo $client['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Client Modal -->
        <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addClientModalLabel">Add New Client</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="../../backend/auth.php">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="signup" class="btn btn-primary">Save Client</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Appointments Tab -->
        <?php if ($activeTab === 'appointments'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Appointment Management</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                    <i class="fas fa-plus me-1"></i> New Appointment
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['appointment_id']; ?></td>
                                <td><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></td>
                                <td><?php echo $appointment['service_name']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($appointment['start_time'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                    <a href="?tab=appointments&edit=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                    <a href="?tab=appointments&delete_appointment=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Appointment Modal -->
        <div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAppointmentModalLabel">Add New Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['user_id']; ?>"><?php echo $client['first_name'] . ' ' . $client['last_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Select Service</option>
                                    <?php 
                                    $stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1");
                                    $services = $stmt->fetchAll();
                                    foreach ($services as $service): 
                                    ?>
                                    <option value="<?php echo $service['service_id']; ?>"><?php echo $service['name']; ?> ($<?php echo $service['price']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add_appointment" class="btn btn-primary">Save Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Products Tab -->
        <?php if ($activeTab === 'products'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Product Management</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-1"></i> Add Product
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['product_id']; ?></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category']; ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td>
                                    <span class="badge <?php echo $product['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?tab=products&edit_product=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                    <a href="?tab=products&delete_product=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Product Modal -->
        <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productModalLabel"><?php echo $editProduct ? 'Edit' : 'Add'; ?> Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <?php if ($editProduct): ?>
                            <input type="hidden" name="product_id" value="<?php echo $editProduct['product_id']; ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $editProduct['name'] ?? ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editProduct['description'] ?? ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category" name="category" value="<?php echo $editProduct['category'] ?? ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $editProduct['price'] ?? ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $editProduct['stock_quantity'] ?? 0; ?>" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php if (isset($editProduct['is_active']) && $editProduct['is_active']) echo 'checked'; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="save_product" class="btn btn-primary">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Inventory Tab -->
        <?php if ($activeTab === 'inventory'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Inventory Management</h1>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Low Stock Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockItems as $item): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td class="<?php echo $item['stock_quantity'] < 3 ? 'text-danger fw-bold' : 'text-warning'; ?>">
                                            <?php echo $item['stock_quantity']; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inventoryModal" 
                                                    data-product-id="<?php echo $item['product_id']; ?>"
                                                    data-product-name="<?php echo $item['name']; ?>"
                                                    data-stock-quantity="<?php echo $item['stock_quantity']; ?>">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory as $item): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['stock_quantity']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inventoryModal" 
                                                    data-product-id="<?php echo $item['product_id']; ?>"
                                                    data-product-name="<?php echo $item['name']; ?>"
                                                    data-stock-quantity="<?php echo $item['stock_quantity']; ?>">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inventory Modal -->
        <div class="modal fade" id="inventoryModal" tabindex="-1" aria-labelledby="inventoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="inventoryModalLabel">Update Inventory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" id="product_id" name="product_id">
                            <div class="mb-3">
                                <label class="form-label">Product</label>
                                <input type="text" class="form-control" id="product_name" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_inventory" class="btn btn-primary">Update Inventory</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Reports Tab -->
        <?php if ($activeTab === 'reports'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Reports</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Revenue</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Popular Services</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Appointments</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popularServices as $service): ?>
                                    <tr>
                                        <td><?php echo $service['name']; ?></td>
                                        <td><?php echo $service['total_appointments']; ?></td>
                                        <td>$<?php echo number_format($service['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Popular Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popularProducts as $product): ?>
                                    <tr>
                                        <td><?php echo $product['name']; ?></td>
                                        <td><?php echo $product['total_sold']; ?></td>
                                        <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Sales</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salesReport as $report): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($report['month'] . '-01')); ?></td>
                                        <td><?php echo $report['total_sales']; ?></td>
                                        <td>$<?php echo number_format($report['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Settings Tab -->
        <?php if ($activeTab === 'settings'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Settings</h1>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $userData['first_name']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $userData['last_name']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $userData['email']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $userData['phone'] ?? ''; ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Change Password</h5>
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
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Script -->
    <script>
            // Initialize modals when needed
        <?php if ($activeTab === 'products' && (isset($_GET['edit_product']) || isset($_GET['add_product']))): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        });
        <?php endif; ?>
        
        // Inventory modal handler
        document.addEventListener('DOMContentLoaded', function() {
            var inventoryModal = document.getElementById('inventoryModal');
            if (inventoryModal) {
                inventoryModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var productId = button.getAttribute('data-product-id');
                    var productName = button.getAttribute('data-product-name');
                    var stockQuantity = button.getAttribute('data-stock-quantity');
                    
                    var modal = this;
                    modal.querySelector('#product_id').value = productId;
                    modal.querySelector('#product_name').value = productName;
                    modal.querySelector('#stock').value = stockQuantity;
                });
            }
            
            // Revenue chart
            var ctx = document.getElementById('revenueChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_map(function($item) { return date('M Y', strtotime($item['month'] . '-01')); }, $salesReport)); ?>,
                        datasets: [{
                            label: 'Revenue',
                            data: <?php echo json_encode(array_column($salesReport, 'total_revenue')); ?>,
                            backgroundColor: 'rgba(255, 107, 157, 0.7)',
                            borderColor: 'rgba(255, 107, 157, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>