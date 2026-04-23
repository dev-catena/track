<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FirmwareController extends Controller
{
    /**
     * Download público do firmware (ESP32 precisa acessar sem auth).
     * GET /firmware/download/{filename}
     */
    public function download(string $filename)
    {
        $filename = basename($filename);
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.bin$/', $filename)) {
            abort(404);
        }

        $path = config('firmware.storage_path', storage_path('app/firmware'));
        $filePath = $path . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath)->withHeaders([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
