<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\File;
use App\Models\Asset;
use App\Models\Employee;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Stream a file from MinIO storage
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $file File ID or File model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function streamFile(Request $request, $file)
    {
        // Get file from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $fileParam = $routeParams['file'] ?? $file;

        // If it's already a File model instance, use it; otherwise find by ID
        if ($fileParam instanceof File) {
            $file = $fileParam;
        } else {
            $file = File::findOrFail((int)$fileParam);
        }

        // Require authentication (session or token)
        if (!auth()->check()) {
            abort(401, 'Unauthenticated');
        }

        // Verify workspace access
        if ($file->workspace_id && auth()->user()->workspace_id !== $file->workspace_id) {
            abort(403, 'Unauthorized access to file');
        }

        try {
            $workspace = $file->workspace;
            if (!$workspace) {
                abort(404, 'Workspace not found for file');
            }
            $disk = $workspace->getStorageDisk();

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
            Log::error('File streaming error: ' . $e->getMessage());
            abort(404, 'File not found or cannot be accessed');
        }
    }

    /**
     * Stream an asset image from MinIO storage
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $asset Asset ID or Asset model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function streamAssetImage(Request $request, $asset)
    {
        // Get asset from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $assetParam = $routeParams['asset'] ?? $asset;

        // If it's already an Asset model instance, use it; otherwise find by ID
        if ($assetParam instanceof Asset) {
            $asset = $assetParam;
        } else {
            $asset = Asset::findOrFail((int)$assetParam);
        }

        // Require authentication (session or token)
        if (!auth()->check()) {
            abort(401, 'Unauthenticated');
        }

        // Verify workspace access
        if ($asset->workspace_id && auth()->user()->workspace_id !== $asset->workspace_id) {
            abort(403, 'Unauthorized access to asset image');
        }

        if (!$asset->image) {
            abort(404, 'Asset image not found');
        }

        try {
            $workspace = $asset->workspace;
            if (!$workspace) {
                abort(404, 'Workspace not found for asset');
            }
            $disk = $workspace->getStorageDisk();

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
            Log::error('Asset image streaming error: ' . $e->getMessage());
            abort(404, 'Image not found or cannot be accessed');
        }
    }

    /**
     * Stream an employee photo from MinIO storage
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $employee Employee ID or Employee model (from route model binding)
     * @return \Illuminate\Http\Response
     */
    public function streamEmployeePhoto(Request $request, $employee)
    {
        // Get employee from route parameters - Laravel might pass model or ID
        $routeParams = $request->route()->parameters();
        $employeeParam = $routeParams['employee'] ?? $employee;

        // If it's already an Employee model instance, use it; otherwise find by ID
        if ($employeeParam instanceof Employee) {
            $employee = $employeeParam;
        } else {
            $employee = Employee::findOrFail((int)$employeeParam);
        }

        // Require authentication (session or token)
        if (!auth()->check()) {
            abort(401, 'Unauthenticated');
        }

        // Verify workspace access
        if ($employee->workspace_id && auth()->user()->workspace_id !== $employee->workspace_id) {
            abort(403, 'Unauthorized access to employee photo');
        }

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
            $workspace = $employee->workspace;
            if (!$workspace) {
                abort(404, 'Workspace not found for employee');
            }
            $disk = $workspace->getStorageDisk();

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
            Log::error('Employee photo streaming error: ' . $e->getMessage());
            abort(404, 'Photo not found or cannot be accessed');
        }
    }

    /**
     * Stream a workspace logo from MinIO storage
     *
     * @param \Illuminate\Http\Request $request
     * @param int $workspace_id Workspace ID
     * @return \Illuminate\Http\Response
     */
    public function streamWorkspaceLogo(Request $request, $workspace_id)
    {
        // Get workspace_id from route parameters - Laravel might be passing the wrong parameter
        // The route is /{workspace}/storage/workspaces/{workspace_id}/logo
        // So we need to get 'workspace_id' from route parameters, not the method parameter
        $routeParams = $request->route()->parameters();
        $actualWorkspaceId = $routeParams['workspace_id'] ?? $workspace_id;

        // Find workspace by ID (should be numeric)
        $workspace = \App\Models\Workspace::findOrFail((int)$actualWorkspaceId);

        // Require authentication (session or token)
        if (!auth()->check()) {
            abort(401, 'Unauthenticated');
        }

        // Verify workspace belongs to current user's workspace (security check)
        if (auth()->user()->workspace_id !== $workspace->id) {
            abort(403, 'Unauthorized access to workspace logo');
        }

        if (!$workspace->logo) {
            abort(404, 'Workspace logo not found');
        }

        try {
            // Workspace logos are in the default MinIO bucket, so use 'minio' disk
            $disk = Storage::disk('minio');

            // Normalize the logo path (remove leading/trailing slashes, ensure correct format)
            $logoPath = trim($workspace->logo, '/');

            // Try to get file size directly (this will fail if file doesn't exist)
            // This is more reliable than exists() check for S3/MinIO
            try {
                $fileSize = $disk->size($logoPath);
            } catch (\Exception $e) {
                // Try to list files in the directory to debug
                $debugInfo = [
                    'workspace_id' => $workspace->id,
                    'logo_path' => $logoPath,
                    'bucket' => config('filesystems.disks.minio.bucket'),
                    'error' => $e->getMessage(),
                ];

                try {
                    $directory = dirname($logoPath);
                    $files = $disk->files($directory);
                    $debugInfo['directory'] = $directory;
                    $debugInfo['files_in_directory'] = $files;
                } catch (\Exception $e2) {
                    $debugInfo['list_error'] = $e2->getMessage();
                }

                Log::error('Workspace logo file not found in MinIO', $debugInfo);
                abort(404, 'Logo not found in storage');
            }

            // Determine MIME type from file extension
            $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
            $mimeType = $this->getMimeTypeFromExtension($extension);

            // Use streaming for better memory efficiency
            return response()->stream(function() use ($disk, $logoPath) {
                $stream = $disk->readStream($logoPath);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($logoPath) . '"',
                'Cache-Control' => 'public, max-age=3600',
                'Content-Length' => $fileSize,
            ]);
        } catch (\Exception $e) {
            Log::error('Workspace logo streaming error: ' . $e->getMessage());
            abort(404, 'Logo not found or cannot be accessed');
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

