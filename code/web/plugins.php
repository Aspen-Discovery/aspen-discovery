<?php
require_once 'bootstrap.php';
require_once 'bootstrap_aspen.php';

// Parse the URL to get plugin slug and file path
$requestUri = $_SERVER['REQUEST_URI'];
$urlPath = parse_url($requestUri, PHP_URL_PATH);

// Remove /plugins/ prefix to get slug/file path
if (strpos($urlPath, '/plugins/') === 0) {
    $pathInfo = substr($urlPath, 9); // Remove '/plugins/'
    $pathParts = explode('/', $pathInfo, 2);
    
    if (count($pathParts) >= 2) {
        $slug = $pathParts[0];
        $filePath = $pathParts[1];
        
        // Validate slug (alphanumeric, underscores, hyphens only)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            http_response_code(400);
            die('Invalid plugin slug');
        }
        
        // Prevent directory traversal
        if (strpos($filePath, '..') !== false || strpos($filePath, '/') === 0) {
            http_response_code(400);
            die('Invalid file path');
        }
        
        // Build the full file path
        global $serverName;
        $pluginDataPath = "/data/aspen-discovery/$serverName/plugins";
        $fullFilePath = "$pluginDataPath/$slug/$filePath";
        
        // Check if file exists and is readable
        if (is_file($fullFilePath) && is_readable($fullFilePath)) {
            // Determine MIME type based on file extension
            $extension = strtolower(pathinfo($fullFilePath, PATHINFO_EXTENSION));
            $mimeType = 'text/plain'; // default
            
            switch ($extension) {
                case 'js':
                    $mimeType = 'application/javascript';
                    break;
                case 'css':
                    $mimeType = 'text/css';
                    break;
                case 'png':
                    $mimeType = 'image/png';
                    break;
                case 'jpg':
                case 'jpeg':
                    $mimeType = 'image/jpeg';
                    break;
                case 'gif':
                    $mimeType = 'image/gif';
                    break;
                case 'svg':
                    $mimeType = 'image/svg+xml';
                    break;
                case 'ico':
                    $mimeType = 'image/x-icon';
                    break;
                case 'html':
                    $mimeType = 'text/html';
                    break;
                case 'json':
                    $mimeType = 'application/json';
                    break;
            }
            
            // Set appropriate headers
            header("Content-Type: $mimeType");
            header('Content-Length: ' . filesize($fullFilePath));
            
            // Add caching headers for static assets
            if (in_array($extension, ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'])) {
                header('Cache-Control: public, max-age=86400'); // 1 day
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
            }
            
            // Output the file
            readfile($fullFilePath);
            exit;
        } else {
            http_response_code(404);
            die('Plugin file not found');
        }
    } else {
        http_response_code(400);
        die('Invalid plugin URL format');
    }
} else {
    http_response_code(400);
    die('Invalid plugin URL');
} 