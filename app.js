const API_URL = 'api/license_keys.php';

let allLicenses = [];

document.addEventListener('DOMContentLoaded', function() {
    loadLicenses();
    loadStatistics();
    
    document.getElementById('searchInput').addEventListener('input', filterLicenses);
});

async function loadLicenses() {
    showLoading(true);
    
    try {
        const response = await fetch(`${API_URL}?action=list`);
        const result = await response.json();
        
        if (result.success) {
            allLicenses = result.data;
            displayLicenses(allLicenses);
        } else {
            showAlert('Failed to load licenses: ' + result.error, 'danger');
        }
    } catch (error) {
        showAlert('Error loading licenses: ' + error.message, 'danger');
    } finally {
        showLoading(false);
    }
}

async function loadStatistics() {
    try {
        const response = await fetch(`${API_URL}?action=stats`);
        const result = await response.json();
        
        if (result.success) {
            const stats = result.data.totals;
            document.getElementById('stat-total').textContent = stats.total || 0;
            document.getElementById('stat-active').textContent = stats.active || 0;
            document.getElementById('stat-inactive').textContent = stats.inactive || 0;
            document.getElementById('stat-expired').textContent = stats.expired || 0;
        }
    } catch (error) {
        console.error('Failed to load statistics:', error);
    }
}

function displayLicenses(licenses) {
    const tbody = document.getElementById('licenseTableBody');
    
    if (licenses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No licenses found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = licenses.map(license => `
        <tr class="animate__animated animate__fadeIn">
            <td><strong>${license.id}</strong></td>
            <td><code>${license.license_key}</code></td>
            <td><code>${license.machine_code}</code></td>
            <td><strong>${license.software}</strong></td>
            <td><span class="badge badge-${license.status}">${license.status.toUpperCase()}</span></td>
            <td>${formatDate(license.activation_date)}</td>
            <td>${license.expiration_date ? formatDate(license.expiration_date) : '<span class="text-muted">Never</span>'}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editLicense(${license.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-action btn-delete" onclick="deleteLicense(${license.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function filterLicenses() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    const filtered = allLicenses.filter(license => 
        license.license_key.toLowerCase().includes(searchTerm) ||
        license.machine_code.toLowerCase().includes(searchTerm) ||
        license.software.toLowerCase().includes(searchTerm) ||
        license.status.toLowerCase().includes(searchTerm)
    );
    
    displayLicenses(filtered);
}

async function createLicense() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    if (!data.machine_code || !data.software) {
        showAlert('Please fill in all required fields', 'warning');
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(`License created successfully!<br>License Key: <strong>${result.data.license_key}</strong>`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
            form.reset();
            loadLicenses();
            loadStatistics();
        } else {
            showAlert('Failed to create license: ' + result.error, 'danger');
        }
    } catch (error) {
        showAlert('Error creating license: ' + error.message, 'danger');
    }
}

async function editLicense(id) {
    const license = allLicenses.find(l => l.id == id);
    
    if (!license) {
        showAlert('License not found', 'danger');
        return;
    }
    
    document.getElementById('edit_id').value = license.id;
    document.getElementById('edit_status').value = license.status;
    document.getElementById('edit_software').value = license.software;
    
    if (license.expiration_date) {
        const date = new Date(license.expiration_date);
        document.getElementById('edit_expiration').value = date.toISOString().slice(0, 16);
    }
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

async function updateLicense() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${API_URL}?action=update`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('License updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadLicenses();
            loadStatistics();
        } else {
            showAlert('Failed to update license: ' + result.error, 'danger');
        }
    } catch (error) {
        showAlert('Error updating license: ' + error.message, 'danger');
    }
}

async function deleteLicense(id) {
    if (!confirm('Are you sure you want to delete this license? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_URL}?action=delete&id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('License deleted successfully!', 'success');
            loadLicenses();
            loadStatistics();
        } else {
            showAlert('Failed to delete license: ' + result.error, 'danger');
        }
    } catch (error) {
        showAlert('Error deleting license: ' + error.message, 'danger');
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return date.toLocaleDateString('en-US', options);
}

function showLoading(show) {
    const loading = document.getElementById('loading');
    if (show) {
        loading.classList.add('active');
    } else {
        loading.classList.remove('active');
    }
}

function showAlert(message, type = 'info') {
    const alertContainer = document.querySelector('.main-container');
    const alertId = 'alert-' + Date.now();
    
    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-custom alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('afterbegin', alertHTML);
    
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.add('animate__fadeOut');
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);
}
