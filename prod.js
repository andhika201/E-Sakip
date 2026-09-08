function deletePk(pkId) {
    if (!confirm('Yakin ingin menghapus data PK ini?')) return;

    const baseUrl = window.base_url ?? '/';
    // Segmen URL (mis. 'kecamatan'), bukan jenis data ('camat'). Fallback ke
    // window.jenis untuk halaman lama yang belum mengirim pkSeg.
    const pkSeg = window.pkSeg || window.jenis || '';
    const roleBase = window.roleBase ?? '';

    // CSRF CodeIgniter hanya membaca token dari: field POST bernama csrf_test_name,
    // body JSON dengan kunci yang sama, ATAU header 'X-CSRF-TOKEN' (Config\Security::$headerName).
    // Header bernama csrf_test_name TIDAK dibaca -> selalu 403.
    const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content ?? '';

    const deleteUrl = `${baseUrl}${roleBase}/pk/${pkSeg}/delete/${pkId}`;

    fetch(deleteUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfHash
        },
        credentials: 'same-origin',
        body: '{}'
    })
        .then(async (res) => {
            // Galat CSRF/otorisasi bisa membalas HTML, bukan JSON. Baca sebagai teks
            // dulu supaya pesannya jelas dan bukan "Unexpected token <".
            const text = await res.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = null;
            }

            if (data && data.success) {
                // Buang pk_id dari query: record-nya sudah tidak ada, kalau
                // dipertahankan halaman memuat detail kosong.
                const url = new URL(window.location.href);
                url.searchParams.delete('pk_id');
                window.location.replace(url.toString());
                return;
            }

            if (data && data.error) {
                alert(data.error);
                return;
            }

            if (res.status === 403) {
                alert('Sesi keamanan kedaluwarsa atau Anda tidak berhak menghapus PK ini. Muat ulang halaman lalu coba lagi.');
                return;
            }

            alert('Gagal menghapus data PK. (HTTP ' + res.status + ')');
        })
        .catch((err) => {
            console.error(err);
            alert('Terjadi kesalahan saat menghapus data PK.');
        });
}
