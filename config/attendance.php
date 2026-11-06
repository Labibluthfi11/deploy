<?php

return [
    'face_detector' => [
        'python' => env('FACE_DETECTOR_PYTHON', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
    ],
];
