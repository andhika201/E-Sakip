<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AuditDb extends BaseCommand
{
    protected $group       = 'Audit';
    protected $name        = 'audit:db';
    protected $description = 'Audit database existing data for LAKIP';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        $stats = [
            'total_lakip_kabupaten' => $db->table('lakip')->where('mode', 'kabupaten')->countAllResults(),
            'lakip_per_tahun' => $db->query("SELECT tahun, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY tahun")->getResultArray(),
            'lakip_per_status' => $db->query("SELECT status, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY status")->getResultArray(),
            'lakip_per_source_type' => $db->query("SELECT source_type, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY source_type")->getResultArray(),
            'lakip_per_source_version' => $db->query("SELECT source_version_id, COUNT(*) as count FROM lakip WHERE mode='kabupaten' GROUP BY source_version_id")->getResultArray(),
            'lakip_no_source_version' => $db->table('lakip')->where('mode', 'kabupaten')->groupStart()->where('source_version_id', null)->orWhere('source_version_id', 0)->groupEnd()->countAllResults(),
        ];
        
        CLI::write(json_encode($stats, JSON_PRETTY_PRINT));
    }
}
