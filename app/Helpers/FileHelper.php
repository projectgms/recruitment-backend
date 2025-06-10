<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileHelper
{
    
      public static function deleteFile($path)
    {
         $disk = env('FILESYSTEM_DISK');

        if (!$path || !Storage::disk($disk)->exists($path)) {
            return false;
        }

        return Storage::disk($disk)->delete($path);
    }
      public static function storeFile(Request $request, $fieldName, $directory )
    {
           $disk = env('FILESYSTEM_DISK');
            if ($fieldName instanceof \Illuminate\Http\UploadedFile)
             {
                $file = $fieldName;
            } else {
                $file = $request->file($fieldName);
            }

            if (!$file || !($file instanceof \Illuminate\Http\UploadedFile)) {
                return null;
            }
           if (!Storage::disk($disk)->exists($directory)) 
           {
              Storage::disk($disk)->makeDirectory($directory);
           }
        $extension = $file->getClientOriginalExtension();
        $filename =  time() . '_' . uniqid() . '.' .$extension;

        return $file->storeAs($directory, $filename, $disk);
    }
      public static function getFileUrl($path)
    {
         $disk = env('FILESYSTEM_DISK');
        if (!$path) return null;

         switch ($disk) {
        case 's3':
        case 'gcs': // Google Cloud Storage
            return Storage::disk($disk)->url($path);

        case 'public':
        case 'local':
        default:
            return env('APP_URL') . Storage::url('app/public/' . $path);
    }
    }
}