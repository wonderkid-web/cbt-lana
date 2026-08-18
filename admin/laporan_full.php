<?php 
require_once '../config/check_admin.php'; 
require_once '../config/database.php';

// --- AMBIL DATA UNTUK FILTER ---
 $query_kelas_list = mysqli_query($conn, "SELECT k.*, j.nama_program, j.kode AS kode_jurusan
                                         FROM kelas k
                                         LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                                         ORDER BY k.nama_kelas ASC");
 $query_mapel_list = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");

 $query_sesi_list = mysqli_query($conn, "SELECT su.id_sesi, su.nama_ujian, m.nama_mapel, su.jenis_ujian,
                                               (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ')
                                                FROM sesi_ujian_kelas suk JOIN kelas k ON suk.id_kelas = k.id_kelas
                                                WHERE suk.id_sesi = su.id_sesi) as nama_kelas
                                        FROM sesi_ujian su
                                        JOIN mapel m ON su.id_mapel = m.id_mapel
                                        ORDER BY su.tgl_mulai DESC");

// --- FILTER GET ---
 $filter_kelas = isset($_GET['filter_kelas']) ? (int)$_GET['filter_kelas'] : 0;
 $filter_mapel = isset($_GET['filter_mapel']) ? (int)$_GET['filter_mapel'] : 0;
 $filter_sesi = isset($_GET['filter_sesi']) ? (int)$_GET['filter_sesi'] : 0;
 $filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// --- BUILD QUERY ---
 $where_conditions = ["u.status = 'selesai'"];

if ($filter_kelas > 0) {
    $where_conditions[] = "s.id_kelas = '$filter_kelas'";
}
if ($filter_mapel > 0) {
    $where_conditions[] = "su.id_mapel = '$filter_mapel'";
}
if ($filter_sesi > 0) {
    $where_conditions[] = "u.id_sesi = '$filter_sesi'";
}
if (!empty($filter_search)) {
    $where_conditions[] = "(s.nama_siswa LIKE '%$filter_search%' OR s.nisn LIKE '%$filter_search%')";
}

 $where_sql = implode(" AND ", $where_conditions);

// --- PAGINATION ---
 $batas = 10;
 $halaman = isset($_GET['pagi']) ? (int)$_GET['pagi'] : 1;
 $halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;
 $sebelum = $halaman - 1;
 $sesudah = $halaman + 1;

 $base_query = "FROM ujian u
               JOIN siswa s ON u.id_user = s.id_user
               JOIN kelas k ON s.id_kelas = k.id_kelas
               JOIN sesi_ujian su ON u.id_sesi = su.id_sesi
               JOIN mapel m ON su.id_mapel = m.id_mapel
               LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
               WHERE $where_sql";

 $count_query = mysqli_query($conn, "SELECT COUNT(*) as total $base_query");
 $jumlah_data = mysqli_fetch_assoc($count_query)['total'];
 $total_halaman = ceil($jumlah_data / $batas);

 $query_rekap = mysqli_query($conn, "SELECT u.id_ujian, su.nama_ujian, u.nilai, u.waktu_mulai, u.waktu_selesai, su.durasi,
                                           s.nama_siswa, s.nisn, s.id_kelas,
                                           k.nama_kelas, k.tingkat,
                                           m.nama_mapel,
                                           su.nama_ujian as nama_sesi, su.jenis_ujian, su.tgl_mulai as jadwal_mulai,
                                           j.nama_program, j.kode AS kode_jurusan
                                    $base_query
                                    ORDER BY k.nama_kelas ASC, s.nama_siswa ASC, u.nilai DESC
                                    LIMIT $halaman_awal, $batas");

 $nomor = $halaman_awal + 1;

// --- STATISTIK GLOBAL ---
 $q_global = mysqli_query($conn, "SELECT 
    COUNT(*) as total_peserta,
    ROUND(AVG(u.nilai), 1) as rata_rata,
    MAX(u.nilai) as nilai_tertinggi,
    MIN(u.nilai) as nilai_terendah,
    SUM(CASE WHEN u.nilai >= 70 THEN 1 ELSE 0 END) as lulus,
    SUM(CASE WHEN u.nilai < 70 THEN 1 ELSE 0 END) as tidak_lulus
    $base_query");
 $global = mysqli_fetch_assoc($q_global);

function getGradeLaporan($nilai) {
    if ($nilai >= 90) return ['grade' => 'A', 'color' => '#16a34a', 'bg' => '#dcfce7'];
    elseif ($nilai >= 80) return ['grade' => 'B', 'color' => '#2563eb', 'bg' => '#dbeafe'];
    elseif ($nilai >= 70) return ['grade' => 'C', 'color' => '#d97706', 'bg' => '#fef3c7'];
    elseif ($nilai >= 60) return ['grade' => 'D', 'color' => '#ea580c', 'bg' => '#ffedd5'];
    else return ['grade' => 'E', 'color' => '#dc2626', 'bg' => '#fee2e2'];
}

function generatePagination($halaman, $total_halaman, $link_search) {
    $pages = []; $maxVisible = 5;
    if ($total_halaman <= $maxVisible) { for ($i = 1; $i <= $total_halaman; $i++) $pages[] = $i; }
    else { $pages[] = 1; $rangeStart = max(2, $halaman - 1); $rangeEnd = min($total_halaman - 1, $halaman + 1);
        if ($halaman <= 3) { $rangeStart = 2; $rangeEnd = min($maxVisible - 1, $total_halaman - 1); }
        elseif ($halaman >= $total_halaman - 2) { $rangeStart = max(2, $total_halaman - $maxVisible + 2); $rangeEnd = $total_halaman - 1; }
        if ($rangeStart > 2) $pages[] = '...';
        for ($i = $rangeStart; $i <= $rangeEnd; $i++) $pages[] = $i;
        if ($rangeEnd < $total_halaman - 1) $pages[] = '...'; $pages[] = $total_halaman; }
    foreach ($pages as $p) { if ($p === '...') { echo '<li class="page-item disabled"><span class="page-link px-1">...</span></li>'; }
        else { $active = ($halaman == $p) ? 'active' : ''; echo '<li class="page-item ' . $active . '"><a class="page-link" href="?pagi=' . $p . $link_search . '">' . $p . '</a></li>'; } }
}

// =====================================================
// EXPORT EXCEL (pakai ZipArchive)
// =====================================================
if (isset($_GET['export'])) {

    $export_query = mysqli_query($conn, "SELECT u.nilai, u.waktu_mulai, u.waktu_selesai,
                                               s.nama_siswa, s.nisn,
                                               k.nama_kelas, k.tingkat,
                                               m.nama_mapel,
                                               su.nama_ujian, su.jenis_ujian,
                                               j.nama_program, j.kode AS kode_jurusan
                                        $base_query
                                        ORDER BY k.nama_kelas ASC, s.nama_siswa ASC, u.nilai DESC");

    $q_stat = mysqli_query($conn, "SELECT 
        COUNT(*) as total_peserta,
        ROUND(AVG(u.nilai), 1) as rata_rata,
        MAX(u.nilai) as nilai_tertinggi,
        MIN(u.nilai) as nilai_terendah,
        SUM(CASE WHEN u.nilai >= 70 THEN 1 ELSE 0 END) as lulus,
        SUM(CASE WHEN u.nilai < 70 THEN 1 ELSE 0 END) as tidak_lulus
        $base_query");
    $stat = mysqli_fetch_assoc($q_stat);

    // Nama file
    $file_label = 'rekap_nilai';
    $kelas_nama = '';
    if ($filter_kelas > 0) {
        $qk = mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$filter_kelas'");
        $kl = mysqli_fetch_assoc($qk);
        if ($kl) { $kelas_nama = $kl['nama_kelas']; $file_label .= '_' . str_replace([' ', '/'], '_', $kelas_nama); }
    }
    if ($filter_mapel > 0) {
        $qm = mysqli_query($conn, "SELECT nama_mapel FROM mapel WHERE id_mapel = '$filter_mapel'");
        $ml = mysqli_fetch_assoc($qm);
        if ($ml) $file_label .= '_' . str_replace(' ', '_', $ml['nama_mapel']);
    }
    $filename = $file_label . '_' . date('Y-m-d') . '.xlsx';

    // --- Bangun data baris untuk sheet ---
    // Format: setiap baris = array of cells, style per cell
    // Kita simpan sebagai array: [['v' => value, 's' => style], ...]
    
    $sheetData = []; // setiap elemen = 1 baris array of ['v'=>val, 's'=>style]
    $numCols = 9;
    $colWidths = [6, 25, 15, 12, 20, 20, 14, 10, 10];

    // Style index (sesuai styles.xml di bawah)
    $ST = [
        'default'  => 0,
        'header'   => 1, // biru, bold putih, border
        'data'     => 2, // border
        'title'    => 3, // bold 14
        'subtitle' => 4, // bold 11
        'info'     => 5, // biasa
        'statH'    => 6, // stat header abu bold
        'statD'    => 7, // stat data border
        'lulus'    => 8, // hijau bold border
        'remedial' => 9, // merah bold border
    ];

    // Baris 1-3: Judul
    $sheetData[] = [['v' => 'REKAP NILAI UJIAN', 's' => $ST['title']]];
    $sheetData[] = [['v' => 'SMK PUTRA ANDA BINJAI', 's' => $ST['subtitle']]];
    $sheetData[] = [['v' => 'Diekspor: ' . date('d F Y, H:i') . ' WIB', 's' => $ST['info']]];
    $sheetData[] = []; // kosong

    // Info filter
    $r = 5;
    if (!empty($kelas_nama)) {
        $sheetData[] = [['v' => 'Kelas:', 's' => $ST['subtitle']], ['v' => $kelas_nama, 's' => $ST['info']]];
        $r++;
    }
    if ($filter_mapel > 0) {
        $qm2 = mysqli_query($conn, "SELECT nama_mapel FROM mapel WHERE id_mapel = '$filter_mapel'");
        $ml2 = mysqli_fetch_assoc($qm2);
        if ($ml2) { $sheetData[] = [['v' => 'Mata Pelajaran:', 's' => $ST['subtitle']], ['v' => $ml2['nama_mapel'], 's' => $ST['info']]]; $r++; }
    }
    if ($filter_sesi > 0) {
        $qs2 = mysqli_query($conn, "SELECT nama_ujian FROM sesi_ujian WHERE id_sesi = '$filter_sesi'");
        $sl2 = mysqli_fetch_assoc($qs2);
        if ($sl2) { $sheetData[] = [['v' => 'Sesi Ujian:', 's' => $ST['subtitle']], ['v' => $sl2['nama_ujian'], 's' => $ST['info']]]; $r++; }
    }
    $sheetData[] = []; // kosong

    // Statistik
    $sheetData[] = [['v' => 'STATISTIK', 's' => $ST['subtitle']]];
    $sheetData[] = [
        ['v' => 'Total Peserta', 's' => $ST['statH']],
        ['v' => 'Rata-rata', 's' => $ST['statH']],
        ['v' => 'Tertinggi', 's' => $ST['statH']],
        ['v' => 'Terendah', 's' => $ST['statH']],
        ['v' => 'Lulus', 's' => $ST['statH']],
        ['v' => 'Tidak Lulus', 's' => $ST['statH']],
    ];
    $sheetData[] = [
        ['v' => (int)$stat['total_peserta'], 's' => $ST['statD']],
        ['v' => $stat['rata_rata'] ?: 0, 's' => $ST['statD']],
        ['v' => (int)($stat['nilai_tertinggi'] ?: 0), 's' => $ST['statD']],
        ['v' => (int)($stat['nilai_terendah'] ?: 0), 's' => $ST['statD']],
        ['v' => (int)$stat['lulus'], 's' => $ST['lulus']],
        ['v' => (int)$stat['tidak_lulus'], 's' => $ST['remedial']],
    ];
    $sheetData[] = []; // kosong

    // Header tabel
    $hLabels = ['NO', 'NAMA SISWA', 'NISN', 'KELAS', 'MAPEL', 'UJIAN', 'TANGGAL', 'NILAI', 'GRADE'];
    $hRow = [];
    foreach ($hLabels as $h) { $hRow[] = ['v' => $h, 's' => $ST['header']]; }
    $sheetData[] = $hRow;

    // Data baris
    $no = 1;
    while ($row = mysqli_fetch_assoc($export_query)) {
        $tanggal = !empty($row['waktu_selesai']) ? date('d-m-Y', strtotime($row['waktu_selesai'])) : '-';
        $g = getGradeLaporan($row['nilai']);

        $sheetData[] = [
            ['v' => $no++, 's' => $ST['data']],
            ['v' => $row['nama_siswa'], 's' => $ST['data']],
            ['v' => $row['nisn'] ?: '-', 's' => $ST['data']],
            ['v' => $row['nama_kelas'], 's' => $ST['data']],
            ['v' => $row['nama_mapel'], 's' => $ST['data']],
            ['v' => $row['nama_ujian'], 's' => $ST['data']],
            ['v' => $tanggal, 's' => $ST['data']],
            ['v' => (int)$row['nilai'], 's' => $ST['data']],
            ['v' => $g['grade'], 's' => $ST['data']],
        ];
    }

    $sheetData[] = [];
    $sheetData[] = [['v' => 'Total: ' . ($no - 1) . ' data', 's' => $ST['subtitle']]];

    // --- Generate XLSX ---
    $tmpdir = sys_get_temp_dir() . '/xlsx_' . uniqid();
    mkdir($tmpdir, 0755, true);

    // [Content_Types].xml
    $ct  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
    $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
    $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
    $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
    $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $ct .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    $ct .= '</Types>';
    file_put_contents($tmpdir . '/[Content_Types].xml', $ct);

    // _rels/.rels
    mkdir($tmpdir . '/_rels', 0755, true);
    $rels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
    $rels .= '</Relationships>';
    file_put_contents($tmpdir . '/_rels/.rels', $rels);

    // xl/workbook.xml
    mkdir($tmpdir . '/xl', 0755, true);
    $wb  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $wb .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $wb .= '<sheets><sheet name="Rekap Nilai" sheetId="1" r:id="rId2"/></sheets></workbook>';
    file_put_contents($tmpdir . '/xl/workbook.xml', $wb);

    // xl/_rels/workbook.xml.rels
    mkdir($tmpdir . '/xl/_rels', 0755, true);
    $wbrels  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $wbrels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $wbrels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $wbrels .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
    $wbrels .= '</Relationships>';
    file_put_contents($tmpdir . '/xl/_rels/workbook.xml.rels', $wbrels);

    // xl/styles.xml
    $styles  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $styles .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $styles .= '<numFmts count="0"/>';
    $styles .= '<fonts count="5">';
    $styles .= '<font><sz val="11"/><name val="Calibri"/></font>';                                          // 0
    $styles .= '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>';                  // 1
    $styles .= '<font><b/><sz val="14"/><name val="Calibri"/></font>';                                        // 2
    $styles .= '<font><b/><sz val="11"/><name val="Calibri"/></font>';                                        // 3
    $styles .= '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>';                  // 4
    $styles .= '</fonts>';
    $styles .= '<fills count="6">';
    $styles .= '<fill><patternFill patternType="none"/></fill>';                                               // 0
    $styles .= '<fill><patternFill patternType="gray125"/></fill>';                                            // 1
    $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/></patternFill></fill>';         // 2
    $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>';         // 3
    $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/></patternFill></fill>';         // 4
    $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>';         // 5
    $styles .= '</fills>';
    $styles .= '<borders count="2">';
    $styles .= '<border><left/><right/><top/><bottom/><diagonal/></border>';                                  // 0
    $styles .= '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>'; // 1
    $styles .= '</borders>';
    $styles .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
    $styles .= '<cellXfs count="10">';
    $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';                              // 0: default
    $styles .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>';  // 1: header biru
    $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>';              // 2: data border
    $styles .= '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>';                // 3: judul besar
    $styles .= '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>';                // 4: sub-judul bold
    $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';                              // 5: info text
    $styles .= '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>';  // 6: stat header
    $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>';              // 7: stat data
    $styles .= '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>';  // 8: lulus hijau
    $styles .= '<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>';  // 9: remedial merah
    $styles .= '</cellXfs>';
    $styles .= '</styleSheet>';
    file_put_contents($tmpdir . '/xl/styles.xml', $styles);

    // xl/worksheets/sheet1.xml
    mkdir($tmpdir . '/xl/worksheets', 0755, true);

    $colLetter_func = function($col) {
        $letter = '';
        while ($col >= 0) {
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = (int)($col / 26) - 1;
        }
        return $letter;
    };

    $xmlEsc = function($str) {
        if ($str === null) return '';
        return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };

    $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

    // Kolom width
    $xml .= '<cols>';
    for ($c = 0; $c < $numCols; $c++) {
        $xml .= '<col min="' . ($c+1) . '" max="' . ($c+1) . '" width="' . $colWidths[$c] . '" customWidth="1"/>';
    }
    $xml .= '</cols>';

    $xml .= '<sheetData>';

    $rowNum = 1;
    foreach ($sheetData as $row) {
        $xml .= '<row r="' . $rowNum . '">';
        for ($c = 0; $c < count($row); $c++) {
            $cell = $row[$c];
            $val = $cell['v'];
            $style = $cell['s'];
            $colL = $colLetter_func($c);

            if ($val !== '' && $val !== null && is_numeric($val) && strlen((string)$val) < 16) {
                $xml .= '<c r="' . $colL . $rowNum . '" s="' . $style . '"><v>' . $val . '</v></c>';
            } else {
                $xml .= '<c r="' . $colL . $rowNum . '" s="' . $style . '" t="inlineStr"><is><t>' . $xmlEsc($val) . '</t></is></c>';
            }
        }
        $xml .= '</row>';
        $rowNum++;
    }

    $xml .= '</sheetData></worksheet>';
    file_put_contents($tmpdir . '/xl/worksheets/sheet1.xml', $xml);

    // ZIP jadi XLSX
    $xlsx_path = sys_get_temp_dir() . '/' . $filename;
    $zip = new ZipArchive();
    $zip->open($xlsx_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmpdir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tmpdir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();

    // Hapus tmp
    function rmdirTmp($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $obj) {
                if ($obj !== '.' && $obj !== '..') {
                    $path = $dir . '/' . $obj;
                    is_dir($path) ? rmdirTmp($path) : unlink($path);
                }
            }
            rmdir($dir);
        }
    }
    rmdirTmp($tmpdir);

    // Download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($xlsx_path));
    header('Cache-Control: max-age=0');
    readfile($xlsx_path);
    unlink($xlsx_path);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Nilai | SMK Putra Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
:root { --sidebar-bg: #0f172a; --accent: #3b82f6; --sidebar-w: 260px; }
body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }
#sidebar { width: var(--sidebar-w); min-width: var(--sidebar-w); max-width: var(--sidebar-w); background: var(--sidebar-bg); color: #fff; height: 100vh; position: fixed; top: 0; left: 0; overflow-y: auto; overflow-x: hidden; transition: margin-left 0.3s ease; z-index: 1050; }
#sidebar::-webkit-scrollbar { width: 4px; }
#sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
#sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
.sidebar-header { padding: 25px; background: rgba(0,0,0,0.2); text-align: center; }
.nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; display: flex; align-items: center; transition: 0.3s; }
.nav-link:hover, .nav-link.active { color: #fff; background: var(--accent); border-radius: 10px; margin: 0 10px; }
.nav-link i { font-size: 1.2rem; margin-right: 15px; }
.content-area { margin-left: var(--sidebar-w); width: calc(100% - var(--sidebar-w)); transition: margin-left 0.3s ease, width 0.3s ease; }
.content-area.expanded { margin-left: 0; width: 100%; }
.top-nav { background: #fff; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
#sidebarOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1040; }
#sidebarOverlay.active { display: block; }
body.mobile-sidebar-open { overflow: hidden !important; }
@media (max-width: 768px) {
    #sidebar { margin-left: calc(var(--sidebar-w) * -1); }
    #sidebar.active { margin-left: 0; }
    .content-area { margin-left: 0 !important; width: 100% !important; }
    #sidebar.collapsed { margin-left: calc(var(--sidebar-w) * -1); }
}
.card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
.stat-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 18px; text-align: center; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.05); }
.stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 1.2rem; }
.stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1; }
.stat-label { font-size: 0.75rem; color: #64748b; margin-top: 4px; }
.grade-badge { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; color: #fff; }
.filter-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; }
    </style>
    <!-- Sidebar: grouping menu + mode mini -->
    <link rel="stylesheet" href="../assets/css/sidebar.css?v=4">
    <script src="../assets/js/sidebar.js?v=4"></script>
</head>
<body>

<div id="sidebarOverlay"></div>
<div class="d-flex">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <div class="content-area" id="contentArea">
        <nav class="top-nav d-flex justify-content-between align-items-center">
            <button class="btn btn-light" id="toggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div class="dropdown">
                <a class="text-decoration-none text-dark dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                    <div class="text-end me-2 d-none d-md-block">
                        <small class="text-muted d-block" style="font-size: 10px;">Login Sebagai</small>
                        <span class="fw-bold" style="font-size: 14px;">Administrator</span>
                    </div>
                    <i class="bi bi-person-circle fs-3 text-primary"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                    <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Rekap Nilai Ujian</h4>
                    <p class="text-muted small mb-0">Lihat dan analisis hasil ujian seluruh siswa.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['export' => 1])); ?>" 
                       class="btn btn-success btn-sm rounded-pill px-3 shadow-sm <?= ($jumlah_data == 0) ? 'disabled' : ''; ?>">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                    </a>
                </div>
            </div>

            <!-- STATISTIK -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-value text-dark"><?= $global['total_peserta'] ?: 0; ?></div>
                        <div class="stat-label">Total Peserta</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="stat-value" style="color:#2563eb;"><?= $global['rata_rata'] ?: 0; ?></div>
                        <div class="stat-label">Rata-rata</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-trophy-fill"></i></div>
                        <div class="stat-value" style="color:#16a34a;"><?= $global['nilai_tertinggi'] ?: 0; ?></div>
                        <div class="stat-label">Tertinggi</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-arrow-down-circle-fill"></i></div>
                        <div class="stat-value" style="color:#dc2626;"><?= $global['nilai_terendah'] ?: 0; ?></div>
                        <div class="stat-label">Terendah</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-value" style="color:#16a34a;"><?= $global['lulus'] ?: 0; ?></div>
                        <div class="stat-label">Lulus (>=70)</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="stat-value" style="color:#dc2626;"><?= $global['tidak_lulus'] ?: 0; ?></div>
                        <div class="stat-label">Tidak Lulus</div>
                    </div>
                </div>
            </div>

            <!-- FILTER -->
            <div class="filter-card p-3 mb-4">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 d-block">KELAS</label>
                        <select name="filter_kelas" class="form-select form-select-sm">
                            <option value="0">Semua Kelas</option>
                            <?php while($k = mysqli_fetch_assoc($query_kelas_list)): ?>
                                <option value="<?= $k['id_kelas']; ?>" <?= ($filter_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($k['nama_kelas']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 d-block">MATA PELAJARAN</label>
                        <select name="filter_mapel" class="form-select form-select-sm">
                            <option value="0">Semua Mapel</option>
                            <?php while($m = mysqli_fetch_assoc($query_mapel_list)): ?>
                                <option value="<?= $m['id_mapel']; ?>" <?= ($filter_mapel == $m['id_mapel']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($m['nama_mapel']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1 d-block">SESI UJIAN</label>
                        <select name="filter_sesi" class="form-select form-select-sm">
                            <option value="0">Semua Sesi</option>
                            <?php while($su = mysqli_fetch_assoc($query_sesi_list)): ?>
                                <option value="<?= $su['id_sesi']; ?>" <?= ($filter_sesi == $su['id_sesi']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($su['nama_ujian']); ?> - <?= htmlspecialchars($su['nama_mapel']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Cari nama/NISN..." value="<?= htmlspecialchars($filter_search); ?>">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                <?php if ($filter_kelas > 0 || $filter_mapel > 0 || $filter_sesi > 0 || !empty($filter_search)): ?>
                <div class="mt-2">
                    <a href="laporan_full.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Reset Filter
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- TABEL REKAP -->
            <div class="card card-custom overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2"></i>Data Nilai</h6>
                        <span class="badge bg-light text-muted border px-3 py-2" style="font-size:0.78rem;">
                            <?= $jumlah_data; ?> data
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" width="4%">No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Mapel</th>
                                <th>Ujian</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jumlah_data > 0): 
                                $n = $nomor;
                                while ($r = mysqli_fetch_assoc($query_rekap)):
                                    $g = getGradeLaporan($r['nilai']);
                                    $lulus = ($r['nilai'] >= 70);
                            ?>
                            <tr>
                                <td class="text-center px-3 fw-bold text-muted"><?= $n++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.9rem;"><?= htmlspecialchars($r['nama_siswa']); ?></div>
                                    <small class="text-muted">NISN: <?= htmlspecialchars($r['nisn'] ?: '-'); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['nama_kelas']); ?></span></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info"><?= htmlspecialchars($r['nama_mapel']); ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.85rem;"><?= htmlspecialchars($r['nama_sesi']); ?></div>
                                    <small class="text-muted"><?= strtoupper($r['jenis_ujian']); ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="small fw-bold"><?= date('d M Y', strtotime($r['waktu_selesai'])); ?></div>
                                    <div class="small text-muted"><?= date('H:i', strtotime($r['waktu_mulai'])); ?> WIB</div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold" style="font-size:1.05rem; color:<?= $g['color']; ?>;">
                                        <?= number_format($r['nilai'], 0); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="grade-badge" style="background:<?= $g['color']; ?>;"><?= $g['grade']; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($lulus): ?>
                                        <span class="badge" style="background:#dcfce7;color:#166534;font-weight:600;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Lulus
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#fee2e2;color:#991b1b;font-weight:600;">
                                            <i class="bi bi-x-circle-fill me-1"></i>Remedial
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Tidak ada data nilai yang cocok dengan filter.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                $link_search_params = array_intersect_key($_GET, array_flip(['filter_kelas','filter_mapel','filter_sesi','search']));
                $link_search = !empty($link_search_params) ? '&' . http_build_query($link_search_params) : '';
                ?>
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <small class="text-muted">Menampilkan <b><?= mysqli_num_rows($query_rekap); ?></b> dari <b><?= $jumlah_data; ?></b> data</small>
                        <?php if($total_halaman > 0): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($halaman <= 1) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman-1).$link_search; ?>">Prev</a></li>
                                <?php generatePagination($halaman, $total_halaman, $link_search); ?>
                                <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : ''; ?>"><a class="page-link px-3" href="?pagi=<?= ($halaman+1).$link_search; ?>">Next</a></li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const content = document.getElementById('contentArea');
    const toggle = document.getElementById('toggleBtn');
    const body = document.body;
    function isMobile() { return window.innerWidth <= 768; }
    function desktopToggle() { const isOpen = !sidebar.classList.contains('collapsed'); if (isOpen) { sidebar.classList.add('collapsed'); content.classList.add('expanded'); } else { sidebar.classList.remove('collapsed'); content.classList.remove('expanded'); } }
    function mobileOpen() { sidebar.classList.add('active'); overlay.classList.add('active'); body.classList.add('mobile-sidebar-open'); }
    function mobileClose() { sidebar.classList.remove('active'); overlay.classList.remove('active'); body.classList.remove('mobile-sidebar-open'); }
    toggle.addEventListener('click', function () { if (isMobile()) { sidebar.classList.contains('active') ? mobileClose() : mobileOpen(); } else { desktopToggle(); } });
    overlay.addEventListener('click', function () { mobileClose(); });
    document.querySelectorAll('#sidebar .nav-link').forEach(function (link) { link.addEventListener('click', function () { if (isMobile()) mobileClose(); }); });
    window.addEventListener('resize', function () { if (!isMobile()) mobileClose(); });
</script>
</body>
</html>
