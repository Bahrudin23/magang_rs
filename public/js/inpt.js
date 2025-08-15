// ===== Helper Functions =====
const $ = s => document.querySelector(s);
async function fetchJSON(url) {
    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!r.ok) throw new Error(r.status + ' ' + r.statusText);
    return r.json();
}
function debounce(fn, delay = 300) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), delay); } }
function showList(el) { if (el) el.style.display = 'block'; }
function hideList(el) { if (el) { el.style.display = 'none'; el.innerHTML = ''; } }

const BASE = '..';

setInterval(function(){
    const now = new Date();
    const year  = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day   = String(now.getDate()).padStart(2, '0');
    const hh    = String(now.getHours()).padStart(2, '0');
    const mm    = String(now.getMinutes()).padStart(2, '0');
    const ss    = String(now.getSeconds()).padStart(2, '0');

    document.getElementById('tgl_masuk').value = `${year}-${month}-${day}`;
    document.getElementById('jam_masuk').value = `${hh}:${mm}:${ss}`;
}, 1);

// ===== Fungsi Pengisi Detail Otomatis =====
const fillPasienDetails = async (no_rkm) => {
    if (!no_rkm) return;
    try {
        const pasien = await fetchJSON(`${BASE}/action/search_pasien.php?no=${encodeURIComponent(no_rkm)}`);
        if (pasien && pasien.no_rawat) {
            $('#no_rawat').value = pasien.no_rawat;
            $('#nm_pasien').value = pasien.nm_pasien;
            $('#no_rkm_medis').value = pasien.no_rkm_medis;
        } else {
            alert('Data registrasi untuk pasien ini tidak ditemukan.');
            $('#no_rawat').value = ''; $('#nm_pasien').value = '';
        }
    } catch (e) { console.error('Gagal mengisi detail pasien:', e); }
};

const fillKamarDetails = async (kodeKamar) => {
    if (!kodeKamar) return;
    try {
        const kamar = await fetchJSON(`${BASE}/action/get_kamar.php?kd=${kodeKamar}`);
        if (kamar) {
            $('#kd_kamar').value = kamar.kd_kamar;
            $('#kd_kamar_txt').value = kamar.kd_kamar;
            $('#kelas').value = kamar.kelas;
            $('#nm_bangsal').value = kamar.nm_bangsal;
            $('#stts_kamar').value = kamar.status;
            $('#trf_kamar').value = kamar.trf_kamar;
            hitungTotal();
        } else {
            alert('Detail kamar tidak ditemukan.');
        }
    } catch (error) { console.error('Error mengisi detail kamar:', error); }
};

// ===== Fungsi Kalkulasi =====
function hitungTotal() {
    const tarifKamar = parseFloat($('#trf_kamar').value || 0);
    const lamaInap = parseInt($('#lama').value || 1, 10);
    const totalBiaya = tarifKamar * lamaInap;
    const inputTotalBiaya = $('#ttl_biaya');
    if (inputTotalBiaya) {
        inputTotalBiaya.value = totalBiaya;
    }
}

// ===== Fungsi Autocomplete Umum =====
const autocomplete = (input, list, url) => {
    if (!input || !list) return;
    input.addEventListener('input', debounce(async (e) => {
        const q = e.target.value.trim();
        if (q.length < 2) { hideList(list); return; }
        try {
            const items = await fetchJSON(`${url}?q=${q}`);
            list.innerHTML = '';
            items.forEach(item => {
                const text = item.nm_pasien ? `${item.no_rkm_medis} - ${item.nm_pasien}` : `${item.kd_kamar} - ${item.nm_bangsal} (${item.status})`;
                const value = item.no_rkm_medis || item.kd_kamar;
                list.innerHTML += `<li data-value="${value}">${text}</li>`;
            });
            showList(list);
        } catch (error) { console.error('Autocomplete error:', error); }
    }));
    list.addEventListener('click', (e) => {
        if (e.target.tagName === 'LI') {
            const value = e.target.dataset.value;
            const text = e.target.textContent;
            input.value = text;
            hideList(list);
            if (input.id === 'no_rkm_medis') {
                fillPasienDetails(value);
            } else if (input.id === 'kd_kamar_txt') {
                fillKamarDetails(value);
            }
        }
    });
    document.addEventListener('click', e => {
        if (e.target !== input) { hideList(list); }
    });
};

// BAGIAN INISIALISASI & EVENT LISTENER
document.addEventListener('DOMContentLoaded', () => {
    autocomplete($('#no_rkm_medis'), $('#auto-rkm'), `${BASE}/action/search_pasien_list.php`);
    autocomplete($('#kd_kamar_txt'), $('#auto-kamar'), `${BASE}/action/search_kamar_list.php`);
    $('#lama')?.addEventListener('input', hitungTotal);
});

// ===== Submit Form =====
$('#form-inap')?.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try{
    const r = await fetch(`${BASE}/action/save_kamar_inap.php`, {method:'POST', body:fd});
    const data = await r.json();
    if(data.ok){
        alert('Tersimpan!');
        location.reload();
    }else{
        alert('Gagal: '+data.msg);
    }
    }catch(e){
    console.error(e); alert('Error server/jaringan');
    }
});