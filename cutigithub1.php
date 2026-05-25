<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// Header anti cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$host = 'sql206.infinityfree.com';
$db   = 'if0_41772736_puskesmas_db';
$user = 'if0_41772736';
$pass = 'Cok123let';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

define('GOOGLE_DRIVE_CUTI_ROOT_FOLDER_ID', '138RUP2UrrPG3saBQ1CioleWOT-fvMmty');

// ==================== FUNGSI BANTU ====================
function base_url($path = '') {
    return 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $path;
}
function redirect($url) {
    header("Location: $url");
    exit;
}
function is_admin_cuti() {
    return isset($_SESSION['admin_cuti_id']);
}
function is_pegawai_login() {
    return isset($_SESSION['pegawai_nip']);
}
function bersihkanTeks($teks) {
    return str_replace(["\r\n", "\r", "\n"], "\n", $teks);
}
function formatTanggalIndo($date) {
    if (empty($date)) return '';
    $bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    $parts = explode('-', $date);
    if (count($parts)==3) return $parts[2].' '.$bulan[$parts[1]].' '.$parts[0];
    return $date;
}

// ==================== PERBAIKAN HITUNG HARI KERJA ====================
function hitungHariKerja($start, $end, $conn) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $endDate->modify('+1 day');
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($startDate, $interval, $endDate);
    
    $libur = [];
    $res = $conn->query("SELECT tanggal FROM hari_libur");
    while ($row = $res->fetch_assoc()) {
        $libur[] = $row['tanggal'];
    }
    
    $kerja = 0;
    foreach ($period as $day) {
        $tgl = $day->format('Y-m-d');
        $dayOfWeek = $day->format('N'); // 1 Senin - 7 Minggu
        // Hanya skip hari Minggu (libur mingguan)
        if ($dayOfWeek == 7) continue;
        if (in_array($tgl, $libur)) continue;
        $kerja++;
    }
    return $kerja;
}

