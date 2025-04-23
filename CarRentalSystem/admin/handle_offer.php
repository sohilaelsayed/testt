<?php
session_start();
include "../includes/config.php";

// Validate Inputs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = intval($_POST['car_id']);
    $discount = intval($_POST['discount']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    // Validate discount range
    if ($discount < 1 || $discount > 100) {
        $_SESSION['offer_msg'] = "Discount must be between 1-100%";
        $_SESSION['offer_msg_type'] = 'error';
        header("Location: DashboardAdmin.php#offers");
        exit;
    }

    // Validate dates
    if (strtotime($start_date) >= strtotime($end_date)) {
        $_SESSION['offer_msg'] = "End date must be after start date";
        $_SESSION['offer_msg_type'] = 'error';
        header("Location: DashboardAdmin.php#offers");
        exit;
    }

    // Use prepared statements
    $stmt = $conn->prepare("
        INSERT INTO offers (car_id, discount_percent, start_date, end_date)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $car_id, $discount, $start_date, $end_date);

    if ($stmt->execute()) {
        $_SESSION['offer_msg'] = "Offer added successfully!";
        $_SESSION['offer_msg_type'] = 'success';
    } else {
        $_SESSION['offer_msg'] = "Database error: " . $stmt->error;
        $_SESSION['offer_msg_type'] = 'error';
    }
    $stmt->close();
} else {
    $_SESSION['offer_msg'] = "Invalid request method";
    $_SESSION['offer_msg_type'] = 'error';
}

header("Location: DashboardAdmin.php#offers");
exit;
?>