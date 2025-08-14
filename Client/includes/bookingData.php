<?php
require "../includes/dbh.inc.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Sanitize and validate input
  $formData = getFormData($_POST);

  if (isValidFormData($formData)) {
    try {
      // Insert booking into the database
      if (!isScheduleOccupied($pdo, $formData['btschedule'], $formData['event_duration'])) {
        insertBooking($pdo, $formData);
        header("Location: ../PHP/booking.php ");
        exit;
      } else {
        $message = "The selected schedule is already occupied. Please choose a different time.";
      }
    } catch (PDOException $e) {
      $message = "Error: " . $e->getMessage();
    }
  } else {
    $message = "Please fill in all required fields.";
  }
}

/**
 * Extract and sanitize form data.
 */
function getFormData($postData)
{
  return [
    'btuser_id' => $postData['btuser_id'] ?? '',
    'btaddress' => $postData['btaddress'] ?? '',
    'btevent' => $postData['btevent'] ?? '',
    'btschedule' => isset($postData['btschedule']) ? date('Y-m-d H:i:s', strtotime($postData['btschedule'])) : '',
    'btattendees' => $postData['btattendees'] ?? '',
    'btservices' => isset($postData['btservices']) ? implode(',', $postData['btservices']) : 'test',
    'btmessage' => $postData['btmessage'] ?? '',
    'event_duration' => computeDuration(isset($postData['btschedule']) ? date('Y-m-d H:i:s', strtotime($postData['btschedule'])) : '', $postData['event_duration']) ?? '',
    'status' => 'Pending',
    'payment_status' => 'unpaid'
  ];
}

/**
 * Validate form data.
 */
function isValidFormData($data)
{
  return !empty($data['btuser_id']) &&
    !empty($data['btaddress']) &&
    !empty($data['btevent']) &&
    !empty($data['btschedule']) &&
    !empty($data['btattendees']) &&
    isset($data['btservices']) && $data['btservices'] !== '' &&
    !empty($data['btmessage']) &&
    !empty($data['event_duration']) &&
    !empty($data['status']) &&
    !empty($data['payment_status']);
}

/**
 * Insert booking into the database.
 */
function insertBooking($pdo, $data)
{
  $sql = "INSERT INTO bookings (btuser_id, btaddress, btevent, btschedule, btattendees, 
      btservices, btmessage, EventDuration, status, payment_status) 
      VALUES (:btuser_id, :btaddress, :btevent, :btschedule, :btattendees, 
      :btservices, :btmessage, :event_duration, :status, :payment_status)";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':btuser_id' => $data['btuser_id'],
    ':btaddress' => $data['btaddress'],
    ':btevent' => $data['btevent'],
    ':btschedule' => $data['btschedule'],
    ':btattendees' => $data['btattendees'],
    ':btservices' => $data['btservices'],
    ':btmessage' => $data['btmessage'],
    ':event_duration' => $data['event_duration'],
    ':status' => $data['status'],
    ':payment_status' => $data['payment_status']
  ]);
}

/**
 * Compute event duration.
 */

function computeDuration($start, $durationInHours)
{
  $startDateTime = new DateTime($start);
  $endDateTime = clone $startDateTime;
  $endDateTime->modify("+$durationInHours hours");

  return $endDateTime->format('Y-m-d H:i:s'); // Ensure the duration is formatted as a string
}


function isScheduleOccupied($pdo, $schedule, $duration)
{
  $stmt = $pdo->prepare("SELECT * FROM bookings WHERE btschedule = :btschedule OR 
    (btschedule <= :end_time AND DATE_ADD(btschedule, INTERVAL EventDuration HOUR) >= :start_time)");
  $stmt->bindParam(':btschedule', $schedule);
  $stmt->bindParam(':start_time', $schedule);
  $endTime = date('Y-m-d H:i:s', strtotime($schedule) + ($duration * 3600));
  $stmt->bindParam(':end_time', $endTime);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
