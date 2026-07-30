# Page 1


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 1
AKSARA Executive Control Center
Functional Blueprint Sprint Juli 2026
Penguatan Monitoring Realisasi Perjanjian Kinerja Berjenjang
Fokus dokumen: menyesuaikan blueprint dengan kondisi existing data Kabupaten Pringsewu. AKSARA
difokuskan sebagai Monitoring Engine dan Executive Dashboard yang menampilkan monitoring realisasi
PK berjenjang, dengan perhitungan Realisasi PK Bupati tetap mengikuti formula pengukuran yang
ditetapkan Bagian Organisasi.
Arsitektur Ringkas
Data AKSARA Existing
↓
Monitoring Engine
↓
Rule-Based Engine
↓
Executive Dashboard
↓
Pimpinan
Versi v1 - Revisi Mekanisme Agregasi Berdasarkan Kondisi Existing


# Page 2


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 2
Definisi Operasional
Bagian ini mengunci pengertian beberapa istilah utama agar tidak terjadi perbedaan tafsir dalam membaca
workflow, dashboard, dan mekanisme agregasi. Bagian ini juga membedakan antara Realisasi Renaksi dan
Realisasi PK, karena keduanya tidak selalu identik dalam kondisi data existing.
Istilah
Definisi
Renaksi
Rencana Aksi sebagai aktivitas operasional untuk mendukung pencapaian target Perjanjian
Kinerja.
Realisasi Renaksi
Capaian pelaksanaan Renaksi yang diinput oleh perangkat daerah. Data ini menjadi sumber
utama untuk monitoring pelaksanaan, status update, drill-down, dan early warning.
Realisasi PK
Capaian atas indikator Perjanjian Kinerja berdasarkan formula pengukuran indikator. Dalam
kondisi existing, khususnya pada level Bupati, Realisasi PK tidak otomatis dihitung dari
agregasi Realisasi Renaksi, tetapi mengikuti formula yang ditetapkan/validasi Bagian
Organisasi.
Monitoring Engine
Mekanisme AKSARA untuk menelusuri keterhubungan PK, Renaksi, Realisasi Renaksi, status
update, dan unit pengampu sebagai dasar monitoring berjenjang.
Formula PK
Rumus atau mekanisme penghitungan capaian indikator PK sesuai karakteristik indikator,
misalnya indeks, persentase, nilai, atau data sektoral. Formula ini menjadi dasar
perhitungan Realisasi PK.
Rule-Based Engine
Mekanisme aturan sederhana untuk menentukan status warna, prioritas pimpinan, dan
early warning berdasarkan data Monitoring Engine.
Executive Dashboard
Media visualisasi hasil Monitoring Engine, Formula PK, dan Rule-Based Engine untuk
mendukung pengendalian pimpinan.
Catatan terminologi: istilah 'Realisasi' pada diagram dan narasi dokumen ini dibaca sebagai Realisasi Renaksi, kecuali jika
disebutkan berbeda secara eksplisit sebagai Realisasi PK.


