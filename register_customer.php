<?php
// register_customer.php - Customer Registration Handler

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "afro_style_kenya";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($dbname);
} else {
    die("Error creating database: " . $conn->error);
}

// Create customers table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    county VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
    occupation VARCHAR(200),
    interests TEXT,
    preferred_size VARCHAR(10),
    newsletter BOOLEAN DEFAULT FALSE,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($createTable)) {
    die("Error creating table: " . $conn->error);
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = sanitize_input($_POST['phone']);
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $county = sanitize_input($_POST['county']);
    $gender = isset($_POST['gender']) ? sanitize_input($_POST['gender']) : null;
    $occupation = isset($_POST['occupation']) ? sanitize_input($_POST['occupation']) : null;
    $preferred_size = isset($_POST['preferred_size']) ? sanitize_input($_POST['preferred_size']) : null;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Handle interests array
    $interests = '';
    if (isset($_POST['interests']) && is_array($_POST['interests'])) {
        $interests = implode(', ', $_POST['interests']);
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email address already registered";
        } else {
            // Insert customer data
            $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, phone, date_of_birth, address, city, county, gender, occupation, interests, preferred_size, newsletter) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssssssi", $first_name, $last_name, $email, $phone, $date_of_birth, $address, $city, $county, $gender, $occupation, $interests, $preferred_size, $newsletter);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Welcome to Afro Style Kenya family.";
                $customer_id = $conn->insert_id;
                
                // You can add email notification here
                // send_welcome_email($email, $first_name);
                
            } else {
                $error = "Registration failed: " . $stmt->error;
            }
            
            $stmt->close();
        }
        $check_email->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status - Afro Style Kenya</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-message {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #D2691E;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #B8860B;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="https://images.pexels.com/photos/934070/pexels-photo-934070.jpeg?auto=compress&cs=tinysrgb&w=100&h=100&fit=crop" alt="Afro Style Kenya Logo" class="logo-img">
                <h1>Afro Style Kenya</h1>
            </div>
            <nav class="nav">
                <ul class="nav-list">
                    <li><a href="index.html" class="nav-link">Home</a></li>
                    <li><a href="about.html" class="nav-link">About Us</a></li>
                    <li><a href="products.html" class="nav-link">Products</a></li>
                    <li><a href="gallery.html" class="nav-link">Gallery</a></li>
                    <li><a href="contact.html" class="nav-link">Contact</a></li>
                    <li><a href="register.html" class="nav-link register-btn">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="page-content">
        <div class="container">
            <?php if (isset($success)): ?>
                <div class="status-message success">
                    <h2>Registration Successful! 🎉</h2>
                    <p><?php echo $success; ?></p>
                    <p>Your customer ID is: <strong>#<?php echo sprintf('%06d', $customer_id); ?></strong></p>
                    <p>You will receive a welcome email shortly with exclusive offers and updates.</p>
                    <a href="index.html" class="back-btn">Continue Shopping</a>
                </div>
            <?php elseif (isset($error)): ?>
                <div class="status-message error">
                    <h2>Registration Failed ❌</h2>
                    <p><?php echo $error; ?></p>
                    <a href="register.html" class="back-btn">Try Again</a>
                </div>
            <?php else: ?>
                <div class="status-message">
                    <h2>Registration Form</h2>
                    <p>Please fill out the registration form to join our fashion community.</p>
                    <a href="register.html" class="back-btn">Go to Registration</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Afro Style Kenya</h3>
                    <p>Premium African-inspired fashion for the modern Kenyan family.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="products.html">Products</a></li>
                        <li><a href="gallery.html">Gallery</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📍 Westlands, Nairobi, Kenya</p>
                    <p>📞 +254 700 123 456</p>
                    <p>✉️ info@afrostylekenya.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Afro Style Kenya. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>