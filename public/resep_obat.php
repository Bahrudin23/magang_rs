<?php
require_once '../config/db.php';
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
        .obat-cell, .pasien-cell { position: relative; }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccc; z-index: 10; max-height: 200px; overflow-y: auto; list-style-type:none; padding:0; margin: 4px 0; }
        .search-results div, .search-results li { padding: 8px; cursor: pointer; }
        .search-results div:hover, .search-results li:hover { background-color: #f0f0f0; }
        .readonly { background-color: #e9ecef !important; }
        .nb-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e0e0e0; font-size: 0.9em; color: #666; }
        .nb-footer p { margin: 0.25rem 0; }
        .harga-total-input { font-weight: bold; }
    
    .aksi-cell { white-space: nowrap; }
    .btn-tambah, .btn-hapus { padding: 4px 8px; margin-right: 4px; }
</style>
</head>
<body>
<div class="container">
    <h3>RESEP OBAT</h3>
    
    <div class="patient-header">
        <div class="pasien-cell header-item">
            <label>Nama</label>: 
            <input type="text" id="pasien-search" placeholder="Ketik nama pasien ranap..." autocomplete="off" style="width: 250px; display: inline-block; padding: 4px 8px; border-radius: 4px; border: 1px solid #ccc;">
            <ul id="pasien-suggestions" class="search-results" style="display:none;"></ul>
        </div>
        <div class="header-item"><label>Tgl. Lahir</label>: <span id="tgl_lahir">-</span></div>
        <div class="header-item"><label>J. Kelamin</label>: <span id="jenis_kelamin">-</span></div>
        <div class="header-item"><label>Umur</label>: <span id="umur">-</span></div>
    </div>

    <form action="../action/save_resep.php" method="POST" id="form-resep">
        <input type="hidden" name="no_rawat" id="no_rawat">
        <input type="hidden" name="kd_dokter" id="kd_dokter">
        <input type="hidden" id="kd_pj" name="kd_pj">

        <div class="resep-number">
            <label for="no_resep">No. Resep</label>
            <input type="text" id="no_resep" name="no_resep" readonly class="readonly">
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
        <th style="width: 10%;">Aksi</th>
    </tr>
</thead>
            <tbody id="resep-body">
    <tr class="resep-row">
        <td class="text-center nomor-cell">1</td>
        <td class="obat-cell">
            <input type="text" class="obat-search" placeholder="Ketik nama obat..." autocomplete="off">
            <input type="hidden" class="obat-input" name="obat[]">
            <div class="search-results" style="display:none;"></div>
        </td>
        <td><input type="number" class="jumlah-input" name="jumlah[]" min="1" value="1"></td>
        <td><input type="text" name="aturan_pakai[]" placeholder="Contoh: 3x1 sehari"></td>
        <td><input type="text" class="stok-input" readonly></td>
        <td><input type="text" class="harga-total-input" readonly></td>
        <td class="aksi-cell">
            <button type="button" class="btn-tambah" title="Tambah baris">+</button>
            <button type="button" class="btn-hapus" title="Hapus baris">🗑</button>
        </td>
    </tr>
</tbody>
        </table>
        <div class="text-right mt-24">
            <button type="submit">Simpan Resep</button>
        </div>
    </form>

    <div class="nb-footer">
        <p><b>NB:</b></p>
        <p>– Harga yang tertera adalah harga persatuan sesuai Jenis Pasien.</p>
        <p>– Obat bisa langsung dicari di kolom 'Nama Obat'.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let patientCache = {};
    const pasienSearchInput = document.getElementById('pasien-search');
    const pasienSuggestions = document.getElementById('pasien-suggestions');
    const tableBody = document.getElementById('resep-body');

    const debounce = (func, delay = 300) => {
        let timeout;
        return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); };
    };

    const searchPasien = async () => {
        const keyword = pasienSearchInput.value;
        if (keyword.length < 2) {
            pasienSuggestions.style.display = 'none'; return;
        }
        try {
            const response = await fetch(`../action/get_resep_pasien_data.php?q=${keyword}`);
            const data = await response.json();
            
            pasienSuggestions.innerHTML = '';
            patientCache = {};
            if (data && data.length > 0) {
                data.forEach(item => {
                    patientCache[item.no_rawat] = item;
                    const li = document.createElement('li');
                    li.innerHTML = `<b>${item.nm_pasien}</b> (No. Rawat: ${item.no_rawat})`;
                    li.dataset.noRawat = item.no_rawat;
                    pasienSuggestions.appendChild(li);
                });
                pasienSuggestions.style.display = 'block';
            } else {
                pasienSuggestions.innerHTML = '<li>Pasien ranap tidak ditemukan</li>';
            }
        } catch (error) { console.error('Error fetching pasien:', error); }
    };
    
    const selectPasien = async (noRawat) => {
        const detail = patientCache[noRawat];
        if (!detail) return;
        
        pasienSearchInput.value = detail.nm_pasien;
        pasienSuggestions.style.display = 'none';

        document.getElementById('no_rawat').value = detail.no_rawat;
        document.getElementById('kd_dokter').value = detail.kd_dokter;
        document.getElementById('kd_pj').value = detail.kd_pj;
        document.getElementById('tgl_lahir').textContent = detail.tgl_lahir_formatted;
        document.getElementById('jenis_kelamin').textContent = detail.jk_formatted;
        document.getElementById('umur').textContent = detail.umur;
        loadRiwayat(detail.no_rawat);
        
        try {
            const resepNoRes = await fetch(`../action/generate_no_resep.php`);
            const resepNoData = await resepNoRes.json();
            document.getElementById('no_resep').value = resepNoData.no_resep;
        } catch (error) {
             document.getElementById('no_resep').value = 'Error';
             console.error('Gagal membuat No. Resep:', error);
        }
    };

    const searchObat = async (input, resultsContainer) => {
        const keyword = input.value;
        const kd_pj = document.getElementById('kd_pj').value;
        if (!kd_pj) {
            alert('Pilih pasien terlebih dahulu!'); return;
        }
        if (keyword.length < 3) {
            resultsContainer.style.display = 'none'; return;
        }
        try {
            const response = await fetch(`../action/search_obat.php?keyword=${keyword}&pj=${kd_pj}`);
            const data = await response.json();

            resultsContainer.innerHTML = '';
            if (data.length > 0) {
                data.forEach(item => {
                    const div = document.createElement('div');
                    div.innerHTML = `${item.nama_brng} (Stok: ${item.stok || 0})`;
                    div.dataset.kode = item.kode_brng;
                    div.dataset.nama = item.nama_brng;
                    div.dataset.stok = item.stok || 0;
                    div.dataset.hargaSatuan = item.harga_satuan; 
                    resultsContainer.appendChild(div);
                });
                resultsContainer.style.display = 'block';
            }
        } catch (error) { console.error('Error fetching obat:', error); }
    };

    // --- EVENT LISTENERS ---
    pasienSearchInput.addEventListener('input', debounce(searchPasien));
    
    pasienSuggestions.addEventListener('click', (e) => {
        const li = e.target.closest('li');
        if (li && li.dataset.noRawat) {
            selectPasien(li.dataset.noRawat);
        }
    });

    tableBody.addEventListener('keyup', (e) => {
        if (e.target.classList.contains('obat-search')) {
            debounce(searchObat)(e.target, e.target.closest('.obat-cell').querySelector('.search-results'));
        }
    });

    tableBody.addEventListener('click', (e) => {
        const targetDiv = e.target.closest('.search-results > div');
        if (targetDiv) {
            const row = targetDiv.closest('.resep-row');
            const hargaSatuan = targetDiv.dataset.hargaSatuan;

            row.dataset.hargaSatuan = hargaSatuan;
            row.querySelector('.obat-search').value = targetDiv.dataset.nama;
            row.querySelector('.obat-input').value = targetDiv.dataset.kode;
            row.querySelector('.stok-input').value = targetDiv.dataset.stok;
            
            const jumlahInput = row.querySelector('.jumlah-input');
            jumlahInput.value = 1;
            
            const hargaTotalInput = row.querySelector('.harga-total-input');
            hargaTotalInput.value = hargaSatuan;

            targetDiv.parentElement.style.display = 'none';
        }
    });

    tableBody.addEventListener('input', (e) => {
        if (e.target.classList.contains('jumlah-input')) {
            const row = e.target.closest('.resep-row');
            const hargaSatuan = parseFloat(row.dataset.hargaSatuan || 0);
            const jumlah = parseInt(e.target.value || 0);
            const hargaTotalInput = row.querySelector('.harga-total-input');
            
            const total = hargaSatuan * jumlah;
            hargaTotalInput.value = isNaN(total) ? 0 : total;
        }
    });
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.pasien-cell') && !e.target.closest('.obat-cell')) {
            document.querySelectorAll('.search-results').forEach(el => el.style.display = 'none');
        }
    });

