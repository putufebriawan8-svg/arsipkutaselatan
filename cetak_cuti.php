<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$host = 'sql206.infinityfree.com';
$db   = 'if0_41772736_puskesmas_db';
$user = 'if0_41772736';
$pass = 'Cok123let';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

function formatTanggalIndo($date) {
    if (empty($date)) return '';
    $bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
              '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
    $parts = explode('-', $date);
    if (count($parts)==3) return $parts[2].' '.$bulan[$parts[1]].' '.$parts[0];
    return $date;
}

$id = (int)$_GET['id'];
if (!$id) die("ID cuti tidak valid.");

$cuti = $conn->query("SELECT c.*, p.nama, p.jabatan, p.unit_kerja, p.masa_kerja, p.kuota_cuti 
                      FROM cuti c JOIN pegawai p ON c.nip = p.nip WHERE c.id = $id")->fetch_assoc();
if (!$cuti) die("Data cuti tidak ditemukan.");

// Data dinamis sesuai variabel di formulir_cuti.php
$tanggal_surat = "Mangupura, " . formatTanggalIndo(date('Y-m-d'));
$nama_pegawai = $cuti['nama'];
$nipppk = $cuti['nip'];
$jabatan = $cuti['jabatan'];
$masa_kerja = $cuti['masa_kerja'] ?: '1 Tahun, 0 Bulan';
$unit_kerja = $cuti['unit_kerja'];
$alasan_cuti = nl2br(htmlspecialchars($cuti['alasan']));
$lama_cuti = $cuti['jumlah_hari'];
$tgl_mulai = formatTanggalIndo($cuti['tanggal_mulai']);
$tgl_selesai = formatTanggalIndo($cuti['tanggal_selesai']);
$alamat_cuti = nl2br(htmlspecialchars($cuti['alamat_selama_cuti']));
$telp = htmlspecialchars($cuti['telepon_selama']);
$sisa_n = $cuti['kuota_cuti'] . ' Hari';

$jenis_cuti = $cuti['jenis_cuti'];
$centang1 = ($jenis_cuti == 'tahunan') ? '✓' : '';
$centang2 = ($jenis_cuti == 'besar') ? '✓' : '';
$centang3 = ($jenis_cuti == 'sakit') ? '✓' : '';
$centang4 = ($jenis_cuti == 'melahirkan') ? '✓' : '';
$centang5 = ($jenis_cuti == 'alasan_penting') ? '✓' : '';
$centang6 = ($jenis_cuti == 'luar_tanggungan') ? '✓' : '';

$atasan_jabatan = "Kepala UPTD Puskesmas Kuta Selatan";
$atasan_nama = "dr. I Made Sugiana, M.Kes.";
$atasan_nip = "19751205 200312 1 010";

$pejabat_jabatan = "Kepala Dinas Kesehatan Kabupaten Badung";
$pejabat_nama = "dr. Made Padma Puspita, Sp.PD.";
$pejabat_pangkat = "Pembina Tingkat I";
$pejabat_nip = "19810909 200902 1 004";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Permintaan dan Pemberian Cuti</title>
    <style>
        /* Layout PERSIS SAMA dengan formulir_cuti.php user (tidak ada perubahan) */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        @page {
            size: Legal; /* HANYA ini yang diubah: dari A4 menjadi Legal */
            margin: 15mm 20mm 15mm 20mm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10.5pt;
            line-height: 1.25;
            color: #000000;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            width: 100%;
            max-width: 794px;
            margin: 0 auto;
        }
        .header-table {
            width: 45%;
            margin-left: auto;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .header-table td {
            padding: 2px 0;
        }
        .judul-form {
            font-weight: bold;
            font-size: 11pt;
            text-decoration: underline;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .main-form-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
        }
        .section-header {
            font-weight: bold;
            text-transform: uppercase;
            background-color: #ffffff;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }
        .space-row td {
            height: 15px;
            padding: 0 !important;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            background-color: #ffffff;
        }
        .main-form-table td {
            padding: 5px 6px;
            vertical-align: middle;
        }
        .footer-note {
            font-size: 8pt;
            line-height: 1.3;
            margin-top: 15px;
        }
        .footer-note p {
            margin: 2px 0;
        }
        /* Tombol cetak (hanya tampil di layar) */
        .print-button-container {
            text-align: center;
            margin: 10px 0 15px;
            padding: 8px;
            background: #f0f2f5;
            border-bottom: 1px solid #ccc;
        }
        .print-button {
            display: inline-block;
            padding: 6px 12px;
            font-size: 10pt;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin: 0 4px;
        }
        .print-button:hover { background: #0056b3; }
        .back-button { background: #6c757d; }
        @media print {
            .print-button-container { display: none; }
        }
    </style>
</head>
<body>
<div class="print-button-container">
    <button onclick="window.print()" class="print-button">🖨 Cetak / Simpan PDF</button>
    <a href="cuti.php" class="print-button back-button">← Kembali</a>
</div>

<div class="container">
    <table class="header-table">
        <tr><td style="width: 100%;"><?php echo $tanggal_surat; ?></td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td>
                Kepada :<br>
                Yth. Kepala Dinas Kesehatan<br>
                Kabupaten Badung<br>
                di<br>
                <span style="padding-left: 15px;">Mangupura</span>
            </td>
        </tr>
    </table>

    <div class="judul-form">FORMULIR PERMINTAAN DAN PEMBERIAN CUTI</div>

    <table class="main-form-table">
        <!-- I. DATA PEGAWAI -->
        <tr>
            <td colspan="4" class="section-header" style="border-top: none;">I. DATA PEGAWAI</td>
        </tr>
        <tr>
            <td style="width: 15%; border-right: 1px solid #000;">Nama</td>
            <td style="width: 35%; border-right: 1px solid #000;"><?php echo htmlspecialchars($nama_pegawai); ?></td>
            <td style="width: 15%; border-right: 1px solid #000;">NIPPPK</td>
            <td style="width: 35%;"><?php echo htmlspecialchars($nipppk); ?></td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000;">Jabatan</td>
            <td style="border-right: 1px solid #000;"><?php echo htmlspecialchars($jabatan); ?></td>
            <td style="border-right: 1px solid #000;">Masa Kerja</td>
            <td style="border-right: 1px solid #000;"><?php echo htmlspecialchars($masa_kerja); ?></td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000;">Unit Kerja</td>
            <td colspan="3"><?php echo htmlspecialchars($unit_kerja); ?></td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- II. JENIS CUTI -->
        <tr>
            <td colspan="4" class="section-header">II. JENIS CUTI YANG DIAMBIL **</td>
        </tr>
        <tr>
            <td colspan="4" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 4%; text-align: center; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000;">1.</td>
                        <td style="width: 36%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Cuti Tahunan</td>
                        <td style="width: 10%; text-align: center; font-family: Arial, sans-serif; font-size: 12pt; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000;"><?php echo $centang1; ?></td>
                        <td style="width: 4%; text-align: center; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000;">2.</td>
                        <td style="width: 32%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Cuti besar</td>
                        <td style="width: 14%; border-bottom: 1px solid #000;"><?php echo $centang2; ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000;">3.</td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">Cuti Sakit</td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"><?php echo $centang3; ?></td>
                        <td style="text-align: center; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000;">4.</td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">Cuti Melahirkan</td>
                        <td style="border-bottom: 1px solid #000;"><?php echo $centang4; ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: center; font-weight: bold; border-right: 1px solid #000;">5.</td>
                        <td style="border-right: 1px solid #000;">Cuti Karena Alasan Penting</td>
                        <td style="border-right: 1px solid #000;"><?php echo $centang5; ?></td>
                        <td style="text-align: center; font-weight: bold; border-right: 1px solid #000;">6.</td>
                        <td style="border-right: 1px solid #000;">Cuti di Luar Tanggungan Negara</td>
                        <td><?php echo $centang6; ?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- III. ALASAN CUTI -->
        <tr>
            <td colspan="4" class="section-header">III. ALASAN CUTI</td>
        </tr>
        <tr>
            <td colspan="4" style="height: 38px; vertical-align: top;"><?php echo $alasan_cuti; ?></td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- IV. LAMANYA CUTI -->
        <tr>
            <td colspan="4" class="section-header">IV. LAMANYA CUTI</td>
        </tr>
        <tr>
            <td colspan="4" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 10%; border-right: 1px solid #000;">Selama</td>
                        <td style="width: 25%; border-right: 1px solid #000;"><?php echo $lama_cuti; ?> ( hari / <span style="text-decoration: line-through;">bulan</span> / <span style="text-decoration: line-through;">tahun</span> )</td>
                        <td style="width: 15%; border-right: 1px solid #000;">Mulai tanggal</td>
                        <td style="width: 20%; text-align: center; border-right: 1px solid #000;"><?php echo $tgl_mulai; ?></td>
                        <td style="width: 8%; text-align: center; border-right: 1px solid #000;">s/d</td>
                        <td style="width: 22%; text-align: center;"><?php echo $tgl_selesai; ?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- V. LAMANYA CUTI *** -->
        <tr>
            <td colspan="4" class="section-header">V. LAMANYA CUTI ***</td>
        </tr>
        <tr>
            <td colspan="4" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td colspan="3" style="width: 50%; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000;">1. CUTI TAHUNAN</td>
                        <td colspan="3" style="width: 50%; font-weight: bold; border-bottom: 1px solid #000;">2. CUTI BESAR</td>
                    </tr>
                    <tr>
                        <td style="width: 12%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;">Tahun</td>
                        <td style="width: 15%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;">Sisa</td>
                        <td style="width: 23%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;">Keterangan</td>
                        <td colspan="3" style="border-bottom: 1px solid #000;">3. CUTI SAKIT</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;">N-1</td>
                        <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;">NIHIL</td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td colspan="3" style="border-bottom: 1px solid #000;">4. CUTI MELAHIRKAN</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;">N</td>
                        <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000;"><?php echo $sisa_n; ?></td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td colspan="3" style="border-bottom: 1px solid #000;">5. CUTI KARENA ALASAN PENTING</td>
                    </tr>
                    <tr>
                        <td style="text-align: center; border-right: 1px solid #000;">N-B</td>
                        <td style="text-align: center; border-right: 1px solid #000;">7 Hari</td>
                        <td style="border-right: 1px solid #000;"></td>
                        <td colspan="3">6. Cuti di Luar Tanggungan Negara</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- VI. ALAMAT SELAMA MENJALANI CUTI -->
        <tr>
            <td colspan="4" class="section-header">VI. ALAMAT SELAMA MENJALANI CUTI</td>
        </tr>
        <tr>
            <td colspan="3" style="width: 65%; height: 110px; vertical-align: top; border-right: 1px solid #000; padding: 6px;">
                <?php echo $alamat_cuti; ?>
            </td>
            <td style="width: 35%; vertical-align: top; padding: 4px;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="border: none; padding: 0 0 15px 0; font-size: 10pt;">TELP.</td>
                        <td style="border: none; padding: 0 0 15px 0; font-size: 10pt; text-align: right;"><?php echo $telp; ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border: none; padding: 10px 0 45px 0; font-size: 10pt;">Hormat Saya,</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border: none; padding: 0; font-weight: bold; text-decoration: underline; font-size: 10pt;"><?php echo htmlspecialchars($nama_pegawai); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="border: none; padding: 0; font-size: 9.5pt;">NIPPPK. <?php echo htmlspecialchars($nipppk); ?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- VII. PERTIMBANGAN ATASAN LANGSUNG -->
        <tr>
            <td colspan="4" class="section-header">VII. PERTIMBANGAN ATASAN LANGSUNG**</td>
        </tr>
        <tr>
            <td colspan="4" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr style="text-align: center; font-weight: bold;">
                        <td style="width: 15%; border-right: 1px solid #000; border-bottom: 1px solid #000;">DISETUJUI</td>
                        <td style="width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000;">PERUBAHAN****</td>
                        <td style="width: 25%; border-right: 1px solid #000; border-bottom: 1px solid #000;">DITANGGUHKAN****</td>
                        <td style="width: 40%; border-bottom: 1px solid #000;">TIDAK DISETUJUI****</td>
                    </tr>
                    <tr style="height: 22px;">
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td rowspan="2" style="vertical-align: top; padding: 6px;">
                            <div style="font-size: 9.5pt; margin-bottom: 50px; text-align: left;"><?php echo $atasan_jabatan; ?></div>
                            <div style="font-weight: bold; text-decoration: underline; font-size: 10pt;"><?php echo $atasan_nama; ?></div>
                            <div style="font-size: 9.5pt; margin-top: 2px;">NIP. <?php echo $atasan_nip; ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="height: 90px; border-right: 1px solid #000;"></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="space-row"><td colspan="4"></td></tr>

        <!-- VIII. KEPUTUSAN PEJABAT YANG BERWENANG -->
        <tr>
            <td colspan="4" class="section-header">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI**</td>
        </tr>
        <tr>
            <td colspan="4" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr style="text-align: center; font-weight: bold;">
                        <td style="width: 15%; border-right: 1px solid #000; border-bottom: 1px solid #000;">DISETUJUI</td>
                        <td style="width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000;">PERUBAHAN****</td>
                        <td style="width: 25%; border-right: 1px solid #000; border-bottom: 1px solid #000;">DITANGGUHKAN****</td>
                        <td style="width: 40%; border-bottom: 1px solid #000;">TIDAK DISETUJUI****</td>
                    </tr>
                    <tr style="height: 22px;">
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>
                        <td rowspan="2" style="vertical-align: top; padding: 6px;">
                            <div style="font-size: 9.5pt; margin-bottom: 55px; text-align: left;"><?php echo $pejabat_jabatan; ?></div>
                            <div style="font-weight: bold; text-decoration: underline; font-size: 10pt;"><?php echo $pejabat_nama; ?></div>
                            <div style="font-size: 9.5pt; margin-top: 2px;"><?php echo $pejabat_pangkat; ?></div>
                            <div style="font-size: 9.5pt;">NIP. <?php echo $pejabat_nip; ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="height: 100px; border-right: 1px solid #000;"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        <p>Catatan :</p>
        <p>* Coret yang tidak perlu</p>
        <p>** Pilih salah satu dengan memberi tanda centang ( √ )</p>
        <p>*** diisi oleh pejabat yang menangani bidang kepegawaian sebelum PNS mengajukan cuti</p>
        <p>**** diberi tanda centang dan alasannnya</p>
        <p>N= Cuti tahun berjalan</p>
        <p>N-1 = Sisa cuti 1 tahun sebelumnya</p>
    </div>
</div>
</body>
</html>