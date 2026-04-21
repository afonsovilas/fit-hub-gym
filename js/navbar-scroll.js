document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('.header');

    if (!header) {
        return;
    }

    function updateHeaderState() {
        if (window.scrollY > 24) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }

    updateHeaderState();
    window.addEventListener('scroll', updateHeaderState, { passive: true });
});
