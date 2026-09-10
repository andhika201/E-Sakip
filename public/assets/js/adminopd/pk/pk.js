// Delete PK function
function deletePk(pkId, nama) {
    // Dialog milik aplikasi (app/Views/templates/konfirmasi.php), bukan confirm()
    // bawaan peramban. Sisa fungsi ini dijalankan setelah pengguna menekan "Ya".
    Konfirmasi.hapus({
        judul: 'Hapus Perjanjian Kinerja',
        pesan: 'Dokumen Perjanjian Kinerja ini akan dihapus permanen.',
        nama: nama || '',
        rincian: [
            'Seluruh sasaran, indikator, dan targetnya',
            'Program/kegiatan beserta pagu anggaran yang menyertainya',
            'Rencana aksi dan realisasi triwulanan yang sudah diisi'
        ]
    }).then(function (ya) {
        if (ya) { deletePkLanjut(pkId); }
    });
}

function deletePkLanjut(pkId) {
    const jenis = typeof window.jenis !== 'undefined' ? window.jenis : (window.pkJenis || 'administrator');
    if (typeof pkData === 'undefined') {
    console.warn('pkData not found, skipping pk.js');
    return;
    }
    // Token CSRF WAJIB ikut. Body-nya JSON, jadi tokennya dikirim lewat
    // header X-CSRF-TOKEN — field POST biasa tidak akan terbaca dari badan
    // JSON. Meta-nya dipasang di <head> bersama (adminOpd/templates/style.php),
    // sehingga tersedia di semua halaman admin.
    const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content || '';

    fetch(`${base_url}adminopd/pk/${jenis}/delete/${pkId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfHash,
        },
        credentials: 'same-origin',
    })
    .then(response => response.json())
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
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.querySelector('#pkTable tbody');
    const grouped = {};

    pkData.forEach(row => {
        if (!grouped[row.pk_id]) {
            grouped[row.pk_id] = {
                sasaran: {},
                jenis: row.jenis,
                tanggal: row.tanggal
            };
        }

        const g = grouped[row.pk_id];

        if (row.sasaran_id && !g.sasaran[row.sasaran_id]) {
            g.sasaran[row.sasaran_id] = {
                nama: row.sasaran,
                indikator: []
            };
        }

        if (row.indikator_id) {
            g.sasaran[row.sasaran_id].indikator.push({
                indikator: row.indikator,
                target: row.target
            });
        }

        if (!g.program) g.program = [];
        if (row.program_kegiatan) {
            g.program.push({
                nama: row.program_kegiatan,
                anggaran: row.anggaran
            });
        }
    });

    let no = 1;
    for (const [pkId, pk] of Object.entries(grouped)) {
        const programText = pk.program.map(p => `• ${p.nama}`).join('<br>');
        const anggaranText = pk.program.map(p => `Rp ${Number(p.anggaran).toLocaleString('id-ID')}`).join('<br>');

        // Nama untuk dialog konfirmasi: sasaran pertama PK ini.
        const pkNama = (Object.values(pk.sasaran)[0] || {}).nama || '';
        const pkNamaAttr = String(pkNama).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

        let isFirstRow = true;
        for (const sasaran of Object.values(pk.sasaran)) {
            for (const indikator of sasaran.indikator) {
                const tr = document.createElement('tr');

                if (isFirstRow) {
                    tr.innerHTML += `<td rowspan="${Object.values(pk.sasaran).reduce((t, s) => t + s.indikator.length, 0)}">${no++}</td>`;
                    isFirstRow = false;
                }

                tr.innerHTML += `
                    <td>${sasaran.nama}</td>
                    <td>${indikator.indikator}</td>
                    <td>${indikator.target}</td>
                    <td rowspan="${Object.values(pk.sasaran).reduce((t, s) => t + s.indikator.length, 0)}">${programText}</td>
                    <td rowspan="${Object.values(pk.sasaran).reduce((t, s) => t + s.indikator.length, 0)}">${anggaranText}</td>
                    <td rowspan="${Object.values(pk.sasaran).reduce((t, s) => t + s.indikator.length, 0)}">
                        <a href="${base_url}adminopd/pk_admin/cetak/${pkId}" class="text-primary">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </td>
                    <td rowspan="${Object.values(pk.sasaran).reduce((t, s) => t + s.indikator.length, 0)}">
                        <a href="${base_url}adminopd/pk_admin/edit/${pkId}" class="btn btn-sm btn-success">Edit</a>
                        <button class="btn btn-sm btn-danger" data-nama="${pkNamaAttr}" onclick="deletePk(${pkId}, this.dataset.nama)">Hapus</button>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        }
    }
});
