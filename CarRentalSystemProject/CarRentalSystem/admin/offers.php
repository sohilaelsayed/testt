<?php
include "../includes/config.php";

// استعلام لجلب العروض مع تفاصيل السيارة
$query = "SELECT offers.*, cars.name, cars.model, cars.price_per_day 
          FROM offers 
          JOIN cars ON offers.car_id = cars.id";
$result = mysqli_query($conn, $query);
?>

<h2>Current Offers</h2>
<table border="1" cellpadding="10">
    <tr>
        <th>Car</th>
        <th>Original Price</th>
        <th>Discount</th>
        <th>Start</th>
        <th>End</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= htmlspecialchars($row['name'] . " (" . $row['model'] . ")") ?></td>
            <td>$<?= number_format($row['price_per_day'], 2) ?></td>
            <td><?= $row['discount_percentage'] ?>%</td>
            <td><?= $row['start_date'] ?></td>
            <td><?= $row['end_date'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>