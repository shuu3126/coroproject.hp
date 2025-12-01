<?php
require_once __DIR__ . '/../db.php';

$jsonPath = __DIR__ . '/../data/talents.json';
if (!file_exists($jsonPath)) exit('talents.json not found');

$data = json_decode(file_get_contents($jsonPath), true);
if (!is_array($data)) exit('JSON parse error');

// ---- main talents ----
$talentSql = "REPLACE INTO talents
  (id, name, kana, talent_group, status, debut, last_active, avatar, bio, long_bio_json, tags_json)
  VALUES
  (:id, :name, :kana, :group, :status, :debut, :last, :avatar, :bio, :long_bio, :tags)";

$stmtTalent = $pdo->prepare($talentSql);

// ---- platforms ----
$pdo->exec("DELETE FROM talent_platforms");
$platformSql = "INSERT INTO talent_platforms (talent_id, name, url) VALUES (:tid, :name, :url)";
$stmtPlatform = $pdo->prepare($platformSql);

// ---- links ----
$pdo->exec("DELETE FROM talent_links");
$linkSql = "INSERT INTO talent_links (talent_id, label, url) VALUES (:tid, :label, :url)";
$stmtLink = $pdo->prepare($linkSql);

foreach ($data as $t) {

  // main
  $stmtTalent->execute([
    ':id'        => $t['id'],
    ':name'      => $t['name'],
    ':kana'      => $t['kana'],
    ':group'     => $t['group'],
    ':status'    => $t['status'],
    ':debut'     => $t['debut'],
    ':last'      => $t['lastActive'],
    ':avatar'    => $t['avatar'],
    ':bio'       => $t['bio'],
    ':long_bio'  => json_encode($t['longBio'], JSON_UNESCAPED_UNICODE),
    ':tags'      => json_encode($t['tags'], JSON_UNESCAPED_UNICODE),
  ]);

  // platforms
  foreach ($t['platforms'] as $p) {
    $stmtPlatform->execute([
      ':tid'  => $t['id'],
      ':name' => $p['name'],
      ':url'  => $p['url']
    ]);
  }

  // links
  foreach ($t['links'] as $l) {
    $stmtLink->execute([
      ':tid'   => $t['id'],
      ':label' => $l['label'],
      ':url'   => $l['url']
    ]);
  }
}

echo "Talents Import Completed!";
