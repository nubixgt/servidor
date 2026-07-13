// Global state
let currentMatchResults = [];
let currentQueryPage = 1;
let currentQueryTotal = 0;
const recordsPerPage = 50;

// Elements
const statsTotalRecords = document.getElementById('stats-total-records');
const statsTotalVillages = document.getElementById('stats-total-villages');
const municipioSelect = document.getElementById('municipio-select');
const queryMunicipioSelect = document.getElementById('query-municipio-select');

// Dashboard elements
const dashTotalRecords = document.getElementById('dash-total-records');
const dashTotalMunicipios = document.getElementById('dash-total-municipios');
const dashTotalVillages = document.getElementById('dash-total-villages');
const dashboardRankedMunicipios = document.getElementById('dashboard-ranked-municipios');
const dashboardActivityTimeline = document.getElementById('dashboard-activity-timeline');
const syncLastUpdate = document.getElementById('sync-last-update');
const btnQuickExport = document.getElementById('btn-quick-export');

const tabButtons = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

const methodButtons = document.querySelectorAll('.method-btn');
const methodContents = document.querySelectorAll('.method-content');

const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const fileInfo = document.getElementById('file-info');
const fileNameSpan = document.getElementById('file-name');

const btnRunMatch = document.getElementById('btn-run-match');
const btnExportExcel = document.getElementById('btn-export-excel');
const textareaAldeas = document.getElementById('aldeas-textarea');

const resultsSummary = document.getElementById('results-summary');
const sumTotal = document.getElementById('sum-total');
const sumMatched = document.getElementById('sum-matched');
const sumUnmatched = document.getElementById('sum-unmatched');
const sumPeople = document.getElementById('sum-people');
const resultsBody = document.getElementById('results-body');

const querySearchInput = document.getElementById('query-search-input');
const btnClearSearch = document.getElementById('btn-clear-search');
const btnQuerySearch = document.getElementById('btn-query-search');
const queryResultsMeta = document.getElementById('query-results-meta');
const queryResultsCount = document.getElementById('query-results-count');
const queryResultsTableBody = document.querySelector('#query-results-table tbody');
const queryPagination = document.getElementById('query-pagination');
const btnPrevPage = document.getElementById('btn-prev-page');
const btnNextPage = document.getElementById('btn-next-page');
const pageIndicator = document.getElementById('page-indicator');

const loadingSpinner = document.getElementById('loading-spinner');
const spinnerText = document.getElementById('spinner-text');

// Theme toggle elements
const themeToggleBtn = document.getElementById('theme-toggle-btn');

// Time elements
const headerDate = document.getElementById('header-date');
const headerTime = document.getElementById('header-time');

// Helper to show/hide loading spinner
function showSpinner(text) {
    spinnerText.textContent = text || 'Procesando...';
    loadingSpinner.classList.add('active');
}

function hideSpinner() {
    loadingSpinner.classList.remove('active');
}

// Format numbers with commas (e.g. 136005 -> 136,005)
function formatNumber(num) {
    if (num === undefined || num === null) return "0";
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Real-time Clock
function startClock() {
    const months = [
        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
    ];
    
    function updateTime() {
        const now = new Date();
        const day = now.getDate();
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();
        
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        const hourStr = String(hours).padStart(2, '0');
        
        headerDate.textContent = `${day} ${monthName} ${year}`;
        headerTime.textContent = `${hourStr}:${minutes}:${seconds} ${ampm}`;
    }
    
    updateTime();
    setInterval(updateTime, 1000);
}

// Theme Management
function initTheme() {
    // Default theme is light, check local storage
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    } else {
        document.body.classList.remove('dark-theme');
    }
}

themeToggleBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-theme');
    const isDark = document.body.classList.contains('dark-theme');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
});

// Add activity log item
function addActivityLog(timeStr, iconClass, title, description, status) {
    const timeline = document.getElementById('dashboard-activity-timeline');
    
    const item = document.createElement('div');
    item.className = 'timeline-item';
    
    let statusClass = 'status-completed';
    if (status === 'procesando') statusClass = 'status-processing';
    else if (status === 'pendiente') statusClass = 'status-pending';
    
    item.innerHTML = `
        <span class="timeline-time">${timeStr}</span>
        <div class="timeline-badge bg-blue"><i class="${iconClass}"></i></div>
        <div class="timeline-content">
            <h4>${title}</h4>
            <p>${description}</p>
        </div>
        <span class="status-pill ${statusClass}">${status}</span>
    `;
    
    // Insert at the top of the timeline, keeping maximum 4 items
    timeline.insertBefore(item, timeline.firstChild);
    if (timeline.children.length > 4) {
        timeline.removeChild(timeline.lastChild);
    }
    
    // Update last sync text
    syncLastUpdate.textContent = `Última actualización: Hace unos instantes`;
}

