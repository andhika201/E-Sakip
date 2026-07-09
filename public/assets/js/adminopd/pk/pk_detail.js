function deletePk(pkId) {
    if (!confirm('Yakin ingin menghapus data PK ini?')) return;

    const baseUrl  = window.base_url  ?? '/';
    const jenisPk  = window.jenis     ?? '';
    const roleBase = window.roleBase  ?? '';

    const csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
    const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;

    const deleteUrl = `${baseUrl}${roleBase}/pk/${jenisPk}/delete/${pkId}`;

    console.log("DELETE URL:", deleteUrl);

    fetch(deleteUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            [csrfName]: csrfHash
        },
        credentials: 'same-origin',
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Data PK berhasil dihapus!');
            location.reload();
        } else {
            alert(data.error || 'Gagal menghapus data PK.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat menghapus data PK.');
    });
}
