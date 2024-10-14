<?php
<<<<<<< HEAD
// Include Composer's autoload file
require_once '../vendor/autoload.php'; // Ensure the path is correct
require_once '../config/database.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeController {
    private $conn;
    private $table_name = "event_registration"; // Assuming event_registration table exists
=======
require_once '../config/database.php';

class EventRegistrationController {
    private $conn;
    private $table_name = "event_registration";
>>>>>>> 22089e1 (fix cors on login routes)

    public function __construct($db) {
        $this->conn = $db;
    }

<<<<<<< HEAD
    // Generate QR code for event registration
    public function registerUserForEvent($event_id, $user_id) {
        header("Content-Type: application/json; charset=UTF-8");

        // Check event quota
        $event_query = "SELECT COUNT(*) as count FROM event_registration WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($event_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $registered_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $quota_query = "SELECT quota FROM event WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($quota_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $event_quota = $stmt->fetch(PDO::FETCH_ASSOC)['quota'];

        if ($registered_count < $event_quota) {
            // Create event-specific QR code directory
            $qr_code_directory = '../qrcodes/event_' . $event_id;
            if (!is_dir($qr_code_directory)) {
                mkdir($qr_code_directory, 0755, true); // Create directory if it does not exist
            }

            // Generate QR code
            $qr_data = $event_id . "_" . $user_id;
            $qr_code_path = $qr_code_directory . '/' . $qr_data . '.png'; // Save in the event-specific folder

            try {
                // Generate the QR code
                $result = Builder::create()
                    ->data($qr_data)
                    ->encoding(new Encoding('UTF-8'))
                    ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                    ->size(300)
                    ->margin(10)
                    ->writer(new PngWriter())
                    ->build();

                // Save QR code to file
                $result->saveToFile($qr_code_path);

                // Register user for event
                $insert_query = "INSERT INTO event_registration (event_id, user_id, qr_code) VALUES (:event_id, :user_id, :qr_code)";
                $stmt = $this->conn->prepare($insert_query);
                $stmt->bindParam(':event_id', $event_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':qr_code', $qr_code_path);

                if ($stmt->execute()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'User registered successfully.',
                        'qr_code' => $qr_code_path
                    ], JSON_PRETTY_PRINT);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to register user.'], JSON_PRETTY_PRINT);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to generate QR code: ' . $e->getMessage()], JSON_PRETTY_PRINT);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Event quota reached.'], JSON_PRETTY_PRINT);
        }
    }

    // Get user QR code only for members
    public function getUserQRCode($event_id, $user_id, $role) {
        header("Content-Type: application/json; charset=UTF-8");

        // Check if user role is Member
        if ($role !== 'Member') {
            echo json_encode(['status' => 'error', 'message' => 'Only Member users can access QR code.'], JSON_PRETTY_PRINT);
            return;
        }

        $query = "SELECT user.username, event_registration.qr_code, event_registration.attendance 
                  FROM event_registration 
                  INNER JOIN user ON event_registration.user_id = user.user_id 
                  WHERE event_registration.event_id = :event_id AND event_registration.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $registered_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($registered_user) {
            echo json_encode([
                'status' => 'success',
                'data' => $registered_user
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found for this event.'], JSON_PRETTY_PRINT);
        }
    }

    // Mark user as attended
    public function markAttendance($qr_code) {
        header("Content-Type: application/json; charset=UTF-8");

        $query = "UPDATE event_registration SET attendance = 1 WHERE qr_code = :qr_code";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':qr_code', $qr_code);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Attendance marked.'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to mark attendance.'], JSON_PRETTY_PRINT);
        }
    }

    // Calculate the number of participants who have registered and attended an event
    public function getEventAttendance($event_id, $propose_user_id) {
        header("Content-Type: application/json; charset=UTF-8");

        // Validate if the user is the proposer of the event
        $validate_query = "SELECT propose_user_id FROM event WHERE event_id = :event_id AND propose_user_id = :propose_user_id";
        $stmt = $this->conn->prepare($validate_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':propose_user_id', $propose_user_id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'You are not authorized to access this event attendance.'], JSON_PRETTY_PRINT);
            return;
        }

        // Count the total registrations for the event
        $count_query = "SELECT COUNT(*) as total_registered FROM event_registration WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($count_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $total_registered = $stmt->fetch(PDO::FETCH_ASSOC)['total_registered'];

        // Count the total attendance for the event
        $attendance_query = "SELECT COUNT(*) as total_attended FROM event_registration WHERE event_id = :event_id AND attendance = 1";
        $stmt = $this->conn->prepare($attendance_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $total_attended = $stmt->fetch(PDO::FETCH_ASSOC)['total_attended'];

        // Get event quota
        $quota_query = "SELECT quota FROM event WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($quota_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $event_quota = $stmt->fetch(PDO::FETCH_ASSOC)['quota'];

        // Prepare response
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_registered' => (int)$total_registered,
                'total_attended' => (int)$total_attended,
                'quota' => (int)$event_quota,
            ]
        ], JSON_PRETTY_PRINT);
    }
}
?>
=======
    // User registers for an event
    public function registerUserForEvent($event_id, $user_id) {
        header("Content-Type: application/json; charset=UTF-8");

        // Check if the user is already registered
        $check_query = "SELECT * FROM event_registration WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($check_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'User already registered.'], JSON_PRETTY_PRINT);
            return;
        }

        // Register user for the event
        $insert_query = "INSERT INTO event_registration (event_id, user_id) VALUES (:event_id, :user_id)";
        $stmt = $this->conn->prepare($insert_query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'User registered successfully.'], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to register user.'], JSON_PRETTY_PRINT);
        }
    }
}
>>>>>>> 22089e1 (fix cors on login routes)
