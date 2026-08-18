<?php
/* =========================================================
   SIDEBAR ADMIN (dipakai bersama oleh semua halaman admin)
   Cukup ubah array $menu di bawah untuk menambah / memindah menu.
   Menu aktif dideteksi otomatis dari nama file yang sedang dibuka.
   ========================================================= */
$__hal = basename($_SERVER['PHP_SELF']);

$__menu = [
    ['tipe' => 'item', 'file' => 'dashboard.php', 'ikon' => 'bi-grid-fill', 'label' => 'Dashboard'],

    ['tipe' => 'grup', 'ikon' => 'bi-hdd-stack', 'label' => 'Master Data', 'anak' => [
        ['file' => 'guru.php',        'ikon' => 'bi-person-badge', 'label' => 'Data Guru'],
        ['file' => 'siswa.php',       'ikon' => 'bi-people',       'label' => 'Data Siswa'],
        ['file' => 'jurusan.php',     'ikon' => 'bi-book-half',    'label' => 'Data Jurusan'],
        ['file' => 'konsentrasi.php', 'ikon' => 'bi-diagram-3',    'label' => 'Data Konsentrasi'],
        ['file' => 'kelas.php',       'ikon' => 'bi-house-door',   'label' => 'Data Kelas'],
        ['file' => 'mapel.php',       'ikon' => 'bi-book',         'label' => 'Data Mapel'],
        ['file' => 'ruang.php',       'ikon' => 'bi-door-open',    'label' => 'Ruang Ujian'],
    ]],

    ['tipe' => 'grup', 'ikon' => 'bi-mortarboard', 'label' => 'Ujian & Nilai', 'anak' => [
        ['file' => 'bank_soal.php',    'ikon' => 'bi-file-earmark-text', 'label' => 'Bank Soal'],
        ['file' => 'sesi_ujian.php',   'ikon' => 'bi-clipboard-check',   'label' => 'Sesi Ujian'],
        ['file' => 'monitoring.php',   'ikon' => 'bi-broadcast',         'label' => 'Monitoring Ujian'],
        ['file' => 'laporan_full.php', 'ikon' => 'bi-bar-chart-line',    'label' => 'Rekap Nilai'],
    ]],

    ['tipe' => 'grup', 'ikon' => 'bi-fingerprint', 'label' => 'Absensi RFID', 'anak' => [
        ['file' => 'kartu_rfid.php', 'ikon' => 'bi-cpu',          'label' => 'Kartu RFID'],
        ['file' => 'log_rfid.php',   'ikon' => 'bi-journal-text', 'label' => 'Log Absensi'],
    ]],

    ['tipe' => 'item', 'file' => 'backup.php', 'ikon' => 'bi-database-down', 'label' => 'Backup & Reset'],
];
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <h5 class="fw-bold mb-0">SMK PUTRA ANDA BINJAI</h5>
        <small class="opacity-50">Admin</small>
    </div>
    <ul class="nav flex-column p-2 mt-3">
        <?php foreach ($__menu as $m): ?>
            <?php if ($m['tipe'] === 'item'): ?>
                <li class="mb-2">
                    <a href="<?= $m['file'] ?>" class="nav-link<?= $__hal === $m['file'] ? ' active' : '' ?>">
                        <i class="bi <?= $m['ikon'] ?>"></i> <?= $m['label'] ?>
                    </a>
                </li>
            <?php else: ?>
                <?php
                // Grup otomatis terbuka kalau halaman yang dibuka ada di dalamnya
                $__aktif = false;
                foreach ($m['anak'] as $a) { if ($__hal === $a['file']) { $__aktif = true; break; } }
                ?>
                <li class="mb-2 nav-group<?= $__aktif ? ' open' : '' ?>">
                    <a href="#" class="nav-link nav-group-toggle<?= $__aktif ? ' has-active' : '' ?>">
                        <i class="bi <?= $m['ikon'] ?>"></i> <?= $m['label'] ?>
                        <i class="bi bi-chevron-down chev"></i>
                    </a>
                    <ul class="nav-group-menu">
                        <?php foreach ($m['anak'] as $a): ?>
                            <li>
                                <a href="<?= $a['file'] ?>" class="nav-link<?= $__hal === $a['file'] ? ' active' : '' ?>">
                                    <i class="bi <?= $a['ikon'] ?>"></i> <?= $a['label'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <hr class="mx-3 opacity-25 text-white">
        <li><a href="../logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
    </ul>
</nav>
