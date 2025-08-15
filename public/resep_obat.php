<?php
require_once '../config/db.php';

// FUNGSI UNTUK MENGHITUNG UMUR (YANG SEBELUMNYA HILANG)
function hitungUmur(string $tgl_lahir): string {
    if (!$tgl_lahir || $tgl_lahir === '0000-00-00') return '-';
    try {
        $lahir = new DateTime($tgl_lahir);
        $today = new DateTime('today');
        $umur = $lahir->diff($today);
        return "{$umur->y} Th {$umur->m} Bl {$umur->d} Hr";
    } catch (Exception $e) {
        return '-';
    }
}

$no_rawat = $_GET['no_rawat'] ?? '';
$pasien = null;
$dokter = null;

if (!empty($no_rawat)) {
    // MENGGUNAKAN PDO, BUKAN KONEKSI YANG LAMA
    $sql = "SELECT 
                p.nm_pasien, p.jk, p.tgl_lahir, 
                d.kd_dokter, d.nm_dokter,
                rp.no_reg
            FROM reg_periksa rp
            JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            WHERE rp.no_rawat = :no_rawat";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $no_rawat]);
    $pasien = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Menyiapkan variabel untuk ditampilkan
$nama_pasien    = $pasien ? $pasien['nm_pasien'] : 'Pasien Tidak Ditemukan';
$jenis_kelamin  = $pasien ? ($pasien['jk'] == 'L' ? 'Laki-laki' : 'Perempuan') : '-';
$tgl_lahir      = $pasien ? date("d-m-Y", strtotime($pasien['tgl_lahir'])) : '-';
$umur           = $pasien ? hitungUmur($pasien['tgl_lahir']) : '-';
$no_resep       = $pasien ? $pasien['no_reg'] : date('YmdHis'); // Nomor resep bisa dari no registrasi atau generate baru
$kd_dokter      = $pasien ? $pasien['kd_dokter'] : '';
require __DIR__.'/partials/header.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Resep Obat</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .patient-header { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin-bottom: 20px; background: #f7f9fc; padding: 15px; border-radius: 8px; border: 1px solid #e1e6ef;}
        .patient-header .header-item label { font-weight: 600; min-width: 80px; display: inline-block; }
        .resep-number { margin-bottom: 20px; }
        .prescription-table { width: 100%; border-collapse: collapse; }
        .prescription-table th, .prescription-table td { border: 1px solid #e1e6ef; padding: 10px; text-align: left; }
        .prescription-table th { background-color: #f5f7fc; }
        .obat-cell { position: relative; }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccc; z-index: 10; max-height: 200px; overflow-y: auto; }
        .search-results div { padding: 8px; cursor: pointer; }
        .search-results div:hover { background-color: #f0f0f0; }
        .readonly { background-color: #e9ecef !important; }
    </style>
</head>
<body>
<div class="container">
    <h3>RESEP OBAT</h3>
    <?php if (isset($_GET['status'])): ?>
        <div class="alert <?= $_GET['status'] == 'sukses' ? 'success' : 'error' ?>" style="padding: 1rem; border-radius: 5px; margin-bottom: 1rem; background-color: <?= $_GET['status'] == 'sukses' ? '#d4edda' : '#f8d7da' ?>; color: <?= $_GET['status'] == 'sukses' ? '#155724' : '#721c24' ?>;">
            <?= $_GET['status'] == 'sukses' ? 'Resep berhasil disimpan!' : 'Gagal menyimpan resep. Pesan: ' . htmlspecialchars($_GET['pesan'] ?? 'Error') ?>
        </div>
    <?php endif; ?>
    
    <div class="patient-header">
        <div class="header-item"><label>Nama</label>: <?= htmlspecialchars($nama_pasien); ?></div>
        <div class="header-item"><label>Tgl. Lahir</label>: <?= htmlspecialchars($tgl_lahir); ?></div>
        <div class="header-item"><label>J. Kelamin</label>: <?= htmlspecialchars($jenis_kelamin); ?></div>
        <div class="header-item"><label>Umur</label>: <?= htmlspecialchars($umur); ?></div>
    </div>

    <form action="../action/save_resep.php" method="POST">
        <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($no_rawat); ?>">
        <input type="hidden" name="kd_dokter" value="<?= htmlspecialchars($kd_dokter); ?>">

        <div class="resep-number">
            <label for="no_resep">No. Resep</label>
            <input type="text" id="no_resep" name="no_resep" value="<?= htmlspecialchars($no_resep); ?>" readonly class="readonly">
        </div>

        <table class="prescription-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 40%;">Obat</th>
                    <th style="width: 10%;">Jumlah</th>
                    <th style="width: 25%;">Aturan Pakai</th>
                    <th style="width: 10%;">Stok</th>
                    <th style="width: 10%;">Harga</th>
                </tr>
            </thead>
            <tbody id="resep-body">
                <?php for ($i = 0; $i < 10; $i++): ?>
                <tr class="resep-row">
                    <td class="text-center"><?= $i + 1; ?></td>
                    <td class="obat-cell">
                        <input type="text" class="obat-search" placeholder="Ketik nama obat..." autocomplete="off">
                        <input type="hidden" class="obat-input" name="obat[]">
                        <div class="search-results" style="display:none;"></div>
                    </td>
                    <td><input type="number" name="jumlah[]" min="1"></td>
                    <td><input type="text" name="aturan_pakai[]" placeholder="Contoh: 3x1 sehari"></td>
                    <td><input type="text" class="stok-input" readonly class="readonly"></td>
                    <td><input type="text" class="harga-input" readonly class="readonly"></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <div class="text-right mt-24">
            <button type="submit">Simpan Resep</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('resep-body');

    const debounce = (func, delay) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    const searchObat = async (input, resultsContainer) => {
        const keyword = input.value;
        if (keyword.length < 3) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return;
        }

        try {
            // Memanggil API yang baru kita buat
            const response = await fetch(`../action/search_obat.php?keyword=${keyword}`);
            const data = await response.json();

            resultsContainer.innerHTML = '';
            if (data.length > 0) {
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.innerHTML = `${item.nama_brng} (Stok: ${item.stok})`;
                    div.dataset.kode = item.kode_brng;
                    div.dataset.nama = item.nama_brng;
                    div.dataset.stok = item.stok;
                    div.dataset.harga = item.harga;
                    resultsContainer.appendChild(div);
                });
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.style.display = 'none';
            }
        } catch (error) {
            console.error('Error fetching obat:', error);
        }
    };

    tableBody.addEventListener('keyup', (e) => {
        if (e.target && e.target.classList.contains('obat-search')) {
            const input = e.target;
            const resultsContainer = input.closest('.obat-cell').querySelector('.search-results');
            debounce(searchObat, 300)(input, resultsContainer);
        }
    });

    tableBody.addEventListener('click', (e) => {
        if (e.target && e.target.parentElement.classList.contains('search-results')) {
            const selectedItem = e.target;
            const row = selectedItem.closest('.resep-row');
            
            // Input yang terlihat oleh user
            const obatSearch = row.querySelector('.obat-search');
            // Input hidden yang akan dikirim ke server
            const obatInput = row.querySelector('.obat-input');
            const stokInput = row.querySelector('.stok-input');
            const hargaInput = row.querySelector('.harga-input');
            const resultsContainer = selectedItem.parentElement;

            obatSearch.value = selectedItem.dataset.nama;
            obatInput.value = selectedItem.dataset.kode; // Simpan kode barangnya
            stokInput.value = selectedItem.dataset.stok;
            hargaInput.value = selectedItem.dataset.harga;
        
            resultsContainer.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.obat-cell')) {
            document.querySelectorAll('.search-results').forEach(div => {
                div.style.display = 'none';
            });
        }
    });
});
</script>
</body>
</html>