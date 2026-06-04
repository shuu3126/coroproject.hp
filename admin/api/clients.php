<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) { api_error(404, 'Client not found'); }
    api_ok($c);
}

if ($method === 'GET') {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['q'])) {
        $where[] = '(name LIKE ? OR contact_person LIKE ? OR email LIKE ?)';
        $q = '%' . $_GET['q'] . '%';
        array_push($params, $q, $q, $q);
    }
    if (!empty($_GET['rank'])) { $where[] = 'rank = ?'; $params[] = $_GET['rank']; }
    $stmt = $pdo->prepare("SELECT id, name, contact_person, email, category, rank, created_at FROM clients WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($method === 'POST' && !$id) {
    $body = api_input();
    if (empty($body['name'])) { api_error(400, "field 'name' is required"); }
    $new_id = 'client-' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO clients (id, name, contact_person, email, phone, category, rank, memo, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
    $stmt->execute([$new_id, $body['name'], $body['contact_person'] ?? null, $body['email'] ?? null, $body['phone'] ?? null, $body['category'] ?? 'company', $body['rank'] ?? 'new', $body['memo'] ?? null]);
    api_ok(['id' => $new_id], 201);
}

if ($method === 'PATCH' && $id) {
    $body    = api_input();
    $allowed = ['name', 'contact_person', 'email', 'phone', 'category', 'rank', 'memo'];
    $sets = []; $params = [];
    foreach ($allowed as $f) { if (array_key_exists($f, $body)) { $sets[] = "{$f} = ?"; $params[] = $body[$f]; } }
    if (empty($sets)) { api_error(400, 'No updatable fields'); }
    $params[] = $id;
    $pdo->prepare("UPDATE clients SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($params);
    api_ok(['id' => $id]);
}

if ($method === 'DELETE' && $id) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) { api_error(404, 'Client not found'); }
    api_ok(['deleted' => $id]);
}

api_error(405, 'Method not allowed');
