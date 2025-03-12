<?php
namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FileUploadTrait
{
    /**
     * Upload a single file.
     *
     * @param UploadedFile $file The file to upload.
     * @param string $folder The destination folder.
     * @return string|null The file path or null if the upload fails.
     */
    public function uploadFile(UploadedFile $file, string $folder): ?string
    {
        return $file->store($folder, 'public');
    }

    /**
     * Upload multiple files.
     *
     * @param array $files Array of UploadedFile instances.
     * @param string $folder The destination folder.
     * @return array An array of file paths.
     */
    public function uploadMultipleFiles(array $files, string $folder): array
    {
        $filePaths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filePaths[] = $file->store($folder, 'public');
            }
        }

        return $filePaths;
    }

    /**
     * Delete a file.
     *
     * @param string $filePath The file path to delete.
     * @return bool True if deleted, false otherwise.
     */
    public function deleteFile(string $filePath): bool
    {
        return Storage::disk('public')->delete($filePath);
    }
}
