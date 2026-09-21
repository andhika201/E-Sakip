<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$db = \Config\Database::connect();

$stats = [
    'total_lakip_kabupaten' => $db->table('lakip')->where('mode', 'kabupaten')->countAllResults(),
    'lakip_per_tahun' => $db->query("SELECT tahun, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY tahun")->getResultArray(),
    'lakip_per_status' => $db->query("SELECT status, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY status")->getResultArray(),
    'lakip_per_source_type' => $db->query("SELECT source_type, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY source_type")->getResultArray(),
    'lakip_per_source_version' => $db->query("SELECT source_version_id, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY source_version_id")->getResultArray(),
    'lakip_no_source_version' => $db->table('lakip')->where('mode', 'kabupaten')->groupStart()->where('source_version_id', null)->orWhere('source_version_id', 0)->groupEnd()->countAllResults(),
];
echo json_encode($stats, JSON_PRETTY_PRINT);
