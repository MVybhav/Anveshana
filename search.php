<?php
// search.php - Search page for destinations

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include_once 'config/db.php';

$destination = $_GET['destination'] ?? '';
$from_location = $_GET['from_location'] ?? '';
$departure_date = $_GET['departure_date'] ?? '';

$exploring_destinations = isset($_GET['exploring_destinations']);

$search_results = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['destination']) || isset($_GET['departure_date'])) ) {
    $search_performed = true;

    // Build the SQL query based on provided parameters
    $sql = "SELECT * FROM packages WHERE 1=1";
    $params = [];
    $types = '';

    if (!$exploring_destinations && !empty($destination)) {
        $sql .= " AND destination LIKE ?";
        $params[] = '%' . $destination . '%';
        $types .= 's';
    }



    // Execute the query
    if (!$conn->connect_error) {
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $search_results[] = $row;
            }
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Destinations | Anveshana</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff6b6b;
            --secondary: #5f27cd;
            --dark: #22223b;
            --light: #f8f7ff;
            --gray: #a1a1aa;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.1);
        }
        h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 2.2rem;
        }
        .search-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }
        .form-group {
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group .number-input {
            width: 90%;
            padding: 12px 15px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            background-color: #fdfdfd;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group .number-input:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
        }
        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 45px;
            color: var(--gray);
        }
        .form-group input[type="text"],
        .form-group input[type="date"] {
            padding-left: 40px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        .number-input {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0;
        }
        .number-input button {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: background-color 0.2s;
        }
        .number-input button:hover {
            background-color: #4a1f9e;
        }
        .number-input input {
            flex-grow: 1;
            text-align: center;
            border: none;
            outline: none;
            font-size: 1.1rem;
            -moz-appearance: textfield;
        }
        .number-input input::-webkit-outer-spin-button,
        .number-input input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .search-button {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .results-section h2 {
            color: var(--secondary);
            margin-bottom: 20px;
            text-align: center;
        }
        .package-card {
            display: flex;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .package-card:hover {
            transform: translateY(-5px);
        }
        .package-card img {
            width: 200px;
            height: 180px;
            object-fit: cover;
            border-radius: 12px 0 0 12px;
        }
        .package-info {
            padding: 20px;
            flex-grow: 1;
        }
        .package-info h3 {
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .package-info p {
            margin-bottom: 8px;
            color: #555;
        }
        .package-info .price {
            font-weight: 700;
            color: var(--secondary);
            font-size: 1.2rem;
            margin-top: 15px;
        }
        .no-results {
            text-align: center;
            color: var(--gray);
            padding: 30px;
            font-size: 1.1rem;
        }
        @media (min-width: 768px) {
            .search-button {
                grid-column: span 1;
            }
        }
        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
                padding: 20px;
            }
            .package-card {
                flex-direction: column;
            }
            .package-card img {
                width: 100%;
                height: 200px;
                border-radius: 12px 12px 0 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Where do you want to go?</h1>
        <form class="search-form" action="search.php" method="GET">
            <div class="form-group">
                <label for="from_location">From</label>
                <i class="fas fa-map-marker-alt icon"></i>
                <input type="text" id="from_location" name="from_location" placeholder="Place" value="<?= htmlspecialchars($from_location) ?>">
            </div>
            <div class="form-group">
                <label for="destination">To</label>
                <i class="fas fa-map-marker-alt icon"></i>
                <input type="text" id="destination" name="destination" placeholder="Place" value="<?= htmlspecialchars($destination) ?>" <?= $exploring_destinations ? 'disabled' : '' ?>>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="exploring_destinations" name="exploring_destinations" onchange="toggleDestinationInput()" <?= $exploring_destinations ? 'checked' : '' ?>>
                <label for="exploring_destinations">I am exploring destinations</label>
            </div>
            <div class="form-group">
                <label for="departure_date">Departure Date</label>
                <i class="fas fa-calendar-alt icon"></i>
                <input type="date" id="departure_date" name="departure_date" value="<?= htmlspecialchars($departure_date) ?>">
            </div>

            <button type="submit" class="search-button">Next</button>
        </form>

        <?php if ($search_performed): ?>
            <div class="results-section">
                <h2>Search Results</h2>
                <?php if (!empty($search_results)): ?>
                    <?php foreach ($search_results as $package): ?>
                        <div class="package-card">
                            <img src="images/packages/<?= htmlspecialchars($package['image']) ?>" alt="<?= htmlspecialchars($package['package_name']) ?>">
                            <div class="package-info">
                                <h3><?= htmlspecialchars($package['package_name']) ?></h3>
                                <p><b>Destination:</b> <?= htmlspecialchars($package['destination']) ?></p>

                                <p class="price">₹<?= number_format($package['price'], 2) ?> per person</p>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="book.php?package_id=<?= $package['id'] ?>" class="search-button" style="display:inline-block;width:auto;padding:8px 15px;font-size:1rem;margin-top:10px;">Book Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="search-button" style="display:inline-block;width:auto;padding:8px 15px;font-size:1rem;margin-top:10px;">Book Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results">No packages found matching your criteria.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleDestinationInput() {
            const destinationInput = document.getElementById('destination');
            const exploringCheckbox = document.getElementById('exploring_destinations');
            if (exploringCheckbox.checked) {
                destinationInput.value = '';
                destinationInput.disabled = true;
            } else {
                destinationInput.disabled = false;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            toggleDestinationInput();
        });
    </script>
</body>
</html>