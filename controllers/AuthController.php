<?php
session_start();
require_once 'database.php'; // Include your database connection file

class AuthController
{
    public function login($email, $password)
    {
        // Validate input
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        }

        if (empty($password)) {
            return json_encode(['status' => 'error', 'message' => 'Password is required.']);
        }

        // Check user in the database
        global $conn; // Use your database connection
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                return json_encode(['status' => 'success', 'message' => 'Login successful.']);
            } else {
                return json_encode(['status' => 'error', 'message' => 'Incorrect password.']);
            }
        } else {
            return json_encode(['status' => 'error', 'message' => 'User not found.']);
        }
    }

    public function logout()
    {
        // Clear session
        session_destroy();
        return json_encode(['status' => 'success', 'message' => 'Logout successful.']);
    }

    // public function forgotPassword($email)
    // {
    //     // Validate email
    //     if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //         return json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    //     }

    //     // Check if email exists in the database
    //     global $conn;
    //     $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    //     $stmt->bind_param("s", $email);
    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     if ($result->num_rows > 0) {
    //         // Here you can implement sending a reset password email
    //         return json_encode(['status' => 'success', 'message' => 'Reset password link sent.']);
    //     } else {
    //         return json_encode(['status' => 'error', 'message' => 'Email not found.']);
    //     }
    // }

    // public function changePassword($userId, $oldPassword, $newPassword)
    // {
    //     // Validate input
    //     if (empty($oldPassword) || empty($newPassword)) {
    //         return json_encode(['status' => 'error', 'message' => 'Both old and new passwords are required.']);
    //     }

    //     global $conn;
    //     $stmt = $conn->prepare("SELECT * FROM user WHERE user_id = ?");
    //     $stmt->bind_param("i", $userId);
    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     if ($result->num_rows > 0) {
    //         $user = $result->fetch_assoc();
    //         // Verify old password
    //         if (password_verify($oldPassword, $user['password'])) {
    //             // Update with new password
    //             $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    //             $updateStmt = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
    //             $updateStmt->bind_param("si", $hashedPassword, $userId);
    //             $updateStmt->execute();

    //             return json_encode(['status' => 'success', 'message' => 'Password changed successfully.']);
    //         } else {
    //             return json_encode(['status' => 'error', 'message' => 'Old password is incorrect.']);
    //         }
    //     } else {
    //         return json_encode(['status' => 'error', 'message' => 'User not found.']);
    //     }
    // }
}
