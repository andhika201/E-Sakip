<?php
$conn = new mysqli('localhost', 'root', '', 'test_sakip');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "SELECT k.id, k.rkt_id, kp.kegiatan, (SELECT COUNT(*) FROM rkt_subkegiatan rs WHERE rs.rkt_kegiatan_id = k.id) as sub_count
        FROM rkt_kegiatan k 
        LEFT JOIN kegiatan_pk kp ON kp.id = k.kegiatan_id
        HAVING sub_count = 0";
$result = $conn->query($sql);
echo "Kegiatans with no subkegiatan:\n";
while($row = $result->fetch_assoc()) {
    print_r($row);
}