# Page 3


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 3
1. Latar Belakang
Berdasarkan arahan Kementerian PANRB, AKSARA perlu diperkuat agar tidak hanya memonitor realisasi Perjanjian
Kinerja pada level Kepala Perangkat Daerah, tetapi mampu menampilkan monitoring realisasi PK secara berjenjang
dari Bupati sampai minimal Eselon III.
Dalam pengembangan internal Kominfo, struktur monitoring telah diarahkan sampai Eselon IV apabila data
tersedia. Hal ini menjadi nilai tambah, selama tidak mengganggu kebutuhan utama Sprint Juli. Berdasarkan diskusi
teknis dan kondisi existing data, fokus Sprint ini disesuaikan: AKSARA memperkuat Monitoring Engine, sedangkan
perhitungan Realisasi PK, khususnya level Bupati, tetap mengikuti formula pengukuran yang ditetapkan Bagian
Organisasi.
Prinsip kunci: dashboard adalah wajah visual. Yang harus hidup terlebih dahulu adalah Monitoring
Engine, lineage data, dan formula pengukuran yang dapat dipertanggungjawabkan.
2. Tujuan Pengembangan
• Menampilkan monitoring PK Bupati, Eselon II/Kepala OPD, Eselon III, dan Eselon IV apabila tersedia.
• Menelusuri keterhubungan PK sampai ke Renaksi dan Realisasi Renaksi.
• Menampilkan Realisasi PK berdasarkan formula pengukuran indikator yang disepakati/validasi Bagian Organisasi.
• Menyediakan status monitoring sederhana bagi pimpinan.
• Menampilkan early warning berbasis rule, bukan AI analysis.
• Menjaga pengembangan tetap realistis sesuai kondisi existing data.
3. Prinsip Pengembangan
Prinsip
Makna Operasional
Single Source of Truth
Untuk monitoring pelaksanaan, sistem membaca Renaksi dan Realisasi Renaksi sebagai
sumber utama. Untuk Realisasi PK, sistem mengikuti formula pengukuran yang telah
ditetapkan.
No Double Entry untuk PD
Perangkat Daerah tidak diberi beban input baru hanya untuk kebutuhan dashboard.
Penyesuaian formula/validasi Realisasi PK dikelola pada level pengendalian.
Monitoring Before Dashboard
Relasi PK - Renaksi - Realisasi Renaksi dipastikan hidup terlebih dahulu; visual dashboard
mengikuti.
Formula Before Automation
Perhitungan PK tidak dipaksakan otomatis dari agregasi Renaksi jika data lineage belum
memadai.
Flexible Hierarchy
Minimal sampai Eselon III; Eselon IV ditampilkan jika tersedia, bukan dipaksakan.
AI Deferred
AI analysis ditunda sampai data, rule, knowledge base, dan formula monitoring stabil.


# Page 4


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 4
4. Arsitektur Monitoring Berjenjang
Struktur monitoring diarahkan mengikuti hirarki kinerja dan organisasi. Sistem tidak boleh memaksa level yang
belum tersedia di database. Monitoring Engine berfungsi menelusuri dukungan Renaksi terhadap PK, sementara
Realisasi PK ditampilkan berdasarkan formula indikator yang sesuai.
Lineage Monitoring
PK Bupati
↓
PK Eselon II / Kepala Perangkat Daerah
↓
PK Eselon III
↓
PK Eselon IV (jika tersedia)
↓
Renaksi
↓
Realisasi Renaksi
Catatan: kebutuhan minimum Kementerian PANRB adalah monitoring sampai Eselon III. Dukungan Eselon IV merupakan penguatan
tambahan dan harus bersifat fleksibel.
4.1 Pemisahan Monitoring dan Perhitungan PK
Berdasarkan kondisi existing, Realisasi Renaksi belum dapat dipaksa menjadi satu-satunya dasar perhitungan
otomatis Realisasi PK Bupati. Beberapa indikator PK Bupati memiliki karakteristik khusus, seperti indeks, nilai
komposit, data sektoral, atau formulasi lain yang tidak selalu dapat dihitung dengan penjumlahan atau rata-rata
Realisasi Renaksi.
Komponen
Fungsi
Monitoring Engine
Menampilkan keterhubungan PK - Renaksi - Realisasi Renaksi, status update, unit
pengampu, dan drill-down.
Formula PK
Menghasilkan Realisasi PK berdasarkan rumus/ketentuan pengukuran indikator yang
ditetapkan Bagian Organisasi.
Executive Dashboard
Menampilkan hasil Monitoring Engine dan Realisasi PK secara berdampingan untuk
kebutuhan pengendalian pimpinan.


