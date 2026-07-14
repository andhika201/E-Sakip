<?php

namespace App\Models;

use CodeIgniter\Model;

class PkModel extends Model
{

    protected $table = 'pk';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'opd_id',
        'jenis',
        'tahun',
        'pihak_1',
        'is_plt_pihak_1',
        'is_plh_pihak_1',
        'jabatan_pihak_1_manual',
        'pihak_2',
        'is_plt_pihak_2',
        'is_plh_pihak_2',
        'jabatan_pihak_2_manual',
        'tanggal'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates - Aktifkan auto timestamps
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'jenis' => 'required|in_list[jpt,camat,administrator,pengawas,bupati]',
        'pihak_1' => 'permit_empty|integer',
        'pihak_2' => 'permit_empty|integer',
        'tanggal' => 'required|valid_date'
    ];

    protected $validationMessages = [
        'jenis' => [
            'required' => 'Jenis PK harus dipilih',
            'in_list' => 'Jenis PK tidak valid'
        ],
        'pihak_1' => [
            'required' => 'Pihak 1 harus dipilih',
            'integer' => 'Pihak 1 harus berupa angka'
        ],
        'pihak_2' => [
            'required' => 'Pihak 2 harus dipilih',
            'integer' => 'Pihak 2 harus berupa angka'
        ],
        'tanggal' => [
            'required' => 'Tanggal harus diisi',
            'valid_date' => 'Format tanggal tidak valid'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    private ?array $pkFieldNames = null;
    private array $fieldNamesByTable = [];

    private function pkHasField(string $field): bool
    {
        if ($this->pkFieldNames === null) {
            try {
                $this->pkFieldNames = $this->db->getFieldNames('pk');
            } catch (\Throwable $e) {
                $this->pkFieldNames = [];
            }
        }

        return in_array($field, $this->pkFieldNames, true);
    }

    private function tableHasField(string $table, string $field): bool
    {
        if (!array_key_exists($table, $this->fieldNamesByTable)) {
            try {
                $this->fieldNamesByTable[$table] = $this->db->tableExists($table)
                    ? $this->db->getFieldNames($table)
                    : [];
            } catch (\Throwable $e) {
                $this->fieldNamesByTable[$table] = [];
            }
        }

        return in_array($field, $this->fieldNamesByTable[$table], true);
    }

    private function normalizeIds(array $ids): array
    {
        $ids = array_map(static fn ($id) => (int) $id, $ids);
        $ids = array_filter($ids, static fn ($id) => $id > 0);

        return array_values(array_unique($ids));
    }

    private function pkPltPayload(array $data): array
    {
        $payload = [];
        foreach (['is_plt_pihak_1', 'is_plh_pihak_1', 'is_plt_pihak_2', 'is_plh_pihak_2'] as $field) {
            if ($this->pkHasField($field)) {
                $payload[$field] = !empty($data[$field]) ? 1 : 0;
            }
        }

        foreach (['jabatan_pihak_1_manual', 'jabatan_pihak_2_manual'] as $field) {
            if ($this->pkHasField($field)) {
                $value = trim((string) ($data[$field] ?? ''));
                $payload[$field] = $value !== '' ? $value : null;
            }
        }

        return $payload;
    }

    private function pkFieldSelect(string $alias, string $field, string $fallback = 'NULL'): string
    {
        return $this->pkHasField($field)
            ? "{$alias}.{$field} AS {$field}"
            : "{$fallback} AS {$field}";
    }

    private function pkPltSelect(string $alias, string $field): string
    {
        return $this->pkFieldSelect($alias, $field, '0');
    }

    private function jabatanPkPltExpr(string $pkAlias, string $field, string $jabatanAlias): string
    {
        $side = str_replace('is_plt_', '', $field);
        $plhField = 'is_plh_' . $side;
        $manualField = 'jabatan_' . $side . '_manual';
        $jabatanExpr = "{$jabatanAlias}.nama_jabatan";

        if ($this->pkHasField($manualField)) {
            $jabatanExpr = "COALESCE(NULLIF({$pkAlias}.{$manualField}, ''), {$jabatanExpr})";
        }

        if ($this->pkHasField($field) && $this->pkHasField($plhField)) {
            return "CASE WHEN {$pkAlias}.{$field} = 1 THEN CONCAT('Plt. ', {$jabatanExpr}) WHEN {$pkAlias}.{$plhField} = 1 THEN CONCAT('Plh. ', {$jabatanExpr}) ELSE {$jabatanExpr} END";
        }

        if ($this->pkHasField($field)) {
            return "IF({$pkAlias}.{$field} = 1, CONCAT('Plt. ', {$jabatanExpr}), {$jabatanExpr})";
        }

        if ($this->pkHasField($plhField)) {
            return "IF({$pkAlias}.{$plhField} = 1, CONCAT('Plh. ', {$jabatanExpr}), {$jabatanExpr})";
        }

        return $jabatanExpr;
    }

    /**
     * Get all program entries (pk_program) for a given PK id
     */
    public function getProgramByPkId($pkId)
    {
        return $this->db->table('pk_program pp')
            ->select('
            MIN(pp.id) as pk_program_id,
            pr.program_kegiatan,
            pr.anggaran,
            pp.program_id
        ')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->groupBy('pp.program_id, pr.anggaran')
            ->orderBy('pk_program_id', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function getKegiatanByPkId($pkId)
    {
        return $this->db->table('pk_kegiatan kk')
            ->select('
            kk.id as pk_kegiatan_id,
            kp.kegiatan,
            kp.anggaran,
            kk.kegiatan_id,
            kk.pk_program_id,
            pr.program_kegiatan
        ')
            ->join('kegiatan_pk kp', 'kp.id = kk.kegiatan_id', 'left')
            ->join('pk_program pp', 'pp.id = kk.pk_program_id', 'left')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->orderBy('pr.program_kegiatan', 'ASC')
            ->orderBy('kk.id', 'ASC')
            ->get()
            ->getResultArray();
    }


    public function getSubKegiatanByPkId($pkId)
    {
        return $this->db->table('pk_subkegiatan psk')
            ->select('
            kp.kegiatan,
            skp.sub_kegiatan,
            skp.anggaran
        ')
            ->join('sub_kegiatan_pk skp', 'skp.id = psk.subkegiatan_id', 'left')
            ->join('pk_kegiatan kk', 'kk.id = psk.pk_kegiatan_id', 'left')
            ->join('kegiatan_pk kp', 'kp.id = kk.kegiatan_id', 'left')
            ->join('pk_program pp', 'pp.id = kk.pk_program_id', 'left')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id', 'left')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id', 'left')
            ->where('ps.pk_id', $pkId)
            ->groupBy([
                'kp.kegiatan',
                'skp.sub_kegiatan',
                'skp.anggaran'
            ])
            ->orderBy('kp.kegiatan', 'ASC')
            ->orderBy('skp.sub_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }


    /**
     * Get all sasaran and their indikator for a given PK id
     */
    public function getSasaranByPkId($pkId)
    {
        $sasaranList = $this->db->table('pk_sasaran')
            ->where('pk_id', $pkId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($sasaranList)) {
            return $sasaranList;
        }

        // ============================================================
        // Batch-load semua level sekaligus untuk menghindari N+1.
        // Query per-level di-orderBy id ASC: karena urutan id ASC secara
        // global identik dengan urutan id ASC di dalam tiap parent, hasil
        // pengelompokan di PHP sama persis dgn versi query-per-baris lama.
        // FK parent ikut di-SELECT hanya utk grouping lalu di-unset() agar
        // struktur baris yang dikembalikan tetap identik.
        // ============================================================
        $sasaranIds = array_column($sasaranList, 'id');

        // INDIKATOR (pi.* sudah memuat pk_sasaran_id, jadi tak perlu tambahan)
        $indikatorList = $this->db->table('pk_indikator pi')
            ->select('pi.*, satuan.satuan as satuan_nama')
            ->join('satuan', 'satuan.id = pi.id_satuan', 'left')
            ->whereIn('pi.pk_sasaran_id', $sasaranIds)
            ->orderBy('pi.id', 'ASC')
            ->get()
            ->getResultArray();
        $indikatorIds = array_column($indikatorList, 'id');

        // PROGRAM (per indikator)
        $programList = !empty($indikatorIds) ? $this->db->table('pk_program pp')
            ->select('pp.id as pk_program_id, pp.pk_indikator_id, pr.program_kegiatan, pr.anggaran, pp.program_id')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->whereIn('pp.pk_indikator_id', $indikatorIds)
            ->orderBy('pp.id', 'ASC')
            ->get()
            ->getResultArray() : [];
        $programIds = array_column($programList, 'pk_program_id');

        // KEGIATAN (per program)
        $kegiatanList = !empty($programIds) ? $this->db->table('pk_kegiatan pkeg')
            ->select('pkeg.id as pk_kegiatan_id, pkeg.pk_program_id, keg.kegiatan, keg.anggaran, pkeg.kegiatan_id')
            ->join('kegiatan_pk keg', 'keg.id = pkeg.kegiatan_id', 'left')
            ->whereIn('pkeg.pk_program_id', $programIds)
            ->orderBy('pkeg.id', 'ASC')
            ->get()
            ->getResultArray() : [];
        $kegiatanIds = array_column($kegiatanList, 'pk_kegiatan_id');

        // SUBKEGIATAN (per kegiatan)
        $subkegiatanList = !empty($kegiatanIds) ? $this->db->table('pk_subkegiatan pksub')
            ->select('pksub.id as pk_subkegiatan_id, pksub.pk_kegiatan_id, sub.sub_kegiatan, sub.anggaran, pksub.subkegiatan_id')
            ->join('sub_kegiatan_pk sub', 'sub.id = pksub.subkegiatan_id', 'left')
            ->whereIn('pksub.pk_kegiatan_id', $kegiatanIds)
            ->orderBy('pksub.id', 'ASC')
            ->get()
            ->getResultArray() : [];

        // ---- Rakit tree dari bawah ke atas (group by FK parent) ----
        $subByKegiatan = [];
        foreach ($subkegiatanList as $row) {
            $parent = $row['pk_kegiatan_id'];
            unset($row['pk_kegiatan_id']); // strip kunci grouping -> baris identik versi lama
            $subByKegiatan[$parent][] = $row;
        }

        $kegByProgram = [];
        foreach ($kegiatanList as $row) {
            $parent = $row['pk_program_id'];
            unset($row['pk_program_id']);
            $row['subkegiatan'] = $subByKegiatan[$row['pk_kegiatan_id']] ?? [];
            $kegByProgram[$parent][] = $row;
        }

        $progByIndikator = [];
        foreach ($programList as $row) {
            $parent = $row['pk_indikator_id'];
            unset($row['pk_indikator_id']);
            $row['kegiatan'] = $kegByProgram[$row['pk_program_id']] ?? [];
            $progByIndikator[$parent][] = $row;
        }

        $indBySasaran = [];
        foreach ($indikatorList as $row) {
            $row['program'] = $progByIndikator[$row['id']] ?? [];
            $indBySasaran[$row['pk_sasaran_id']][] = $row;
        }

        foreach ($sasaranList as &$sasaran) {
            $sasaran['indikator'] = $indBySasaran[$sasaran['id']] ?? [];
        }
        unset($sasaran);

        return $sasaranList;
    }



    /**
     * Get indikator acuan (referensi) for a given PK
     */
    public function getIndikatorAcuanByPkId($pkId)
    {
        return $this->db->table('pk_referensi pr')
            ->select('pr.*, pi.indikator as nama_indikator, pi.id as indikator_id')
            ->join('pk_indikator pi', 'pi.id = pr.referensi_indikator_id', 'left')
            ->where('pr.pk_id', $pkId)
            ->get()
            ->getResultArray();
    }

    /**
     * Save a complete PK (with sasaran->indikator->program->kegiatan->subkegiatan)
     * (Kept as robust implementation)
     * Returns inserted PK id or false
     */
    public function saveCompletePk($data)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $now = date('Y-m-d H:i:s');

            // ---------------------------------------
            // 1. INSERT PK
            // ---------------------------------------
            $pkData = [
                'opd_id' => $data['opd_id'],
                'tahun' => $data['tahun'],
                'jenis' => $data['jenis'],
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tanggal' => $data['tanggal'],
                'created_at' => $now,
                'updated_at' => $now
            ];
            $pkData = array_merge($pkData, $this->pkPltPayload($data));

            $db->table('pk')->insert($pkData);
            $this->logDbError($db, 'INSERT PK');
            $pkId = $db->insertID();


            // ---------------------------------------
            // 2. REFERENSI ACUAN
            if (!empty($data['referensi_acuan'])) {
                foreach ($data['referensi_acuan'] as $ref) {
                    $db->table('pk_referensi')->insert([
                        'pk_id' => $pkId,
                        'referensi_pk_id' => $ref['referensi_pk_id'] ?? null,
                        'referensi_indikator_id' => $ref['referensi_indikator_id'] ?? null,
                    ]);
                    $this->logDbError($db, 'INSERT PK REFERENSI');
                }
            }


            // ---------------------------------------
            // 3. LOOP: SASARAN → INDIKATOR → PROGRAM → KEGIATAN → SUBKEGIATAN
            // ---------------------------------------
            if (!empty($data['sasaran_pk'])) {

                foreach ($data['sasaran_pk'] as $sasaran) {

                    // Insert Sasaran
                    $db->table('pk_sasaran')->insert([
                        'pk_id' => $pkId,
                        'jenis' => $data['jenis'],
                        'sasaran' => $sasaran['sasaran'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $this->logDbError($db, 'INSERT PK SASARAN');
                    $pkSasaranId = $db->insertID();


                    if (!empty($sasaran['indikator'])) {

                        foreach ($sasaran['indikator'] as $indikator) {

                            $idSatuan = $indikator['id_satuan'] ?? ($indikator['satuan'] ?? null);

                            // Insert Indikator
                            $db->table('pk_indikator')->insert([
                                'pk_sasaran_id' => $pkSasaranId,
                                'jenis' => $data['jenis'],
                                'indikator' => $indikator['indikator'] ?? '',
                                'target' => $indikator['target'] ?? '',
                                'id_satuan' => $idSatuan,
                                'jenis_indikator' => $indikator['jenis_indikator'],
                                'created_at' => $now,
                                'updated_at' => $now
                            ]);
                            $this->logDbError($db, 'INSERT PK INDIKATOR');
                            $pkIndikatorId = $db->insertID();

                            // ================================
                            // 🔥 INSERT PROGRAM (JPT / ADMIN / PENGAWAS)
                            // ================================
                            if (!empty($indikator['program'])) {

                                foreach ($indikator['program'] as $programItem) {

                                    // Insert program
                                    $db->table('pk_program')->insert([
                                        'program_id' => $programItem['program_id'] ?? null,
                                        'pk_indikator_id' => $pkIndikatorId,
                                        'created_at' => $now,
                                        'updated_at' => $now
                                    ]);

                                    $this->logDbError($db, 'INSERT PK PROGRAM');
                                    $pkProgramId = $db->insertID();


                                    // ================================
                                    // 🔥 INSERT KEGIATAN (admin + pengawas)
                                    // ================================
                                    if (!empty($programItem['kegiatan'])) {
                                        foreach ($programItem['kegiatan'] as $kegiatanItem) {

                                            $db->table('pk_kegiatan')->insert([
                                                'pk_program_id' => $pkProgramId,
                                                'kegiatan_id' => $kegiatanItem['kegiatan_id'] ?? null,
                                                'created_at' => $now,
                                                'updated_at' => $now
                                            ]);
                                            $this->logDbError($db, 'INSERT PK KEGIATAN');
                                            $pkKegiatanId = $db->insertID();


                                            // ================================
                                            // 🔥 INSERT SUBKEGIATAN (pengawas)
                                            // ================================
                                            if (!empty($kegiatanItem['subkegiatan'])) {

                                                foreach ($kegiatanItem['subkegiatan'] as $subItem) {
                                                    $db->table('pk_subkegiatan')->insert([
                                                        'pk_kegiatan_id' => $pkKegiatanId,
                                                        'subkegiatan_id' => $subItem['subkegiatan_id'] ?? null,
                                                        'created_at' => $now,
                                                        'updated_at' => $now
                                                    ]);
                                                    $this->logDbError($db, 'INSERT PK SUBKEGIATAN');
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }


            // ---------------------------------------
            // 4. MISI BUPATI
            // ---------------------------------------
            if (!empty($data['misi_bupati_id'])) {
                foreach ($data['misi_bupati_id'] as $misiId) {
                    $db->table('pk_misi')->insert([
                        'pk_id' => $pkId,
                        'rpjmd_misi_id' => $misiId,
                    ]);
                    $this->logDbError($db, 'INSERT PK MISI');
                }
            }

            // commit
            $db->transComplete();
            return $pkId;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }


    private function logDbError($db, $label)
    {
        $error = $db->error();
        if (!empty($error['code'])) {
            log_message('error', "DB ERROR on {$label}: " . $error['message']);
        }
    }

    private function deletePkSubkegiatanByKegiatanIds(array $pkKegiatanIds): void
    {
        $pkKegiatanIds = $this->normalizeIds($pkKegiatanIds);
        if (empty($pkKegiatanIds)) {
            return;
        }

        $this->db->table('pk_subkegiatan')
            ->whereIn('pk_kegiatan_id', $pkKegiatanIds)
            ->delete();
    }

    private function deletePkKegiatanBranches(array $pkKegiatanIds): void
    {
        $pkKegiatanIds = $this->normalizeIds($pkKegiatanIds);
        if (empty($pkKegiatanIds)) {
            return;
        }

        $this->deletePkSubkegiatanByKegiatanIds($pkKegiatanIds);
        $this->db->table('pk_kegiatan')
            ->whereIn('id', $pkKegiatanIds)
            ->delete();
    }

    private function deletePkKegiatanByProgramIds(array $pkProgramIds): void
    {
        $pkProgramIds = $this->normalizeIds($pkProgramIds);
        if (empty($pkProgramIds)) {
            return;
        }

        $rows = $this->db->table('pk_kegiatan')
            ->select('id')
            ->whereIn('pk_program_id', $pkProgramIds)
            ->get()
            ->getResultArray();

        $this->deletePkKegiatanBranches(array_column($rows, 'id'));
    }

    private function deletePkProgramBranches(array $pkProgramIds): void
    {
        $pkProgramIds = $this->normalizeIds($pkProgramIds);
        if (empty($pkProgramIds)) {
            return;
        }

        $this->deletePkKegiatanByProgramIds($pkProgramIds);
        $this->db->table('pk_program')
            ->whereIn('id', $pkProgramIds)
            ->delete();
    }

    private function deleteTargetRencanaByIndikatorIds(array $pkIndikatorIds): void
    {
        $pkIndikatorIds = $this->normalizeIds($pkIndikatorIds);
        if (empty($pkIndikatorIds) || !$this->tableHasField('target_rencana', 'pk_indikator_id')) {
            return;
        }

        $targetRows = $this->db->table('target_rencana')
            ->select('id')
            ->whereIn('pk_indikator_id', $pkIndikatorIds)
            ->get()
            ->getResultArray();
        $targetIds = $this->normalizeIds(array_column($targetRows, 'id'));

        if (!empty($targetIds) && $this->tableHasField('monev', 'target_rencana_id')) {
            $this->db->table('monev')
                ->whereIn('target_rencana_id', $targetIds)
                ->delete();
        }

        $this->db->table('target_rencana')
            ->whereIn('pk_indikator_id', $pkIndikatorIds)
            ->delete();
    }

    private function deletePkIndikatorBranches(array $pkIndikatorIds, bool $deleteIndikatorRows = true): void
    {
        $pkIndikatorIds = $this->normalizeIds($pkIndikatorIds);
        if (empty($pkIndikatorIds)) {
            return;
        }

        $programRows = $this->db->table('pk_program')
            ->select('id')
            ->whereIn('pk_indikator_id', $pkIndikatorIds)
            ->get()
            ->getResultArray();

        $this->deletePkProgramBranches(array_column($programRows, 'id'));
        $this->deleteTargetRencanaByIndikatorIds($pkIndikatorIds);

        if ($this->tableHasField('pk_referensi', 'referensi_indikator_id')) {
            $this->db->table('pk_referensi')
                ->whereIn('referensi_indikator_id', $pkIndikatorIds)
                ->delete();
        }

        if ($deleteIndikatorRows) {
            $this->db->table('pk_indikator')
                ->whereIn('id', $pkIndikatorIds)
                ->delete();
        }
    }

    private function deletePkSasaranBranches(array $pkSasaranIds): void
    {
        $pkSasaranIds = $this->normalizeIds($pkSasaranIds);
        if (empty($pkSasaranIds)) {
            return;
        }

        $indicatorRows = $this->db->table('pk_indikator')
            ->select('id')
            ->whereIn('pk_sasaran_id', $pkSasaranIds)
            ->get()
            ->getResultArray();

        $this->deletePkIndikatorBranches(array_column($indicatorRows, 'id'), true);
        $this->db->table('pk_sasaran')
            ->whereIn('id', $pkSasaranIds)
            ->delete();
    }

    public function deleteCompletePk(int $pkId): bool
    {
        $db = \Config\Database::connect();
        $db->transException(true)->transBegin();

        try {
            $sasaranRows = $db->table('pk_sasaran')
                ->select('id')
                ->where('pk_id', $pkId)
                ->get()
                ->getResultArray();

            $this->deletePkSasaranBranches(array_column($sasaranRows, 'id'));

            if ($this->tableHasField('pk_referensi', 'pk_id')) {
                $refBuilder = $db->table('pk_referensi');
                if ($this->tableHasField('pk_referensi', 'referensi_pk_id')) {
                    $refBuilder
                        ->groupStart()
                        ->where('pk_id', $pkId)
                        ->orWhere('referensi_pk_id', $pkId)
                        ->groupEnd()
                        ->delete();
                } else {
                    $refBuilder->where('pk_id', $pkId)->delete();
                }
            }

            if ($this->tableHasField('pk_misi', 'pk_id')) {
                $db->table('pk_misi')->where('pk_id', $pkId)->delete();
            }

            $db->table('pk')->where('id', $pkId)->delete();
            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Update PK and related data (clean & re-insert approach)
     * - This function removes all related rows for the given pk id and inserts fresh ones according to $data
     * - Returns boolean
     */
    public function updateCompletePk($pkId, $data)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $now = date('Y-m-d H:i:s');

        try {

            // =====================================================
            // 1. UPDATE PK
            // =====================================================
            $pkUpdate = [
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tahun' => $data['tahun'],
                'tanggal' => $data['tanggal'],
                'updated_at' => $now
            ];
            $pkUpdate = array_merge($pkUpdate, $this->pkPltPayload($data));

            $db->table('pk')->where('id', $pkId)->update($pkUpdate);

            // =====================================================
            // 2. SYNC SASARAN
            // =====================================================
            $existingSasaran = $db->table('pk_sasaran')
                ->where('pk_id', $pkId)->get()->getResultArray();
            $existingSasaranIds = array_column($existingSasaran, 'id');
            $usedSasaranIds = [];

            foreach ($data['sasaran_pk'] as $s) {

                // ---------- SASARAN ----------
                if (!empty($s['pk_sasaran_id'])) {
                    $db->table('pk_sasaran')
                        ->where('id', $s['pk_sasaran_id'])
                        ->update([
                            'sasaran' => $s['sasaran'],
                            'updated_at' => $now
                        ]);
                    $pkSasaranId = $s['pk_sasaran_id'];
                } else {
                    $db->table('pk_sasaran')->insert([
                        'pk_id' => $pkId,
                        'jenis' => $data['jenis'],
                        'sasaran' => $s['sasaran'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $pkSasaranId = $db->insertID();
                }
                $usedSasaranIds[] = $pkSasaranId;

                // =====================================================
                // 3. SYNC INDIKATOR
                // =====================================================
                $existingInd = $db->table('pk_indikator')
                    ->where('pk_sasaran_id', $pkSasaranId)->get()->getResultArray();
                $existingIndIds = array_column($existingInd, 'id');
                $usedIndIds = [];

                foreach ($s['indikator'] as $ind) {

                    if (!empty($ind['pk_indikator_id'])) {
                        $db->table('pk_indikator')->where('id', $ind['pk_indikator_id'])->update([
                            'indikator' => $ind['indikator'],
                            'target' => $ind['target'],
                            'id_satuan' => $ind['id_satuan'],
                            'jenis_indikator' => $ind['jenis_indikator'],
                            'updated_at' => $now
                        ]);
                        $pkIndId = $ind['pk_indikator_id'];
                    } else {
                        $db->table('pk_indikator')->insert([
                            'pk_sasaran_id' => $pkSasaranId,
                            'jenis' => $data['jenis'],
                            'indikator' => $ind['indikator'],
                            'target' => $ind['target'],
                            'id_satuan' => $ind['id_satuan'],
                            'jenis_indikator' => $ind['jenis_indikator'],
                            'created_at' => $now,
                            'updated_at' => $now
                        ]);
                        $pkIndId = $db->insertID();
                    }
                    $usedIndIds[] = $pkIndId;

                    // =====================================================
                    // 4. SYNC PROGRAM
                    // =====================================================
                    if (empty($ind['program']) || !is_array($ind['program'])) {
                        continue;
                    }

                    foreach ($ind['program'] as $p) {

                        if (!empty($p['pk_program_id'])) {
                            $db->table('pk_program')->where('id', $p['pk_program_id'])->update([
                                'program_id' => $p['program_id'],
                                'updated_at' => $now
                            ]);
                            $pkProgramId = $p['pk_program_id'];
                        } else {
                            $db->table('pk_program')->insert([
                                'pk_indikator_id' => $pkIndId,
                                'program_id' => $p['program_id'],
                                'created_at' => $now,
                                'updated_at' => $now
                            ]);
                            $pkProgramId = $db->insertID();
                        }

                        // 🔥 JPT/CAMAT STOP DI PROGRAM
                        if (in_array($data['jenis'], ['jpt', 'camat'], true)) {
                            continue;
                        }

                        if (empty($p['kegiatan']) || !is_array($p['kegiatan'])) {
                            continue;
                        }

                        // =====================================================
                        // 5. SYNC KEGIATAN
                        // =====================================================
                        foreach ($p['kegiatan'] as $k) {

                            if (!empty($k['pk_kegiatan_id'])) {
                                $db->table('pk_kegiatan')->where('id', $k['pk_kegiatan_id'])->update([
                                    'kegiatan_id' => $k['kegiatan_id'],
                                    'updated_at' => $now
                                ]);
                                $pkKegId = $k['pk_kegiatan_id'];
                            } else {
                                $db->table('pk_kegiatan')->insert([
                                    'pk_program_id' => $pkProgramId,
                                    'kegiatan_id' => $k['kegiatan_id'],
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ]);
                                $pkKegId = $db->insertID();
                            }

                            // =====================================================
                            // 6. SYNC SUBKEGIATAN
                            // =====================================================
                            if (
                                $data['jenis'] === 'pengawas' &&
                                !empty($k['subkegiatan']) &&
                                is_array($k['subkegiatan'])
                            ) {

                                $existingSub = $db->table('pk_subkegiatan')
                                    ->where('pk_kegiatan_id', $pkKegId)
                                    ->get()
                                    ->getResultArray();

                                $existingSubIds = array_column($existingSub, 'id');
                                $usedSubIds = [];

                                foreach ($k['subkegiatan'] as $sk) {

                                    if (!empty($sk['pk_subkegiatan_id'])) {

                                        // UPDATE
                                        $db->table('pk_subkegiatan')
                                            ->where('id', $sk['pk_subkegiatan_id'])
                                            ->update([
                                                'subkegiatan_id' => $sk['subkegiatan_id'],
                                                'updated_at' => $now
                                            ]);

                                        $pkSubId = $sk['pk_subkegiatan_id'];
                                    } else {

                                        // INSERT
                                        $db->table('pk_subkegiatan')->insert([
                                            'pk_kegiatan_id' => $pkKegId,
                                            'subkegiatan_id' => $sk['subkegiatan_id'],
                                            'created_at' => $now,
                                            'updated_at' => $now
                                        ]);

                                        $pkSubId = $db->insertID();
                                    }

                                    $usedSubIds[] = $pkSubId;
                                }

                                // DELETE subkegiatan yang dihapus user
                                $deleteSub = array_diff($existingSubIds, $usedSubIds);
                                if (!empty($deleteSub)) {
                                    $db->table('pk_subkegiatan')->whereIn('id', $deleteSub)->delete();
                                }
                            }
                        }
                    }
                }
            }

            // DELETE sasaran yang dihapus
            $deleteSasaran = array_diff($existingSasaranIds, $usedSasaranIds);
            if ($deleteSasaran) {
                $db->table('pk_sasaran')->whereIn('id', $deleteSasaran)->delete();
            }

            $db->transComplete();
            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }


    /**
     * Sinkronisasi penuh struktur PK berdasarkan payload form edit.
     * Baris yang tidak lagi dikirim dari form dihapus manual sampai cabang terdalam
     * agar tidak tertinggal orphan saat FK memakai ON DELETE SET NULL.
     */
    public function syncCompletePk($pkId, $data): bool
    {
        $db = \Config\Database::connect();
        $db->transException(true)->transBegin();
        $now = date('Y-m-d H:i:s');

        try {
            $pkUpdate = [
                'pihak_1' => $data['pihak_1'],
                'pihak_2' => $data['pihak_2'],
                'tahun' => $data['tahun'],
                'tanggal' => $data['tanggal'],
                'updated_at' => $now
            ];
            $pkUpdate = array_merge($pkUpdate, $this->pkPltPayload($data));

            $db->table('pk')->where('id', $pkId)->update($pkUpdate);

            $db->table('pk_referensi')->where('pk_id', $pkId)->delete();
            foreach (($data['referensi_acuan'] ?? []) as $ref) {
                $refPkId = (int) ($ref['referensi_pk_id'] ?? 0);
                $refIndId = (int) ($ref['referensi_indikator_id'] ?? 0);
                if ($refPkId <= 0 || $refIndId <= 0) {
                    continue;
                }

                $db->table('pk_referensi')->insert([
                    'pk_id' => $pkId,
                    'referensi_pk_id' => $refPkId,
                    'referensi_indikator_id' => $refIndId,
                ]);
            }

            if ($this->tableHasField('pk_misi', 'pk_id')) {
                $db->table('pk_misi')->where('pk_id', $pkId)->delete();
                foreach (($data['misi_bupati_id'] ?? []) as $misiId) {
                    $misiId = (int) $misiId;
                    if ($misiId <= 0) {
                        continue;
                    }

                    $db->table('pk_misi')->insert([
                        'pk_id' => $pkId,
                        'rpjmd_misi_id' => $misiId,
                    ]);
                }
            }

            $existingSasaran = $db->table('pk_sasaran')->where('pk_id', $pkId)->get()->getResultArray();
            $existingSasaranIds = $this->normalizeIds(array_column($existingSasaran, 'id'));
            $usedSasaranIds = [];

            foreach (($data['sasaran_pk'] ?? []) as $s) {
                $postedSasaranId = (int) ($s['pk_sasaran_id'] ?? 0);
                $sasaranText = trim((string) ($s['sasaran'] ?? ''));
                if ($sasaranText === '') {
                    continue;
                }

                if ($postedSasaranId > 0 && in_array($postedSasaranId, $existingSasaranIds, true)) {
                    $db->table('pk_sasaran')->where('id', $postedSasaranId)->update([
                        'sasaran' => $sasaranText,
                        'updated_at' => $now
                    ]);
                    $pkSasaranId = $postedSasaranId;
                } else {
                    $db->table('pk_sasaran')->insert([
                        'pk_id' => $pkId,
                        'jenis' => $data['jenis'],
                        'sasaran' => $sasaranText,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                    $pkSasaranId = (int) $db->insertID();
                }
                $usedSasaranIds[] = $pkSasaranId;

                $existingInd = $db->table('pk_indikator')->where('pk_sasaran_id', $pkSasaranId)->get()->getResultArray();
                $existingIndIds = $this->normalizeIds(array_column($existingInd, 'id'));
                $usedIndIds = [];

                foreach (($s['indikator'] ?? []) as $ind) {
                    $postedIndId = (int) ($ind['pk_indikator_id'] ?? 0);
                    $indikatorText = trim((string) ($ind['indikator'] ?? ''));
                    if ($indikatorText === '') {
                        continue;
                    }

                    $indicatorPayload = [
                        'indikator' => $indikatorText,
                        'target' => $ind['target'] ?? '',
                        'id_satuan' => $ind['id_satuan'] ?? null,
                        'jenis_indikator' => $ind['jenis_indikator'] ?? null,
                        'updated_at' => $now
                    ];

                    if ($postedIndId > 0 && in_array($postedIndId, $existingIndIds, true)) {
                        $db->table('pk_indikator')->where('id', $postedIndId)->update($indicatorPayload);
                        $pkIndId = $postedIndId;
                    } else {
                        $indicatorPayload['pk_sasaran_id'] = $pkSasaranId;
                        $indicatorPayload['jenis'] = $data['jenis'];
                        $indicatorPayload['created_at'] = $now;
                        $db->table('pk_indikator')->insert($indicatorPayload);
                        $pkIndId = (int) $db->insertID();
                    }
                    $usedIndIds[] = $pkIndId;

                    $existingProgramRows = $db->table('pk_program')
                        ->select('id')
                        ->where('pk_indikator_id', $pkIndId)
                        ->get()
                        ->getResultArray();
                    $existingProgramIds = $this->normalizeIds(array_column($existingProgramRows, 'id'));
                    $usedProgramIds = [];

                    foreach (($ind['program'] ?? []) as $p) {
                        $programId = (int) ($p['program_id'] ?? 0);
                        if ($programId <= 0) {
                            continue;
                        }

                        $postedProgramId = (int) ($p['pk_program_id'] ?? 0);
                        if ($postedProgramId > 0 && in_array($postedProgramId, $existingProgramIds, true)) {
                            $db->table('pk_program')->where('id', $postedProgramId)->update([
                                'pk_indikator_id' => $pkIndId,
                                'program_id' => $programId,
                                'updated_at' => $now
                            ]);
                            $pkProgramId = $postedProgramId;
                        } else {
                            $db->table('pk_program')->insert([
                                'pk_indikator_id' => $pkIndId,
                                'program_id' => $programId,
                                'created_at' => $now,
                                'updated_at' => $now
                            ]);
                            $pkProgramId = (int) $db->insertID();
                        }
                        $usedProgramIds[] = $pkProgramId;

                        if (in_array($data['jenis'], ['jpt', 'camat'], true)) {
                            $this->deletePkKegiatanByProgramIds([$pkProgramId]);
                            continue;
                        }

                        $existingKegRows = $db->table('pk_kegiatan')
                            ->select('id')
                            ->where('pk_program_id', $pkProgramId)
                            ->get()
                            ->getResultArray();
                        $existingKegIds = $this->normalizeIds(array_column($existingKegRows, 'id'));
                        $usedKegIds = [];

                        foreach (($p['kegiatan'] ?? []) as $k) {
                            $kegiatanId = (int) ($k['kegiatan_id'] ?? 0);
                            if ($kegiatanId <= 0) {
                                continue;
                            }

                            $postedKegId = (int) ($k['pk_kegiatan_id'] ?? 0);
                            if ($postedKegId > 0 && in_array($postedKegId, $existingKegIds, true)) {
                                $db->table('pk_kegiatan')->where('id', $postedKegId)->update([
                                    'pk_program_id' => $pkProgramId,
                                    'kegiatan_id' => $kegiatanId,
                                    'updated_at' => $now
                                ]);
                                $pkKegId = $postedKegId;
                            } else {
                                $db->table('pk_kegiatan')->insert([
                                    'pk_program_id' => $pkProgramId,
                                    'kegiatan_id' => $kegiatanId,
                                    'created_at' => $now,
                                    'updated_at' => $now
                                ]);
                                $pkKegId = (int) $db->insertID();
                            }
                            $usedKegIds[] = $pkKegId;

                            if ($data['jenis'] === 'pengawas' && !empty($k['subkegiatan']) && is_array($k['subkegiatan'])) {
                                $existingSub = $db->table('pk_subkegiatan')
                                    ->where('pk_kegiatan_id', $pkKegId)
                                    ->get()
                                    ->getResultArray();
                                $existingSubIds = $this->normalizeIds(array_column($existingSub, 'id'));
                                $usedSubIds = [];

                                foreach ($k['subkegiatan'] as $sk) {
                                    $subkegiatanId = (int) ($sk['subkegiatan_id'] ?? 0);
                                    if ($subkegiatanId <= 0) {
                                        continue;
                                    }

                                    $postedSubId = (int) ($sk['pk_subkegiatan_id'] ?? 0);
                                    if ($postedSubId > 0 && in_array($postedSubId, $existingSubIds, true)) {
                                        $db->table('pk_subkegiatan')->where('id', $postedSubId)->update([
                                            'pk_kegiatan_id' => $pkKegId,
                                            'subkegiatan_id' => $subkegiatanId,
                                            'updated_at' => $now
                                        ]);
                                        $pkSubId = $postedSubId;
                                    } else {
                                        $db->table('pk_subkegiatan')->insert([
                                            'pk_kegiatan_id' => $pkKegId,
                                            'subkegiatan_id' => $subkegiatanId,
                                            'created_at' => $now,
                                            'updated_at' => $now
                                        ]);
                                        $pkSubId = (int) $db->insertID();
                                    }

                                    $usedSubIds[] = $pkSubId;
                                }

                                $deleteSub = array_diff($existingSubIds, $usedSubIds);
                                if (!empty($deleteSub)) {
                                    $db->table('pk_subkegiatan')->whereIn('id', $deleteSub)->delete();
                                }
                            } else {
                                $this->deletePkSubkegiatanByKegiatanIds([$pkKegId]);
                            }
                        }

                        $deleteKegiatan = array_diff($existingKegIds, $usedKegIds);
                        $this->deletePkKegiatanBranches($deleteKegiatan);
                    }

                    $deletePrograms = array_diff($existingProgramIds, $usedProgramIds);
                    $this->deletePkProgramBranches($deletePrograms);
                }

                $deleteIndikator = array_diff($existingIndIds, $usedIndIds);
                $this->deletePkIndikatorBranches($deleteIndikator, true);
            }

            $deleteSasaran = array_diff($existingSasaranIds, $usedSasaranIds);
            $this->deletePkSasaranBranches($deleteSasaran);

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }


    /**
     * Get all satuan
     */
    public function getAllSatuan()
    {
        return $this->db->table('satuan')->orderBy('satuan', 'ASC')->get()->getResultArray();
    }

    /**
     * Get all programs (program_pk)
     */
    public function getAllPrograms()
    {
        return $this->db->table('program_pk')->orderBy('program_kegiatan', 'ASC')->get()->getResultArray();
    }

    public function getProgramsForBupatiFromPkJpt(int $tahun)
    {
        return $this->db->table('pk')
            ->select([
                'pk.id AS pk_id',
                'pk.opd_id',
                'opd.nama_opd',
                'pr.id AS program_id',
                'pr.program_kegiatan',
                'pr.anggaran'
            ])
            ->join('opd', 'opd.id = pk.opd_id', 'left')
            ->join('pegawai', 'pegawai.id = pk.pihak_1', 'left') // 🔥 JOIN PEGAWAI
            ->join('pk_sasaran ps', 'ps.pk_id = pk.id', 'inner')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'inner')
            ->join('pk_program pp', 'pp.pk_indikator_id = pi.id', 'inner')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'inner')
            ->where('pk.jenis', 'jpt')
            ->where('pi.jenis', 'jpt')
            ->where('pk.tahun', $tahun)

            // 🔥 KONDISI KHUSUS OPD 2
            ->groupStart()
            ->where('pk.opd_id !=', 2)
            ->orGroupStart()
            ->where('pk.opd_id', 2)
            ->where('pegawai.jabatan_id', 170)
            ->groupEnd()
            ->groupEnd()

            ->groupBy([
                'pk.id',
                'pk.opd_id',
                'opd.nama_opd',
                'pr.id',
                'pr.anggaran'
            ])
            ->orderBy('pk.opd_id', 'ASC')
            ->orderBy('pr.program_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }



    public function getKegiatan()
    {
        return $this->db->table('kegiatan_pk')
            ->select('id, kegiatan, anggaran')
            ->orderBy('kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSubKegiatan()
    {
        return $this->db->table('sub_kegiatan_pk')
            ->select('id, sub_kegiatan, anggaran')
            ->orderBy('sub_kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get JPT programs from existing pk_program entries in this OPD
     * Returns program_pk rows (program_kegiatan + id)
     */
    public function getJptPrograms($opdId)
    {
        return $this->db->table('pk_program')
            ->select('program_pk.program_kegiatan, program_pk.id, program_pk.anggaran')
            ->join('program_pk', 'program_pk.id = pk_program.program_id')
            ->join('pk_indikator', 'pk_indikator.id = pk_program.pk_indikator_id')
            ->join('pk_sasaran', 'pk_sasaran.id = pk_indikator.pk_sasaran_id')
            ->join('pk', 'pk.id = pk_sasaran.pk_id')
            // Program puncak OPD: Dinas 'jpt', Kecamatan 'camat' (satu OPD hanya salah satunya)
            ->whereIn('pk_indikator.jenis', ['jpt', 'camat'])
            ->where('pk.opd_id', $opdId)
            ->orderBy('pk.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getKegiatanAdmin($opdId)
    {
        return $this->db->table('pk_kegiatan pkeg')
            ->select([
                'keg.id',
                'keg.kegiatan',
                'keg.anggaran',
                'pp.program_id AS program_id'
            ])
            ->join('kegiatan_pk keg', 'keg.id = pkeg.kegiatan_id')
            ->join('pk_program pp', 'pp.id = pkeg.pk_program_id')
            ->join('pk_indikator pi', 'pi.id = pp.pk_indikator_id')
            ->join('pk_sasaran ps', 'ps.id = pi.pk_sasaran_id')
            ->join('pk', 'pk.id = ps.pk_id')
            ->where('pi.jenis', 'administrator')
            ->where('pk.opd_id', $opdId)
            ->groupBy('keg.id, pp.program_id')
            ->orderBy('keg.kegiatan', 'ASC')
            ->get()
            ->getResultArray();
    }


    /**
     * Get program by id (wrapper)
     */
    public function getProgramById($id)
    {
        return $this->db->table('program_pk')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Format anggaran (helper)
     */
    public function formatAnggaran($anggaran)
    {
        return 'Rp ' . number_format((float) $anggaran, 0, ',', '.');
    }

    /**
     * Search programs by keyword
     */
    public function searchPrograms($keyword)
    {
        return $this->db->table('program_pk')
            ->like('program_kegiatan', $keyword)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get PK Pimpinan by OPD and jenis (wrapper)
     */
    public function getPkPimpinan($opdId, $referensiJenis)
    {
        return $this->where('opd_id', $opdId)
            ->where('jenis', $referensiJenis)
            ->findAll();
    }

    /**
     * Get jenis_indikator by pk id (fixed to use pk_sasaran join)
     */
    public function getJenisIndikatorByPkId($pkId)
    {
        $rows = $this->db->table('pk_indikator')
            ->select('pk_indikator.id, pk_indikator.jenis_indikator')
            ->join('pk_sasaran', 'pk_sasaran.id = pk_indikator.pk_sasaran_id')
            ->where('pk_sasaran.pk_id', $pkId)
            ->get()
            ->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['jenis_indikator'];
        }
        return $result;
    }

    /**
     * Update capaian indikator (bulk)
     */
    public function updateCapaianIndikator($capaianArr)
    {
        $db = \Config\Database::connect();
        foreach ($capaianArr as $indikatorId => $nilaiCapaian) {
            $db->table('pk_indikator')->where('id', $indikatorId)->update([
                'capaian' => $nilaiCapaian
            ]);
        }
    }

    /**
     * Get summarized PK data by opd + jenis (used by controller)
     */
    public function getCompletePkByOpdIdAndJenis($opdId, $jenis, $tahun)
    {
        $builder = $this->db->table('pk');
        $builder->select('pk.*, opd.nama_opd');
        $builder->join('opd', 'opd.id = pk.opd_id', 'left');
        $builder->where('pk.opd_id', $opdId);
        $builder->where('pk.jenis', $jenis);
        $builder->where('pk.tahun', $tahun);
        $query = $builder->get();

        $results = $query->getResultArray();
        if (empty($results)) {
            return [];
        }

        foreach ($results as &$pk) {
            $pk['sasaran'] = $this->getSasaranByPkId($pk['id']);
            $pk['program'] = $this->getProgramByPkId($pk['id']);
            $pk['kegiatan'] = $this->getKegiatanByPkId($pk['id']);
            $pk['subkegiatan'] = $this->getSubKegiatanByPkId($pk['id']);
        }

        return $results;
    }

    public function getCompletePkById($id)
    {
        $builder = $this->db->table('pk');
        $builder->select('pk.*, opd.nama_opd');
        $builder->join('opd', 'opd.id = pk.opd_id', 'left');
        $builder->where('pk.id', $id);
        $query = $builder->get();

        $results = $query->getResultArray();
        if (empty($results)) {
            return [];
        }

        foreach ($results as &$pk) {
            $pk['sasaran'] = $this->getSasaranByPkId($pk['id']);
            // $pk['program'] = $this->getProgramByPkId($pk['id']);
            // $pk['kegiatan'] = $this->getKegiatanByPkId($pk['id']);
            // $pk['subkegiatan'] = $this->getSubKegiatanByPkId($pk['id']);
        }

        return $results;
    }

    public function getProgramByJenis($pkId, $jenis)
    {
        switch (strtolower($jenis)) {
            case 'bupati':
                $pk = $this->select('tahun')->find($pkId);
                return $pk ? $this->getProgramsForBupatiFromPkJpt((int) $pk['tahun']) : [];

            case 'jpt':
            case 'camat': // Camat = struktur identik JPT (indikator → program)
                return $this->getProgramByPkId($pkId);

            case 'administrator':
                return $this->getKegiatanByPkId($pkId);

            case 'pengawas':
                return $this->getSubKegiatanByPkId($pkId);

            default:
                return [];
        }
    }


    /**
     * getCompletePkByOpdId (detailed join-based summary)
     * Kept for compatibility (returns single merged structure)
     */
    public function getCompletePkByOpdId($opdId)
    {
        $pihak1Jabatan = $this->jabatanPkPltExpr('p', 'is_plt_pihak_1', 'j1');
        $pihak2Jabatan = $this->jabatanPkPltExpr('p', 'is_plt_pihak_2', 'j2');
        $pihak1Plt     = $this->pkPltSelect('p', 'is_plt_pihak_1');
        $pihak2Plt     = $this->pkPltSelect('p', 'is_plt_pihak_2');
        $pihak1Plh     = $this->pkPltSelect('p', 'is_plh_pihak_1');
        $pihak2Plh     = $this->pkPltSelect('p', 'is_plh_pihak_2');
        $pihak1Manual  = $this->pkFieldSelect('p', 'jabatan_pihak_1_manual');
        $pihak2Manual  = $this->pkFieldSelect('p', 'jabatan_pihak_2_manual');

        $query = $this->db->table('pk p')
            ->select("
                p.id as pk_id,
                p.opd_id,
                p.jenis,
                p.tanggal,
                {$pihak1Plt},
                {$pihak2Plt},
                {$pihak1Plh},
                {$pihak2Plh},
                {$pihak1Manual},
                {$pihak2Manual},
                o.nama_opd,

                peg1.id as pihak_1_id,
                peg1.nama_pegawai as pihak_1_nama,
                peg1.nip_pegawai as pihak_1_nip,
                {$pihak1Jabatan} as pihak_1_jabatan,
                pang1.nama_pangkat as pihak_1_pangkat,
                pang1.golongan as pihak_1_golongan,

                peg2.id as pihak_2_id,
                peg2.nama_pegawai as pihak_2_nama,
                peg2.nip_pegawai as pihak_2_nip,
                {$pihak2Jabatan} as pihak_2_jabatan,
                pang2.nama_pangkat as pihak_2_pangkat,
                pang2.golongan as pihak_2_golongan,

                ps.id as sasaran_id,
                ps.sasaran,

                pi.id as indikator_id,
                pi.indikator,
                pi.target,

                pp.id as pk_program_id,
                pr.program_kegiatan,
                pr.anggaran
            ", false)
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan j1', 'j1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan j2', 'j2.id = peg2.jabatan_id', 'left')
            ->join('pangkat pang2', 'pang2.id = peg2.pangkat_id', 'left')
            ->join('pk_sasaran ps', 'ps.pk_id = p.id', 'left')
            ->join('pk_indikator pi', 'pi.pk_sasaran_id = ps.id', 'left')
            ->join('pk_program pp', 'pp.pk_id = p.id', 'left')
            ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
            ->where('p.opd_id', $opdId)
            ->orderBy('ps.id')
            ->orderBy('pi.id')
            ->orderBy('pp.id')
            ->get()
            ->getResultArray();

        if (empty($query)) {
            return null;
        }

        $first = $query[0];
        $result = [
            'pk_id' => $first['pk_id'],
            'opd_id' => $first['opd_id'],
            'nama_opd' => $first['nama_opd'],
            'jenis' => $first['jenis'],
            'tanggal' => $first['tanggal'],
            'pihak_1' => [
                'id' => $first['pihak_1_id'],
                'nama' => $first['pihak_1_nama'],
                'nip' => $first['pihak_1_nip'],
                'jabatan' => $first['pihak_1_jabatan'],
                'pangkat' => $first['pihak_1_pangkat'],
                'golongan' => $first['pihak_1_golongan'],
                'is_plt' => (int) ($first['is_plt_pihak_1'] ?? 0),
                'is_plh' => (int) ($first['is_plh_pihak_1'] ?? 0),
                'jabatan_manual' => $first['jabatan_pihak_1_manual'] ?? null,
            ],
            'pihak_2' => [
                'id' => $first['pihak_2_id'],
                'nama' => $first['pihak_2_nama'],
                'nip' => $first['pihak_2_nip'],
                'jabatan' => $first['pihak_2_jabatan'],
                'pangkat' => $first['pihak_2_pangkat'],
                'golongan' => $first['pihak_2_golongan'],
                'is_plt' => (int) ($first['is_plt_pihak_2'] ?? 0),
                'is_plh' => (int) ($first['is_plh_pihak_2'] ?? 0),
                'jabatan_manual' => $first['jabatan_pihak_2_manual'] ?? null,
            ],
            'sasaran' => [],
            'program' => [],
        ];

        foreach ($query as $row) {
            // Sasaran & indikator
            if ($row['sasaran_id']) {
                $sid = $row['sasaran_id'];
                if (!isset($result['sasaran'][$sid])) {
                    $result['sasaran'][$sid] = [
                        'sasaran_id' => $sid,
                        'sasaran' => $row['sasaran'],
                        'indikator' => []
                    ];
                }

                if (!empty($row['indikator_id'])) {
                    $existingIndikatorIds = array_column($result['sasaran'][$sid]['indikator'], 'indikator_id');
                    if (!in_array($row['indikator_id'], $existingIndikatorIds)) {
                        $result['sasaran'][$sid]['indikator'][] = [
                            'indikator_id' => $row['indikator_id'],
                            'indikator' => $row['indikator'],
                            'target' => $row['target']
                        ];
                    }
                }
            }

            // Program
            if (!empty($row['pk_program_id'])) {
                $existingProgramIds = array_column($result['program'], 'pk_program_id');
                if (!in_array($row['pk_program_id'], $existingProgramIds)) {
                    $result['program'][] = [
                        'pk_program_id' => $row['pk_program_id'],
                        'program_kegiatan' => $row['program_kegiatan'],
                        'anggaran' => $row['anggaran']
                    ];
                }
            }
        }

        // convert sasaran map to numeric array
        $result['sasaran'] = array_values($result['sasaran']);

        return $result;
    }

    public function getPkRelasiByOpdJenisTahun($opdId, $jenis, $tahun)
    {
        $pihak1Jabatan = $this->jabatanPkPltExpr('p', 'is_plt_pihak_1', 'jab1');
        $pihak2Jabatan = $this->jabatanPkPltExpr('p', 'is_plt_pihak_2', 'jab2');

        return $this->db->table('pk p')
            ->select("
            p.id,
            CASE 
                WHEN p.jenis = 'bupati' THEN 
                    CONCAT(peg1.nama_pegawai, ' (', {$pihak1Jabatan}, ')')
                ELSE
                    CONCAT(
                        peg1.nama_pegawai, ' (', {$pihak1Jabatan}, ')',
                        ' ↔ ',
                        peg2.nama_pegawai, ' (', {$pihak2Jabatan}, ')'
                    )
            END AS relasi
        ")
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan jab1', 'jab1.id = peg1.jabatan_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan jab2', 'jab2.id = peg2.jabatan_id', 'left')
            ->where('p.opd_id', $opdId)
            ->where('p.jenis', $jenis)
            ->where('p.tahun', $tahun)
            ->orderBy('peg1.nama_pegawai', 'ASC')
            ->get()
            ->getResultArray();
    }



    /**
     * Get PK by id (detailed)
     */
    public function getPkById($id)
    {
        $pihak1Jabatan = $this->jabatanPkPltExpr('p', 'is_plt_pihak_1', 'jab1');
        $pihak2Jabatan = $this->jabatanPkPltExpr('p', 'is_plt_pihak_2', 'jab2');
        $pihak1Plt     = $this->pkPltSelect('p', 'is_plt_pihak_1');
        $pihak2Plt     = $this->pkPltSelect('p', 'is_plt_pihak_2');
        $pihak1Plh     = $this->pkPltSelect('p', 'is_plh_pihak_1');
        $pihak2Plh     = $this->pkPltSelect('p', 'is_plh_pihak_2');
        $pihak1Manual  = $this->pkFieldSelect('p', 'jabatan_pihak_1_manual');
        $pihak2Manual  = $this->pkFieldSelect('p', 'jabatan_pihak_2_manual');

        $builder = $this->db->table('pk p')
            ->select("
            p.id as pk_id,
            p.jenis,
            p.tahun,
            p.pihak_1,
            p.pihak_2,
            p.opd_id,
            p.tanggal,
            {$pihak1Plt},
            {$pihak2Plt},
            {$pihak1Plh},
            {$pihak2Plh},
            {$pihak1Manual},
            {$pihak2Manual},
            o.nama_opd,
            o.singkatan,

            peg1.nama_pegawai as nama_pihak_1,
            peg1.nip_pegawai as nip_pihak_1,
            {$pihak1Jabatan} as jabatan_pihak_1,
            pang1.nama_pangkat as pangkat_pihak_1,
            pang1.golongan as golongan_pihak_1,

            peg2.nama_pegawai as nama_pihak_2,
            peg2.nip_pegawai as nip_pihak_2,
            {$pihak2Jabatan} as jabatan_pihak_2,
            pang2.nama_pangkat as pangkat_pihak_2,
            pang2.golongan as golongan_pihak_2
        ", false)
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('pegawai peg1', 'peg1.id = p.pihak_1', 'left')
            ->join('jabatan jab1', 'jab1.id = peg1.jabatan_id', 'left')
            ->join('pangkat pang1', 'pang1.id = peg1.pangkat_id', 'left')
            ->join('pegawai peg2', 'peg2.id = p.pihak_2', 'left')
            ->join('jabatan jab2', 'jab2.id = peg2.jabatan_id', 'left')
            ->join('pangkat pang2', 'pang2.id = peg2.pangkat_id', 'left')
            ->where('p.id', $id)
            ->get();

        $pk = $builder->getRowArray();
        if (!$pk) {
            return null;
        }

        // =======================
        // AMBIL SASARAN PK
        // =======================
        $pk['sasaran_pk'] = $this->db->table('pk_sasaran')
            ->where('pk_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($pk['sasaran_pk'] as &$s) {

            // =======================
            // AMBIL INDIKATOR
            // =======================
            $indikatorList = $this->db->table('pk_indikator')
                ->select('pk_indikator.*, satuan.satuan as satuan_nama')
                ->join('satuan', 'satuan.id = pk_indikator.id_satuan', 'left')
                ->where('pk_sasaran_id', $s['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($indikatorList as &$indikator) {

                // format satuan
                $indikator['satuan'] = $indikator['satuan_nama'] ?? ($indikator['id_satuan'] ?? '-');

                // =======================
                // AMBIL PROGRAM
                // =======================
                $programList = $this->db->table('pk_program pp')
                    ->select('
                    pp.id as pk_program_id,
                    pr.program_kegiatan,
                    pr.anggaran,
                    pp.program_id,
                    pp.pk_indikator_id
                ')
                    ->join('program_pk pr', 'pr.id = pp.program_id', 'left')
                    ->where('pp.pk_indikator_id', $indikator['id'])
                    ->orderBy('pp.id', 'ASC')
                    ->get()
                    ->getResultArray();

                // =======================
                // AMBIL KEGIATAN DALAM PROGRAM
                // =======================
                foreach ($programList as &$program) {

                    // 🔥 ambil kegiatan berdasarkan program
                    $kegiatanList = $this->db->table('pk_kegiatan pkeg')
                        ->select('pkeg.id AS pk_kegiatan_id,
                                pkeg.kegiatan_id AS kegiatan_id,
                                keg.kegiatan
                                ')
                        ->join('kegiatan_pk keg', 'keg.id = pkeg.kegiatan_id', 'left')
                        ->where('pkeg.pk_program_id', $program['pk_program_id'])
                        ->orderBy('pkeg.id', 'ASC')
                        ->get()
                        ->getResultArray();

                    // looping kegiatan → ambil subkegiatan
                    foreach ($kegiatanList as &$keg) {

                        $subList = $this->db->table('pk_subkegiatan psub')
                            ->select('psub.id as sub_id,
                                    psub.id as pk_subkegiatan_id,
                                    sub.sub_kegiatan,
                                    sub.anggaran,
                                    psub.pk_kegiatan_id,
                                    psub.subkegiatan_id
                                ')
                            ->join('sub_kegiatan_pk sub', 'sub.id = psub.subkegiatan_id', 'left')
                            ->where('psub.pk_kegiatan_id', $keg['pk_kegiatan_id'])
                            ->orderBy('psub.id', 'ASC')
                            ->get()
                            ->getResultArray();

                        // masukkan subkegiatan ke dalam kegiatan
                        $keg['subkegiatan'] = $subList;
                    }

                    // masukkan kegiatan ke dalam program
                    $program['kegiatan'] = $kegiatanList;
                }

                // simpan program lengkap ke indikator
                $indikator['program'] = $programList;
            }

            // masukkan indikator ke sasaran
            $s['indikator'] = $indikatorList;
        }

        return $pk;
    }
}
