<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

// Function to send JSON response
function sendJsonResponse($data)
{
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Start output buffering
ob_start();
// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'btonedatabase';


$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get customer name from session
$customer_name = isset($_SESSION['login']) ? $_SESSION['login'] :
    (isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '');

// Debug information
/*echo "<div style='background: #f5f5f5; padding: 20px; margin: 20px;'>";
echo "<h3>Current Session Information</h3>";
echo "Current Date and Time (UTC): " . date('Y-m-d H:i:s') . "<br>";
echo "Current User's Login: " . htmlspecialchars($customer_name) . "<br>";
echo "Session Data:<br>";
echo "<pre>";*/
//print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Fetch available equipment
function getAvailableEquipment($conn)
{
    $sql = "SELECT id, item_name, category, available_quantity, unit_price 
            FROM inventory 
            WHERE available_quantity > 0 
            AND category IN ('Sound Equipment', 'Visual Equipment', 'Lighting Equipment', 'Effects Equipment', 'Furniture')
            ORDER BY category, item_name";
    $result = $conn->query($sql);
    $equipment = [];

    while ($row = $result->fetch_assoc()) {
        $equipment[$row['category']][] = $row;
    }

    return $equipment;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Make sure user is logged in
    if (empty($customer_name)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Please log in to continue']);
    }

    $contact_email = isset($_POST['contact_email']) ? filter_var($_POST['contact_email'], FILTER_SANITIZE_EMAIL) : '';
    $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
    $event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
    $event_time = isset($_POST['event_time']) ? trim($_POST['event_time']) : '';
    $event_package = isset($_POST['event_package']) ? trim($_POST['event_package']) : '';
    $location_type = isset($_POST['location_type']) ? trim($_POST['location_type']) : '';
    $additional_details = isset($_POST['additional_details']) ? trim($_POST['additional_details']) : '';
    $total_cost = 0;

    // Set the status based on which button was clicked
    $status = (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') ? 'In Cart' : 'Pending';

    // Validate required fields
    if (empty($event_date) || empty($event_time) || empty($event_package)) {
        sendJsonResponse(['status' => 'error', 'message' => 'Required fields are missing']);
    }

    // Calculate travel fee
    $travel_fee = 0;
    if ($event_package == 'Portable Bar' || $event_package == 'Portable Sound and Lights') {
        $travel_fee = isset($_POST['travel_fee']) ? (int) $_POST['travel_fee'] : 0;
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // Check equipment availability
        $equipment_available = true;
        $equipment_errors = [];

        if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equipment_id => $quantity) {
                if ($quantity > 0) {
                    $check_sql = "SELECT available_quantity FROM inventory WHERE id = ?";
                    $stmt = $conn->prepare($check_sql);
                    $stmt->bind_param("i", $equipment_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $available = $result->fetch_assoc();

                    if ($quantity > $available['available_quantity']) {
                        $equipment_available = false;
                        $equipment_errors[] = "Not enough quantity available for equipment ID: $equipment_id";
                    }

                    if (isset($_POST['equipment_price'][$equipment_id])) {
                        $total_cost += $_POST['equipment_price'][$equipment_id] * $quantity;
                    }
                }
            }
        }

        if (!$equipment_available) {
            throw new Exception(implode("\n", $equipment_errors));
        }

        // Add travel fee to total cost
        $total_cost += $travel_fee;

        $insert_booking = "INSERT INTO customer_inquiries (
            customer_name, 
            contact_email, 
            contact_phone, 
            inquiry_date, 
            event_date, 
            event_time, 
            event_package, 
            location_type,
            additional_details, 
            total_cost,
            travel_fee,
            down_payment_status,
            down_payment_amount,
            payment_status,
            status
        ) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'pending', 0.00, 'pending', ?)";

        $stmt = $conn->prepare($insert_booking);
        $stmt->bind_param(
            "ssssssssdds",
            $customer_name,
            $contact_email,
            $contact_phone,
            $event_date,
            $event_time,
            $event_package,
            $location_type,
            $additional_details,
            $total_cost,
            $travel_fee,
            $status
        );
        $stmt->execute();

        $booking_id = $conn->insert_id;

        // Insert equipment items
        if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equipment_id => $quantity) {
                if ($quantity > 0) {
                    $insert_equipment = "INSERT INTO booking_equipment (booking_id, equipment_id, quantity, rental_start, rental_end) 
                                    VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_equipment);
                    $stmt->bind_param("iiiss", $booking_id, $equipment_id, $quantity, $event_date, $event_date);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        sendJsonResponse(['status' => 'success', 'booking_id' => $booking_id]);
    } catch (Exception $e) {
        $conn->rollback();
        sendJsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

$available_equipment = getAvailableEquipment($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Booking - BTONE</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        header {
            background-color: #333;
            color: white;
            padding: 1rem;
            margin-bottom: 20px;
        }

        .company-name {
            margin: 0;
            font-size: 2rem;
        }

        .nav-bar ul {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-bar a {
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .equipment-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .equipment-category {
            margin-bottom: 15px;
        }

        .equipment-item {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
        }

        .equipment-quantity {
            width: 60px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 70%;
            background: white;
            padding: 20px;
            border-radius: 5px;
        }

        #map {
            width: 100%;
            height: 90%;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .total-cost {
            font-weight: bold;
            font-size: 18px;
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .user-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <header>
        <h1 class="company-name">BTONE</h1>
        <nav class="nav-bar">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="user_cart.php">View Cart</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($customer_name); ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <span>(Administrator)</span>
            <?php endif; ?>
        </div>

        <h2>Add Booking</h2>
        <form id="bookingForm" method="POST">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>"
                    readonly>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="contact_email"
                    value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>"
                    required>
            </div>

            <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="contact_phone" required>
            </div>

            <div class="form-group">
                <label>Event Date:</label>
                <input type="date" name="event_date" required>
            </div>

            <div class="form-group">
                <label>Event Package:</label>
                <select name="event_package" id="event_package" required>
                    <option value="Wedding">Wedding</option>
                    <option value="18th Birthday">18th Birthday</option>
                    <option value="Welcome Party">Welcome Party</option>
                    <option value="Portable Bar">Portable Bar</option>
                    <option value="Portable Sound and Lights">Portable Sound and Lights</option>
                    <option value="Catering">Catering</option>
                </select>
            </div>

            <div class="form-group">
                <label>Event Time:</label>
                <input type="time" name="event_time" required>
            </div>

            <div class="form-group">
                <label>Location Type:</label>
                <select id="location_type" name="location_type" required>
                    <option value="On-site">On-site</option>
                    <option value="Custom">Custom</option>
                </select>
            </div>

            <div id="customLocationDiv" class="form-group" style="display: none;">
                <input type="text" id="event_location" name="event_location" placeholder="Select location from map"
                    readonly>
                <button type="button" id="openMapBtn" class="btn btn-secondary">Select Location</button>
            </div>

            <div id="mapModal" class="modal">
                <div class="modal-content">
                    <div id="map"></div>
                    <div style="margin-top: 10px;">
                        <button type="button" id="saveLocation" class="btn btn-primary">Save Location</button>
                        <button type="button" id="closeModal" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </div>

            <div id="travel-fee-section" class="form-group" style="display: none;">
                <label>Travel Fee (₱):</label>
                <input type="number" name="travel_fee" id="travel_fee" value="0" readonly>
            </div>

            <div class="equipment-section">
                <h3>Select Equipment</h3>
                <?php foreach ($available_equipment as $category => $items): ?>
                    <div class="equipment-category">
                        <h4><?php echo htmlspecialchars($category); ?></h4>
                        <?php foreach ($items as $item): ?>
                            <div class="equipment-item">
                                <label>
                                    <input type="checkbox" class="equipment-checkbox" data-id="<?php echo $item['id']; ?>"
                                        data-price="<?php echo $item['unit_price']; ?>">
                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                    (₱<?php echo number_format($item['unit_price'], 2); ?>)
                                </label>
                                <input type="number" name="equipment_quantity[<?php echo $item['id']; ?>]"
                                    class="equipment-quantity" data-id="<?php echo $item['id']; ?>" value="0" min="0"
                                    max="<?php echo $item['available_quantity']; ?>">
                                <input type="hidden" name="equipment_price[<?php echo $item['id']; ?>]"
                                    value="<?php echo $item['unit_price']; ?>">
                                <input type="hidden" name="equipment[<?php echo $item['id']; ?>]" class="equipment-value"
                                    data-id="<?php echo $item['id']; ?>" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label>Additional Details:</label>
                <textarea name="additional_details" rows="4"></textarea>
            </div>

            <div class="total-cost">
                Total Cost: ₱<span id="totalCost">0.00</span>
            </div>

            <div class="button-group">
                <button type="button" id="addToCartBtn" class="btn btn-secondary">Add to Cart</button>
                <button type="submit" id="submitBookingBtn" class="btn btn-primary">Submit Booking</button>
            </div>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const locationTypeSelect = document.getElementById("location_type");
            const customLocationDiv = document.getElementById("customLocationDiv");
            const openMapBtn = document.getElementById("openMapBtn");
            const mapModal = document.getElementById("mapModal");
            const closeModalBtn = document.getElementById("closeModal");
            const saveLocationBtn = document.getElementById("saveLocation");
            const eventLocationInput = document.getElementById("event_location");
            const bookingForm = document.getElementById("bookingForm");
            const addToCartBtn = document.getElementById("addToCartBtn");
            const submitBookingBtn = document.getElementById("submitBookingBtn");

            let map, marker, selectedLocation = "";

            locationTypeSelect.addEventListener("change", function () {
                customLocationDiv.style.display = this.value === "Custom" ? "block" : "none";
                if (this.value !== "Custom") {
                    eventLocationInput.value = "On-site";
                } else {
                    eventLocationInput.value = "";
                }
            });

            openMapBtn.addEventListener("click", function () {
                mapModal.style.display = "block";
                initMap();
            });

            closeModalBtn.addEventListener("click", function () {
                mapModal.style.display = "none";
            });

            saveLocationBtn.addEventListener("click", function () {
                if (selectedLocation) {
                    eventLocationInput.value = selectedLocation;
                    mapModal.style.display = "none";
                } else {
                    alert("Please select a location on the map.");
                }
            });

            addToCartBtn.addEventListener("click", async function (e) {
                e.preventDefault();

                updateTotalCost();

                try {
                    const formData = new FormData(bookingForm);
                    formData.append('action', 'add_to_cart');

                    const response = await fetch(window.location.href, {
                        method: "POST",
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === "success") {
                        if (confirm("Item added to cart! Would you like to view your cart?")) {
                            window.location.href = "user_cart.php";
                        } else {
                            window.location.reload();
                        }
                    } else {
                        throw new Error(result.message || "Unknown error occurred");
                    }
                } catch (error) {
                    console.error("Form submission error:", error);
                    alert(error.message || "An error occurred. Please try again.");
                }
            });

            submitBookingBtn.addEventListener("click", async function (e) {
                e.preventDefault();

                if (!confirm("Are you sure you want to submit this booking?")) {
                    return;
                }

                updateTotalCost();

                try {
                    const formData = new FormData(bookingForm);
                    formData.append('action', 'submit_booking');

                    const response = await fetch(window.location.href, {
                        method: "POST",
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === "success") {
                        // Create form for paying.php
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'paying.php';

                        // Add booking_id
                        const bookingIdInput = document.createElement('input');
                        bookingIdInput.type = 'hidden';
                        bookingIdInput.name = 'booking_id';
                        bookingIdInput.value = result.booking_id;
                        form.appendChild(bookingIdInput);

                        // Add customer_name
                        const customerNameInput = document.createElement('input');
                        customerNameInput.type = 'hidden';
                        customerNameInput.name = 'customer_name';
                        customerNameInput.value = result.customer_name || '<?php echo htmlspecialchars($customer_name); ?>';
                        form.appendChild(customerNameInput);

                        document.body.appendChild(form);
                        form.submit();
                    } else {
                        throw new Error(result.message || "Unknown error occurred");
                    }
                } catch (error) {
                    console.error("Form submission error:", error);
                    alert(error.message || "An error occurred. Please try again.");
                }
            });

            const checkboxes = document.querySelectorAll(".equipment-checkbox");
            const quantityInputs = document.querySelectorAll(".equipment-quantity");
            const equipmentValues = document.querySelectorAll(".equipment-value");
            const totalCostSpan = document.getElementById("totalCost");
            const eventPackageSelect = document.getElementById("event_package");
            const travelFeeSection = document.getElementById("travel-fee-section");
            const travelFeeInput = document.getElementById("travel_fee");

            function updateTotalCost() {
                let equipmentCost = 0;

                quantityInputs.forEach(input => {
                    const equipmentId = input.dataset.id;
                    const checkbox = document.querySelector(`.equipment-checkbox[data-id="${equipmentId}"]`);
                    const hiddenValue = document.querySelector(`.equipment-value[data-id="${equipmentId}"]`);
                    const quantity = parseInt(input.value) || 0;

                    if (quantity > 0) {
                        const price = parseFloat(checkbox.dataset.price);
                        equipmentCost += price * quantity;
                        checkbox.checked = true;
                        hiddenValue.value = quantity;
                    } else {
                        checkbox.checked = false;
                        hiddenValue.value = "0";
                    }
                });

                const travelFee = parseFloat(travelFeeInput.value) || 0;
                const totalCost = equipmentCost + travelFee;
                totalCostSpan.textContent = totalCost.toFixed(2);
            }

            function updatePackageFields() {
                const selectedPackage = eventPackageSelect.value;
                if (selectedPackage === "Portable Bar" || selectedPackage === "Portable Sound and Lights") {
                    travelFeeSection.style.display = "block";
                    travelFeeInput.value = "500";
                } else {
                    travelFeeSection.style.display = "none";
                    travelFeeInput.value = "0";
                }
                updateTotalCost();
            }

            eventPackageSelect.addEventListener("change", updatePackageFields);

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function () {
                    const equipmentId = this.dataset.id;
                    const quantityInput = document.querySelector(`.equipment-quantity[data-id="${equipmentId}"]`);

                    if (this.checked && parseInt(quantityInput.value) === 0) {
                        quantityInput.value = "1";
                    } else if (!this.checked) {
                        quantityInput.value = "0";
                    }

                    updateTotalCost();
                });
            });

            quantityInputs.forEach(input => {
                input.addEventListener("input", function () {
                    const equipmentId = this.dataset.id;
                    const checkbox = document.querySelector(`.equipment-checkbox[data-id="${equipmentId}"]`);
                    const quantity = parseInt(this.value) || 0;

                    checkbox.checked = quantity > 0;
                    updateTotalCost();
                });
            });

            function initMap() {
                if (!map) {
                    map = L.map('map').setView([14.5995, 120.9842], 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    map.on("click", function (e) {
                        if (marker) map.removeLayer(marker);
                        marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
                        selectedLocation = e.latlng.lat + ", " + e.latlng.lng;
                    });
                }
            }

            if (locationTypeSelect.value === "On-site") {
                eventLocationInput.value = "On-site";
            }

            updatePackageFields();
            updateTotalCost();
        });
    </script>
</body>

</html>