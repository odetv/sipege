/**
 * Helper untuk Penyusunan & Perhitungan Jadwal PM (Penerima Manfaat) Harian per Tanggal
 * Digunakan pada Formulir RAB SPPG agar kuota paket & hari pelayanan efektif 100% akurat.
 */

const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

/**
 * Cek apakah kelompok termasuk kategori Posyandu / 3B
 */
export function isPosyanduKelompok(kategori, nama) {
    const kat = String(kategori || '').toLowerCase();
    const nm = String(nama || '').toLowerCase();
    return (
        kat.includes('posyandu') ||
        kat.includes('3b') ||
        kat.includes('balita') ||
        kat.includes('bumil') ||
        kat.includes('busui') ||
        nm.includes('posyandu') ||
        nm.includes('poskesdes')
    );
}

/**
 * Hitung PK, PB, dan Total PM sebuah kelompok dari rincian sub-kategorinya
 */
export function calculateKelompokTotalsFromRincian(rincianList = [], statusMenerimaKelompok = true) {
    if (!statusMenerimaKelompok) {
        return {
            porsi_kecil: 0,
            porsi_besar: 0,
            total_pm: 0,
            active_rincian_count: 0,
            total_rincian_count: Array.isArray(rincianList) ? rincianList.length : 0,
        };
    }

    let pk = 0;
    let pb = 0;
    let activeCount = 0;

    (rincianList || []).forEach((r) => {
        if (r && r.is_menerima !== false) {
            activeCount += 1;
            const l = Math.max(0, parseInt(r.jumlah_laki_laki, 10) || 0);
            const p = Math.max(0, parseInt(r.jumlah_perempuan, 10) || 0);
            const subtotal = l + p;
            if (r.jenis_porsi === 'Porsi Kecil') {
                pk += subtotal;
            } else {
                pb += subtotal;
            }
        }
    });

    return {
        porsi_kecil: pk,
        porsi_besar: pb,
        total_pm: pk + pb,
        active_rincian_count: activeCount,
        total_rincian_count: Array.isArray(rincianList) ? rincianList.length : 0,
    };
}

/**
 * Normalisasi daftar kelompok sasaran beserta sub-kategori (rincian) untuk 1 hari/tanggal tertentu
 */
