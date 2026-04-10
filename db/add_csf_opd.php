<?php
$db = new mysqli('192.168.100.10', 'user_lan', '123456', 'sakip_new');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Add CSF to renstra_sasaran
$res1 = $db->query("ALTER TABLE renstra_sasaran ADD COLUMN csf TEXT NULL AFTER renstra_tujuan_id");
if ($res1) echo "Added csf to renstra_sasaran\n";
else echo "renstra_sasaran: " . $db->error . "\n";

// Add CSF to cascading_sasaran_opd
$res2 = $db->query("ALTER TABLE cascading_sasaran_opd ADD COLUMN csf TEXT NULL AFTER nama_sasaran");
if ($res2) echo "Added csf to cascading_sasaran_opd\n";
else echo "cascading_sasaran_opd: " . $db->error . "\n";

$db->close();
