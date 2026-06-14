<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['name', 'label', 'is_system'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'name'  => 'required|alpha_dash|max_length[50]|is_unique[roles.name,id,{id}]',
        'label' => 'permit_empty|string|max_length[100]',
    ];
    protected $validationMessages = [
        'name' => [
            'required'  => 'Slug role harus diisi',
            'alpha_dash' => 'Slug hanya boleh huruf, angka, - dan _',
            'is_unique' => 'Slug role sudah digunakan',
        ],
    ];

    /**
     * Semua role + jumlah permission.
     * Catatan: jumlah user per role TIDAK dihitung via JOIN string (users.role vs
     * roles.name bisa beda collation) — dihitung di controller secara PHP.
     */
    public function getRolesWithCount(): array
    {
        return $this->db->table('roles r')
            ->select('r.*, (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS perm_count')
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Jumlah user per nama role: ['admin' => 1, ...]. */
    public function userCountByRole(): array
    {
        $rows = $this->db->table('users')
            ->select('role, COUNT(*) AS c')
            ->groupBy('role')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['role']] = (int) $r['c'];
        }
        return $out;
    }

    /** Semua permission, dikelompokkan per grup. */
    public function allPermissions(): array
    {
        return $this->db->table('permissions')
            ->orderBy('grup', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** ID permission yang dimiliki sebuah role. */
    public function permissionIdsForRole(int $roleId): array
    {
        $rows = $this->db->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'permission_id'));
    }

    /**
     * Set ulang permission sebuah role (replace).
     *
     * @param int[] $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $db = $this->db;
        $db->transStart();

        $db->table('role_permissions')->where('role_id', $roleId)->delete();

        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        foreach ($permissionIds as $pid) {
            if ($pid > 0) {
                $db->table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $pid]);
            }
        }

        $db->transComplete();
    }
}
