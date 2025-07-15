<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'username',
        'password',
        'email',
        'role',
        'opd_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'username' => 'required|min_length[4]|max_length[50]|is_unique[users.username,id,{id}]',
        'password' => 'required|min_length[6]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'role' => 'required|in_list[admin_kabupaten,admin_opd]',
    ];

    protected $validationMessages = [
        'username' => [
            'required' => 'Username harus diisi',
            'min_length' => 'Username minimal 3 karakter',
            'max_length' => 'Username maksimal 50 karakter',
            'is_unique' => 'Username sudah digunakan'
        ],
        'password' => [
            'required' => 'Password harus diisi',
            'min_length' => 'Password minimal 6 karakter'
        ],
        'email' => [
            'required' => 'Email harus diisi',
            'valid_email' => 'Format email tidak valid',
            'is_unique' => 'Email sudah digunakan'
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash password sebelum disimpan ke database
     */
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * Cari user berdasarkan username untuk login
     */
    public function findUserByUsername($username)
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Mendapatkan user berdasarkan role
     */
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)->findAll();
    }

    /**
     * Mendapatkan admin OPD berdasarkan OPD ID
     */
    public function getAdminOpdByOpdId($opdId)
    {
        return $this->where('role', 'admin_opd')
                    ->where('opd_id', $opdId)
                    ->findAll();
    }

    /**
     * Mendapatkan semua admin kabupaten (opd_id = NULL)
     */
    public function getAdminKabupaten()
    {
        return $this->where('role', 'admin_kabupaten')
                    ->where('opd_id', null)
                    ->findAll();
    }

    /**
     * Cek apakah user adalah admin kabupaten
     */
    public function isAdminKabupaten($userId)
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin_kabupaten' && $user['opd_id'] === null;
    }

    /**
     * Cek apakah user adalah admin OPD untuk OPD tertentu
     */
    public function isAdminOpdForOpd($userId, $opdId)
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin_opd' && $user['opd_id'] == $opdId;
    }
}