export function normalizeKelompoksForDate(masterKelompokList = [], existingKelompoks = null, defaultMenerima = true) {
    if (!Array.isArray(masterKelompokList) || masterKelompokList.length === 0) {
        if (Array.isArray(existingKelompoks) && existingKelompoks.length > 0) {
            return JSON.parse(JSON.stringify(existingKelompoks));
        }
        return [];
    }

    const existMap = {};
    if (Array.isArray(existingKelompoks)) {
        existingKelompoks.forEach((k) => {
            if (k && k.id !== undefined) {
                existMap[k.id] = k;
            }
        });
    }

    return masterKelompokList.map((m) => {
        const exist = existMap[m.id];
        const isPos = isPosyanduKelompok(m.kategori, m.nama_kelompok);
        const defPk = parseInt(m.total_porsi_kecil, 10) || 0;
        const defPb = parseInt(m.total_porsi_besar, 10) || 0;

        let statusMenerima = defaultMenerima;
        if (exist && exist.status_menerima !== undefined) {
            statusMenerima = Boolean(exist.status_menerima);
        }

        // Siapkan rincian sub-kategori
        let rincian = [];
        const existRincianMap = {};
        if (exist && Array.isArray(exist.rincian)) {
            exist.rincian.forEach((r) => {
                if (r && (r.sub_kategori || r.id)) {
                    const key = r.id ? `id_${r.id}` : `sk_${r.sub_kategori}`;
                    existRincianMap[key] = r;
                }
            });
        }

        if (Array.isArray(m.rincian) && m.rincian.length > 0) {
            rincian = m.rincian.map((r) => {
                const key = r.id ? `id_${r.id}` : `sk_${r.sub_kategori}`;
                const exR = existRincianMap[key];
                const defL = parseInt(r.jumlah_laki_laki, 10) || 0;
                const defP = parseInt(r.jumlah_perempuan, 10) || 0;

                let isMenerima = statusMenerima;
                let curL = defL;
                let curP = defP;

                if (exR) {
                    isMenerima = exR.is_menerima !== undefined ? Boolean(exR.is_menerima) : statusMenerima;
                    curL = exR.jumlah_laki_laki !== undefined ? (parseInt(exR.jumlah_laki_laki, 10) || 0) : defL;
                    curP = exR.jumlah_perempuan !== undefined ? (parseInt(exR.jumlah_perempuan, 10) || 0) : defP;
                }

                return {
                    id: r.id,
                    sub_kategori: r.sub_kategori || 'Sub-Kategori',
                    jenis_porsi: r.jenis_porsi || (isPos ? 'Porsi Kecil' : 'Porsi Besar'),
                    default_laki_laki: defL,
                    default_perempuan: defP,
                    jumlah_laki_laki: curL,
                    jumlah_perempuan: curP,
                    total: isMenerima ? (curL + curP) : 0,
                    is_menerima: isMenerima,
                };
            });
        } else {
            // Jika master belum memiliki rincian (misal kelompok baru/tanpa jenjang), buat rincian default
            const exR_pk = existRincianMap['sk_Porsi Kecil'];
            const exR_pb = existRincianMap['sk_Porsi Besar'];

            if (defPk > 0 || isPos) {
                const curL = exR_pk ? (parseInt(exR_pk.jumlah_laki_laki, 10) || 0) : Math.ceil(defPk / 2);
                const curP = exR_pk ? (parseInt(exR_pk.jumlah_perempuan, 10) || 0) : Math.floor(defPk / 2);
                const isM = exR_pk ? Boolean(exR_pk.is_menerima) : statusMenerima;
                rincian.push({
                    id: null,
                    sub_kategori: 'Porsi Kecil (Jenjang Awal / Balita)',
                    jenis_porsi: 'Porsi Kecil',
                    default_laki_laki: Math.ceil(defPk / 2),
                    default_perempuan: Math.floor(defPk / 2),
                    jumlah_laki_laki: curL,
                    jumlah_perempuan: curP,
                    total: isM ? (curL + curP) : 0,
                    is_menerima: isM,
                });
            }

            if (defPb > 0 || !isPos) {
                const curL = exR_pb ? (parseInt(exR_pb.jumlah_laki_laki, 10) || 0) : Math.ceil(defPb / 2);
                const curP = exR_pb ? (parseInt(exR_pb.jumlah_perempuan, 10) || 0) : Math.floor(defPb / 2);
                const isM = exR_pb ? Boolean(exR_pb.is_menerima) : statusMenerima;
                rincian.push({
                    id: null,
                    sub_kategori: 'Porsi Besar (Jenjang Lanjut / Umum)',
                    jenis_porsi: 'Porsi Besar',
                    default_laki_laki: Math.ceil(defPb / 2),
                    default_perempuan: Math.floor(defPb / 2),
                    jumlah_laki_laki: curL,
                    jumlah_perempuan: curP,
                    total: isM ? (curL + curP) : 0,
                    is_menerima: isM,
                });
            }
        }

        // Hitung total dari rincian
        const kTotals = calculateKelompokTotalsFromRincian(rincian, statusMenerima);

        let pk = kTotals.porsi_kecil;
        let pb = kTotals.porsi_besar;

        // Fallback jika rincian kosong
        if (rincian.length === 0) {
            pk = statusMenerima ? (exist?.porsi_kecil ?? defPk) : 0;
            pb = statusMenerima ? (exist?.porsi_besar ?? defPb) : 0;
        }

        return {
            id: m.id,
            nama_kelompok: m.nama_kelompok || 'Kelompok Sasaran',
            npsn: m.npsn || '',
            desa_kelurahan: m.desa_kelurahan || '',
            kecamatan: m.kecamatan || '',
            kategori: m.kategori || (isPos ? 'Posyandu' : 'Sekolah'),
            is_posyandu: isPos,
            status_menerima: statusMenerima,
            default_porsi_kecil: defPk,
            default_porsi_besar: defPb,
            porsi_kecil: pk,
            porsi_besar: pb,
            total_pm: pk + pb,
            rincian: rincian,
        };
    });
}

/**
 * Hitung akumulasi PK dan PB dari seluruh kelompok yang berstatus menerima
 */
