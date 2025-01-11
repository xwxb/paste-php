<?php
// 如果访问根路径且请求方式为GET，重定向到 /web
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/') {
    header('Location: /web');
    exit;
}

require_once 'config.php';
require_once 'PasteHandler.php';

header('Content-Type: application/json');
$handler = new PasteHandler();

// 中文注释：这里先做基础限流
session_start();
if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = [];
}

// Clean old requests
$_SESSION['requests'] = array_filter($_SESSION['requests'], function ($time) {
    return $time > time() - RATE_LIMIT_WINDOW;
});

if (count($_SESSION['requests']) >= RATE_LIMIT_MAX) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded']));
}

$_SESSION['requests'][] = time();

// Route handling
// ~~所有请求都会进入这里~~,php 起的只有第一层路径才会默认走这里，根据请求方法和路径进行不同的处理
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
// part01 把 0 当作 uid，1 当作当前操作了；rest设计2部分仅做前端展示

switch ($method) {
    case 'POST':
        // 根 url post 用来创建新的 paste
        if (empty($pathParts[0])) {
            // $content = file_get_contents('php://input'); // 从原始的 POST 数据流中读取内容
            $content = $_POST['content']; // 从解析后的 POST 数据中读取 'content' 字段
            if (empty($content)) {
                http_response_code(400);
                die(json_encode(value: ['error' => 'No content provided']));
            }

            $options = [
                'expires_at' => $_POST['expires_at'] ?? null,
                'max_views' => $_POST['max_views'] ?? 0,
                'is_encrypted' => $_POST['is_encrypted'] ?? 0,
                'is_markdown' => $_POST['is_markdown'] ?? 0,
                'file_extension' => $_POST['file_extension'] ?? ''
            ];

            $result = $handler->create($content, $options);
            http_response_code(302);
            header('Location: /' . $result['uuid']);
            exit;
        }
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
                require 'templates/edit.php';
                exit;
            }

            // Handle HTML rendering
            if (empty($extension)) {
                header('Content-Type: text/html; charset=utf-8');
                $lines = explode("\n", htmlspecialchars($note['content']));
                require 'templates/view.php';
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
        // echo 1; die;  // 这里确实要留意，不是地址栏返回的，这里不会展示。
        if (isset($pathParts[0])) {
            $content = $_PUT['content']; // 从解析后的 PUT 数据中读取 'content' 字段
            
            // 目前只能手动读，put可能还是不常用
            // 获取原始输入
            $putData = file_get_contents('php://input');

            // 解析 multipart/form-data
            $boundary = substr($_SERVER['CONTENT_TYPE'], strpos($_SERVER['CONTENT_TYPE'], 'boundary=') + 9);
            $parts = array_slice(explode('--' . $boundary, $putData), 1, -1);

            $data = [];
            foreach ($parts as $part) {
                if (preg_match('/name="([^"]+)".*?(?:\r\n|\r|\n){2}(.*)/s', $part, $matches)) {
                    $data[$matches[1]] = trim($matches[2]);
                }
            }

            $content = $data['content'];
            error_log("content: " . ($content ?? 'empty'));

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
