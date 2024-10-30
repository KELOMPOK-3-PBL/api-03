<?php
    function response($status, $message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            "status" => $status,
            "code" => $statusCode,
            "message" => $message,
            "data" => $data,
        ];

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
?>