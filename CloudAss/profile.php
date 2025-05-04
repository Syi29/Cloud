<?php
session_start();
require_once 'helpFunction.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];
$error = '';
$success = '';

$sql = "SELECT * FROM Users WHERE userID = '$userID'";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

if ($result->num_rows === 0) {
    die("No user found with ID: " . htmlspecialchars($userID));
}

$user = $result->fetch_assoc();

if (!$user) {
    die("Failed to fetch user data");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($address)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidEmail($email) && $email !== $user['userEmail']) {
        $error = 'Please enter a valid email address';
    } else {
        if ($email !== $user['userEmail']) {
            $checkSql = "SELECT userID FROM Users WHERE userEmail = '$email' AND userID != '$userID'";
            $checkResult = $conn->query($checkSql);
            if ($checkResult->num_rows > 0) {
                $error = 'Email already registered to another account';
            }
        }

        if (!empty($currentPassword)) {
            if (!verifyPassword($currentPassword, $user['userPass'])) {
                $error = 'Current password is incorrect';
            } elseif (empty($newPassword) || empty($confirmPassword)) {
                $error = 'Please fill in both new password fields';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters long';
            }
        }

        if (empty($error)) {
            $updateSql = "UPDATE Users SET 
                         userName = '$name',
                         userEmail = '$email',
                         userAddress = '$address'";
            
            if (!empty($currentPassword) && !empty($newPassword)) {
                $hashedPassword = hashPassword($newPassword);
                $updateSql .= ", userPass = '$hashedPassword'";
            }

            $updateSql .= " WHERE userID = '$userID'";

            if ($conn->query($updateSql)) {
                $_SESSION['userName'] = $name;
                $_SESSION['userEmail'] = $email;
                $success = 'Profile updated successfully';
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
            } else {
                $error = 'Failed to update profile';
                echo json_encode(['success' => false, 'message' => 'Update failed!']);
            }
        }
    }
    exit;
}

include 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h4 id="profile-name"><?php echo htmlspecialchars($user['userName']); ?></h4>
                    <p id="profile-email"><?php echo htmlspecialchars($user['userEmail']); ?></p>
                    <p id="profile-address"><?php echo htmlspecialchars($user['userAddress']); ?></p>
                    <p class="text-muted">
                        <small>User since: <?php echo isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?></small>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Edit Profile</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert custom-success-alert"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div id="profile-success-msg" class="alert alert-success" style="display:none;"></div>

                    <form id="profile-form" method="post" action="profile.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($user['userName']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($user['userEmail']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Shipping Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['userAddress']); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5>Change Password</h5>
                        <p class="text-muted small">Leave blank if you don't want to change your password</p>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-success-alert {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #0f5132;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
    animation: fadeIn 0.5s;
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
</style>

<script>
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        if (this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
});

document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    fetch('profile.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const msgBox = document.getElementById('profile-success-msg');
        if (data.success) {
            msgBox.textContent = data.message;
            msgBox.style.display = 'block';
            document.getElementById('profile-name').textContent = form.name.value;
            document.getElementById('profile-email').textContent = form.email.value;
            document.getElementById('profile-address').textContent = form.address.value;
        } else {
            msgBox.textContent = data.message;
            msgBox.style.display = 'block';
            msgBox.classList.remove('alert-success');
            msgBox.classList.add('alert-danger');
        }
    });
});
</script>

<?php include 'footer.php'; ?>