# Page 5


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 5
5. Spesifikasi Komponen Dashboard
Setiap komponen dashboard harus memiliki sumber data, logika, output, dan aksi drill-down yang jelas. Dengan
pendekatan ini, dashboard tidak hanya menjadi tampilan visual, tetapi memiliki dasar data, formula, dan alur
penelusuran yang dapat dipertanggungjawabkan.
5.1 Card - Status Realisasi PK Bupati
Aspek
Uraian
Tujuan
Menampilkan posisi capaian PK Bupati secara ringkas sebagai indikator pengendalian pimpinan
tertinggi.
Sumber Data
PK Bupati, formula pengukuran indikator PK Bupati, OPD pengampu, struktur PK Eselon II/III/IV,
Renaksi, dan Realisasi Renaksi periode berjalan sebagai data monitoring pendukung.
Output
Nilai Realisasi PK Bupati berdasarkan formula, status warna, indikator perlu perhatian, dan
akses drill-down ke OPD/renaksi pendukung.
Catatan
Realisasi PK Bupati tidak dipaksakan dihitung otomatis dari agregasi Realisasi Renaksi apabila
formula indikator belum memungkinkan.
PK Bupati
→Formula PK
→Realisasi PK
→OPD
Pengampu
→Renaksi
→Realisasi
Renaksi
Workflow ringkas komponen dashboard.
5.2 Card - Status Realisasi PK Eselon II / Kepala OPD
Aspek
Uraian
Tujuan
Menampilkan capaian PK masing-masing Kepala Perangkat Daerah serta status dukungan
Renaksi.
Sumber Data
PK Kepala OPD, formula indikator PK OPD jika tersedia, unit pengampu, Renaksi, Realisasi
Renaksi, target periode.
Output
Capaian per OPD, status warna, indikator belum optimal, OPD belum update, dan drill-down ke
unit.
Catatan
Jika formula capaian PK OPD belum tersedia penuh, dashboard tetap dapat menampilkan status
monitoring Renaksi sebagai indikator pengendalian awal.
OPD
→Indikator PK
→Formula/Targ
et
→Renaksi
→Realisasi
Renaksi
→Status OPD
Workflow ringkas komponen dashboard.


# Page 6


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 6
5.3 Card - Status Realisasi PK Eselon III
Aspek
Uraian
Tujuan
Memastikan monitoring tidak berhenti pada level Kepala OPD dan dapat ditelusuri ke unit
pelaksana.
Sumber Data
PK Eselon III, relasi Renaksi, Realisasi Renaksi periode berjalan, target periode, dan formula
indikator jika tersedia.
Output
Capaian/status per unit Eselon III, status pelaporan, indikator terlambat atau di bawah target.
Eselon III
→Indikator PK
→Renaksi
→Realisasi
Renaksi
→Rule Status
→Status Unit
Workflow ringkas komponen dashboard.
5.4 Card - Status Realisasi PK Eselon IV
Aspek
Uraian
Tujuan
Menampilkan monitoring lebih rinci apabila struktur data tersedia.
Sumber Data
PK Eselon IV, Renaksi, Realisasi Renaksi, relasi ke Eselon III.
Output
Capaian/status per sub-unit dan kontribusi monitoring terhadap Eselon III.
Catatan
Eselon IV adalah penguatan tambahan. Kebutuhan minimal Eselon III tidak boleh terganggu oleh
pengejaran level tambahan.
Eselon IV
→Indikator/Sub
-output
→Renaksi
→Realisasi
Renaksi
→Kontribusi
→Status
Workflow ringkas komponen dashboard.
5.5 Card - OPD / Unit Belum Update
Aspek
Uraian
Tujuan
Mengetahui OPD atau unit yang belum memperbarui Renaksi/Realisasi Renaksi pada periode
berjalan.
Sumber Data
Periode Renaksi, Realisasi Renaksi, timestamp update, status pelaporan, struktur OPD/unit.
Output
Daftar OPD/unit belum update dan jumlahnya per periode.
Rule/Logika
Jika pada periode berjalan belum ada update atau Realisasi Renaksi, unit masuk daftar belum
update.
Periode Aktif
→Cek Update
→OPD/Unit
→Status
→Daftar
→Tindak Lanjut
Workflow ringkas komponen dashboard.


