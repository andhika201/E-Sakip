# Panduan Video e-SAKIP AKSARA

Paket ini berisi materi video panduan penggunaan web e-SAKIP AKSARA untuk semua role yang ada di kode aplikasi:

- Publik
- Super Admin (`admin`)
- Admin Kabupaten (`admin_kab`)
- Admin OPD (`admin_opd`)
- Admin Kecamatan (`admin_kecamatan`)
- Admin Inspektorat (`admin_inspektorat`)
- Bupati (`bupati`)

Sumber penyusunan utama:

- `app/Config/Routes.php`
- `app/Views/templates/admin_menu.php`
- `app/Helpers/rbac_helper.php`
- `app/Filters/ReadOnlyRoleFilter.php`
- `ALUR_FITUR_DAN_ROUTE.md`
- `README.md`
- file update RBAC di folder `db/`

## File Penting

- `naskah_video.md` - naskah narasi lengkap per slide.
- `storyboard.csv` - urutan slide, durasi, judul, dan fokus materi.
- `scripts/render_panduan_video.ps1` - script render otomatis menjadi gambar slide, audio narasi, dan MP4.
- `output/panduan-esakip-semua-role.mp4` - video hasil render dengan suara narasi.
- `output/panduan-esakip-semua-role-subtitle.mp4` - video dengan suara narasi dan subtitle internal.
- `output/panduan-esakip-semua-role.srt` - subtitle eksternal bila ingin diunggah terpisah.
- `output/panduan-esakip-narasi.wav` - audio narasi hasil TTS Windows.

## Cara Render Ulang

Dari root proyek:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\render_panduan_video.ps1
```

Script akan membuat:

- `docs/panduan_video_esakip/output/slides/*.png`
- `docs/panduan_video_esakip/output/panduan-esakip-semua-role.mp4`
- `docs/panduan_video_esakip/output/panduan-esakip-semua-role-subtitle.mp4`
- `docs/panduan_video_esakip/output/panduan-esakip-semua-role.srt`
- `docs/panduan_video_esakip/output/panduan-esakip-narasi.wav`

Catatan: video ini sengaja tidak memuat password akun. Untuk pelatihan internal, gunakan akun yang dibuat oleh Super Admin sesuai role masing-masing.
Suara narasi dibuat otomatis dengan TTS Windows; jika voice Indonesia tidak terpasang, aksennya mengikuti voice Windows yang tersedia.
