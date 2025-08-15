const BASE = '..';

document.addEventListener('DOMContentLoaded', function () {
  // Auto isi Tanggal & Jam tiap detik
  const el = document.getElementById('tgl_jam');
  if (!el) return;

  const tick = () => {
    const now = new Date();
    const YYYY = now.getFullYear();
    const MM = String(now.getMonth() + 1).padStart(2, '0');
    const DD = String(now.getDate()).padStart(2, '0');
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');
    el.value = `${YYYY}-${MM}-${DD}T${hh}:${mm}:${ss}`;
  };
  tick();
  setInterval(tick, 500);
});

// utils
function debounce(fn, d = 300) {
  let t;
  return (...a) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...a), d);
  };
}
async function fetchJSON(url) {
  try {
    const r = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!r.ok) throw new Error('Fetch error');
    return r.json();
  } catch (e) {
    console.error('fetchJSON error:', e);
    return [];
  }
}
function show(el) { el.style.display = 'block'; }
function hide(el) { el.style.display = 'none'; el.innerHTML = ''; }

// === Autocomplete No. Rawat ===
const inpRawat = document.getElementById('no_rawat');
const listRawat = document.getElementById('auto-rawat');
const inpRM = document.getElementById('no_rkm_medis');
const inpNama = document.getElementById('nm_pasien');

if (inpRawat && listRawat) {
  inpRawat.addEventListener('input', debounce(async () => {
    const q = inpRawat.value.trim();
    if (q.length < 2) { hide(listRawat); return; }
    const data = await fetchJSON(`${BASE}/action/search_rawat_list.php?q=${encodeURIComponent(q)}`);
    if (!data.length) { hide(listRawat); return; }
    listRawat.innerHTML = data.map(d => `
      <li data-no="${d.no_rawat}" data-rm="${d.no_rkm_medis}" data-nm="${d.nm_pasien}">
        ${d.no_rawat} — ${d.no_rkm_medis} — ${d.nm_pasien}
      </li>`).join('');
    show(listRawat);
  }));

  listRawat.addEventListener('click', e => {
    const li = e.target.closest('li'); if (!li) return;
    inpRawat.value = li.dataset.no;
    if (inpRM)   inpRM.value   = li.dataset.rm;
    if (inpNama) inpNama.value = li.dataset.nm;
    hide(listRawat);
  });
}

// === Autocomplete NIP Petugas ===
const inpStaff = document.getElementById('nip_petugas');
const listStaff = document.getElementById('auto-staff');
const inpJbtn = document.getElementById('nm_jbtn');

if (inpStaff && listStaff) {
  inpStaff.addEventListener('input', debounce(async () => {
    const q = inpStaff.value.trim();
    if (q.length < 2) { hide(listStaff); return; }
    const data = await fetchJSON(`${BASE}/action/search_staff_list.php?q=${encodeURIComponent(q)}`);
    if (!data.length) { hide(listStaff); return; }
    listStaff.innerHTML = data.map(d => {
      const jabatan = d.nm_jbtn || d.nm_sps || d.jabatan || '';
      return `<li data-nip="${d.nip}" data-jbtn="${jabatan}">
        ${d.nip} — ${d.nama} <small>(${jabatan})</small></li>`;
    }).join('');
    show(listStaff);
  }));
  listStaff.addEventListener('click', e => {
    const li = e.target.closest('li'); if (!li) return;
    inpStaff.value = li.dataset.nip;
    if (inpJbtn) inpJbtn.value = li.dataset.jbtn;
    hide(listStaff);
  });
}

// === Validasi submit (tensi & SpO2 wajib) ===
const formAsm = document.getElementById('form-asesmen');
if (formAsm) {
  formAsm.addEventListener('submit', function (e) {
    const tensi = (document.getElementById('tensi') || {}).value?.trim() || '';
    const spo2  = (document.getElementById('spo2')  || {}).value?.trim() || '';
    if (tensi === '' || spo2 === '') {
      e.preventDefault();
      if (tensi === '') {
        document.getElementById('tensi').focus();
        showTooltip('tensi', 'Tensi wajib diisi!');
      } else {
        document.getElementById('spo2').focus();
        showTooltip('spo2', 'SpO2 wajib diisi!');
      }
      return false;
    }
    if (!tensi.includes('/')) {
      e.preventDefault();
      document.getElementById('tensi').focus();
      showTooltip('tensi', 'Format tensi harus seperti 120/80');
      return false;
    }
  });

  const msg = new URLSearchParams(location.search).get('msg');
  if (msg === 'saved') {
    window.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type !== 'hidden' && !el.readOnly && !el.disabled && el.name !== 'no_rawat') {
          if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
        }
      });
    });
  }
}

function showTooltip(fieldId, message) {
  const input = document.getElementById(fieldId);
  if (!input) return;
  const old = document.getElementById('tooltip-' + fieldId);
  if (old) old.remove();
  const tip = document.createElement('div');
  tip.id = 'tooltip-' + fieldId;
  tip.innerHTML = `<span style="color:#f39c12;">&#9888;</span> ${message}`;
  tip.style.position = 'absolute';
  tip.style.background = '#fff';
  tip.style.border = '1px solid #ccc';
  tip.style.padding = '5px 10px';
  tip.style.borderRadius = '5px';
  tip.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
  tip.style.zIndex = '1000';
  const rect = input.getBoundingClientRect();
  tip.style.left = rect.left + window.scrollX + 'px';
  tip.style.top  = rect.top  + window.scrollY - 40 + 'px';
  document.body.appendChild(tip);
  setTimeout(() => tip.remove(), 2000);
}

// === DataTables untuk Riwayat + pagination AJAX ===
function initRiwayatTable() {
  const $tbl = $('#tabel-riwayat');
  if (!$tbl.length) return;
  if ($.fn.DataTable.isDataTable($tbl)) $tbl.DataTable().destroy();
  const table = $tbl.DataTable({
    pageLength: 5,
    dom: 't',
    ordering: false,
    language: {
      emptyTable: "Belum ada data asesmen.",
      zeroRecords: "Tidak ada data yang cocok."
    }
  });
  $tbl.css('visibility', 'visible');
  $('#cari_riwayat').off('keyup').on('keyup', function () { table.search(this.value).draw(); });
}
$(document).ready(function () { initRiwayatTable(); });

// Helper: ambil filter saat ini (no_rawat/no_rkm_medis) dari URL/DOM
function currentFilter() {
  const p = new URLSearchParams(location.search);
  const byUrlRawat = p.get('no_rawat') || '';
  const byUrlRM    = p.get('no_rkm_medis') || '';
  const byDomRawat = (document.getElementById('no_rawat') || {}).value || '';
  const byDomRM    = (document.getElementById('no_rkm_medis') || {}).value || '';
  return {
    no_rawat: byUrlRawat || byDomRawat || '',
    no_rkm_medis: byUrlRM || byDomRM || '',
  };
}

function loadRiwayat(page){
  const f = currentFilter();
  $.get('asesmen.php', {
    ajax: 1,
    page,
    no_rawat: f.no_rawat || '',
    no_rkm_medis: f.no_rkm_medis || ''
  }, function(html){
    $('#riwayat-container').html(html);
    initRiwayatTable();
  });
}

$('#riwayat-container').on('click', '.pagination a.page-link', function(e){
  e.preventDefault();
  const page = new URL(this.href, location.href).searchParams.get('page') || 1;
  loadRiwayat(page);
});
