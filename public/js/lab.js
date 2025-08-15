
// lab.js — script for permintaan_lab.php

document.addEventListener('DOMContentLoaded', function () {
  // ======================
  // Auto-update Tanggal & Jam
  // ======================
  function pad(n){ return (n < 10 ? '0' + n : '' + n); }
  function nowStrings(){
    var now = new Date();
    var dmy = pad(now.getDate()) + '-' + pad(now.getMonth() + 1) + '-' + now.getFullYear();
    var hms = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    return { dmy: dmy, hms: hms };
  }

  var tglEl = document.getElementById('tanggal') || document.querySelector('input[name="tgl_permintaan"]');
  var jamEl = document.getElementById('jam') || document.querySelector('input[name="jam_permintaan"]');

  function tick(){
    var n = nowStrings();
    if (tglEl && !tglEl.disabled) { tglEl.value = n.dmy; }
    if (jamEl && !jamEl.disabled) { jamEl.value = n.hms; }
  }
  // start immediately, then every 500ms
  tick();
  setInterval(tick, 500);

  // ======================
  // Autocomplete Pasien
  // ======================
  const pasienSearchInput = document.getElementById('pasien-search');
  const suggestionsContainer = document.getElementById('pasien-suggestions');
  const noRawatInput = document.getElementById('no-rawat');
  const noRmInput = document.getElementById('no-rkm-medis');
  const poliInput = document.getElementById('poli');
  const dokterSelect = document.getElementById('dokter');
  const diagnosaTextarea = document.getElementById('diagnosa');

  const debounce = (func, delay) => {
    let timeout;
    return (...args) => { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), delay); };
  };

  const searchPatients = async () => {
    if (!pasienSearchInput) return;
    const query = pasienSearchInput.value;
    if (query.length < 2) {
      if (suggestionsContainer) suggestionsContainer.style.display = 'none';
      return;
    }
    try {
      // ==== PATH URL SUDAH BENAR MENGARAH KE FOLDER ACTION ====
      const apiUrl = `/magang/action/search_pasien_lab.php?q=${encodeURIComponent(query)}`;
      const response = await fetch(apiUrl);
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

      const data = await response.json();
      if (data && data.error) throw new Error(data.message || 'Unknown error');

      if (!suggestionsContainer) return;
      suggestionsContainer.innerHTML = '';

      if (Array.isArray(data) && data.length > 0) {
        data.forEach(pasien => {
          const li = document.createElement('li');
          li.textContent = `${pasien.no_rkm_medis} - ${pasien.nm_pasien}`;
          li.dataset.noRm = pasien.no_rkm_medis;
          li.dataset.nama = pasien.nm_pasien;
          suggestionsContainer.appendChild(li);
        });
      } else {
        suggestionsContainer.innerHTML = '<li style="color: #6c757d; cursor: default;">Pasien rawat inap tidak ditemukan</li>';
      }
      suggestionsContainer.style.display = 'block';
    } catch (error) {
      console.error('Error fetching suggestions:', error);
      if (suggestionsContainer) {
        suggestionsContainer.innerHTML = `<li style="color: red; cursor: default;">Gagal memuat data. Cek console (F12).</li>`;
        suggestionsContainer.style.display = 'block';
      }
    }
  };

  if (pasienSearchInput) {
    pasienSearchInput.addEventListener('input', debounce(searchPatients, 300));
  }

  if (suggestionsContainer) {
    suggestionsContainer.addEventListener('click', async (e) => {
      const suggestion = e.target.closest('li');
      if (suggestion && suggestion.dataset.noRm) {
        const noRm = suggestion.dataset.noRm;
        const nama = suggestion.dataset.nama;
        if (pasienSearchInput) pasienSearchInput.value = `${noRm} - ${nama}`;
        if (noRmInput) noRmInput.value = noRm;
        suggestionsContainer.style.display = 'none';

        try {
          // ==== PATH URL SUDAH BENAR MENGARAH KE FOLDER ACTION ====
          const detailApiUrl = `/magang/action/get_detail_pasien_lab.php?no_rkm_medis=${noRm}`;
          const response = await fetch(detailApiUrl);
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          const detail = await response.json();
          if (detail && detail.error) throw new Error(detail.message || 'Unknown error');

          if (detail && detail.no_rawat) {
            if (noRawatInput) noRawatInput.value = detail.no_rawat;
            if (poliInput) poliInput.value = detail.lokasi;
            if (diagnosaTextarea) diagnosaTextarea.value = detail.diagnosa;
            if (dokterSelect && detail.kd_dokter) {
              dokterSelect.value = detail.kd_dokter;
            }
          } else {
            alert('Detail data rawat inap untuk pasien ini tidak ditemukan.');
          }
        } catch (error) {
          console.error('Error fetching details:', error);
          alert(`Gagal memuat detail pasien. Cek console (F12).\nError: ${error.message}`);
        }
      }
    });
  }

  // Tutup dropdown saat klik di luar
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-wrapper') && suggestionsContainer) {
      suggestionsContainer.style.display = 'none';
    }
  });
});
