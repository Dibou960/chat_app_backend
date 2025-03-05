<?php

namespace App\Functions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use WebSocket\Client;
use Illuminate\Support\Facades\DB;
use App\Jobs\DeleteFileAndRecordJob;

class FileFunctions {
    /**
     * Gère le stockage d'un fichier unique (image ou vidéo).
     */
    public static function handleSingleFile($file, $directory, $customWidth = null) {
        if (!$file) return null;
    
        if (is_string($file)) {
            $file = self::convertBase64ToFile($file);
        }
    
        if ($file instanceof UploadedFile) {
            # 🔥 Reconversion en Illuminate\Http\UploadedFile pour assurer la compatibilité
            $tempPath = $file->getPathname();
            $file = new UploadedFile(
                $tempPath,
                $file->getClientOriginalName(),
                $file->getClientMimeType(),
                $file->getError(),
                true
            );
    
            # Compression si c'est une image
            if (Str::startsWith($file->getMimeType(), 'image')) {
                $file = self::compressImage($file, $customWidth ?? 1024);
            } else {
                $file = self::compressVideo($file);
            }
    
            # 🔥 Laravel UploadedFile → Utilisation de store()
            $path = $file->store($directory, 'public');
            return 'storage/' . $path;
        }
    
        return null;
    }  

    /**
     * Convertit un fichier Base64 en UploadedFile.
     */
    private static function convertBase64ToFile($base64String) {
        $mimeToExt = [
            'data:image/jpeg;base64,' => 'jpeg',
            'data:image/png;base64,' => 'png',
            'data:video/mp4;base64,' => 'mp4'
        ];
    
        foreach ($mimeToExt as $mime => $ext) {
            if (Str::startsWith($base64String, $mime)) {
                $data = base64_decode(Str::replaceFirst($mime, '', $base64String));
                if (!$data) return null;
                $tempPath = tempnam(sys_get_temp_dir(), 'file_') . ".$ext";
                file_put_contents($tempPath, $data);
    
                # 🔥 Conversion en Illuminate\Http\UploadedFile pour utiliser store()
                return new UploadedFile($tempPath, "temp.$ext", mime_content_type($tempPath), null, true);
            }
        }
        return null;
    }

    /**
     * Compresse une image ou une vidéo selon son type.
     */
    private static function compressFile(UploadedFile $file, $maxWidth) {
        return Str::startsWith($file->getMimeType(), 'image')
            ? self::compressImage($file, $maxWidth)
            : self::compressVideo($file);
    }

    /**
     * Compresse une vidéo avec FFmpeg.
     */
    public static function compressVideo(UploadedFile $file): ?UploadedFile {
        $tempPath = $file->getPathname();
        $compressedPath = tempnam(sys_get_temp_dir(), 'compressed_') . '.mp4';

        $command = ['ffmpeg', '-i', $tempPath, '-vcodec', 'libx264', '-crf', '28', '-preset', 'fast', '-b:v', '500k', '-y', $compressedPath];
        $process = new Process($command);
        
        try {
            $process->mustRun();
            return new UploadedFile($compressedPath, $file->getClientOriginalName(), $file->getClientMimeType(), null, true);
        } catch (ProcessFailedException $exception) {
            throw new \RuntimeException('Échec de la compression vidéo: ' . $exception->getMessage());
        }
    }

    /**
     * Compresse une image.
     */
    public static function compressImage(UploadedFile $file, $maxWidth = 1024, $quality = 75) {
        $imagePath = $file->getPathname();
        list($width, $height) = getimagesize($imagePath);
    
        if ($maxWidth && $width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = round($height * ($maxWidth / $width));
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
    
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $image = imagecreatefromstring(file_get_contents($imagePath));
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
        $tempPath = tempnam(sys_get_temp_dir(), 'compressed_img');
        imagejpeg($resizedImage, $tempPath, $quality);
        imagedestroy($image);
        imagedestroy($resizedImage);
    
        return new UploadedFile($tempPath, $file->getClientOriginalName(), $file->getMimeType(), null, true);
    }
    

    /**
     * Supprime un fichier.
     */
    public static function deleteFile($filePath) {
        if (!$filePath) return false;
        $filePath = Str::replaceFirst('storage/', '', $filePath);
        return Storage::disk('public')->exists($filePath) && Storage::disk('public')->delete($filePath);
    }

    /**
     * Envoie des données au serveur WebSocket.
     */
    public static function sendToWebSocket($data) {
        try {
            #dev server web socket ws://localhost:8080
            #prod server web socket wss://ws-serveur-1.onrender.com
            $client = new Client("wss://ws-serveur-1.onrender.com");
            $client->send(json_encode($data));
        } catch (\Exception $e) {
            \Log::error("🚨 Erreur WebSocket : " . $e->getMessage());
        }
    }

    public static function deleteFileAndRecordAfterDelay($model, $recordId, $fileColumn, $delay, $unit = 'minutes') {
        // 🔥 Convertir le délai en minutes
        switch ($unit) {
            case 'hours':
                $delay *= 60;
                break;
            case 'days':
                $delay *= 1440;
                break;
            case 'minutes':
            default:
                break;
        }
    
        // 🔥 Programmer la suppression en utilisant un Job Laravel
        DeleteFileAndRecordJob::dispatch($model, $recordId, $fileColumn)->delay(now()->addMinutes($delay));
    
    }

}
