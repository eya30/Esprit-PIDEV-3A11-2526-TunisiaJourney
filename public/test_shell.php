<?php
$script   = escapeshellarg(__DIR__ . '/../face_api/embed.py');
$imgData  = base64_encode(file_get_contents(__DIR__ . '/test.jpg'));
$input    = json_encode(['image_base64' => $imgData]);

$tmpInput = tempnam(sys_get_temp_dir(), 'face_') . '.json';
file_put_contents($tmpInput, $input);

$command = "py -3.11 $script < " . escapeshellarg($tmpInput) . " 2>&1";
$output  = shell_exec($command);
unlink($tmpInput);

echo '<pre>' . htmlspecialchars(substr($output, 0, 500)) . '...</pre>';