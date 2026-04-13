<?php
// ============================================================
//  api.php  —  Unified REST API
//  Routes:
//    GET    /api.php?r=posts              → list posts
//    POST   /api.php?r=posts             → create post  [AUTH]
//    DELETE /api.php?r=posts&id=X        → delete post  [AUTH]
//    GET    /api.php?r=events            → list map events
//    POST   /api.php?r=events            → create event [AUTH]
//    DELETE /api.php?r=events&id=X       → delete event [AUTH]
//    GET    /api.php?r=events&search=kw  → search events
//    POST   /api.php?r=logout            → destroy session
// ============================================================

require_once 'db.php';
session_start();
init_db();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$method = $_SERVER['REQUEST_METHOD'];
$route  = $_GET['r'] ?? 'posts';

function require_auth(): void {
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized. Session expired.']);
        exit();
    }
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function body(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ── LOGOUT ────────────────────────────────────────────────────────────
if ($route === 'logout' && $method === 'POST') {
    session_destroy();
    json_out(['success' => true]);
}

// ── POSTS ─────────────────────────────────────────────────────────────
if ($route === 'posts') {
    if ($method === 'GET') {
        $pdo  = get_pdo();
        $stmt = $pdo->query(
            "SELECT id, type, title, content, post_date AS `date`, created_at
             FROM posts ORDER BY post_date DESC, created_at DESC"
        );
        json_out($stmt->fetchAll());
    }
    if ($method === 'POST') {
        require_auth();
        $b = body();
        if (empty($b['content']) || empty($b['date'])) json_out(['error' => 'Missing content or date'], 400);
        $type = in_array($b['type'] ?? '', ['daily','weekly']) ? $b['type'] : 'daily';
        $id   = uniqid('p_', true);
        $pdo  = get_pdo();
        $pdo->prepare("INSERT INTO posts (id, type, title, content, post_date) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id, $type, $b['title'] ?? '', $b['content'], $b['date']]);
        $row = $pdo->prepare("SELECT id, type, title, content, post_date AS `date`, created_at FROM posts WHERE id=?");
        $row->execute([$id]);
        json_out($row->fetch(), 201);
    }
    if ($method === 'DELETE') {
        require_auth();
        $id = $_GET['id'] ?? '';
        if (!$id) json_out(['error' => 'Missing id'], 400);
        $pdo  = get_pdo();
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_out(['error' => 'Not found'], 404);
        json_out(['success' => true]);
    }
}

// ── MAP EVENTS ────────────────────────────────────────────────────────
if ($route === 'events') {
    if ($method === 'GET') {
        $pdo    = get_pdo();
        $search = $_GET['search'] ?? '';
        if ($search !== '') {
            $kw   = '%' . $search . '%';
            $stmt = $pdo->prepare(
                "SELECT id, title, description, lat, lng, event_date AS `date`, created_at
                 FROM map_events WHERE title LIKE ? OR description LIKE ?
                 ORDER BY event_date DESC, created_at DESC"
            );
            $stmt->execute([$kw, $kw]);
        } else {
            $stmt = $pdo->query(
                "SELECT id, title, description, lat, lng, event_date AS `date`, created_at
                 FROM map_events ORDER BY event_date ASC, created_at ASC"
            );
        }
        json_out($stmt->fetchAll());
    }
    if ($method === 'POST') {
        require_auth();
        $b = body();
        if (empty($b['title']) || !isset($b['lat'], $b['lng'], $b['date']))
            json_out(['error' => 'Missing required fields'], 400);
        $pdo = get_pdo();
        $pdo->prepare("INSERT INTO map_events (title, description, lat, lng, event_date) VALUES (?, ?, ?, ?, ?)")
            ->execute([$b['title'], $b['description'] ?? '', (float)$b['lat'], (float)$b['lng'], $b['date']]);
        $newId = $pdo->lastInsertId();
        $row   = $pdo->prepare("SELECT id, title, description, lat, lng, event_date AS `date`, created_at FROM map_events WHERE id=?");
        $row->execute([$newId]);
        json_out($row->fetch(), 201);
    }
    if ($method === 'DELETE') {
        require_auth();
        $id = $_GET['id'] ?? '';
        if (!$id) json_out(['error' => 'Missing id'], 400);
        $pdo  = get_pdo();
        $stmt = $pdo->prepare("DELETE FROM map_events WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_out(['error' => 'Not found'], 404);
        json_out(['success' => true]);
    }
}

http_response_code(404);
echo json_encode(['error' => 'Unknown route']);
