// Renstra Table Management JavaScript

// Period data for dynamic filtering - will be set from PHP
let periodData = {};

// Set period data function (called from PHP)
function setPeriodData(data) {
    periodData = data;
}

// Filter by period
function filterByPeriode() {
    const selectedPeriod = document.getElementById('periode-filter').value;
    const rows = document.querySelectorAll('#renstra-table-body tr');
    
    // Show/hide year headers
    document.querySelectorAll('.year-header').forEach(header => {
        if (selectedPeriod === '' || header.dataset.periode === selectedPeriod) {
            header.style.display = '';
        } else {
            header.style.display = 'none';
        }
    });
    
    // Show/hide year cells
    document.querySelectorAll('.year-cells').forEach(cells => {
        if (selectedPeriod === '' || cells.dataset.periode === selectedPeriod) {
            cells.style.display = '';
        } else {
            cells.style.display = 'none';
        }
    });
    
    // Filter rows
    rows.forEach(row => {
        if (selectedPeriod === '' || row.dataset.periode === selectedPeriod) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Reapply status filter
    filterByStatus();
}

// Filter by status
function filterByStatus() {
    const selectedStatus = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('#renstra-table-body tr');
    
    rows.forEach(row => {
        const isVisibleByPeriod = row.style.display !== 'none';
        const rowStatus = row.dataset.status;
        
        if (isVisibleByPeriod && (selectedStatus === '' || rowStatus === selectedStatus)) {
            row.style.display = '';
        } else if (selectedStatus !== '' && rowStatus !== selectedStatus) {
            row.style.display = 'none';
        }
    });
}

// Delete function
function deleteRenstra(id, baseUrl) {
    if (confirm('Apakah Anda yakin ingin menghapus data Renstra ini?')) {
        fetch(`${baseUrl}adminopd/renstra/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Terjadi kesalahan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        });
    }
}

// Function to toggle status via AJAX
function toggleStatus(sasaranId, baseUrl, csrfHeader, csrfHash) {
    if (confirm('Apakah Anda yakin ingin mengubah status Renstra ini?')) {
        fetch(`${baseUrl}adminopd/renstra/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                [csrfHeader]: csrfHash
            },
            body: JSON.stringify({
                id: sasaranId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated status
                window.location.reload();
            } else {
                alert('Gagal mengubah status: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengubah status');
        });
    }
}

// Initialize filters on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-select latest period and apply filter
    const periodeSelect = document.getElementById('periode-filter');
    if (periodeSelect && periodeSelect.value) {
        filterByPeriode();
    }
});