// ==================== AJAX DETAIL REKAP ====================
if (isset($_GET['ajax_detail']) && isset($_GET['nip']) && is_admin_cuti()) {
    $nip = $conn->real_escape_string($_GET['nip']);
    $peg = $conn->query("SELECT nama, sisa_n1, sisa_n FROM pegawai WHERE nip='$nip'")->fetch_assoc();
    if (!$peg) {
        echo "<div class='alert alert-danger'>Data pegawai tidak ditemukan.</div>";
        exit;
    }
    $total_sisa = $peg['sisa_n1'] + $peg['sisa_n'];
    $sisa_n1_display = ($peg['sisa_n1'] > 0) ? $peg['sisa_n1'] . ' Hari' : 'NIHIL';
    $sisa_n_display = $peg['sisa_n'] . ' Hari';
    
    echo '<div class="mb-4 p-3 bg-light rounded-3 border">
            <div class="row text-center">
                <div class="col-md-4"><strong>Sisa N-1 (Cuti Lalu):</strong> ' . $sisa_n1_display . '</div>
                <div class="col-md-4"><strong>Sisa N (Cuti Tahun Ini):</strong> ' . $sisa_n_display . '</div>
                <div class="col-md-4"><strong>Total Sisa Kuota:</strong> ' . $total_sisa . ' Hari</div>
            </div>
          </div>';
    
    $detail = $conn->query("SELECT * FROM cuti WHERE nip='$nip' AND status='disetujui' ORDER BY tanggal_mulai DESC");
    if($detail->num_rows == 0) {
        echo '<div class="alert alert-info">Tidak ada riwayat cuti yang disetujui untuk pegawai ini.</div>';
    } else {
        echo '<div class="table-responsive"><table class="table table-bordered table-sm">';
        echo '<thead class="table-light"><tr><th>No</th><th>Jenis Cuti</th><th>Rentang Tanggal</th><th>Jumlah Hari</th><th>File Scan</th></tr></thead><tbody>';
        $no = 1;
        while($d = $detail->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . ucfirst(str_replace('_', ' ', $d['jenis_cuti'])) . '</td>';
            echo '<td>' . formatTanggalIndo($d['tanggal_mulai']) . ' s/d ' . formatTanggalIndo($d['tanggal_selesai']) . '</td>';
            echo '<td>' . $d['jumlah_hari'] . ' hari' . '</td>';
            echo '<td>';
            if($d['file_scan']) echo '<a href="' . $d['file_scan'] . '" target="_blank" class="btn btn-xs btn-primary">Lihat</a>';
            else echo '<span class="text-muted">-</span>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></td></div>';
    }
    exit;
}



// ==================== PROSES LOGIN ====================
if (isset($_POST['login_pegawai'])) {
    $nip = trim($_POST['nip']);
    $peg = $conn->query("SELECT * FROM pegawai WHERE nip='$nip'")->fetch_assoc();
    if ($peg) {
        $_SESSION['pegawai_nip'] = $peg['nip'];
        $_SESSION['pegawai_nama'] = $peg['nama'];
        redirect('cuti.php');
    } else {
        $error_login = "NIP tidak ditemukan.";
    }
}

if (isset($_POST['login_admin'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($_POST['password']);
    $user = $conn->query("SELECT id FROM users WHERE username='$username' AND password='$password'")->fetch_assoc();
    if ($user) {
        $_SESSION['admin_cuti_id'] = $user['id'];
        redirect('cuti.php?admin');
    } else {
        $error_login = "Username atau password salah.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('cuti.php');
}

// ==================== PROSES AJUKAN CUTI ====================
if (isset($_POST['ajukan_cuti']) && is_pegawai_login()) {
    $nip = $_SESSION['pegawai_nip'];
    $jenis_cuti = $_POST['jenis_cuti'];
    $alasan = bersihkanTeks($_POST['alasan']);
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $alamat = bersihkanTeks($_POST['alamat']);
    $telepon = trim($_POST['telepon']);
    $error_msg = null;

    if ($tanggal_selesai < $tanggal_mulai) {
        $error_msg = "Tanggal selesai harus setelah atau sama dengan tanggal mulai.";
    } else {
        $jumlah_hari = hitungHariKerja($tanggal_mulai, $tanggal_selesai, $conn);
        if ($jumlah_hari == 0) {
            $error_msg = "Tidak ada hari kerja dalam rentang tanggal tersebut (libur atau weekend).";
        } else {
            $peg = $conn->query("SELECT sisa_n1, sisa_n FROM pegawai WHERE nip='$nip'")->fetch_assoc();
            $total_sisa = $peg['sisa_n1'] + $peg['sisa_n'];
            if ($total_sisa < $jumlah_hari) {
                $error_msg = "Sisa cuti tidak mencukupi. Dibutuhkan $jumlah_hari hari, sisa $total_sisa hari.";
            } else {
                $cekBentrok = $conn->query("SELECT id FROM cuti WHERE nip='$nip' AND status='disetujui' AND (
                    (tanggal_mulai BETWEEN '$tanggal_mulai' AND '$tanggal_selesai') OR
                    (tanggal_selesai BETWEEN '$tanggal_mulai' AND '$tanggal_selesai') OR
                    ('$tanggal_mulai' BETWEEN tanggal_mulai AND tanggal_selesai)
                )");
                if ($cekBentrok->num_rows > 0) {
                    $error_msg = "Tanggal bentrok dengan cuti yang sudah disetujui.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO cuti (nip, jenis_cuti, alasan, tanggal_mulai, tanggal_selesai, jumlah_hari, alamat_selama_cuti, telepon_selama, status) VALUES (?,?,?,?,?,?,?,?,'menunggu_verifikasi')");
                    $stmt->bind_param("sssssiss", $nip, $jenis_cuti, $alasan, $tanggal_mulai, $tanggal_selesai, $jumlah_hari, $alamat, $telepon);
                    if ($stmt->execute()) {
                        $cuti_id = $stmt->insert_id;
                        echo "<script>alert('Pengajuan berhasil! Silakan cetak formulir.'); window.location.href='cetak_cuti.php?id=$cuti_id';</script>";
                        exit;
                    } else {
                        $error_msg = "Gagal menyimpan: " . $conn->error;
                    }
                }
            }
        }
    }
    if ($error_msg) {
        $error_msg_pegawai = $error_msg;
    }
}

// ==================== PROSES VERIFIKASI ADMIN ====================
$verifikasi_success = false;
$verifikasi_error = null;
$detail_cuti_sukses = null;
if (isset($_POST['verifikasi_cuti']) && is_admin_cuti()) {
    $cuti_id = (int)$_POST['cuti_id'];
    $status = $_POST['status'];
    $catatan = bersihkanTeks($_POST['catatan_penolakan'] ?? '');
    $file_url = null;
    $error_msg = null;

    $cuti = $conn->query("SELECT c.*, p.nama, p.sisa_n1, p.sisa_n FROM cuti c JOIN pegawai p ON c.nip = p.nip WHERE c.id=$cuti_id")->fetch_assoc();
    if (!$cuti) {
        $error_msg = "Data cuti tidak ditemukan.";
    } else {
        if ($status == 'disetujui') {
            if (isset($_FILES['file_scan']) && $_FILES['file_scan']['error'] == 0 && $_FILES['file_scan']['size'] > 0 && $_FILES['file_scan']['size'] <= 2*1024*1024) {
                $ext = strtolower(pathinfo($_FILES['file_scan']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf','jpg','jpeg','png'])) {
                    $tanggal_mulai = $cuti['tanggal_mulai'];
                    $nama_file = $cuti['nip'] . '_' . str_replace(' ', '_', $cuti['nama']) . '_' . $tanggal_mulai . '.' . $ext;
                    $tahun = date('Y', strtotime($tanggal_mulai));
                    $tmpFile = $_FILES['file_scan']['tmp_name'];
                    $mime = mime_content_type($tmpFile);
                    
                    $rootFolderId = GOOGLE_DRIVE_CUTI_ROOT_FOLDER_ID;
                    $tahunFolderId = createGoogleDriveFolderIfNotExists($rootFolderId, $tahun);
                    if ($tahunFolderId) {
                        $fileId = uploadFileToGoogleDrive($tmpFile, $nama_file, $mime, $tahunFolderId);
                        if ($fileId) {
                            $file_url = 'https://drive.google.com/file/d/' . $fileId . '/view';
                        } else {
                            $error_msg = "Upload ke Google Drive gagal.";
                        }
                    } else {
                        $error_msg = "Gagal membuat folder tahun di Google Drive.";
                    }
                } else {
                    $error_msg = "Format file tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG.";
                }
            } else {
                $error_msg = "File scan wajib diupload saat menyetujui cuti. Max 2MB.";
            }
        }

        if (!$error_msg && $status == 'disetujui' && $file_url) {
            // Potong prioritas: dari sisa_n1 dulu
            $sisa_n1 = $cuti['sisa_n1'];
            $jumlah = $cuti['jumlah_hari'];
            if ($sisa_n1 >= $jumlah) {
                $conn->query("UPDATE pegawai SET sisa_n1 = sisa_n1 - $jumlah WHERE nip='{$cuti['nip']}'");
            } else {
                $sisa_n = $cuti['sisa_n'];
                $sisa_n_baru = $sisa_n - ($jumlah - $sisa_n1);
                $conn->query("UPDATE pegawai SET sisa_n1 = 0, sisa_n = $sisa_n_baru WHERE nip='{$cuti['nip']}'");
            }
            $conn->query("UPDATE pegawai SET kuota_cuti = sisa_n1 + sisa_n WHERE nip='{$cuti['nip']}'");
            $conn->query("UPDATE cuti SET status='disetujui', file_scan='$file_url', tanggal_verifikasi=NOW() WHERE id=$cuti_id");
            $conn->query("INSERT INTO log_kuota (nip, perubahan, keterangan) VALUES ('{$cuti['nip']}', -{$jumlah}, 'Cuti disetujui id $cuti_id')");
            
            $detail_cuti_sukses = [
                'nama' => $cuti['nama'],
                'nip' => $cuti['nip'],
                'periode' => formatTanggalIndo($cuti['tanggal_mulai']).' s/d '.formatTanggalIndo($cuti['tanggal_selesai']),
                'durasi' => $cuti['jumlah_hari'],
                'file_url' => $file_url
            ];
            $verifikasi_success = true;
        } elseif (!$error_msg && $status == 'ditolak') {
            $conn->query("UPDATE cuti SET status='ditolak', catatan_penolakan='$catatan', tanggal_verifikasi=NOW() WHERE id=$cuti_id");
            $verifikasi_success = true;
        } else {
            $error_msg = "Terjadi kesalahan dalam proses verifikasi.";
        }
    }
    $verifikasi_error = $error_msg;
}

// ==================== ADMIN: ROLLOVER, EDIT PEGAWAI, DLL ====================
if (is_admin_cuti()) {
    if (isset($_POST['rollover_tahun'])) {
        $jatah_baru = (int)$_POST['jatah_baru'];
        $tahun = date('Y');
        $res = $conn->query("SELECT nip, sisa_n FROM pegawai");
        while ($row = $res->fetch_assoc()) {
            $sisa_n = $row['sisa_n'];
            $carry_over = min($sisa_n, 6);
            $sisa_n1_baru = $carry_over;
            $sisa_n_baru = $jatah_baru;
            $conn->query("UPDATE pegawai SET sisa_n1 = $sisa_n1_baru, sisa_n = $sisa_n_baru, kuota_cuti = sisa_n1 + sisa_n WHERE nip='{$row['nip']}'");
            $conn->query("INSERT INTO log_kuota (nip, perubahan, keterangan) VALUES ('{$row['nip']}', $sisa_n1_baru+$sisa_n_baru, 'Rollover $tahun: carry=$carry_over, jatah_baru=$jatah_baru')");
        }
        echo "<script>alert('Rollover selesai. Maksimal 6 hari sisa N dibawa ke tahun depan.'); window.location='cuti.php?admin#rollover';</script>";
        exit;
    }

    if (isset($_POST['tambah_libur'])) {
        $tanggal = $conn->real_escape_string($_POST['tanggal_libur']);
        $keterangan = $conn->real_escape_string($_POST['keterangan_libur']);
        $conn->query("INSERT INTO hari_libur (tanggal, keterangan) VALUES ('$tanggal', '$keterangan') ON DUPLICATE KEY UPDATE keterangan='$keterangan'");
        redirect('cuti.php?admin#libur');
    }
    if (isset($_GET['hapus_libur'])) {
        $id = (int)$_GET['hapus_libur'];
        $conn->query("DELETE FROM hari_libur WHERE id=$id");
        redirect('cuti.php?admin#libur');
    }

    if (isset($_POST['edit_pegawai'])) {
        $nip_lama = $_POST['nip_lama'];
        $nip_baru = $conn->real_escape_string($_POST['nip']);
        $nama = $conn->real_escape_string($_POST['nama']);
        $jabatan = $conn->real_escape_string($_POST['jabatan']);
        $unit = $conn->real_escape_string($_POST['unit_kerja']);
        $kuota = (int)$_POST['kuota_cuti'];
        $conn->query("UPDATE pegawai SET nip='$nip_baru', nama='$nama', jabatan='$jabatan', unit_kerja='$unit', kuota_cuti=$kuota, sisa_n1=0, sisa_n=$kuota WHERE nip='$nip_lama'");
        redirect('cuti.php?admin#pegawai');
    }
    if (isset($_POST['tambah_pegawai'])) {
        $nip = $conn->real_escape_string($_POST['nip']);
        $nama = $conn->real_escape_string($_POST['nama']);
        $jabatan = $conn->real_escape_string($_POST['jabatan']);
        $unit = $conn->real_escape_string($_POST['unit_kerja']);
        $kuota = (int)$_POST['kuota_cuti'];
        $conn->query("INSERT INTO pegawai (nip, nama, jabatan, unit_kerja, kuota_cuti, sisa_n1, sisa_n) VALUES ('$nip','$nama','$jabatan','$unit',$kuota,0,$kuota)");
        redirect('cuti.php?admin#pegawai');
    }
    if (isset($_GET['hapus_pegawai'])) {
        $nip = $conn->real_escape_string($_GET['hapus_pegawai']);
        $conn->query("DELETE FROM pegawai WHERE nip='$nip'");
        redirect('cuti.php?admin#pegawai');
    }
    if (isset($_GET['hapus_file'])) {
        $id = (int)$_GET['hapus_file'];
        $conn->query("UPDATE cuti SET file_scan = NULL WHERE id=$id");
        echo "<script>alert('File dihapus dari database.'); window.location='cuti.php?admin#riwayat';</script>";
        exit;
    }
    if (isset($_POST['ganti_file'])) {
        $id = (int)$_POST['cuti_id'];
        $cuti = $conn->query("SELECT c.nip, p.nama FROM cuti c JOIN pegawai p ON c.nip = p.nip WHERE c.id=$id AND c.status='disetujui'")->fetch_assoc();
        if ($cuti && $_FILES['file_scan_baru']['error']==0 && $_FILES['file_scan_baru']['size'] <= 2*1024*1024) {
            $ext = strtolower(pathinfo($_FILES['file_scan_baru']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf','jpg','jpeg','png'])) {
                $nama_file = $cuti['nip'] . '_' . str_replace(' ', '_', $cuti['nama']) . '_' . time() . '.' . $ext;
                $tahun = date('Y');
                $tmp = $_FILES['file_scan_baru']['tmp_name'];
                $mime = mime_content_type($tmp);
                $tahunFolderId = createGoogleDriveFolderIfNotExists(GOOGLE_DRIVE_CUTI_ROOT_FOLDER_ID, $tahun);
                if ($tahunFolderId) {
                    $fileId = uploadFileToGoogleDrive($tmp, $nama_file, $mime, $tahunFolderId);
                    if ($fileId) {
                        $url = 'https://drive.google.com/file/d/' . $fileId . '/view';
                        $conn->query("UPDATE cuti SET file_scan='$url' WHERE id=$id");
                        echo "<script>alert('File berhasil diganti.'); window.location='cuti.php?admin#riwayat';</script>";
                        exit;
                    }
                }
            }
        }
        echo "<script>alert('Gagal mengganti file.'); window.history.back();</script>";
        exit;
    }
    if (isset($_GET['batalkan_persetujuan'])) {
        $id = (int)$_GET['batalkan_persetujuan'];
        $cuti = $conn->query("SELECT nip, jumlah_hari FROM cuti WHERE id=$id AND status='disetujui'")->fetch_assoc();
        if ($cuti) {
            $conn->query("UPDATE pegawai SET sisa_n = sisa_n + {$cuti['jumlah_hari']}, kuota_cuti = sisa_n1 + sisa_n WHERE nip='{$cuti['nip']}'");
            $conn->query("UPDATE cuti SET status='ditolak', catatan_penolakan='Dibatalkan admin', tanggal_verifikasi=NOW() WHERE id=$id");
            echo "<script>alert('Persetujuan dibatalkan. Kuota dikembalikan ke sisa_n.'); window.location='cuti.php?admin#riwayat';</script>";
        } else {
            echo "<script>alert('Data tidak ditemukan.'); window.location='cuti.php?admin#riwayat';</script>";
        }
        exit;
    }

    // Export Excel (.xls)
    if ((isset($_GET['export_rekap']) || isset($_POST['export_rekap'])) && is_admin_cuti()) {
        $bulan = isset($_REQUEST['bulan']) ? (int)$_REQUEST['bulan'] : 0;
        $tahun = isset($_REQUEST['tahun']) ? (int)$_REQUEST['tahun'] : 0;
        if (!$bulan || !$tahun) {
            $bulan = (int)date('m');
            $tahun = (int)date('Y');
        }
        $bulan_padded = str_pad($bulan, 2, "0", STR_PAD_LEFT);
        $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $start_date = "$tahun-$bulan_padded-01";
        $end_date = "$tahun-$bulan_padded-$jumlah_hari";
        $nama_bulan = date('F', mktime(0,0,0,$bulan,1));
        
        $pegawai_data = $conn->query("SELECT nip, nama, jabatan FROM pegawai ORDER BY nama");
        $cuti_list = $conn->query("SELECT nip, tanggal_mulai, tanggal_selesai FROM cuti WHERE status='disetujui' AND tanggal_mulai <= '$end_date' AND tanggal_selesai >= '$start_date'");
        
        $cuti_map = [];
        while ($c = $cuti_list->fetch_assoc()) {
            $nip = $c['nip'];
            $current = max(strtotime($c['tanggal_mulai']), strtotime($start_date));
            $end = min(strtotime($c['tanggal_selesai']), strtotime($end_date));
            while ($current <= $end) {
                $tgl = (int)date('j', $current);
                $cuti_map[$nip][$tgl] = true;
                $current = strtotime("+1 day", $current);
            }
        }
        
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=rekap_cuti_{$bulan_padded}_{$tahun}.xls");
        header("Cache-Control: private, max-age=0, must-revalidate");
        header("Pragma: public");
        
        echo '<table border="1">';
        echo '<tr><td colspan="' . (4 + $jumlah_hari + 2) . '" style="font-size:14pt;font-weight:bold;text-align:center;">REKAP CUTI BULAN ' . strtoupper($nama_bulan) . ' ' . $tahun . '</td></tr>';
        echo '<tr><th>No</th><th>NIP</th><th>Nama</th><th>Jabatan</th>';
        for ($d=1;$d<=$jumlah_hari;$d++) echo '<th>'.$d.'</th>';
        echo '<th>Total Hari</th><th>Keterangan</th></tr>';
        
        $no = 1;
        while ($p = $pegawai_data->fetch_assoc()) {
            $nip = $p['nip'];
            $nama = $p['nama'];
            $jabatan = $p['jabatan'];
            $total = 0;
            echo '<tr>';
            echo '<td style="text-align:center">'.$no++.'</td>';
            echo '<td>' . htmlspecialchars($nip) . '</td>';
            echo '<td>' . htmlspecialchars($nama) . '</td>';
            echo '<td>' . htmlspecialchars($jabatan) . '</td>';
            for ($d=1;$d<=$jumlah_hari;$d++) {
                $cuti = isset($cuti_map[$nip][$d]) ? 'C' : '';
                if ($cuti == 'C') $total++;
                echo '<td style="text-align:center; background-color: '.($cuti == 'C' ? '#fee2e2' : 'transparent').';">'.$cuti.'</td>';
            }
            echo '<td style="text-align:center; font-weight:bold;">'.$total.'</td>';
            echo '<td></td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }
}

// ==================== AJAX hitung hari & libur ====================
if (isset($_GET['ajax_hitung_hari']) && isset($_GET['mulai']) && isset($_GET['selesai'])) {
    $mulai = $_GET['mulai'];
    $selesai = $_GET['selesai'];
    echo hitungHariKerja($mulai, $selesai, $conn);
    exit;
}
if (isset($_GET['ajax_libur'])) {
    header('Content-Type: application/json');
    $libur = [];
    $res = $conn->query("SELECT tanggal FROM hari_libur");
    while ($row = $res->fetch_assoc()) {
        $libur[] = $row['tanggal'];
    }
    echo json_encode($libur);
    exit;
}

// ==================== HALAMAN LOGIN ====================
if (!is_pegawai_login() && !is_admin_cuti()) {
    // (HTML login sama seperti kode asli, tidak diubah)
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Sistem Cuti Puskesmas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            body { background: #f0f4f8; font-family: 'Segoe UI', system-ui; }
            .login-card { max-width: 450px; margin: 5rem auto; background: white; border-radius: 20px; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1); }
            .nav-tabs .nav-link { font-weight: 600; border: none; color: #64748b; margin-bottom: -1px; }
            .nav-tabs .nav-link.active { color: #2563eb; border-bottom: 2px solid #2563eb; background: transparent; }
            .btn-primary { background: #2563eb; border: none; padding: 10px; border-radius: 12px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="login-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="bi bi-calendar2-check-fill fs-1 text-primary"></i>
                    <h4 class="fw-bold mt-2">Sistem Cuti Digital</h4>
                    <p class="text-muted small">Silakan masuk untuk melanjutkan</p>
                </div>
                <ul class="nav nav-tabs justify-content-center mb-4" id="loginTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pegawaiTab">Pegawai</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#adminTab">Admin</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane show active" id="pegawaiTab">
                        <form method="POST">
                            <div class="mb-3"><label class="form-label">NIP / NIPPPK</label><input type="text" name="nip" class="form-control" required></div>
                            <button type="submit" name="login_pegawai" class="btn btn-primary w-100">Masuk <i class="bi bi-arrow-right ms-1"></i></button>
                        </form>
                    </div>
                    <div class="tab-pane" id="adminTab">
                        <form method="POST">
                            <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                            <button type="submit" name="login_admin" class="btn btn-primary w-100">Login Admin</button>
                        </form>
                    </div>
                </div>
                <?php if(isset($error_login)) echo "<div class='alert alert-danger mt-3'>$error_login</div>"; ?>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

if (is_admin_cuti() && !isset($_GET['admin'])) {
    redirect('cuti.php?admin');
}

// ==================== PANEL ADMIN ====================
if (is_admin_cuti() && isset($_GET['admin'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
        <title>Admin Cuti - Panel Manajemen Modern</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-main: #f8fafc;
                --text-primary: #0f172a;
                --text-secondary: #64748b;
                --border-color: #e2e8f0;
                --primary-color: #3b82f6;
                --primary-light: #eff6ff;
                --success-color: #10b981;
                --success-light: #ecfdf5;
                --danger-color: #ef4444;
                --danger-light: #fef2f2;
                --warning-color: #f59e0b;
                --warning-light: #fffbeb;
            }
            body { 
                background-color: var(--bg-main); 
                color: var(--text-primary); 
                font-family: 'Inter', system-ui, -apple-system, sans-serif; 
                -webkit-font-smoothing: antialiased; 
            }
            h2 { font-weight: 700; color: var(--text-primary); }
            
            /* Modern Card Dashboard */
            .card { 
                background: #ffffff; 
                border-radius: 16px; 
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
                border: 1px solid var(--border-color); 
                margin-bottom: 2rem; 
                overflow: hidden; 
            }
            .card-header { 
                background-color: #ffffff; 
                border-bottom: 1px solid var(--border-color); 
                font-weight: 600; 
                padding: 1.25rem 1.5rem; 
            }
            .card-body { padding: 1.5rem; }
            
            /* Premium Modern Nav Tabs */
            .nav-tabs-wrapper {
                background: #ffffff;
                padding: 6px;
                border-radius: 12px;
                border: 1px solid var(--border-color);
                margin-bottom: 2rem;
            }
            .nav-tabs { 
                border-bottom: none; 
                gap: 4px; 
                flex-wrap: nowrap;
            }
            .nav-tabs .nav-item { margin-bottom: 0; }
            .nav-tabs .nav-link { 
                border: none; 
                color: var(--text-secondary); 
                font-weight: 500; 
                padding: 10px 20px; 
                border-radius: 8px; 
                transition: all 0.2s ease; 
                white-space: nowrap;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.9rem;
            }
            .nav-tabs .nav-link:hover { 
                color: var(--text-primary); 
                background-color: var(--bg-main); 
            }
            .nav-tabs .nav-link.active { 
                color: var(--primary-color); 
                background-color: var(--primary-light); 
                font-weight: 600; 
            }
            
            /* Ultra Clean & Modern Table Design */
            .table-responsive { 
                border-radius: 12px; 
                border: 1px solid var(--border-color); 
                background: #ffffff;
            }
            .table { 
                color: var(--text-primary); 
                vertical-align: middle; 
                margin-bottom: 0; 
            }
            .table th { 
                background-color: #f8fafc; 
                color: #475569; 
                font-weight: 600; 
                text-transform: uppercase; 
                font-size: 0.75rem; 
                letter-spacing: 0.06em; 
                padding: 14px 20px; 
                border-bottom: 1px solid var(--border-color); 
            }
            .table td { 
                padding: 16px 20px; 
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.92rem;
            }
            .table-hover tbody tr { transition: background-color 0.15s ease; }
            .table-hover tbody tr:hover { background-color: #f8fafc; }
            .table-hover tbody tr:last-child td { border-bottom: none; }
            
            /* Typography Component inside Tables */
            .emp-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; }
            .emp-sub { color: var(--text-secondary); font-size: 0.8rem; margin-top: 1px; }
            .date-box { display: flex; align-items: center; gap: 8px; color: #334155; }
            .date-sub { font-size: 0.8rem; color: var(--text-secondary); margin-left: 24px; }
            
            /* Status Badges & Pills (Soft Contrast) */
            .badge-jenis { 
                background-color: var(--primary-light); 
                color: #1e40af; 
                font-weight: 500; 
                border-radius: 6px; 
                padding: 6px 12px; 
                font-size: 0.8rem; 
                display: inline-flex;
                align-items: center;
                border: 1px solid #bfdbfe; 
            }
            .badge-durasi { 
                background-color: var(--success-light); 
                color: #166534; 
                font-weight: 600; 
                border-radius: 6px; 
                padding: 6px 12px; 
                font-size: 0.8rem; 
                display: inline-flex;
                align-items: center;
                border: 1px solid #bbf7d0; 
            }
            .badge-kuota { 
                background-color: #f1f5f9; 
                color: #334155; 
                font-weight: 600; 
                border-radius: 6px; 
                padding: 6px 12px; 
                font-size: 0.8rem; 
                display: inline-flex;
                align-items: center;
                gap: 4px;
                border: 1px solid #e2e8f0;
            }
            
            /* Buttons Customization */
            .btn { font-weight: 500; border-radius: 10px; padding: 8px 18px; font-size: 0.9rem; transition: all 0.15s ease; }
            .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; }
            .btn-primary { background-color: var(--primary-color); border: none; }
            .btn-primary:hover { background-color: #2563eb; }
            .btn-success { background-color: var(--success-color); border: none; }
            .btn-success:hover { background-color: #059669; }
            .btn-danger { background-color: var(--danger-color); border: none; }
            .btn-danger:hover { background-color: #dc2626; }
            .btn-action-view { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
            .btn-action-view:hover { background-color: #e2e8f0; color: #1e293b; }
            
            /* Modern Accordion */
            .accordion-item { border: 1px solid var(--border-color); border-radius: 12px !important; overflow: hidden; margin-bottom: 10px; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
            .accordion-button { font-weight: 600; background-color: #ffffff; color: var(--text-primary); padding: 1.25rem 1.5rem; }
            .accordion-button:not(.collapsed) { background-color: #f8fafc; color: var(--primary-color); box-shadow: none; border-bottom: 1px solid var(--border-color); }
            .accordion-body { padding: 0; }
            
            /* Form Fields */
            .form-control, .form-select { border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; font-size: 0.95rem; color: var(--text-primary); }
            .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12); }
            
            /* Custom Success Banner Modal */
            .modal-success-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-bottom: none; border-top-left-radius: 20px !important; border-top-right-radius: 20px !important; }
            .success-icon-wrapper { position: relative; width: 80px; height: 80px; background-color: #ecfdf5; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.12); }
            .data-receipt-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-top: 15px; text-align: left; }
            .data-receipt-item { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.9rem; }
            .data-receipt-item:last-child { border-bottom: none; }
            
            @media (max-width: 992px) {
                .nav-tabs-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                .nav-tabs { width: max-content; }
            }
        </style>
    </head>
    <body>
        <div class="container mt-4 mt-md-5 mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-3 border-bottom">
                <div>
                    <h2 class="fs-4 fs-md-3 mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Admin Cuti Puskesmas</h2>
                    <p class="text-secondary small mb-0">Sistem Pengarsipan & Manajemen Informasi Cuti Terpusat</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"><i class="bi bi-house"></i> Beranda</a>
                    <a href="cuti.php?logout" class="btn btn-danger btn-sm d-flex align-items-center gap-1"><i class="bi bi-box-arrow-right"></i> Keluar</a>
                </div>
            </div>

            <div class="nav-tabs-wrapper">
                <ul class="nav nav-tabs" id="adminTab">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#verifikasi"><i class="bi bi-file-earmark-check"></i>Verifikasi Berkas</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pegawai"><i class="bi bi-people"></i>Database Pegawai</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#riwayat"><i class="bi bi-clock-history"></i>Riwayat Cuti</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rekap"><i class="bi bi-clipboard-data"></i>Rekap Bulanan</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#libur"><i class="bi bi-calendar-x"></i>Hari Libur</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rollover"><i class="bi bi-arrow-repeat"></i>Rollover Kuota</button></li>
                </ul>
            </div>

            <?php if(isset($verifikasi_success) && $verifikasi_success): ?>
            <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="modal-header modal-success-header text-white py-3">
                            <h5 class="modal-title mx-auto d-flex align-items-center gap-2 fw-bold"><i class="bi bi-cloud-check-fill"></i> SINKRONISASI SUKSES</h5>
                        </div>
                        <div class="modal-body text-center py-4 px-4">
                            <div class="success-icon-wrapper">
                                <i class="bi bi-check-lg" style="font-size: 2.8rem; -webkit-text-stroke: 1px;"></i>
                            </div>
                            <h4 class="mb-2 text-dark fw-bold">Verifikasi Berhasil!</h4>
                            <p class="text-muted small px-2">Dokumen berkas fisik telah berhasil diunggah ke Google Drive Cloud Storage dan kuota tahunan pegawai otomatis terpotong.</p>
                            
                            <div class="data-receipt-box shadow-sm">
                                <div class="data-receipt-item">
                                    <span class="text-secondary fw-medium">Nama Pegawai</span>
                                    <span class="text-dark fw-bold"><?= htmlspecialchars($detail_cuti_sukses['nama']) ?></span>
                                </div>
                                <div class="data-receipt-item">
                                    <span class="text-secondary fw-medium">NIP</span>
                                    <span class="text-dark fw-semibold"><?= htmlspecialchars($detail_cuti_sukses['nip']) ?></span>
                                </div>
                                <div class="data-receipt-item">
                                    <span class="text-secondary fw-medium">Periode Cuti</span>
                                    <span class="text-dark fw-semibold"><?= $detail_cuti_sukses['periode'] ?></span>
                                </div>
                                <div class="data-receipt-item">
                                    <span class="text-secondary fw-medium">Pemotongan Kuota</span>
                                    <span class="text-danger fw-bold">-<?= $detail_cuti_sukses['durasi'] ?> Hari Kerja</span>
                                </div>
                                <div class="data-receipt-item">
                                    <span class="text-secondary fw-medium">Arsip Cloud</span>
                                    <span><a href="<?= $detail_cuti_sukses['file_url'] ?>" target="_blank" class="text-primary text-decoration-none fw-bold"><i class="bi bi-link-45deg"></i> Buka Drive</a></span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center border-0 pb-4 pt-0">
                           <button type="button" class="btn btn-success px-5 shadow-sm py-2 fw-semibold" style="border-radius: 10px;" data-bs-dismiss="modal" onclick="window.location.href='cuti.php?admin#verifikasi';">Selesai & Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var successModal = new bootstrap.Modal(document.getElementById("successModal"));
                    successModal.show();
                });
            </script>
            <?php elseif(!empty($verifikasi_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2" style="border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($verifikasi_error) ?>
            </div>
            <?php endif; ?>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="verifikasi">
                    <div class="table-responsive shadow-sm">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Pegawai / Pemohon</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal Periode</th>
                                    <th>Durasi Kerja</th>
                                    <th class="text-end">Aksi Keputusan</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $verif = $conn->query("SELECT c.*, p.nama FROM cuti c JOIN pegawai p ON c.nip = p.nip WHERE c.status='menunggu_verifikasi' ORDER BY c.created_at ASC");
                            if($verif->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i> 
                                        <span>Belum ada permohonan baru yang memerlukan verifikasi berkas.</span>
                                    </td>
                                </tr>
                            <?php else:
                            while($v = $verif->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="emp-name"><?= htmlspecialchars($v['nama']) ?></div>
                                        <div class="emp-sub">NIP. <?= htmlspecialchars($v['nip']) ?></div>
                                    </td>
                                    <td><span class="badge-jenis"><?= ucfirst(str_replace('_', ' ', $v['jenis_cuti'])) ?></span></td>
                                    <td>
                                        <div class="date-box"><i class="bi bi-calendar3 text-primary small"></i> <?= formatTanggalIndo($v['tanggal_mulai']) ?></div>
                                        <div class="date-sub">s/d <?= formatTanggalIndo($v['tanggal_selesai']) ?></div>
                                    </td>
                                    <td><span class="badge-durasi"><?= (int)$v['jumlah_hari'] ?> Hari Kerja</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-success me-1 shadow-xs" data-bs-toggle="modal" data-bs-target="#verifModal" data-id="<?= $v['id'] ?>"><i class="bi bi-check2-circle me-1"></i>Setuju</button>
                                        <button class="btn btn-sm btn-outline-danger shadow-xs" data-bs-toggle="modal" data-bs-target="#tolakModal" data-id="<?= $v['id'] ?>"><i class="bi bi-x-circle me-1"></i>Tolak</button>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="pegawai">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <span class="text-secondary small fw-medium">Daftar staf dan aparatur aktif Puskesmas</span>
                        <button class="btn btn-primary btn-sm shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#tambahPegawaiModal"><i class="bi bi-person-plus"></i> Tambah Pegawai</button>
                    </div>
                    <div class="table-responsive shadow-sm">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Informasi Pegawai</th>
                                    <th>Jabatan Struktural</th>
                                    <th>Unit Kerja</th>
                                    <th>Sisa Hak Cuti</th>
                                    <th class="text-end">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $listPeg = $conn->query("SELECT *, (sisa_n1+sisa_n) as total_sisa FROM pegawai ORDER BY nama");
                            while($p = $listPeg->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="emp-name"><?= htmlspecialchars($p['nama']) ?></div>
                                        <div class="emp-sub">NIP. <?= htmlspecialchars($p['nip']) ?></div>
                                    </td>
                                    <td><div class="text-dark fw-medium" style="font-size:0.9rem;"><?= htmlspecialchars($p['jabatan']) ?></div></td>
                                    <td><div class="text-secondary small"><?= htmlspecialchars($p['unit_kerja']) ?></div></td>
                                    <td><span class="badge-kuota"><i class="bi bi-pie-chart text-success"></i> <?= (int)$p['total_sisa'] ?> Hari</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-action-view me-1" data-bs-toggle="modal" data-bs-target="#editPegawaiModal" data-nip="<?= htmlspecialchars($p['nip']) ?>" data-nama="<?= htmlspecialchars($p['nama']) ?>" data-jabatan="<?= htmlspecialchars($p['jabatan']) ?>" data-unit="<?= htmlspecialchars($p['unit_kerja']) ?>" data-kuota="<?= $p['total_sisa'] ?>"><i class="bi bi-pencil-square"></i></button>
                                        <a href="?hapus_pegawai=<?= urlencode($p['nip']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin menghapus data pegawai ini?')"><i class="bi bi-trash3"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="riwayat">
                    <div class="card border-0 shadow-none bg-transparent mb-3">
                        <form method="get" class="row g-2 align-items-center">
                            <input type="hidden" name="admin" value="">
                            <div class="col-12 col-md-8">
                                <input type="text" name="cari" class="form-control" placeholder="Cari nama atau NIP pegawai..." value="<?= htmlspecialchars($_GET['cari']??'') ?>">
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Cari</button>
                            </div>
                            <div class="col-6 col-md-2">
                                <a href="cuti.php?admin#riwayat" class="btn btn-light border w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                    
                    <?php
                    $keyword = $conn->real_escape_string($_GET['cari']??'');
                    $where = "c.status='disetujui'";
                    if(!empty($keyword)) $where .= " AND (p.nama LIKE '%$keyword%' OR p.nip LIKE '%$keyword%')";
                    $pegawaiQuery = $conn->query("SELECT DISTINCT p.nip, p.nama, p.jabatan, p.unit_kerja FROM cuti c JOIN pegawai p ON c.nip = p.nip WHERE $where ORDER BY p.nama");
                    if($pegawaiQuery->num_rows == 0): ?>
                        <div class="alert alert-light border text-center py-5 text-muted" style="border-radius:12px;">
                            <i class="bi bi-search fs-3 d-block mb-2 text-secondary"></i> Tidak ditemukan riwayat arsip cuti yang telah disetujui.
                        </div>
                    <?php else:
                        echo '<div class="accordion" id="accordionRiwayat">';
                        $no = 1;
                        while($peg = $pegawaiQuery->fetch_assoc()):
                            $nip = $peg['nip'];
                            $nama = $peg['nama'];
                            $collapseId = "collapseRiwayat$no";
                            $cutiList = $conn->query("SELECT * FROM cuti WHERE nip='$nip' AND status='disetujui' ORDER BY tanggal_mulai DESC");
                            $total_cuti = $cutiList->num_rows;
                            $total_hari = 0;
                            while($h = $cutiList->fetch_assoc()) $total_hari += $h['jumlah_hari'];
                            $cutiList->data_seek(0);
                    ?>
                        <div class="accordion-item shadow-xs">
                            <h2 class="accordion-header" id="heading<?= $no ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                                    <div class="d-flex justify-content-between w-100 me-3 flex-wrap gap-2 align-items-center">
                                        <div>
                                            <i class="bi bi-person-circle text-secondary me-2"></i>
                                            <strong class="text-dark"><?= htmlspecialchars($nama) ?></strong> 
                                            <span class="text-muted small ms-1">(NIP. <?= htmlspecialchars($nip) ?>)</span>
                                        </div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill fs-7 py-1.5 fw-semibold border border-primary border-opacity-10"><?= $total_cuti ?> Pengajuan (<?= $total_hari ?> Hari)</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="<?= $collapseId ?>" class="accordion-collapse collapse" data-bs-parent="#accordionRiwayat">
                                <div class="accordion-body">
                                    <div class="table-responsive" style="border: none; border-radius: 0;">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Jenis Cuti</th>
                                                    <th>Rentang Tanggal</th>
                                                    <th>Akumulasi</th>
                                                    <th>Dokumen Arsip</th>
                                                    <th class="text-end">Opsi Pembatalan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php while($c = $cutiList->fetch_assoc()): ?>
                                                <tr>
                                                    <td><span class="badge-jenis"><?= ucfirst(str_replace('_', ' ', $c['jenis_cuti'])) ?></span></td>
                                                    <td>
                                                        <div class="small fw-medium text-dark"><i class="bi bi-calendar-range text-muted me-1"></i> <?= formatTanggalIndo($c['tanggal_mulai']) ?> s/d <?= formatTanggalIndo($c['tanggal_selesai']) ?></div>
                                                    </td>
                                                    <td><span class="badge-durasi"><?= $c['jumlah_hari'] ?> Hari</span></td>
                                                    <td>
                                                        <?php if($c['file_scan']): ?>
                                                            <a href="<?= $c['file_scan'] ?>" target="_blank" class="btn btn-xs btn-outline-primary px-2.5 py-1" style="font-size:0.78rem; border-radius:6px;"><i class="bi bi-cloud-arrow-down-fill"></i> Buka Cloud</a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="?batalkan_persetujuan=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2.5" style="border-radius:6px;" onclick="return confirm('Batalkan persetujuan? Kuota cuti tahunan pegawai akan otomatis dikembalikan.')"><i class="bi bi-arrow-counterclockwise"></i> Batalkan</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php $no++; endwhile; echo '</div>'; endif; ?>
                </div>

                <div class="tab-pane fade" id="rekap">
                    <div class="card border-0 shadow-none bg-transparent mb-1">
                        <form method="get" class="row g-2 align-items-end">
                            <input type="hidden" name="admin" value="">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Bulan</label>
                                <select name="bulan" class="form-select"><?php for($m=1;$m<=12;$m++){ $selected = ($_GET['bulan']??date('m'))==$m ? 'selected' : ''; echo "<option value='$m' $selected>".date('F', mktime(0,0,0,$m,1))."</option>"; } ?></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Tahun</label>
                                <select name="tahun" class="form-select"><?php for($t=date('Y')-2;$t<=date('Y')+1;$t++){ $selected = ($_GET['tahun']??date('Y'))==$t ? 'selected' : ''; echo "<option value='$t' $selected>$t</option>"; } ?></select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="bi bi-filter me-1"></i>Filter</button>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="export_rekap" value="1" class="btn btn-success w-100 shadow-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive shadow-sm mt-3">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">No</th>
                                    <th>Identitas Pegawai</th>
                                    <th>Jabatan</th>
                                    <th>Frekuensi Cuti</th>
                                    <th>Total Hari Kerja</th>
                                    <th class="text-end">Rincian</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $where_rekap = "c.status='disetujui'";
                            if(!empty($_GET['bulan'])) $where_rekap .= " AND MONTH(c.tanggal_mulai) = ".(int)$_GET['bulan'];
                            if(!empty($_GET['tahun'])) $where_rekap .= " AND YEAR(c.tanggal_mulai) = ".(int)$_GET['tahun'];
                            $rekapQuery = $conn->query("SELECT p.nip, p.nama, p.jabatan, SUM(c.jumlah_hari) as total_hari, COUNT(c.id) as jumlah_cuti FROM cuti c JOIN pegawai p ON c.nip = p.nip WHERE $where_rekap GROUP BY p.nip ORDER BY p.nama");
                            if($rekapQuery->num_rows==0): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-folder-x fs-3 d-block mb-2"></i> Tidak ada riwayat aktivitas cuti pada periode bulan/tahun terpilih.</td></tr>
                            <?php else: $no=1; while($r=$rekapQuery->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-secondary fw-semibold ps-4"><?= $no++ ?></td>
                                    <td>
                                        <div class="emp-name"><?= htmlspecialchars($r['nama']) ?></div>
                                        <div class="emp-sub">NIP. <?= htmlspecialchars($r['nip']) ?></div>
                                    </td>
                                    <td><div class="text-secondary small fw-medium"><?= htmlspecialchars($r['jabatan']) ?></div></td>
                                    <td><span class="badge bg-light text-dark border px-2.5 py-1 rounded smallfw-semibold"><?= (int)$r['jumlah_cuti'] ?> Kali</span></td>
                                    <td><span class="badge-durasi"><?= (int)$r['total_hari'] ?> Hari</span></td>
                                    <td class="text-end"><button class="btn btn-sm btn-action-view" data-bs-toggle="modal" data-bs-target="#detailModal" data-nip="<?= htmlspecialchars($r['nip']) ?>" data-nama="<?= htmlspecialchars($r['nama']) ?>"><i class="bi bi-eye-fill"></i> Detail</button></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="libur">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-calendar-x text-danger me-1"></i> Daftar Hari Libur Nasional / Daerah</div>
                        <div class="card-body">
                            <form method="POST" class="row g-3 mb-4 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold text-muted">Tanggal</label>
                                    <input type="date" name="tanggal_libur" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Keterangan Hari Libur</label>
                                    <input type="text" name="keterangan_libur" class="form-control" placeholder="Contoh: Hari Raya Nyepi" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" name="tambah_libur" class="btn btn-primary w-100 shadow-sm"><i class="bi bi-plus-lg me-1"></i>Tambah Libur</button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal Kalender</th>
                                            <th>Keterangan Deskripsi</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $liburList = $conn->query("SELECT * FROM hari_libur ORDER BY tanggal DESC");
                                        if($liburList->num_rows == 0): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data hari libur nasional terdaftar.</td></tr>
                                        <?php else:
                                            while($l = $liburList->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-medium text-dark"><i class="bi bi-calendar-event text-danger me-2"></i><?= formatTanggalIndo($l['tanggal']) ?></td>
                                                <td class="text-secondary small"><?= htmlspecialchars($l['keterangan']) ?></td>
                                                <td class="text-end"><a href="?hapus_libur=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus tanggal libur ini?')"><i class="bi bi-trash"></i> Hapus</a></td>
                                            </tr>
                                        <?php endwhile; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="rollover">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><i class="bi bi-arrow-repeat text-warning me-1"></i> Sinkronisasi Siklus Kuota Tahunan</div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-semibold">Jatah Kuota Baru (Hari)</label>
                                            <input type="number" name="jatah_baru" class="form-control" value="19" required>
                                        </div>
                                        <button type="submit" name="rollover_tahun" class="btn btn-primary w-100 shadow-sm" onclick="return confirm('Proses rollover akan mereset kuota semua pegawai secara global. Lanjutkan?')">Proses Pembaruan Tahun</button>
                                    </form>
                                    <div class="bg-light p-3 rounded-3 mt-3 border">
                                        <p class="text-secondary small mb-0"><i class="bi bi-info-circle-fill text-primary"></i> Aturan BKN: Sisa cuti tahun berjalan (N) maksimal 6 hari dibawa ke tahun depan. Sisa N-1 hangus total.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="verifModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px;">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header border-0 pb-0"><h5>Persetujuan & Pengarsipan Berkas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body py-3">
                    <input type="hidden" name="cuti_id" id="verif_id">
                    <input type="hidden" name="status" value="disetujui">
                    <div class="mb-2"><label class="form-label fw-medium text-secondary small">Unggah Scan Surat Cuti Fisik (PDF/JPG/PNG, maks 2MB)</label><input type="file" name="file_scan" class="form-control" required></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="verifikasi_cuti" class="btn btn-primary w-100 py-2.5 shadow-sm">Validasi & Simpan ke Cloud</button></div>
            </form>
        </div></div></div>

        <div class="modal fade" id="tolakModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px;">
            <form method="post">
                <div class="modal-header border-0 pb-0"><h5>Penolakan Permohonan Cuti</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body py-3">
                    <input type="hidden" name="cuti_id" id="tolak_id">
                    <input type="hidden" name="status" value="ditolak">
                    <div class="mb-2"><label class="form-label fw-medium text-secondary small">Alasan Penolakan Resmi</label><textarea name="catatan_penolakan" class="form-control" rows="3" placeholder="Tulis alasan logis penolakan permohonan staf..." required></textarea></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="verifikasi_cuti" class="btn btn-danger w-100 py-2.5 shadow-sm">Tolak Secara Resmi</button></div>
            </form>
        </div></div></div>

        <div class="modal fade" id="detailModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:16px;">
            <div class="modal-header"><h5>Rincian Riwayat Pegawai</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="detailContent"></div>
        </div></div></div>

        <div class="modal fade" id="editPegawaiModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px;">
            <form method="post">
                <div class="modal-header"><h5>Edit Profil Pegawai</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="nip_lama" id="edit_nip_lama">
                    <div class="mb-2"><label class="form-label text-muted small">NIP</label><input type="text" name="nip" id="edit_nip" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label text-muted small">Nama Lengkap</label><input type="text" name="nama" id="edit_nama" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label text-muted small">Jabatan</label><input type="text" name="jabatan" id="edit_jabatan" class="form-control"></div>
                    <div class="mb-2"><label class="form-label text-muted small">Unit Kerja</label><input type="text" name="unit_kerja" id="edit_unit" class="form-control"></div>
                    <div class="mb-2"><label class="form-label text-muted small">Kuota Cuti (Total)</label><input type="number" name="kuota_cuti" id="edit_kuota" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="submit" name="edit_pegawai" class="btn btn-primary w-100">Simpan Perubahan</button></div>
            </form>
        </div></div></div>

        <div class="modal fade" id="tambahPegawaiModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px;">
            <form method="post">
                <div class="modal-header"><h5>Tambah Pegawai Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label text-muted small">NIP</label><input type="text" name="nip" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label text-muted small">Nama Lengkap</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label text-muted small">Jabatan</label><input type="text" name="jabatan" class="form-control"></div>
                    <div class="mb-2"><label class="form-label text-muted small">Unit Kerja</label><input type="text" name="unit_kerja" class="form-control"></div>
                    <div class="mb-2"><label class="form-label text-muted small">Kuota Cuti Awal</label><input type="number" name="kuota_cuti" class="form-control" value="19" required></div>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_pegawai" class="btn btn-success w-100">Simpan Anggota Baru</button></div>
            </form>
        </div></div></div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.getElementById('verifModal')?.addEventListener('show.bs.modal', function(e) {
                document.getElementById('verif_id').value = e.relatedTarget.getAttribute('data-id');
            });
            document.getElementById('tolakModal')?.addEventListener('show.bs.modal', function(e) {
                document.getElementById('tolak_id').value = e.relatedTarget.getAttribute('data-id');
            });
            document.getElementById('detailModal')?.addEventListener('show.bs.modal', function(e) {
                var nip = e.relatedTarget.getAttribute('data-nip');
                var nama = e.relatedTarget.getAttribute('data-nama');
                fetch('cuti.php?ajax_detail=1&nip=' + encodeURIComponent(nip))
                    .then(r=>r.text())
                    .then(html=>{
                        document.getElementById('detailContent').innerHTML = '<h6 class="mb-3 fw-bold"><i class="bi bi-person-badge text-primary me-1"></i> Staf: '+nama+'</h6>'+html;
                    });
            });
            document.getElementById('editPegawaiModal')?.addEventListener('show.bs.modal', function(e) {
                var btn = e.relatedTarget;
                document.getElementById('edit_nip_lama').value = btn.getAttribute('data-nip');
                document.getElementById('edit_nip').value = btn.getAttribute('data-nip');
                document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
                document.getElementById('edit_jabatan').value = btn.getAttribute('data-jabatan')||'';
                document.getElementById('edit_unit').value = btn.getAttribute('data-unit')||'';
                document.getElementById('edit_kuota').value = btn.getAttribute('data-kuota');
            });
        </script>
        
    </body>
    </html>
    <?php
    exit;
}

// ==================== DASHBOARD PEGAWAI ====================
if (is_pegawai_login() && !isset($_GET['admin'])) {
    $nip = $_SESSION['pegawai_nip'];
    $pegawai = $conn->query("SELECT p.*, (p.sisa_n1 + p.sisa_n) as total_sisa FROM pegawai p WHERE p.nip = '$nip'")->fetch_assoc();
    if (!$pegawai) { echo "<div class='alert alert-danger'>Data pegawai tidak ditemukan.</div>"; exit; }
    $sisa_cuti = $pegawai['total_sisa'];
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <title>Dashboard Cuti Pegawai</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif; color: #1e293b; min-height: 100vh; }
            .container { padding: 16px !important; max-width: 100% !important; }
            .card-modern { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 20px; }
            .card-header-custom { padding: 16px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9; }
            .profile-card-modern { background: #0f172a; border-radius: 20px; padding: 24px; color: #ffffff; margin-bottom: 24px; }
            .stats-grid-modern { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
            .stat-box { background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 14px 10px; text-align: center; }
            .stat-num { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.05em; line-height: 1.2; }
            .text-accent-warning { color: #fbbf24; }
            .text-accent-success { color: #34d399; }
            .stat-desc { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; font-weight: 500; }
            .form-control, .form-select { border-radius: 10px; border: 1px solid #cbd5e1; padding: 11px 14px; font-size: 0.9rem; color: #334155; }
            .form-control:focus, .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12); }
            .btn-modern-primary { background: #4f46e5; color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600; font-size: 0.95rem; transition: all 0.2s; }
            .btn-modern-primary:hover { background: #4338ca; color: white; }
            .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .table-modern { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
            .table-modern th { background: #f8fafc; padding: 14px 16px; font-weight: 600; color: #64748b; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0; }
            .table-modern td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
            .status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
            .navbar-blur { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 14px 20px; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100; }
            .action-buttons { display: flex; flex-direction: column; gap: 6px; }
            .action-buttons .btn { padding: 6px 12px; font-size: 0.75rem; font-weight: 500; border-radius: 8px; }
            @media (min-width: 768px) { .container { max-width: 800px !important; margin: 0 auto; padding: 32px 16px !important; } .action-buttons { flex-direction: row; gap: 8px; } }
        </style>
    </head>
    <body>
        <nav class="navbar-blur">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2"><div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-inline-flex"><i class="bi bi-calendar2-check-fill fs-5"></i></div><span class="fw-bold tracking-tight text-slate-900">E-Cuti Puskesmas</span></div>
                <div class="d-flex gap-2"><a href="index.php" class="btn btn-sm btn-light border rounded-3 px-3"><i class="bi bi-house-door me-1"></i><span class="d-none d-sm-inline">Beranda</span></a><a href="cuti.php?logout" class="btn btn-sm btn-light text-danger border rounded-3 px-3"><i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-sm-inline">Keluar</span></a></div>
            </div>
        </nav>
        <div class="container">
            <?php if(isset($error_msg_pegawai)) echo "<div class='alert alert-danger'>$error_msg_pegawai</div>"; ?>
            <div class="profile-card-modern shadow-sm">
                <div class="d-flex align-items-center gap-3 mb-4"><div class="bg-white bg-opacity-10 rounded-3 p-3 text-center" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-person-workspace fs-3"></i></div><div><h5 class="fw-bold mb-1"><?= htmlspecialchars($pegawai['nama']) ?></h5><p class="mb-0 small text-muted" style="color: #94a3b8 !important;">NIP. <?= htmlspecialchars($pegawai['nip']) ?></p></div></div>
                <div class="row g-2 mb-2"><div class="col-6"><div style="background: rgba(255,255,255,0.04); border-radius: 10px; padding: 10px 14px;"><span class="d-block text-muted small mb-1">Unit Kerja</span><span class="fw-medium small"><?= htmlspecialchars($pegawai['unit_kerja']) ?></span></div></div><div class="col-6"><div style="background: rgba(255,255,255,0.04); border-radius: 10px; padding: 10px 14px;"><span class="d-block text-muted small mb-1">Jabatan</span><span class="fw-medium small"><?= htmlspecialchars($pegawai['jabatan']) ?></span></div></div></div>
                <div class="stats-grid-modern">
                    <div class="stat-box"><div class="stat-num"><?= $pegawai['sisa_n1'] + $pegawai['sisa_n'] + ($pegawai['kuota_cuti'] - ($pegawai['sisa_n1']+$pegawai['sisa_n'])) ?></div><div class="stat-desc">Jatah Awal</div></div>
                    <div class="stat-box"><div class="stat-num text-accent-warning"><?= ($pegawai['kuota_cuti'] - ($pegawai['sisa_n1']+$pegawai['sisa_n'])) ?></div><div class="stat-desc">Terpakai</div></div>
                    <div class="stat-box"><div class="stat-num text-accent-success"><?= $sisa_cuti ?></div><div class="stat-desc">Sisa Kuota</div></div>
                </div>
            </div>
            <div class="card-modern">
                <div class="card-header-custom"><h6 class="fw-bold m-0"><i class="bi bi-plus-circle text-primary me-2"></i>Ajukan Cuti Baru</h6></div>
                <div class="p-4">
                    <?php if($sisa_cuti <= 0): ?>
                        <div class="alert alert-warning">Jatah cuti Anda sudah habis.</div>
                    <?php else: ?>
                        <form method="POST" id="formCuti">
                            <div class="mb-3"><label class="form-label small fw-semibold text-muted">Jenis Cuti</label><select name="jenis_cuti" class="form-select" required><option value="tahunan">Cuti Tahunan</option><option value="besar">Cuti Besar</option><option value="sakit">Cuti Sakit</option><option value="melahirkan">Cuti Melahirkan</option><option value="alasan_penting">Cuti Karena Alasan Penting</option><option value="luar_tanggungan">Cuti di Luar Tanggungan Negara</option></select></div>
                            <div class="mb-3"><label class="form-label small fw-semibold text-muted">Alasan Cuti</label><textarea name="alasan" class="form-control" rows="2" required></textarea></div>
                            <div class="row g-3 mb-3"><div class="col-6"><label class="form-label small fw-semibold text-muted">Tanggal Mulai</label><input type="text" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required></div><div class="col-6"><label class="form-label small fw-semibold text-muted">Tanggal Selesai</label><input type="text" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required></div></div>
                            <div class="mb-3"><label class="form-label small fw-semibold text-muted">Alamat Selama Cuti</label><textarea name="alamat" class="form-control" rows="2" required></textarea></div>
                            <div class="mb-4"><label class="form-label small fw-semibold text-muted">No. Telepon / WhatsApp Aktif</label><input type="tel" name="telepon" class="form-control" required></div>
                            <div class="mb-3 text-muted small">Jumlah hari kerja: <span id="jumlah_hari">0</span> hari</div>
                            <button type="submit" name="ajukan_cuti" class="btn btn-modern-primary w-100">Kirim Permohonan Cuti</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-modern">
                <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold m-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Pengajuan</h6>
                </div>
                <div class="table-wrapper">
                    <table class="table-modern">
                        <thead><tr><th>Periode Cuti</th><th>Durasi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php 
                            $riwayat = $conn->query("SELECT * FROM cuti WHERE nip='$nip' ORDER BY created_at DESC"); 
                            if($riwayat->num_rows == 0): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada riwayat pengajuan cuti.</td></tr>
                            <?php else: 
                                while($r = $riwayat->fetch_assoc()): 
                                    if ($r['status'] == 'menunggu_verifikasi') { $badge_style = 'background: #fef3c7; color: #b45309;'; $status_text = 'Menunggu'; }
                                    elseif ($r['status'] == 'disetujui') { $badge_style = 'background: #ecfdf5; color: #047857;'; $status_text = 'Disetujui'; }
                                    else { $badge_style = 'background: #fef2f2; color: #b91c1c;'; $status_text = 'Ditolak'; } 
                                    ?>
                                    <tr>
                                        <td><span class="fw-medium d-block"><?= formatTanggalIndo($r['tanggal_mulai']) ?></span><small class="text-muted">s.d <?= formatTanggalIndo($r['tanggal_selesai']) ?></small></td>
                                        <td><span class="fw-semibold"><?= $r['jumlah_hari'] ?> Hari</span></td>
                                        <td><span class="status-pill" style="<?= $badge_style ?>"><?= $status_text ?></span></td>
                                        <td class="action-buttons"><a href="cetak_cuti.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-light border" target="_blank">Cetak</a><?php if($r['status'] == 'disetujui' && $r['file_scan']) echo '<a href="'.$r['file_scan'].'" class="btn btn-sm btn-dark" target="_blank">Dokumen</a>'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
        <script>
            let hariLibur = [];
            fetch('cuti.php?ajax_libur=1').then(res=>res.json()).then(data=>{ hariLibur = data; initFlatpickr(); });
            function initFlatpickr() {
                flatpickr("#tanggal_mulai", { locale: "id", firstDayOfWeek: 1, dateFormat: "Y-m-d", disable: [function(date) { if (date.getDay() === 0) return true; let tglStr = date.toISOString().split('T')[0]; return hariLibur.includes(tglStr); }], onChange: hitungHari });
                flatpickr("#tanggal_selesai", { locale: "id", firstDayOfWeek: 1, dateFormat: "Y-m-d", disable: [function(date) { if (date.getDay() === 0) return true; let tglStr = date.toISOString().split('T')[0]; return hariLibur.includes(tglStr); }], onChange: hitungHari });
            }
            function hitungHari() {
                let mulai = document.getElementById('tanggal_mulai').value;
                let selesai = document.getElementById('tanggal_selesai').value;
                if (!mulai || !selesai) return;
                fetch(`cuti.php?ajax_hitung_hari=1&mulai=${mulai}&selesai=${selesai}`).then(res=>res.text()).then(jml=>document.getElementById('jumlah_hari').innerText = jml);
            }
        </script>
    </body>
    </html>
    <?php
}
?>
