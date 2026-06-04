<?php
require __DIR__ . '/_bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = api_path_id();

// GET /api/clients/{id}
if ($method === 'GET' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) { api_error(404, 'Client not found'); }
    api_ok($client);
}

// POST /api/clients（新規作成）
if ($method === 'POST' && !$id) {
    $body = api_input();
    if (empty($body['name'])) { api_error(400, "field 'name' is required"); }
    $stmt = $pdo->prepare("
        INSERT INTO clients (name, contact_person, email, phone, category, rank, memo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $body['name'],
        $body['contact_person'] ?? null,
        $body['email'] ?? null,
        $body['phone'] ?? null,
        $body['category'] ?? 'company',
        $body['rank'] ?? 'new',
        $body['memo'] ?? null,
    ]);
    api_ok(['id' => (int)$pdo->lastInsertId()], 201);
}

// GET /api/clients?q=検索ワード
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];
    if (!empty($_GET['q'])) {
        $where[] = '(name LIKE ? OR contact_person LIKE ? OR email LIKE ?)';
        $q = '%' . $_GET['q'] . '%';
        $params = array_merge($params, [$q, $q, $q]);
    }
    if (!empty($_GET['rank'])) {
        $where[] = 'rank = ?';
        $params[] = $_GET['rank'];
    }
    $stmt = $pdo->prepare("
        SELECT id, name, contact_person, email, category, rank, created_at
        FROM clients
        WHERE " . implode(' AND ', $where) . "
        ORDER BY id DESC
    ");
    $stmt->execute($params);
    api_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

api_error(405, 'Method not allowed');
