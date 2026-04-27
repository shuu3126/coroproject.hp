<?php
declare(strict_types=1);

function inquiry_columns(PDO $pdo, bool $refresh = false): array
{
    static $cache = null;

    if ($refresh || $cache === null) {
        $cache = [];
        $stmt = $pdo->query('SHOW COLUMNS FROM inquiries');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $cache[(string)$column['Field']] = true;
        }
    }

    return $cache;
}

function ensure_inquiries_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS inquiries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(100) NOT NULL DEFAULT 'general',
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            topic VARCHAR(255) NOT NULL,
            url VARCHAR(500) NULL,
            message TEXT NOT NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'new',
            admin_memo TEXT NULL,
            reply_subject VARCHAR(255) NULL,
            reply_body LONGTEXT NULL,
            replied_at DATETIME NULL,
            replied_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_inquiries_created_at (created_at),
            INDEX idx_inquiries_status (status),
            INDEX idx_inquiries_source (source)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columns = inquiry_columns($pdo, true);
    $requiredColumns = [
        'source' => "ALTER TABLE inquiries ADD COLUMN source VARCHAR(100) NOT NULL DEFAULT 'general' AFTER id",
        'status' => "ALTER TABLE inquiries ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'new' AFTER user_agent",
        'admin_memo' => "ALTER TABLE inquiries ADD COLUMN admin_memo TEXT NULL AFTER status",
        'reply_subject' => "ALTER TABLE inquiries ADD COLUMN reply_subject VARCHAR(255) NULL AFTER admin_memo",
        'reply_body' => "ALTER TABLE inquiries ADD COLUMN reply_body LONGTEXT NULL AFTER reply_subject",
        'replied_at' => "ALTER TABLE inquiries ADD COLUMN replied_at DATETIME NULL AFTER reply_body",
        'replied_by' => "ALTER TABLE inquiries ADD COLUMN replied_by BIGINT UNSIGNED NULL AFTER replied_at",
        'created_at' => "ALTER TABLE inquiries ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE inquiries ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];

    foreach ($requiredColumns as $column => $sql) {
        if (!isset($columns[$column])) {
            $pdo->exec($sql);
        }
    }

    inquiry_columns($pdo, true);
    $done = true;
}

function inquiry_status_options(): array
{
    return [
        'new' => 'New',
        'in_progress' => 'In Progress',
        'replied' => 'Replied',
        'closed' => 'Closed',
    ];
}

function inquiry_status_label(string $status): string
{
    $options = inquiry_status_options();
    return $options[$status] ?? $status;
}

function inquiry_source_options(): array
{
    return [
        'general' => 'General',
        'business-matching' => 'Business Matching',
        'creative-support' => 'Creative Support',
        'production' => 'Production',
        'other' => 'Other',
    ];
}

function inquiry_source_label(string $source): string
{
    $options = inquiry_source_options();
    return $options[$source] ?? $source;
}

function inquiry_normalize_source(string $source): string
{
    $source = strtolower(trim($source));
    if ($source === '') {
        return 'general';
    }

    $aliases = [
        'business_matching' => 'business-matching',
        'businessmatching' => 'business-matching',
        'creative_support' => 'creative-support',
        'creativesupport' => 'creative-support',
    ];

    if (isset($aliases[$source])) {
        $source = $aliases[$source];
    }

    return array_key_exists($source, inquiry_source_options()) ? $source : 'other';
}
