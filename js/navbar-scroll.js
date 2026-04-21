document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('.header');
    const menuToggle = document.querySelector('.menu-toggle');
    const navbarMenu = document.querySelector('.navbar-menu');

    if (!header) {
        return;
    }

    if (menuToggle && navbarMenu) {
        if (!navbarMenu.id) {
            navbarMenu.id = 'primary-navigation';
        }

        menuToggle.setAttribute('aria-controls', navbarMenu.id);
        menuToggle.setAttribute('aria-expanded', 'false');

        menuToggle.addEventListener('click', function () {
            const isOpen = navbarMenu.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });

        navbarMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navbarMenu.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                navbarMenu.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
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
