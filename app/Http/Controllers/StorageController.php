<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Models\Asset;
use App\Models\Employee;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Stream a file from MinIO storage
     * 
     * @param int $id File ID
     * @return \Illuminate\Http\Response
     */
    public function streamFile($id)
    {
        $file = File::findOrFail($id);

        try {
            $disk = Storage::disk('minio');
            
            if (!$disk->exists($file->file_path)) {
                abort(404, 'File not found');
            }

            $mimeType = $file->mime_type ?: 'application/octet-stream';
            $fileSize = $disk->size($file->file_path);

            // Use streaming for better memory efficiency
            return response()->stream(function() use ($disk, $file) {
                $stream = $disk->readStream($file->file_path);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $file->name . '"',
                'Cache-Control' => 'public, max-age=3600',
                'Content-Length' => $fileSize,
            ]);
        } catch (\Exception $e) {
            \Log::error('File streaming error: ' . $e->getMessage());
            abort(404, 'File not found or cannot be accessed');
        }
    }

    /**
     * Stream an asset image from MinIO storage
     * 
     * @param int $id Asset ID
     * @return \Illuminate\Http\Response
     */
    public function streamAssetImage($id)
    {
        $asset = Asset::findOrFail($id);

        if (!$asset->image) {
            abort(404, 'Asset image not found');
        }

        try {
            $disk = Storage::disk('minio');
            
            if (!$disk->exists($asset->image)) {
                abort(404, 'Image not found in storage');
            }

            // Determine MIME type from file extension
            $extension = pathinfo($asset->image, PATHINFO_EXTENSION);
            $mimeType = $this->getMimeTypeFromExtension($extension);
            $fileSize = $disk->size($asset->image);

            // Use streaming for better memory efficiency
            return response()->stream(function() use ($disk, $asset) {
                $stream = $disk->readStream($asset->image);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($asset->image) . '"',
                'Cache-Control' => 'public, max-age=3600',
                'Content-Length' => $fileSize,
            ]);
        } catch (\Exception $e) {
            \Log::error('Asset image streaming error: ' . $e->getMessage());
            abort(404, 'Image not found or cannot be accessed');
        }
    }

    /**
     * Stream an employee photo from MinIO storage
     * 
     * @param int $id Employee ID
     * @return \Illuminate\Http\Response
     */
    public function streamEmployeePhoto($id)
    {
        $employee = Employee::findOrFail($id);

        // Try to get photo from File relationship
        $photoFile = $employee->photoFile;
        if (!$photoFile) {
            // Check if photo field contains a file ID
            if ($employee->photo && is_numeric($employee->photo)) {
                $photoFile = File::find($employee->photo);
            }
        }

        if (!$photoFile || $photoFile->file_type !== 'photo') {
            abort(404, 'Employee photo not found');
        }

        try {
            $disk = Storage::disk('minio');
            
            if (!$disk->exists($photoFile->file_path)) {
                abort(404, 'Photo not found in storage');
            }

            $mimeType = $photoFile->mime_type ?: 'image/jpeg';
            $fileSize = $disk->size($photoFile->file_path);

            // Use streaming for better memory efficiency
            return response()->stream(function() use ($disk, $photoFile) {
                $stream = $disk->readStream($photoFile->file_path);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $photoFile->name . '"',
                'Cache-Control' => 'public, max-age=3600',
                'Content-Length' => $fileSize,
            ]);
        } catch (\Exception $e) {
            \Log::error('Employee photo streaming error: ' . $e->getMessage());
            abort(404, 'Photo not found or cannot be accessed');
        }
    }

    /**
     * Get MIME type from file extension
     * 
     * @param string $extension
     * @return string
     */
    private function getMimeTypeFromExtension($extension)
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $extension = strtolower($extension);
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

