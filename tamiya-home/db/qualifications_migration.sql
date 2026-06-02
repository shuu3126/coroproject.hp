-- qualifications テーブル（職人の資格・免許）
CREATE TABLE IF NOT EXISTS qualifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    craftsman_id    INT NOT NULL,
    name            VARCHAR(200) NOT NULL,
    issued_date     DATE,
    expiry_date     DATE,
    note            TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (craftsman_id) REFERENCES craftsmen(id) ON DELETE CASCADE,
    INDEX idx_expiry (expiry_date),
    INDEX idx_craftsman (craftsman_id)
);
