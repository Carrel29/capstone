<?php
require_once "../includes/dbh.inc.php";

class AllData
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllBookingAndUser()
    {
        $stmt = $this->pdo->prepare("SELECT  `btuser.bt_user_id`, `btuser.bt_first_name btuser.bt_last_name` AS fullname,`btuser.bt_email`,`btuser.bt_phone_number`,`bookings.id AS booking_id`,`bookings.btaddress`,`bookings.btevent`,`bookings.btschedule`,`bookings.btattendees`,`bookings.btservices`,`bookings.btmessage`
                                    FROM bookings
                                    INNER JOIN btuser ON bookings.btuser_id = btuser.bt_user_id; ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookingAndUserById($userId)
    {
        $stmt = $this->pdo->prepare("
                SELECT  
                    btuser.bt_user_id,
                    CONCAT(btuser.bt_first_name, ' ', btuser.bt_last_name) AS fullname,
                    btuser.bt_email,
                    btuser.bt_phone_number,
                    bookings.id AS booking_id,
                    bookings.btaddress,
                    bookings.btevent,
                    bookings.btschedule,
                    bookings.btattendees,
                    bookings.btservices,
                    bookings.btmessage
                FROM bookings
                INNER JOIN btuser ON bookings.btuser_id = btuser.bt_user_id 
                WHERE btuser.bt_user_id = :user_id
                ORDER BY bookings.id DESC
                LIMIT 1
            ");

        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllUser()
    {
        $stmt = $this->pdo->prepare("SELECT `bt_user_id`, `bt_first_name`, `bt_last_name`, `bt_email`, `bt_phone_number`, `bt_is_active`, `bt_privilege_id` FROM `btuser`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById($userId)
    {
        $stmt = $this->pdo->prepare("SELECT `bt_user_id`, `bt_first_name`, `bt_last_name`, `bt_email`, `bt_phone_number`, `bt_is_active`, `bt_privilege_id` FROM `btuser` WHERE bt_user_id = :btuser_id");
        $stmt->bindParam(':btuser_id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isAvailableSchedule($schedule)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE btschedule = :btschedule");
        $stmt->bindParam(':btschedule', $schedule);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllServices(){
        $stmt = $this->pdo->prepare("SELECT `services_id`, `name`, `price` FROM `service`");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function computeTotalPrices($services){
        //all event is 25000
        $totalPrice = 25000;

        $servicesArray = explode(',', $services);
        foreach ($servicesArray as $service) {
            $stmt = $this->pdo->prepare("SELECT price FROM service WHERE name = :name");
            $stmt->bindParam(':name', $service);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $totalPrice += $result['price'];
            }
        }
        return $totalPrice;
    }

    public function getTotalBooking(){
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM bookings");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getTotalSales(){
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM sales where status  = 2");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }


    public function getAllUserAndSales(){
        $stmt = $this->pdo->prepare("
            SELECT 
            sales.sales_id AS sales_id,
            sales.btuser_id,
            sales.booking_id,
            sales.GcashReferenceNo AS refNo,
            sales.TotalAmount AS TotalAmount,
            sales.AmountPaid AS AmountPaid, 
            sales.Status AS Status,
            sales.DateCreated,
            sales.DateUpdate,
            sales.userUpdated_Id AS updatedBy,
            CONCAT(btuser.bt_first_name, ' ', btuser.bt_last_name) AS fullname,
            btuser.bt_email as email,
            btuser.bt_phone_number,
            btuser.bt_is_active,
            btuser.bt_privilege_id
            FROM sales
            INNER JOIN btuser ON sales.btuser_id = btuser.bt_user_id
            WHERE sales.Status = 1
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUserAndSalesCompleted(){
        $stmt = $this->pdo->prepare("
            SELECT 
            sales.sales_id AS sales_id,
            sales.btuser_id,
            sales.booking_id,
            sales.GcashReferenceNo AS refNo,
            sales.TotalAmount AS TotalAmount,
            sales.AmountPaid AS AmountPaid, 
            sales.Status AS Status,
            sales.DateCreated,
            sales.DateUpdate,
            sales.userUpdated_Id,
            CONCAT(btuser.bt_first_name, ' ', btuser.bt_last_name) AS fullname,
            btuser.bt_email as email,
            btuser.bt_phone_number,
            btuser.bt_is_active,
            btuser.bt_privilege_id
            FROM sales
            INNER JOIN btuser ON sales.btuser_id = btuser.bt_user_id
            WHERE sales.Status = 2
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function updateSaleStatusAndAmountPaid($salesId, $status, $refNo, $userUpdatedId, $amountPaid)
    {
        $stmt = $this->pdo->prepare("UPDATE sales SET Status = :status, AmountPaid = AmountPaid + :amountPaid, userUpdated_Id = :userUpdatedId, DateUpdate = NOW() WHERE sales_id = :sales_id AND GcashReferenceNo = :refNo");
        $stmt->bindParam(':sales_id', $salesId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':refNo', $refNo);
        $stmt->bindParam(':userUpdatedId', $userUpdatedId);
        $stmt->bindParam(':amountPaid', $amountPaid);
        return $stmt->execute();
    }
}
