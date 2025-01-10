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
            // Enable error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            // Fix path concatenation
            $apiDoc = dirname(__DIR__) . '/docs/api.txt';
            
            // Debug log
            error_log("Looking for file at: " . $apiDoc);
            
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
