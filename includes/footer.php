<?php
// Footer include
?>
<footer class="footer mt-auto py-3 bg-white border-top">
    <div class="container-fluid container-xl d-flex flex-wrap justify-content-between align-items-center text-muted small">
        <div>
            <span>&copy; <?= date('Y') ?> <strong>User Management System</strong> &bull; Secure Authentication & Profile Portal</span>
        </div>
        <div class="d-flex gap-3">
            <span class="d-flex align-items-center gap-1"><i class="bi bi-shield-check text-success"></i> PHP 8.2 & MariaDB</span>
            <span class="d-none d-sm-inline">&bull;</span>
            <span>Local XAMPP Environment</span>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-dismiss alert boxes after 5 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 6000);
    });
});

// Password toggle helper function
function togglePasswordVisibility(inputId, toggleBtnId) {
    const input = document.getElementById(inputId);
    const btn = document.getElementById(toggleBtnId);
    if (!input || !btn) return;
    
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
</script>
</body>
</html>