// Initialize application data
async function initApp() {
    showSpinner('Inicializando Centro de Datos...');
    try {
        const response = await fetch('/api/stats');
        if (!response.ok) throw new Error('No se pudieron obtener estadísticas');
        const data = await response.json();
        
        // Populate dashboard cards
        dashTotalRecords.textContent = formatNumber(data.total_registros);
        dashTotalVillages.textContent = formatNumber(data.total_aldeas);
        dashTotalMunicipios.textContent = formatNumber(data.total_municipios);
        
        // Clean municipio selectors and fill
        municipioSelect.innerHTML = '<option value="">Todos los Municipios (Búsqueda Global)</option>';
        queryMunicipioSelect.innerHTML = '<option value="">Todos los Municipios</option>';
        
        data.municipios.forEach(muni => {
            const opt1 = document.createElement('option');
            opt1.value = muni;
            opt1.textContent = muni;
            municipioSelect.appendChild(opt1);
            
            const opt2 = document.createElement('option');
            opt2.value = muni;
            opt2.textContent = muni;
            queryMunicipioSelect.appendChild(opt2);
        });
        
        // Populate Top Municipios ranked list
        dashboardRankedMunicipios.innerHTML = '';
        const maxVal = data.top_municipios[0] ? data.top_municipios[0].count : 1;
        
        data.top_municipios.slice(0, 5).forEach((muni, index) => {
            const pct = (muni.count / maxVal) * 100;
            const rankItem = document.createElement('div');
            rankItem.className = 'ranked-item';
            
            rankItem.innerHTML = `
                <span class="rank-num">${index + 1}</span>
                <div class="rank-details">
                    <div class="rank-name-row">
                        <span class="rank-name">${muni.name}</span>
                        <span class="rank-val">${formatNumber(muni.count)}</span>
                    </div>
                    <div class="rank-progress-bar">
                        <div class="progress-fill" style="width: 0%;"></div>
                    </div>
                </div>
            `;
            dashboardRankedMunicipios.appendChild(rankItem);
            
            // Trigger animation
            setTimeout(() => {
                const fill = rankItem.querySelector('.progress-fill');
                if (fill) fill.style.width = `${pct}%`;
            }, 100 + index * 50);
        });
        
    } catch (error) {
        console.error(error);
        alert('Error al conectar con el backend. Asegúrate de que el servidor esté corriendo.');
    } finally {
        hideSpinner();
    }
}

// Navigation Tabs switching
tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const targetTab = btn.getAttribute('data-tab');
        
        tabButtons.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(targetTab).classList.add('active');
    });
});

// Input Methods switching
methodButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const targetMethod = btn.getAttribute('data-method');
        
        methodButtons.forEach(b => b.classList.remove('active'));
        methodContents.forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(targetMethod).classList.add('active');
    });
});

// Drag and drop events for Excel file
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    if (e.dataTransfer.files.length > 0) {
        handleFileSelection(e.dataTransfer.files[0]);
    }
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        handleFileSelection(fileInput.files[0]);
    }
});

function handleFileSelection(file) {
    if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
        fileNameSpan.textContent = file.name;
        fileInfo.style.display = 'inline-flex';
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    } else {
        alert('Por favor, selecciona un archivo Excel válido (.xlsx o .xls)');
        fileInput.value = '';
        fileInfo.style.display = 'none';
    }
}

