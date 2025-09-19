<?php

namespace App\Models;

use CodeIgniter\Model;

class ProgramPkModel extends Model
{
    protected $table = 'program_pk'; 
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'program_kegiatan',
        'anggaran',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';


    public function getAllPrograms()
    {
        return $this->orderBy('id', 'DESC')->findAll();
    }
}