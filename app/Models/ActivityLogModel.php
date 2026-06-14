<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'user_id', 'username', 'role', 'action', 'module', 'description',
        'method', 'url', 'ip_address', 'user_agent', 'created_at',
    ];

    /** Nilai unik sebuah kolom (untuk dropdown filter). */
    public function distinctColumn(string $col): array
    {
        if (!in_array($col, ['action', 'module', 'username', 'role'], true)) {
            return [];
        }
        $rows = $this->builder()
            ->select($col)
            ->where("{$col} IS NOT NULL", null, false)
            ->where("{$col} !=", '')
            ->distinct()
            ->orderBy($col, 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_column($rows, $col)));
    }
}
