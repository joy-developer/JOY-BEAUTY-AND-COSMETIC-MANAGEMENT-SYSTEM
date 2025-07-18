<?php
require_once 'backend/config.php';

// Log page access
logInfo('Index page accessed', [
    'ip_address' => getUserIP(),
    'user_agent' => getUserAgent(),
    'timestamp' => date('Y-m-d H:i:s')
]);

// Fetch Services
$services = [];
try {
    $sql_services = "SELECT name, description, image_url FROM services WHERE is_active = 1 LIMIT 6";
    $stmt_services = $pdo->prepare($sql_services);
    $stmt_services->execute();
    $services = $stmt_services->fetchAll();
    
    logInfo('Services fetched successfully', [
        'count' => count($services)
    ]);
} catch(PDOException $e) {
    logError('Failed to fetch services', [
        'error' => $e->getMessage(),
        'query' => $sql_services
    ]);
    $services = []; // Fallback to empty array
}

// Fetch Products
$products = [];
try {
    $sql_products = "SELECT name, image_url, price FROM products WHERE is_active = 1 LIMIT 4";
    $stmt_products = $pdo->prepare($sql_products);
    $stmt_products->execute();
    $products = $stmt_products->fetchAll();
    
    logInfo('Products fetched successfully', [
        'count' => count($products)
    ]);
} catch(PDOException $e) {
    logError('Failed to fetch products', [
        'error' => $e->getMessage(),
        'query' => $sql_products
    ]);
    $products = []; // Fallback to empty array
}
// Fetch Testimonials
$testimonials = [];
try {
    $sql_testimonials = "SELECT quote, client_name, client_title, client_image_url FROM testimonials ORDER BY created_at DESC LIMIT 8";
    $stmt_testimonials = $pdo->prepare($sql_testimonials);
    $stmt_testimonials->execute();
    $testimonials = $stmt_testimonials->fetchAll();
    
    logInfo('Testimonials fetched successfully', [
        'count' => count($testimonials)
    ]);
} catch(PDOException $e) {
    logError('Failed to fetch testimonials', [
        'error' => $e->getMessage(),
        'query' => $sql_testimonials
    ]);
    $testimonials = []; // Fallback to empty array
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joy Beauty and Cosmetic</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Arial', sans-serif;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }
        
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
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 1.5rem;
            position: relative;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
        }
        
        nav ul li a:hover {
            color: var(--gold);
        }
        
        nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--gold);
            bottom: 0;
            left: 0;
            transition: width 0.3s ease;
        }
        
        nav ul li a:hover::after {
            width: 100%;
        }
        
        .login-btn {
            background-color: white;
            color: var(--primary-pink);
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .login-btn:hover {
            background-color: transparent;
            color: white;
            border-color: white;
            text-decoration: none;
        }
        
        .hero {
            text-align: center;
            padding: 5rem 1rem;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                        url('https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1887&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(to bottom, transparent, var(--light-gray));
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeIn 1s ease;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            animation: fadeIn 1.5s ease;
        }
        
        .book-btn {
            background-color: var(--primary-pink);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 133, 162, 0.4);
            animation: fadeIn 2s ease;
        }
        
        .book-btn:hover {
            background-color: var(--dark-pink);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 133, 162, 0.6);
        }
        
        .services {
            padding: 4rem 2rem;
            text-align: center;
            background-color: var(--light-gray);
        }
        
        .section-title {
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: var(--primary-pink);
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-pink), var(--purple));
            bottom: -10px;
            left: 25%;
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: left;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .service-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .service-content {
            padding: 1.5rem;
        }
        
        .service-card h3 {
            color: var(--primary-pink);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .service-card p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .products {
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--light-pink), white);
            text-align: center;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: scale(1.03);
        }
        
        .product-img {
            height: 250px;
            width: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-info h3 {
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .product-info .price {
            color: var(--primary-pink);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .testimonials {
            padding: 4rem 2rem;
            background-color: white;
            text-align: center;
        }
        
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .testimonial-card {
            background: var(--light-gray);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .testimonial-card .quote {
            font-style: italic;
            margin-bottom: 1rem;
            color: #555;
        }
        
        .testimonial-card .client {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .client-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .client-info h4 {
            color: var(--primary-pink);
            margin-bottom: 0.2rem;
        }
        
        .client-info p {
            color: #777;
            font-size: 0.9rem;
        }
        
        .about {
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--teal), var(--dark-blue));
            color: white;
            text-align: center;
        }
        
        .about-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .about p {
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .cta {
            padding: 4rem 2rem;
            background: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.9)), 
                        url('https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1888&q=80');
            background-size: cover;
            background-position: center;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-pink);
        }
        
        .cta p {
            max-width: 600px;
            margin: 0 auto 2rem;
            font-size: 1.1rem;
        }
        
        footer {
            background: linear-gradient(135deg, var(--dark-gray), #111);
            color: white;
            padding: 3rem 2rem 1.5rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto 2rem;
        }
        
        .footer-column h3 {
            color: var(--primary-pink);
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-column ul li a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-column ul li a:hover {
            color: var(--primary-pink);
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-links a {
            color: white;
            background-color: var(--primary-pink);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background-color: var(--dark-pink);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #aaa;
            font-size: 0.9rem;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                padding: 1rem;
            }
            
            .logo {
                margin-bottom: 1rem;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0.5rem;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/logo.png" alt="Joy Beauty Logo">
            Joy Beauty & Cosmetic
        </div>
        <nav>
            <ul>
                <li><a href="#services">Services</a></li>
                <li><a href="#products">Products</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="pages/user/login.php" class="login-btn">Login</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <h1>Your Beauty, Our Passion</h1>
        <p>Discover our premium beauty services and cosmetic products designed to enhance your natural beauty</p>
        <a href="pages/user/login.php"><button class="book-btn">Book an Appointment</button></a>
    </section>

    <section class="services" id="services">
        <h2 class="section-title">Our Services</h2>
        <div class="service-grid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <img src="<?php echo htmlspecialchars($service['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-img">
                        <div class="service-content">
                            <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p><?php echo htmlspecialchars($service['description']); ?></p>
                            <button class="book-btn">Book Now</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No services available at the moment. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="products" id="products">
        <h2 class="section-title">Featured Products</h2>
        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">Ksh<?php echo number_format($product['price'], 2); ?></p>
                            <button class="book-btn">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No products available at the moment. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="testimonials" id="testimonials">
        <h2 class="section-title">What Our Clients Say</h2>
        <div class="testimonial-grid">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <p class="quote">"<?php echo htmlspecialchars($testimonial['quote']); ?>"</p>
                        <div class="client">
                            <?php if (!empty($testimonial['client_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($testimonial['client_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" class="client-img">
                            <?php else: ?>
                                <div class="default-avatar">
                                    <?php echo strtoupper(substr($testimonial['client_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="client-info">
                                <h4><?php echo htmlspecialchars($testimonial['client_name']); ?></h4>
                                <?php if (!empty($testimonial['client_title'])): ?>
                                    <p><?php echo htmlspecialchars($testimonial['client_title']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No testimonials available at the moment. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="about" id="about">
        <h2 class="section-title" style="color: white;">About Us</h2>
        <div class="about-content">
            <p>At Joy Beauty & Cosmetic, we believe in enhancing your natural beauty and providing a luxurious experience. Our team of expert stylists and estheticians are dedicated to offering personalized services and high-quality products that leave you feeling radiant and confident. We are committed to using the finest ingredients and latest techniques to ensure your satisfaction.</p>
            <a href="pages/user/login.php"><button class="book-btn">Learn More</button></a>
        </div>
    </section>

    <section class="cta">
        <h2>Ready to Experience Joy Beauty?</h2>
        <p>Book your appointment today and let us pamper you with our exceptional services and products.</p>
        <a href="pages/user/login.php"><button class="book-btn">Book Your Transformation</button></a>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Joy Beauty & Cosmetic</h3>
                <p>Your ultimate destination for beauty and wellness.</p>
                <p>Nairobi, Kenya</p>
                <p>Email: info@joybeauty.com</p>
                <p>Phone: +254 7XX XXX XXX</p>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#products">Products</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="pages/user/login.php">Login</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date("Y"); ?> Joy Beauty & Cosmetic. All Rights Reserved.
        </div>
    </footer>
</body>
</html>