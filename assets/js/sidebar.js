/* =========================================================
   SIDEBAR
   - Grouping menu (buka/tutup grup, model akordeon)
   - Mode mini: ingat kondisi antar halaman + tooltip nama menu
   Di-load di <head>. Logika tombol toggle bawaan tiap halaman
   tidak diubah.
   ========================================================= */
(function () {
    var KEY  = 'cbtSidebarMini';
    var root = document.documentElement;

    // Terapkan sebelum halaman tergambar supaya tidak berkedip
    try {
        if (localStorage.getItem(KEY) === '1') root.classList.add('sidebar-mini');
    } catch (e) {}

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else { fn(); }
    }

    ready(function () {
        var sidebar = document.getElementById('sidebar');
        var content = document.getElementById('contentArea') ||
                      document.querySelector('.content-area');

        if (!sidebar) { root.classList.remove('sidebar-mini'); return; }

        // Pindahkan penanda sementara ke class yang dipakai tombol toggle
        if (root.classList.contains('sidebar-mini')) {
            sidebar.classList.add('collapsed');
            if (content) content.classList.add('expanded');
        }
        root.classList.remove('sidebar-mini');

        /* ---------- Grouping menu (akordeon) ---------- */
        var groups = Array.prototype.slice.call(sidebar.querySelectorAll('.nav-group'));
        groups.forEach(function (grup) {
            var judul = grup.querySelector('.nav-group-toggle');
            if (!judul) return;
            judul.addEventListener('click', function (e) {
                e.preventDefault();
                var sudahTerbuka = grup.classList.contains('open');
                // Hanya satu grup terbuka dalam satu waktu
                groups.forEach(function (g) { if (g !== grup) g.classList.remove('open'); });
                grup.classList.toggle('open', !sudahTerbuka);
            });
        });

        /* ---------- Mode mini: tooltip + simpan kondisi ---------- */
        // Tooltip hanya untuk menu yang tampil sebagai ikon di mode mini.
        // Anak menu di dalam flyout sudah ada teksnya, jadi tidak perlu.
        var links  = Array.prototype.slice.call(
                        sidebar.querySelectorAll('.nav-link:not(.nav-group-toggle)'))
                     .filter(function (l) { return !l.closest('.nav-group-menu'); });
        var labels = links.map(function (l) { return l.textContent.trim(); });

        function sync() {
            var mini = sidebar.classList.contains('collapsed') && window.innerWidth > 768;
            links.forEach(function (l, i) {
                if (mini) { l.setAttribute('title', labels[i]); }
                else      { l.removeAttribute('title'); }
            });
            try { localStorage.setItem(KEY, mini ? '1' : '0'); } catch (e) {}
        }

        new MutationObserver(sync).observe(sidebar, {
            attributes: true, attributeFilter: ['class']
        });
        window.addEventListener('resize', sync);
        sync();
    });
})();