// ==========================================
// Cruce de Aldeas Logic
// ==========================================
btnRunMatch.addEventListener('click', async () => {
    const isTextMethod = document.querySelector('.method-btn[data-method="method-text"]').classList.contains('active');
    const muniFilter = municipioSelect.value;
    
    showSpinner('Cruzando listados con el padrón... Por favor espera.');
    
    try {
        let results = [];
        let sourceName = "";
        
        if (isTextMethod) {
            const text = textareaAldeas.value.trim();
            if (!text) {
                alert('Ingresa al menos una aldea para buscar.');
                hideSpinner();
                return;
            }
            
            const aldeasList = text.split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);
                
            sourceName = `Texto pegado (${aldeasList.length} aldeas)`;
            
            const response = await fetch('/api/match', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    aldeas: aldeasList,
                    municipio_filter: muniFilter || null
                })
            });
            
            if (!response.ok) throw new Error('Error al procesar coincidencia');
            results = await response.json();
            
        } else {
            // File upload method
            if (fileInput.files.length === 0) {
                alert('Por favor, sube un archivo de Excel.');
                hideSpinner();
                return;
            }
            
            sourceName = fileInput.files[0].name;
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            if (muniFilter) {
                formData.append('municipio_filter', muniFilter);
            }
            
            const response = await fetch('/api/match-file', {
                body: formData,
                method: 'POST'
            });
            
            if (!response.ok) throw new Error('Error al procesar coincidencia desde archivo');
            results = await response.json();
        }
        
        currentMatchResults = results;
        renderMatchResults(results);
        
        // Log activity in real-time
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        addActivityLog(
            timeStr, 
            isTextMethod ? 'fa-solid fa-map-location-dot' : 'fa-solid fa-file-excel',
            'Cruce de aldeas completado',
            `Se cruzó: "${sourceName}".`,
            'Completado'
        );
        
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error al procesar el cruce de aldeas: ' + error.message);
    } finally {
        hideSpinner();
    }
});

function renderMatchResults(results) {
    if (results.length === 0) {
        resultsSummary.style.display = 'none';
        btnExportExcel.disabled = true;
        resultsBody.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h3>No se encontraron registros</h3>
                <p>Las aldeas ingresadas no generaron coincidencias ni entradas.</p>
            </div>
        `;
        return;
    }
    
    // Calculate stats
    let totalRequested = results.length;
    let matchedCount = 0;
    let unmatchedCount = 0;
    let totalPeopleCount = 0;
    
    // Group results by Municipio
    const groups = {};
    
    results.forEach(item => {
        if (item.match && item.match !== 'SIN COINCIDENCIA') {
            matchedCount++;
            totalPeopleCount += item.count;
        } else {
            unmatchedCount++;
        }
        
        const muniKey = item.municipio;
        if (!groups[muniKey]) {
            groups[muniKey] = [];
        }
        groups[muniKey].push(item);
    });
    
    // Update summary cards
    sumTotal.textContent = formatNumber(totalRequested);
    sumMatched.textContent = formatNumber(matchedCount);
    sumUnmatched.textContent = formatNumber(unmatchedCount);
    sumPeople.textContent = formatNumber(totalPeopleCount);
    resultsSummary.style.display = 'grid';
    btnExportExcel.disabled = false;
    
    // Render groups
    resultsBody.innerHTML = '';
    
    const sortedMunicipios = Object.keys(groups).sort((a, b) => {
        if (a === 'No Encontrado') return 1;
        if (b === 'No Encontrado') return -1;
        return a.localeCompare(b);
    });
    
    sortedMunicipios.forEach(muni => {
        const items = groups[muni];
        const groupCard = document.createElement('div');
        groupCard.className = 'muni-group-card';
        
        const header = document.createElement('div');
        header.className = 'muni-group-title';
        
        const isMatchedMuni = muni !== 'No Encontrado';
        const displayMuniName = isMatchedMuni ? muni : 'Aldeas Sin Coincidencia';
        header.innerHTML = `
            <span>${displayMuniName} | Aldeas:</span>
            <span class="muni-badge">${items.length} ${items.length === 1 ? 'item' : 'items'}</span>
        `;
        groupCard.appendChild(header);
        
        const listContainer = document.createElement('div');
        listContainer.className = 'muni-village-list';
        
        items.forEach(item => {
            const row = document.createElement('div');
            const hasMatch = item.match && item.match !== 'SIN COINCIDENCIA';
            row.className = `village-match-row ${hasMatch ? 'matched' : 'unmatched'}`;
            
            if (hasMatch) {
                row.innerHTML = `
                    <div class="row-text">
                        <strong>${item.target}</strong>
                        <span class="matched-name"> &rarr; ${item.match}</span>
                    </div>
                    <div class="row-count">${formatNumber(item.count)}</div>
                `;
            } else {
                row.innerHTML = `
                    <div class="row-text">
                        <strong>${item.target}</strong>
                        <span class="matched-name"> &rarr; Sin Coincidencia</span>
                    </div>
                    <div class="row-count">0</div>
                `;
            }
            listContainer.appendChild(row);
        });
        
        groupCard.appendChild(listContainer);
        resultsBody.appendChild(groupCard);
    });
}

// Download Excel File Trigger
async function triggerExcelDownload(resultsArray) {
    if (resultsArray.length === 0) {
        alert('Realiza primero un cruce de aldeas para exportar.');
        return;
    }
    
    showSpinner('Generando archivo Excel de descarga...');
    try {
        const response = await fetch('/api/export', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(resultsArray)
        });
        
        if (!response.ok) throw new Error('Error al generar la exportación');
        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `cruce_aldeas_progreso_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
    } catch (error) {
        console.error(error);
        alert('No se pudo exportar el Excel: ' + error.message);
    } finally {
        hideSpinner();
    }
}

