<script>
    (function () {
        const main = document.querySelector('main');
        if (!main) {
            return;
        }

        const storageKey = 'scroll:' + location.pathname + location.search;
        const tables = Array.from(document.querySelectorAll('.wide-table-scroll'));
        let saved = null;

        try {
            saved = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
        } catch (error) {
            saved = null;
        }

        if (saved && typeof saved.main === 'number') {
            main.scrollTop = saved.main;
        }

        if (saved && Array.isArray(saved.tables)) {
            tables.forEach(function (table, index) {
                if (typeof saved.tables[index] === 'number') {
                    table.scrollTop = saved.tables[index];
                }
            });
        }

        let frame = 0;
        const persist = function () {
            if (frame) {
                return;
            }

            frame = requestAnimationFrame(function () {
                frame = 0;
                sessionStorage.setItem(storageKey, JSON.stringify({
                    main: main.scrollTop,
                    tables: tables.map(function (table) {
                        return table.scrollTop;
                    }),
                }));
            });
        };

        main.addEventListener('scroll', persist, { passive: true });
        tables.forEach(function (table) {
            table.addEventListener('scroll', persist, { passive: true });
        });
    })();
</script>
