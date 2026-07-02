<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kolom 2FA (TOTP authenticator) pada tabel users. Idempoten.
 */
class AddTwoFactorToUsers extends Migration
{
    public function up()
    {
        $db     = \Config\Database::connect();
        $fields = array_column($db->getFieldData('users'), 'name');

        if (!in_array('two_factor_secret', $fields, true)) {
            $this->forge->addColumn('users', [
                'two_factor_secret' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'password'],
            ]);
        }
        if (!in_array('two_factor_enabled', $fields, true)) {
            $this->forge->addColumn('users', [
                'two_factor_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'two_factor_secret'],
            ]);
        }
    }

    public function down()
    {
        $db     = \Config\Database::connect();
        $fields = array_column($db->getFieldData('users'), 'name');
        if (in_array('two_factor_enabled', $fields, true)) {
            $this->forge->dropColumn('users', 'two_factor_enabled');
        }
        if (in_array('two_factor_secret', $fields, true)) {
            $this->forge->dropColumn('users', 'two_factor_secret');
        }
    }
}
