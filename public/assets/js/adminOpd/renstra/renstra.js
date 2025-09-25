// Renstra Table Management JavaScript

// Store original data for filtering
let originalData = [];

// Function to filter and rebuild table
function filterByPeriode() {
    filterData();
}

function filterByStatus() {
    filterData();
}

function filterByRpjmd() {
    filterData();
}

function filterData() {
    const selectedPeriod = document.getElementById('periode-filter').value;
    const selectedStatus = document.getElementById('status-filter').value;
    const selectedRpjmd = document.getElementById('rpjmd-filter').value;
    
    // Filter original data
    const filteredData = originalData.filter(function(data) {
        const periodMatch = selectedPeriod === '' || 
            (data.tahun_mulai + '-' + data.tahun_akhir) === selectedPeriod;
        const statusMatch = selectedStatus === '' || data.status === selectedStatus;
        const rpjmdMatch = selectedRpjmd === '' || data.rpjmd_sasaran === selectedRpjmd;
        
        return periodMatch && statusMatch && rpjmdMatch;
    });
    
    // Update table headers based on selected period
    updateTableHeaders(selectedPeriod);
    
    // Rebuild table
    rebuildTable(filteredData, selectedPeriod);
}

function updateTableHeaders(selectedPeriod) {
    if (typeof window.periodData !== 'undefined' && Object.keys(window.periodData).length > 0) {
        const yearHeaderSpan = document.querySelector('th[colspan]');
        const yearHeaders = document.querySelectorAll('.year-header');
        
        if (selectedPeriod === '') {
            // Show all years from all periods
            let allYears = new Set();
            Object.keys(window.periodData).forEach(periodKey => {
                if (window.periodData[periodKey].years) {
                    window.periodData[periodKey].years.forEach(year => allYears.add(year));
                }
            });
            
            const sortedYears = Array.from(allYears).sort((a, b) => a - b);
            
            // Update colspan
            if (yearHeaderSpan) {
                yearHeaderSpan.setAttribute('colspan', sortedYears.length);
            }
            
            // Show/hide year headers
            yearHeaders.forEach(header => {
                const headerYear = parseInt(header.textContent);
                if (sortedYears.includes(headerYear)) {
                    header.style.display = '';
                } else {
                    header.style.display = 'none';
                }
            });
        } else {
            // Show only years from selected period
            if (window.periodData[selectedPeriod] && window.periodData[selectedPeriod].years) {
                const years = window.periodData[selectedPeriod].years;
                
                // Update colspan
                if (yearHeaderSpan) {
                    yearHeaderSpan.setAttribute('colspan', years.length);
                }
                
                // Show/hide year headers based on selected period
                yearHeaders.forEach(header => {
                    const headerYear = parseInt(header.textContent);
                    if (years.includes(headerYear)) {
                        header.style.display = '';
                    } else {
                        header.style.display = 'none';
                    }
                });
            }
        }
    }
}

function rebuildTable(data, selectedPeriod) {
    const tbody = document.getElementById('renstra-table-body');
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        // Show no data message
        const totalColumns = 6 + (getCurrentYearColumns(selectedPeriod)); // RPJMD Sasaran, Sasaran, Indikator, Satuan, Status, Action + Target columns
        tbody.innerHTML = `
            <tr>
                <td colspan="${totalColumns}" class="border p-3 text-center text-muted">
                    Belum ada data Renstra. <a href="${window.base_url}renstra/tambah" class="text-success">Tambah data pertama</a>
                </td>
            </tr>
        `;
        return;
    }
    
    // Group data by RPJMD sasaran first, then by sasaran
    const groupedByRpjmd = {};
    data.forEach(function(item) {
        const rpjmdKey = item.rpjmd_sasaran;
        if (!groupedByRpjmd[rpjmdKey]) {
            groupedByRpjmd[rpjmdKey] = {};
        }
        
        const sasaranKey = item.sasaran_id;
        if (!groupedByRpjmd[rpjmdKey][sasaranKey]) {
            groupedByRpjmd[rpjmdKey][sasaranKey] = [];
        }
        
        groupedByRpjmd[rpjmdKey][sasaranKey].push(item);
    });
    
    // Calculate rowspans
    const rpjmdRowspan = {};
    const sasaranRowspan = {};
    
    Object.keys(groupedByRpjmd).forEach(rpjmdKey => {
        rpjmdRowspan[rpjmdKey] = 0;
        Object.keys(groupedByRpjmd[rpjmdKey]).forEach(sasaranKey => {
            const itemCount = groupedByRpjmd[rpjmdKey][sasaranKey].length;
            sasaranRowspan[sasaranKey] = itemCount;
            rpjmdRowspan[rpjmdKey] += itemCount;
        });
    });
    
    // Build table rows
    Object.keys(groupedByRpjmd).forEach(rpjmdKey => {
        let isFirstRowOfRpjmd = true;
        
        Object.keys(groupedByRpjmd[rpjmdKey]).forEach(sasaranKey => {
            let isFirstRowOfSasaran = true;
            
            groupedByRpjmd[rpjmdKey][sasaranKey].forEach((item, index) => {
                const row = document.createElement('tr');
                row.setAttribute('data-periode', item.tahun_mulai + '-' + item.tahun_akhir);
                row.setAttribute('data-status', item.status || 'draft');
                row.setAttribute('data-rpjmd', item.rpjmd_sasaran);
                
                let html = '';
                
                // RPJMD Sasaran - show only once per RPJMD group
                if (isFirstRowOfRpjmd) {
                    html += `<td class="border p-2" rowspan="${rpjmdRowspan[rpjmdKey]}">${escapeHtml(item.rpjmd_sasaran || 'N/A')}</td>`;
                    isFirstRowOfRpjmd = false;
                }
                
                // Sasaran - show only once per sasaran
                if (isFirstRowOfSasaran) {
                    html += `<td class="border p-2" rowspan="${sasaranRowspan[sasaranKey]}">${escapeHtml(item.sasaran || 'N/A')}</td>`;
                    isFirstRowOfSasaran = false;
                }
                
                // Indikator and Satuan
                html += `
                    <td class="border p-2">${escapeHtml(item.indikator_sasaran || 'N/A')}</td>
                    <td class="border p-2">${escapeHtml(item.satuan || 'N/A')}</td>
                `;
                
                // Target per tahun
                html += buildTargetColumns(item, selectedPeriod);
                
                // Status - show only once per sasaran
                if (index === 0) {
                    const status = item.status || 'draft';
                    const badgeClass = status === 'selesai' ? 'bg-success' : 'bg-warning';
                    html += `
                        <td class="border p-2" rowspan="${sasaranRowspan[sasaranKey]}">
                            <button class="badge ${badgeClass} border-0" 
                                    onclick="toggleStatus(${item.sasaran_id})" 
                                    style="cursor: pointer;" 
                                    title="Klik untuk mengubah status">
                                ${status.charAt(0).toUpperCase() + status.slice(1)}
                            </button>
                        </td>
                    `;
                }
                
                // Action - show only once per sasaran
                if (index === 0) {
                    html += `
                        <td class="border p-2 align-middle text-center" rowspan="${sasaranRowspan[sasaranKey]}">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <a href="/adminopd/renstra/edit/${item.sasaran_id}" class="btn btn-success btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(${item.sasaran_id})">
                                    <i class="fas fa-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </td>
                    `;
                }
                
                row.innerHTML = html;
                tbody.appendChild(row);
            });
        });
    });
}

