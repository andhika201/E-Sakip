<?php
$mysqli = new mysqli("localhost", "root", "", "test_sakip");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql1 = "ALTER TABLE rkt_subkegiatan ADD COLUMN indikator_sasaran_sub_kegiatan VARCHAR(255) NULL AFTER sub_kegiatan_id";
if ($mysqli->query($sql1) === TRUE) {
    echo "Column indikator_sasaran_sub_kegiatan created successfully\n";
} else {
    echo "Error creating column: " . $mysqli->error . "\n";
}

$sql2 = "ALTER TABLE rkt_subkegiatan ADD COLUMN target VARCHAR(255) NULL AFTER indikator_sasaran_sub_kegiatan";
if ($mysqli->query($sql2) === TRUE) {
    echo "Column target created successfully\n";
} else {
    echo "Error creating column: " . $mysqli->error . "\n";
}

$mysqli->close();