btnExportExcel.addEventListener('click', () => triggerExcelDownload(currentMatchResults));
btnQuickExport.addEventListener('click', () => triggerExcelDownload(currentMatchResults));

// ==========================================
// Padrón Direct Queries Logic
// ==========================================
async function searchPadron(page = 1) {
    const q = querySearchInput.value.trim();
    const muni = queryMunicipioSelect.value;
    
    showSpinner('Buscando en el padrón electoral...');
    try {
        const params = new URLSearchParams({
            q: q,
            page: page.toString(),
            limit: recordsPerPage.toString()
        });
        if (muni) {
            params.append('municipio', muni);
        }
        
        const response = await fetch(`/api/query?${params.toString()}`);
        if (!response.ok) throw new Error('Error en consulta');
        const data = await response.json();
        
        currentQueryPage = data.page;
        currentQueryTotal = data.total;
        
        renderQueryResults(data.records);
        updatePagination();
        
        // Log query activity
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        addActivityLog(
            timeStr, 
            'fa-solid fa-magnifying-glass',
            'Búsqueda en Padrón realizada',
            `Consulta: "${q || 'Ver todos'}" (${data.total} encontrados)`,
            'Completado'
        );
        
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error al realizar la consulta: ' + error.message);
    } finally {
        hideSpinner();
    }
}

function renderQueryResults(records) {
    queryResultsTableBody.innerHTML = '';
    
    if (records.length === 0) {
        queryResultsMeta.style.display = 'none';
        queryPagination.style.display = 'none';
        queryResultsTableBody.innerHTML = `
            <tr>
                <td colspan="5" class="table-empty">
                    <i class="fa-solid fa-search-minus"></i>
                    <p>No se encontraron ciudadanos que coincidan con la búsqueda.</p>
                </td>
            </tr>
        `;
        return;
    }
    
    queryResultsCount.textContent = `Encontrados: ${formatNumber(currentQueryTotal)} ciudadanos`;
    queryResultsMeta.style.display = 'block';
    
    records.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-family: monospace; font-weight: 600; color: var(--accent-blue);">${row.DPI}</td>
            <td style="font-weight: 500;">${row['NOMBRE CIUDADANO']}</td>
            <td>${row.MUNICIPIO}</td>
            <td>${row.ALDEA}</td>
            <td style="text-align: center; font-weight: 600; color: var(--accent-purple);">${row.EDAD}</td>
        `;
        queryResultsTableBody.appendChild(tr);
    });
}

function updatePagination() {
    const totalPages = Math.ceil(currentQueryTotal / recordsPerPage);
    pageIndicator.textContent = `Página ${currentQueryPage} de ${totalPages || 1}`;
    
    btnPrevPage.disabled = currentQueryPage <= 1;
    btnNextPage.disabled = currentQueryPage >= totalPages;
    
    queryPagination.style.display = totalPages > 1 ? 'flex' : 'none';
}

// Search interactions
btnQuerySearch.addEventListener('click', () => searchPadron(1));

querySearchInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
        searchPadron(1);
    }
    if (querySearchInput.value.length > 0) {
        btnClearSearch.style.display = 'block';
    } else {
        btnClearSearch.style.display = 'none';
    }
});

btnClearSearch.addEventListener('click', () => {
    querySearchInput.value = '';
    btnClearSearch.style.display = 'none';
    searchPadron(1);
});

btnPrevPage.addEventListener('click', () => {
    if (currentQueryPage > 1) {
        searchPadron(currentQueryPage - 1);
    }
});

btnNextPage.addEventListener('click', () => {
    const totalPages = Math.ceil(currentQueryTotal / recordsPerPage);
    if (currentQueryPage < totalPages) {
        searchPadron(currentQueryPage + 1);
    }
});

// Run app init
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    startClock();
    initApp();
});
