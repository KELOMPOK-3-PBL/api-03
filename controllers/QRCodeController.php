<?php
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

    public function __construct($db) {
        $this->conn = $db;
    }

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
    // Get registered users and their attendance status
    public function getRegisteredUsers($event_id) {
        header("Content-Type: application/json; charset=UTF-8");

        $query = "SELECT user.username, event_registration.qr_code, event_registration.attendance 
                  FROM event_registration 
                  INNER JOIN user ON event_registration.user_id = user.user_id 
                  WHERE event_registration.event_id = :event_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        $registered_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $registered_users
        ], JSON_PRETTY_PRINT);
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
}
?>
