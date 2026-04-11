<?php
$host = '192.168.100.10';
$db = 'sakip_new';
$user = 'user_lan';
$pass = '123456';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM lakip LIKE 'target_hitung'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE lakip ADD COLUMN target_hitung VARCHAR(255) NULL AFTER rpjmd_target_id");
        echo "Added target_hitung.\n";
    } else {
        echo "target_hitung already exists.\n";
    }

    $stmt2 = $pdo->query("SHOW COLUMNS FROM lakip LIKE 'capaian_hitung'");
    if (!$stmt2->fetch()) {
        $pdo->exec("ALTER TABLE lakip ADD COLUMN capaian_hitung VARCHAR(255) NULL AFTER capaian_tahun_ini");
        echo "Added capaian_hitung.\n";
    } else {
        echo "capaian_hitung already exists.\n";
    }

} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
