<?php
if ($_SERVER['REQUEST_URI'] === '/') {
    header('Location: /web');
    exit;
}

require_once 'config.php';
require_once 'PasteHandler.php';

header('Content-Type: application/json');
$handler = new PasteHandler();

// Basic rate limiting
session_start();
if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = [];
}

// Clean old requests
$_SESSION['requests'] = array_filter($_SESSION['requests'], function($time) {
    return $time > time() - RATE_LIMIT_WINDOW;
});

if (count($_SESSION['requests']) >= RATE_LIMIT_MAX) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded']));
}

$_SESSION['requests'][] = time();

// Route handling
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
// 后面基本就是 把 0 当作 uid，1 当作当前操作了。

switch ($method) {
    case 'POST':
        if (strpos($path, '/api/create') !== false) {
            $content = $_POST['content'];
            $options = [
                'file_extension' => $_POST['file_extension'] ?? '',
                'expires_at' => $_POST['expires_at'] ?? null,
                'max_views' => $_POST['max_views'] ?? 0,
            ];
            
            $result = $handler->create($content, $options);
            header('Location: /' . $result['uuid']);
            exit;
        }

        $content = file_get_contents('php://input');
        if (empty($content)) {
            http_response_code(400);
            die(json_encode(['error' => 'No content provided']));
        }

        $options = [
            'expires_at' => $_POST['expires_at'] ?? null,
            'max_views' => $_POST['max_views'] ?? 0,
            'is_encrypted' => $_POST['is_encrypted'] ?? 0,
            'is_markdown' => $_POST['is_markdown'] ?? 0
        ];
        
        $result = $handler->create($content, $options);
        http_response_code($result['status']);
        echo json_encode(['url' => $_SERVER['HTTP_HOST'] . '/' . $result['uuid']]);
        break;

    case 'GET':
        if (isset($pathParts[0])) {
            $pathInfo = pathinfo($pathParts[0]);
            $uuid = $pathInfo['filename'];
            $extension = $pathInfo['extension'] ?? '';
            
            $note = $handler->read($uuid);
            
            if ($note === null) {
                http_response_code(404);
                die(json_encode(['error' => 'Note not found']));
            }

            // Handle raw file requests
            if (in_array($extension, ['txt', 'md'])) {
                header('Content-Type: text/plain');
                echo $note['content'];
                exit;
            }

            // Handle edit page request
            if (isset($pathParts[1]) && $pathParts[1] === 'edit') {
                header('Content-Type: text/html; charset=utf-8');
                ?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Paste</title>
    <link rel="stylesheet" href="/web/style.css">
</head>
<body>
    <main>
        <form action="/<?php echo htmlspecialchars($uuid); ?>/update" method="post">
            <textarea name="content"><?php echo htmlspecialchars($note['content']); ?></textarea>
            <div class="controls">
                <select name="file_extension">
                    <option value="">Plain text</option>
                    <!-- Add your file extension options here -->
                </select>
                <button type="submit">Update Paste</button>
            </div>
        </form>
    </main>
</body>
</html>
                <?php
                exit;
            }

            // Handle HTML rendering
            if (empty($extension)) {
                header('Content-Type: text/html; charset=utf-8');
                $lines = explode("\n", htmlspecialchars($note['content']));
                ?>
<!DOCTYPE html>
<html>
<head>
    <title>Paste <?php echo htmlspecialchars($uuid); ?></title>
    <style>
        body {
            font-family: monospace;
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }
        pre {
            background: white;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            overflow-x: auto;
        }
        .line-numbers {
            color: #999;
            padding-right: 15px;
            border-right: 1px solid #ddd;
            margin-right: 15px;
            -webkit-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body>
    <pre><code><?php
    foreach ($lines as $index => $line) {
        echo '<span class="line-numbers">' . str_pad($index + 1, 3, ' ', STR_PAD_LEFT) . '</span>' . $line . "\n";
    }
    ?></code></pre>
    <div class="controls">
        <a href="/<?php echo htmlspecialchars($uuid); ?>/edit" class="button">Edit</a>
    </div>
</body>
</html>
                <?php
                exit;
            }

            // Default to plain text for API requests
            header('Content-Type: text/plain');
            echo $note['content'];
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No paste ID provided']);
        }
        break;

    case 'PUT':
        if (isset($pathParts[0])) {
            $content = file_get_contents('php://input');
            if ($handler->update($pathParts[0], $content)) {
                echo json_encode(['message' => 'Updated successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Paste not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No paste ID provided']);
        }
        break;

    case 'POST':
        if (preg_match('#^/([a-zA-Z0-9]+)/update$#', $path, $matches)) {
            $uuid = $matches[1];
            $content = $_POST['content'];
            $options = ['file_extension' => $_POST['file_extension'] ?? ''];
            
            if ($handler->update($uuid, $content, $options)) {
                header('Location: /' . $uuid);
                exit;
            }
            
            http_response_code(404);
            die(json_encode(['error' => 'Paste not found']));
        }
        break;

    case 'DELETE':
        if (isset($pathParts[0])) {
            if ($handler->delete($pathParts[0])) {
                echo json_encode(['message' => 'Deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Paste not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No paste ID provided']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