# Page 7


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 7
5.6 Card - Early Warning
Aspek
Uraian
Tujuan
Memberi tanda awal mengenai potensi masalah pelaksanaan kinerja.
Sumber Data
Target periode, Realisasi Renaksi periode, status update, formula/status PK jika tersedia, dan
ambang batas capaian.
Output
Status hijau, kuning, merah pada indikator/unit terkait.
Rule/Logika
Hijau = sesuai/di atas target; Kuning = belum lengkap/mendekati batas/perlu perhatian; Merah
= belum update/terlambat/di bawah target.
Target
→Realisasi
Renaksi
→Cek Rule
→Hijau/Kuning/
Merah
→Dashboard
Workflow ringkas komponen dashboard.
5.7 Card - Prioritas Pimpinan
Aspek
Uraian
Tujuan
Menampilkan 3-5 isu pengendalian yang paling perlu dilihat pimpinan.
Sumber Data
Hasil Monitoring Engine, Formula PK, dan Rule-Based Engine: capaian, status update, indikator
merah/kuning, unit belum update.
Output
Daftar prioritas ringkas berbasis rule, bukan AI analysis.
Rule/Logika
Contoh: 3 OPD belum update; 5 unit Eselon III belum update; 2 indikator PK Bupati di bawah
target formula periode.
Monitoring
Engine
→Formula PK
→Rule-Based
→Template
Prioritas
→Dashboard
Workflow ringkas komponen dashboard.
Batas penting: Prioritas Pimpinan bukan analisis penyebab. Ia hanya daftar kondisi yang harus segera dilihat pimpinan.
6. Mekanisme Agregasi dan Formula Capaian
Mekanisme pada Sprint Juli disesuaikan dengan kondisi existing data. Realisasi Renaksi digunakan sebagai dasar
monitoring pelaksanaan, drill-down, status update, dan early warning. Namun, Realisasi PK, khususnya PK Bupati,
tidak dipaksakan dihitung otomatis melalui agregasi Realisasi Renaksi apabila formula indikator belum
memungkinkan.
Jalan tengah yang digunakan adalah memisahkan alur monitoring dan alur perhitungan Realisasi PK.
Dengan cara ini, dashboard tetap dapat menampilkan monitoring yang bersih, sementara Realisasi PK
tetap mengikuti formula pengukuran yang dapat dipertanggungjawabkan.
Alur Monitoring
Alur Perhitungan Realisasi PK
Renaksi
↓
Realisasi Renaksi
↓
Monitoring Engine
↓
Status Monitoring
↓
Dashboard Monitoring
Formula PK
↓
Validasi Bagian Organisasi
↓
Realisasi PK
↓
Status Capaian
↓
Dashboard PK
• Realisasi Renaksi menjadi dasar monitoring pelaksanaan dan penelusuran sumber capaian.
• Realisasi PK Bupati mengikuti formula pengukuran indikator yang disepakati dan/atau divalidasi Bagian
Organisasi.
• Jika formula indikator sudah dapat dihitung otomatis dari data existing, perhitungan dapat diotomatisasi secara
bertahap.


# Page 8


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 8
• Jika formula belum siap, dashboard tetap menampilkan status monitoring, data pendukung, dan ruang validasi
formula.
• Data belum lengkap tidak boleh otomatis dianggap hijau.
6.1 Implikasi terhadap Dashboard
Area
Implikasi
PK Bupati
Menampilkan Realisasi PK berbasis formula; drill-down menampilkan OPD/Renaksi
pendukung sebagai konteks monitoring.
PK Eselon II/III/IV
Menampilkan capaian/status sesuai formula atau status monitoring yang tersedia. Tidak
memaksa otomatisasi jika data belum matang.
Early Warning
Tetap berjalan dari status update, keterlambatan, capaian di bawah ambang, dan data
belum lengkap.
Prioritas Pimpinan
Mengambil ringkasan dari kondisi formula PK dan monitoring Renaksi, bukan dari AI
analysis.


