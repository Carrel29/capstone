<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";
include_once "../includes/allData.php";
require_once "calendar-utils.php"; 
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Current timestamp and user info
$current_utc = '2025-05-11 19:30:37';
$user_login = 'Carrel29';


$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

$bookings = getBookingsForMonth($pdo, $year, str_pad($month,2,"0",STR_PAD_LEFT));
$firstDay = date('N', strtotime("$year-$month-01"));
$daysInMonth = date('t', strtotime("$year-$month-01"));

// Get user data
$data = new AllData($pdo);
$getUserById = $data->getUserById($user_id);
$allBookingAndUser = $data->getBookingAndUserById($user_id);
$services = $data->getAllServices();

// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'btonedatabase';
$port = '3308';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

$available_equipment = getAvailableEquipment($conn);

// Update form processing section
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $formData = [
        'btuser_id' => $_SESSION['bt_user_id'],
        'btaddress' => $_POST['btaddress'] ?? '',
        'btevent' => $_POST['btevent'] ?? '',
        'btschedule' => isset($_POST['btschedule']) ? date('Y-m-d H:i:s', strtotime($_POST['btschedule'])) : '',
        'btattendees' => $_POST['btattendees'] ?? 0,
        'additional_headcount' => $_POST['additional_headcount'] ?? 0,
        'btservices' => isset($_POST['btservices']) ? implode(',', $_POST['btservices']) : '',
        'btmessage' => $_POST['btmessage'] ?? '',
        'event_duration' => computeDuration(isset($_POST['btschedule']) ? date('Y-m-d H:i:s', strtotime($_POST['btschedule'])) : '', $_POST['event_duration'] ?? 4),
        'status' => 'Pending',
        'payment_status' => 'unpaid'
    ];

    if (isValidFormData($formData)) {
        try {
            if (!isScheduleOccupied($pdo, $formData['btschedule'], $formData['event_duration'])) {
                $totalCost = calculateTotalCost($formData);
                $formData['total_cost'] = $totalCost;
                
                $sql = "INSERT INTO bookings (btuser_id, btaddress, btevent, btschedule, btattendees, 
                        btservices, btmessage, EventDuration, total_cost, additional_headcount, 
                        status, payment_status, created_at) 
                        VALUES (:btuser_id, :btaddress, :btevent, :btschedule, :btattendees, 
                        :btservices, :btmessage, :event_duration, :total_cost, :additional_headcount, 
                        :status, :payment_status, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':btuser_id' => $formData['btuser_id'],
                    ':btaddress' => $formData['btaddress'],
                    ':btevent' => $formData['btevent'],
                    ':btschedule' => $formData['btschedule'],
                    ':btattendees' => $formData['btattendees'],
                    ':btservices' => $formData['btservices'],
                    ':btmessage' => $formData['btmessage'],
                    ':event_duration' => $formData['event_duration'],
                    ':total_cost' => $totalCost,
                    ':additional_headcount' => $formData['additional_headcount'],
                    ':status' => $formData['status'],
                    ':payment_status' => $formData['payment_status']
                ]);

                header("Location: user_cart.php");
                exit();
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Add cost calculation function
function calculateTotalCost($data) {
    $baseCost = 0;
    $headcountCost = 50; // Cost per additional head
    $additionalHeads = max(0, $data['additional_headcount']);

    if ($data['btevent'] === 'Wedding') {
        $baseCost = 1000; // Base bundle cost
    } else {
        $baseCost = 300; // Default base cost
    }

    return $baseCost + ($additionalHeads * $headcountCost);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../CSS/style.css" />
    <script src="../JS/payment.js"></script>
    <script src="../JS/services.js"></script>
    <title>Booking Form</title>
    <script>
        function updateForm() {
            const eventType = document.getElementById('btevent').value;
            const bundleDiv = document.querySelector('.wedding-bundle');
            bundleDiv.style.display = eventType === 'Wedding' ? 'block' : 'none';
        }
    </script>
</head>

<body>
    <header>
        <h1 class="company-name">BTONE</h1>
        <nav class="nav-bar">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#aboutus">About Us</a></li>
                <li class="dropdown">
                    <a href="#"><img src="../Img/menu.png" alt="" class="img-round"></a>
                    <ul class="dropdown-content">
                        <li><a href="#"><?php echo $fullname ?></a></li>
                        <li><a href="../includes/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>

    <main class="h-100 gap-20">
        <div>
            <h1 class="text-center">Booking Form</h1>
        </div>
        <div class="card w-100">
            <div class="combo-display-flex-column-start w-100 mx-20">
                <form id="bookingForm" action="../includes/bookingData.php" method="POST" class="w-100">
                    <!-- System Information -->
                    <div>
                        <label>Current Date and Time (UTC):</label>
                        <input type="text" value="<?php echo $current_utc; ?>" readonly>
                    </div>

                    <div>
                        <label>Current User:</label>
                        <input type="text" value="<?php echo $user_login; ?>" readonly>
                    </div>

                    <!-- User Information -->
                    <input type="hidden" name="btuser_id" value="<?php echo htmlspecialchars($user_id); ?>">

                    <div>
                        <label for="name">Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($fullname); ?>" readonly>
                    </div>

                    <div>
                        <label for="address">Address</label>
                        <input type="text" name="btaddress" required>
                    </div>

                    <div>
                        <label for="contact">Contact No.</label>
                        <input type="text" value="<?php echo $getUserById['bt_phone_number']; ?>" readonly>
                    </div>

                    <div>
                        <label for="email">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                    <!-- Event Details -->
                    <div>
                        <label for="event">Event</label>
                        <select name="btevent" class="form-control" id="btevent" required onchange="updateForm()">
                            <?php
                            $events = [
                                "Weddings",
                                "18 Birthday",
                                "Silver and Golden",
                                "60th Birthday & Anniversary",
                                "Children Party",
                                "Birthday Party",
                                "Christmas & Yearend Party",
                                "Exhibits",
                                "Seminar",
                                "Js Prom",
                                "Graduation Ball",
                                "Graduation"
                            ];

                            foreach ($events as $event) {
                                echo '<option value="' . htmlspecialchars($event) . '">' . htmlspecialchars($event) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                   <div style="max-width:400px;margin:auto;">
    <h3 style="text-align:center;">
        <a href="?year=<?=$month==1?$year-1:$year;?>&month=<?=$month==1?12:$month-1;?>">&#8592;</a>
        <?=date('F Y', strtotime("$year-$month-01"));?>
        <a href="?year=<?=$month==12?$year+1:$year;?>&month=<?=$month==12?1:$month+1;?>">&#8594;</a>
    </h3>
    <table style="width:100%;border-collapse:collapse;text-align:center;">
        <tr>
            <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
        </tr>
        <tr>
            <?php
            for ($blank=1; $blank<$firstDay; $blank++) echo "<td></td>";
            for ($day=1, $cell=$firstDay; $day<=$daysInMonth; $day++, $cell++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $color = isset($bookings[$dateStr]) ? getDayColor($bookings[$dateStr]) : 'green';
                echo "<td style='background:$color;color:#fff;padding:8px;border:1px solid #ccc;'>$day</td>";
                if ($cell%7==0) echo "</tr><tr>";
            }
            ?>
        </tr>
    </table>
    <div style="margin-top:10px;">
        <span style="background:green;color:white;padding:2px 8px;border-radius:4px;">Available</span>
        <span style="background:yellow;color:black;padding:2px 8px;border-radius:4px;">Partially</span>
        <span style="background:red;color:white;padding:2px 8px;border-radius:4px;">Fully</span>
    </div>
</div>

                    <div>
                        <label for="event duration">Event Duration</label>
                        <select name="event_duration" id="event_duration" class="form-control" required>
                            <option value="12">Half Day (12 hours)</option>
                            <option value="24">Whole Day (24 hours)</option>
                        </select>
                    </div>

                    <div>
                        <label for="Attendees">No. Attendees</label>
                        <input type="number" name="btattendees" required>
                    </div>

                    <div class="additional-headcount" style="display:none;">
                        <label for="additional_headcount">Additional Headcount</label>
                        <input type="number" name="additional_headcount" value="0" min="0">
                    </div>

                    <!-- Location Section -->
                    <div>
                        <label>Location Type:</label>
                        <select id="location_type" name="location_type" class="form-control" required>
                            <option value="On-site">On-site</option>
                            <option value="Custom">Custom Address</option>
                        </select>
                    </div>

                    <div id="customAddressDiv" style="display: none;">
                        <label>Custom Address:</label>
                        <textarea name="custom_address" class="form-control" rows="3"
                            placeholder="Enter complete address"></textarea>
                    </div>

                    <div id="travel-fee-section" style="display: none;">
                        <label>Travel Fee (₱):</label>
                        <input type="number" name="travel_fee" id="travel_fee" value="0" class="form-control" readonly>
                    </div>

                    <!-- Equipment Section -->
                    <?php if (!empty($available_equipment)): ?>
                        <div>
                            <label>Equipment</label>
                            <div class="checkbox-group">
                                <?php foreach ($available_equipment as $category => $items): ?>
                                    <div class="checkbox-category">
                                        <h4><?php echo htmlspecialchars($category); ?></h4>
                                        <?php foreach ($items as $item): ?>
                                            <div class="checkbox-item">
                                                <input type="checkbox" id="equipment-<?php echo $item['id']; ?>"
                                                    class="equipment-checkbox" data-id="<?php echo $item['id']; ?>"
                                                    data-price="<?php echo $item['unit_price']; ?>">
                                                <label for="equipment-<?php echo $item['id']; ?>">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                    (₱<?php echo number_format($item['unit_price'], 2); ?>)
                                                </label>
                                                <input type="number" name="equipment[<?php echo $item['id']; ?>]"
                                                    class="equipment-quantity" value="0" min="0"
                                                    max="<?php echo $item['available_quantity']; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Services Section -->
                    <label for="services">Services</label>
                    <div class="checkbox-group">
                        <?php foreach ($services as $service): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="service-<?php echo htmlspecialchars($service['services_id']); ?>"
                                    name="btservices[]" value="<?php echo htmlspecialchars($service['name']); ?>">
                                <label for="service-<?php echo htmlspecialchars($service['services_id']); ?>">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Message -->
                    <label for="Message">Message</label>
                    <input type="text" name="btmessage" required>

                    <!-- Total Cost -->
                    <div class="total-cost">
                        Total Cost: ₱<span id="totalCost">0.00</span>
                    </div>

                    <!-- Submit Button -->
                    <div class="btn-group-card gap-20">
                        <input class="btn btn-view-now" type="submit" value="Submit">
                    </div>

                    <!-- Wedding Bundle Section -->
                    <div class="wedding-bundle" style="display:none;">
                        <h3>Wedding Bundle</h3>
                        <p>Includes: Basic Sound System, Lights, and Projector</p>
                        <p>Base Cost: ₱1000</p>
                        <label>Additional Headcount (₱50/head):</label>
                        <input type="number" name="additional_headcount" value="0" min="0">
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Location Type Handling
            const locationTypeSelect = document.getElementById("location_type");
            const customAddressDiv = document.getElementById("customAddressDiv");
            const travelFeeSection = document.getElementById("travel-fee-section");

            locationTypeSelect.addEventListener("change", function () {
                customAddressDiv.style.display = this.value === "Custom" ? "block" : "none";
                updateTravelFee();
            });

            // Equipment Selection Handling
            const checkboxes = document.querySelectorAll(".equipment-checkbox");
            const quantityInputs = document.querySelectorAll(".equipment-quantity");
            const totalCostSpan = document.getElementById("totalCost");

            function updateTotalCost() {
                let total = 0;

                // Calculate equipment costs
                quantityInputs.forEach(input => {
                    const equipmentId = input.dataset.id;
                    const checkbox = document.querySelector(`.equipment-checkbox[data-id="${equipmentId}"]`);
                    const quantity = parseInt(input.value) || 0;

                    if (quantity > 0) {
                        const price = parseFloat(checkbox.dataset.price);
                        total += price * quantity;
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                    }
                });

                // Add travel fee if applicable
                if (locationTypeSelect.value === "Custom") {
                    total += 500; // Default travel fee
                }

                totalCostSpan.textContent = total.toFixed(2);
            }

            function updateTravelFee() {
                if (locationTypeSelect.value === "Custom") {
                    travelFeeSection.style.display = "block";
                    document.getElementById("travel_fee").value = "500";
                } else {
                    travelFeeSection.style.display = "none";
                    document.getElementById("travel_fee").value = "0";
                }
                updateTotalCost();
            }

            // Equipment Selection Event Listeners
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function () {
                    const equipmentId = this.dataset.id;
                    const quantityInput = document.querySelector(`.equipment-quantity[name="equipment[${equipmentId}]"]`);

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
                    const checkbox = document.querySelector(`#equipment-${equipmentId}`);
                    const quantity = parseInt(this.value) || 0;
                    checkbox.checked = quantity > 0;
                    updateTotalCost();
                });
            });

            // Initialize
            updateTravelFee();
            updateTotalCost();
        });
    </script>
</body>

</html>