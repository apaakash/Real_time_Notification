<?php

$register_error1 = "";
$register_error2 = "";
$login_error1 = "";
$login_error2 = "";
$product_error1 = "";
$product_error2 = "";
$product_error3 = "";
$product_error4 = "";

// Include PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "ecommerce");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// User registration
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if email already exists
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        $register_error1 = "Email already registered!";
    } else {
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            $register_error2 = "Registration successful!";
        } else {
            $register_error1 = "Error during registration. Please try again.";
        }
    }
}

// User login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email' AND password = '$password'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $login_error1 = "Login successful!";
    } else {
        $login_error2 = "Invalid username or password!";
    }
}

// Logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Place order
if (isset($_POST['order'])) {
    if (!isset($_SESSION['user_id'])) {
        $product_error1 = "You must be logged in to place an order.";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_name = $_POST['product_name'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];

    $sql = "INSERT INTO orders (user_id, product_name, quantity, total_price) VALUES ('$user_id', '$product_name', '$quantity', '$total_price')";

    if ($conn->query($sql) === TRUE) {
        // Fetch user's email
        $user_result = $conn->query("SELECT email FROM users WHERE id = '$user_id'");
        $user = $user_result->fetch_assoc();
        $email = $user['email'];

        // Send order confirmation email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'Your_verifyed_email'; // Your email
            $mail->Password = 'Your_api_key'; // Your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('Your_verifyed_email', 'E-commerce Website');
            $mail->addAddress($email); // User's email

            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation';
            $mail->Body = "<h1>Order Confirmation</h1>
                <p>Thank you for your order.:</p>
                <p><strong>Product:</strong> $product_name</p>
                <p><strong>Quantity:</strong> $quantity</p>
                <p><strong>Total Price:</strong> ₹$total_price</p>";

            $mail->send();
            $product_error2 = "Order placed successfully! Confirmation email sent.";
        } catch (Exception $e) {
            $product_error3 = "Order placed, but email could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        $product_error4 = "Error placing order. Please try again.";
    }
}

$conn->close();
?>

<!-- HTML for Registration, Login, and Order -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Simple E-commerce</title>
</head>

<body>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <h1>Register</h1>
        <form method="POST">
            <input type="text" name="name" placeholder="Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
            <p style="color: red;"><?php echo $register_error1; ?></p>
            <p style="color: green;"><?php echo $register_error2; ?></p>
        </form>

        <h1>Login</h1>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
            <p style="color: green;"><?php echo $login_error1; ?></p>
            <p style="color: red;"><?php echo $login_error2; ?></p>
        </form>
    <?php else: ?>
        <h1>Place Order</h1>
        <form method="POST">
            <input type="text" name="product_name" placeholder="Product Name" required>
            <input type="number" name="quantity" placeholder="Quantity" required>
            <input type="number" step="0.01" name="total_price" placeholder="Total Price" required>
            <button type="submit" name="order">Place Order</button>
            <p style="color: green;"><?php echo $product_error2; ?></p>
            <p style="color: red;"><?php echo $product_error3; ?></p>
            <p style="color: red;"><?php echo $product_error4; ?></p>
        </form>
        <form method="POST">
            <button type="submit" name="logout">Logout</button>
        </form>
    <?php endif; ?>
</body>

</html>
