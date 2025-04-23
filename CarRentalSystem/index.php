<?php
session_start();
include "includes/config.php";

// Database connection check
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize variables
$search = $type = $sort = '';
$error = '';
$regularResult = $premiumResult = null;

// Sanitize and validate inputs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'none';
    
    // Validate car type
    $validTypes = ['Sedan', 'SUV', 'Crossover'];
    $type = in_array($type, $validTypes) ? $type : '';
    
    // Validate sort parameter
    $validSorts = ['price_asc', 'price_desc', 'year_asc', 'year_desc', 'available'];
    $sort = in_array($sort, $validSorts) ? $sort : 'none';
}

// Prepare base queries using prepared statements
function prepareQuery($conn, $search, $type, $isPremium = false) {
    $where = [];
    $params = [];
    $types = '';

    // Search condition
    if (!empty($search)) {
        $where[] = "(cars.name LIKE ? OR cars.model LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ss';
    }

    // Type filter
    if (!empty($type)) {
        $where[] = "cars.type = ?";
        $params[] = $type;
        $types .= 's';
    }

    // Premium category filter
    if ($isPremium) {
        $where[] = "cars.category = 'premium'";
    } else {
        // Non-premium users can't see premium cars
        if (!isset($_SESSION['user']) || 
            (!in_array($_SESSION['user']['role'], ['admin', 'premium']))) {
            $where[] = "cars.category != 'premium'";
        }
    }

    // Build query
    $query = "SELECT cars.*, offers.discount_percent, offers.start_date, offers.end_date 
              FROM cars 
              LEFT JOIN offers ON cars.id = offers.car_id";
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(' AND ', $where);
    }

    return [$query, $params, $types];
}

// Add sorting to query
function addSorting(&$query, $sort) {
    switch ($sort) {
        case 'price_asc':
            $query .= " ORDER BY price_per_day ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY price_per_day DESC";
            break;
        case 'year_asc':
            $query .= " ORDER BY CAST(SUBSTRING_INDEX(model, ' ', -1) AS UNSIGNED) ASC";
            break;
        case 'year_desc':
            $query .= " ORDER BY CAST(SUBSTRING_INDEX(model, ' ', -1) AS UNSIGNED) DESC";
            break;
        case 'available':
            $query .= " ORDER BY status ASC";
            break;
    }
}

