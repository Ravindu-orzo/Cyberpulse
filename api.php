<?php
// ============================================================
//  api.php  —  Unified REST API
//  Routes:
//    GET/POST/DELETE  ?r=posts
//    GET/POST/DELETE  ?r=events
//    GET/POST/DELETE  ?r=event_groups
//    GET/POST/DELETE  ?r=writeups
//    GET/POST/DELETE  ?r=resources
//    POST             ?r=upload_image
//    POST             ?r=logout
// ============================================================

require_once 'db.php';
session_start();

// ── LOGOUT (destroys session for both normal and view-only) ──
if ($route === 'logout' && $method === 'POST') {
    session_destroy();
    json_out(['success' => true]);
}

init_db();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$method = $_SERVER['REQUEST_METHOD'];
$route  = $_GET['r'] ?? 'posts';

// View-only session check
$viewOnly = !empty($_SESSION['view_only']);

function require_auth(): void {
    global $viewOnly;
    if ($viewOnly) {
        http_response_code(403);
        echo json_encode(['error' => 'View-only mode. No modifications allowed.']);
        exit();
    }
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

// ── PUBLIC PROFILE SESSION ────────────────────────────────────────────
if ($route === 'view_profile' && $method === 'POST') {
    session_start();
    $_SESSION['view_only'] = true;
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

// ── EVENT GROUPS ──────────────────────────────────────────────────────
if ($route === 'event_groups') {
    if ($method === 'GET') {
        $pdo  = get_pdo();
        $stmt = $pdo->query("SELECT * FROM event_groups ORDER BY created_at DESC");
        json_out($stmt->fetchAll());
    }
    if ($method === 'POST') {
        require_auth();
        $b = body();
        if (empty($b['name'])) json_out(['error' => 'Missing name'], 400);
        $validTags = ['live','investigation','concluded','monitoring','archived'];
        $tag = in_array($b['tag'] ?? '', $validTags) ? $b['tag'] : 'investigation';
        $pdo = get_pdo();
        $pdo->prepare("INSERT INTO event_groups (name, tag, description) VALUES (?, ?, ?)")
            ->execute([$b['name'], $tag, $b['description'] ?? '']);
        $newId = $pdo->lastInsertId();
        $row   = $pdo->prepare("SELECT * FROM event_groups WHERE id=?");
        $row->execute([$newId]);
        json_out($row->fetch(), 201);
    }
    if ($method === 'DELETE') {
        require_auth();
        $id = $_GET['id'] ?? '';
        if (!$id) json_out(['error' => 'Missing id'], 400);
        $pdo = get_pdo();
        // Also delete all pins in this group
        $pdo->prepare("DELETE FROM map_events WHERE event_group = ?")->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM event_groups WHERE id = ?");
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
        $group  = $_GET['group']  ?? '';
        if ($search !== '') {
            $kw   = '%' . $search . '%';
            $stmt = $pdo->prepare(
                "SELECT id, event_group, title, description, lat, lng, event_date AS `date`, event_time AS `time`, created_at
                 FROM map_events WHERE title LIKE ? OR description LIKE ?
                 ORDER BY event_date ASC, event_time ASC, created_at ASC"
            );
            $stmt->execute([$kw, $kw]);
        } elseif ($group !== '') {
            $stmt = $pdo->prepare(
                "SELECT id, event_group, title, description, lat, lng, event_date AS `date`, event_time AS `time`, created_at
                 FROM map_events WHERE event_group = ?
                 ORDER BY event_date ASC, event_time ASC, created_at ASC"
            );
            $stmt->execute([$group]);
        } else {
            $stmt = $pdo->query(
                "SELECT id, event_group, title, description, lat, lng, event_date AS `date`, event_time AS `time`, created_at
                 FROM map_events ORDER BY event_date ASC, event_time ASC, created_at ASC"
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
        $pdo->prepare("INSERT INTO map_events (event_group, title, description, lat, lng, event_date, event_time) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $b['event_group'] ?? 'default',
                $b['title'],
                $b['description'] ?? '',
                (float)$b['lat'],
                (float)$b['lng'],
                $b['date'],
                $b['time'] ?? null
            ]);
        $newId = $pdo->lastInsertId();
        $row   = $pdo->prepare("SELECT id, event_group, title, description, lat, lng, event_date AS `date`, event_time AS `time`, created_at FROM map_events WHERE id=?");
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

// ── WRITEUPS ──────────────────────────────────────────────────────────
if ($route === 'writeups') {
    if ($method === 'GET') {
        $pdo = get_pdo();
        $cat = $_GET['category'] ?? '';
        $id  = $_GET['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM writeups WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) json_out(['error' => 'Not found'], 404);
            json_out($row);
        } elseif ($cat) {
            $stmt = $pdo->prepare("SELECT id, category, title, platform_tag, difficulty, created_at, updated_at FROM writeups WHERE category=? ORDER BY created_at DESC");
            $stmt->execute([$cat]);
            json_out($stmt->fetchAll());
        } else {
            $stmt = $pdo->query("SELECT id, category, title, platform_tag, difficulty, created_at, updated_at FROM writeups ORDER BY created_at DESC");
            json_out($stmt->fetchAll());
        }
    }
    if ($method === 'POST') {
        require_auth();
        $b = body();
        if (empty($b['title']) || empty($b['category'])) json_out(['error' => 'Missing title or category'], 400);
        $pdo = get_pdo();
        if (!empty($b['id'])) {
            // Update existing
            $pdo->prepare("UPDATE writeups SET title=?, content=?, platform_tag=?, difficulty=?, category=? WHERE id=?")
                ->execute([$b['title'], $b['content'] ?? '', $b['platform_tag'] ?? '', $b['difficulty'] ?? 'medium', $b['category'], $b['id']]);
            $stmt = $pdo->prepare("SELECT * FROM writeups WHERE id=?");
            $stmt->execute([$b['id']]);
            json_out($stmt->fetch());
        } else {
            $pdo->prepare("INSERT INTO writeups (category, title, content, platform_tag, difficulty) VALUES (?, ?, ?, ?, ?)")
                ->execute([$b['category'], $b['title'], $b['content'] ?? '', $b['platform_tag'] ?? '', $b['difficulty'] ?? 'medium']);
            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM writeups WHERE id=?");
            $stmt->execute([$newId]);
            json_out($stmt->fetch(), 201);
        }
    }
    if ($method === 'DELETE') {
        require_auth();
        $id = $_GET['id'] ?? '';
        if (!$id) json_out(['error' => 'Missing id'], 400);
        $pdo  = get_pdo();
        $stmt = $pdo->prepare("DELETE FROM writeups WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_out(['error' => 'Not found'], 404);
        json_out(['success' => true]);
    }
}

// ── RESOURCES ─────────────────────────────────────────────────────────
if ($route === 'resources') {
    if ($method === 'GET') {
        $pdo = get_pdo();
        $cat = $_GET['category'] ?? '';
        if ($cat) {
            $stmt = $pdo->prepare("SELECT * FROM writeup_resources WHERE category=? ORDER BY created_at DESC");
            $stmt->execute([$cat]);
        } else {
            $stmt = $pdo->query("SELECT * FROM writeup_resources ORDER BY created_at DESC");
        }
        json_out($stmt->fetchAll());
    }
    if ($method === 'POST') {
        require_auth();
        $b = body();
        if (empty($b['title']) || empty($b['url']) || empty($b['category'])) json_out(['error' => 'Missing fields'], 400);
        $pdo = get_pdo();
        $pdo->prepare("INSERT INTO writeup_resources (category, title, url, description) VALUES (?, ?, ?, ?)")
            ->execute([$b['category'], $b['title'], $b['url'], $b['description'] ?? '']);
        $newId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM writeup_resources WHERE id=?");
        $stmt->execute([$newId]);
        json_out($stmt->fetch(), 201);
    }
    if ($method === 'DELETE') {
        require_auth();
        $id = $_GET['id'] ?? '';
        if (!$id) json_out(['error' => 'Missing id'], 400);
        $pdo  = get_pdo();
        $stmt = $pdo->prepare("DELETE FROM writeup_resources WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) json_out(['error' => 'Not found'], 404);
        json_out(['success' => true]);
    }
}

// ── IMAGE UPLOAD ──────────────────────────────────────────────────────
if ($route === 'upload_image' && $method === 'POST') {
    require_auth();
    if (empty($_FILES['image'])) json_out(['error' => 'No file'], 400);

    $file    = $_FILES['image'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) json_out(['error' => 'Invalid file type'], 400);
    if ($file['size'] > 5 * 1024 * 1024) json_out(['error' => 'File too large (max 5MB)'], 400);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $ext;
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) json_out(['error' => 'Upload failed'], 500);

    $pdo = get_pdo();
    $pdo->prepare("INSERT INTO writeup_images (filename, original) VALUES (?, ?)")
        ->execute([$filename, $file['name']]);

    json_out(['url' => 'uploads/' . $filename, 'filename' => $filename]);
}

http_response_code(404);
echo json_encode(['error' => 'Unknown route']);
