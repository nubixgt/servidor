// --- ELEMENTOS DEL DOM ---
const searchInput = document.getElementById('search-input');
const clearBtn = document.getElementById('clear-btn');
const statusBar = document.getElementById('status-bar');
const resultsCount = document.getElementById('results-count');
const warningBadge = document.getElementById('warning-badge');
const resultsArea = document.getElementById('results-area');
const emptyState = document.getElementById('empty-state');
const loaderState = document.getElementById('loader-state');
const resultsGrid = document.getElementById('results-grid');
const noResultsState = document.getElementById('no-results-state');

// Elementos del Modal
const detailModal = document.getElementById('detail-modal');
const modalClose = document.getElementById('modal-close');
const modalName = document.getElementById('modal-name');
const modalBadgeAge = document.getElementById('modal-badge-age');
const modalDpi = document.getElementById('modal-dpi');
const modalDept = document.getElementById('modal-dept');
const modalMuni = document.getElementById('modal-muni');
const modalAldea = document.getElementById('modal-aldea');
const modalOkBtn = document.getElementById('modal-ok-btn');

// --- VARIABLES DE ESTADO ---
let searchTimeout = null;
let currentSearchVal = '';

// --- FUNCIONES AUXILIARES ---

// Formatear DPI en formato: XXXX XXXXX XXXX (por si viene sin espacios)
function formatDPI(dpi) {
    if (!dpi) return '---';
    const digits = dpi.replace(/\D/g, '');
    if (digits.length === 13) {
        return `${digits.substring(0, 4)} ${digits.substring(4, 9)} ${digits.substring(9, 13)}`;
    }
    return dpi;
}

// Mostrar/Ocultar estados
function showState(state) {
    emptyState.style.display = state === 'empty' ? 'block' : 'none';
    loaderState.style.display = state === 'loading' ? 'grid' : 'none';
    resultsGrid.style.display = state === 'results' ? 'grid' : 'none';
    noResultsState.style.display = state === 'no-results' ? 'block' : 'none';
    
    if (state !== 'results' && state !== 'loading') {
        statusBar.style.display = 'none';
    } else if (state === 'results') {
        statusBar.style.display = 'flex';
    }
}

// Cargar la información en el modal y abrirlo
function openModal(citizen) {
    modalName.textContent = citizen.nombre || 'SIN NOMBRE';
    modalBadgeAge.textContent = citizen.edad !== undefined ? `${citizen.edad} años` : 'Edad no reg.';
    modalDpi.textContent = formatDPI(citizen.dpi);
    modalDept.textContent = citizen.departamento || '---';
    modalMuni.textContent = citizen.municipio || '---';
    modalAldea.textContent = citizen.aldea || '---';
    
    detailModal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevenir scroll de fondo
}

// Cerrar el modal
function closeModal() {
    detailModal.style.display = 'none';
    document.body.style.overflow = ''; // Restaurar scroll
}

// Crear una tarjeta de ciudadano
function createCitizenCard(citizen) {
    const card = document.createElement('div');
    card.className = 'citizen-card';
    
    // Mapear Aldea (Colonia/Sector)
    const aldeaText = citizen.aldea || 'No especificada';
    
    card.innerHTML = `
        <div>
            <div class="card-header">
                <h4 class="card-name" title="${citizen.nombre}">${citizen.nombre.toLowerCase()}</h4>
                <span class="badge badge-age">${citizen.edad} años</span>
            </div>
            
            <div class="card-details">
                <div class="detail-row">
                    <span class="detail-icon">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <div class="detail-text">
                        <label>Ubicación</label>
                        <span>${citizen.departamento}, ${citizen.municipio}</span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <span class="detail-icon">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                    </span>
                    <div class="detail-text">
                        <label>Colonia / Sector / Aldea</label>
                        <span title="${aldeaText}">${aldeaText}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="dpi-container">
                <label>DPI</label>
                <span class="dpi-value">${formatDPI(citizen.dpi)}</span>
            </div>
            <span class="view-more-hint">
                Ver detalle
                <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </span>
        </div>
    `;
    
    // Evento de clic en la tarjeta
    card.addEventListener('click', () => openModal(citizen));
    
    return card;
}

// Realizar la búsqueda en la API
async function performSearch(query) {
    if (!query) {
        showState('empty');
        clearBtn.classList.remove('visible');
        return;
    }
    
    clearBtn.classList.add('visible');
    showState('loading');
    
    try {
        const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
        if (!response.ok) {
            throw new Error('Respuesta de red no válida');
        }
        
        const data = await response.json();
        
        if (data.error) {
            console.error(data.error);
            showState('no-results');
            return;
        }

        resultsGrid.innerHTML = '';
        
        if (data.results && data.results.length > 0) {
            // Generar tarjetas
            data.results.forEach(citizen => {
                const card = createCitizenCard(citizen);
                resultsGrid.appendChild(card);
            });
            
            // Actualizar barra de estado
            if (data.is_dpi) {
                resultsCount.innerHTML = `Búsqueda por DPI. Se encontró <span>1</span> registro coincidente.`;
                warningBadge.style.display = 'none';
            } else {
                resultsCount.innerHTML = `Se encontraron <span>${data.results.length}</span> ${data.results.length === 1 ? 'coincidencia' : 'coincidencias'}.`;
                warningBadge.style.display = data.has_more ? 'inline-block' : 'none';
            }
            
            showState('results');
        } else {
            showState('no-results');
        }
    } catch (error) {
        console.error('Error al realizar la búsqueda:', error);
        showState('no-results');
    }
}

// --- EVENTOS ---

// Entrada de texto (con debounce de 300ms)
searchInput.addEventListener('input', (e) => {
    const val = e.target.value.trim();
    
    if (val === '') {
        clearBtn.classList.remove('visible');
    } else {
        clearBtn.classList.add('visible');
    }

    if (val === currentSearchVal) return;
    
    currentSearchVal = val;
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(val);
    }, 300);
});

// Clic en limpiar
clearBtn.addEventListener('click', () => {
    searchInput.value = '';
    currentSearchVal = '';
    clearBtn.classList.remove('visible');
    searchInput.focus();
    showState('empty');
});

// Clic fuera del modal para cerrar
detailModal.addEventListener('click', (e) => {
    if (e.target === detailModal) {
        closeModal();
    }
});

// Clic en cerrar modal
modalClose.addEventListener('click', closeModal);
modalOkBtn.addEventListener('click', closeModal);

// Cerrar con Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && detailModal.style.display === 'flex') {
        closeModal();
    }
});
