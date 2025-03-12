<?php
namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadHelper
{
    /**
     * Upload single or multiple files and return file paths.
     *
     * @param UploadedFile|array|null $files The file(s) to upload.
     * @param string $folder The folder to store the files in.
     * @param string $disk The storage disk to use (default: 'public').
     * @return string|array|null The file path(s) or null if no file is uploaded.
     */
    public static function upload($files, string $folder, string $disk = 'public')
    {
        if (!$files) {
            return null;
        }

        $folderPath = "uploads/{$folder}/" . date('Y') . '/' . date('m'); // Organized by year & month

        // If a single file is uploaded, process it
        if ($files instanceof UploadedFile) {
            return self::storeFile($files, $folderPath, $disk);
        }

        // Handle multiple file uploads
        $savedPaths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $savedPaths[] = self::storeFile($file, $folderPath, $disk);
            }
        }

        return $savedPaths ?: null;
    }

    /**
     * Store a single file and return the file path.
     *
     * @param UploadedFile $file The file to store.
     * @param string $folderPath The folder path where the file should be stored.
     * @param string $disk The storage disk.
     * @return string The stored file path.
     */
    private static function storeFile(UploadedFile $file, string $folderPath, string $disk): string
    {
        $filename = Str::random(15) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folderPath, $filename, $disk);
    }
}
