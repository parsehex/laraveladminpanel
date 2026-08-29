<script>
    window.scrollSidebarToActiveLink = function () {
        const collapsedDesktop = document.documentElement.classList.contains('sidebar-collapsed')
            && window.matchMedia('(min-width: 1024px)').matches;

        if (collapsedDesktop) {
            return;
        }

        const sidebar = document.querySelector('aside.ui-sidebar');

        if (! sidebar) {
            return;
        }

        const active = sidebar.querySelector('a.ui-nav-link.is-active')
            || sidebar.querySelector('.ui-nav-link.is-active');

        if (! active) {
            return;
        }

        active.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.scrollSidebarToActiveLink);
    } else {
        window.scrollSidebarToActiveLink();
    }
</script>
