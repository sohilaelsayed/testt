<?php
include "../includes/config.php";

$car_id = $_POST['car_id'];
$discount = $_POST['discount'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

$sql = "INSERT INTO offers (car_id, discount_percent, start_date, end_date) 
        VALUES ('$car_id', '$discount', '$start_date', '$end_date')";

if (mysqli_query($conn, $sql)) {
    header("Location: ../admin/DashboardAdmin.php#offers");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>