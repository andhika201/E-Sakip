// Pastikan base_url dan jenis tersedia
const baseUrl = window.base_url || '';
const jenisPk = window.jenis ;

// Delete PK function
function deletePk(pkId) {
    if (!confirm('Yakin ingin menghapus data PK ini?')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
console.log("DELETE URL:", `${baseUrl}adminopd/pk/${jenisPk}/delete/${pkId}`);

    fetch(`${baseUrl}adminopd/pk/${jenisPk}/delete/${pkId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
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
    .catch(() => alert('Terjadi kesalahan saat menghapus data PK.'));
}

