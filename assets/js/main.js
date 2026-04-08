/**
 * Society Connect - Main JavaScript
 * Handles UI interactions, dynamic behavior
 */

// ============================================================
// DOCUMENT READY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    initTooltips();
    initAlertAutoDismiss();
    initConfirmDialogs();
    initTopbarClock();
    animateStatNumbers();
    initSidebarActive();
});

// ============================================================
// BOOTSTRAP TOOLTIPS
// ============================================================
function initTooltips() {
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, { trigger: 'hover' });
        });
    }
}

// ============================================================
// AUTO-DISMISS ALERTS
// ============================================================
function initAlertAutoDismiss() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            } else {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 400);
            }
        }, 5000);
    });
}

// ============================================================
// CONFIRM DIALOGS (data-confirm attribute)
// ============================================================
function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });
}

// ============================================================
// TOPBAR CLOCK
// ============================================================
function initTopbarClock() {
    const el = document.getElementById('topbarClock');
    if (!el) return;
    const update = () => {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('en-IN', {
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    };
    update();
    setInterval(update, 1000);
}

// ============================================================
// ANIMATE STAT NUMBERS
// ============================================================
function animateStatNumbers() {
    document.querySelectorAll('.stat-num[data-target]').forEach(el => {
        const target  = parseFloat(el.dataset.target) || 0;
        const prefix  = el.dataset.prefix  || '';
        const suffix  = el.dataset.suffix  || '';
        const isFloat = el.dataset.float   === 'true';
        let current   = 0;
        const step    = target / 40;
        const timer   = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = prefix + (isFloat ? current.toFixed(2) : Math.floor(current).toLocaleString()) + suffix;
        }, 30);
    });
}

// ============================================================
// ACTIVE NAV LINK HIGHLIGHT
// ============================================================
function initSidebarActive() {
    const path = window.location.pathname;
    document.querySelectorAll('.nav-link-custom').forEach(link => {
        if (link.getAttribute('href') && path.includes(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });
}

// ============================================================
// FORM VALIDATION HELPER
// ============================================================
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    form.classList.add('was-validated');
    return form.checkValidity();
}

// ============================================================
// MINI SEARCH / TABLE FILTER
// ============================================================
function filterTable(inputId, tableId) {
    const query = document.getElementById(inputId)?.value?.toLowerCase() || '';
    const rows  = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}

// ============================================================
// BOOKING TIME VALIDATION
// ============================================================
function validateBookingTime() {
    const startInput = document.getElementById('start_time');
    const endInput   = document.getElementById('end_time');
    if (!startInput || !endInput) return true;
    if (endInput.value && startInput.value && endInput.value <= startInput.value) {
        alert('End time must be after start time.');
        endInput.value = '';
        return false;
    }
    return true;
}

// Set min booking date to today
(function setMinBookingDate() {
    const dateInput = document.getElementById('booking_date');
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
})();

// ============================================================
// STATUS COLOR MAP
// ============================================================
const STATUS_COLORS = {
    paid:        '#10B981',
    pending:     '#F59E0B',
    overdue:     '#EF4444',
    open:        '#EF4444',
    resolved:    '#10B981',
    in_progress: '#06B6D4',
    assigned:    '#F59E0B',
    inside:      '#10B981',
    exited:      '#94A3B8',
};

// ============================================================
// TOAST NOTIFICATION
// ============================================================
function showToast(msg, type = 'success') {
    const container = document.getElementById('toastContainer') || (() => {
        const c = document.createElement('div');
        c.id = 'toastContainer';
        c.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(c);
        return c;
    })();

    const bg = { success: '#10B981', error: '#EF4444', warning: '#F59E0B', info: '#06B6D4' }[type] || '#10B981';
    const toast = document.createElement('div');
    toast.style.cssText = `background:${bg};color:white;padding:14px 20px;border-radius:12px;font-weight:600;
        font-size:14px;box-shadow:0 8px 24px rgba(0,0,0,0.2);max-width:320px;
        animation:slideIn 0.3s ease;`;
    toast.textContent = msg;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// CSS for toast animation
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideIn { from { transform:translateX(120%); opacity:0; } to { transform:translateX(0); opacity:1; } }
    @keyframes slideOut { from { transform:translateX(0); opacity:1; } to { transform:translateX(120%); opacity:0; } }
`;
document.head.appendChild(toastStyle);

// ============================================================
// PRINT FUNCTIONALITY
// ============================================================
function printSection(sectionId) {
    const content = document.getElementById(sectionId)?.innerHTML;
    if (!content) return;
    const win = window.open('', '_blank');
    win.document.write(`
        <html><head><title>Print - Society Connect</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head><body class="p-4">${content}</body></html>
    `);
    win.document.close();
    win.print();
}
