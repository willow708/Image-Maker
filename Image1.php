<?php
// Replace with your actual Hugging Face API key
$apiToken = 'YOUR_HUGGING_FACE_API_KEY';
$apiUrl = "https://api-inference.huggingface.co/models/CompVis/stable-diffusion-v1-4";

// Get user input from command line arguments
$prompt = $argv[1] ?? '';

if (empty($prompt)) {
    echo "Please provide a text prompt as an argument.\n";
    exit(1);
}

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiToken",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["inputs" => $prompt]));

// Execute the request
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    echo "Failed to generate image. HTTP Status Code: $httpCode\n";
    if ($httpCode == 401) {
        echo "Unauthorized: Please check your API token.\n";
    }
    exit(1);
}

// Save the image to a file
$inputImagePath = "generated_image.png";
file_put_contents($inputImagePath, $response);

// Resize the image to match a 15-inch screen resolution (e.g., 1920x1080)
function resizeImage($inputImagePath, $outputImagePath, $newWidth, $newHeight) {
    list($originalWidth, $originalHeight) = getimagesize($inputImagePath);
    $sourceImage = imagecreatefromstring(file_get_contents($inputImagePath));
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Maintain aspect ratio if needed
    $ratio = min($newWidth / $originalWidth, $newHeight / $originalHeight);
    $newWidthMaintainRatio = $originalWidth * $ratio;
    $newHeightMaintainRatio = $originalHeight * $ratio;
    $xOffset = ($newWidth - $newWidthMaintainRatio) / 2;
    $yOffset = ($newHeight - $newHeightMaintainRatio) / 2;

    imagecopyresampled($resizedImage, $sourceImage, $xOffset, $yOffset, 0, 0, $newWidthMaintainRatio, $newHeightMaintainRatio, $originalWidth, $originalHeight);
    
    imagepng($resizedImage, $outputImagePath);
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
}

$newWidth = 1920; // New width (Full HD resolution)
$newHeight = 1080; // New height (Full HD resolution)
$outputImagePath = "resized_image.png";
resizeImage($inputImagePath, $outputImagePath, $newWidth, $newHeight);

echo "Original image generated and saved as $inputImagePath\n";
echo "Resized image saved as $outputImagePath\n";
?>
