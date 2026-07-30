<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Satuan bertipe PREDIKAT + skala nilainya.
 *
 * Indikator yang target/capaiannya berupa predikat (opini BPK WTP/WDP/TW/TMP,
 * nilai SAKIP AA..D, akreditasi) diberi SKOR per predikat, lalu skornya masuk
 * rumus Capaian Total yang sudah ada — lihat app/Helpers/capaian_helper.php.
 *
 * Kembaran SQL langsungnya (berikut seed Opini BPK & Nilai SAKIP):
 * db/update_2026-07-27_satuan_predikat.sql
 */
class CreateSatuanSkalaTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('satuan') && !$this->db->fieldExists('tipe', 'satuan')) {
            $this->forge->addColumn('satuan', [
                'tipe' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'angka',
                    'comment'    => 'angka | persen | predikat',
                    'after'      => 'satuan',
                ],
            ]);
        }

        if ($this->db->tableExists('satuan_skala')) {
            return;
        }

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'satuan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'kode'      => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'yang ditulis di target/capaian, mis. WTP'],
            'label'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'nilai'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'comment' => 'skor untuk perhitungan persentase'],
            'urutan'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['satuan_id', 'kode'], 'uq_satuan_skala_kode');
        $this->forge->addKey(['satuan_id', 'urutan'], false, false, 'idx_satuan_skala_urut');
        $this->forge->addForeignKey('satuan_id', 'satuan', 'id', 'CASCADE', 'CASCADE', 'fk_satuan_skala_satuan');
        $this->forge->createTable('satuan_skala', true);
    }

    public function down()
    {
        $this->forge->dropTable('satuan_skala', true);

        if ($this->db->tableExists('satuan') && $this->db->fieldExists('tipe', 'satuan')) {
            $this->forge->dropColumn('satuan', 'tipe');
        }
    }
}
