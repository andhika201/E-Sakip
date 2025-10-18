<?php

namespace App\Models\Opd;

use CodeIgniter\Model;

class LakipOpdModel extends Model
{
    protected $table = 'lakip';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'renstra_indikator_id',
        'rpjmd_indikator_id',
        'target_lalu',
        'capaian_lalu',
        'capaian_tahun_ini',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';


    /**
     * Get all LAKIP records with pagination
     */
    public function getAllLakip($limit = 10, $offset = 0)
    {
        return $this->orderBy('created_at', 'DESC')
            ->findAll($limit, $offset);
    }

    /**
     * Get LAKIP by year (extract from tanggal_laporan)
     */
    public function getLakipByYear($year)
    {
        return $this->where('YEAR(tanggal_laporan)', $year)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get LAKIP by status
     */
    public function getLakipByStatus($status)
    {
        return $this->where('status', $status)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }


    /**
     * Get latest LAKIP
     */
    public function getLatestLakip($limit = 5)
    {
        return $this->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    public function getAvailableYears()
{
    $query = $this->db->table('renstra_target')
        ->select('DISTINCT tahun', false)
        ->orderBy('tahun', 'ASC')
        ->get();

    $years = [];
    foreach ($query->getResultArray() as $row) {
        $years[] = $row['tahun'];
    }

    return $years;
}



    /**
     * Delete file and record
     */
    public function deleteLakip($id)
    {
        $lakip = $this->find($id);

        if ($lakip && !empty($lakip['file'])) {
            // Delete physical file
            $filePath = WRITEPATH . 'uploads/lakip/opd/' . $lakip['file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        return $this->delete($id);
    }

    /**
     * Create new LAKIP record with file upload handling
     */
    public function createLakipWithFile($data, $file = null)
    {
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads/lakip/opd/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    throw new \Exception('Failed to create upload directory');
                }
            }

            // Generate unique filename
            $fileName = $file->getRandomName();

            if ($file->move($uploadPath, $fileName)) {
                $data['file'] = $fileName;
            } else {
                throw new \Exception('Failed to upload file: ' . $file->getErrorString());
            }
        }

        return $this->insert($data);
    }

    /**
     * Update LAKIP with file upload handling
     */
    public function updateLakipWithFile($id, $data, $file = null)
    {
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Get old record to delete old file
            $oldLakip = $this->find($id);

            $uploadPath = WRITEPATH . 'uploads/lakip/opd/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    throw new \Exception('Failed to create upload directory');
                }
            }

            // Generate unique filename
            $fileName = $file->getRandomName();

            if ($file->move($uploadPath, $fileName)) {
                // Delete old file if exists
                if ($oldLakip && !empty($oldLakip['file'])) {
                    $oldFilePath = $uploadPath . $oldLakip['file'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $data['file'] = $fileName;
            } else {
                throw new \Exception('Failed to upload file: ' . $file->getErrorString());
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Get total count for pagination
     */
    public function getTotalCount()
    {
        return $this->countAllResults();
    }


    public function getRenstra($opd_id)
    {
        // Ambil data IKU saja
        $ikuList = $this->db->table('lakip')
            ->select("
            lakip.*,
            rpjmd_indikator_sasaran.indikator_sasaran AS rpjmd_indikator,
            rpjmd_indikator_sasaran.satuan AS rpjmd_satuan,
            renstra_indikator_sasaran.indikator_sasaran AS renstra_indikator,
            renstra_indikator_sasaran.satuan AS renstra_satuan,
            renstra_sasaran.sasaran AS sasaran_renstra,
            rpjmd_sasaran.sasaran_rpjmd
        ", false)
            ->join('rpjmd_indikator_sasaran', 'rpjmd_indikator_sasaran.id = lakip.rpjmd_indikator_id', 'left')
            ->join('renstra_indikator_sasaran', 'renstra_indikator_sasaran.id = lakip.renstra_indikator_id', 'left')
            ->join('renstra_sasaran', 'renstra_sasaran.id = renstra_indikator_sasaran.renstra_sasaran_id', 'left')
            ->join('rpjmd_sasaran', 'rpjmd_sasaran.id = renstra_sasaran.rpjmd_sasaran_id', 'left')
            ->where('renstra_sasaran.opd_id', $opd_id)
            ->orderBy('lakip.id', 'ASC')
            ->get()
            ->getResultArray();

        return $ikuList;
    }
}