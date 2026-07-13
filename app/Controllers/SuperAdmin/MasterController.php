<?php

namespace App\Controllers\SuperAdmin;

use App\Controllers\BaseController;
use App\Models\PegawaiModel;
use App\Models\OpdModel;
use App\Models\SatuanModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use Config\Database;

/**
 * Panel Super Admin "satu tampilan" (tabbed) untuk master data:
 * Pegawai, Pangkat, Jabatan, OPD, User, Role & Permission, Satuan.
 *
 * Tiap entitas memakai endpoint save (create/update gabung) & delete sendiri,
 * lalu redirect kembali ke tab terkait dengan flash message.
 */
class MasterController extends BaseController
{
    protected PegawaiModel $pegawaiModel;
    protected OpdModel $opdModel;
    protected SatuanModel $satuanModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    /** @var \CodeIgniter\Database\BaseConnection */
    protected $db;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->opdModel     = new OpdModel();
        $this->satuanModel  = new SatuanModel();
        $this->userModel    = new UserModel();
        $this->roleModel    = new RoleModel();
        $this->db           = Database::connect();
    }

    private function isSafeText($val): bool
    {
        if ($val === null || $val === '') {
            return true;
        }
        return (bool) preg_match(
            '/^(?!.*<\s*script\b)(?!.*<\/\s*script\s*>)(?!.*javascript\s*:)(?!.*data\s*:\s*text\/html)(?!.*on\w+\s*=)(?!.*<\?php)(?!.*<\?).*$/is',
            (string) $val
        );
    }

    private function back(string $tab, string $type, string $msg)
    {
        return redirect()->to('adminkab/master?tab=' . $tab)->with($type, $msg);
    }

    /* ===================== HALAMAN UTAMA ===================== */
    public function index()
    {
        $opdAll     = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $pangkatAll = $this->db->table('pangkat')->orderBy('id', 'ASC')->get()->getResultArray();
        $jabatanAll = $this->pegawaiModel->getJabatanList();

        // Tidak men-join users<->roles via string (beda collation). Label role
        // di-resolve di PHP dari daftar roles.
        $usersAll = $this->db->table('users u')
            ->select('u.user_id, u.username, u.email, u.role, u.opd_id, u.is_active, o.nama_opd')
            ->join('opd o', 'o.id = u.opd_id', 'left')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        // roles + jumlah user (PHP) + label map
        $roles         = $this->roleModel->getRolesWithCount();
        $userCountByRole = $this->roleModel->userCountByRole();
        $roleLabelByName = [];
        foreach ($roles as &$r) {
            $r['user_count'] = $userCountByRole[$r['name']] ?? 0;
            $roleLabelByName[$r['name']] = $r['label'] ?? $r['name'];
        }
        unset($r);
        foreach ($usersAll as &$u) {
            $u['role_label'] = $roleLabelByName[$u['role']] ?? $u['role'];
        }
        unset($u);

        $data = [
            'title'           => 'Master Data (Super Admin)',
            'activeTab'       => $this->request->getGet('tab') ?: 'pegawai',
            'simpegConfigured' => (new \App\Libraries\Pegawai\SimpegClient())->isConfigured(),

            'pegawai'        => [], // dimuat via DataTables server-side (pegawaiData)
            'pangkat'        => $pangkatAll,
            'jabatan'        => $jabatanAll,
            'opd'            => $opdAll,
            'users'          => $usersAll,
            'satuan'         => $this->satuanModel->getAllSatuan(),

            'roles'          => $roles,
            'permissions'    => $this->roleModel->allPermissions(),
            'rolePermMap'    => $this->buildRolePermMap(),

            // opsi untuk dropdown
            'opdOptions'     => $opdAll,
            'jabatanOptions' => $jabatanAll,
            'pangkatOptions' => $pangkatAll,
            'roleOptions'    => $this->db->table('roles')->orderBy('label', 'ASC')->get()->getResultArray(),
        ];

        return view('adminKabupaten/master/index', $data);
    }

    /** map role_id => [permission_id,...] */
    private function buildRolePermMap(): array
    {
        $rows = $this->db->table('role_permissions')->get()->getResultArray();
        $map  = [];
        foreach ($rows as $r) {
            $map[(int) $r['role_id']][] = (int) $r['permission_id'];
        }
        return $map;
    }

    /**
     * Endpoint DataTables server-side untuk tab Pegawai (data besar, ribuan baris).
     * Mendukung search, sort, dan pagination di sisi server.
     */
    public function pegawaiData()
    {
        $req    = $this->request;
        $draw   = (int) $req->getGet('draw');
        $start  = (int) $req->getGet('start');
        $length = (int) $req->getGet('length');
        if ($length < 1) { $length = 10; }
        $searchVal = (string) ($req->getGet('search')['value'] ?? '');

        $orderable = [1 => 'p.nama_pegawai', 2 => 'p.nip_pegawai', 3 => 'o.nama_opd', 4 => 'j.nama_jabatan', 5 => 'pk.nama_pangkat', 6 => 'p.level'];
        $orderIdx  = (int) ($req->getGet('order')[0]['column'] ?? 1);
        $orderDir  = strtolower((string) ($req->getGet('order')[0]['dir'] ?? 'asc'));
        if (!in_array($orderDir, ['asc', 'desc'], true)) { $orderDir = 'asc'; }
        $orderCol = $orderable[$orderIdx] ?? 'p.nama_pegawai';

        $builder = $this->db->table('pegawai p')
            ->select('p.id, p.nama_pegawai, p.nip_pegawai, p.opd_id, p.jabatan_id, p.pangkat_id, p.level, p.is_plt, o.nama_opd, j.nama_jabatan, pk.nama_pangkat')
            ->join('opd o', 'o.id = p.opd_id', 'left')
            ->join('jabatan j', 'j.id = p.jabatan_id', 'left')
            ->join('pangkat pk', 'pk.id = p.pangkat_id', 'left');

        if ($searchVal !== '') {
            $builder->groupStart()
                ->like('p.nama_pegawai', $searchVal)
                ->orLike('p.nip_pegawai', $searchVal)
                ->orLike('o.nama_opd', $searchVal)
                ->orLike('j.nama_jabatan', $searchVal)
                ->orLike('pk.nama_pangkat', $searchVal)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);
        $rows = $builder->orderBy($orderCol, $orderDir)->get($length, $start)->getResultArray();
        $recordsTotal = $this->db->table('pegawai')->countAllResults();

        $data = [];
        $no   = $start;
        foreach ($rows as $r) {
            $no++;
            $json = htmlspecialchars(json_encode([
                'id'           => $r['id'],
                'nama_pegawai' => $r['nama_pegawai'],
                'nip_pegawai'  => $r['nip_pegawai'],
                'opd_id'       => $r['opd_id'],
                'jabatan_id'   => $r['jabatan_id'],
                'pangkat_id'   => $r['pangkat_id'],
                'level'        => $r['level'],
                'is_plt'       => $r['is_plt'],
            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES);

            $delUrl = base_url('adminkab/master/pegawai/delete/' . (int) $r['id']);
            $aksi   = '<button class="btn btn-warning btn-sm" data-edit="modal-pegawai" data-json="' . $json . '"><i class="fas fa-edit"></i></button> '
                . '<a href="' . $delUrl . '" onclick="return confirm(\'Yakin hapus data ini?\')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>';

            $data[] = [
                'no'      => $no,
                'nama'    => esc($r['nama_pegawai']),
                'nip'     => esc($r['nip_pegawai']),
                'opd'     => esc($r['nama_opd'] ?? '-'),
                'jabatan' => esc($r['nama_jabatan'] ?? '-') . ($r['is_plt'] == 1 ? ' (Plt.)' : ''),
                'pangkat' => esc($r['nama_pangkat'] ?? '-'),
                'level'   => esc($r['level'] ?? '-'),
                'aksi'    => $aksi,
            ];
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /* ===================== PEGAWAI ===================== */
    public function pegawaiSave()
    {
        $id   = (int) $this->request->getPost('id');
        $nama = trim((string) $this->request->getPost('nama_pegawai'));
        $nip  = trim((string) $this->request->getPost('nip_pegawai'));

        if ($nama === '' || $nip === '') {
            return $this->back('pegawai', 'error', 'Nama & NIP pegawai wajib diisi.');
        }
        if (!$this->isSafeText($nama)) {
            return $this->back('pegawai', 'error', 'Nama pegawai mengandung input berbahaya.');
        }

        $payload = [
            'nama_pegawai' => $nama,
            'nip_pegawai'  => $nip,
            'opd_id'       => (int) $this->request->getPost('opd_id'),
            'jabatan_id'   => (int) $this->request->getPost('jabatan_id'),
            'pangkat_id'   => (int) $this->request->getPost('pangkat_id'),
            'level'        => $this->request->getPost('level') ?: 'USER',
            'is_plt'       => (int) $this->request->getPost('is_plt'),
        ];

        if ($id) {
            $this->db->table('pegawai')->where('id', $id)->update($payload + ['updated_at' => date('Y-m-d H:i:s')]);
            return $this->back('pegawai', 'success', 'Data pegawai diperbarui.');
        }

        // create: lengkapi kolom NOT NULL dengan default aman
        $payload += [
            'password'   => password_hash($nip, PASSWORD_DEFAULT),
            'tukin'      => 0,
            'first_time' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('pegawai')->insert($payload);
        return $this->back('pegawai', 'success', 'Pegawai baru ditambahkan (password awal = NIP).');
    }

    public function pegawaiDelete($id = null)
    {
        $this->db->table('pegawai')->where('id', (int) $id)->delete();
        return $this->back('pegawai', 'success', 'Data pegawai dihapus.');
    }

    /* ===================== PANGKAT ===================== */
    public function pangkatSave()
    {
        $id   = (int) $this->request->getPost('id');
        $nama = trim((string) $this->request->getPost('nama_pangkat'));
        $gol  = trim((string) $this->request->getPost('golongan'));

        if ($nama === '') {
            return $this->back('pangkat', 'error', 'Nama pangkat wajib diisi.');
        }

        $payload = ['nama_pangkat' => $nama, 'golongan' => $gol, 'updated_at' => date('Y-m-d H:i:s')];

        if ($id) {
            $this->db->table('pangkat')->where('id', $id)->update($payload);
            return $this->back('pangkat', 'success', 'Pangkat diperbarui.');
        }
        $this->db->table('pangkat')->insert($payload + ['created_at' => date('Y-m-d H:i:s')]);
        return $this->back('pangkat', 'success', 'Pangkat ditambahkan.');
    }

    public function pangkatDelete($id = null)
    {
        $this->db->table('pangkat')->where('id', (int) $id)->delete();
        return $this->back('pangkat', 'success', 'Pangkat dihapus.');
    }

    /* ===================== JABATAN ===================== */
    public function jabatanSave()
    {
        $id   = (int) $this->request->getPost('id');
        $nama = trim((string) $this->request->getPost('nama_jabatan'));
        if ($nama === '') {
            return $this->back('jabatan', 'error', 'Nama jabatan wajib diisi.');
        }
        if (!$this->isSafeText($nama)) {
            return $this->back('jabatan', 'error', 'Nama jabatan mengandung input berbahaya.');
        }

        $opdId  = $this->request->getPost('opd_id');
        $eselon = trim((string) $this->request->getPost('eselon'));
        if (!$this->isSafeText($eselon)) {
            return $this->back('jabatan', 'error', 'Eselon mengandung input berbahaya.');
        }
        $payload = [
            'nama_jabatan' => $nama,
            'opd_id'       => ($opdId === '' || $opdId === null) ? null : (int) $opdId,
            'eselon'       => $eselon === '' ? null : $eselon,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            $this->db->table('jabatan')->where('id', $id)->update($payload);
            return $this->back('jabatan', 'success', 'Jabatan diperbarui.');
        }
        $this->db->table('jabatan')->insert($payload + ['created_at' => date('Y-m-d H:i:s')]);
        return $this->back('jabatan', 'success', 'Jabatan ditambahkan.');
    }

    public function jabatanDelete($id = null)
    {
        $this->db->table('jabatan')->where('id', (int) $id)->delete();
        return $this->back('jabatan', 'success', 'Jabatan dihapus.');
    }

    /* ===================== OPD ===================== */
    public function opdSave()
    {
        $id   = (int) $this->request->getPost('id');
        $nama = trim((string) $this->request->getPost('nama_opd'));
        if ($nama === '') {
            return $this->back('opd', 'error', 'Nama OPD wajib diisi.');
        }
        if (!$this->isSafeText($nama)) {
            return $this->back('opd', 'error', 'Nama OPD mengandung input berbahaya.');
        }

        $payload = [
            'nama_opd'   => $nama,
            'singkatan'  => trim((string) $this->request->getPost('singkatan')) ?: null,
            'alamat_opd' => trim((string) $this->request->getPost('alamat_opd')) ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            $this->db->table('opd')->where('id', $id)->update($payload);
            return $this->back('opd', 'success', 'OPD diperbarui.');
        }
        $this->db->table('opd')->insert($payload + ['created_at' => date('Y-m-d H:i:s')]);
        return $this->back('opd', 'success', 'OPD ditambahkan.');
    }

    public function opdDelete($id = null)
    {
        $id = (int) $id;
        $dipakai = $this->db->table('pegawai')->where('opd_id', $id)->countAllResults()
            + $this->db->table('users')->where('opd_id', $id)->countAllResults();
        if ($dipakai > 0) {
            return $this->back('opd', 'error', "OPD tidak bisa dihapus karena masih dipakai {$dipakai} pegawai/user.");
        }
        $this->db->table('opd')->where('id', $id)->delete();
        return $this->back('opd', 'success', 'OPD dihapus.');
    }

    /* ===================== USER ===================== */
    public function userSave()
    {
        $id       = (int) $this->request->getPost('id');
        $username = trim((string) $this->request->getPost('username'));
        $email    = trim((string) $this->request->getPost('email'));
        $role     = trim((string) $this->request->getPost('role'));
        $password = (string) $this->request->getPost('password');
        $opdId    = $this->request->getPost('opd_id');
        $isActive = $this->request->getPost('is_active') ? 1 : 0;

        if ($username === '' || $email === '' || $role === '') {
            return $this->back('user', 'error', 'Username, email, dan role wajib diisi.');
        }
        // role harus ada di tabel roles
        $roleExists = $this->db->table('roles')->where('name', $role)->countAllResults();
        if (!$roleExists) {
            return $this->back('user', 'error', 'Role tidak valid.');
        }

        // keunikan username/email (kecuali dirinya sendiri)
        $dupU = $this->db->table('users')->where('username', $username);
        if ($id) { $dupU->where('user_id !=', $id); }
        if ($dupU->countAllResults() > 0) {
            return $this->back('user', 'error', 'Username sudah digunakan.');
        }
        $dupE = $this->db->table('users')->where('email', $email);
        if ($id) { $dupE->where('user_id !=', $id); }
        if ($dupE->countAllResults() > 0) {
            return $this->back('user', 'error', 'Email sudah digunakan.');
        }

        $payload = [
            'username'  => $username,
            'email'     => $email,
            'role'      => $role,
            'opd_id'    => ($opdId === '' || $opdId === null) ? null : (int) $opdId,
            'is_active' => $isActive,
        ];

        if ($id) {
            if ($password !== '') {
                if (strlen($password) < 6) {
                    return $this->back('user', 'error', 'Password minimal 6 karakter.');
                }
                $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('users')->where('user_id', $id)->update($payload);
            return $this->back('user', 'success', 'User diperbarui.');
        }

        // create: password wajib
        if (strlen($password) < 6) {
            return $this->back('user', 'error', 'Password wajib diisi (minimal 6 karakter) untuk user baru.');
        }
        $payload['password']   = password_hash($password, PASSWORD_DEFAULT);
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('users')->insert($payload);
        return $this->back('user', 'success', 'User baru ditambahkan.');
    }

    public function userDelete($id = null)
    {
        $id = (int) $id;
        if ($id === (int) session()->get('user_id')) {
            return $this->back('user', 'error', 'Tidak bisa menghapus akun Anda sendiri.');
        }
        $this->db->table('users')->where('user_id', $id)->delete();
        return $this->back('user', 'success', 'User dihapus.');
    }

    /* ===================== ROLE & PERMISSION ===================== */
    public function roleSave()
    {
        $id    = (int) $this->request->getPost('id');
        $name  = trim((string) $this->request->getPost('name'));
        $label = trim((string) $this->request->getPost('label'));

        if ($name === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            return $this->back('role', 'error', 'Slug role wajib & hanya boleh huruf/angka/-/_.');
        }

        $dup = $this->db->table('roles')->where('name', $name);
        if ($id) { $dup->where('id !=', $id); }
        if ($dup->countAllResults() > 0) {
            return $this->back('role', 'error', 'Slug role sudah digunakan.');
        }

        $payload = ['name' => $name, 'label' => $label ?: $name, 'updated_at' => date('Y-m-d H:i:s')];

        if ($id) {
            $row = $this->db->table('roles')->where('id', $id)->get()->getRowArray();
            // role sistem: jangan ubah slug (dipakai users.role & AuthFilter)
            if ($row && (int) $row['is_system'] === 1) {
                unset($payload['name']);
            }
            $this->db->table('roles')->where('id', $id)->update($payload);
            return $this->back('role', 'success', 'Role diperbarui.');
        }
        $payload['is_system'] = 0;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->table('roles')->insert($payload);
        return $this->back('role', 'success', 'Role ditambahkan.');
    }

    public function roleDelete($id = null)
    {
        $id  = (int) $id;
        $row = $this->db->table('roles')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->back('role', 'error', 'Role tidak ditemukan.');
        }
        if ((int) $row['is_system'] === 1) {
            return $this->back('role', 'error', 'Role sistem tidak bisa dihapus.');
        }
        $dipakai = $this->db->table('users')->where('role', $row['name'])->countAllResults();
        if ($dipakai > 0) {
            return $this->back('role', 'error', "Role masih dipakai {$dipakai} user.");
        }
        $this->db->table('role_permissions')->where('role_id', $id)->delete();
        $this->db->table('roles')->where('id', $id)->delete();
        return $this->back('role', 'success', 'Role dihapus.');
    }

    /** Simpan matriks permission (checkbox) untuk semua role sekaligus. */
    public function rolePermSave()
    {
        // input: perm[role_id][] = permission_id
        $matrix  = $this->request->getPost('perm') ?? [];
        $roles   = $this->db->table('roles')->select('id, name')->get()->getResultArray();
        $allPids = array_map('intval', array_column(
            $this->db->table('permissions')->select('id')->get()->getResultArray(),
            'id'
        ));

        foreach ($roles as $r) {
            $rid = (int) $r['id'];
            if ($r['name'] === 'admin') {
                // super admin selalu punya semua izin (checkbox-nya disabled di form)
                $this->roleModel->syncPermissions($rid, $allPids);
                continue;
            }
            $checked = $matrix[$rid] ?? [];
            $this->roleModel->syncPermissions($rid, is_array($checked) ? $checked : []);
        }

        return $this->back('role', 'success', 'Matriks permission disimpan.');
    }

    /* ===================== SATUAN ===================== */
    public function satuanSave()
    {
        $id     = (int) $this->request->getPost('id');
        $satuan = trim((string) $this->request->getPost('satuan'));
        if ($satuan === '') {
            return $this->back('satuan', 'error', 'Nama satuan wajib diisi.');
        }

        if ($id) {
            $this->db->table('satuan')->where('id', $id)->update(['satuan' => $satuan]);
            return $this->back('satuan', 'success', 'Satuan diperbarui.');
        }
        $this->db->table('satuan')->insert(['satuan' => $satuan]);
        return $this->back('satuan', 'success', 'Satuan ditambahkan.');
    }

    public function satuanDelete($id = null)
    {
        $this->db->table('satuan')->where('id', (int) $id)->delete();
        return $this->back('satuan', 'success', 'Satuan dihapus.');
    }
}
