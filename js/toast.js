/**
 * js/toast.js
 *
 * Global toast notification system for FitHub.
 * Displays alerts in the bottom-right corner.
 */

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Bottom-right styling
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#495057'};
        color: white;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        z-index: 9999;
        animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: var(--font-body);
        font-size: 0.95rem;
        border-left: 4px solid ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#2563eb'};
        pointer-events: auto;
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
        setTimeout(() => toast.remove(), 300);
    }, 4000); // 4 seconds visibility
}

// Toast animations
if (!document.querySelector('style[data-toasts]')) {
    const style = document.createElement('style');
    style.setAttribute('data-toasts', '');
    style.textContent = `
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(100px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
        }
    `;
    document.head.appendChild(style);
}