export function calculateDateTotalsFromKelompoks(kelompoks = []) {
    let pk = 0;
    let pb = 0;
    let countMenerima = 0;
    let countTidakMenerima = 0;

    (kelompoks || []).forEach((k) => {
        if (k.status_menerima !== false) {
            countMenerima += 1;
            // Jika ada rincian, hitung dari rincian
            if (Array.isArray(k.rincian) && k.rincian.length > 0) {
                const kt = calculateKelompokTotalsFromRincian(k.rincian, true);
                pk += kt.porsi_kecil;
                pb += kt.porsi_besar;
            } else {
                pk += (parseInt(k.porsi_kecil, 10) || 0);
                pb += (parseInt(k.porsi_besar, 10) || 0);
            }
        } else {
            countTidakMenerima += 1;
        }
    });

    return {
        porsi_kecil: pk,
        porsi_besar: pb,
        total_pm: pk + pb,
        count_menerima: countMenerima,
        count_tidak_menerima: countTidakMenerima,
    };
}

/**
 * Terapkan preset ke daftar kelompok untuk suatu tanggal
 * Preset: 'all' | 'sekolah' | 'posyandu' | 'none' | 'reset'
 */
export function applyPresetToKelompoks(kelompoks = [], preset = 'all') {
    (kelompoks || []).forEach((k) => {
        if (preset === 'all') {
            k.status_menerima = true;
            (k.rincian || []).forEach((r) => {
                r.is_menerima = true;
                r.total = (parseInt(r.jumlah_laki_laki, 10) || 0) + (parseInt(r.jumlah_perempuan, 10) || 0);
            });
        } else if (preset === 'sekolah') {
            k.status_menerima = !k.is_posyandu;
            (k.rincian || []).forEach((r) => {
                r.is_menerima = !k.is_posyandu;
                r.total = r.is_menerima ? ((parseInt(r.jumlah_laki_laki, 10) || 0) + (parseInt(r.jumlah_perempuan, 10) || 0)) : 0;
            });
        } else if (preset === 'posyandu') {
            k.status_menerima = k.is_posyandu;
            (k.rincian || []).forEach((r) => {
                r.is_menerima = k.is_posyandu;
                r.total = r.is_menerima ? ((parseInt(r.jumlah_laki_laki, 10) || 0) + (parseInt(r.jumlah_perempuan, 10) || 0)) : 0;
            });
        } else if (preset === 'none') {
            k.status_menerima = false;
            (k.rincian || []).forEach((r) => {
                r.is_menerima = false;
                r.total = 0;
            });
        } else if (preset === 'reset') {
            k.status_menerima = true;
            (k.rincian || []).forEach((r) => {
                r.is_menerima = true;
                r.jumlah_laki_laki = r.default_laki_laki ?? r.jumlah_laki_laki;
                r.jumlah_perempuan = r.default_perempuan ?? r.jumlah_perempuan;
                r.total = (parseInt(r.jumlah_laki_laki, 10) || 0) + (parseInt(r.jumlah_perempuan, 10) || 0);
            });
        }

        // Update PK & PB kelompok
        const kt = calculateKelompokTotalsFromRincian(k.rincian, k.status_menerima);
        k.porsi_kecil = kt.porsi_kecil;
        k.porsi_besar = kt.porsi_besar;
        k.total_pm = kt.total_pm;
    });

    return calculateDateTotalsFromKelompoks(kelompoks);
}

/**
 * Menghasilkan daftar tanggal dalam 1 bulan (1 s/d 28/29/30/31) dengan status aktif/libur
 * dan kuota PM porsi kecil & besar beserta rincian titik sasaran (kelompoks).
 */
