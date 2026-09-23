param(
    [int] $Width = 1920,
    [int] $Height = 1080,
    [string] $OutputName = "panduan-esakip-semua-role.mp4"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$docDir = Join-Path $root "docs\panduan_video_esakip"
$outDir = Join-Path $docDir "output"
$slideDir = Join-Path $outDir "slides"
$listPath = Join-Path $outDir "slides.ffconcat"
$outputPath = Join-Path $outDir $OutputName
$tempVideoPath = Join-Path $outDir "panduan-esakip-semua-role-video-only.mp4"
$audioPath = Join-Path $outDir "panduan-esakip-narasi.wav"
$srtPath = Join-Path $outDir "panduan-esakip-semua-role.srt"
$subtitleOutputPath = Join-Path $outDir "panduan-esakip-semua-role-subtitle.mp4"

New-Item -ItemType Directory -Force -Path $slideDir | Out-Null

Add-Type -AssemblyName System.Drawing

function New-Color([int] $r, [int] $g, [int] $b, [int] $a = 255) {
    return [System.Drawing.Color]::FromArgb($a, $r, $g, $b)
}

function New-Font([float] $size, [System.Drawing.FontStyle] $style = [System.Drawing.FontStyle]::Regular) {
    return New-Object System.Drawing.Font("Segoe UI", $size, $style, [System.Drawing.GraphicsUnit]::Pixel)
}

function Draw-RoundRect($g, [System.Drawing.Brush] $brush, [System.Drawing.RectangleF] $rect, [float] $radius) {
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $d = $radius * 2
    $path.AddArc($rect.X, $rect.Y, $d, $d, 180, 90)
    $path.AddArc($rect.Right - $d, $rect.Y, $d, $d, 270, 90)
    $path.AddArc($rect.Right - $d, $rect.Bottom - $d, $d, $d, 0, 90)
    $path.AddArc($rect.X, $rect.Bottom - $d, $d, $d, 90, 90)
    $path.CloseFigure()
    $g.FillPath($brush, $path)
    $path.Dispose()
}

function Draw-TextBox(
    $g,
    [string] $text,
    [float] $x,
    [float] $y,
    [float] $w,
    [float] $h,
    [System.Drawing.Font] $font,
    [System.Drawing.Color] $color,
    [System.Drawing.StringAlignment] $align = [System.Drawing.StringAlignment]::Near
) {
    $brush = New-Object System.Drawing.SolidBrush($color)
    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = $align
    $format.LineAlignment = [System.Drawing.StringAlignment]::Near
    $format.Trimming = [System.Drawing.StringTrimming]::Word
    $format.FormatFlags = 0
    $rect = [System.Drawing.RectangleF]::new($x, $y, $w, $h)
    $g.DrawString($text, $font, $brush, $rect, $format)
    $format.Dispose()
    $brush.Dispose()
}

function Draw-Bullets($g, [string[]] $items, [float] $x, [float] $y, [float] $w, [float] $lineHeight, [float] $fontSize = 34) {
    $font = New-Font $fontSize
    $dotBrush = New-Object System.Drawing.SolidBrush((New-Color 0 116 62))
    $textColor = New-Color 37 48 42
    $cy = $y
    foreach ($item in $items) {
        $g.FillEllipse($dotBrush, $x, $cy + 13, 12, 12)
        Draw-TextBox $g $item ($x + 28) $cy ($w - 28) ($lineHeight + 18) $font $textColor
        $cy += $lineHeight
    }
    $dotBrush.Dispose()
    $font.Dispose()
}

function Draw-Card($g, [string] $title, [string[]] $items, [float] $x, [float] $y, [float] $w, [float] $h, [System.Drawing.Color] $accent) {
    $shadowBrush = New-Object System.Drawing.SolidBrush((New-Color 0 0 0 20))
    $cardBrush = New-Object System.Drawing.SolidBrush((New-Color 255 255 255 242))
    $accentBrush = New-Object System.Drawing.SolidBrush($accent)
    Draw-RoundRect $g $shadowBrush ([System.Drawing.RectangleF]::new($x + 8, $y + 10, $w, $h)) 22
    Draw-RoundRect $g $cardBrush ([System.Drawing.RectangleF]::new($x, $y, $w, $h)) 22
    $g.FillRectangle($accentBrush, $x, $y, 10, $h)
    $titleFont = New-Font 34 ([System.Drawing.FontStyle]::Bold)
    Draw-TextBox $g $title ($x + 34) ($y + 28) ($w - 60) 48 $titleFont (New-Color 21 49 31)
    Draw-Bullets $g $items ($x + 34) ($y + 92) ($w - 60) 43 27
    $titleFont.Dispose()
    $shadowBrush.Dispose()
    $cardBrush.Dispose()
    $accentBrush.Dispose()
}

function Draw-Header($g, [string] $title, [string] $subtitle) {
    $titleFont = New-Font 54 ([System.Drawing.FontStyle]::Bold)
    $subFont = New-Font 26
    Draw-TextBox $g $title 110 74 1360 72 $titleFont (New-Color 21 49 31)
    Draw-TextBox $g $subtitle 114 148 1400 48 $subFont (New-Color 85 104 94)
    $titleFont.Dispose()
    $subFont.Dispose()
}

function Draw-Brand($g) {
    $logoPath = Join-Path $root "public\assets\images\LogoTentang.png"
    if (Test-Path $logoPath) {
        $img = [System.Drawing.Image]::FromFile($logoPath)
        $ratio = [Math]::Min(210 / $img.Width, 125 / $img.Height)
        $w = [int]($img.Width * $ratio)
        $h = [int]($img.Height * $ratio)
        $g.DrawImage($img, $Width - $w - 90, 58, $w, $h)
        $img.Dispose()
    }
}

function New-Slide([int] $number, [string] $title, [string] $subtitle, [scriptblock] $body) {
    $bmp = New-Object System.Drawing.Bitmap($Width, $Height)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit

    $bg = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        ([System.Drawing.Rectangle]::new(0, 0, $Width, $Height)),
        (New-Color 241 247 243),
        (New-Color 225 238 230),
        35
    )
    $g.FillRectangle($bg, 0, 0, $Width, $Height)
    $bg.Dispose()

    $bandBrush = New-Object System.Drawing.SolidBrush((New-Color 0 116 62))
    $g.FillRectangle($bandBrush, 0, 0, 34, $Height)
    $bandBrush.Dispose()

    Draw-Header $g $title $subtitle
    Draw-Brand $g
    & $body $g

    $footFont = New-Font 20
    Draw-TextBox $g ("Panduan e-SAKIP AKSARA - Slide {0:00}" -f $number) 112 ($Height - 58) 620 32 $footFont (New-Color 91 108 99)
    $footFont.Dispose()

    $file = Join-Path $slideDir ("slide_{0:00}.png" -f $number)
    $bmp.Save($file, [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose()
    $bmp.Dispose()
    return $file
}

function Format-SrtTime([double] $seconds) {
    $span = [TimeSpan]::FromSeconds($seconds)
    return "{0:00}:{1:00}:{2:00},{3:000}" -f [Math]::Floor($span.TotalHours), $span.Minutes, $span.Seconds, $span.Milliseconds
}

$slides = @()

$slides += @{
    Duration = 7
    File = New-Slide 1 "Panduan Penggunaan e-SAKIP AKSARA" "Lengkap untuk semua role pengguna" {
        param($g)
        $hero = New-Object System.Drawing.SolidBrush((New-Color 255 255 255 238))
        Draw-RoundRect $g $hero ([System.Drawing.RectangleF]::new(110, 255, 1700, 540)) 30
        $hero.Dispose()
        $big = New-Font 68 ([System.Drawing.FontStyle]::Bold)
        $small = New-Font 34
        Draw-TextBox $g "Kabupaten Pringsewu" 160 315 900 88 $big (New-Color 0 116 62)
        Draw-TextBox $g "Role yang dibahas: Publik, Super Admin, Admin Kabupaten, Admin OPD, Admin Kecamatan, Admin Inspektorat, dan Bupati." 164 420 1160 120 $small (New-Color 37 48 42)
        Draw-Bullets $g @("Alur login dan keamanan akun", "Menu utama per role", "Siklus kerja SAKIP dari perencanaan sampai pelaporan") 170 585 1080 56 32
        $big.Dispose()
        $small.Dispose()
    }
}

$slides += @{
    Duration = 8
    File = New-Slide 2 "Masuk dan Keamanan" "Gunakan akun sesuai role yang dibuat Super Admin" {
        param($g)
        $loginShot = Join-Path $docDir "assets\login.png"
        if (Test-Path $loginShot) {
            $img = [System.Drawing.Image]::FromFile($loginShot)
            $frameBrush = New-Object System.Drawing.SolidBrush((New-Color 255 255 255))
            Draw-RoundRect $g $frameBrush ([System.Drawing.RectangleF]::new(1115, 258, 615, 365)) 20
            $g.DrawImage($img, 1130, 273, 585, 329)
            $frameBrush.Dispose()
            $img.Dispose()
        }
        Draw-Card $g "Langkah Dasar" @(
            "Buka /login lalu isi username dan password.",
            "Sistem mengarahkan pengguna ke dashboard sesuai role.",
            "Gunakan Profil Saya untuk ganti password.",
            "Aktifkan 2FA bila kebijakan instansi mewajibkan.",
            "Logout setelah selesai menggunakan aplikasi."
        ) 120 260 900 430 (New-Color 0 116 62)
    }
}

$slides += @{
    Duration = 9
    File = New-Slide 3 "Peta Role" "Sidebar otomatis menampilkan menu sesuai hak akses" {
        param($g)
        Draw-Card $g "Role Tulis" @("Super Admin: semua modul dan pengaturan.", "Admin Kabupaten: dokumen tingkat kabupaten.", "Admin OPD: dokumen satu perangkat daerah.", "Admin Kecamatan: pola OPD untuk kecamatan.") 110 245 820 465 (New-Color 0 116 62)
        Draw-Card $g "Role Baca/Pantau" @("Publik: halaman informasi tanpa login.", "Admin Inspektorat: evaluasi read-only lintas OPD.", "Bupati: dashboard eksekutif dan monitoring read-only.") 990 245 820 465 (New-Color 19 101 155)
    }
}

$slides += @{
    Duration = 8
    File = New-Slide 4 "Publik" "Informasi kinerja dapat dibuka tanpa login" {
        param($g)
        Draw-Card $g "Menu Publik" @("Dashboard publik", "RPJMD, RKPD, Renstra, RKT", "IKU OPD dan Perjanjian Kinerja", "Cascading dan Pohon Kinerja", "LAKIP Kabupaten dan LAKIP OPD", "Tentang Kami dan dokumentasi API") 120 250 790 500 (New-Color 0 116 62)
        Draw-Card $g "Output" @("Gunakan filter yang tersedia di halaman.", "Cetak PDF untuk arsip resmi.", "Ekspor Excel bila menu menyediakan tombolnya.", "Publik hanya melihat data yang sudah ditampilkan aplikasi.") 990 250 790 500 (New-Color 196 143 31)
    }
}

$slides += @{
    Duration = 9
    File = New-Slide 5 "Super Admin" "Menyiapkan fondasi aplikasi dan hak akses" {
        param($g)
        Draw-Card $g "Master Data" @("OPD, pegawai, jabatan, pangkat", "User, role, permission", "Satuan dan data pendukung", "Sinkronisasi pegawai bila provider tersedia") 120 245 760 455 (New-Color 0 116 62)
        Draw-Card $g "Pengendalian" @("Program dan kegiatan PK", "Log aktivitas", "Pengaturan aplikasi dan identitas web", "Ambang status capaian dashboard", "Pembuatan akun role khusus") 950 245 820 455 (New-Color 91 79 159)
    }
}

$slides += @{
    Duration = 9
    File = New-Slide 6 "Admin Kabupaten: Perencanaan" "Mengelola dokumen dan struktur kinerja tingkat kabupaten" {
        param($g)
        Draw-Card $g "Urutan Utama" @("Dashboard Kabupaten dan mode fokus OPD", "RPJMD dan versi RPJMD", "RKPD", "IKU Kabupaten dan Revisi IKU", "Pohon Kinerja dan Cascading Kabupaten", "Perjanjian Kinerja Bupati") 120 245 810 500 (New-Color 0 116 62)
        Draw-Card $g "Kunci Operasional" @("Pastikan tahun/periode benar.", "Gunakan alur versi untuk perubahan dokumen.", "Cek status sebelum mengajukan atau menetapkan.", "Cetak dokumen saat data sudah siap.") 1000 245 780 500 (New-Color 19 101 155)
    }
}

$slides += @{
    Duration = 9
    File = New-Slide 7 "Admin Kabupaten: Pengukuran" "Target, MONEV, verifikasi, dan pelaporan kabupaten" {
        param($g)
        Draw-Card $g "Pengukuran" @("Target dan Rencana Aksi PK Bupati", "Monitoring Rencana Aksi PK Bupati", "Monitoring PK OPD atau Kecamatan", "MONEV dan realisasi anggaran") 120 245 790 455 (New-Color 0 116 62)
        Draw-Card $g "Pelaporan dan Verifikasi" @("LAKIP Kabupaten", "Verifikasi pengajuan versi dokumen", "Permintaan perbaikan LAKIP OPD", "Evaluasi Inspektorat", "Cetak PDF atau Excel bila tersedia") 990 245 790 455 (New-Color 196 143 31)
    }
}

$slides += @{
    Duration = 9
    File = New-Slide 8 "Admin OPD" "Mengelola data perangkat daerah masing-masing" {
        param($g)
        Draw-Card $g "Perencanaan OPD" @("Renstra ditautkan ke sasaran RPJMD", "Versi Renstra", "RKT atau Renja", "IKU OPD dan Revisi IKU", "Cascading Eselon II, III, IV, dan Pelaksana") 120 245 790 500 (New-Color 0 116 62)
        Draw-Card $g "Pengukuran dan Laporan" @("PK JPT, Administrator, dan Pengawas", "Target dan Rencana Aksi", "Monitoring Rencana Aksi", "LAKIP OPD", "Pengesahan dan permintaan perbaikan LAKIP") 990 245 790 500 (New-Color 91 79 159)
    }
}

$slides += @{
    Duration = 8
    File = New-Slide 9 "Admin Kecamatan" "Area OPD dengan penyesuaian struktur kecamatan" {
        param($g)
        Draw-Card $g "Yang Sama" @("Dashboard, Renstra, RKT/Renja", "IKU, Cascading, Target, MONEV", "LAKIP kecamatan", "Lingkup data tetap sesuai kecamatan akun") 120 260 790 430 (New-Color 0 116 62)
        Draw-Card $g "Yang Berbeda" @("PK JPT tidak ditampilkan untuk kecamatan.", "Gunakan PK Kecamatan sebagai puncak struktur.", "PK Administrator dan PK Pengawas tetap digunakan.", "Label jenjang mengikuti kebutuhan kecamatan.") 990 260 790 430 (New-Color 196 143 31)
    }
}

$slides += @{
    Duration = 8
    File = New-Slide 10 "Admin Inspektorat" "Evaluator read-only lintas OPD" {
        param($g)
        Draw-Card $g "Dapat Dilihat" @("Dashboard Kabupaten", "RPJMD", "Cascading Kabupaten", "Perjanjian Kinerja Bupati", "LAKIP Kabupaten", "Evaluasi Inspektorat") 120 250 790 470 (New-Color 19 101 155)
        Draw-Card $g "Batasan" @("Tidak untuk input data kinerja.", "Tidak untuk ubah atau hapus dokumen.", "Dipakai untuk membaca, mengecek, dan mengevaluasi.", "Akses lintas OPD tidak perlu opd_id.") 990 250 790 470 (New-Color 196 143 31)
    }
}

$slides += @{
    Duration = 8
    File = New-Slide 11 "Bupati" "Dashboard eksekutif dan monitoring read-only" {
        param($g)
        Draw-Card $g "Menu Utama" @("Dashboard Eksekutif", "Perjanjian Kinerja", "Target dan Rencana Aksi", "MONEV", "LAKIP") 120 255 710 455 (New-Color 0 116 62)
        Draw-Card $g "Cara Pakai" @("Buka detail dari kartu, grafik, atau drawer.", "Pantau status OPD dan capaian misi.", "Lihat anggaran kinerja bila tersedia.", "Akses bersifat baca saja untuk menjaga data.") 920 255 860 455 (New-Color 19 101 155)
    }
}

$slides += @{
    Duration = 10
    File = New-Slide 12 "Siklus Tahunan" "Urutan kerja lintas role dalam satu tahun anggaran" {
        param($g)
        $steps = @(
            @("1", "Super Admin", "Master data, user, role, program PK"),
            @("2", "Admin Kabupaten", "RPJMD, RKPD, IKU, Cascading, PK Bupati"),
            @("3", "Admin OPD/Kec.", "Renstra, RKT, IKU, PK, Target, MONEV, LAKIP"),
            @("4", "Verifikasi", "Validasi versi, perbaikan LAKIP, evaluasi read-only"),
            @("5", "Bupati dan Publik", "Monitoring eksekutif dan transparansi informasi")
        )
        $x = 125
        $y = 300
        foreach ($s in $steps) {
            $accent = New-Object System.Drawing.SolidBrush((New-Color 0 116 62))
            $white = New-Object System.Drawing.SolidBrush((New-Color 255 255 255 242))
            Draw-RoundRect $g $white ([System.Drawing.RectangleF]::new($x, $y, 310, 330)) 24
            $g.FillEllipse($accent, $x + 26, $y + 28, 58, 58)
            $numFont = New-Font 30 ([System.Drawing.FontStyle]::Bold)
            Draw-TextBox $g $s[0] ($x + 26) ($y + 38) 58 46 $numFont (New-Color 255 255 255) ([System.Drawing.StringAlignment]::Center)
            $titleFont = New-Font 32 ([System.Drawing.FontStyle]::Bold)
            $bodyFont = New-Font 25
            Draw-TextBox $g $s[1] ($x + 28) ($y + 108) 260 72 $titleFont (New-Color 21 49 31)
            Draw-TextBox $g $s[2] ($x + 28) ($y + 190) 260 108 $bodyFont (New-Color 67 82 73)
            $numFont.Dispose()
            $titleFont.Dispose()
            $bodyFont.Dispose()
            $accent.Dispose()
            $white.Dispose()
            $x += 350
        }
    }
}

$slides += @{
    Duration = 8
    File = New-Slide 13 "Praktik Baik" "Agar data tetap rapi, aman, dan mudah diaudit" {
        param($g)
        Draw-Card $g "Sebelum Input" @("Cek tahun, periode, mode, dan OPD.", "Pastikan status dokumen: draft, diajukan, disahkan.", "Ikuti tombol aksi yang tersedia di halaman.", "Jangan mengubah data lewat URL langsung.") 120 245 790 500 (New-Color 0 116 62)
        Draw-Card $g "Setelah Input" @("Gunakan cetak PDF atau Excel untuk arsip.", "Ajukan verifikasi bila alurnya mewajibkan.", "Ganti password awal dan aktifkan 2FA bila perlu.", "Logout setelah selesai bekerja.") 990 245 790 500 (New-Color 196 143 31)
    }
}

$slides += @{
    Duration = 11
    File = New-Slide 14 "Proses Input Data" "Alur umum tambah, simpan, verifikasi, dan cetak" {
        param($g)
        Draw-Card $g "Input Awal" @(
            "Pilih modul sesuai role dan menu.",
            "Klik Tambah atau buka form input.",
            "Isi tahun, periode, OPD, dan data utama.",
            "Isi sasaran, indikator, target, realisasi, lampiran.",
            "Simpan draft dan cek kembali datanya."
        ) 120 245 790 500 (New-Color 0 116 62)
        Draw-Card $g "Proses Lanjutan" @(
            "Ajukan validasi atau pengesahan bila tersedia.",
            "Admin atau verifikator menyetujui atau mengembalikan.",
            "Perbaiki data bila dikembalikan.",
            "Finalkan dokumen sesuai alur modul.",
            "Gunakan Cetak PDF atau Excel untuk arsip."
        ) 990 245 790 500 (New-Color 196 143 31)
    }
}

$slides += @{
    Duration = 6
    File = New-Slide 15 "Penutup" "Panduan siap digunakan untuk pelatihan pengguna" {
        param($g)
        $font = New-Font 44 ([System.Drawing.FontStyle]::Bold)
        $body = New-Font 34
        $brush = New-Object System.Drawing.SolidBrush((New-Color 255 255 255 242))
        Draw-RoundRect $g $brush ([System.Drawing.RectangleF]::new(180, 310, 1560, 360)) 30
        $brush.Dispose()
        Draw-TextBox $g "Kunci penggunaan: ikuti menu yang tampil sesuai role." 240 385 1440 70 $font (New-Color 0 116 62) ([System.Drawing.StringAlignment]::Center)
        Draw-TextBox $g "Aplikasi sudah memfilter hak akses melalui role dan permission, sehingga tiap pengguna cukup bekerja dari dashboard dan sidebar masing-masing." 310 485 1300 120 $body (New-Color 37 48 42) ([System.Drawing.StringAlignment]::Center)
        $font.Dispose()
        $body.Dispose()
    }
}

$totalDuration = 0.0
foreach ($s in $slides) {
    $totalDuration += [double] $s.Duration
}

$concat = New-Object System.Collections.Generic.List[string]
$concat.Add("ffconcat version 1.0")
for ($i = 0; $i -lt $slides.Count; $i++) {
    $s = $slides[$i]
    $safeFile = ($s.File -replace "\\", "/")
    $concat.Add("file '$safeFile'")
    $duration = [double] $s.Duration
    if ($i -eq ($slides.Count - 1)) {
        # The concat demuxer needs the final image repeated. On this Windows
        # FFmpeg build, the repeated final image contributes the same duration,
        # so write half here to keep the rendered video equal to storyboard time.
        $duration = $duration / 2
    }
    $concat.Add("duration $duration")
}
$lastFile = ($slides[-1].File -replace "\\", "/")
$concat.Add("file '$lastFile'")
Set-Content -Path $listPath -Value $concat -Encoding ASCII

$captionTexts = @(
    "Selamat datang di panduan penggunaan web e-SAKIP AKSARA Kabupaten Pringsewu. Video ini menjelaskan alur penggunaan untuk semua role.",
    "Buka halaman login, isi username dan password sesuai akun yang diberikan. Setelah berhasil masuk, sistem mengarahkan pengguna ke dashboard sesuai role.",
    "Setiap role melihat menu yang berbeda. Publik membaca informasi, role admin mengelola data, sedangkan Inspektorat dan Bupati berfokus pada pemantauan.",
    "Pengunjung publik dapat melihat dashboard, RPJMD, RKPD, Renstra, RKT, IKU, Perjanjian Kinerja, Cascading, Pohon Kinerja, dan LAKIP.",
    "Super Admin menyiapkan master data, user, role, permission, program kegiatan PK, log aktivitas, pengaturan aplikasi, dan ambang dashboard.",
    "Admin Kabupaten mengelola dashboard kabupaten, RPJMD, versi RPJMD, RKPD, IKU Kabupaten, Pohon Kinerja, Cascading, dan PK Bupati.",
    "Admin Kabupaten juga mengelola Target Rencana Aksi, MONEV, LAKIP Kabupaten, verifikasi dokumen, dan permintaan perbaikan LAKIP OPD.",
    "Admin OPD mengelola Renstra, RKT atau Renja, IKU, Cascading, PK, Target Rencana Aksi, MONEV, dan LAKIP untuk perangkat daerahnya.",
    "Admin Kecamatan memakai area OPD dengan penyesuaian struktur kecamatan. Fokusnya PK Kecamatan, Administrator, Pengawas, target, monitoring, dan laporan.",
    "Admin Inspektorat adalah evaluator read-only lintas OPD. Role ini dipakai untuk membaca, mengecek, dan mengevaluasi data kinerja.",
    "Role Bupati memakai dashboard eksekutif dan halaman monitoring read-only untuk PK, Target Rencana Aksi, MONEV, LAKIP, status OPD, dan misi.",
    "Satu siklus SAKIP berjalan dari master data, perencanaan kabupaten, penurunan ke OPD dan kecamatan, verifikasi, evaluasi, lalu monitoring eksekutif.",
    "Sebelum input, cek tahun, periode, OPD, dan status dokumen. Gunakan tombol resmi, cetak arsip, ganti password awal, dan aktifkan 2FA bila perlu.",
    "Proses input data dimulai dari pilih modul, klik tambah, isi form, simpan draft, ajukan validasi, lakukan perbaikan bila dikembalikan, lalu cetak arsip.",
    "Panduan ini siap dipakai untuk pelatihan. Kunci penggunaan: ikuti menu yang tampil sesuai role karena aplikasi sudah memfilter hak akses."
)

$srt = New-Object System.Collections.Generic.List[string]
$cursor = 0.0
for ($i = 0; $i -lt $slides.Count; $i++) {
    $start = $cursor
    $end = $cursor + [double] $slides[$i].Duration
    $srt.Add([string] ($i + 1))
    $srt.Add(("{0} --> {1}" -f (Format-SrtTime $start), (Format-SrtTime $end)))
    $srt.Add($captionTexts[$i])
    $srt.Add("")
    $cursor = $end
}
Set-Content -Path $srtPath -Value $srt -Encoding UTF8

Add-Type -AssemblyName System.Speech
$speaker = New-Object System.Speech.Synthesis.SpeechSynthesizer
$speaker.Rate = 1
$speaker.Volume = 100
$voice = $speaker.GetInstalledVoices() |
    Where-Object { $_.VoiceInfo.Name -eq "Microsoft Zira Desktop" } |
    Select-Object -First 1
if ($voice) {
    $speaker.SelectVoice($voice.VoiceInfo.Name)
}
$speaker.SetOutputToWaveFile($audioPath)
$speaker.Speak(($captionTexts -join " "))
$speaker.Dispose()

$ffmpeg = Get-Command ffmpeg -ErrorAction Stop
$ffprobe = Get-Command ffprobe -ErrorAction Stop
$audioDurationRaw = & $ffprobe.Source -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $audioPath
$audioDuration = [double]::Parse(($audioDurationRaw | Select-Object -First 1), [System.Globalization.CultureInfo]::InvariantCulture)
$tempo = 1.0
if ($audioDuration -gt $totalDuration -and $totalDuration -gt 0) {
    $tempo = $audioDuration / $totalDuration
}
if ($tempo -lt 0.5) { $tempo = 0.5 }
if ($tempo -gt 2.0) { $tempo = 2.0 }
$tempoText = $tempo.ToString("0.###", [System.Globalization.CultureInfo]::InvariantCulture)
$durationText = ([double] $totalDuration).ToString("0.###", [System.Globalization.CultureInfo]::InvariantCulture)
& $ffmpeg.Source -y -f concat -safe 0 -i $listPath -vf "fps=30,format=yuv420p" -movflags +faststart $tempVideoPath
& $ffmpeg.Source -y -i $tempVideoPath -i $audioPath -map 0:v -map 1:a -c:v copy -c:a aac -b:a 160k -af "atempo=$tempoText,apad,atrim=0:$durationText" -t $durationText -movflags +faststart $outputPath
& $ffmpeg.Source -y -i $outputPath -i $srtPath -map 0:v -map 0:a -map 1:0 -c:v copy -c:a copy -c:s mov_text -metadata:s:s:0 language=ind -movflags +faststart $subtitleOutputPath

Write-Host "Video selesai:"
Write-Host $outputPath
Write-Host "Video dengan subtitle:"
Write-Host $subtitleOutputPath
Write-Host "Subtitle SRT:"
Write-Host $srtPath
Write-Host "Audio narasi:"
Write-Host $audioPath