// Execute queries with prepared statements
// Execute queries with prepared statements
try {
    // Regular cars query
    list($regularQuery, $regularParams, $regularTypes) = prepareQuery($conn, $search, $type);
    addSorting($regularQuery, $sort);
    $stmtRegular = $conn->prepare($regularQuery);
    if ($stmtRegular) {
        // Only bind parameters if there are actual parameters to bind
        if (!empty($regularParams)) {
            $stmtRegular->bind_param($regularTypes, ...$regularParams);
        }
        $stmtRegular->execute();
        $regularResult = $stmtRegular->get_result();
    }

    // Premium cars query
    list($premiumQuery, $premiumParams, $premiumTypes) = prepareQuery($conn, $search, $type, true);
    addSorting($premiumQuery, $sort);
    $stmtPremium = $conn->prepare($premiumQuery);
    if ($stmtPremium) {
        // Only bind parameters if there are actual parameters to bind
        if (!empty($premiumParams)) {
            $stmtPremium->bind_param($premiumTypes, ...$premiumParams);
        }
        $stmtPremium->execute();
        $premiumResult = $stmtPremium->get_result();
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Service</title>
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/main-content.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/sort-filter.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .car-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 80px; padding: 20px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .premium-section { margin-bottom: 40px; }
        .card img { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; }
        .available { color: #4CAF50; font-weight: bold; }
        .not-available { color: #f44336; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Car Rental Service</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="my_rental.php">My Rentals</a></li>
                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <li><a href="admin/DashboardAdmin.php">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="Login-Signup-Logout/logout.php">Logout</a></li>
                    <li>
                        <a href="profile.php" class="profile-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="Login-Signup-Logout/login.php">Login</a></li>
                    <li><a href="Login-Signup-Logout/signup.php">Sign Up</a></li>
                <?php endif; ?>
                <li><a href="about us.html">About Us</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Available Cars</h2>
        <div class="sort-filter">
            <form method="GET">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <label>Sort By:</label>
                <select name="sort">
                    <option value="none" <?= $sort == 'none' ? 'selected' : '' ?>>Default</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Price (Low-High)</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Price (High-Low)</option>
                    <option value="year_asc" <?= $sort == 'year_asc' ? 'selected' : '' ?>>Year (Old-New)</option>
                    <option value="year_desc" <?= $sort == 'year_desc' ? 'selected' : '' ?>>Year (New-Old)</option>
                    <option value="available" <?= $sort == 'available' ? 'selected' : '' ?>>Availability</option>
                </select>
                <button type="submit">Apply</button>
            </form>
        </div>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['premium', 'admin'])): ?>
            <div class="premium-section">
                <h3>Premium Cars</h3>
                <div class="car-grid">
                    <?= renderCars($premiumResult) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="regular-section">
            <h3><?= (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'premium') 
                    ? 'Standard Vehicles' 
                    : 'Available Cars' ?></h3>
            <div class="car-grid">
                <?= renderCars($regularResult) ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: info@carrentalservice.com</p>
                <p>Phone: 0000000</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <ul class="social-links">
                    <li><a href="#"><i class="fab fa-facebook"></i></a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                    <li><a href="#"><i class="fab fa-linkedin"></i></a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="about us.html">About Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Subscribe</h3>
                <form>
                    <input type="email" placeholder="Enter your email" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Car Rental Service. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

<?php
function renderCars($result) {
    if (!$result || $result->num_rows === 0) {
        return '<p>No cars available</p>';
    }

    $output = '';
    while ($car = $result->fetch_assoc()) {
        $output .= renderCarCard($car);
    }
    return $output;
}

function renderCarCard($car) {
    $name = htmlspecialchars($car['name'] ?? 'Unknown');
    $model = htmlspecialchars($car['model'] ?? 'Unknown');
    $type = htmlspecialchars($car['type'] ?? 'N/A');
    $originalPrice = isset($car['price_per_day']) ? number_format($car['price_per_day'], 2) : 'N/A';
    $discountPercent = $car['discount_percent'] ?? 0;
    $status = $car['status'] ?? 'not available';
    $image = !empty($car['image']) 
        ? 'images/' . htmlspecialchars($car['image']) 
        : 'images/default.png';

    // Calculate offer price
    $offerPrice = '';
    if ($discountPercent > 0) {
        $offerPrice = $originalPrice * (1 - $discountPercent / 100);
        $priceHtml = "<span class='original-price'>$$originalPrice</span> 
                      <span class='discount-price'>$$offerPrice</span>";
    } else {
        $priceHtml = $originalPrice !== 'N/A' ? "$$originalPrice" : 'N/A';
    }

    $availabilityClass = $status === 'available' ? 'available' : 'not-available';
    $availabilityText = ucfirst($status);

    ob_start();
    ?>
    <div class="card">
        <img src="<?= $image ?>" alt="<?= "$name $model" ?>">
        <h3><?= "$name ($model)" ?></h3>
        <div class="car-details">
            <p><strong>Type:</strong> <?= $type ?></p>
            <p><strong>Price:</strong> <?= $priceHtml ?>/day</p>
            <p><strong>Status:</strong> 
                <span class="<?= $availabilityClass ?>"><?= $availabilityText ?></span>
            </p>
        </div>
        <?php if ($status === 'available'): ?>
            <form method="GET" action="rent_request.php">
                <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                <button type="submit" class="btn-rent">Rent Now</button>
            </form>
        <?php else: ?>
            <button class="btn-disabled" disabled>Not Available</button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>