# Page 9


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 9
7. Mekanisme Drill-down
Drill-down digunakan untuk menelusuri sumber capaian, bukan sekadar fitur navigasi visual, tetapi mekanisme
penelusuran capaian sampai ke sumber Realisasi Renaksi dan formula indikator yang membentuk Realisasi PK.
Alur Drill-down
Dashboard PK Bupati
↓
Klik Indikator
↓
OPD Pengampu
↓
PK Kepala OPD
↓
Eselon III/IV
↓
Renaksi
↓
Realisasi Renaksi
↓
Catatan Formula/Validasi PK
Catatan: apabila nilai PK Bupati berasal dari formula yang belum sepenuhnya otomatis, drill-down tetap menampilkan data
pendukung agar pimpinan dapat melihat konteks OPD, unit, Renaksi, dan status pelaksanaan.
8. Penundaan Penggunaan AI
Penggunaan AI analysis pada Sprint Juli ditunda secara sadar berdasarkan pertimbangan arsitektur sistem dan
kondisi existing data. AI tidak digunakan untuk menggantikan formula PK, menentukan status capaian, atau
membuat analisis penyebab.
Alasan
Penjelasan
Data belum sepenuhnya stabil
Relasi PK, Renaksi, Realisasi Renaksi, formula PK, dan struktur unit masih perlu
dipastikan konsisten.
AI tanpa konteks cenderung generik
Model dapat menghasilkan kalimat umum yang tidak membantu pengendalian
kinerja.
Formula harus akuntabel
Realisasi PK harus mengikuti formula pengukuran yang dapat dijelaskan, diuji, dan
dipertanggungjawabkan.
Rule lebih transparan
Rule-Based Engine lebih mudah diaudit, diuji, dan dijelaskan kepada pimpinan.
AI butuh knowledge layer
AI baru efektif setelah tersedia rule, knowledge base, formula, dan kalibrasi domain
SAKIP.
Urutan Pengembangan yang Disarankan
Monitoring Engine
↓
Formula PK & Rule-Based Engine
↓
Knowledge Layer
↓
AI Analysis
Prinsip: AI tidak boleh menjadi sumber kebenaran. AI hanya boleh menjadi lapisan interpretasi setelah
data, rule, dan formula stabil.
9. Failure Aspect dan Patch


# Page 10


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 10
Risiko
Dampak
Patch
Realisasi Renaksi dipaksa
menjadi Realisasi PK
Bupati
Nilai PK Bupati berpotensi tidak akurat
karena formula indikator
berbeda-beda.
Pisahkan alur monitoring dan formula PK. Gunakan
formula Bagian Organisasi sebagai dasar Realisasi
PK Bupati.
Relasi PK - Renaksi belum
sempurna
Drill-down dan monitoring pendukung
tidak lengkap.
Gunakan mapping minimal untuk indikator prioritas
terlebih dahulu.
Data Realisasi Renaksi
belum lengkap
Dashboard tampak rendah atau tidak
valid.
Tampilkan status belum lengkap dan daftar unit
belum update.
Eselon IV tidak merata
Drill-down antar OPD tidak konsisten.
Gunakan struktur fleksibel: Bupati - Eselon II - Eselon
III - Eselon IV jika tersedia.
AI menghasilkan analisis
generik
Dashboard terlihat canggih tetapi tidak
membantu.
Tunda AI; gunakan rule-based priority list.
Scope melebar
Target Sprint Juli berisiko gagal.
Kunci fokus pada monitoring realisasi PK berjenjang
dan formula PK yang realistis.


