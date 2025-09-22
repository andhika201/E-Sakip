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
                        <a href="${base_url}adminopd/pk_opd/cetak/${pkId}" class="text-primary">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </td>
                    <td rowspan="${Object.values(pk.sasaran).reduce((t, s) => t + s.indikator.length, 0)}">
                        <a href="${base_url}adminopd/pk_opd/edit/${pkId}" class="btn btn-sm btn-success">Edit</a>
                        <button class="btn btn-sm btn-danger" onclick="deletePk(${pkId})">Hapus</button>
                    </td>
                `;
                tbody.appendChild(tr);
            }
        }
    }
});

/**
 * Function to delete PK with confirmation
 * @param {number} pkId - The ID of the PK to delete
 * @param {string} baseUrl - Base URL for the application (optional, will use global base_url if not provided)
 */
function deletePk(pkId, baseUrl = null) {
    // Use global base_url if baseUrl not provided
    const url = baseUrl || (typeof base_url !== 'undefined' ? base_url : '');
    
    if (!url) {
        console.error('Base URL is not defined');
        alert('Terjadi kesalahan sistem. Silakan refresh halaman dan coba lagi.');
        return;
    }
    
    // Confirmation dialog
    const confirmMessage = 'Apakah Anda yakin ingin menghapus PK ini?\n\nData yang dihapus tidak dapat dikembalikan.';
    
    if (confirm(confirmMessage)) {
        // Show loading state (optional)
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Menghapus...';
        button.disabled = true;
        
        try {
            // Redirect to delete URL
            window.location.href = url + 'adminopd/pk_opd/delete/' + pkId;
        } catch (error) {
            console.error('Error during delete:', error);
            alert('Terjadi kesalahan saat menghapus data.');
            
            // Restore button state
            button.textContent = originalText;
            button.disabled = false;
        }
    }
}
