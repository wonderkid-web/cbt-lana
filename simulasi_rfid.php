<?php
session_start();
require_once 'config/database.php';

if (isset($_POST['tap_uid'])) {
    $uid = strtoupper(trim($_POST['tap_uid']));
    $api_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/rfid_handler.php';
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['uid' => $uid]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response_api = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response_api, true);
    $api_success = $result['success'] ?? false;
    $api_response = $result['response'] ?? 'ERROR';
}

// PAKAI $conn DAN nama_siswa, nisn SESUAI TABEL KAMU
  $kartu_data = mysqli_query($conn, "SELECT kr.uid_rfid, u.status, s.nama_siswa, s.nisn FROM kartu_rfid kr JOIN siswa s ON kr.siswa_id = s.id_siswa JOIN users u ON s.id_user = u.id_user ORDER BY kr.id");
 $log_hari_ini = mysqli_query($conn, "SELECT lr.*, s.nama_siswa, s.nisn FROM log_rfid lr JOIN siswa s ON lr.siswa_id = s.id_siswa WHERE DATE(lr.waktu) = CURDATE() ORDER BY lr.waktu DESC LIMIT 20");

if (isset($_GET['reset'])) {
    mysqli_query($conn, "DELETE FROM absensi_hari_ini WHERE tanggal = CURDATE()");
    mysqli_query($conn, "DELETE FROM log_rfid WHERE DATE(waktu) = CURDATE()");
    header("Location: simulasi_rfid.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simulasi RFID</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .header { background: #1a1a2e; color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1100px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card h3 { font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .full { grid-column: 1 / -1; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 14px; }
        .full ol { margin-left: 20px; margin-top: 5px; }
        .full li { margin-bottom: 5px; }
        .kartu-item { display: flex; align-items: center; justify-content: space-between; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; margin-bottom: 10px; }
        .kartu-item.aktif { border-left: 4px solid #22c55e; }
        .kartu-item.nonaktif { border-left: 4px solid #ef4444; opacity: 0.6; }
        .kartu-info .nama { font-weight: 600; font-size: 14px; }
        .kartu-info .detail { font-size: 12px; color: #888; }
        .kartu-info .uid { font-family: monospace; font-size: 11px; color: #667eea; background: #f0f4ff; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; }
        .btn-tap { padding: 8px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-tap.aktif { background: #667eea; color: white; }
        .btn-tap.aktif:hover { background: #5a6fd6; }
        .btn-tap.nonaktif { background: #e0e0e0; color: #999; cursor: not-allowed; }
        .resp { margin-top: 15px; padding: 15px; border-radius: 10px; display: none; }
        .resp.show { display: block; }
        .resp.ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .resp.err { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .custom { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 2px dashed #ddd; }
        .custom input { flex: 1; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-family: monospace; }
        .custom input:focus { outline: none; border-color: #667eea; }
        .custom button { padding: 10px 20px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .log-item { display: flex; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .log-time { width: 80px; color: #888; font-family: monospace; }
        .log-name { flex: 1; }
        .log-status { color: #22c55e; font-weight: 600; }
        .empty { text-align: center; padding: 30px; color: #aaa; }
        .btn-reset { background: none; border: 2px solid #ef4444; color: #ef4444; padding: 6px 15px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-reset:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📟 Simulasi RFID</h1>
        <a href="?reset=1" class="btn-reset" onclick="return confirm('Reset absensi hari ini?')">🔄 Reset Hari Ini</a>
    </div>

    <div class="grid">
        <div class="card full">
            <strong>📋 Cara Pakai:</strong>
            <ol>
                <li>Buka Tab Baru → buka <code>login.php</code> → Login sebagai Siswa</li>
                <li>Tab 1 akan mengarah ke halaman "Waiting RFID" (Menunggu tap kartu)</li>
                <li>Kembali ke Tab Ini → Klik tombol [TAP] pada kartu siswa tersebut</li>
                <li>Lihat Tab 1 otomatis berubah menjadi "Terverifikasi ✅"</li>
            </ol>
        </div>

        <div class="card">
            <h3>💳 Daftar Kartu</h3>
            <?php if ($kartu_data->num_rows > 0): ?>
                <?php while ($k = $kartu_data->fetch_assoc()): ?>
                    <div class="kartu-item <?= $k['status'] ?>">
                        <div class="kartu-info">
                            <div class="nama"><?= htmlspecialchars($k['nama_siswa']) ?></div>
                            <div class="detail"><?= htmlspecialchars($k['nisn']) ?></div>
                            <div class="uid">UID: <?= htmlspecialchars($k['uid_rfid']) ?></div>
                        </div>
                        <?php if ($k['status'] == 'aktif'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="tap_uid" value="<?= $k['uid_rfid'] ?>">
                                <button type="submit" class="btn-tap aktif">📡 TAP</button>
                            </form>
                        <?php else: ?>
                            <button class="btn-tap nonaktif" disabled>TAP</button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty">Tidak ada kartu terdaftar</div>
            <?php endif; ?>

            <div class="custom">
                <input type="text" id="manualUid" placeholder="UID manual (contoh: XX-XX-XX-XX)" value="FF-FF-FF-FF">
                <button onclick="tapManual()">📡 TAP Manual</button>
            </div>

            <?php if (isset($api_response)): ?>
                <div class="resp show <?= $api_success ? 'ok' : 'err' ?>">
                    <strong>Response:</strong><br>
                    <?php 
                    if (strpos($api_response, 'SUCCESS:') === 0) {
                        echo "✅ Berhasil! Kartu milik: " . htmlspecialchars(substr($api_response, 8));
                    } elseif ($api_response == 'INVALID') { echo "❌ Kartu tidak aktif"; }
                    elseif ($api_response == 'NOTFOUND') { echo "❌ Kartu tidak terdaftar"; }
                    elseif ($api_response == 'ALREADY') { echo "⚠️ Sudah absen hari ini"; }
                    else { echo "❌ Error"; }
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📝 Log Hari Ini</h3>
            <?php if ($log_hari_ini->num_rows > 0): ?>
                <?php while ($l = $log_hari_ini->fetch_assoc()): ?>
                    <div class="log-item">
                        <div class="log-time"><?= date('H:i:s', strtotime($l['waktu'])) ?></div>
                        <div class="log-name"><strong><?= htmlspecialchars($l['nama_siswa']) ?></strong><br><small style="color:#aaa"><?= htmlspecialchars($l['nisn']) ?></small></div>
                        <div class="log-status">✅ Absen</div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty">Belum ada yang absen hari ini</div>
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" id="formManual" style="display:none">
        <input type="hidden" name="tap_uid" id="manualUidInput">
    </form>
    <script>
        function tapManual() {
            const uid = document.getElementById('manualUid').value.trim();
            if (!uid) { alert('Masukkan UID'); return; }
            if (!confirm('Tap kartu UID: ' + uid + '?')) return;
            document.getElementById('manualUidInput').value = uid.toUpperCase();
            document.getElementById('formManual').submit();
        }
    </script>
</body>
</html>