# Page 11


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 11
10. Fokus Implementasi Terdekat
• Pastikan PK Bupati, Eselon II, Eselon III, dan Eselon IV tersambung sesuai ketersediaan data.
• Pastikan Renaksi dan Realisasi Renaksi dapat ditelusuri dari tiap level PK.
• Buat view/query monitoring untuk kebutuhan dashboard.
• Siapkan field, tabel, atau mekanisme formula Realisasi PK yang dikelola/validasi Bagian Organisasi, terutama
untuk PK Bupati.
• Tampilkan status capaian sederhana berdasarkan formula dan rule yang disepakati.
• Jalankan drill-down dasar sampai Renaksi dan Realisasi Renaksi.
• Terapkan rule warna hijau/kuning/merah.
• Tunda AI analysis sampai struktur data, formula, rule, dan knowledge layer stabil.
11. Acceptance Criteria
No
Kriteria Keberhasilan
1
AKSARA dapat menampilkan Realisasi PK Bupati berdasarkan formula yang ditetapkan/validasi Bagian Organisasi.
2
AKSARA dapat menampilkan monitoring PK Eselon II/Kepala OPD.
3
AKSARA dapat menampilkan monitoring PK Eselon III.
4
AKSARA dapat menampilkan monitoring PK Eselon IV apabila tersedia.
5
Capaian/status dapat ditelusuri sampai Renaksi dan Realisasi Renaksi.
6
OPD/unit belum update dapat ditampilkan.
7
Status hijau, kuning, merah dapat ditampilkan berdasarkan rule yang disepakati.
8
Prioritas pimpinan berbasis rule tersedia.
9
Tidak ada input baru bagi Perangkat Daerah hanya untuk kebutuhan dashboard.
10
AI analysis tidak digunakan pada Sprint Juli.
12. Kesimpulan
Blueprint ini menempatkan AKSARA sebagai platform manajemen kinerja yang diperkuat dengan Monitoring
Engine, Formula PK, Rule-Based Engine, dan Executive Dashboard. Fokus utama Sprint Juli adalah memastikan
monitoring realisasi PK berjenjang berjalan dari Bupati sampai minimal Eselon III, dengan dukungan Eselon IV
apabila tersedia.
Penyesuaian utama pada blueprint ini adalah pemisahan antara monitoring Realisasi Renaksi dan perhitungan
Realisasi PK. Realisasi Renaksi digunakan untuk monitoring, drill-down, status update, dan early warning. Realisasi
PK, khususnya PK Bupati, mengikuti formula pengukuran indikator yang ditetapkan/validasi Bagian Organisasi.
Dengan pendekatan ini, pengembangan tetap realistis, tidak memperlebar scope, dan tetap menjawab arahan
Kementerian PANRB.


# Page 12


AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Halaman 12
13. Document Information
Item
Keterangan
Nama Dokumen
AKSARA Executive Control Center - Functional Blueprint Sprint Juli 2026
Versi
v1 - Revisi Mekanisme Agregasi
Status
Draft Implementasi
Fokus
Monitoring Realisasi PK Berjenjang berdasarkan kondisi existing data
Document Owner
M. Andrew Fickry MT
Unit
Bagian Organisasi Sekretariat Daerah Kabupaten Pringsewu
Implementasi Teknis
Dinas Komunikasi dan Informatika Kabupaten Pringsewu
Catatan Penggunaan
• Dokumen ini menjadi acuan fungsional awal antara Bagian Organisasi dan Dinas Kominfo.
• Perubahan teknis pada database, view, query, formula, atau dashboard perlu tetap merujuk pada prinsip Single
Source of Truth, No Double Entry bagi Perangkat Daerah, dan pemisahan Monitoring Engine dengan Formula PK.
• Pengembangan AI analysis tidak dimasukkan dalam Sprint Juli dan ditempatkan sebagai kandidat fase berikutnya
setelah Monitoring Engine, Rule-Based Engine, Formula PK, dan knowledge layer stabil.
Bagian ini menjelaskan informasi pengendalian dokumen sebagai mekanisme pengelolaan versi, sehingga setiap
perubahan terhadap blueprint dapat dilakukan secara terstruktur, terdokumentasi, dan dapat
dipertanggungjawabkan.
Versi
Tanggal
Perubahan
PIC
v1
Juli 2026
Revisi mekanisme agregasi menyesuaikan kondisi existing
data dan pemisahan Monitoring Engine dengan Formula PK
Document Owner
