<?php
session_start();
require_once 'helpFunction.php';

if (isset($_SESSION['userID'])) {
    header('Location: home.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $address = sanitize($_POST['address']);

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword) || empty($address)) {
        $error = 'Please fill in all fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $checkSql = "SELECT userID FROM Users WHERE userEmail = '$email'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            $userID = generateUniqueID('U');
            $hashedPassword = hashPassword($password);

            $sql = "INSERT INTO Users (userID, userEmail, userPass, userName, userAddress) 
                    VALUES ('$userID', '$email', '$hashedPassword', '$name', '$address')";

            if ($conn->query($sql)) {
                $success = '<b>Registration successful!</b> You can now login.';
                $_POST = array();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

include 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm register-card">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4">Register</h1>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="custom-success-alert">
                            <?php echo $success; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="login.php" class="btn btn-primary btn-lg px-5">Login Now</a>
                        </div>
                    <?php else: ?>
                        <form method="post" action="register.php">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <div class="form-text">We'll never share your email with anyone else.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required 
                                       minlength="6">
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 10px;
}

.card-body {
    padding: 3rem;
}

.custom-success-alert {
    background-color: #d1e7dd;
    color: #0f5132;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #badbcc;
    border-radius: 0.5rem;
    text-align: center;
    font-size: 1.1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn-lg {
    font-size: 1.2rem;
    padding: 0.75rem 2rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 576px) {
    .card-body {
        padding: 2rem;
    }
}

.register-card {
    background: #e9ecef;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
}
</style>

<script>
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'footer.php'; ?>
