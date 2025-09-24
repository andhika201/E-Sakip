# Dashboard Admin Kabupaten - E-SAKIP

## Overview

Dashboard ini menampilkan progress semua dokumen SAKIP (Sistem Akuntabilitas Kinerja Instansi Pemerintah) berdasarkan status untuk Admin Kabupaten.

## Dokumen SAKIP yang ditampilkan:

1. **RPJMD** (Rencana Pembangunan Jangka Menengah Daerah)
2. **RKPD** (Rencana Kerja Pemerintah Daerah)
3. **RENSTRA** (Rencana Strategis)
4. **RENJA** (Rencana Kerja)
5. **IKU** (Indikator Kinerja Utama)
6. **LAKIP Kabupaten** (Laporan Akuntabilitas Kinerja Instansi Pemerintah)
7. **LAKIP OPD** (Laporan Akuntabilitas Kinerja OPD)

## Fitur Utama

### 1. Dashboard Statistik

- Menampilkan ringkasan jumlah dokumen berdasarkan status (Selesai/Draft)
- Charts berbentuk donut untuk visualisasi data
- Data diambil secara real-time dari database

### 2. Filter Data

- **Filter berdasarkan Unit Kerja (OPD)**: Menampilkan data spesifik untuk OPD tertentu
- **Filter berdasarkan Tahun**: Menampilkan data untuk tahun tertentu
- **Filter kombinasi**: OPD + Tahun untuk data yang lebih spesifik

### 3. Tabel Data Detail

- Menampilkan data dalam format tabel untuk setiap jenis dokumen
- Progress status untuk setiap OPD
- Data dapat difilter berdasarkan kriteria yang dipilih

## File yang diimplement

### 1. Controller: `AdminKabupatenController.php`

**Location**: `app/Controllers/AdminKabupatenController.php`

**Methods**:

- `index()`: Menampilkan dashboard utama dengan data lengkap
- `getDashboardData()`: AJAX endpoint untuk filter data
- `getStats()`: API endpoint untuk mendapatkan statistik

**Fitur**:

- Error handling dengan try-catch
- Validasi input untuk filter
- Response JSON untuk AJAX requests

### 2. Model: `DashboardModel.php`

**Location**: `app/Models/DashboardModel.php`

**Methods**:

- `getAllStats($opdId = null, $year = null)`: Mendapatkan statistik semua dokumen
- `getRpjmdStats($opdId = null, $year = null)`: Statistik RPJMD
- `getRkpdStats($opdId = null, $year = null)`: Statistik RKPD
- `getRenstraStats($opdId = null, $year = null)`: Statistik RENSTRA
- `getRenjaStats($opdId = null, $year = null)`: Statistik RENJA
- `getIkuStats($opdId = null, $year = null)`: Statistik IKU
- `getLakipKabupatenStats($opdId = null, $year = null)`: Statistik LAKIP Kabupaten
- `getLakipOpdStats($opdId = null, $year = null)`: Statistik LAKIP OPD
- `getOpdList()`: Daftar semua OPD
- `getAvailableYears()`: Daftar tahun yang tersedia

**Fitur**:

- Query builder untuk fleksibilitas
- Parameter opsional untuk filtering
- Error handling dan validasi data

### 3. View: `dashboard.php`

**Location**: `app/Views/adminKabupaten/dashboard.php`

**Fitur**:

- Charts dinamis menggunakan Chart.js
- Filter form dengan dropdown OPD dan Tahun
- AJAX untuk real-time filtering
- Responsive design dengan Bootstrap
- Error handling dan loading states
- Notification system untuk user feedback

## Cara Penggunaan

### 1. Akses Dashboard

- Login sebagai Admin Kabupaten
- Navigate ke Dashboard Admin Kabupaten
- Dashboard akan menampilkan data lengkap secara default

### 2. Menggunakan Filter

1. **Filter by OPD**:

   - Pilih OPD dari dropdown "Pilih Unit Kerja"
   - Klik tombol "Tampilkan"
   - Data akan diupdate untuk OPD yang dipilih

2. **Filter by Tahun**:

   - Pilih tahun dari dropdown "Pilih Tahun"
   - Klik tombol "Tampilkan"
   - Data akan diupdate untuk tahun yang dipilih

3. **Filter Kombinasi**:
   - Pilih OPD dan Tahun
   - Klik tombol "Tampilkan"
   - Data akan diupdate untuk kriteria gabungan

### 3. Membaca Data

- **Charts**: Menampilkan proporsi Selesai vs Draft
- **Tables**: Menampilkan detail data per OPD
- **Status Colors**:
  - Hijau: Selesai/Tercapai
  - Kuning: Draft/Proses

## Database Schema

### Tables yang digunakan:

1. `rpjmd_misi` - Data RPJMD
2. `rkpd_sasaran` - Data RKPD
3. `renstra_sasaran` - Data RENSTRA
4. `renja_sasaran` - Data RENJA
5. `iku_table` - Data IKU (perlu dikonfigurasi sesuai nama table)
6. `lakip_kabupaten` - Data LAKIP Kabupaten (perlu dikonfigurasi)
7. `lakip_opd` - Data LAKIP OPD (perlu dikonfigurasi)

### Fields yang digunakan:

- `status`: Status dokumen (1=Selesai, 0=Draft)
- `opd_id`: ID Unit Kerja
- `tahun`: Tahun data
- `created_at`/`updated_at`: Timestamp

## Status Implementation

### ✅ Completed:

1. Controller methods dengan error handling
2. Model methods untuk semua jenis dokumen
3. View dengan charts dinamis
4. AJAX filtering functionality
5. Error handling dan loading states
6. Responsive design

### 🔄 Testing Required:

1. Test filter functionality dengan data real
2. Verify database table names dan fields
3. Test AJAX endpoints
4. Validate chart data accuracy

### 📝 Next Steps:

1. **Database Configuration**: Pastikan nama table dan field sesuai dengan schema aktual
2. **Data Testing**: Test dengan data real untuk memastikan accuracy
3. **Performance**: Optimize queries jika diperlukan
4. **UI Enhancement**: Tambahkan loading animations dan better error messages

## Troubleshooting

### Common Issues:

1. **Data tidak muncul**:

   - Check database connection
   - Verify table names dalam model
   - Check field names (status, opd_id, tahun)

2. **Filter tidak berfungsi**:

   - Check AJAX endpoint URL
   - Verify JavaScript console untuk errors
   - Check network tab untuk HTTP errors

3. **Charts tidak tampil**:
   - Check Chart.js library loaded
   - Verify data format dari PHP ke JavaScript
   - Check browser console untuk errors

### Debug Steps:

1. Check PHP errors dalam logs
2. Check JavaScript console
3. Verify database queries dalam debug mode
4. Check network requests untuk AJAX calls

## Security Notes

- Semua input di-escape dengan `esc()` function
- AJAX requests menggunakan CSRF protection
- Input validation pada controller level
- Sanitized database queries menggunakan Query Builder

## Performance Considerations

- Database queries dioptimasi dengan indexes
- AJAX requests untuk prevent full page reload
- Chart data di-cache dalam JavaScript variables
- Minimal DOM manipulation untuk better performance
