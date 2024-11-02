<?php
class FileUploadHelper {
    private $uploadDir;

    public function __construct($uploadDir = null) {
        // Use provided path or default to ../../images as per your project's folder structure
        $this->uploadDir = realpath($uploadDir ?? __DIR__ . '/../../images') . '/';
    }

    public function uploadFile($file, $type = 'poster') {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($file['tmp_name']);
    
        if (!in_array($fileType, $allowedTypes)) {
            response('error', 'Only JPG, JPEG, and PNG files are allowed.', null, 400);
            return null;
        }
    
        // Set target directory based on file type
        $targetDir = $this->uploadDir . ($type === 'avatar' ? 'avatar/' : 'poster/');
    
        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
    
        // Generate file name based on date and time
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = date('Ymd_His') . '.' . $extension;
        $filePath = $targetDir . $fileName;
    
        // Check if a file with the same name already exists
        if (file_exists($filePath)) {
            $fileName = date('Ymd_His') . '_' . uniqid() . '.' . $extension;
            $filePath = $targetDir . $fileName;
        }
    
        // Move the uploaded file to the target directory
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return "/pbl/images/{$type}/" . $fileName; // Adjusted the return path
        } else {
            error_log("File upload failed: could not move file."); // Log the error for debugging
            response('error', 'Failed to upload file.', null, 500);
            return null; // Return null if the upload fails
        }
    }
    
}
?>
