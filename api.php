<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data_file = __DIR__ . '/posts.json';

// Initialize file if it doesn't exist
if (!file_exists($data_file)) {
    file_put_contents($data_file, json_encode([]));
}

function load_posts($file) {
    $content = file_get_contents($file);
    $posts = json_decode($content, true);
    return is_array($posts) ? $posts : [];
}

function save_posts($file, $posts) {
    file_put_contents($file, json_encode($posts, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $posts = load_posts($data_file);
    // Sort by date descending, then by created_at descending
    usort($posts, function($a, $b) {
        $dateCompare = strcmp($b['date'], $a['date']);
        if ($dateCompare !== 0) return $dateCompare;
        return strcmp($b['created_at'], $a['created_at']);
    });
    echo json_encode($posts);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['type']) || empty($input['content']) || empty($input['date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: type, content, date']);
        exit();
    }

    $type = in_array($input['type'], ['daily', 'weekly']) ? $input['type'] : 'daily';
    $posts = load_posts($data_file);

    $new_post = [
        'id' => uniqid('post_', true),
        'type' => $type,
        'title' => isset($input['title']) ? trim($input['title']) : '',
        'content' => trim($input['content']),
        'date' => $input['date'],
        'created_at' => date('Y-m-d H:i:s')
    ];

    array_unshift($posts, $new_post);
    save_posts($data_file, $posts);

    http_response_code(201);
    echo json_encode($new_post);

} elseif ($method === 'DELETE') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing post id']);
        exit();
    }

    $posts = load_posts($data_file);
    $filtered = array_values(array_filter($posts, fn($p) => $p['id'] !== $id));

    if (count($filtered) === count($posts)) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit();
    }

    save_posts($data_file, $filtered);
    echo json_encode(['success' => true]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
