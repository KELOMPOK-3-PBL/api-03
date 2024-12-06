<?php
class FileUploadHelper {
    private $uploadDir;
    private $publicDir;

    public function __construct($uploadDir = null, $publicDir = '/pbl/api-03/images/') {
        // Gunakan path default atau sesuai struktur proyek Anda
        $this->uploadDir = realpath($uploadDir ?? __DIR__ . '/../images') . '/';
        $this->publicDir = $publicDir; // Simpan direktori publik relatif
    }

    public function uploadFile($file, $type = 'poster', $oldFilePath = null) {
        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($file['tmp_name']);
    
        if (!in_array($fileType, $allowedTypes)) {
            response('error', 'Only JPG, JPEG, and PNG files are allowed.', null, 400);
            return null;
        }
    
        // Set direktori target berdasarkan tipe file
        $targetDir = $this->uploadDir . ($type === 'avatar' ? 'avatar/' : 'poster/');
    
        // Pastikan direktori target ada
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
    
        // Jika ada path file lama, hapus file lama
        if ($oldFilePath && file_exists($this->uploadDir . $oldFilePath)) {
            if (unlink($this->uploadDir . $oldFilePath)) {
                error_log("Old file deleted: " . $oldFilePath);
            } else {
                error_log("Failed to delete old file: " . $oldFilePath);
            }
        }

        // Buat nama file berdasarkan tanggal dan waktu
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = date('Ymd_His') . '.' . $extension;
        $filePath = $targetDir . $fileName;
    
        // Periksa jika file dengan nama yang sama sudah ada
        if (file_exists($filePath)) {
            $fileName = date('Ymd_His') . '_' . uniqid() . '.' . $extension;
            $filePath = $targetDir . $fileName;
        }
    
        // Pindahkan file yang diupload ke direktori target
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Kembalikan path relatif untuk database (folder publik)
            return $this->publicDir . "{$type}/" . $fileName;
        } else {
            error_log("File upload failed: could not move file.");
            response('error', 'Failed to upload file.', null, 500);
            return null;
        }
    }

    public function deleteFile($filePath) {
        $basePath = realpath(__DIR__ . '/../images');
        if ($basePath === false) {
            return [
                'status' => 'error',
                'message' => 'Base path not found.'
            ];
        }
        
        // Sesuaikan path untuk direktori baru
        $normalizedPath = str_replace($this->publicDir, '', $filePath);
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
    
        if (file_exists($fullPath)) {
            if (is_writable($fullPath)) {
                if (unlink($fullPath)) {
                    return [
                        'status' => 'success',
                        'message' => 'File deleted successfully.'
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to delete the file.'
                    ];
                }
            } else {
                return [
                    'status' => 'error',
                    'message' => 'File is not writable.'
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => 'File not found.'
            ];
        }
    }     
}
?>