// === Helper to get URL params ===
const urlParams = new URLSearchParams(window.location.search);

// === Add/Remove rows ===
function renumberRows(){
    document.querySelectorAll('#resep-body .resep-row').forEach((tr, idx)=>{
        const cell = tr.querySelector('.nomor-cell');
        if (cell) cell.textContent = String(idx+1);
    });
}
function newRow(){
    const tr = document.createElement('tr');
    tr.className = 'resep-row';
    tr.innerHTML = `
        <td class="text-center nomor-cell"></td>
        <td class="obat-cell">
            <input type="text" class="obat-search" placeholder="Ketik nama obat..." autocomplete="off">
            <input type="hidden" class="obat-input" name="obat[]">
            <div class="search-results" style="display:none;"></div>
        </td>
        <td><input type="number" class="jumlah-input" name="jumlah[]" min="1" value="1"></td>
        <td><input type="text" name="aturan_pakai[]" placeholder="Contoh: 3x1 sehari"></td>
        <td><input type="text" class="stok-input" readonly></td>
        <td><input type="text" class="harga-total-input" readonly></td>
        <td class="aksi-cell">
            <button type="button" class="btn-tambah" title="Tambah baris">+</button>
            <button type="button" class="btn-hapus" title="Hapus baris">🗑</button>
        </td>
    `;
    return tr;
}
document.getElementById('resep-body').addEventListener('click', (e)=>{
    if (e.target.classList.contains('btn-tambah')){
        const tr = newRow();
        e.target.closest('tr').after(tr);
        renumberRows();
    }
    if (e.target.classList.contains('btn-hapus')){
        const tbody = document.getElementById('resep-body');
        if (tbody.querySelectorAll('.resep-row').length === 1){
            // reset inputs if only one row left
            const row = tbody.querySelector('.resep-row');
            row.querySelectorAll('input').forEach(i=>{ if(i.type==='hidden') i.value = ''; else i.value=''; });
            row.dataset.hargaSatuan = 0;
        } else {
            e.target.closest('tr').remove();
        }
        renumberRows();
    }
});

