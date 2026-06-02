CREATE TABLE IF NOT EXISTS site_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    site_id    INT NOT NULL,
    user_id    INT,
    user_name  VARCHAR(100) NOT NULL,
    body       TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id, created_at)
);
