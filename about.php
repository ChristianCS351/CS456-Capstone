<?php
// Database connection removed for local testing
// $host = 'localhost';
// $user = 'root'; 
// $pass = 'mysql'; 
// $dbname = '351final';

// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     die("Connection failed: " . $e->getMessage());
// }

$message = "";
$status_class = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $problem = $_POST['problem'];

    // Skip database insertion and simulate a successful submission
    $message = "Your message has been successfully sent! Our team will get back to you shortly.";
    $status_class = "success";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us & Contact - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">
    
    <!-- Meta tags for SEO best practices -->
    <meta name="description" content="Learn more about Pantry Pilot, your ultimate grocery and pantry management tool. Contact us for support or inquiries.">
    <meta name="keywords" content="Pantry Pilot, about, contact, support, grocery management">
    
    <!-- Modern Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="about.css">
</head>
<body class="about-page">

    <!-- Top Navigation aligned with the rest of the site -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="nav-brand">
                    <img src="PantryPilotlogo2.png" style="height: 50px;">
                </a>
                <a href="index.php">Home</a>
                <a href="tracking.php">Pantry</a>
                <a href="grocery.php">Shopping List</a>
                <a href="about.php" class="active">About & Help</a>
            </div>
            <div class="nav-right">
                <a href="AccountInfo.php">Account Info</a>
                <a href="login.php" class="btn-login">Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>We are Pantry Pilot</h1>
            <p>Your intelligent companion for seamless grocery and pantry management.</p>
        </div>
    </header>

    <main class="main-content">
        <!-- About Section -->
        <section class="about-info card-glass" id="about-info">
            <div class="section-badge"><i class="fa-solid fa-leaf"></i> Our Mission</div>
            <h2>Reduce Waste, Save Time</h2>
            <p>
                At Pantry Pilot, we believe managing your groceries should be effortless. 
                Our platform tracks your pantry inventory, alerts you about expiring foods, 
                and helps you build smarter shopping lists. Whether you're a busy professional 
                or managing a large household, we provide the tools you need to minimize food waste 
                and maximize efficiency.
            </p>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fa-solid fa-barcode"></i>
                    <h3>Smart Tracking</h3>
                    <p>Easily manage items using our built-in barcode scanner system.</p>
                </div>
                <div class="feature-item">
                    <i class="fa-regular fa-clock"></i>
                    <h3>Expiry Alerts</h3>
                    <p>Never let good food go bad with timely expiration notifications.</p>
                </div>
                <div class="feature-item">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <h3>Dynamic Lists</h3>
                    <p>Auto-generate grocery needs based on your current inventory.</p>
                </div>
            </div>
        </section>

        <div class="grid-layout">
            <!-- Contact Form Section -->
            <section class="contact-section card-glass" id="contact-form">
                <h2><i class="fa-solid fa-envelope-open-text"></i> Get In Touch</h2>
                <p class="subtitle">Have a question or found an issue? Let us know!</p>
                
                <?php if (!empty($message)): ?>
                    <div class="alert message-<?php echo $status_class; ?>">
                        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="about.php#contact-form" method="post" class="modern-form">
                    <div class="input-group">
                        <input type="text" id="subject" name="subject" required placeholder=" ">
                        <label for="subject"><i class="fa-solid fa-heading"></i> Subject</label>
                        <div class="focus-border"></div>
                    </div>

                    <div class="input-group">
                        <textarea id="problem" name="problem" required placeholder=" "></textarea>
                        <label for="problem"><i class="fa-solid fa-comment-dots"></i> How can we help?</label>
                        <div class="focus-border"></div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span>Send Message</span> <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </section>

            <!-- FAQ Section -->
            <section class="faq-section card-glass" id="faqs">
                <h2><i class="fa-solid fa-circle-question"></i> Frequently Asked Questions</h2>
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            How do I scan a barcode? <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <p>You can use the built-in barcode scanner on the Pantry page. Simply click "Scan Item", grant camera permissions, and point your camera at the product's barcode.</p>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-header">
                            How accurate are expiration dates? <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <p>We use standard shelf-life guidelines for common items, but you can always manually edit the expiration date to match the exact label on your product.</p>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-header">
                            Can I share my list? <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <p>Currently, you can access your list from any device by logging into your account. Multi-user sharing features are coming in our next major update!</p>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-header">
                            Is Pantry Pilot free? <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <p>Yes! The core features of Pantry Pilot are completely free to use to help you reduce food waste.</p>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <button class="accordion-header">
                            Can I sort my tables? <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <p>Yes! The sorting feature allows you to sort your tables in many various ways, just click the cookie to change the table's sorting conditions.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Interactive Scripts -->
    <script>
        // Smooth FAQ Accordion
        document.querySelectorAll('.accordion-header').forEach(button => {
            button.addEventListener('click', () => {
                const accordionContent = button.nextElementSibling;
                const icon = button.querySelector('i');
                
                // Toggle active class
                button.classList.toggle('active');
                
                if (button.classList.contains('active')) {
                    accordionContent.style.maxHeight = accordionContent.scrollHeight + "px";
                    icon.style.transform = "rotate(180deg)";
                } else {
                    accordionContent.style.maxHeight = null;
                    icon.style.transform = "rotate(0deg)";
                }
            });
        });

        // Form placeholder animation handling for auto-fill
        const inputs = document.querySelectorAll('.input-group input, .input-group textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                if (input.value.trim() !== "") {
                    input.classList.add('has-value');
                } else {
                    input.classList.remove('has-value');
                }
            });
        });
    </script>
</body>
</html>