// === Auto-fill patient when opened from Detail ===
async function fetchByNoRawat(no_rawat){
    try{
        const res = await fetch(`../action/search_rawat.php?no=${encodeURIComponent(no_rawat)}`);
        const data = await res.json();
        if(!data) return;
        document.getElementById('no_rawat').value = data.no_rawat;
        document.getElementById('pasien-search').value = data.nm_pasien;

        // Lengkapi data detail (tgl lahir, jk, umur, kd_dokter, kd_pj)
        const res2 = await fetch(`../action/get_resep_pasien_data.php?q=${encodeURIComponent(data.no_rkm_medis)}`);
        const arr = await res2.json();
        if (Array.isArray(arr) && arr.length){
            const d = arr[0];
            document.getElementById('tgl_lahir').textContent = d.tgl_lahir_formatted || '-';
            document.getElementById('jenis_kelamin').textContent = d.jk_formatted || '-';
            document.getElementById('umur').textContent = d.umur || '-';
            document.getElementById('kd_dokter').value = d.kd_dokter || '';
            document.getElementById('kd_pj').value = d.kd_pj || '';
            loadRiwayat(data.no_rawat);
        }
    }catch(err){ console.error(err); }
}

// If url has no_rawat => auto fill
if (urlParams.get('no_rawat')){
    fetchByNoRawat(urlParams.get('no_rawat'));
}

// === Riwayat Resep ===
async function loadRiwayat(no_rawat){
    try{
        if (!no_rawat){ 
            document.getElementById('riwayat-container').style.display = 'none';
            return;
        }
        const res = await fetch(`../action/get_riwayat_resep.php?no_rawat=${encodeURIComponent(no_rawat)}`);
        const html = await res.text();
        const box = document.getElementById('riwayat-container');
        box.innerHTML = html;
        box.style.display = html.trim() ? 'block' : 'none';
    }catch(err){
        console.error(err);
    }
}

// Show/hide riwayat only after patient picked from suggestions too
pasienSuggestions?.addEventListener('click', (e)=>{
    const li = e.target.closest('li[data-no_rawat]');
    if (li){
        loadRiwayat(li.dataset.no_rawat);
    }
});

});
</script>
</body>
</html>