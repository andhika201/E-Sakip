<?php

namespace App\Models;

use CodeIgniter\Model;

class LakipKabupatenModel extends Model
{
    protected $table = 'lakip_kab';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'judul',
        'tanggal_laporan',
        'file',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'judul' => 'required|min_length[3]|max_length[255]',
        'tanggal_laporan' => 'permit_empty|valid_date',
        'status' => 'in_list[draft,selesai]'
    ];

    protected $validationMessages = [
        'judul' => [
            'required' => 'Judul laporan harus diisi',
            'min_length' => 'Judul laporan minimal 3 karakter',
            'max_length' => 'Judul laporan melebihi maksimal karakter'
        ],
        'tanggal_laporan' => [
            'valid_date' => 'Format tanggal tidak valid'
        ],
        'status' => [
            'in_list' => 'Status harus draft atau selesai'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true; 

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
     * Get LAKIP statistics by status
     */
    public function getStatsByStatus()
    {
        $result = $this->select('status, COUNT(*) as count')
                      ->groupBy('status')
                      ->findAll();
        
        $stats = [
            'draft' => 0,
            'selesai' => 0
        ];

        foreach ($result as $row) {
            if (isset($row['status'])) {
                $stats[$row['status']] = (int)$row['count'];
            }
        }

        return $stats;
    }

    /**
     * Get latest LAKIP
     */
    public function getLatestLakip($limit = 5)
    {
        return $this->orderBy('created_at', 'DESC')
                   ->findAll($limit);
    }

    /**
     * Update status
     */
    public function updateStatus($id, $status)
    {
        $data = ['status' => $status];
        return $this->update($id, $data);
    }

    /**
     * Get available years from tanggal_laporan
     */
    public function getAvailableYears()
    {
        $result = $this->select('DISTINCT YEAR(tanggal_laporan) as tahun')
                      ->where('tanggal_laporan IS NOT NULL')
                      ->orderBy('tahun', 'DESC')
                      ->findAll();
        
        $years = [];
        foreach ($result as $row) {
            if (!empty($row['tahun'])) {
                $years[] = $row['tahun'];
            }
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
            $filePath = WRITEPATH . 'uploads/lakip/kabupaten/' . $lakip['file'];
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
            $uploadPath = WRITEPATH . 'uploads/lakip/kabupaten/';
            
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
            
            $uploadPath = WRITEPATH . 'uploads/lakip/kabupaten/';
            
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
}