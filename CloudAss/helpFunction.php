<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'clouddb';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function generateUniqueID($prefix) {
    global $conn;
    
    $table = '';
    $idColumn = '';
    
    switch ($prefix) {
        case 'C':
            $table = 'Cart';
            $idColumn = 'cartID';
            break;
        case 'U':
            $table = 'Users';
            $idColumn = 'userID';
            break;
        case 'P':
            $table = 'Product';
            $idColumn = 'productID';
            break;
        case 'O':
            $table = 'Orders';
            $idColumn = 'orderID';
            break;
        case 'OT':
            $table = 'OrderItem';
            $idColumn = 'orderItemID';
            break;
        case 'PM':
            $table = 'ProductImage';
            $idColumn = 'productImageID';
            break;
    }
    
    if ($table && $idColumn) {
        $sql = "SELECT $idColumn FROM $table ORDER BY $idColumn DESC LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $lastID = $result->fetch_assoc()[$idColumn];
            $number = intval(substr($lastID, strlen($prefix))) + 1;
        } else {
            $number = 10001;
        }
        
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    return $prefix . str_pad(10001, 5, '0', STR_PAD_LEFT); 
}


function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($input));
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?> 