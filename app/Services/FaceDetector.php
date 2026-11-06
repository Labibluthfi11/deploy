<?php

namespace App\Services;

use App\Exceptions\FaceDetectionUnavailableException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class FaceDetector
{
    public function containsFace(UploadedFile $photo): bool
    {
        $script = base_path('scripts/face_detector.py');
        if (! is_file($script)) {
            $this->unavailable('Face detection script is missing.');
        }

        $process = new Process([
            config('attendance.face_detector.python'),
            $script,
            $photo->getPathname(),
        ], base_path());
        $process->setTimeout(10);

        try {
            $process->mustRun();
        } catch (ExceptionInterface|ProcessTimedOutException $exception) {
            $this->unavailable('Face detection process failed.', $exception);
        }

        $result = json_decode($process->getOutput(), true);
        if (! is_array($result) || ! is_bool($result['face_detected'] ?? null)) {
            $this->unavailable('Face detection returned an invalid response.');
        }

        return $result['face_detected'];
    }

    private function unavailable(string $message, ?\Throwable $exception = null): never
    {
        Log::error($message, [
            'exception' => $exception ? get_class($exception) : null,
            'reason' => $exception?->getMessage(),
        ]);

        throw new FaceDetectionUnavailableException(
            'Face detection is temporarily unavailable.',
            previous: $exception
        );
    }
}
