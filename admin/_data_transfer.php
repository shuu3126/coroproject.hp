<?php

function admin_data_transfer_tables() {
    return [
        'news' => 'お知らせ',
        'talents' => 'タレント',
        'talent_links' => 'タレントリンク',
        'talent_platforms' => 'タレントプラットフォーム',
        'clients' => '顧客',
        'biz_ext_talents' => '所属外VTuber',
        'biz_deals' => 'Business案件',
        'biz_deal_candidates' => 'Business候補者',
        'cre_creators' => 'クリエイター',
        'cre_projects' => 'Creative案件',
        'accounting_talent_settings' => 'タレント会計設定',
        'accounting_revenues' => '収益',
        'accounting_invoices' => '請求書',
        'accounting_invoice_items' => '請求書明細',
        'accounting_invoiced_months' => '請求済み月',
        'accounting_receipts' => '領収書',
        'accounting_journal_categories' => '記帳カテゴリ',
        'accounting_journal_entries' => '記帳',
        'inquiries' => 'お問い合わせ',
        'inquiry_replies' => 'お問い合わせ返信',
        'mail_contacts' => 'メール宛先帳',
        'mail_messages' => 'メール',
        'settings' => '設定',
        'admin_logs' => '操作ログ',
    ];
}

function admin_data_quote_identifier($name) {
    return '`' . str_replace('`', '``', (string)$name) . '`';
}

function admin_data_table_exists($pdo, $table) {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?'
        );
        $stmt->execute([(string)$table]);
        return ((int)$stmt->fetchColumn()) > 0;
    } catch (Exception $e) {
        return false;
    }
}

function admin_data_table_columns($pdo, $table) {
    if (!admin_data_table_exists($pdo, $table)) {
        return [];
    }

    $columns = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM ' . admin_data_quote_identifier($table));
    foreach ($stmt->fetchAll() as $row) {
        $columns[] = (string)$row['Field'];
    }
    return $columns;
}

function admin_data_primary_columns($pdo, $table) {
    if (!admin_data_table_exists($pdo, $table)) {
        return [];
    }

    $columns = [];
    $stmt = $pdo->query('SHOW KEYS FROM ' . admin_data_quote_identifier($table) . " WHERE Key_name = 'PRIMARY'");
    foreach ($stmt->fetchAll() as $row) {
        $columns[] = (string)$row['Column_name'];
    }
    return $columns;
}

function admin_data_counts($pdo) {
    $counts = [];
    foreach (admin_data_transfer_tables() as $table => $label) {
        if (!admin_data_table_exists($pdo, $table)) {
            continue;
        }
        $counts[$table] = [
            'label' => $label,
            'count' => (int)$pdo->query('SELECT COUNT(*) FROM ' . admin_data_quote_identifier($table))->fetchColumn(),
        ];
    }
    return $counts;
}

function admin_data_export_payload($pdo) {
    $tables = [];

    foreach (admin_data_transfer_tables() as $table => $label) {
        $columns = admin_data_table_columns($pdo, $table);
        if (!$columns) {
            continue;
        }

        $orderColumns = admin_data_primary_columns($pdo, $table);
        $orderSql = '';
        if ($orderColumns) {
            $orderSql = ' ORDER BY ' . implode(', ', array_map('admin_data_quote_identifier', $orderColumns));
        }

        $rows = $pdo->query('SELECT * FROM ' . admin_data_quote_identifier($table) . $orderSql)->fetchAll();
        $tables[$table] = [
            'label' => $label,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    return [
        'type' => 'coro_admin_data_export',
        'version' => 1,
        'exported_at' => date('c'),
        'tables' => $tables,
    ];
}

function admin_data_send_export($pdo) {
    $payload = admin_data_export_payload($pdo);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('JSONの生成に失敗しました。');
    }

    $filename = 'coro-admin-data-' . date('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

function admin_data_import_file($pdo, $file, $mode = 'merge') {
    if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('インポートするJSONファイルを選択してください。');
    }

    $path = (string)$file['tmp_name'];
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        throw new RuntimeException('JSONファイルを読み込めませんでした。');
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || ($payload['type'] ?? '') !== 'coro_admin_data_export' || empty($payload['tables']) || !is_array($payload['tables'])) {
        throw new RuntimeException('この管理画面からエクスポートしたJSONファイルを選択してください。');
    }

    $mode = $mode === 'replace' ? 'replace' : 'merge';
    $allowedTables = admin_data_transfer_tables();
    $tablesToImport = [];
    foreach ($allowedTables as $table => $label) {
        if (isset($payload['tables'][$table]) && is_array($payload['tables'][$table])) {
            $tablesToImport[$table] = $payload['tables'][$table];
        }
    }

    if (!$tablesToImport) {
        throw new RuntimeException('インポート対象のデータがありません。');
    }

    $stats = [];
    $pdo->beginTransaction();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        if ($mode === 'replace') {
            foreach (array_reverse(array_keys($tablesToImport)) as $table) {
                if (admin_data_table_exists($pdo, $table)) {
                    $pdo->exec('DELETE FROM ' . admin_data_quote_identifier($table));
                }
            }
        }

        foreach ($tablesToImport as $table => $tableData) {
            if (!admin_data_table_exists($pdo, $table)) {
                continue;
            }

            $currentColumns = admin_data_table_columns($pdo, $table);
            $incomingColumns = isset($tableData['columns']) && is_array($tableData['columns'])
                ? array_values(array_intersect($tableData['columns'], $currentColumns))
                : $currentColumns;
            $rows = isset($tableData['rows']) && is_array($tableData['rows']) ? $tableData['rows'] : [];

            if (!$incomingColumns || !$rows) {
                $stats[$table] = ['label' => $allowedTables[$table], 'count' => 0];
                continue;
            }

            $primaryColumns = admin_data_primary_columns($pdo, $table);
            $updateColumns = array_values(array_diff($incomingColumns, $primaryColumns));
            if (!$updateColumns) {
                $updateColumns = [$incomingColumns[0]];
            }

            $quotedColumns = array_map('admin_data_quote_identifier', $incomingColumns);
            $placeholders = implode(', ', array_fill(0, count($incomingColumns), '?'));
            $updates = [];
            foreach ($updateColumns as $column) {
                $quotedColumn = admin_data_quote_identifier($column);
                $updates[] = $quotedColumn . ' = VALUES(' . $quotedColumn . ')';
            }

            $sql = 'INSERT INTO ' . admin_data_quote_identifier($table)
                . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . $placeholders . ')'
                . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $values = [];
                foreach ($incomingColumns as $column) {
                    $values[] = array_key_exists($column, $row) ? $row[$column] : null;
                }
                $stmt->execute($values);
                $count++;
            }

            $stats[$table] = ['label' => $allowedTables[$table], 'count' => $count];
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->commit();
    } catch (Exception $e) {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Exception $ignored) {}
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $stats;
}