export function generateDefaultJadwalPmHarian(year, month, defaultPk = 0, defaultPb = 0, existingList = null, masterKelompokList = []) {
    const y = parseInt(year, 10) || new Date().getFullYear();
    const m = parseInt(month, 10) || (new Date().getMonth() + 1);
    const hasMaster = Array.isArray(masterKelompokList) && masterKelompokList.length > 0;

    let fallbackPk = parseInt(defaultPk, 10) || 0;
    let fallbackPb = parseInt(defaultPb, 10) || 0;

    if (hasMaster && fallbackPk === 0 && fallbackPb === 0) {
        masterKelompokList.forEach((k) => {
            fallbackPk += (parseInt(k.total_porsi_kecil, 10) || 0);
            fallbackPb += (parseInt(k.total_porsi_besar, 10) || 0);
        });
    }

    const daysInMonth = new Date(y, m, 0).getDate();

    const existingMap = {};
    if (Array.isArray(existingList)) {
        existingList.forEach((it) => {
            if (it && it.tanggal) {
                existingMap[it.tanggal] = it;
            } else if (it && it.hari_ke) {
                existingMap[it.hari_ke] = it;
            }
        });
    }

    const result = [];
    for (let d = 1; d <= daysInMonth; d++) {
        const dateObj = new Date(y, m - 1, d);
        const dayOfWeek = dateObj.getDay(); // 0 = Minggu, 6 = Sabtu
        const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
        const dayName = NAMA_HARI[dayOfWeek];
        const dateStr = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

        const exist = existingMap[dateStr] || existingMap[d];

        let isAktif = !isWeekend;
        let ket = isWeekend ? (dayOfWeek === 0 ? 'Hari Libur (Minggu)' : 'Hari Libur (Sabtu)') : 'Pelayanan KBM Efektif';
        let existingKelompoks = null;

        if (exist) {
            isAktif = exist.is_aktif !== undefined ? Boolean(exist.is_aktif) : !isWeekend;
            ket = exist.keterangan || (isAktif ? 'Pelayanan KBM Efektif' : 'Hari Libur');
            existingKelompoks = exist.kelompoks || null;
        }

        let kelompoks = [];
        let pk = 0;
        let pb = 0;

        if (hasMaster) {
            kelompoks = normalizeKelompoksForDate(masterKelompokList, existingKelompoks, isAktif);
            const totals = calculateDateTotalsFromKelompoks(kelompoks);
            pk = totals.porsi_kecil;
            pb = totals.porsi_besar;

            if (exist && (!exist.kelompoks || exist.kelompoks.length === 0)) {
                if (exist.porsi_kecil !== undefined) pk = parseInt(exist.porsi_kecil, 10) || 0;
                if (exist.porsi_besar !== undefined) pb = parseInt(exist.porsi_besar, 10) || 0;
            }
        } else {
            if (exist) {
                pk = exist.porsi_kecil !== undefined ? (parseInt(exist.porsi_kecil, 10) || 0) : (isAktif ? fallbackPk : 0);
                pb = exist.porsi_besar !== undefined ? (parseInt(exist.porsi_besar, 10) || 0) : (isAktif ? fallbackPb : 0);
                kelompoks = exist.kelompoks || [];
            } else {
                pk = isAktif ? fallbackPk : 0;
                pb = isAktif ? fallbackPb : 0;
                kelompoks = [];
            }
        }

        result.push({
            tanggal: dateStr,
            hari_ke: d,
            nama_hari: dayName,
            is_weekend: isWeekend,
            is_aktif: isAktif,
            porsi_kecil: pk,
            porsi_besar: pb,
            total_pm: pk + pb,
            keterangan: ket,
            kelompoks: kelompoks,
        });
    }

    return result;
}

/**
 * Menghitung ringkasan akumulasi dan rerata dari jadwal PM harian
 */
export function calculateJadwalPmSummary(jadwalList = []) {
    if (!Array.isArray(jadwalList) || jadwalList.length === 0) {
        return {
            totalHari: 0,
            hariEfektif: 0,
            hariLibur: 0,
            totalPorsiKecil: 0,
            totalPorsiBesar: 0,
            grandTotalPm: 0,
            rerataPorsiKecil: 0,
            rerataPorsiBesar: 0,
            rerataTotalPm: 0,
        };
    }

    let hariEfektif = 0;
    let hariLibur = 0;
    let totalPorsiKecil = 0;
    let totalPorsiBesar = 0;

    jadwalList.forEach((it) => {
        if (it.is_aktif) {
            hariEfektif += 1;
            totalPorsiKecil += (parseInt(it.porsi_kecil, 10) || 0);
            totalPorsiBesar += (parseInt(it.porsi_besar, 10) || 0);
        } else {
            hariLibur += 1;
        }
    });

    const grandTotalPm = totalPorsiKecil + totalPorsiBesar;
    const rerataPorsiKecil = hariEfektif > 0 ? Math.round(totalPorsiKecil / hariEfektif) : 0;
    const rerataPorsiBesar = hariEfektif > 0 ? Math.round(totalPorsiBesar / hariEfektif) : 0;
    const rerataTotalPm = rerataPorsiKecil + rerataPorsiBesar;

    return {
        totalHari: jadwalList.length,
        hariEfektif,
        hariLibur,
        totalPorsiKecil,
        totalPorsiBesar,
        grandTotalPm,
        rerataPorsiKecil,
        rerataPorsiBesar,
        rerataTotalPm,
    };
}