function buildTargetColumns(item, selectedPeriod) {
    let html = '';
    
    if (typeof window.periodData !== 'undefined' && Object.keys(window.periodData).length > 0) {
        if (selectedPeriod === '') {
            // Show all periods but only years that exist in periodData
            let allYears = new Set();
            Object.keys(window.periodData).forEach(periodKey => {
                if (window.periodData[periodKey].years) {
                    window.periodData[periodKey].years.forEach(year => allYears.add(year));
                }
            });
            
            const sortedYears = Array.from(allYears).sort((a, b) => a - b);
            
            sortedYears.forEach(year => {
                const dataPeriodKey = item.tahun_mulai + '-' + item.tahun_akhir;
                let target = '-';
                
                // Check if this item's period contains this year and has target data
                if (year >= item.tahun_mulai && year <= item.tahun_akhir && 
                    item.targets && item.targets[year]) {
                    target = item.targets[year];
                }
                
                html += `<td class="border p-2 align-top text-start">${escapeHtml(target)}</td>`;
            });
        } else {
            // Show specific period only
            if (window.periodData[selectedPeriod] && window.periodData[selectedPeriod].years) {
                window.periodData[selectedPeriod].years.forEach(year => {
                    const target = (item.targets && item.targets[year]) ? item.targets[year] : '-';
                    html += `<td class="border p-2 align-top text-start">${escapeHtml(target)}</td>`;
                });
            }
        }
    } else {
        // Fallback for default years
        for (let year = 2025; year <= 2029; year++) {
            const target = (item.targets && item.targets[year]) ? item.targets[year] : '-';
            html += `<td class="border p-2">${escapeHtml(target)}</td>`;
        }
    }
    
    return html;
}

function getCurrentYearColumns(selectedPeriod) {
    if (typeof window.periodData !== 'undefined' && Object.keys(window.periodData).length > 0) {
        if (selectedPeriod === '') {
            // Count all unique years from all periods
            let allYears = new Set();
            Object.keys(window.periodData).forEach(periodKey => {
                if (window.periodData[periodKey].years) {
                    window.periodData[periodKey].years.forEach(year => allYears.add(year));
                }
            });
            return allYears.size;
        } else if (window.periodData[selectedPeriod] && window.periodData[selectedPeriod].years) {
            return window.periodData[selectedPeriod].years.length;
        }
    }
    return 5; // Default fallback
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text.toString();
    return div.innerHTML;
}

// Function to populate RPJMD sasaran filter options
function populateRpjmdFilter() {
    const rpjmdFilter = document.getElementById('rpjmd-filter');
    if (!rpjmdFilter || originalData.length === 0) return;
    
    const uniqueRpjmd = [...new Set(originalData.map(item => item.rpjmd_sasaran))];
    
    // Clear existing options except the first one (All)
    while (rpjmdFilter.children.length > 1) {
        rpjmdFilter.removeChild(rpjmdFilter.lastChild);
    }
    
    // Add unique RPJMD sasaran options
    uniqueRpjmd.forEach(function(sasaran) {
        const option = document.createElement('option');
        option.value = sasaran;
        option.textContent = sasaran.length > 80 ? sasaran.substring(0, 80) + '...' : sasaran;
        option.title = sasaran; // Full text in tooltip
        rpjmdFilter.appendChild(option);
    });
}

// Set original data from PHP
function setOriginalData(data) {
    originalData = data || [];
    populateRpjmdFilter();
}

// Global variables for PHP data
window.base_url = '';
window.csrf_header = '';
window.csrf_hash = '';

// Set period data for JavaScript
function setPeriodData(data) {
    if (typeof data !== 'undefined') {
        window.periodData = data;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Get initial period selection
    const periodeSelect = document.getElementById('periode-filter');
    const initialPeriod = periodeSelect ? periodeSelect.value : '';
    
    // Initial filter application
    filterData();
});
