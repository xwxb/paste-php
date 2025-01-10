<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect logic
$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri, PHP_URL_PATH);
if (preg_match('#^/web/.+#', $parsed_url)) {
    // 这里可以进行你需要的操作，例如重定向
    header("Location: /web", true, 301);
    exit();
}

// Fix path concatenation
$apiDoc = dirname(__DIR__) . '/docs/api.txt';

// Debug log
error_log("Looking for file at: " . $apiDoc);

?>


<!DOCTYPE html>
<html>
<head>
    <title>Paste</title>
    <link rel="stylesheet" href="/web/style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <main>
        <div class="editor-section">
            <form action="/api/create" method="post" enctype="multipart/form-data">
                <textarea name="content" rows="20" placeholder="Paste your content here..."></textarea>
                <div class="controls">
                    <select name="file_extension">
                        <option value="">Plain text</option>
                        <option value="php">PHP</option>
                        <option value="js">JavaScript</option>
                        <option value="py">Python</option>
                        <option value="md">Markdown</option>
                    </select>
                    <button type="submit">Create Paste</button>
                </div>
            </form>
        </div>
        
        <div class="docs-section">
            <?php 
            if (file_exists($apiDoc)) {
                echo '<pre>' . htmlspecialchars(file_get_contents($apiDoc)) . '</pre>';
            } else {
                echo "<!-- File not found at: $apiDoc -->";
            }
            ?>
        </div>
    </main>
</body>
</html>
