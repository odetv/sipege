import { generateKopHtml, getActiveKopConfig } from "@/Services/kopDokumenHelper";
import { 
    LOGO_BGN_BASE64, 
    LOGO_BGN_RAW_BASE64, 
    LOGO_YAYASAN_BASE64, 
    LOGO_YAYASAN_RAW_BASE64 
} from "@/Services/logoBase64Helper";
import ExcelJS from "exceljs";
import {
    Document,
    Packer,
    Paragraph,
    Table,
    TableRow,
    TableCell,
    TextRun,
    ImageRun,
    AlignmentType,
    WidthType,
    BorderStyle,
    HeadingLevel,
    PageBreak,
    ShadingType,
    VerticalAlign,
    PageOrientation,
} from "docx";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import html2canvas from "html2canvas";

// Helper Format Angka & Tanggal
export function formatRupiahNum(val) {
    if (!val && val !== 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(Math.round(val));
}

export function formatTanggalIndoFull(tgl) {
    if (!tgl) return '-';
    try {
        const d = new Date(tgl);
        if (isNaN(d.getTime())) return tgl;
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    } catch {
        return tgl;
    }
}

// -------------------------------------------------------------
// CENTRAL DATA BUILDER UNTUK DOKUMEN WORK ORDER & PERENCANAAN MENU
// -------------------------------------------------------------
export function buildWorkOrderFullExportData(wo) {
    const raw = wo.raw || wo;
    const noWO = wo.id || raw.nomor_wo || raw.id || '-';
    const namaMenu = wo.nama || raw.nama_menu || raw.nama || '-';
    const statusMenu = wo.status_wo || raw.status || 'Draft';
    
    let rawDb = raw.database_pangan || wo.database_pangan || '';
    let dbPangan = 'Kemenkes';
    if (rawDb) {
        const s = String(rawDb).toLowerCase().trim();
        if (s === 'csv' || s === 'tkpi2020' || s.includes('kemenkes')) {
            dbPangan = 'Kemenkes';
        } else if (s === 'fta' || s.includes('nutri')) {
            dbPangan = 'Nutri Survey';
        } else {
            dbPangan = rawDb;
        }
    }

    const tglDist = formatTanggalIndoFull(wo.tanggal || raw.tanggal_distribusi);
    const kopConfig = getActiveKopConfig();
    const sppgName = kopConfig?.nama_instansi || 'SPPG BULELENG SUKASADA TEGALLINGGAH';

    // 1. Sub Menus & Rincian
    const subMenuMap = {};
    const subMenusList = [];
    const rawSubMenu = raw.sub_menu || {};
    for (let i = 1; i <= 5; i++) {
        const key = `sub_menu_${i}`;
        const val = (wo.sub_menus && wo.sub_menus[i - 1]) || raw[key] || rawSubMenu[key] || '';
        if (val) {
            subMenuMap[key] = val;
            subMenusList.push({ key, label: `Sub Menu ${i}`, nama: val });
        }
    }
    const items = wo.items || raw.items || [];
    items.forEach(it => {
        if (it.sub_menu_key && it.nama_sub_menu && !subMenuMap[it.sub_menu_key]) {
            subMenuMap[it.sub_menu_key] = it.nama_sub_menu;
            const idx = it.sub_menu_key.replace('sub_menu_', '');
            subMenusList.push({ key: it.sub_menu_key, label: `Sub Menu ${idx}`, nama: it.nama_sub_menu });
        }
    });

    const rincianSubMenuStr = subMenusList.length > 0 
        ? subMenusList.map((sm, idx) => `${idx + 1}. ${sm.nama}`).join(', ') 
        : '-';
    const rincianSubMenuMultiline = subMenusList.length > 0 
        ? subMenusList.map((sm, idx) => `${idx + 1}. ${sm.nama}`).join('\n') 
        : '-';

    // 2. Deteksi Alergi Terdampak Berdasarkan Menu Saat Ini (Bahan Alergi / Substitusi)
    const menuAllergenTypes = new Set();
    items.forEach(it => {
        if (it.tipe_porsi === 'alergi' && it.jenis_alergi) {
            menuAllergenTypes.add(it.jenis_alergi.trim());
        }
    });
    if (raw.sub_menu_alergi) {
        if (Array.isArray(raw.sub_menu_alergi)) {
            raw.sub_menu_alergi.forEach(a => {
                const j = typeof a === 'string' ? a : (a?.jenis_alergi || '');
                if (j) menuAllergenTypes.add(j.trim());
            });
        } else if (typeof raw.sub_menu_alergi === 'object') {
            Object.values(raw.sub_menu_alergi).forEach(val => {
                if (Array.isArray(val)) {
                    val.forEach(a => {
                        const j = typeof a === 'string' ? a : (a?.jenis_alergi || '');
                        if (j) menuAllergenTypes.add(j.trim());
                    });
                }
            });
        }
    }

    // Helper filter alergi yang HANYA terdampak menu ini
    function filterKpmImpactedAllergens(kpmDetailAlergi) {
        if (!Array.isArray(kpmDetailAlergi) || kpmDetailAlergi.length === 0) return '-';
        if (menuAllergenTypes.size === 0) return '-';

        const matched = [];
        kpmDetailAlergi.forEach(al => {
            const rawJenis = (al.jenis_alergi || (typeof al === 'string' ? al : '')).trim();
            if (!rawJenis) return;

            for (const menuAl of menuAllergenTypes) {
                const mClean = menuAl.toLowerCase().trim();
                const rClean = rawJenis.toLowerCase().trim();
                if (rClean === mClean || rClean.includes(mClean) || mClean.includes(rClean)) {
                    const pk = Number(al.porsi_kecil || 0);
                    const pb = Number(al.porsi_besar || 0);
                    const tot = pk + pb;
                    if (tot > 0) {
                        matched.push(`${rawJenis}: ${tot} PM (${pk} PK, ${pb} PB)`);
                    } else if (al.total || al.jumlah) {
                        matched.push(`${rawJenis}: ${al.total || al.jumlah} PM`);
                    }
                    break;
                }
            }
        });

        return matched.length > 0 ? matched.join('; ') : '-';
    }

    // 3. Kelompoks Penerima Manfaat
    const allKelompoks = wo.kelompoks || raw.kelompoks || [];
    const kpmMenerima = [];
    const kpmTidakMenerima = [];

    allKelompoks.forEach(k => {
        const isMenerima = k.is_menerima === true || k.is_menerima === 1 || k.status_menerima === true || (k.is_menerima !== false && k.is_menerima !== 0 && k.status_menerima !== false);
        const kelData = k.kelompok || k;
        const detailAlergi = k.detail_alergi || k.keterangan_alergi || kelData.detail_alergi || kelData.keterangan_alergi || [];
        const item = {
            id: k.id,
            nama: k.nama_kelompok || kelData.nama_kelompok || kelData.nama || '-',
            kategori: k.kategori || kelData.kategori || '-',
            pk: Number(k.porsi_kecil !== undefined ? k.porsi_kecil : (kelData.total_porsi_kecil || 0)) || 0,
            pb: Number(k.porsi_besar !== undefined ? k.porsi_besar : (kelData.total_porsi_besar || 0)) || 0,
            total: Number(k.total_penerima !== undefined ? k.total_penerima : (kelData.total_penerima || 0)) || 0,
            detail_alergi: detailAlergi,
            keterangan_alergi_terdampak: filterKpmImpactedAllergens(detailAlergi),
        };
        if (item.total === 0 && (item.pk + item.pb) > 0) item.total = item.pk + item.pb;

        if (isMenerima) {
            kpmMenerima.push(item);
        } else {
            kpmTidakMenerima.push(item);
        }
    });

    // Rekap Total KPM Menerima
    const rekapMenerima = kpmMenerima.reduce((acc, k) => {
        acc.pk += k.pk;
        acc.pb += k.pb;
        acc.total += k.total;
        return acc;
    }, { pk: 0, pb: 0, total: 0 });

    // Rekap Total KPM Tidak Menerima
    const rekapTidakMenerima = kpmTidakMenerima.reduce((acc, k) => {
        acc.pk += k.pk;
        acc.pb += k.pb;
        acc.total += k.total;
        return acc;
    }, { pk: 0, pb: 0, total: 0 });

    // Total Sasaran PM
    const totalPK = rekapMenerima.pk || Number(wo.porsi_pk || raw.total_pk || 0);
    const totalPB = rekapMenerima.pb || Number(wo.porsi_pb || raw.total_pb || 0);
    const totalPM = rekapMenerima.total || Number(wo.total_porsi || raw.total_pm || (totalPK + totalPB));

    // 4. Ringkasan Alergi (Tabel 3) - Fokus pada Alergi yang ada di Menu Ini
    const allergenMap = {};
    kpmMenerima.forEach(k => {
        if (Array.isArray(k.detail_alergi)) {
            k.detail_alergi.forEach(al => {
                const jenis = (al.jenis_alergi || (typeof al === 'string' ? al : '')).trim();
                if (!jenis) return;
                
                const isRelevant = menuAllergenTypes.size === 0 || Array.from(menuAllergenTypes).some(ma => {
                    const mClean = ma.toLowerCase();
                    const jClean = jenis.toLowerCase();
                    return jClean === mClean || jClean.includes(mClean) || mClean.includes(jClean);
                });

                if (isRelevant) {
                    if (!allergenMap[jenis]) {
                        allergenMap[jenis] = { jenis, pk: 0, pb: 0, total: 0 };
                    }
                    const apk = Number(al.porsi_kecil) || 0;
                    const apb = Number(al.porsi_besar) || 0;
                    allergenMap[jenis].pk += apk;
                    allergenMap[jenis].pb += apb;
                    allergenMap[jenis].total += (apk + apb);
                }
            });
        }
    });

    const activeAllergens = Object.values(allergenMap).filter(a => a.total > 0);

    // Hitung porsi normal
    let totalAllergenPK = 0;
    let totalAllergenPB = 0;
    activeAllergens.forEach(a => {
        totalAllergenPK += a.pk;
        totalAllergenPB += a.pb;
    });

    const normalPK = Math.max(0, totalPK - totalAllergenPK);
    const normalPB = Math.max(0, totalPB - totalAllergenPB);
    const normalTotal = normalPK + normalPB;

    // 5. Pagu Anggaran (Tabel 4)
    const paguRatePK = 8000;
    const paguRatePB = 10000;
    const paguNominalPK = totalPK * paguRatePK;
    const paguNominalPB = totalPB * paguRatePB;
    const paguTotal = paguNominalPK + paguNominalPB;

    // 6. Formulasi Bahan Pangan (Sheet 2: A)
    let totalNetKg = 0;
    let totalGrossKg = 0;
    let totalBelanja = 0;

    const formattedItems = items.map((it, idx) => {
        const subName = it.nama_sub_menu || subMenuMap[it.sub_menu_key] || (it.sub_menu_key ? `Sub Menu ${it.sub_menu_key.replace('sub_menu_', '')}` : '-');
        const bahanMaster = it.nama || '-';
        const namaPO = it.nama_po || it.nama || '-';
        const satuan = it.satuan || 'Kg';
        const jenis = it.jenis === 'operasional' ? 'Operasional' : 'Bahan Baku';
        
        const isAlergi = it.tipe_porsi === 'alergi';
        const peruntukan = isAlergi ? `Alergi (${it.jenis_alergi || 'Khusus'})` : 'Normal';

        const gramPK = Number(it.gram_pk !== undefined ? it.gram_pk : (it.gram_bersih_pk || 0)) || 0;
        const gramPB = Number(it.gram_pb !== undefined ? it.gram_pb : (it.gram_bersih_pb || 0)) || 0;

        const bdd = Number(it.bdd !== undefined ? it.bdd : 100) || 100;
        const buffer = Number(it.buffer !== undefined ? it.buffer : 0) || 0;

        const grossKgPK = Number(it.gross_kg_pk) || 0;
        const grossKgPB = Number(it.gross_kg_pb) || 0;
        const totalGross = Number(it.total_gross_kg !== undefined ? it.total_gross_kg : (grossKgPK + grossKgPB)) || 0;

        const targetPK = isAlergi ? (allergenMap[it.jenis_alergi]?.pk || 1) : totalPK;
        const targetPB = isAlergi ? (allergenMap[it.jenis_alergi]?.pb || 1) : totalPB;
        const netKg = Number(it.total_net_kg !== undefined ? it.total_net_kg : ((gramPK * targetPK + gramPB * targetPB) / 1000)) || 0;

        const harga = Number(it.harga_master || it.harga_aktual || it.harga || 0);
        const subtotal = Number(it.subtotal_master !== undefined ? it.subtotal_master : (it.subtotal !== undefined ? it.subtotal : Math.round(totalGross * harga))) || 0;

        totalNetKg += netKg;
        totalGrossKg += totalGross;
        totalBelanja += subtotal;

        // Food cost portion calculation
        const bddFactor = bdd > 0 ? (bdd / 100) : 1;
        const bufferFactor = 1 + (buffer / 100);
        const costPK = jenis === 'Operasional' ? 0 : Math.round(((gramPK / bddFactor) * bufferFactor / 1000) * harga * 100) / 100;
        const costPB = jenis === 'Operasional' ? 0 : Math.round(((gramPB / bddFactor) * bufferFactor / 1000) * harga * 100) / 100;

        return {
            no: idx + 1,
            sub_menu_key: it.sub_menu_key,
            sub_menu: subName,
            bahan_master: bahanMaster,
            nama_po: namaPO,
            satuan,
            jenis,
            peruntukan,
            tipe_porsi: it.tipe_porsi,
            jenis_alergi: it.jenis_alergi,
            gram_pk: gramPK,
            gram_pb: gramPB,
            gross_kg_pk: grossKgPK,
            gross_kg_pb: grossKgPB,
            bdd,
            buffer,
            total_gross: totalGross,
            net_kg: netKg,
            harga,
            subtotal,
            keterangan: it.keterangan || '-',
            cost_pk: costPK,
            cost_pb: costPB,
            nutrisi_pk: it.nutrisi_pk || it.nutrisiPK || {},
            nutrisi_pb: it.nutrisi_pb || it.nutrisiPB || {},
        };
    });

    const selisihPagu = paguTotal - totalBelanja;

    // 7. Food Cost per Sub Menu (Normal & Alergi)
    const normalItems = formattedItems.filter(it => it.tipe_porsi !== 'alergi');
    
    const foodCostNormalMap = {};
    subMenusList.forEach(sm => {
        foodCostNormalMap[sm.key] = {
            key: sm.key,
            sub_menu: sm.nama,
            count: 0,
            cost_pk: 0,
            cost_pb: 0,
        };
    });
    normalItems.forEach(it => {
        const k = it.sub_menu_key || 'sub_menu_1';
        if (!foodCostNormalMap[k]) {
            foodCostNormalMap[k] = { key: k, sub_menu: it.sub_menu, count: 0, cost_pk: 0, cost_pb: 0 };
        }
        foodCostNormalMap[k].count++;
        foodCostNormalMap[k].cost_pk += it.cost_pk;
        foodCostNormalMap[k].cost_pb += it.cost_pb;
    });

    const foodCostNormalList = Object.values(foodCostNormalMap);
    const totalFcPKNormal = foodCostNormalList.reduce((acc, row) => acc + row.cost_pk, 0);
    const totalFcPBNormal = foodCostNormalList.reduce((acc, row) => acc + row.cost_pb, 0);

    foodCostNormalList.forEach(row => {
        row.percent_pk = totalFcPKNormal > 0 ? (row.cost_pk / totalFcPKNormal) * 100 : 0;
        row.percent_pb = totalFcPBNormal > 0 ? (row.cost_pb / totalFcPBNormal) * 100 : 0;
    });

    // Food Cost for Allergen Variants in this menu
    const allergenFoodCostTables = [];
    menuAllergenTypes.forEach(jenis => {
        const subItems = formattedItems.filter(it => it.tipe_porsi === 'alergi' && it.jenis_alergi === jenis);
        const alFoodCostMap = {};
        subMenusList.forEach(sm => {
            alFoodCostMap[sm.key] = {
                key: sm.key,
                sub_menu: sm.nama,
                count: 0,
                cost_pk: 0,
                cost_pb: 0,
            };
        });

        formattedItems.forEach(it => {
            const k = it.sub_menu_key || 'sub_menu_1';
            if (!alFoodCostMap[k]) {
                alFoodCostMap[k] = { key: k, sub_menu: it.sub_menu, count: 0, cost_pk: 0, cost_pb: 0 };
            }

            if (it.tipe_porsi === 'alergi') {
                if (it.jenis_alergi === jenis) {
                    alFoodCostMap[k].sub_menu = `${it.sub_menu} (${it.nama_po})`;
                    alFoodCostMap[k].count++;
                    alFoodCostMap[k].cost_pk += it.cost_pk;
                    alFoodCostMap[k].cost_pb += it.cost_pb;
                }
            } else {
                const hasReplacementInSub = subItems.some(si => si.sub_menu_key === it.sub_menu_key);
                if (!hasReplacementInSub) {
                    alFoodCostMap[k].count++;
                    alFoodCostMap[k].cost_pk += it.cost_pk;
                    alFoodCostMap[k].cost_pb += it.cost_pb;
                }
            }
        });

        const list = Object.values(alFoodCostMap);
        const totalPKAl = list.reduce((acc, row) => acc + row.cost_pk, 0);
        const totalPBAl = list.reduce((acc, row) => acc + row.cost_pb, 0);

        list.forEach(row => {
            row.percent_pk = totalPKAl > 0 ? (row.cost_pk / totalPKAl) * 100 : 0;
            row.percent_pb = totalPBAl > 0 ? (row.cost_pb / totalPBAl) * 100 : 0;
        });

        allergenFoodCostTables.push({
            jenis_alergi: jenis,
            list,
            total_pk: totalPKAl,
            total_pb: totalPBAl,
        });
    });

    // 8. Kandungan Gizi (Sheet 2: C)
    const giziList = [];
    const akgPK = raw.akg_pk || wo.akg_pk || {};
    const akgPB = raw.akg_pb || wo.akg_pb || {};

    let energiPK = akgPK.energi || Number(wo.energi_pk) || 0;
    let proteinPK = akgPK.protein || Number(wo.protein_pk) || 0;
    let lemakPK = akgPK.lemak || Number(wo.lemak_pk) || 0;
    let karboPK = akgPK.karbohidrat || Number(wo.karbo_pk) || 0;
    let seratPK = akgPK.serat || Number(wo.serat_pk) || 0;

    let energiPB = akgPB.energi || Number(wo.energi_pb) || 0;
    let proteinPB = akgPB.protein || Number(wo.protein_pb) || 0;
    let lemakPB = akgPB.lemak || Number(wo.lemak_pb) || 0;
    let karboPB = akgPB.karbohidrat || Number(wo.karbo_pb) || 0;
    let seratPB = akgPB.serat || Number(wo.serat_pb) || 0;

    if (energiPK === 0) {
        normalItems.forEach(it => {
            energiPK += Number(it.nutrisi_pk?.energi || 0);
            proteinPK += Number(it.nutrisi_pk?.protein || 0);
            lemakPK += Number(it.nutrisi_pk?.lemak || 0);
            karboPK += Number(it.nutrisi_pk?.karbohidrat || 0);
            seratPK += Number(it.nutrisi_pk?.serat || 0);
        });
    }
    if (energiPB === 0) {
        normalItems.forEach(it => {
            energiPB += Number(it.nutrisi_pb?.energi || 0);
            proteinPB += Number(it.nutrisi_pb?.protein || 0);
            lemakPB += Number(it.nutrisi_pb?.lemak || 0);
            karboPB += Number(it.nutrisi_pb?.karbohidrat || 0);
            seratPB += Number(it.nutrisi_pb?.serat || 0);
        });
    }

    giziList.push({
        peruntukan: 'Normal',
        jenis_pm: 'PK & PB',
        pk: { energi: Number(energiPK.toFixed(1)), protein: Number(proteinPK.toFixed(1)), lemak: Number(lemakPK.toFixed(1)), karbo: Number(karboPK.toFixed(1)), serat: Number(seratPK.toFixed(1)) },
        pb: { energi: Number(energiPB.toFixed(1)), protein: Number(proteinPB.toFixed(1)), lemak: Number(lemakPB.toFixed(1)), karbo: Number(karboPB.toFixed(1)), serat: Number(seratPB.toFixed(1)) },
        status: 'Memenuhi'
    });

    menuAllergenTypes.forEach(jenis => {
        const subItems = formattedItems.filter(it => it.tipe_porsi === 'alergi' && it.jenis_alergi === jenis);
        let alEnergiPK = 0, alProteinPK = 0, alLemakPK = 0, alKarboPK = 0, alSeratPK = 0;
        let alEnergiPB = 0, alProteinPB = 0, alLemakPB = 0, alKarboPB = 0, alSeratPB = 0;

        formattedItems.forEach(it => {
            if (it.tipe_porsi === 'alergi') {
                if (it.jenis_alergi === jenis) {
                    alEnergiPK += Number(it.nutrisi_pk?.energi || 0);
                    alProteinPK += Number(it.nutrisi_pk?.protein || 0);
                    alLemakPK += Number(it.nutrisi_pk?.lemak || 0);
                    alKarboPK += Number(it.nutrisi_pk?.karbohidrat || 0);
                    alSeratPK += Number(it.nutrisi_pk?.serat || 0);

                    alEnergiPB += Number(it.nutrisi_pb?.energi || 0);
                    alProteinPB += Number(it.nutrisi_pb?.protein || 0);
                    alLemakPB += Number(it.nutrisi_pb?.lemak || 0);
                    alKarboPB += Number(it.nutrisi_pb?.karbohidrat || 0);
                    alSeratPB += Number(it.nutrisi_pb?.serat || 0);
                }
            } else {
                const hasReplacementInSub = subItems.some(si => si.sub_menu_key === it.sub_menu_key);
                if (!hasReplacementInSub) {
                    alEnergiPK += Number(it.nutrisi_pk?.energi || 0);
                    alProteinPK += Number(it.nutrisi_pk?.protein || 0);
                    alLemakPK += Number(it.nutrisi_pk?.lemak || 0);
                    alKarboPK += Number(it.nutrisi_pk?.karbohidrat || 0);
                    alSeratPK += Number(it.nutrisi_pk?.serat || 0);

                    alEnergiPB += Number(it.nutrisi_pb?.energi || 0);
                    alProteinPB += Number(it.nutrisi_pb?.protein || 0);
                    alLemakPB += Number(it.nutrisi_pb?.lemak || 0);
                    alKarboPB += Number(it.nutrisi_pb?.karbohidrat || 0);
                    alSeratPB += Number(it.nutrisi_pb?.serat || 0);
                }
            }
        });

        if (alEnergiPK === 0) alEnergiPK = energiPK;
        if (alEnergiPB === 0) alEnergiPB = energiPB;

        giziList.push({
            peruntukan: `Alergi ${jenis}`,
            jenis_pm: 'PK & PB',
            pk: { energi: Number(alEnergiPK.toFixed(1)), protein: Number(alProteinPK.toFixed(1)), lemak: Number(alLemakPK.toFixed(1)), karbo: Number(alKarboPK.toFixed(1)), serat: Number(alSeratPK.toFixed(1)) },
            pb: { energi: Number(alEnergiPB.toFixed(1)), protein: Number(alProteinPB.toFixed(1)), lemak: Number(alLemakPB.toFixed(1)), karbo: Number(alKarboPB.toFixed(1)), serat: Number(alSeratPB.toFixed(1)) },
            status: 'Memenuhi'
        });
    });

    return {
        noWO,
        namaMenu,
        statusMenu,
        dbPangan,
        tglDist,
        rincianSubMenuStr,
        subMenusList,
        rincianSubMenuMultiline,
        sppgName,
        totalPK,
        totalPB,
        totalPM,
        kpmMenerima,
        kpmTidakMenerima,
        rekapMenerima,
        rekapTidakMenerima,
        penerimaMenerimaList: kpmMenerima.map(k => ({
            nama_kpm: k.nama || '-',
            kategori: k.kategori || '-',
            jumlah_pk: k.pk,
            jumlah_pb: k.pb,
            total_pm: k.total,
            alergi_desc: k.keterangan_alergi_terdampak || '-',
        })),
        penerimaLiburList: kpmTidakMenerima.map(k => ({
            nama_kpm: k.nama || '-',
            kategori: k.kategori || '-',
            jumlah_pk: k.pk,
            jumlah_pb: k.pb,
            total_pm: k.total,
            alergi_desc: k.keterangan_alergi_terdampak || '-',
        })),
        menuAllergenTypes: Array.from(menuAllergenTypes),
        activeAllergens,
        normalPK,
        normalPB,
        normalTotal,
        paguRatePK,
        paguRatePB,
        paguNominalPK,
        paguNominalPB,
        paguTotal,
        formattedItems,
        totalNetKg,
        totalGrossKg,
        totalBelanja,
        selisihPagu,
        foodCostNormalList,
        totalFcPKNormal,
        totalFcPBNormal,
        allergenFoodCostTables,
        giziList,
        totalSasaranNormalPK: normalPK,
        totalSasaranNormalPB: normalPB,
        sasaranAlergiPerType: Object.fromEntries(
            activeAllergens.map(a => [a.jenis, { pk: a.pk, pb: a.pb, total: a.total }])
        ),
        paguBahanTotal: paguTotal,
        totalBelanjaPO: totalBelanja,
    };
}

// -------------------------------------------------------------
// 1. EXPORT WORK ORDER EXCEL (.XLSX) DENGAN EXCELJS
// -------------------------------------------------------------
export async function exportWorkOrderExcel(wo) {
    const data = buildWorkOrderFullExportData(wo);
    const sppgName = data.sppgName;
    const filename = `${data.noWO}_${(data.namaMenu || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.xlsx`;

    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'SIPEGE SPPG';
    workbook.created = new Date();

    // Reusable styles
    const borderThin = {
        top: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        left: { style: 'thin', color: { argb: 'FFCBD5E1' } },
        right: { style: 'thin', color: { argb: 'FFCBD5E1' } },
    };

    const headerFill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFF1F5F9' },
    };

    // Embed Logos
    let logoBgnId = null;
    let logoYayasanId = null;
    try {
        logoBgnId = workbook.addImage({
            base64: LOGO_BGN_RAW_BASE64,
            extension: 'png',
        });
        logoYayasanId = workbook.addImage({
            base64: LOGO_YAYASAN_RAW_BASE64,
            extension: 'png',
        });
    } catch (e) {
        console.warn('Gagal memuat logo ke workbook Excel:', e);
    }

    // =========================================================
    // SHEET 1: PERENCANAAN PRODUKSI
    // =========================================================
    const sheet1 = workbook.addWorksheet('Perencanaan Produksi', {
        views: [{ showGridLines: true }],
    });

    sheet1.columns = [
        { width: 16 },  // A: No / Label 1
        { width: 16 },  // B: Status / Label 1
        { width: 34 },  // C: Nama KPM / Value 1
        { width: 22 },  // D: Kategori / Value 1
        { width: 20 },  // E: Jumlah PK / Label 2
        { width: 20 },  // F: Jumlah PB / Label 2
        { width: 20 },  // G: Total PM / Value 2
        { width: 36 },  // H: Keterangan Alergi / Value 2
    ];
    for (let c = 9; c <= 25; c++) {
        sheet1.getColumn(c).width = 20;
    }

    // Add Kop to Sheet 1
    if (logoBgnId !== null) {
        sheet1.addImage(logoBgnId, {
            tl: { nativeCol: 2, nativeColOff: 781050, nativeRow: 0, nativeRowOff: 18000 },
            ext: { width: 62, height: 62 },
        });
    }
    if (logoYayasanId !== null) {
        sheet1.addImage(logoYayasanId, {
            tl: { nativeCol: 6, nativeColOff: 38100, nativeRow: 0, nativeRowOff: 18000 },
            ext: { width: 62, height: 62 },
        });
    }

    sheet1.mergeCells('A1:H1');
    sheet1.getCell('A1').value = 'SPPG BULELENG SUKASADA TEGALLINGGAH';
    sheet1.getCell('A1').font = { name: 'Arial', size: 13, bold: true };
    sheet1.getCell('A1').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.mergeCells('A2:H2');
    sheet1.getCell('A2').value = 'YAYASAN PESANTREN MIFTAHUL ULUM';
    sheet1.getCell('A2').font = { name: 'Arial', size: 12, bold: true };
    sheet1.getCell('A2').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.mergeCells('A3:H3');
    sheet1.getCell('A3').value = 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';
    sheet1.getCell('A3').font = { name: 'Arial', size: 9 };
    sheet1.getCell('A3').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet1.mergeCells('A4:H4');
    sheet1.getCell('A4').value = 'E-mail: sppgsukasadategallinggah@gmail.com';
    sheet1.getCell('A4').font = { name: 'Arial', size: 9, italic: true };
    sheet1.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

    // Border bawah kop
    for (let c = 1; c <= 8; c++) {
        sheet1.getRow(4).getCell(c).border = { bottom: { style: 'medium', color: { argb: 'FF000000' } } };
    }

    sheet1.addRow([]); // Gap Baris Kosong (Row 5)

    // Title Banner Sheet 1
    const rowBanner1 = sheet1.addRow(['LAPORAN PERENCANAAN PRODUKSI MAKAN BERGIZI GRATIS']);
    sheet1.mergeCells(`A${rowBanner1.number}:H${rowBanner1.number}`);
    rowBanner1.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FFFFFFFF' } };
    rowBanner1.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
    rowBanner1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rowBanner1.height = 24;

    const rowBanner2 = sheet1.addRow([sppgName]);
    sheet1.mergeCells(`A${rowBanner2.number}:H${rowBanner2.number}`);
    rowBanner2.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FFE0E7FF' } };
    rowBanner2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E3A8A' } };
    rowBanner2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rowBanner2.height = 20;

    sheet1.addRow([]); // Gap Baris Kosong

    // A. Informasi Perencanaan
    const rInfoHead = sheet1.addRow(['A. Informasi Perencanaan']);
    sheet1.mergeCells(`A${rInfoHead.number}:H${rInfoHead.number}`);
    rInfoHead.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FF0F172A' } };
    rInfoHead.height = 22;

    // Row Info 1: No WO & Tgl Distribusi
    const rInfo1 = sheet1.addRow([
        'No. Perencanaan Produksi', '', `:  ${data.noWO}`, '',
        'Tanggal Distribusi Menu', '', `:  ${data.tglDist}`, ''
    ]);
    sheet1.mergeCells(`A${rInfo1.number}:B${rInfo1.number}`);
    sheet1.mergeCells(`C${rInfo1.number}:D${rInfo1.number}`);
    sheet1.mergeCells(`E${rInfo1.number}:F${rInfo1.number}`);
    sheet1.mergeCells(`G${rInfo1.number}:H${rInfo1.number}`);
    rInfo1.getCell(1).font = { bold: true };
    rInfo1.getCell(3).font = { bold: true, color: { argb: 'FF0F172A' } };
    rInfo1.getCell(5).font = { bold: true };
    rInfo1.getCell(7).font = { bold: true, color: { argb: 'FF1E3A8A' } };
    rInfo1.height = 20;

    // Row Info 2: Status Menu & Nama Menu
    const rInfo2 = sheet1.addRow([
        'Status Menu', '', `:  ${data.statusMenu}`, '',
        'Nama Menu Produksi', '', `:  ${data.namaMenu}`, ''
    ]);
    sheet1.mergeCells(`A${rInfo2.number}:B${rInfo2.number}`);
    sheet1.mergeCells(`C${rInfo2.number}:D${rInfo2.number}`);
    sheet1.mergeCells(`E${rInfo2.number}:F${rInfo2.number}`);
    sheet1.mergeCells(`G${rInfo2.number}:H${rInfo2.number}`);
    rInfo2.getCell(1).font = { bold: true };
    rInfo2.getCell(3).font = { bold: true, color: { argb: 'FF047857' } };
    rInfo2.getCell(5).font = { bold: true };
    rInfo2.getCell(7).font = { bold: true };
    rInfo2.height = 20;

    // Row Info 3: Database Pangan & Total Sasaran PM
    const rInfo3 = sheet1.addRow([
        'Database Pangan', '', `:  ${data.dbPangan}`, '',
        'Total Sasaran PM', '', `:  ${data.totalPM.toLocaleString('id-ID')} PM (PK: ${data.totalPK.toLocaleString('id-ID')}, PB: ${data.totalPB.toLocaleString('id-ID')})`, ''
    ]);
    sheet1.mergeCells(`A${rInfo3.number}:B${rInfo3.number}`);
    sheet1.mergeCells(`C${rInfo3.number}:D${rInfo3.number}`);
    sheet1.mergeCells(`E${rInfo3.number}:F${rInfo3.number}`);
    sheet1.mergeCells(`G${rInfo3.number}:H${rInfo3.number}`);
    rInfo3.getCell(1).font = { bold: true };
    rInfo3.getCell(5).font = { bold: true };
    rInfo3.getCell(7).font = { bold: true };
    rInfo3.height = 20;

    // Row Info 4: Rincian Sub Menu (Multiline Alt+Enter)
    const subMenuLines = data.subMenusList && data.subMenusList.length > 0
        ? data.subMenusList.map((sm, idx) => (idx === 0 ? `:  ${idx + 1}. ${sm.nama}` : `   ${idx + 1}. ${sm.nama}`)).join('\n')
        : ':  -';
    const rInfo4 = sheet1.addRow(['Rincian Sub Menu', '', subMenuLines, '', '', '', '', '']);
    sheet1.mergeCells(`A${rInfo4.number}:B${rInfo4.number}`);
    sheet1.mergeCells(`C${rInfo4.number}:H${rInfo4.number}`);
    rInfo4.getCell(1).font = { bold: true };
    rInfo4.getCell(1).alignment = { vertical: 'top' };
    rInfo4.getCell(3).alignment = { wrapText: true, vertical: 'top' };
    rInfo4.height = Math.max(26, (data.subMenusList?.length || 1) * 19);

    sheet1.addRow([]); // Gap Baris Kosong

    // B. Data Penerima Manfaat Terdistribusi
    const rDistHead = sheet1.addRow(['B. Data Penerima Manfaat Terdistribusi']);
    sheet1.mergeCells(`A${rDistHead.number}:H${rDistHead.number}`);
    rDistHead.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FF0F172A' } };
    rDistHead.height = 22;

    sheet1.addRow([]); // Gap Baris Kosong

    // Tabel 1: Menerima
    const rT1Head = sheet1.addRow(['Tabel 1. Data Penerima Manfaat yang Menerima Menu']);
    sheet1.mergeCells(`A${rT1Head.number}:H${rT1Head.number}`);
    rT1Head.getCell(1).font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FF047857' } };

    const rT1Cols = sheet1.addRow(['No', 'Status', 'Nama KPM', 'Kategori', 'Jumlah PK', 'Jumlah PB', 'Total PM', 'Keterangan Alergi (Menu Ini)']);
    rT1Cols.font = { bold: true };
    rT1Cols.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDCFCE7' } };
        cell.border = borderThin;
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
    });

    data.kpmMenerima.forEach((k, idx) => {
        const row = sheet1.addRow([
            idx + 1,
            'Menerima',
            k.nama,
            k.kategori,
            k.pk,
            k.pb,
            k.total,
            k.keterangan_alergi_terdampak || '-'
        ]);
        row.getCell(1).alignment = { horizontal: 'center' };
        row.getCell(2).alignment = { horizontal: 'center' };
        row.getCell(2).font = { bold: true, color: { argb: 'FF047857' } };
        row.getCell(3).font = { bold: true };
        row.getCell(4).alignment = { horizontal: 'center' };
        row.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(7).font = { bold: true };
        row.getCell(8).alignment = { wrapText: true };
        row.eachCell(cell => { cell.border = borderThin; });
    });

    const rT1Rekap = sheet1.addRow(['Rekap Total Penerima (Menerima):', '', '', '', data.rekapMenerima.pk, data.rekapMenerima.pb, data.rekapMenerima.total, '']);
    sheet1.mergeCells(`A${rT1Rekap.number}:D${rT1Rekap.number}`);
    rT1Rekap.font = { bold: true };
    rT1Rekap.getCell(1).alignment = { horizontal: 'right' };
    rT1Rekap.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
    rT1Rekap.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
    rT1Rekap.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
    rT1Rekap.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDCFCE7' } };
        cell.border = borderThin;
    });

    sheet1.addRow([]); // Gap Baris Kosong

    // Tabel 2: Tidak Menerima
    const rT2Head = sheet1.addRow(['Tabel 2. Data Penerima Manfaat yang Tidak Menerima Menu (Libur/Off)']);
    sheet1.mergeCells(`A${rT2Head.number}:H${rT2Head.number}`);
    rT2Head.getCell(1).font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FFB91C1C' } };

    const rT2Cols = sheet1.addRow(['No', 'Status', 'Nama KPM', 'Kategori', 'Jumlah PK', 'Jumlah PB', 'Total PM', '']);
    sheet1.mergeCells(`G${rT2Cols.number}:H${rT2Cols.number}`);
    rT2Cols.font = { bold: true };
    rT2Cols.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
        cell.border = borderThin;
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
    });

    if (data.kpmTidakMenerima.length === 0) {
        const rEmpty = sheet1.addRow(['Seluruh KPM menerima pelayanan makanan pada tanggal ini (Nihil kelompok libur).', '', '', '', '', '', '', '']);
        sheet1.mergeCells(`A${rEmpty.number}:H${rEmpty.number}`);
        rEmpty.getCell(1).alignment = { horizontal: 'center' };
        rEmpty.getCell(1).font = { italic: true, color: { argb: 'FF64748B' } };
        rEmpty.eachCell(cell => { cell.border = borderThin; });
    } else {
        data.kpmTidakMenerima.forEach((k, idx) => {
            const row = sheet1.addRow([
                idx + 1,
                'Libur / Off',
                k.nama,
                k.kategori,
                k.pk,
                k.pb,
                k.total,
                ''
            ]);
            sheet1.mergeCells(`G${row.number}:H${row.number}`);
            row.getCell(1).alignment = { horizontal: 'center' };
            row.getCell(2).alignment = { horizontal: 'center' };
            row.getCell(2).font = { bold: true, color: { argb: 'FFB91C1C' } };
            row.getCell(4).alignment = { horizontal: 'center' };
            row.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
            row.eachCell(cell => { cell.border = borderThin; });
        });
    }

    const rT2Rekap = sheet1.addRow(['Rekap Total (Tidak Menerima):', '', '', '', data.rekapTidakMenerima.pk, data.rekapTidakMenerima.pb, data.rekapTidakMenerima.total, '']);
    sheet1.mergeCells(`A${rT2Rekap.number}:D${rT2Rekap.number}`);
    sheet1.mergeCells(`G${rT2Rekap.number}:H${rT2Rekap.number}`);
    rT2Rekap.font = { bold: true };
    rT2Rekap.getCell(1).alignment = { horizontal: 'right' };
    rT2Rekap.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
    rT2Rekap.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
    rT2Rekap.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
    rT2Rekap.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
        cell.border = borderThin;
    });

    sheet1.addRow([]); // Gap Baris Kosong

    // Tabel 3: Ringkasan Sasaran Porsi (Header 2 Baris: Merge Normal & Alergi, Baris 2 PK & PB)
    const rT3Head = sheet1.addRow(['Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi']);
    sheet1.mergeCells(`A${rT3Head.number}:H${rT3Head.number}`);
    rT3Head.getCell(1).font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FF0F172A' } };

    // Baris 1: Grup Kategori (Normal, Alergi X, Alergi Y)
    const t3Row1 = ['Sasaran Normal', ''];
    // Baris 2: Sub Kolom (PK, PB)
    const t3Row2 = ['PK', 'PB'];
    // Baris Nilai: Angka
    const t3Values = [data.normalPK, data.normalPB];

    data.activeAllergens.forEach(al => {
        t3Row1.push(`Alergi ${al.jenis}`, '');
        t3Row2.push('PK', 'PB');
        t3Values.push(al.pk, al.pb);
    });

    // Pastikan lebar kolom cukup lega dan tidak terpotong
    for (let c = 1; c <= t3Row1.length; c++) {
        if (!sheet1.getColumn(c).width || sheet1.getColumn(c).width < 16) {
            sheet1.getColumn(c).width = 16;
        }
    }

    const rT3_1 = sheet1.addRow(t3Row1);
    rT3_1.height = 26;
    rT3_1.font = { bold: true };

    const rT3_2 = sheet1.addRow(t3Row2);
    rT3_2.height = 22;
    rT3_2.font = { bold: true };

    // Lakukan merge Baris 1 untuk setiap kategori 2 kolom
    const t3R1Num = rT3_1.number;
    const t3R2Num = rT3_2.number;

    let curCol = 1;
    // Merge Sasaran Normal (Kolom 1:2)
    sheet1.mergeCells(t3R1Num, curCol, t3R1Num, curCol + 1);
    curCol += 2;

    // Merge setiap Alergi (2 kolom per alergi)
    data.activeAllergens.forEach(() => {
        sheet1.mergeCells(t3R1Num, curCol, t3R1Num, curCol + 1);
        curCol += 2;
    });

    // Styling Header Baris 1 & 2
    [rT3_1, rT3_2].forEach(r => {
        r.eachCell({ includeEmpty: true }, cell => {
            cell.fill = headerFill;
            cell.border = borderThin;
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        });
    });

    const rT3Vals = sheet1.addRow(t3Values);
    rT3Vals.height = 24;
    rT3Vals.font = { bold: true };
    rT3Vals.eachCell({ includeEmpty: true }, (cell) => {
        cell.border = borderThin;
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
    });

    sheet1.addRow([]); // Gap Baris Kosong

    // Tabel 4: Batas Pagu Anggaran
    const rT4Head = sheet1.addRow(['Tabel 4. Perhitungan Mencari Batas Pagu Anggaran (PK Rp 8.000 dan PB Rp 10.000)']);
    sheet1.mergeCells(`A${rT4Head.number}:H${rT4Head.number}`);
    rT4Head.getCell(1).font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FF0F172A' } };

    const rT4Cols = sheet1.addRow(['No', 'Kategori Sasaran Porsi', '', 'Jumlah PM', 'Pagu / Porsi', 'Rumus Pagu', '', 'Total Pagu Anggaran']);
    sheet1.mergeCells(`B${rT4Cols.number}:C${rT4Cols.number}`);
    sheet1.mergeCells(`F${rT4Cols.number}:G${rT4Cols.number}`);
    rT4Cols.font = { bold: true };
    rT4Cols.eachCell(cell => {
        cell.fill = headerFill;
        cell.border = borderThin;
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
    });

    const rT4Pk = sheet1.addRow([1, 'Porsi Kecil (PK - PAUD/TK & SD 1-3)', '', data.totalPK, 8000, `${data.totalPK} PM Ã— Rp 8.000`, '', data.paguNominalPK]);
    sheet1.mergeCells(`B${rT4Pk.number}:C${rT4Pk.number}`);
    sheet1.mergeCells(`F${rT4Pk.number}:G${rT4Pk.number}`);
    rT4Pk.getCell(1).alignment = { horizontal: 'center' };
    rT4Pk.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
    rT4Pk.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
    rT4Pk.getCell(5).numFmt = '"Rp "#,##0';
    rT4Pk.getCell(6).alignment = { horizontal: 'center' };
    rT4Pk.getCell(8).alignment = { horizontal: 'right', vertical: 'middle' };
    rT4Pk.getCell(8).font = { bold: true };
    rT4Pk.getCell(8).numFmt = '"Rp "#,##0';
    rT4Pk.eachCell(cell => { cell.border = borderThin; });

    const rT4Pb = sheet1.addRow([2, 'Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)', '', data.totalPB, 10000, `${data.totalPB} PM Ã— Rp 10.000`, '', data.paguNominalPB]);
    sheet1.mergeCells(`B${rT4Pb.number}:C${rT4Pb.number}`);
    sheet1.mergeCells(`F${rT4Pb.number}:G${rT4Pb.number}`);
    rT4Pb.getCell(1).alignment = { horizontal: 'center' };
    rT4Pb.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
    rT4Pb.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
    rT4Pb.getCell(5).numFmt = '"Rp "#,##0';
    rT4Pb.getCell(6).alignment = { horizontal: 'center' };
    rT4Pb.getCell(8).alignment = { horizontal: 'right', vertical: 'middle' };
    rT4Pb.getCell(8).font = { bold: true };
    rT4Pb.getCell(8).numFmt = '"Rp "#,##0';
    rT4Pb.eachCell(cell => { cell.border = borderThin; });

    const rT4Tot = sheet1.addRow(['Total Batas Pagu Anggaran MBG:', '', '', data.totalPM, '', 'Pagu PK + Pagu PB', '', data.paguTotal]);
    sheet1.mergeCells(`A${rT4Tot.number}:C${rT4Tot.number}`);
    sheet1.mergeCells(`F${rT4Tot.number}:G${rT4Tot.number}`);
    rT4Tot.font = { bold: true };
    rT4Tot.getCell(1).alignment = { horizontal: 'right' };
    rT4Tot.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
    rT4Tot.getCell(6).alignment = { horizontal: 'center' };
    rT4Tot.getCell(8).alignment = { horizontal: 'right' };
    rT4Tot.getCell(8).font = { bold: true, color: { argb: 'FF1E3A8A' } };
    rT4Tot.getCell(8).numFmt = '"Rp "#,##0';
    rT4Tot.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDBEAFE' } };
        cell.border = borderThin;
    });


    // =========================================================
    // SHEET 2: FORMULA MAKANAN
    // =========================================================
    const sheet2 = workbook.addWorksheet('Formula Makanan', {
        views: [{ showGridLines: true }],
    });

    // 17 Kolom: Berat Bersih & Berat Kotor masing-masing pisah kolom PK dan PB
    sheet2.columns = [
        { width: 6 },   // A: No
        { width: 28 },  // B: Sub Menu (lega, tidak terpotong)
        { width: 38 },  // C: Bahan Pangan Master (sangat lega, muat nama master panjang)
        { width: 26 },  // D: Nama PO (lega untuk nama dagang PO)
        { width: 14 },  // E: Satuan / Energi (kkal)
        { width: 14 },  // F: Jenis / Protein (g)
        { width: 16 },  // G: Peruntukan Porsi / Lemak (g)
        { width: 16 },  // H: Berat Bersih PK (g) / Karbohidrat (g)
        { width: 14 },  // I: Berat Bersih PB (g) / Serat (g)
        { width: 14 },  // J: Berat Kotor PK (kg)
        { width: 14 },  // K: Berat Kotor PB (kg)
        { width: 10 },  // L: BDD (%)
        { width: 10 },  // M: Buffer (%)
        { width: 16 },  // N: Kebutuhan (PO)
        { width: 16 },  // O: Harga/Satuan
        { width: 18 },  // P: Subtotal
        { width: 16 },  // Q: Keterangan
    ];

    // Auto-expand kolom B, C, D bila ada nama menu/bahan yang lebih panjang dari standar
    let maxLenB = 26;
    let maxLenC = 36;
    let maxLenD = 24;
    data.formattedItems.forEach(it => {
        if (it.sub_menu && it.sub_menu.length > maxLenB) maxLenB = it.sub_menu.length;
        if (it.bahan_master && it.bahan_master.length > maxLenC) maxLenC = it.bahan_master.length;
        if (it.nama_po && it.nama_po.length > maxLenD) maxLenD = it.nama_po.length;
    });
    sheet2.getColumn(2).width = Math.min(maxLenB + 4, 44);
    sheet2.getColumn(3).width = Math.min(maxLenC + 4, 55);
    sheet2.getColumn(4).width = Math.min(maxLenD + 4, 38);

    // Hitung posisi Logo Kop Sheet 2 secara matematis agar persis membingkai teks judul
    const colPxOffsets_s2 = [0];
    let totalPx_s2 = 0;
    for (let c = 1; c <= 17; c++) {
        const w = sheet2.getColumn(c).width || 15;
        const px = Math.floor((w * 7.5) + 5);
        totalPx_s2 += px;
        colPxOffsets_s2.push(totalPx_s2);
    }
    const centerPx_s2 = totalPx_s2 / 2;
    const targetBgnPx_s2 = (centerPx_s2 - 180) - 70;
    const targetYayasanPx_s2 = (centerPx_s2 + 180) + 15;

    function findColAndOffset_s2(targetPx) {
        for (let c = 0; c < colPxOffsets_s2.length - 1; c++) {
            if (targetPx >= colPxOffsets_s2[c] && targetPx < colPxOffsets_s2[c + 1]) {
                const offPx = Math.round(targetPx - colPxOffsets_s2[c]);
                return { nativeCol: c, nativeColOff: offPx * 9525 };
            }
        }
        return { nativeCol: 4, nativeColOff: 0 };
    }

    const bgnPos_s2 = findColAndOffset_s2(targetBgnPx_s2);
    const yayasanPos_s2 = findColAndOffset_s2(targetYayasanPx_s2);

    if (logoBgnId !== null) {
        sheet2.addImage(logoBgnId, {
            tl: { nativeCol: bgnPos_s2.nativeCol, nativeColOff: bgnPos_s2.nativeColOff, nativeRow: 0, nativeRowOff: 18000 },
            ext: { width: 62, height: 62 },
        });
    }
    if (logoYayasanId !== null) {
        sheet2.addImage(logoYayasanId, {
            tl: { nativeCol: yayasanPos_s2.nativeCol, nativeColOff: yayasanPos_s2.nativeColOff, nativeRow: 0, nativeRowOff: 18000 },
            ext: { width: 62, height: 62 },
        });
    }

    sheet2.mergeCells('A1:Q1');
    sheet2.getCell('A1').value = 'SPPG BULELENG SUKASADA TEGALLINGGAH';
    sheet2.getCell('A1').font = { name: 'Arial', size: 13, bold: true };
    sheet2.getCell('A1').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet2.mergeCells('A2:Q2');
    sheet2.getCell('A2').value = 'YAYASAN PESANTREN MIFTAHUL ULUM';
    sheet2.getCell('A2').font = { name: 'Arial', size: 12, bold: true };
    sheet2.getCell('A2').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet2.mergeCells('A3:Q3');
    sheet2.getCell('A3').value = 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';
    sheet2.getCell('A3').font = { name: 'Arial', size: 9 };
    sheet2.getCell('A3').alignment = { horizontal: 'center', vertical: 'middle' };

    sheet2.mergeCells('A4:Q4');
    sheet2.getCell('A4').value = 'E-mail: sppgsukasadategallinggah@gmail.com';
    sheet2.getCell('A4').font = { name: 'Arial', size: 9, italic: true };
    sheet2.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

    for (let c = 1; c <= 17; c++) {
        sheet2.getRow(4).getCell(c).border = { bottom: { style: 'medium', color: { argb: 'FF000000' } } };
    }

    sheet2.addRow([]); // Gap Baris Kosong (Row 5)

    // Title Banner Sheet 2
    const rowBanner1_s2 = sheet2.addRow(['LAPORAN FORMULA MAKANAN & KEBUTUHAN BELANJA MBG']);
    sheet2.mergeCells(`A${rowBanner1_s2.number}:Q${rowBanner1_s2.number}`);
    rowBanner1_s2.getCell(1).font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FFFFFFFF' } };
    rowBanner1_s2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF047857' } };
    rowBanner1_s2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rowBanner1_s2.height = 24;

    const rowBanner2_s2 = sheet2.addRow([sppgName]);
    sheet2.mergeCells(`A${rowBanner2_s2.number}:Q${rowBanner2_s2.number}`);
    rowBanner2_s2.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FFD1FAE5' } };
    rowBanner2_s2.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF047857' } };
    rowBanner2_s2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
    rowBanner2_s2.height = 20;

    sheet2.addRow([]); // Gap Baris Kosong

    // A. Pemilihan Bahan Pangan
    const rBahanHead = sheet2.addRow(['A. Pemilihan Bahan Pangan']);
    sheet2.mergeCells(`A${rBahanHead.number}:Q${rBahanHead.number}`);
    rBahanHead.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FF0F172A' } };
    rBahanHead.height = 22;

    // Header 2 Baris: Baris 1 merge PK PB, Baris 2 PK dan PB pisah kolom
    const rBahanCols1 = sheet2.addRow([
        'No',
        'Sub Menu',
        'Bahan Pangan Master',
        'Nama PO',
        'Satuan',
        'Jenis',
        'Peruntukan Porsi',
        'Berat Bersih (g)',
        '',
        'Berat Kotor (kg)',
        '',
        'BDD (%)',
        'Buffer (%)',
        'Kebutuhan (PO)',
        'Harga/Satuan',
        'Subtotal',
        'Keterangan'
    ]);

    const rBahanCols2 = sheet2.addRow([
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        'PK',
        'PB',
        'PK',
        'PB',
        '',
        '',
        '',
        '',
        '',
        ''
    ]);

    const r1Num = rBahanCols1.number;
    const r2Num = rBahanCols2.number;

    // Vertical Merges
    ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'L', 'M', 'N', 'O', 'P', 'Q'].forEach(col => {
        sheet2.mergeCells(`${col}${r1Num}:${col}${r2Num}`);
    });

    // Horizontal Merges
    sheet2.mergeCells(`H${r1Num}:I${r1Num}`); // Berat Bersih (g)
    sheet2.mergeCells(`J${r1Num}:K${r1Num}`); // Berat Kotor (kg)

    [rBahanCols1, rBahanCols2].forEach(r => {
        r.font = { bold: true };
        r.height = 22;
        r.eachCell(cell => {
            cell.fill = headerFill;
            cell.border = borderThin;
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        });
    });

    let totalGramPK = 0;
    let totalGramPB = 0;
    let totalGrossKgPK = 0;
    let totalGrossKgPB = 0;

    data.formattedItems.forEach(it => {
        totalGramPK += it.gram_pk;
        totalGramPB += it.gram_pb;
        totalGrossKgPK += it.gross_kg_pk;
        totalGrossKgPB += it.gross_kg_pb;

        const row = sheet2.addRow([
            it.no,
            it.sub_menu,
            it.bahan_master,
            it.nama_po,
            it.satuan,
            it.jenis,
            it.peruntukan,
            it.gram_pk,                          // Pure number
            it.gram_pb,                          // Pure number
            Number(it.gross_kg_pk.toFixed(2)),   // Pure number
            Number(it.gross_kg_pb.toFixed(2)),   // Pure number
            it.bdd,
            it.buffer,
            it.total_gross,
            it.harga,
            it.subtotal,
            it.keterangan || '-'
        ]);

        row.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(2).alignment = { vertical: 'middle', wrapText: true };
        row.getCell(3).alignment = { vertical: 'middle', wrapText: true };
        row.getCell(4).alignment = { vertical: 'middle', wrapText: true };
        row.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };

        // Angka-angka rata tengah agar mudah dilihat
        row.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(8).numFmt = '#,##0.00';
        row.getCell(9).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(9).numFmt = '#,##0.00';

        row.getCell(10).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(10).numFmt = '#,##0.00';
        row.getCell(11).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(11).numFmt = '#,##0.00';

        row.getCell(12).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(13).alignment = { horizontal: 'center', vertical: 'middle' };

        row.getCell(14).alignment = { horizontal: 'center', vertical: 'middle' };
        row.getCell(14).font = { bold: true };
        row.getCell(14).numFmt = '#,##0.00';

        row.getCell(15).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(15).numFmt = '"Rp "#,##0';

        row.getCell(16).alignment = { horizontal: 'right', vertical: 'middle' };
        row.getCell(16).font = { bold: true };
        row.getCell(16).numFmt = '"Rp "#,##0';

        row.getCell(17).alignment = { horizontal: 'center' };
        row.eachCell(cell => { cell.border = borderThin; });
    });

    // Total Kebutuhan Bahan (Pisah Total PK dan PB)
    const rTotBahan1 = sheet2.addRow([
        'Total Kebutuhan Bahan:', '', '', '', '', '', '',
        Number(totalGramPK.toFixed(2)),
        Number(totalGramPB.toFixed(2)),
        Number(totalGrossKgPK.toFixed(2)),
        Number(totalGrossKgPB.toFixed(2)),
        '', '',
        data.totalGrossKg,
        '',
        data.totalBelanja,
        ''
    ]);
    sheet2.mergeCells(`A${rTotBahan1.number}:G${rTotBahan1.number}`);
    rTotBahan1.font = { bold: true };
    rTotBahan1.getCell(1).alignment = { horizontal: 'right' };
    [8, 9, 10, 11, 14].forEach(c => {
        rTotBahan1.getCell(c).alignment = { horizontal: 'center', vertical: 'middle' };
        rTotBahan1.getCell(c).numFmt = '#,##0.00';
    });
    rTotBahan1.getCell(16).alignment = { horizontal: 'right', vertical: 'middle' };
    rTotBahan1.getCell(16).numFmt = '"Rp "#,##0';
    rTotBahan1.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
        cell.border = borderThin;
    });

    const rTotBahan2 = sheet2.addRow(['Batas Pagu Anggaran MBG:', '', '', '', '', '', '', '', '', '', '', '', '', data.paguTotal, '', '', '']);
    sheet2.mergeCells(`A${rTotBahan2.number}:M${rTotBahan2.number}`);
    sheet2.mergeCells(`N${rTotBahan2.number}:Q${rTotBahan2.number}`);
    rTotBahan2.font = { bold: true };
    rTotBahan2.getCell(1).alignment = { horizontal: 'right' };
    rTotBahan2.getCell(14).alignment = { horizontal: 'right' };
    rTotBahan2.getCell(14).numFmt = '"Rp "#,##0';
    rTotBahan2.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFFBEB' } };
        cell.border = borderThin;
    });

    const rTotBahan3 = sheet2.addRow(['Total Belanja Formulasi:', '', '', '', '', '', '', '', '', '', '', '', '', data.totalBelanja, '', '', '']);
    sheet2.mergeCells(`A${rTotBahan3.number}:M${rTotBahan3.number}`);
    sheet2.mergeCells(`N${rTotBahan3.number}:Q${rTotBahan3.number}`);
    rTotBahan3.font = { bold: true };
    rTotBahan3.getCell(1).alignment = { horizontal: 'right' };
    rTotBahan3.getCell(14).alignment = { horizontal: 'right' };
    rTotBahan3.getCell(14).numFmt = '"Rp "#,##0';
    rTotBahan3.getCell(14).font = { bold: true, color: { argb: 'FF1E3A8A' } };
    rTotBahan3.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
        cell.border = borderThin;
    });

    const rTotBahan4 = sheet2.addRow(['Selisih / Efisiensi Anggaran:', '', '', '', '', '', '', '', '', '', '', '', '', `${formatRupiahNum(data.selisihPagu)} ${data.selisihPagu >= 0 ? '(Sisa Pagu)' : '(Melebihi Pagu)'}`, '', '', '']);
    sheet2.mergeCells(`A${rTotBahan4.number}:M${rTotBahan4.number}`);
    sheet2.mergeCells(`N${rTotBahan4.number}:Q${rTotBahan4.number}`);
    rTotBahan4.font = { bold: true };
    rTotBahan4.getCell(1).alignment = { horizontal: 'right' };
    rTotBahan4.getCell(14).alignment = { horizontal: 'right' };
    rTotBahan4.getCell(14).font = { bold: true, color: { argb: data.selisihPagu >= 0 ? 'FF15803D' : 'FFB91C1C' } };
    rTotBahan4.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: data.selisihPagu >= 0 ? 'FFDCFCE7' : 'FFFEE2E2' } };
        cell.border = borderThin;
    });

    sheet2.addRow([]); // Gap Baris Kosong

    // B. Food Cost
    const rFcHead = sheet2.addRow(['B. Food Cost']);
    sheet2.mergeCells(`A${rFcHead.number}:Q${rFcHead.number}`);
    rFcHead.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FF0F172A' } };
    rFcHead.height = 22;

    sheet2.addRow([]); // Gap Baris Kosong

    // Tabel 1. Rincian Food Cost Per Sub Menu Normal (Header 2 Baris: Food Cost & Persentase Pisah PK PB)
    const rFc1Head = sheet2.addRow(['Tabel 1. Rincian Food Cost Per Sub Menu Normal']);
    sheet2.mergeCells(`A${rFc1Head.number}:H${rFc1Head.number}`);
    rFc1Head.getCell(1).font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FF0369A1' } };

    const rFc1Cols1 = sheet2.addRow(['No', 'Sub Menu', '', 'Jumlah Bahan Baku', 'Food Cost (Rp)', '', 'Persentase (%)', '']);
    const rFc1Cols2 = sheet2.addRow(['', '', '', '', 'PK', 'PB', 'PK', 'PB']);
    const fcR1 = rFc1Cols1.number;
    const fcR2 = rFc1Cols2.number;

    sheet2.mergeCells(`A${fcR1}:A${fcR2}`);
    sheet2.mergeCells(`B${fcR1}:C${fcR2}`);
    sheet2.mergeCells(`D${fcR1}:D${fcR2}`);
    sheet2.mergeCells(`E${fcR1}:F${fcR1}`); // Food Cost (Rp)
    sheet2.mergeCells(`G${fcR1}:H${fcR1}`); // Persentase (%)

    [rFc1Cols1, rFc1Cols2].forEach(r => {
        r.font = { bold: true };
        r.height = 22;
        r.eachCell(cell => {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0F2FE' } };
            cell.border = borderThin;
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
        });
    });

    data.foodCostNormalList.forEach((row, idx) => {
        const r = sheet2.addRow([
            idx + 1,
            row.sub_menu,
            '',
            `${row.count} Bahan`,
            row.cost_pk,
            row.cost_pb,
            Number(row.percent_pk.toFixed(1)), // Angka murni
            Number(row.percent_pb.toFixed(1))  // Angka murni
        ]);
        sheet2.mergeCells(`B${r.number}:C${r.number}`);
        r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        r.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
        r.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
        r.getCell(5).numFmt = '"Rp "#,##0';
        r.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
        r.getCell(6).numFmt = '"Rp "#,##0';
        r.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
        r.getCell(7).numFmt = '#,##0.0';
        r.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
        r.getCell(8).numFmt = '#,##0.0';
        r.eachCell(cell => { cell.border = borderThin; });
    });

    const rFc1Rekap = sheet2.addRow(['Rekap Total Food Cost Normal:', '', '', '', data.totalFcPKNormal, data.totalFcPBNormal, 100, 100]);
    sheet2.mergeCells(`A${rFc1Rekap.number}:D${rFc1Rekap.number}`);
    rFc1Rekap.font = { bold: true };
    rFc1Rekap.getCell(1).alignment = { horizontal: 'right' };
    rFc1Rekap.getCell(5).alignment = { horizontal: 'right' };
    rFc1Rekap.getCell(5).numFmt = '"Rp "#,##0';
    rFc1Rekap.getCell(6).alignment = { horizontal: 'right' };
    rFc1Rekap.getCell(6).numFmt = '"Rp "#,##0';
    rFc1Rekap.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
    rFc1Rekap.getCell(7).numFmt = '#,##0.0';
    rFc1Rekap.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
    rFc1Rekap.getCell(8).numFmt = '#,##0.0';
    rFc1Rekap.eachCell(cell => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0F2FE' } };
        cell.border = borderThin;
    });

    // Tabel 2..n Food Cost Alergi (jika ada)
    data.allergenFoodCostTables.forEach((alt, tIdx) => {
        sheet2.addRow([]); // Gap Baris Kosong
        const rAltHead = sheet2.addRow([`Tabel ${tIdx + 2}. Rincian Food Cost Per Sub Menu Alergi ${alt.jenis_alergi}`]);
        sheet2.mergeCells(`A${rAltHead.number}:H${rAltHead.number}`);
        rAltHead.getCell(1).font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FFB91C1C' } };

        const rAltCols1 = sheet2.addRow(['No', 'Sub Menu', '', 'Jumlah Bahan Baku', 'Food Cost (Rp)', '', 'Persentase (%)', '']);
        const rAltCols2 = sheet2.addRow(['', '', '', '', 'PK', 'PB', 'PK', 'PB']);
        const alR1 = rAltCols1.number;
        const alR2 = rAltCols2.number;

        sheet2.mergeCells(`A${alR1}:A${alR2}`);
        sheet2.mergeCells(`B${alR1}:C${alR2}`);
        sheet2.mergeCells(`D${alR1}:D${alR2}`);
        sheet2.mergeCells(`E${alR1}:F${alR1}`);
        sheet2.mergeCells(`G${alR1}:H${alR1}`);

        [rAltCols1, rAltCols2].forEach(r => {
            r.font = { bold: true };
            r.height = 22;
            r.eachCell(cell => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
                cell.border = borderThin;
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            });
        });

        alt.list.forEach((row, idx) => {
            const r = sheet2.addRow([
                idx + 1,
                row.sub_menu,
                '',
                `${row.count} Bahan`,
                row.cost_pk,
                row.cost_pb,
                Number(row.percent_pk.toFixed(1)), // Angka murni
                Number(row.percent_pb.toFixed(1))  // Angka murni
            ]);
            sheet2.mergeCells(`B${r.number}:C${r.number}`);
            r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            r.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
            r.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
            r.getCell(5).numFmt = '"Rp "#,##0';
            r.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
            r.getCell(6).numFmt = '"Rp "#,##0';
            r.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
            r.getCell(7).numFmt = '#,##0.0';
            r.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
            r.getCell(8).numFmt = '#,##0.0';
            r.eachCell(cell => { cell.border = borderThin; });
        });

        const rAltRekap = sheet2.addRow([`Rekap Total Food Cost Alergi ${alt.jenis_alergi}:`, '', '', '', alt.total_pk, alt.total_pb, 100, 100]);
        sheet2.mergeCells(`A${rAltRekap.number}:D${rAltRekap.number}`);
        rAltRekap.font = { bold: true };
        rAltRekap.getCell(1).alignment = { horizontal: 'right' };
        rAltRekap.getCell(5).alignment = { horizontal: 'right' };
        rAltRekap.getCell(5).numFmt = '"Rp "#,##0';
        rAltRekap.getCell(6).alignment = { horizontal: 'right' };
        rAltRekap.getCell(6).numFmt = '"Rp "#,##0';
        rAltRekap.getCell(7).alignment = { horizontal: 'center', vertical: 'middle' };
        rAltRekap.getCell(7).numFmt = '#,##0.0';
        rAltRekap.getCell(8).alignment = { horizontal: 'center', vertical: 'middle' };
        rAltRekap.getCell(8).numFmt = '#,##0.0';
        rAltRekap.eachCell(cell => {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
            cell.border = borderThin;
        });
    });

    sheet2.addRow([]); // Gap Baris Kosong

    // C. Kandungan Gizi (Pas Kolom A..H, 1 Kolom Peruntukan Porsi agar pas sejajar dengan tabel Food Cost diatasnya)
    const rGzHead = sheet2.addRow(['C. Kandungan Gizi']);
    sheet2.mergeCells(`A${rGzHead.number}:H${rGzHead.number}`);
    rGzHead.getCell(1).font = { name: 'Arial', size: 11, bold: true, color: { argb: 'FF0F172A' } };
    rGzHead.height = 22;

    // Tabel Kandungan Gizi Kompak (A sampai H, pas sejajar kolom Serat dengan Food Cost):
    // Col A (width 6): No
    // Col B (width 28): Peruntukan Porsi (1 kolom saja, tidak dimerge 2 kolom)
    // Col C (width 38): Jenis PM
    // Col D (width 26): Energi (kkal)
    // Col E (width 14): Protein (g)
    // Col F (width 14): Lemak (g)
    // Col G (width 16): Karbohidrat (g)
    // Col H (width 16): Serat (g)
    const rGzCols = sheet2.addRow([
        'No',
        'Peruntukan Porsi',
        'Jenis PM',
        'Energi (kkal)',
        'Protein (g)',
        'Lemak (g)',
        'Karbohidrat (g)',
        'Serat (g)'
    ]);
    rGzCols.height = 26;
    rGzCols.font = { bold: true };

    for (let c = 1; c <= 8; c++) {
        const cell = rGzCols.getCell(c);
        cell.fill = headerFill;
        cell.border = borderThin;
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    }

    data.giziList.forEach((g, idx) => {
        const r1 = sheet2.addRow([
            idx + 1,
            g.peruntukan,
            'PK (Porsi Kecil)',
            g.pk.energi,
            g.pk.protein,
            g.pk.lemak,
            g.pk.karbo,
            g.pk.serat
        ]);
        const r2 = sheet2.addRow([
            '',
            '',
            'PB (Porsi Besar)',
            g.pb.energi,
            g.pb.protein,
            g.pb.lemak,
            g.pb.karbo,
            g.pb.serat
        ]);

        const r1N = r1.number;
        const r2N = r2.number;

        sheet2.mergeCells(`A${r1N}:A${r2N}`);
        sheet2.mergeCells(`B${r1N}:B${r2N}`); // 1 kolom saja di Kolom B

        r1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
        r1.getCell(2).alignment = { vertical: 'middle', wrapText: true };
        r1.getCell(2).font = { bold: true };
        r1.getCell(3).alignment = { horizontal: 'center', vertical: 'middle' };
        r1.getCell(3).font = { bold: true };
        r2.getCell(3).alignment = { horizontal: 'center', vertical: 'middle' };
        r2.getCell(3).font = { bold: true };
        
        // Angka-angka rata tengah (horizontal: 'center') agar mudah dilihat: Kolom 4 sampai 8
        for (let c = 4; c <= 8; c++) {
            r1.getCell(c).alignment = { horizontal: 'center', vertical: 'middle' };
            r1.getCell(c).numFmt = '#,##0.0';
            r2.getCell(c).alignment = { horizontal: 'center', vertical: 'middle' };
            r2.getCell(c).numFmt = '#,##0.0';
        }

        for (let c = 1; c <= 8; c++) {
            r1.getCell(c).border = borderThin;
            r2.getCell(c).border = borderThin;
        }
    });

    sheet2.addRow([]); // Gap Baris Kosong
    sheet2.addRow([]); // Gap Baris Kosong

    // Lembar Pengesahan TTD Sheet 2
    const rTtdTitle = sheet2.addRow(['', 'Direncanakan Oleh:', '', 'Diperiksa Oleh:', '', 'Mengetahui & Menyetujui:', '']);
    sheet2.mergeCells(`B${rTtdTitle.number}:C${rTtdTitle.number}`);
    sheet2.mergeCells(`D${rTtdTitle.number}:E${rTtdTitle.number}`);
    sheet2.mergeCells(`F${rTtdTitle.number}:G${rTtdTitle.number}`);
    rTtdTitle.getCell(2).alignment = { horizontal: 'center' };
    rTtdTitle.getCell(4).alignment = { horizontal: 'center' };
    rTtdTitle.getCell(6).alignment = { horizontal: 'center' };

    const rTtdRole = sheet2.addRow(['', 'Tim Ahli Gizi SPPG', '', 'Petugas Keuangan / Akuntan', '', 'Kepala SPPG', '']);
    sheet2.mergeCells(`B${rTtdRole.number}:C${rTtdRole.number}`);
    sheet2.mergeCells(`D${rTtdRole.number}:E${rTtdRole.number}`);
    sheet2.mergeCells(`F${rTtdRole.number}:G${rTtdRole.number}`);
    rTtdRole.font = { bold: true };
    rTtdRole.getCell(2).alignment = { horizontal: 'center' };
    rTtdRole.getCell(4).alignment = { horizontal: 'center' };
    rTtdRole.getCell(6).alignment = { horizontal: 'center' };

    sheet2.addRow([]);
    sheet2.addRow([]);
    sheet2.addRow([]);

    const rTtdName = sheet2.addRow(['', '(....................................)', '', '(....................................)', '', '(....................................)', '']);
    sheet2.mergeCells(`B${rTtdName.number}:C${rTtdName.number}`);
    sheet2.mergeCells(`D${rTtdName.number}:E${rTtdName.number}`);
    sheet2.mergeCells(`F${rTtdName.number}:G${rTtdName.number}`);
    rTtdName.font = { bold: true };
    rTtdName.getCell(2).alignment = { horizontal: 'center' };
    rTtdName.getCell(4).alignment = { horizontal: 'center' };
    rTtdName.getCell(6).alignment = { horizontal: 'center' };

    // Export to ArrayBuffer and Download as .xlsx
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// -------------------------------------------------------------
// 2. EXPORT WORK ORDER WORD (.DOCX) DENGAN DOCX (IDENTIK DENGAN EXCEL & RAPI)
// -------------------------------------------------------------
export async function exportWorkOrderWord(wo) {
    const data = buildWorkOrderFullExportData(wo);
    const sppgName = data.sppgName;
    const filename = `${data.noWO}_${(data.namaMenu || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.docx`;

    function base64ToUint8Array(base64) {
        const binaryString = atob(base64);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes;
    }

    const bgnBytes = base64ToUint8Array(LOGO_BGN_RAW_BASE64);
    const yayasanBytes = base64ToUint8Array(LOGO_YAYASAN_RAW_BASE64);

    // Border definitions: TableBorderNone eliminates ALL inside and outside gridlines in Word!
    const docxTableBorderNone = {
        top: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        bottom: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        left: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        right: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        insideHorizontal: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        insideVertical: { style: BorderStyle.NONE, size: 0, color: 'auto' },
    };

    const docxCellBorderNone = {
        top: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        bottom: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        left: { style: BorderStyle.NONE, size: 0, color: 'auto' },
        right: { style: BorderStyle.NONE, size: 0, color: 'auto' },
    };

    const docxBorderThin = {
        top: { style: BorderStyle.SINGLE, size: 4, color: 'CBD5E1' },
        bottom: { style: BorderStyle.SINGLE, size: 4, color: 'CBD5E1' },
        left: { style: BorderStyle.SINGLE, size: 4, color: 'CBD5E1' },
        right: { style: BorderStyle.SINGLE, size: 4, color: 'CBD5E1' },
    };

    function docxCell({
        text = '',
        children = null,
        width = null,
        colSpan = 1,
        rowSpan = 1,
        bold = false,
        italic = false,
        align = AlignmentType.LEFT,
        color = undefined,
        size = 14,
        shading = null,
        borders = docxBorderThin,
        margins = { top: 30, bottom: 30, left: 60, right: 60 },
    }) {
        const cellChildren = children || [
            new Paragraph({
                alignment: align,
                spacing: { before: 0, after: 0, line: 200 },
                children: [
                    new TextRun({
                        text: text != null ? String(text) : '',
                        bold,
                        italic,
                        color,
                        size,
                        font: 'Arial',
                    }),
                ],
            }),
        ];
        const config = {
            borders,
            children: cellChildren,
            verticalAlign: VerticalAlign.CENTER,
            margins,
        };
        if (width) config.width = { size: width, type: WidthType.DXA };
        if (colSpan > 1) config.columnSpan = colSpan;
        if (rowSpan > 1) config.rowSpan = rowSpan;
        if (shading) config.shading = shading;
        return new TableCell(config);
    }

    function createKopDocxTable() {
        return new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: {
                top: { style: BorderStyle.NONE, size: 0, color: 'auto' },
                bottom: { style: BorderStyle.DOUBLE, size: 18, color: '000000' },
                left: { style: BorderStyle.NONE, size: 0, color: 'auto' },
                right: { style: BorderStyle.NONE, size: 0, color: 'auto' },
                insideHorizontal: { style: BorderStyle.NONE, size: 0, color: 'auto' },
                insideVertical: { style: BorderStyle.NONE, size: 0, color: 'auto' },
            },
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        new TableCell({
                            width: { size: 2600, type: WidthType.DXA },
                            borders: docxCellBorderNone,
                            children: [new Paragraph({ text: '' })],
                        }),
                        new TableCell({
                            width: { size: 1200, type: WidthType.DXA },
                            borders: docxCellBorderNone,
                            verticalAlign: VerticalAlign.CENTER,
                            children: [
                                new Paragraph({
                                    children: [
                                        new ImageRun({
                                            data: bgnBytes,
                                            transformation: { width: 48, height: 48 },
                                        }),
                                    ],
                                    alignment: AlignmentType.RIGHT,
                                }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 7750, type: WidthType.DXA },
                            borders: docxCellBorderNone,
                            verticalAlign: VerticalAlign.CENTER,
                            children: [
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'SPPG BULELENG SUKASADA TEGALLINGGAH', bold: true, size: 21, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                    spacing: { before: 0, after: 20 },
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'YAYASAN PESANTREN MIFTAHUL ULUM', bold: true, size: 19, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                    spacing: { before: 0, after: 20 },
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali', size: 15, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                    spacing: { before: 0, after: 10 },
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'E-mail: sppgsukasadategallinggah@gmail.com', size: 14, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                    spacing: { before: 0, after: 0 },
                                }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 1200, type: WidthType.DXA },
                            borders: docxCellBorderNone,
                            verticalAlign: VerticalAlign.CENTER,
                            children: [
                                new Paragraph({
                                    children: [
                                        new ImageRun({
                                            data: yayasanBytes,
                                            transformation: { width: 48, height: 48 },
                                        }),
                                    ],
                                    alignment: AlignmentType.LEFT,
                                }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 2600, type: WidthType.DXA },
                            borders: docxCellBorderNone,
                            children: [new Paragraph({ text: '' })],
                        }),
                    ],
                }),
            ],
        });
    }

    function createBannerTable(title, sub, bgColor, textColor = 'FFFFFF') {
        return new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxTableBorderNone,
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        new TableCell({
                            width: { size: 15350, type: WidthType.DXA },
                            borders: docxCellBorderNone,
                            shading: { fill: bgColor, type: ShadingType.CLEAR },
                            verticalAlign: VerticalAlign.CENTER,
                            margins: { top: 70, bottom: 70, left: 100, right: 100 },
                            children: [
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: title, bold: true, size: 19, font: 'Arial', color: textColor }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                    spacing: { before: 0, after: 15 },
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: sub, bold: true, size: 15, font: 'Arial', color: textColor }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                    spacing: { before: 0, after: 0 },
                                }),
                            ],
                        }),
                    ],
                }),
            ],
        });
    }

    // 8 Kolom Tabel 1 & Tabel 2: [600, 1100, 4400, 1250, 1300, 1300, 1400, 4000] = 15350 DXA
    const colW_T1 = [600, 1100, 4400, 1250, 1300, 1300, 1400, 4000];

    // =========================================================================
    // SHEET 1 - HALAMAN 1
    // =========================================================================
    const sheet1Page1Children = [
        createKopDocxTable(),
        new Paragraph({ spacing: { before: 50 } }),
        createBannerTable('LAPORAN PERENCANAAN PRODUKSI MAKAN BERGIZI GRATIS', sppgName, '1E3A8A', 'FFFFFF'),
        new Paragraph({ spacing: { before: 60 } }),

        // A. Informasi Perencanaan (Murni Borderless Sesuai Excel)
        new Paragraph({
            children: [new TextRun({ text: 'A. Informasi Perencanaan', bold: true, size: 17, font: 'Arial', color: '0F172A' })],
            spacing: { before: 40, after: 30 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxTableBorderNone,
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No. Perencanaan Produksi', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: `:  ${data.noWO}`, width: 4175, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: 'Tanggal Distribusi Menu', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: `:  ${data.tglDist}`, width: 4175, bold: true, color: '1E3A8A', size: 14, borders: docxCellBorderNone }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Status Menu', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: `:  ${data.statusMenu}`, width: 4175, bold: true, color: '047857', size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: 'Nama Menu Produksi', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: `:  ${data.namaMenu}`, width: 4175, bold: true, size: 14, borders: docxCellBorderNone }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Database Pangan', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: `:  ${data.dbPangan}`, width: 4175, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: 'Total Sasaran PM', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({ text: `:  ${data.totalPM.toLocaleString('id-ID')} PM (PK: ${data.totalPK.toLocaleString('id-ID')}, PB: ${data.totalPB.toLocaleString('id-ID')})`, width: 4175, bold: true, size: 14, borders: docxCellBorderNone }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rincian Sub Menu', width: 3500, bold: true, size: 14, borders: docxCellBorderNone }),
                        docxCell({
                            width: 11850,
                            colSpan: 3,
                            borders: docxCellBorderNone,
                            children: (data.subMenusList && data.subMenusList.length > 0)
                                ? data.subMenusList.map((sm, idx) => new Paragraph({
                                    spacing: { before: 0, after: 0, line: 180 },
                                    children: [new TextRun({ text: idx === 0 ? `:  ${idx + 1}. ${sm.nama}` : `   ${idx + 1}. ${sm.nama}`, size: 14, font: 'Arial' })],
                                }))
                                : [new Paragraph({ children: [new TextRun({ text: ':  -', size: 14, font: 'Arial' })] })],
                        }),
                    ],
                }),
            ],
        }),
        new Paragraph({ spacing: { before: 40 } }),

        // B. Data Penerima Manfaat Terdistribusi
        new Paragraph({
            children: [new TextRun({ text: 'B. Data Penerima Manfaat Terdistribusi', bold: true, size: 17, font: 'Arial', color: '0F172A' })],
            spacing: { before: 30, after: 20 },
        }),
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 1. Data Penerima Manfaat yang Menerima Menu', bold: true, color: '047857', size: 15, font: 'Arial' })],
            spacing: { before: 10, after: 20 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: colW_T1[0], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Status', width: colW_T1[1], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Nama KPM', width: colW_T1[2], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Kategori', width: colW_T1[3], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Jumlah PK', width: colW_T1[4], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Jumlah PB', width: colW_T1[5], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Total PM', width: colW_T1[6], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: 'Keterangan Alergi (Menu Ini)', width: colW_T1[7], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                    ],
                }),
                ...data.penerimaMenerimaList.map((row, idx) =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: colW_T1[0], align: AlignmentType.CENTER, size: 13 }),
                            docxCell({ text: 'Menerima', width: colW_T1[1], bold: true, align: AlignmentType.CENTER, color: '047857', size: 13 }),
                            docxCell({ text: row.nama_kpm, width: colW_T1[2], bold: true, size: 13 }),
                            docxCell({ text: row.kategori, width: colW_T1[3], align: AlignmentType.CENTER, size: 13 }),
                            docxCell({ text: String(row.jumlah_pk), width: colW_T1[4], align: AlignmentType.CENTER, size: 13 }),
                            docxCell({ text: String(row.jumlah_pb), width: colW_T1[5], align: AlignmentType.CENTER, size: 13 }),
                            docxCell({ text: String(row.total_pm), width: colW_T1[6], bold: true, align: AlignmentType.CENTER, size: 13 }),
                            docxCell({ text: row.alergi_desc || '-', width: colW_T1[7], size: 13 }),
                        ],
                    })
                ),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rekap Total (Menerima):', colSpan: 4, width: colW_T1[0] + colW_T1[1] + colW_T1[2] + colW_T1[3], bold: true, align: AlignmentType.RIGHT, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: String(data.totalPK), width: colW_T1[4], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: String(data.totalPB), width: colW_T1[5], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: `${data.totalPM.toLocaleString('id-ID')} PM`, width: colW_T1[6], bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, color: '15803D', size: 13 }),
                        docxCell({ text: '', width: colW_T1[7], shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 13 }),
                    ],
                }),
            ],
        }),
    ];

    // =========================================================================
    // SHEET 1 - HALAMAN 2
    // =========================================================================
    const sheet1Page2Children = [
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 2. Data Penerima Manfaat yang Tidak Menerima Menu (Libur/Off)', bold: true, color: 'B91C1C', size: 15, font: 'Arial' })],
            spacing: { before: 20, after: 20 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: colW_T1[0], bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: 'Status', width: colW_T1[1], bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: 'Nama KPM', width: colW_T1[2] + colW_T1[3], colSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: 'Jumlah PK', width: colW_T1[4], bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: 'Jumlah PB', width: colW_T1[5], bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: 'Total PM', width: colW_T1[6] + colW_T1[7], colSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                    ],
                }),
                ...(data.penerimaLiburList.length > 0
                    ? data.penerimaLiburList.map((row, idx) =>
                        new TableRow({
                            cantSplit: true,
                            children: [
                                docxCell({ text: String(idx + 1), width: colW_T1[0], align: AlignmentType.CENTER, size: 13 }),
                                docxCell({ text: 'Libur', width: colW_T1[1], bold: true, align: AlignmentType.CENTER, color: 'B91C1C', size: 13 }),
                                docxCell({ text: row.nama_kpm, width: colW_T1[2] + colW_T1[3], colSpan: 2, bold: true, size: 13 }),
                                docxCell({ text: String(row.jumlah_pk), width: colW_T1[4], align: AlignmentType.CENTER, size: 13 }),
                                docxCell({ text: String(row.jumlah_pb), width: colW_T1[5], align: AlignmentType.CENTER, size: 13 }),
                                docxCell({ text: `${row.total_pm} PM`, width: colW_T1[6] + colW_T1[7], colSpan: 2, bold: true, align: AlignmentType.CENTER, size: 13 }),
                            ],
                        })
                    )
                    : [
                        new TableRow({
                            cantSplit: true,
                            children: [
                                docxCell({
                                    text: 'Seluruh KPM menerima pelayanan makanan pada tanggal ini (Nihil kelompok libur).',
                                    colSpan: 7,
                                    width: 15350,
                                    align: AlignmentType.CENTER,
                                    italic: true,
                                    color: '64748B',
                                    size: 13,
                                }),
                            ],
                        }),
                    ]
                ),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rekap Total (Tidak Menerima):', colSpan: 4, width: colW_T1[0] + colW_T1[1] + colW_T1[2] + colW_T1[3], bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: '0', width: colW_T1[4], bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: '0', width: colW_T1[5], bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                        docxCell({ text: '0 PM', width: colW_T1[6] + colW_T1[7], colSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 }),
                    ],
                }),
            ],
        }),
        new Paragraph({ spacing: { before: 50 } }),

        // Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi', bold: true, color: '0F172A', size: 15, font: 'Arial' })],
            spacing: { before: 20, after: 20 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Sasaran Normal', colSpan: 2, width: Math.floor(15350 / (1 + data.menuAllergenTypes.length)), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                        ...data.menuAllergenTypes.map(al =>
                            docxCell({ text: `Alergi ${al}`, colSpan: 2, width: Math.floor(15350 / (1 + data.menuAllergenTypes.length)), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, color: 'B91C1C', size: 13 })
                        ),
                    ],
                }),
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'PK', width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: 'PB', width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 13 }),
                        ...data.menuAllergenTypes.flatMap(() => [
                            docxCell({ text: 'PK', width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 13 }),
                            docxCell({ text: 'PB', width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 13 }),
                        ]),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: String(data.totalSasaranNormalPK), width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, size: 14 }),
                        docxCell({ text: String(data.totalSasaranNormalPB), width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, size: 14 }),
                        ...data.menuAllergenTypes.flatMap(al => {
                            const pk = data.sasaranAlergiPerType[al]?.pk || 0;
                            const pb = data.sasaranAlergiPerType[al]?.pb || 0;
                            return [
                                docxCell({ text: String(pk), width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, color: pk > 0 ? 'B91C1C' : undefined, size: 14 }),
                                docxCell({ text: String(pb), width: Math.floor(15350 / ((1 + data.menuAllergenTypes.length) * 2)), bold: true, align: AlignmentType.CENTER, color: pb > 0 ? 'B91C1C' : undefined, size: 14 }),
                            ];
                        }),
                    ],
                }),
            ],
        }),
        new Paragraph({ spacing: { before: 50 } }),

        // Tabel 4. Batas Pagu Anggaran Operasional dan Bahan Pangan
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 4. Batas Pagu Anggaran Operasional dan Bahan Pangan', bold: true, color: '0F172A', size: 15, font: 'Arial' })],
            spacing: { before: 20, after: 20 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 700, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                        docxCell({ text: 'Kategori Sasaran PM', width: 5500, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                        docxCell({ text: 'Alokasi Sasaran', width: 1400, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                        docxCell({ text: 'Pagu Satuan (Rp)', width: 2900, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                        docxCell({ text: 'Rumus / Estimasi', width: 1650, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                        docxCell({ text: 'Total Pagu Anggaran (Rp)', width: 3200, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: '1', width: 700, align: AlignmentType.CENTER, size: 13 }),
                        docxCell({ text: 'Porsi Kecil (PK - PAUD, TK, RA, SD 1-3)', width: 5500, bold: true, size: 13 }),
                        docxCell({ text: `${data.totalPK.toLocaleString('id-ID')} PM`, width: 1400, align: AlignmentType.CENTER, size: 13 }),
                        docxCell({ text: formatRupiahNum(data.paguRatePK), width: 2900, align: AlignmentType.RIGHT, size: 13 }),
                        docxCell({ text: `${data.totalPK.toLocaleString('id-ID')} Ã— Rp 8.000`, width: 1650, align: AlignmentType.CENTER, size: 13 }),
                        docxCell({ text: formatRupiahNum(data.paguNominalPK), width: 3200, bold: true, align: AlignmentType.RIGHT, size: 13 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: '2', width: 700, align: AlignmentType.CENTER, size: 13 }),
                        docxCell({ text: 'Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)', width: 5500, bold: true, size: 13 }),
                        docxCell({ text: `${data.totalPB.toLocaleString('id-ID')} PM`, width: 1400, align: AlignmentType.CENTER, size: 13 }),
                        docxCell({ text: formatRupiahNum(data.paguRatePB), width: 2900, align: AlignmentType.RIGHT, size: 13 }),
                        docxCell({ text: `${data.totalPB.toLocaleString('id-ID')} Ã— Rp 10.000`, width: 1650, align: AlignmentType.CENTER, size: 13 }),
                        docxCell({ text: formatRupiahNum(data.paguNominalPB), width: 3200, bold: true, align: AlignmentType.RIGHT, size: 13 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Total Batas Pagu Anggaran MBG:', colSpan: 2, width: 6200, bold: true, align: AlignmentType.RIGHT, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: `${data.totalPM.toLocaleString('id-ID')} PM`, width: 1400, bold: true, align: AlignmentType.CENTER, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: '', width: 2900, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: 'Pagu PK + Pagu PB', width: 1650, align: AlignmentType.CENTER, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: formatRupiahNum(data.paguTotal), width: 3200, bold: true, align: AlignmentType.RIGHT, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 13 }),
                    ],
                }),
            ],
        }),
    ];

    // =========================================================================
    // SHEET 2 - HALAMAN 3: PEMILIHAN BAHAN PANGAN (17 KOLOM)
    // =========================================================================
    const colW_A = [400, 1600, 2200, 1550, 550, 750, 850, 750, 750, 780, 780, 550, 550, 920, 1100, 1250, 800];

    const sheet2Page1Children = [
        createKopDocxTable(),
        new Paragraph({ spacing: { before: 50 } }),
        createBannerTable('LAPORAN FORMULA MAKANAN & KEBUTUHAN BELANJA MBG', sppgName, '047857', 'FFFFFF'),
        new Paragraph({ spacing: { before: 60 } }),

        // A. Pemilihan Bahan Pangan
        new Paragraph({
            children: [new TextRun({ text: 'A. Pemilihan Bahan Pangan', bold: true, size: 17, font: 'Arial', color: '0F172A' })],
            spacing: { before: 40, after: 30 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: colW_A[0], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Sub Menu', width: colW_A[1], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Bahan Master', width: colW_A[2], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Nama PO', width: colW_A[3], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Sat', width: colW_A[4], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Jenis', width: colW_A[5], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Peruntukan', width: colW_A[6], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Berat Bersih (g)', colSpan: 2, width: colW_A[7] + colW_A[8], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Berat Kotor (kg)', colSpan: 2, width: colW_A[9] + colW_A[10], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'BDD', width: colW_A[11], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Buf', width: colW_A[12], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PO (Kg)', width: colW_A[13], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Harga Satuan', width: colW_A[14], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Total Biaya', width: colW_A[15], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Kandungan Gizi', width: colW_A[16], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    ],
                }),
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'PK', width: colW_A[7], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PB', width: colW_A[8], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PK', width: colW_A[9], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PB', width: colW_A[10], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    ],
                }),
                ...data.formattedItems.map((item, idx) =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: colW_A[0], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: item.sub_menu, width: colW_A[1], bold: true, size: 12 }),
                            docxCell({ text: item.bahan_master, width: colW_A[2], bold: true, size: 12 }),
                            docxCell({ text: item.nama_po, width: colW_A[3], size: 12 }),
                            docxCell({ text: item.satuan, width: colW_A[4], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: item.jenis, width: colW_A[5], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: item.peruntukan, width: colW_A[6], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: String(item.gram_pk), width: colW_A[7], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: String(item.gram_pb), width: colW_A[8], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: Number(item.gross_kg_pk.toFixed(2)), width: colW_A[9], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: Number(item.gross_kg_pb.toFixed(2)), width: colW_A[10], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: String(item.bdd), width: colW_A[11], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: String(item.buffer), width: colW_A[12], align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: Number(item.total_gross.toFixed(2)), width: colW_A[13], bold: true, align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: formatRupiahNum(item.harga), width: colW_A[14], align: AlignmentType.RIGHT, size: 12 }),
                            docxCell({ text: formatRupiahNum(item.subtotal), width: colW_A[15], bold: true, align: AlignmentType.RIGHT, size: 12 }),
                            docxCell({ text: (item.keterangan || '-'), width: colW_A[16], size: 11 }),
                        ],
                    })
                ),
            ],
        }),
        new Paragraph({ spacing: { before: 30 } }),

        // Rekapitulasi Kebutuhan Belanja
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Batas Pagu Anggaran Bahan Makanan:', colSpan: 13, width: 10900, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: formatRupiahNum(data.paguBahanTotal), colSpan: 4, width: 4450, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Total Belanja Bahan Makanan (PO):', colSpan: 13, width: 10900, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                        docxCell({ text: formatRupiahNum(data.totalBelanjaPO), colSpan: 4, width: 4450, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, color: '1E3A8A', size: 13 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Selisih Anggaran Bahan Makanan:', colSpan: 13, width: 10900, bold: true, align: AlignmentType.RIGHT, shading: { fill: data.selisihPagu >= 0 ? 'DCFCE7' : 'FEE2E2', type: ShadingType.CLEAR }, color: data.selisihPagu >= 0 ? '15803D' : 'B91C1C', size: 13 }),
                        docxCell({ text: `${formatRupiahNum(Math.abs(data.selisihPagu))} (${data.selisihPagu >= 0 ? 'Surplus' : 'Defisit'})`, colSpan: 4, width: 4450, bold: true, align: AlignmentType.RIGHT, shading: { fill: data.selisihPagu >= 0 ? 'DCFCE7' : 'FEE2E2', type: ShadingType.CLEAR }, color: data.selisihPagu >= 0 ? '15803D' : 'B91C1C', size: 13 }),
                    ],
                }),
            ],
        }),
    ];

    // =========================================================================
    // SHEET 2 - HALAMAN 4: FOOD COST, KANDUNGAN GIZI & LEMBAR PENGESAHAN TTD
    // =========================================================================
    const ttdTable = new Table({
        width: { size: 15350, type: WidthType.DXA },
        borders: docxTableBorderNone,
        rows: [
            new TableRow({
                cantSplit: true,
                children: [
                    new TableCell({
                        width: { size: 5116, type: WidthType.DXA },
                        borders: docxCellBorderNone,
                        children: [
                            new Paragraph({ children: [new TextRun({ text: 'Direncanakan Oleh:', size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            new Paragraph({ children: [new TextRun({ text: 'Tim Ahli Gizi SPPG', bold: true, size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            new Paragraph({ text: '', spacing: { before: 200 } }),
                            new Paragraph({ children: [new TextRun({ text: '( ............................................ )', bold: true, size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                        ],
                    }),
                    new TableCell({
                        width: { size: 5117, type: WidthType.DXA },
                        borders: docxCellBorderNone,
                        children: [
                            new Paragraph({ children: [new TextRun({ text: 'Diperiksa Oleh:', size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            new Paragraph({ children: [new TextRun({ text: 'Petugas Keuangan / Akuntan', bold: true, size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            new Paragraph({ text: '', spacing: { before: 200 } }),
                            new Paragraph({ children: [new TextRun({ text: '( ............................................ )', bold: true, size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                        ],
                    }),
                    new TableCell({
                        width: { size: 5117, type: WidthType.DXA },
                        borders: docxCellBorderNone,
                        children: [
                            new Paragraph({ children: [new TextRun({ text: 'Mengetahui & Menyetujui:', size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            new Paragraph({ children: [new TextRun({ text: 'Kepala SPPG', bold: true, size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            new Paragraph({ text: '', spacing: { before: 200 } }),
                            new Paragraph({ children: [new TextRun({ text: '( ............................................ )', bold: true, size: 14, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                        ],
                    }),
                ],
            }),
        ],
    });

    const foodCostNormalTable = new Table({
        width: { size: 15350, type: WidthType.DXA },
        borders: docxBorderThin,
        rows: [
            new TableRow({
                tableHeader: true,
                cantSplit: true,
                children: [
                    docxCell({ text: 'No', width: 600, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Sub Menu', width: 4550, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Jumlah Bahan Baku', width: 2200, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Food Cost (Rp)', colSpan: 2, width: 4000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Persentase (%)', colSpan: 2, width: 4000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                ],
            }),
            new TableRow({
                tableHeader: true,
                cantSplit: true,
                children: [
                    docxCell({ text: 'PK', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'PB', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'PK', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'PB', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                ],
            }),
            ...data.foodCostNormalList.map((row, idx) =>
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: String(idx + 1), width: 600, align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: row.sub_menu, width: 4550, bold: true, size: 12 }),
                        docxCell({ text: `${row.count} Bahan`, width: 2200, align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: formatRupiahNum(row.cost_pk), width: 2000, align: AlignmentType.RIGHT, size: 12 }),
                        docxCell({ text: formatRupiahNum(row.cost_pb), width: 2000, align: AlignmentType.RIGHT, size: 12 }),
                        docxCell({ text: String(row.percent_pk.toFixed(1)), width: 2000, align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: String(row.percent_pb.toFixed(1)), width: 2000, align: AlignmentType.CENTER, size: 12 }),
                    ],
                })
            ),
            new TableRow({
                cantSplit: true,
                children: [
                    docxCell({ text: 'Rekap Total Food Cost Normal:', colSpan: 3, width: 7350, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: formatRupiahNum(data.totalFcPKNormal), width: 2000, bold: true, align: AlignmentType.RIGHT, color: '0369A1', shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: formatRupiahNum(data.totalFcPBNormal), width: 2000, bold: true, align: AlignmentType.RIGHT, color: '0369A1', shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: '100', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: '100', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                ],
            }),
        ],
    });

    const allergenFoodCostParagraphsAndTables = data.allergenFoodCostTables.flatMap((alt, tIdx) => [
        new Paragraph({
            children: [new TextRun({ text: `Tabel ${tIdx + 2}: Rincian Food Cost Per Sub Menu Alergi ${alt.jenis_alergi}`, bold: true, color: 'B91C1C', size: 14, font: 'Arial' })],
            spacing: { before: 20, after: 15 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 600, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Sub Menu', width: 4550, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Jumlah Bahan Baku', width: 2200, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Food Cost (Rp)', colSpan: 2, width: 4000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'Persentase (%)', colSpan: 2, width: 4000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                    ],
                }),
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'PK', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PB', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PK', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: 'PB', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                    ],
                }),
                ...alt.list.map((row, idx) =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: 600, align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: row.sub_menu, width: 4550, bold: true, size: 12 }),
                            docxCell({ text: `${row.count} Bahan`, width: 2200, align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: formatRupiahNum(row.cost_pk), width: 2000, align: AlignmentType.RIGHT, size: 12 }),
                            docxCell({ text: formatRupiahNum(row.cost_pb), width: 2000, align: AlignmentType.RIGHT, size: 12 }),
                            docxCell({ text: String(row.percent_pk.toFixed(1)), width: 2000, align: AlignmentType.CENTER, size: 12 }),
                            docxCell({ text: String(row.percent_pb.toFixed(1)), width: 2000, align: AlignmentType.CENTER, size: 12 }),
                        ],
                    })
                ),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: `Rekap Total Food Cost Alergi ${alt.jenis_alergi}:`, colSpan: 3, width: 7350, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: formatRupiahNum(alt.total_pk), width: 2000, bold: true, align: AlignmentType.RIGHT, color: 'B91C1C', shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: formatRupiahNum(alt.total_pb), width: 2000, bold: true, align: AlignmentType.RIGHT, color: 'B91C1C', shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: '100', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: '100', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 12 }),
                    ],
                }),
            ],
        }),
    ]);

    // 8 Kolom Tabel Kandungan Gizi: No (600), Peruntukan Porsi (3150), Jenis PM (1200), Energi (2100), Protein (2100), Lemak (2100), Karbo (2100), Serat (2000) = 15350 DXA
    const colW_Gizi = [600, 3150, 1200, 2100, 2100, 2100, 2100, 2000];

    const kandunganGiziTable = new Table({
        width: { size: 15350, type: WidthType.DXA },
        borders: docxBorderThin,
        rows: [
            new TableRow({
                tableHeader: true,
                cantSplit: true,
                children: [
                    docxCell({ text: 'No', width: colW_Gizi[0], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Peruntukan Porsi', width: colW_Gizi[1], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Jenis PM', width: colW_Gizi[2], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Energi (kkal)', width: colW_Gizi[3], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Protein (g)', width: colW_Gizi[4], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Lemak (g)', width: colW_Gizi[5], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Karbohidrat (g)', width: colW_Gizi[6], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                    docxCell({ text: 'Serat (g)', width: colW_Gizi[7], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 12 }),
                ],
            }),
            ...data.giziList.map((g) =>
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: g.rowSpan > 0 ? String(g.no) : '', width: colW_Gizi[0], rowSpan: g.rowSpan > 0 ? g.rowSpan : 1, align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: g.rowSpan > 0 ? g.peruntukan : '', width: colW_Gizi[1], rowSpan: g.rowSpan > 0 ? g.rowSpan : 1, bold: true, size: 12 }),
                        docxCell({ text: g.jenis_pm, width: colW_Gizi[2], bold: true, align: AlignmentType.CENTER, shading: { fill: g.jenis_pm === 'PK' ? 'FEF9C3' : 'E0F2FE', type: ShadingType.CLEAR }, size: 12 }),
                        docxCell({ text: String(g.energi), width: colW_Gizi[3], align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: String(g.protein), width: colW_Gizi[4], align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: String(g.lemak), width: colW_Gizi[5], align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: String(g.karbo), width: colW_Gizi[6], align: AlignmentType.CENTER, size: 12 }),
                        docxCell({ text: String(g.serat), width: colW_Gizi[7], align: AlignmentType.CENTER, size: 12 }),
                    ],
                })
            ),
        ],
    });

    const giziAndTtdSection = [
        new Paragraph({
            children: [new TextRun({ text: 'C. Kandungan Gizi', bold: true, size: 16, font: 'Arial', color: '0F172A' })],
            spacing: { before: 25, after: 15 },
        }),
        new Paragraph({
            children: [new TextRun({ text: 'Tabel: Rincian Kandungan Gizi', bold: true, color: '0F172A', size: 14, font: 'Arial' })],
            spacing: { before: 10, after: 15 },
        }),
        kandunganGiziTable,
        new Paragraph({ spacing: { before: 35 } }),
        ttdTable,
    ];

    // Cek apakah tabel food cost sangat panjang (lebih dari 1 allergen)
    const totalFcRows = (data.foodCostNormalList.length + 3) + 
        data.allergenFoodCostTables.reduce((acc, alt) => acc + alt.list.length + 3, 0);

    const sheet2Page2Children = [
        new Paragraph({
            children: [new TextRun({ text: 'B. Food Cost', bold: true, size: 16, font: 'Arial', color: '0F172A' })],
            spacing: { before: 20, after: 15 },
        }),
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 1. Rincian Food Cost Per Sub Menu Normal', bold: true, color: '047857', size: 14, font: 'Arial' })],
            spacing: { before: 10, after: 15 },
        }),
        foodCostNormalTable,
        ...allergenFoodCostParagraphsAndTables,
    ];

    let sheet2ChildrenCombined = [];
    if (totalFcRows > 12) {
        // Jika tabel food cost panjang, pisahkan Kandungan Gizi + TTD ke halaman 5 bersama-sama sehingga TTD TIDAK SENDIRIAN!
        sheet2ChildrenCombined = [
            ...sheet2Page1Children,
            new Paragraph({ children: [new PageBreak()] }),
            ...sheet2Page2Children,
            new Paragraph({ children: [new PageBreak()] }),
            ...giziAndTtdSection,
        ];
    } else {
        // Jika muat di 4 halaman, satukan Food Cost + Kandungan Gizi + TTD di halaman 4
        sheet2ChildrenCombined = [
            ...sheet2Page1Children,
            new Paragraph({ children: [new PageBreak()] }),
            ...sheet2Page2Children,
            ...giziAndTtdSection,
        ];
    }

    const doc = new Document({
        styles: {
            default: {
                document: {
                    run: { font: 'Arial', size: 14 },
                },
            },
        },
        sections: [
            {
                properties: {
                    page: {
                        size: { orientation: PageOrientation.LANDSCAPE, width: 16838, height: 11906 },
                        margin: { top: 400, bottom: 400, left: 700, right: 700 },
                    },
                },
                children: [
                    ...sheet1Page1Children,
                    new Paragraph({ children: [new PageBreak()] }),
                    ...sheet1Page2Children,
                ],
            },
            {
                properties: {
                    page: {
                        size: { orientation: PageOrientation.LANDSCAPE, width: 16838, height: 11906 },
                        margin: { top: 400, bottom: 400, left: 700, right: 700 },
                    },
                },
                children: sheet2ChildrenCombined,
            },
        ],
    });

    const blob = await Packer.toBlob(doc);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// -------------------------------------------------------------
// 3. GENERATE HTML (UNTUK PRINT & PREVIEW WINDOW)
// -------------------------------------------------------------
export function generateWorkOrderHtml(wo, forPrint = false) {
    const data = buildWorkOrderFullExportData(wo);
    const sppgName = data.sppgName;

    function getKopHtmlSnippet() {
        return `
        <div class="kop-container">
            <div class="kop-spacer"></div>
            <div class="kop-logo-box"><img src="${LOGO_BGN_BASE64}" class="kop-logo" alt="Logo BGN"></div>
            <div class="kop-text">
                <h2>SPPG BULELENG SUKASADA TEGALLINGGAH</h2>
                <h3>YAYASAN PESANTREN MIFTAHUL ULUM</h3>
                <p>Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali</p>
                <p class="kop-contact">E-mail: sppgsukasadategallinggah@gmail.com</p>
            </div>
            <div class="kop-logo-box"><img src="${LOGO_YAYASAN_BASE64}" class="kop-logo" alt="Logo Yayasan"></div>
            <div class="kop-spacer"></div>
        </div>
        <div class="kop-line-double"></div>
        `;
    }

    const rowsT1 = data.penerimaMenerimaList.map((row, idx) => `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="text-align: center; font-weight: bold; color: #047857;">Menerima</td>
            <td style="font-weight: bold;">${row.nama_kpm}</td>
            <td style="text-align: center;">${row.kategori}</td>
            <td style="text-align: center;">${row.jumlah_pk}</td>
            <td style="text-align: center;">${row.jumlah_pb}</td>
            <td style="text-align: center; font-weight: bold;">${row.total_pm}</td>
            <td>${row.alergi_desc || '-'}</td>
        </tr>
    `).join('');

    const rowsT2 = data.penerimaLiburList.length > 0
        ? data.penerimaLiburList.map((row, idx) => `
            <tr>
                <td style="text-align: center;">${idx + 1}</td>
                <td style="text-align: center; font-weight: bold; color: #b91c1c;">Libur</td>
                <td colspan="2" style="font-weight: bold;">${row.nama_kpm}</td>
                <td style="text-align: center;">${row.jumlah_pk}</td>
                <td style="text-align: center;">${row.jumlah_pb}</td>
                <td colspan="2" style="text-align: center; font-weight: bold;">${row.total_pm} PM</td>
            </tr>
        `).join('')
        : `
            <tr>
                <td colspan="7" style="text-align: center; font-style: italic; color: #64748b; padding: 6px;">Seluruh KPM menerima pelayanan makanan pada tanggal ini (Nihil kelompok libur).</td>
            </tr>
        `;

    const thAllergenCols1 = data.menuAllergenTypes.map(al => `<th colspan="2" style="text-align: center; color: #b91c1c;">Alergi ${al}</th>`).join('');
    const thAllergenCols2 = data.menuAllergenTypes.map(() => `<th style="text-align: center;">PK</th><th style="text-align: center;">PB</th>`).join('');
    const tdAllergenVals = data.menuAllergenTypes.map(al => {
        const pk = data.sasaranAlergiPerType[al]?.pk || 0;
        const pb = data.sasaranAlergiPerType[al]?.pb || 0;
        return `<td style="text-align: center; font-weight: bold; ${pk > 0 ? 'color: #b91c1c;' : ''}">${pk}</td><td style="text-align: center; font-weight: bold; ${pb > 0 ? 'color: #b91c1c;' : ''}">${pb}</td>`;
    }).join('');

    const rowsItems = data.formattedItems.map((item, idx) => `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="font-weight: bold;">${item.sub_menu}</td>
            <td style="font-weight: bold;">${item.bahan_master}</td>
            <td>${item.nama_po}</td>
            <td style="text-align: center;">${item.satuan}</td>
            <td style="text-align: center;">${item.jenis}</td>
            <td style="text-align: center;">${item.peruntukan}</td>
            <td style="text-align: center;">${item.gram_pk}</td>
            <td style="text-align: center;">${item.gram_pb}</td>
            <td style="text-align: center;">${Number(item.gross_kg_pk.toFixed(2))}</td>
            <td style="text-align: center;">${Number(item.gross_kg_pb.toFixed(2))}</td>
            <td style="text-align: center;">${item.bdd}</td>
            <td style="text-align: center;">${item.buffer}</td>
            <td style="text-align: center; font-weight: bold;">${Number(item.total_gross.toFixed(2))}</td>
            <td style="text-align: right;">${formatRupiahNum(item.harga)}</td>
            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(item.subtotal)}</td>
            <td style="font-size: 6.5pt;">${item.keterangan || '-'}</td>
        </tr>
    `).join('');

    const rowsFcNormal = data.foodCostNormalList.map((row, idx) => `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="font-weight: bold;">${row.sub_menu}</td>
            <td style="text-align: center;">${row.count} Bahan</td>
            <td style="text-align: right;">${formatRupiahNum(row.cost_pk)}</td>
            <td style="text-align: right;">${formatRupiahNum(row.cost_pb)}</td>
            <td style="text-align: center;">${row.percent_pk.toFixed(1)}</td>
            <td style="text-align: center;">${row.percent_pb.toFixed(1)}</td>
        </tr>
    `).join('');

    const allergenFcTablesHtml = data.allergenFoodCostTables.map((alt, tIdx) => {
        const altRows = alt.list.map((row, idx) => `
            <tr>
                <td style="text-align: center;">${idx + 1}</td>
                <td style="font-weight: bold;">${row.sub_menu}</td>
                <td style="text-align: center;">${row.count} Bahan</td>
                <td style="text-align: right;">${formatRupiahNum(row.cost_pk)}</td>
                <td style="text-align: right;">${formatRupiahNum(row.cost_pb)}</td>
                <td style="text-align: center;">${row.percent_pk.toFixed(1)}</td>
                <td style="text-align: center;">${row.percent_pb.toFixed(1)}</td>
            </tr>
        `).join('');

        return `
        <div style="margin-top: 10px;">
            <p style="margin: 0 0 3px; font-weight: bold; color: #b91c1c;">Tabel ${tIdx + 2}: Rincian Food Cost Per Sub Menu Alergi ${alt.jenis_alergi}</p>
            <table>
                <thead>
                    <tr style="background-color: #fee2e2; color: #b91c1c;">
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2">Sub Menu</th>
                        <th rowspan="2" style="width: 120px;">Jumlah Bahan Baku</th>
                        <th colspan="2" style="text-align: center;">Food Cost (Rp)</th>
                        <th colspan="2" style="text-align: center;">Persentase (%)</th>
                    </tr>
                    <tr style="background-color: #fee2e2; color: #b91c1c;">
                        <th style="width: 110px; text-align: center;">PK</th>
                        <th style="width: 110px; text-align: center;">PB</th>
                        <th style="width: 75px; text-align: center;">PK</th>
                        <th style="width: 75px; text-align: center;">PB</th>
                    </tr>
                </thead>
                <tbody>${altRows}</tbody>
                <tfoot>
                    <tr style="background-color: #fee2e2; font-weight: bold;">
                        <td colspan="3" style="text-align: right;">Rekap Total Food Cost Alergi ${alt.jenis_alergi}:</td>
                        <td style="text-align: right; color: #b91c1c;">${formatRupiahNum(alt.total_pk)}</td>
                        <td style="text-align: right; color: #b91c1c;">${formatRupiahNum(alt.total_pb)}</td>
                        <td style="text-align: center;">100</td>
                        <td style="text-align: center;">100</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        `;
    }).join('');

    const rowsGizi = data.giziList.map((g) => `
        <tr>
            ${g.rowSpan > 0 ? `<td rowspan="${g.rowSpan}" style="text-align: center;">${g.no}</td>` : ''}
            ${g.rowSpan > 0 ? `<td rowspan="${g.rowSpan}" style="font-weight: bold;">${g.peruntukan}</td>` : ''}
            <td style="text-align: center; font-weight: bold; background-color: ${g.jenis_pm === 'PK' ? '#fef9c3' : '#e0f2fe'};">${g.jenis_pm}</td>
            <td style="text-align: center;">${g.energi}</td>
            <td style="text-align: center;">${g.protein}</td>
            <td style="text-align: center;">${g.lemak}</td>
            <td style="text-align: center;">${g.karbo}</td>
            <td style="text-align: center;">${g.serat}</td>
        </tr>
    `).join('');

    return `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Laporan Work Order - ${data.noWO}</title>
            <style>
                @page { size: A4 landscape; margin: 8mm; }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 8pt;
                    color: #0f172a;
                    background: #ffffff;
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .pdf-page-container {
                    width: 100%;
                    page-break-after: always;
                    box-sizing: border-box;
                    padding-bottom: 5px;
                }
                .pdf-page-container:last-child {
                    page-break-after: auto;
                }
                .kop-container {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding-bottom: 4px;
                }
                .kop-spacer { width: 40px; flex-shrink: 0; }
                .kop-logo-box { width: 55px; text-align: center; flex-shrink: 0; }
                .kop-logo { width: 46px; height: 46px; object-fit: contain; }
                .kop-text { flex: 1; text-align: center; padding: 0 10px; }
                .kop-text h2 { margin: 0; font-size: 11pt; font-weight: bold; color: #000; letter-spacing: 0.5px; }
                .kop-text h3 { margin: 2px 0; font-size: 10pt; font-weight: bold; color: #000; }
                .kop-text p { margin: 1px 0; font-size: 7.5pt; color: #334155; }
                .kop-line-double {
                    border-top: 2px solid #000;
                    border-bottom: 1px solid #000;
                    height: 2px;
                    margin: 2px 0 8px 0;
                }
                .banner {
                    color: #ffffff;
                    text-align: center;
                    padding: 6px 10px;
                    border-radius: 4px;
                    margin-bottom: 8px;
                }
                .banner h1 { margin: 0; font-size: 10pt; font-weight: bold; letter-spacing: 0.5px; }
                .banner h2 { margin: 2px 0 0; font-size: 8.5pt; font-weight: bold; }
                .sec-head {
                    font-size: 9pt;
                    font-weight: bold;
                    color: #0f172a;
                    margin: 6px 0 3px;
                }
                .info-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 6px;
                }
                .info-table td {
                    border: none !important;
                    padding: 2px 4px;
                    font-size: 7.5pt;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 6px;
                }
                th, td {
                    border: 1px solid #cbd5e1;
                    padding: 2.5px 3.5px;
                    font-size: 7.2pt;
                }
                th {
                    font-weight: bold;
                }
                .ttd {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 15px;
                    text-align: center;
                }
                .ttd-box { width: 30%; }
                .ttd-box p { margin: 0; font-size: 7.5pt; }
                .ttd-line { margin-top: 35px; font-weight: bold; font-size: 7.5pt; }
            </style>
        </head>
        <body>
            <div class="pdf-page-container">
                ${getKopHtmlSnippet()}
                <div class="banner" style="background-color: #1e3a8a;">
                    <h1>LAPORAN PERENCANAAN PRODUKSI MAKAN BERGIZI GRATIS</h1>
                    <h2>${sppgName}</h2>
                </div>
                <div class="sec-head">A. Informasi Perencanaan</div>
                <table class="info-table">
                    <tr>
                        <td style="width: 22%; font-weight: bold;">No. Perencanaan Produksi</td>
                        <td style="width: 28%; font-weight: bold;">:  ${data.noWO}</td>
                        <td style="width: 22%; font-weight: bold;">Tanggal Distribusi Menu</td>
                        <td style="width: 28%; font-weight: bold; color: #1e3a8a;">:  ${data.tglDist}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Status Menu</td>
                        <td><strong style="color: #047857;">:  ${data.statusMenu}</strong></td>
                        <td style="font-weight: bold;">Nama Menu Produksi</td>
                        <td><strong>:  ${data.namaMenu}</strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Database Pangan</td>
                        <td>:  ${data.dbPangan}</td>
                        <td style="font-weight: bold;">Total Sasaran PM</td>
                        <td><strong>:  ${data.totalPM.toLocaleString('id-ID')} PM</strong> (PK: ${data.totalPK.toLocaleString('id-ID')}, PB: ${data.totalPB.toLocaleString('id-ID')})</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; vertical-align: top;">Rincian Sub Menu</td>
                        <td colspan="3" style="vertical-align: top; line-height: 1.3;">
                            ${data.subMenusList && data.subMenusList.length > 0 
                                ? data.subMenusList.map((sm, idx) => `<div>${idx === 0 ? ':  ' : '&nbsp;&nbsp;&nbsp;'}${idx + 1}. ${sm.nama}</div>`).join('') 
                                : ':  -'}
                        </td>
                    </tr>
                </table>
                <div class="sec-head">B. Data Penerima Manfaat Terdistribusi</div>
                <p style="margin: 0 0 3px; font-weight: bold; color: #047857;">Tabel 1. Data Penerima Manfaat yang Menerima Menu</p>
                <table>
                    <thead>
                        <tr style="background-color: #dcfce7; color: #15803d;">
                            <th style="width: 4%;">No</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 28%;">Nama KPM</th>
                            <th style="width: 8%;">Kategori</th>
                            <th style="width: 9%;">Jumlah PK</th>
                            <th style="width: 9%;">Jumlah PB</th>
                            <th style="width: 9%;">Total PM</th>
                            <th style="width: 25%;">Keterangan Alergi (Menu Ini)</th>
                        </tr>
                    </thead>
                    <tbody>${rowsT1}</tbody>
                    <tfoot>
                        <tr style="background-color: #dcfce7; font-weight: bold; color: #15803d;">
                            <td colspan="4" style="text-align: right;">Rekap Total (Menerima):</td>
                            <td style="text-align: center;">${data.totalPK}</td>
                            <td style="text-align: center;">${data.totalPB}</td>
                            <td style="text-align: center;">${data.totalPM.toLocaleString('id-ID')} PM</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="pdf-page-container">
                <p style="margin: 0 0 3px; font-weight: bold; color: #b91c1c;">Tabel 2. Data Penerima Manfaat yang Tidak Menerima Menu (Libur/Off)</p>
                <table>
                    <thead>
                        <tr style="background-color: #fee2e2; color: #b91c1c;">
                            <th style="width: 4%;">No</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 36%;">Nama KPM</th>
                            <th style="width: 9%;">Jumlah PK</th>
                            <th style="width: 9%;">Jumlah PB</th>
                            <th colspan="2" style="width: 34%;">Total PM</th>
                        </tr>
                    </thead>
                    <tbody>${rowsT2}</tbody>
                    <tfoot>
                        <tr style="background-color: #fee2e2; font-weight: bold; color: #b91c1c;">
                            <td colspan="3" style="text-align: right;">Rekap Total (Tidak Menerima):</td>
                            <td style="text-align: center;">0</td>
                            <td style="text-align: center;">0</td>
                            <td colspan="2" style="text-align: center;">0 PM</td>
                        </tr>
                    </tfoot>
                </table>
                <div class="sec-head">Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi</div>
                <table>
                    <thead>
                        <tr style="background-color: #f1f5f9;">
                            <th colspan="2" style="text-align: center;">Sasaran Normal</th>
                            ${thAllergenCols1}
                        </tr>
                        <tr style="background-color: #f8fafc;">
                            <th style="text-align: center;">PK</th>
                            <th style="text-align: center;">PB</th>
                            ${thAllergenCols2}
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="text-align: center; font-weight: bold;">
                            <td>${data.totalSasaranNormalPK}</td>
                            <td>${data.totalSasaranNormalPB}</td>
                            ${tdAllergenVals}
                        </tr>
                    </tbody>
                </table>
                <div class="sec-head">Tabel 4. Batas Pagu Anggaran Operasional dan Bahan Pangan</div>
                <table>
                    <thead>
                        <tr style="background-color: #dbeafe; color: #1e3a8a;">
                            <th style="width: 4%;">No</th>
                            <th style="width: 36%;">Kategori Sasaran PM</th>
                            <th style="width: 14%;">Alokasi Sasaran</th>
                            <th style="width: 16%;">Pagu Satuan (Rp)</th>
                            <th style="width: 12%;">Rumus / Estimasi</th>
                            <th style="width: 18%;">Total Pagu Anggaran (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">1</td>
                            <td style="font-weight: bold;">Porsi Kecil (PK - PAUD, TK, RA, SD 1-3)</td>
                            <td style="text-align: center;">${data.totalPK.toLocaleString('id-ID')} PM</td>
                            <td style="text-align: right;">${formatRupiahNum(data.paguRatePK)}</td>
                            <td style="text-align: center;">${data.totalPK.toLocaleString('id-ID')} Ã— Rp 8.000</td>
                            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(data.paguNominalPK)}</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">2</td>
                            <td style="font-weight: bold;">Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)</td>
                            <td style="text-align: center;">${data.totalPB.toLocaleString('id-ID')} PM</td>
                            <td style="text-align: right;">${formatRupiahNum(data.paguRatePB)}</td>
                            <td style="text-align: center;">${data.totalPB.toLocaleString('id-ID')} Ã— Rp 10.000</td>
                            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(data.paguNominalPB)}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #dbeafe; font-weight: bold; color: #1e3a8a;">
                            <td colspan="2" style="text-align: right;">Total Batas Pagu Anggaran MBG:</td>
                            <td style="text-align: center;">${data.totalPM.toLocaleString('id-ID')} PM</td>
                            <td></td>
                            <td style="text-align: center;">Pagu PK + Pagu PB</td>
                            <td style="text-align: right;">${formatRupiahNum(data.paguTotal)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="pdf-page-container">
                ${getKopHtmlSnippet()}
                <div class="banner" style="background-color: #047857;">
                    <h1>LAPORAN FORMULA MAKANAN & KEBUTUHAN BELANJA MBG</h1>
                    <h2>${sppgName}</h2>
                </div>
                <div class="sec-head">A. Pemilihan Bahan Pangan</div>
                <table>
                    <thead>
                        <tr style="background-color: #f1f5f9;">
                            <th rowspan="2" style="width: 25px;">No</th>
                            <th rowspan="2">Sub Menu</th>
                            <th rowspan="2">Bahan Master</th>
                            <th rowspan="2">Nama PO</th>
                            <th rowspan="2" style="width: 28px;">Sat</th>
                            <th rowspan="2" style="width: 50px;">Jenis</th>
                            <th rowspan="2" style="width: 55px;">Peruntukan</th>
                            <th colspan="2" style="text-align: center;">Berat Bersih (g)</th>
                            <th colspan="2" style="text-align: center;">Berat Kotor (kg)</th>
                            <th rowspan="2" style="width: 32px;">BDD</th>
                            <th rowspan="2" style="width: 32px;">Buf</th>
                            <th rowspan="2" style="width: 48px;">PO (Kg)</th>
                            <th rowspan="2" style="width: 65px; text-align: right;">Harga Satuan</th>
                            <th rowspan="2" style="width: 75px; text-align: right;">Total Biaya</th>
                            <th rowspan="2" style="width: 170px;">Kandungan Gizi Utama</th>
                        </tr>
                        <tr style="background-color: #f1f5f9;">
                            <th style="width: 40px; text-align: center;">PK</th>
                            <th style="width: 40px; text-align: center;">PB</th>
                            <th style="width: 40px; text-align: center;">PK</th>
                            <th style="width: 40px; text-align: center;">PB</th>
                        </tr>
                    </thead>
                    <tbody>${rowsItems}</tbody>
                </table>
                <table>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td style="text-align: right;">Batas Pagu Anggaran Bahan Makanan:</td>
                        <td style="width: 160px; text-align: right;">${formatRupiahNum(data.paguBahanTotal)}</td>
                        <td style="width: 45%; color: #64748b; font-style: italic;">Berdasarkan alokasi pagu bahan pangan</td>
                    </tr>
                    <tr style="background-color: #dbeafe; font-weight: bold; color: #1e3a8a;">
                        <td style="text-align: right;">Total Belanja Bahan Makanan (PO):</td>
                        <td style="width: 160px; text-align: right;">${formatRupiahNum(data.totalBelanjaPO)}</td>
                        <td style="width: 45%; font-style: italic;">Total akumulasi biaya pembelanjaan PO</td>
                    </tr>
                    <tr style="background-color: ${data.selisihPagu >= 0 ? '#dcfce7' : '#fee2e2'}; font-weight: bold; color: ${data.selisihPagu >= 0 ? '#15803d' : '#b91c1c'};">
                        <td style="text-align: right;">Selisih Anggaran Bahan Makanan:</td>
                        <td style="width: 160px; text-align: right;">${formatRupiahNum(Math.abs(data.selisihPagu))} (${data.selisihPagu >= 0 ? 'Surplus' : 'Defisit'})</td>
                        <td style="width: 45%; font-style: italic;">${data.selisihPagu >= 0 ? 'Sisa anggaran belanja terkendali' : 'Biaya belanja melebihi pagu anggaran'}</td>
                    </tr>
                </table>
            </div>

            <div class="pdf-page-container">
                <div class="sec-head">B. Food Cost</div>
                <p style="margin: 0 0 3px; font-weight: bold; color: #047857;">Tabel 1. Rincian Food Cost Per Sub Menu Normal</p>
                <table>
                    <thead>
                        <tr style="background-color: #e0f2fe; color: #0369a1;">
                            <th rowspan="2" style="width: 30px;">No</th>
                            <th rowspan="2">Sub Menu</th>
                            <th rowspan="2" style="width: 120px;">Jumlah Bahan Baku</th>
                            <th colspan="2" style="text-align: center;">Food Cost (Rp)</th>
                            <th colspan="2" style="text-align: center;">Persentase (%)</th>
                        </tr>
                        <tr style="background-color: #e0f2fe; color: #0369a1;">
                            <th style="width: 110px; text-align: center;">PK</th>
                            <th style="width: 110px; text-align: center;">PB</th>
                            <th style="width: 75px; text-align: center;">PK</th>
                            <th style="width: 75px; text-align: center;">PB</th>
                        </tr>
                    </thead>
                    <tbody>${rowsFcNormal}</tbody>
                    <tfoot>
                        <tr style="background-color: #e0f2fe; font-weight: bold;">
                            <td colspan="3" style="text-align: right;">Rekap Total Food Cost Normal:</td>
                            <td style="text-align: right; color: #0369a1;">${formatRupiahNum(data.totalFcPKNormal)}</td>
                            <td style="text-align: right; color: #0369a1;">${formatRupiahNum(data.totalFcPBNormal)}</td>
                            <td style="text-align: center;">100</td>
                            <td style="text-align: center;">100</td>
                        </tr>
                    </tfoot>
                </table>
                ${allergenFcTablesHtml}
                <div class="sec-head" style="margin-top: 10px;">C. Kandungan Gizi</div>
                <p style="margin: 0 0 3px; font-weight: bold;">Tabel: Rincian Kandungan Gizi</p>
                <table>
                    <thead>
                        <tr style="background-color: #f1f5f9;">
                            <th style="width: 30px; text-align: center;">No</th>
                            <th style="width: 180px;">Peruntukan Porsi</th>
                            <th style="width: 60px; text-align: center;">Jenis PM</th>
                            <th style="text-align: center;">Energi (kkal)</th>
                            <th style="text-align: center;">Protein (g)</th>
                            <th style="text-align: center;">Lemak (g)</th>
                            <th style="text-align: center;">Karbohidrat (g)</th>
                            <th style="text-align: center;">Serat (g)</th>
                        </tr>
                    </thead>
                    <tbody>${rowsGizi}</tbody>
                </table>
                <div class="ttd">
                    <div class="ttd-box">
                        <p>Direncanakan Oleh:<br><strong>Tim Ahli Gizi SPPG</strong></p>
                        <div class="ttd-line">( ............................................ )</div>
                    </div>
                    <div class="ttd-box">
                        <p>Diperiksa Oleh:<br><strong>Petugas Keuangan / Akuntan</strong></p>
                        <div class="ttd-line">( ............................................ )</div>
                    </div>
                    <div class="ttd-box">
                        <p>Mengetahui & Menyetujui:<br><strong>Kepala SPPG</strong></p>
                        <div class="ttd-line">( ............................................ )</div>
                    </div>
                </div>
            </div>
            ${forPrint ? '<script>window.onload = function() { window.print(); };</script>' : ''}
        </body>
        </html>
    `;
}

// -------------------------------------------------------------
// 4. EXPORT WORK ORDER PDF (.PDF) MURNI TEKS DENGAN JSPDF & AUTOTABLE
// -------------------------------------------------------------
export async function exportWorkOrderPdf(wo) {
    const data = buildWorkOrderFullExportData(wo);
    const sppgName = data.sppgName;
    const filename = `${data.noWO}_${(data.namaMenu || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;

    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4',
    });

    function drawKop(yStart) {
        // Logo BGN kiri
        try {
            doc.addImage(LOGO_BGN_BASE64, 'PNG', 20, yStart, 16, 16);
        } catch (e) {}

        // Teks Kop tengah
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.setTextColor(0, 0, 0);
        doc.text('SPPG BULELENG SUKASADA TEGALLINGGAH', 148.5, yStart + 4, { align: 'center' });
        doc.setFontSize(9.5);
        doc.text('YAYASAN PESANTREN MIFTAHUL ULUM', 148.5, yStart + 8.5, { align: 'center' });
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.text('Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali', 148.5, yStart + 12.5, { align: 'center' });
        doc.text('E-mail: sppgsukasadategallinggah@gmail.com', 148.5, yStart + 16, { align: 'center' });

        // Logo Yayasan kanan
        try {
            doc.addImage(LOGO_YAYASAN_BASE64, 'PNG', 261, yStart, 16, 16);
        } catch (e) {}

        // Double line pembatas
        doc.setDrawColor(0, 0, 0);
        doc.setLineWidth(0.6);
        doc.line(10, yStart + 18, 287, yStart + 18);
        doc.setLineWidth(0.2);
        doc.line(10, yStart + 19, 287, yStart + 19);

        return yStart + 21;
    }

    function drawBanner(title, sub, bgRgb, yStart) {
        doc.setFillColor(bgRgb[0], bgRgb[1], bgRgb[2]);
        doc.rect(10, yStart, 277, 9.5, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.text(title, 148.5, yStart + 4.2, { align: 'center' });
        doc.setFontSize(8);
        doc.text(sub, 148.5, yStart + 8, { align: 'center' });
        doc.setTextColor(0, 0, 0);
        return yStart + 12;
    }

    // =========================================================
    // HALAMAN 1: PERENCANAAN PRODUKSI & PENERIMA MANFAAT
    // =========================================================
    let y = drawKop(7);
    y = drawBanner('LAPORAN PERENCANAAN PRODUKSI MAKAN BERGIZI GRATIS', sppgName, [30, 58, 138], y);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.text('A. Informasi Perencanaan', 10, y + 3);

    autoTable(doc, {
        startY: y + 4.5,
        margin: { left: 10, right: 10 },
        theme: 'plain',
        styles: { font: 'helvetica', fontSize: 7, cellPadding: 0.8, textColor: [15, 23, 42] },
        columnStyles: {
            0: { fontStyle: 'bold', cellWidth: 45 },
            1: { cellWidth: 93 },
            2: { fontStyle: 'bold', cellWidth: 45 },
            3: { cellWidth: 94 },
        },
        body: [
            ['No. Perencanaan Produksi', `:  ${data.noWO}`, 'Tanggal Distribusi Menu', `:  ${data.tglDist}`],
            ['Status Menu', `:  ${data.statusMenu}`, 'Nama Menu Produksi', `:  ${data.namaMenu}`],
            ['Database Pangan', `:  ${data.dbPangan}`, 'Total Sasaran PM', `:  ${data.totalPM.toLocaleString('id-ID')} PM (PK: ${data.totalPK.toLocaleString('id-ID')}, PB: ${data.totalPB.toLocaleString('id-ID')})`],
            ['Rincian Sub Menu', data.subMenusList && data.subMenusList.length > 0
                ? data.subMenusList.map((sm, i) => `${i === 0 ? ':  ' : '   '}${i + 1}. ${sm.nama}`).join('\n')
                : ':  -', '', ''],
        ],
    });

    y = doc.lastAutoTable.finalY + 3;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.text('B. Data Penerima Manfaat Terdistribusi', 10, y);
    doc.setFontSize(7.5);
    doc.setTextColor(4, 120, 87);
    doc.text('Tabel 1. Data Penerima Manfaat yang Menerima Menu', 10, y + 3.5);
    doc.setTextColor(0, 0, 0);

    const bodyT1 = data.penerimaMenerimaList.map((row, idx) => [
        String(idx + 1),
        'Menerima',
        row.nama_kpm,
        row.kategori,
        String(row.jumlah_pk),
        String(row.jumlah_pb),
        String(row.total_pm),
        row.alergi_desc || '-'
    ]);

    autoTable(doc, {
        startY: y + 5,
        margin: { left: 10, right: 10 },
        head: [['No', 'Status', 'Nama KPM', 'Kategori', 'Jumlah PK', 'Jumlah PB', 'Total PM', 'Keterangan Alergi (Menu Ini)']],
        body: bodyT1,
        foot: [['Rekap Total (Menerima):', '', '', '', String(data.totalPK), String(data.totalPB), `${data.totalPM.toLocaleString('id-ID')} PM`, '']],
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 6.8, cellPadding: 1, lineColor: [203, 213, 225], lineWidth: 0.15 },
        headStyles: { fillColor: [220, 252, 231], textColor: [21, 128, 61], fontStyle: 'bold', halign: 'center' },
        footStyles: { fillColor: [220, 252, 231], textColor: [21, 128, 61], fontStyle: 'bold' },
        columnStyles: {
            0: { halign: 'center', cellWidth: 10 },
            1: { halign: 'center', cellWidth: 20, fontStyle: 'bold', textColor: [4, 120, 87] },
            2: { cellWidth: 80, fontStyle: 'bold' },
            3: { halign: 'center', cellWidth: 22 },
            4: { halign: 'center', cellWidth: 22 },
            5: { halign: 'center', cellWidth: 22 },
            6: { halign: 'center', cellWidth: 25, fontStyle: 'bold' },
            7: { cellWidth: 76 }
        }
    });

    // =========================================================
    // HALAMAN 2: LIBUR/OFF, SASARAN PORSI & BATAS PAGU
    // =========================================================
    doc.addPage('a4', 'landscape');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(185, 28, 28);
    doc.text('Tabel 2. Data Penerima Manfaat yang Tidak Menerima Menu (Libur/Off)', 10, 12);
    doc.setTextColor(0, 0, 0);

    const bodyT2 = data.penerimaLiburList.length > 0
        ? data.penerimaLiburList.map((row, idx) => [
            String(idx + 1),
            'Libur',
            row.nama_kpm,
            String(row.jumlah_pk),
            String(row.jumlah_pb),
            `${row.total_pm} PM`
        ])
        : [
            [{ content: 'Seluruh KPM menerima pelayanan makanan pada tanggal ini (Nihil kelompok libur).', colSpan: 6, styles: { halign: 'center', fontStyle: 'italic', textColor: [100, 116, 139] } }]
        ];

    autoTable(doc, {
        startY: 14,
        margin: { left: 10, right: 10 },
        head: [['No', 'Status', 'Nama KPM', 'Jumlah PK', 'Jumlah PB', 'Total PM']],
        body: bodyT2,
        foot: [['Rekap Total (Tidak Menerima):', '', '', '0', '0', '0 PM']],
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.2, lineColor: [203, 213, 225], lineWidth: 0.15 },
        headStyles: { fillColor: [254, 226, 226], textColor: [185, 28, 28], fontStyle: 'bold', halign: 'center' },
        footStyles: { fillColor: [254, 226, 226], textColor: [185, 28, 28], fontStyle: 'bold' },
        columnStyles: {
            0: { halign: 'center', cellWidth: 10 },
            1: { halign: 'center', cellWidth: 20 },
            2: { cellWidth: 127 },
            3: { halign: 'center', cellWidth: 30 },
            4: { halign: 'center', cellWidth: 30 },
            5: { halign: 'center', cellWidth: 60 }
        }
    });

    let yP2 = doc.lastAutoTable.finalY + 5;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.text('Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi', 10, yP2);

    const t3HeadRow1 = [
        { content: 'Sasaran Normal', colSpan: 2 },
        ...data.menuAllergenTypes.map(al => ({ content: `Alergi ${al}`, colSpan: 2 }))
    ];
    const t3HeadRow2 = [
        'PK', 'PB',
        ...data.menuAllergenTypes.flatMap(() => ['PK', 'PB'])
    ];
    const t3BodyRow = [
        String(data.totalSasaranNormalPK),
        String(data.totalSasaranNormalPB),
        ...data.menuAllergenTypes.flatMap(al => [
            String(data.sasaranAlergiPerType[al]?.pk || 0),
            String(data.sasaranAlergiPerType[al]?.pb || 0),
        ])
    ];

    autoTable(doc, {
        startY: yP2 + 2,
        margin: { left: 10, right: 10 },
        head: [t3HeadRow1, t3HeadRow2],
        body: [t3BodyRow],
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 7.2, cellPadding: 1.5, lineColor: [203, 213, 225], lineWidth: 0.15, halign: 'center' },
        headStyles: { fillColor: [241, 245, 249], textColor: [15, 23, 42], fontStyle: 'bold' }
    });

    yP2 = doc.lastAutoTable.finalY + 5;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.text('Tabel 4. Batas Pagu Anggaran Operasional dan Bahan Pangan', 10, yP2);

    autoTable(doc, {
        startY: yP2 + 2,
        margin: { left: 10, right: 10 },
        head: [['No', 'Kategori Sasaran PM', 'Alokasi Sasaran', 'Pagu Satuan (Rp)', 'Rumus / Estimasi', 'Total Pagu Anggaran (Rp)']],
        body: [
            ['1', 'Porsi Kecil (PK - PAUD, TK, RA, SD 1-3)', `${data.totalPK.toLocaleString('id-ID')} PM`, formatRupiahNum(data.paguRatePK), `${data.totalPK.toLocaleString('id-ID')} Ã— Rp 8.000`, formatRupiahNum(data.paguNominalPK)],
            ['2', 'Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)', `${data.totalPB.toLocaleString('id-ID')} PM`, formatRupiahNum(data.paguRatePB), `${data.totalPB.toLocaleString('id-ID')} Ã— Rp 10.000`, formatRupiahNum(data.paguNominalPB)],
        ],
        foot: [['Total Batas Pagu Anggaran MBG:', '', `${data.totalPM.toLocaleString('id-ID')} PM`, '', 'Pagu PK + Pagu PB', formatRupiahNum(data.paguTotal)]],
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 7.2, cellPadding: 1.5, lineColor: [203, 213, 225], lineWidth: 0.15 },
        headStyles: { fillColor: [219, 234, 254], textColor: [30, 58, 138], fontStyle: 'bold' },
        footStyles: { fillColor: [219, 234, 254], textColor: [30, 58, 138], fontStyle: 'bold' },
        columnStyles: {
            0: { halign: 'center', cellWidth: 12 },
            1: { cellWidth: 95, fontStyle: 'bold' },
            2: { halign: 'center', cellWidth: 35 },
            3: { halign: 'right', cellWidth: 40 },
            4: { halign: 'center', cellWidth: 40 },
            5: { halign: 'right', cellWidth: 55, fontStyle: 'bold' }
        }
    });

    // =========================================================
    // HALAMAN 3: PEMILIHAN BAHAN PANGAN (17 KOLOM)
    // =========================================================
    doc.addPage('a4', 'landscape');
    y = drawKop(7);
    y = drawBanner('LAPORAN FORMULA MAKANAN & KEBUTUHAN BELANJA MBG', sppgName, [4, 120, 87], y);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.text('A. Pemilihan Bahan Pangan', 10, y + 3);

    const bpHeadRow1 = [
        { content: 'No', rowSpan: 2 },
        { content: 'Sub Menu', rowSpan: 2 },
        { content: 'Bahan Master', rowSpan: 2 },
        { content: 'Nama PO', rowSpan: 2 },
        { content: 'Sat', rowSpan: 2 },
        { content: 'Jenis', rowSpan: 2 },
        { content: 'Peruntukan', rowSpan: 2 },
        { content: 'Berat Bersih (g)', colSpan: 2 },
        { content: 'Berat Kotor (kg)', colSpan: 2 },
        { content: 'BDD', rowSpan: 2 },
        { content: 'Buf', rowSpan: 2 },
        { content: 'PO (Kg)', rowSpan: 2 },
        { content: 'Harga Satuan', rowSpan: 2 },
        { content: 'Total Biaya', rowSpan: 2 },
        { content: 'Kandungan Gizi', rowSpan: 2 },
    ];
    const bpHeadRow2 = ['PK', 'PB', 'PK', 'PB'];

    const bpBody = data.formattedItems.map((item, idx) => [
        String(idx + 1),
        item.sub_menu,
        item.nama_bahan,
        item.nama_po,
        item.satuan,
        item.kategori,
        item.peruntukan,
        String(item.berat_bersih_pk),
        String(item.berat_bersih_pb),
        String(item.berat_kotor_pk),
        String(item.berat_kotor_pb),
        item.bdd_display,
        item.buffer_display,
        String(item.qty_po_display),
        formatRupiahNum(item.harga_satuan),
        formatRupiahNum(item.total_biaya),
        item.gizi_utama,
    ]);

    autoTable(doc, {
        startY: y + 4.5,
        margin: { left: 10, right: 10 },
        head: [bpHeadRow1, bpHeadRow2],
        body: bpBody,
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 6.2, cellPadding: 0.9, lineColor: [203, 213, 225], lineWidth: 0.15 },
        headStyles: { fillColor: [241, 245, 249], textColor: [15, 23, 42], fontStyle: 'bold', halign: 'center' },
        columnStyles: {
            0: { halign: 'center', cellWidth: 7 },
            1: { cellWidth: 20, fontStyle: 'bold' },
            2: { cellWidth: 25, fontStyle: 'bold' },
            3: { cellWidth: 25 },
            4: { halign: 'center', cellWidth: 8 },
            5: { halign: 'center', cellWidth: 14 },
            6: { halign: 'center', cellWidth: 15 },
            7: { halign: 'center', cellWidth: 11 },
            8: { halign: 'center', cellWidth: 11 },
            9: { halign: 'center', cellWidth: 11 },
            10: { halign: 'center', cellWidth: 11 },
            11: { halign: 'center', cellWidth: 9 },
            12: { halign: 'center', cellWidth: 9 },
            13: { halign: 'center', cellWidth: 13, fontStyle: 'bold' },
            14: { halign: 'right', cellWidth: 18 },
            15: { halign: 'right', cellWidth: 21, fontStyle: 'bold' },
            16: { cellWidth: 49, fontSize: 5.8 }
        }
    });

    let yRekap = doc.lastAutoTable.finalY + 4;
    autoTable(doc, {
        startY: yRekap,
        margin: { left: 10, right: 10 },
        body: [
            ['Batas Pagu Anggaran Bahan Makanan:', formatRupiahNum(data.paguBahanTotal), 'Berdasarkan alokasi pagu bahan pangan'],
            ['Total Belanja Bahan Makanan (PO):', formatRupiahNum(data.totalBelanjaPO), 'Total akumulasi biaya pembelanjaan PO'],
            ['Selisih Anggaran Bahan Makanan:', `${formatRupiahNum(Math.abs(data.selisihPagu))} (${data.selisihPagu >= 0 ? 'Surplus' : 'Defisit'})`, data.selisihPagu >= 0 ? 'Sisa anggaran belanja terkendali' : 'Biaya belanja melebihi pagu anggaran']
        ],
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.2, lineColor: [203, 213, 225], lineWidth: 0.15 },
        columnStyles: {
            0: { fontStyle: 'bold', cellWidth: 70 },
            1: { fontStyle: 'bold', halign: 'right', cellWidth: 45 },
            2: { cellWidth: 162, fontStyle: 'italic', textColor: [100, 116, 139] }
        }
    });

    // =========================================================
    // HALAMAN 4: FOOD COST, KANDUNGAN GIZI & LEMBAR PENGESAHAN TTD
    // =========================================================
    doc.addPage('a4', 'landscape');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.text('B. Food Cost', 10, 12);
    doc.setFontSize(7.5);
    doc.setTextColor(4, 120, 87);
    doc.text('Tabel 1. Rincian Food Cost Per Sub Menu Normal', 10, 16);
    doc.setTextColor(0, 0, 0);

    const fcNormalBody = data.foodCostNormalList.map((row, idx) => [
        String(idx + 1),
        row.sub_menu,
        `${row.count} Bahan`,
        formatRupiahNum(row.cost_pk),
        formatRupiahNum(row.cost_pb),
        row.percent_pk.toFixed(1),
        row.percent_pb.toFixed(1)
    ]);

    autoTable(doc, {
        startY: 18,
        margin: { left: 10, right: 10 },
        head: [
            [
                { content: 'No', rowSpan: 2 },
                { content: 'Sub Menu', rowSpan: 2 },
                { content: 'Jumlah Bahan Baku', rowSpan: 2 },
                { content: 'Food Cost (Rp)', colSpan: 2 },
                { content: 'Persentase (%)', colSpan: 2 }
            ],
            ['PK', 'PB', 'PK', 'PB']
        ],
        body: fcNormalBody,
        foot: [['Rekap Total Food Cost Normal:', '', '', formatRupiahNum(data.totalFcPKNormal), formatRupiahNum(data.totalFcPBNormal), '100', '100']],
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 6.8, cellPadding: 1, lineColor: [203, 213, 225], lineWidth: 0.15 },
        headStyles: { fillColor: [224, 242, 254], textColor: [3, 105, 161], fontStyle: 'bold', halign: 'center' },
        footStyles: { fillColor: [224, 242, 254], textColor: [3, 105, 161], fontStyle: 'bold' },
        columnStyles: {
            0: { halign: 'center', cellWidth: 10 },
            1: { cellWidth: 70, fontStyle: 'bold' },
            2: { halign: 'center', cellWidth: 35 },
            3: { halign: 'right', cellWidth: 40 },
            4: { halign: 'right', cellWidth: 40 },
            5: { halign: 'center', cellWidth: 41 },
            6: { halign: 'center', cellWidth: 41 }
        }
    });

    data.allergenFoodCostTables.forEach((alt, tIdx) => {
        let yAlt = doc.lastAutoTable.finalY + 4;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(185, 28, 28);
        doc.text(`Tabel ${tIdx + 2}: Rincian Food Cost Per Sub Menu Alergi ${alt.jenis_alergi}`, 10, yAlt);
        doc.setTextColor(0, 0, 0);

        const altBody = alt.list.map((row, idx) => [
            String(idx + 1),
            row.sub_menu,
            `${row.count} Bahan`,
            formatRupiahNum(row.cost_pk),
            formatRupiahNum(row.cost_pb),
            row.percent_pk.toFixed(1),
            row.percent_pb.toFixed(1)
        ]);

        autoTable(doc, {
            startY: yAlt + 2,
            margin: { left: 10, right: 10 },
            head: [
                [
                    { content: 'No', rowSpan: 2 },
                    { content: 'Sub Menu', rowSpan: 2 },
                    { content: 'Jumlah Bahan Baku', rowSpan: 2 },
                    { content: 'Food Cost (Rp)', colSpan: 2 },
                    { content: 'Persentase (%)', colSpan: 2 }
                ],
                ['PK', 'PB', 'PK', 'PB']
            ],
            body: altBody,
            foot: [[`Rekap Total Food Cost Alergi ${alt.jenis_alergi}:`, '', '', formatRupiahNum(alt.total_pk), formatRupiahNum(alt.total_pb), '100', '100']],
            theme: 'grid',
            styles: { font: 'helvetica', fontSize: 6.8, cellPadding: 1, lineColor: [203, 213, 225], lineWidth: 0.15 },
            headStyles: { fillColor: [254, 226, 226], textColor: [185, 28, 28], fontStyle: 'bold', halign: 'center' },
            footStyles: { fillColor: [254, 226, 226], textColor: [185, 28, 28], fontStyle: 'bold' },
            columnStyles: {
                0: { halign: 'center', cellWidth: 10 },
                1: { cellWidth: 70, fontStyle: 'bold' },
                2: { halign: 'center', cellWidth: 35 },
                3: { halign: 'right', cellWidth: 40 },
                4: { halign: 'right', cellWidth: 40 },
                5: { halign: 'center', cellWidth: 41 },
                6: { halign: 'center', cellWidth: 41 }
            }
        });
    });

    let yP4 = doc.lastAutoTable.finalY + 4;
    // Cek jika halaman tidak cukup untuk Kandungan Gizi + TTD, pindah ke halaman baru bersama-sama sehingga TTD TIDAK SENDIRIAN!
    if (yP4 > 120) {
        doc.addPage('a4', 'landscape');
        yP4 = 14;
    }

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.text('C. Kandungan Gizi', 10, yP4);
    doc.setFontSize(7.5);
    doc.text('Tabel: Rincian Kandungan Gizi', 10, yP4 + 3.5);

    const giziBody = data.giziList.map((g) => [
        g.rowSpan > 0 ? String(g.no) : '',
        g.rowSpan > 0 ? g.peruntukan : '',
        g.jenis_pm,
        String(g.energi),
        String(g.protein),
        String(g.lemak),
        String(g.karbo),
        String(g.serat)
    ]);

    autoTable(doc, {
        startY: yP4 + 5,
        margin: { left: 10, right: 10 },
        head: [['No', 'Peruntukan Porsi', 'Jenis PM', 'Energi (kkal)', 'Protein (g)', 'Lemak (g)', 'Karbohidrat (g)', 'Serat (g)']],
        body: giziBody,
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 6.8, cellPadding: 1, lineColor: [203, 213, 225], lineWidth: 0.15 },
        headStyles: { fillColor: [241, 245, 249], textColor: [15, 23, 42], fontStyle: 'bold', halign: 'center' },
        columnStyles: {
            0: { halign: 'center', cellWidth: 10 },
            1: { cellWidth: 55, fontStyle: 'bold' },
            2: { halign: 'center', cellWidth: 22, fontStyle: 'bold' },
            3: { halign: 'center', cellWidth: 38 },
            4: { halign: 'center', cellWidth: 38 },
            5: { halign: 'center', cellWidth: 38 },
            6: { halign: 'center', cellWidth: 38 },
            7: { halign: 'center', cellWidth: 38 }
        }
    });

    // Lembar Pengesahan Tanda Tangan
    let ttdY = doc.lastAutoTable.finalY + 8;
    if (ttdY > 175) {
        doc.addPage('a4', 'landscape');
        ttdY = 25;
    }

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.8);
    doc.text('Direncanakan Oleh:', 55, ttdY, { align: 'center' });
    doc.setFont('helvetica', 'bold');
    doc.text('Tim Ahli Gizi SPPG', 55, ttdY + 3.8, { align: 'center' });
    doc.text('( ............................................ )', 55, ttdY + 18, { align: 'center' });

    doc.setFont('helvetica', 'normal');
    doc.text('Diperiksa Oleh:', 148.5, ttdY, { align: 'center' });
    doc.setFont('helvetica', 'bold');
    doc.text('Petugas Keuangan / Akuntan', 148.5, ttdY + 3.8, { align: 'center' });
    doc.text('( ............................................ )', 148.5, ttdY + 18, { align: 'center' });

    doc.setFont('helvetica', 'normal');
    doc.text('Mengetahui & Menyetujui:', 242, ttdY, { align: 'center' });
    doc.setFont('helvetica', 'bold');
    doc.text('Kepala SPPG', 242, ttdY + 3.8, { align: 'center' });
    doc.text('( ............................................ )', 242, ttdY + 18, { align: 'center' });

    doc.save(filename);
}

// -------------------------------------------------------------
// 5. PRINT WORK ORDER PREVIEW
// -------------------------------------------------------------
export function printWorkOrder(wo) {
    const printWindow = window.open('', '_blank', 'width=1150,height=800');
    if (!printWindow) {
        alert('Mohon izinkan pop-up pada browser untuk mencetak dokumen.');
        return;
    }
    const html = generateWorkOrderHtml(wo, true);
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}

// 2. EXPORT PURCHASE ORDER (DAFTAR PO KEUANGAN)
// -------------------------------------------------------------

// 2. EXPORT PURCHASE ORDER (DAFTAR PO KEUANGAN) - MULTI-SHEET EXCEL RESMI
// -------------------------------------------------------------

function sanitizeExcelSheetName(name, existingNames) {
    let clean = (name || 'Sheet').replace(/[\\/?*:[\]]/g, ' ').replace(/\s+/g, ' ').trim();
    if (clean.length > 28) {
        clean = clean.substring(0, 28).trim();
    }
    let finalName = clean;
    let counter = 1;
    while (existingNames.has(finalName)) {
        const suffix = ` (${counter++})`;
        finalName = `${clean.substring(0, 31 - suffix.length)}${suffix}`;
    }
    existingNames.add(finalName);
    return finalName;
}

function renderKopSuratExcel(sheet, maxCols = 8, logoBgnId = null, logoYayasanId = null) {
    const lastColLetter = String.fromCharCode(64 + maxCols);

    // Merge teks kop dari kolom A sampai kolom terakhir agar benar-benar tepat di tengah-tengah secara simetris
    sheet.mergeCells(`A1:${lastColLetter}1`);
    const c1 = sheet.getCell('A1');
    c1.value = 'SPPG BULELENG SUKASADA TEGALLINGGAH';
    c1.font = { name: 'Arial', size: 12, bold: true };
    c1.alignment = { horizontal: 'center', vertical: 'middle' };
    sheet.getRow(1).height = 18;

    sheet.mergeCells(`A2:${lastColLetter}2`);
    const c2 = sheet.getCell('A2');
    c2.value = 'YAYASAN PESANTREN MIFTAHUL ULUM';
    c2.font = { name: 'Arial', size: 11, bold: true };
    c2.alignment = { horizontal: 'center', vertical: 'middle' };
    sheet.getRow(2).height = 17;

    sheet.mergeCells(`A3:${lastColLetter}3`);
    const c3 = sheet.getCell('A3');
    c3.value = 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali';
    c3.font = { name: 'Arial', size: 9 };
    c3.alignment = { horizontal: 'center', vertical: 'middle' };
    sheet.getRow(3).height = 15;

    sheet.mergeCells(`A4:${lastColLetter}4`);
    const c4 = sheet.getCell('A4');
    c4.value = 'E-mail: sppgsukasadategallinggah@gmail.com';
    c4.font = { name: 'Arial', size: 9, italic: true };
    c4.alignment = { horizontal: 'center', vertical: 'middle' };
    sheet.getRow(4).height = 16;

    // Garis tebal pemisah bawah kop surat (dari kolom A sampai kolom terakhir)
    for (let c = 1; c <= maxCols; c++) {
        sheet.getRow(4).getCell(c).border = {
            bottom: { style: 'medium', color: { argb: 'FF000000' } }
        };
    }

    // Logo kiri di Kolom A: sejajar dengan margin kiri (jarak ~8px dari tepi kiri tabel)
    if (logoBgnId !== null) {
        sheet.addImage(logoBgnId, {
            tl: { col: 0.15, row: 0.12 },
            ext: { width: 56, height: 56 }
        });
    }
    // Logo kanan di Kolom terakhir: simetris dengan jarak logo kiri ke tepi kanan tabel
    if (logoYayasanId !== null) {
        const rightOffset = maxCols === 8 ? 0.28 : 0.27;
        sheet.addImage(logoYayasanId, {
            tl: { col: maxCols - rightOffset, row: 0.12 },
            ext: { width: 56, height: 56 }
        });
    }
}

export async function exportPoExcel(po, options = {}) {
    if (!po) return;

    try {
        const workbook = new ExcelJS.Workbook();
        workbook.creator = 'SIPEGE SPPG';
        workbook.created = new Date();

        // 1. Embed Logos
        let logoBgnId = null;
        let logoYayasanId = null;
        try {
            logoBgnId = workbook.addImage({
                base64: LOGO_BGN_RAW_BASE64,
                extension: 'png',
            });
            logoYayasanId = workbook.addImage({
                base64: LOGO_YAYASAN_RAW_BASE64,
                extension: 'png',
            });
        } catch (e) {
            console.warn('Gagal memuat logo ke workbook Excel:', e);
        }

        // 2. Parse Tanggal Order & Tanggal Kirim
        const DAYS_INDO = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const MONTHS_INDO = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function formatIndoFull(dateObj) {
            const dayName = DAYS_INDO[dateObj.getDay()];
            const d = String(dateObj.getDate()).padStart(2, '0');
            const m = MONTHS_INDO[dateObj.getMonth()];
            const y = dateObj.getFullYear();
            return `${dayName}, ${d} ${m} ${y}`;
        }

        let kirimDateObj = po.tanggal ? new Date(po.tanggal) : new Date();
        if (isNaN(kirimDateObj.getTime())) kirimDateObj = new Date();

        let orderDateObj = null;
        if (options.orderDate) {
            orderDateObj = new Date(options.orderDate);
        } else if (po.created_at) {
            orderDateObj = new Date(po.created_at);
            if (isNaN(orderDateObj.getTime())) orderDateObj = null;
        }

        if (!orderDateObj) {
            // Default tanggal order H-1 sebelum tanggal kirim
            orderDateObj = new Date(kirimDateObj);
            orderDateObj.setDate(orderDateObj.getDate() - 1);
        }

        const orderDateFormatted = formatIndoFull(orderDateObj);
        const kirimDateFormatted = formatIndoFull(kirimDateObj);

        const ddOrder = String(orderDateObj.getDate()).padStart(2, '0');
        const mmOrder = String(orderDateObj.getMonth() + 1).padStart(2, '0');
        const yyyyOrder = String(orderDateObj.getFullYear());

        const orderDateSlash = `${ddOrder}/${mmOrder}/${yyyyOrder}`;
        const orderDateHyphen = `${ddOrder}-${mmOrder}-${yyyyOrder}`;

        const filename = `NPO-${orderDateHyphen}.xlsx`;

        // 3. Klasifikasi Item (BB vs OPS)
        function isItemOps(it) {
            const trx = `${it.jenis_transaksi || ''} ${it.jenis || ''} ${it.kategori || ''}`.toLowerCase();
            return trx.includes('ops') || trx.includes('operasional') || trx.includes('kas bank') || trx.includes('petty cash') || trx.includes('non-pangan');
        }

        // Helper keterangan / catatan riil bahan dari rancang menu (bukan nama sub menu)
        function resolveItemKeterangan(it) {
            const candidates = [
                it.keterangan,
                it.catatan,
                it.spesifikasi,
                it.catatan_bahan,
                it.notes,
            ];
            for (const val of candidates) {
                if (!val) continue;
                const str = String(val).trim();
                if (str && str !== '-' && !str.toLowerCase().startsWith('sub menu') && !str.toLowerCase().startsWith('sub_menu')) {
                    return str;
                }
            }
            return '-';
        }

        const allItems = po.items || [];
        const bbItems = allItems.filter(it => !isItemOps(it));
        const opsItems = allItems.filter(it => isItemOps(it));

        // Grouping items per supplier
        function groupItemsBySupplier(items) {
            const groups = new Map();
            for (const it of items) {
                const sup = it.supplier || po.supplier || {};
                const supId = sup.id || null;
                const namaUsaha = sup.nama_usaha || it.vendor || po.vendor || 'Rumah Tempe';
                const namaPemilik = sup.nama_pemilik || (namaUsaha.toLowerCase().includes('tempe') ? 'Noer Hakim' : '-');
                const alamat = sup.alamat_lengkap || sup.alamat || (namaUsaha.toLowerCase().includes('tempe') ? 'Jl. Pulau Sugara No. 31' : 'Jl. Raya Angling Darma, Sukasada');

                const key = supId ? `id_${supId}` : `name_${namaUsaha}`;
                if (!groups.has(key)) {
                    groups.set(key, {
                        supplier: {
                            id: supId,
                            namaUsaha,
                            namaPemilik,
                            alamat,
                        },
                        items: [],
                    });
                }
                groups.get(key).items.push(it);
            }
            return Array.from(groups.values());
        }

        const bbSupplierGroups = groupItemsBySupplier(bbItems);
        const opsSupplierGroups = groupItemsBySupplier(opsItems);

        // Jika tidak ada supplier di item tapi ada supplier PO
        if (bbSupplierGroups.length === 0 && bbItems.length > 0) {
            const defSup = po.supplier || {};
            bbSupplierGroups.push({
                supplier: {
                    id: defSup.id || null,
                    namaUsaha: defSup.nama_usaha || po.vendor || 'Supplier Utama',
                    namaPemilik: defSup.nama_pemilik || '-',
                    alamat: defSup.alamat_lengkap || defSup.alamat || 'Jl. Raya Angling Darma, Sukasada',
                },
                items: bbItems,
            });
        }

        // Shared Style Definition
        const borderThinBlack = {
            top: { style: 'thin', color: { argb: 'FF000000' } },
            bottom: { style: 'thin', color: { argb: 'FF000000' } },
            left: { style: 'thin', color: { argb: 'FF000000' } },
            right: { style: 'thin', color: { argb: 'FF000000' } },
        };
        const headerGrayFill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFBFBFBF' },
        };
        const accountingNumFmt = '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"??_);_(@_)';

        const existingSheetNames = new Set();

        // =========================================================
        // A. SHEET REKAP NOTA PESANAN (REKAP BB & REKAP OPS)
        // =========================================================
        function buildRekapSheet(classification) {
            const isBB = classification === 'BB';
            const sheetTitle = isBB ? 'Rekap BB' : 'Rekap OPS';
            const docTitle = isBB ? 'REKAP NOTA PESANAN BAHAN BAKU' : 'REKAP NOTA PESANAN BARANG OPERASIONAL';
            const currentItems = isBB ? bbItems : opsItems;

            const finalSheetName = sanitizeExcelSheetName(sheetTitle, existingSheetNames);
            const sheet = workbook.addWorksheet(finalSheetName, {
                views: [{ showGridLines: true }],
            });

            // Column Widths (A - H, 8 Kolom) - dilebarkan agar teks tidak terpotong
            sheet.columns = [
                { width: 6 },   // A: No
                { width: 36 },  // B: Uraian Pesanan
                { width: 26 },  // C: Nama Supplier
                { width: 11 },  // D: Kuantitas Jml
                { width: 11 },  // E: Kuantitas Satuan
                { width: 17 },  // F: Harga
                { width: 20 },  // G: Jumlah
                { width: 36 },  // H: Keterangan
            ];

            // Render Kop Surat
            renderKopSuratExcel(sheet, 8, logoBgnId, logoYayasanId);

            sheet.addRow([]); // Row 5 (gap)
            sheet.getRow(5).height = 10;

            // Row 6: Title
            const rTitle = sheet.addRow([docTitle]);
            sheet.mergeCells(`A${rTitle.number}:H${rTitle.number}`);
            rTitle.getCell(1).font = { name: 'Arial', size: 11, bold: true };
            rTitle.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rTitle.height = 20;

            sheet.addRow([]); // Row 7 (gap)
            sheet.getRow(7).height = 10;

            // Rows 8 - 12: Metadata Kiri & Kotak Porsi Kanan
            const porsiPk = Number(po.porsi_pk || 0);
            const porsiPb = Number(po.porsi_pb || 0);
            const totalPorsi = Number(po.total_porsi || (porsiPk + porsiPb) || 0);

            // Pagu Anggaran
            let paguNominal = 0;
            if (isBB) {
                const costPk = Number(po.cost_pk || 8000);
                const costPb = Number(po.cost_pb || 10000);
                paguNominal = Math.round((porsiPk * costPk) + (porsiPb * costPb));
                if (paguNominal === 0 && po.total_nominal) {
                    paguNominal = Math.round(Number(po.total_nominal));
                }
            }

            const catatanRancangMenu = po.catatan_rancang_menu && po.catatan_rancang_menu !== '-'
                ? po.catatan_rancang_menu
                : '-';

            const metaRows = [
                { label: 'Nama SPPG', val: 'SPPG Buleleng Sukasada Tegallinggah', boxLabel: 'Jumlah Porsi Kecil', boxVal: porsiPk, boxFmt: '#,##0' },
                { label: 'ID SPPG', val: 'QQCV0LUG', boxLabel: 'Jumlah Porsi Besar', boxVal: porsiPb, boxFmt: '#,##0' },
                { label: 'Nama Yayasan', val: 'Yayasan Pesantren Miftahul Ulum', boxLabel: 'Total Porsi', boxVal: totalPorsi, boxFmt: '#,##0' },
                { label: 'Hari/Tanggal Order', val: orderDateFormatted, boxLabel: 'Pagu Anggaran', boxVal: paguNominal, boxFmt: accountingNumFmt },
                { label: 'Hari/Tanggal Kirim', val: kirimDateFormatted, boxLabel: 'Catatan', boxVal: catatanRancangMenu, boxFmt: '@' },
            ];

            metaRows.forEach((m) => {
                // Merge A:B untuk label dan C:E untuk value agar label dan nilai tidak terpotong
                const r = sheet.addRow([m.label, '', `:  ${m.val}`, '', '', m.boxLabel, '', m.boxVal]);
                const rn = r.number;
                sheet.mergeCells(`A${rn}:B${rn}`);
                sheet.mergeCells(`C${rn}:E${rn}`);
                sheet.mergeCells(`F${rn}:G${rn}`);

                r.getCell(1).font = { name: 'Arial', size: 9.5 };
                r.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
                r.getCell(3).font = { name: 'Arial', size: 9.5 };
                r.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };

                // Styling Kotak Kanan (Cols F, G, H)
                r.getCell(6).font = { name: 'Arial', size: 9.5 };
                r.getCell(6).border = borderThinBlack;
                r.getCell(7).border = borderThinBlack;
                r.getCell(6).alignment = { horizontal: 'left', vertical: 'middle' };

                const cBoxVal = r.getCell(8);
                cBoxVal.font = { name: 'Arial', size: 9.5 };
                cBoxVal.border = borderThinBlack;
                cBoxVal.alignment = { horizontal: m.boxFmt === '@' ? 'center' : 'right', vertical: 'middle', wrapText: true };
                if (m.boxFmt !== '@') {
                    cBoxVal.numFmt = m.boxFmt;
                }
                r.height = 18;
            });

            sheet.addRow([]); // Row 13 (gap)
            sheet.getRow(13).height = 10;

            // Row 14 & 15: Table Header
            const rHead1 = sheet.addRow(['No', 'Uraian Pesanan', 'Nama Supplier', 'Kuantitas', '', 'Harga', 'Jumlah', 'Keterangan']);
            const rHead2 = sheet.addRow(['', '', '', 'Jml', 'Satuan', '', '', '']);

            const h1N = rHead1.number;
            const h2N = rHead2.number;

            sheet.mergeCells(`A${h1N}:A${h2N}`);
            sheet.mergeCells(`B${h1N}:B${h2N}`);
            sheet.mergeCells(`C${h1N}:C${h2N}`);
            sheet.mergeCells(`D${h1N}:E${h1N}`);
            sheet.mergeCells(`F${h1N}:F${h2N}`);
            sheet.mergeCells(`G${h1N}:G${h2N}`);
            sheet.mergeCells(`H${h1N}:H${h2N}`);

            [rHead1, rHead2].forEach(r => {
                r.height = 20;
                r.font = { name: 'Arial', size: 9.5, bold: true };
                for (let c = 1; c <= 8; c++) {
                    const cell = r.getCell(c);
                    cell.fill = headerGrayFill;
                    cell.border = borderThinBlack;
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                }
            });

            // Table Data Rows: Berapapun bahan yang masuk list, HANYA sejumlah bahan itu saja tanpa padding kosong
            const rowCount = currentItems.length;
            const startDataRow = sheet.rowCount + 1;

            if (rowCount === 0) {
                const rEmpty = sheet.addRow(['-', 'Tidak ada data transaksi', '-', 0, '-', 0, 0, '-']);
                rEmpty.height = 19;
                for (let c = 1; c <= 8; c++) {
                    rEmpty.getCell(c).border = borderThinBlack;
                    rEmpty.getCell(c).font = { name: 'Arial', size: 9 };
                }
                rEmpty.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            } else {
                for (let i = 0; i < rowCount; i++) {
                    const it = currentItems[i];
                    const rNum = startDataRow + i;

                    const gross = Number(it.gross_kg) || 0;
                    const stok = Number(it.stok_digunakan_kg || 0);
                    const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
                        ? Number(it.qty_beli_po_kg)
                        : Math.max(0, gross - stok);
                    const harga = Math.round(Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0)));
                    const supplierName = it.supplier?.nama_usaha || po.supplier?.nama_usaha || it.vendor || po.vendor || 'CV. Citra Lestari Abadi';
                    const satuan = it.satuan || 'kg';

                    const ket = resolveItemKeterangan(it);

                    const r = sheet.addRow([
                        i + 1,
                        it.nama || '',
                        supplierName,
                        qtyBeli,
                        satuan,
                        harga,
                        { formula: `ROUND(D${rNum}*F${rNum}, 0)`, result: Math.round(qtyBeli * harga) },
                        ket
                    ]);

                    r.height = 19;
                    r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(2).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(4).alignment = { horizontal: 'right', vertical: 'middle' };

                    // Format angka rapi: tanpa titik desimal gantung jika bilangan bulat
                    const isQtyInt = Math.abs(qtyBeli - Math.round(qtyBeli)) < 0.001;
                    r.getCell(4).numFmt = isQtyInt ? '#,##0' : '#,##0.##';

                    r.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(6).numFmt = accountingNumFmt;
                    r.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(7).numFmt = accountingNumFmt;
                    r.getCell(8).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

                    for (let c = 1; c <= 8; c++) {
                        r.getCell(c).border = borderThinBlack;
                        r.getCell(c).font = { name: 'Arial', size: 9 };
                    }
                }
            }

            const endDataRow = rowCount > 0 ? (startDataRow + rowCount - 1) : startDataRow;

            // Summary Rows (3 Baris: Total Pengeluaran, Pagu Anggaran, Sisa Anggaran)
            // 1. Total Pengeluaran
            const rTot = sheet.addRow(['Total Pengeluaran', '', '', '', '', 'Rp', { formula: `SUM(G${startDataRow}:G${endDataRow})` }, '']);
            const totN = rTot.number;
            sheet.mergeCells(`A${totN}:E${totN}`);
            rTot.height = 20;
            rTot.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rTot.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rTot.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rTot.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rTot.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rTot.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rTot.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rTot.getCell(c).border = borderThinBlack;

            // 2. Pagu Anggaran
            const rPagu = sheet.addRow(['Pagu Anggaran', '', '', '', '', 'Rp', paguNominal, '']);
            const paguN = rPagu.number;
            sheet.mergeCells(`A${paguN}:E${paguN}`);
            rPagu.height = 20;
            rPagu.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rPagu.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rPagu.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rPagu.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rPagu.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rPagu.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rPagu.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rPagu.getCell(c).border = borderThinBlack;

            // 3. Sisa Anggaran
            const rSisa = sheet.addRow(['Sisa Anggaran', '', '', '', '', 'Rp', { formula: `G${paguN}-G${totN}` }, '']);
            const sisaN = rSisa.number;
            sheet.mergeCells(`A${sisaN}:E${sisaN}`);
            rSisa.height = 20;
            rSisa.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSisa.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rSisa.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rSisa.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rSisa.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rSisa.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rSisa.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rSisa.getCell(c).border = borderThinBlack;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 14;

            // 1. Tanggal Sukasada: Merge A:D, sejajar dan simetris tepat di atas blok tanda tangan kiri
            const rDate = sheet.addRow([`Sukasada, ${orderDateFormatted}`, '', '', '']);
            const dateN = rDate.number;
            sheet.mergeCells(`A${dateN}:D${dateN}`);
            rDate.getCell(1).font = { name: 'Arial', size: 9.5 };
            rDate.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rDate.height = 18;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 10;

            // 2. Tanda Tangan Baris 1: Pengawas Keuangan SPPG (A:D) dan PIC Yayasan (E:H)
            const rSig1 = sheet.addRow(['Pengawas Keuangan SPPG Buleleng Sukasada Tegallinggah', '', '', '', 'PIC Yayasan Pesantren Miftahul Ulum', '', '', '']);
            const sig1N = rSig1.number;
            sheet.mergeCells(`A${sig1N}:D${sig1N}`);
            sheet.mergeCells(`E${sig1N}:H${sig1N}`);
            rSig1.getCell(1).font = { name: 'Arial', size: 9.5 };
            rSig1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig1.getCell(5).font = { name: 'Arial', size: 9.5 };
            rSig1.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig1.height = 20;

            // Space TTD
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;

            // 3. Nama Penandatangan Baris 1: I Gusti Ayu Made Padmi Swari (A:D) dan Susianah (E:H)
            const rSig2 = sheet.addRow(['I Gusti Ayu Made Padmi Swari, S.Ak.', '', '', '', 'Susianah', '', '', '']);
            const sig2N = rSig2.number;
            sheet.mergeCells(`A${sig2N}:D${sig2N}`);
            sheet.mergeCells(`E${sig2N}:H${sig2N}`);
            rSig2.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSig2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig2.getCell(5).font = { name: 'Arial', size: 9.5, bold: true };
            rSig2.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig2.height = 18;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 12;

            // 4. Baris Jabatan 2: Kepala SPPG di tengah bawah (merge A:H melintasi seluruh lebar tabel)
            const rSig3 = sheet.addRow(['Kepala SPPG Buleleng Sukasada Tegallinggah', '', '', '', '', '', '', '']);
            const sig3N = rSig3.number;
            sheet.mergeCells(`A${sig3N}:H${sig3N}`);
            rSig3.getCell(1).font = { name: 'Arial', size: 9.5 };
            rSig3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig3.height = 20;

            // Space TTD
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;

            // 5. Nama Penandatangan Baris 2: I Gede Gelgel Abdiutama (Merge A:H)
            const rSig4 = sheet.addRow(['I Gede Gelgel Abdiutama, S.Kom.', '', '', '', '', '', '', '']);
            const sig4N = rSig4.number;
            sheet.mergeCells(`A${sig4N}:H${sig4N}`);
            rSig4.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSig4.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig4.height = 18;
        }

        // =========================================================
        // B. SHEET NOTA PESANAN PER SUPPLIER
        // =========================================================
        function buildSupplierSheet({ classification, poNumber, supplier, items }) {
            const isBB = classification === 'BB';
            const docTitle = isBB ? 'NOTA PESANAN BAHAN BAKU' : 'NOTA PESANAN BARANG OPERASIONAL';

            // Nama sheet bersih
            const prefix = isBB ? '' : 'OPS - ';
            const rawSheetName = `${prefix}${supplier.namaUsaha || 'Supplier'}`;
            const finalSheetName = sanitizeExcelSheetName(rawSheetName, existingSheetNames);

            const sheet = workbook.addWorksheet(finalSheetName, {
                views: [{ showGridLines: true }],
            });

            // Column Widths (A - G, 7 Kolom) - Seimbang simetris (A:D lebar 68, E:G lebar 70)
            sheet.columns = [
                { width: 6 },   // A: No
                { width: 38 },  // B: Uraian Pesanan
                { width: 12 },  // C: Kuantitas Jml
                { width: 12 },  // D: Kuantitas Satuan
                { width: 18 },  // E: Harga
                { width: 20 },  // F: Jumlah
                { width: 32 },  // G: Keterangan
            ];

            // Kop Surat
            renderKopSuratExcel(sheet, 7, logoBgnId, logoYayasanId);

            sheet.addRow([]); // Row 5 (gap)
            sheet.getRow(5).height = 10;

            // Row 6: Title
            const rTitle = sheet.addRow([docTitle]);
            sheet.mergeCells(`A${rTitle.number}:G${rTitle.number}`);
            rTitle.getCell(1).font = { name: 'Arial', size: 11, bold: true };
            rTitle.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rTitle.height = 20;

            // Row 7: Subtitle No. PO
            const rSub = sheet.addRow([`No. ${poNumber}`]);
            sheet.mergeCells(`A${rSub.number}:G${rSub.number}`);
            rSub.getCell(1).font = { name: 'Arial', size: 10, bold: true };
            rSub.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSub.height = 18;

            sheet.addRow([]); // Row 8 (gap)
            sheet.getRow(8).height = 10;

            // Metadata Supplier (Rows 9 - 16) - Merge A:B untuk label dan C:G untuk value
            const metaRows = [
                { label: 'Nama SPPG', val: 'SPPG Buleleng Sukasada Tegallinggah' },
                { label: 'ID SPPG', val: 'QQCV0LUG' },
                { label: 'Nama Yayasan', val: 'Yayasan Pesantren Miftahul Ulum' },
                { label: 'Kepada Supplier', val: supplier.namaUsaha || 'Rumah Tempe' },
                { label: 'Nama Pemilik Supplier', val: supplier.namaPemilik || 'Noer Hakim' },
                { label: 'Alamat Usaha Supplier', val: supplier.alamat || 'Jl. Pulau Sugara No. 31' },
                { label: 'Hari/Tanggal Order', val: orderDateFormatted },
                { label: 'Hari/Tanggal Kirim', val: kirimDateFormatted },
            ];

            metaRows.forEach((m) => {
                const r = sheet.addRow([m.label, '', `:  ${m.val}`]);
                const rn = r.number;
                sheet.mergeCells(`A${rn}:B${rn}`);
                sheet.mergeCells(`C${rn}:G${rn}`);
                r.getCell(1).font = { name: 'Arial', size: 9.5 };
                r.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
                r.getCell(3).font = { name: 'Arial', size: 9.5 };
                r.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };
                r.height = 18;
            });

            sheet.addRow([]); // Row 17 (gap)
            sheet.getRow(17).height = 10;

            // Table Header (Rows 18 & 19)
            const rHead1 = sheet.addRow(['No', 'Uraian Pesanan', 'Kuantitas', '', 'Harga', 'Jumlah', 'Keterangan']);
            const rHead2 = sheet.addRow(['', '', 'Jml', 'Satuan', '', '', '']);

            const h1N = rHead1.number;
            const h2N = rHead2.number;

            sheet.mergeCells(`A${h1N}:A${h2N}`);
            sheet.mergeCells(`B${h1N}:B${h2N}`);
            sheet.mergeCells(`C${h1N}:D${h1N}`);
            sheet.mergeCells(`E${h1N}:E${h2N}`);
            sheet.mergeCells(`F${h1N}:F${h2N}`);
            sheet.mergeCells(`G${h1N}:G${h2N}`);

            [rHead1, rHead2].forEach(r => {
                r.height = 20;
                r.font = { name: 'Arial', size: 9.5, bold: true };
                for (let c = 1; c <= 7; c++) {
                    const cell = r.getCell(c);
                    cell.fill = headerGrayFill;
                    cell.border = borderThinBlack;
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                }
            });

            // Table Data Rows: Berapapun bahan yang masuk list, HANYA sejumlah bahan itu saja tanpa padding baris kosong
            const rowCount = items.length;
            const startDataRow = sheet.rowCount + 1;

            if (rowCount === 0) {
                const rEmpty = sheet.addRow(['-', 'Tidak ada data transaksi', 0, '-', 0, 0, '-']);
                rEmpty.height = 19;
                for (let c = 1; c <= 7; c++) {
                    rEmpty.getCell(c).border = borderThinBlack;
                    rEmpty.getCell(c).font = { name: 'Arial', size: 9 };
                }
                rEmpty.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            } else {
                for (let i = 0; i < rowCount; i++) {
                    const it = items[i];
                    const rNum = startDataRow + i;

                    const gross = Number(it.gross_kg) || 0;
                    const stok = Number(it.stok_digunakan_kg || 0);
                    const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
                        ? Number(it.qty_beli_po_kg)
                        : Math.max(0, gross - stok);
                    const harga = Math.round(Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0)));
                    const satuan = it.satuan || 'kg';
                    const ket = resolveItemKeterangan(it);

                    const r = sheet.addRow([
                        i + 1,
                        it.nama || '',
                        qtyBeli,
                        satuan,
                        harga,
                        { formula: `ROUND(C${rNum}*E${rNum}, 0)`, result: Math.round(qtyBeli * harga) },
                        ket
                    ]);

                    r.height = 19;
                    r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(2).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(3).alignment = { horizontal: 'right', vertical: 'middle' };

                    // Format angka rapi tanpa titik desimal gantung
                    const isQtyInt = Math.abs(qtyBeli - Math.round(qtyBeli)) < 0.001;
                    r.getCell(3).numFmt = isQtyInt ? '#,##0' : '#,##0.##';

                    r.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(5).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(5).numFmt = accountingNumFmt;
                    r.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(6).numFmt = accountingNumFmt;
                    r.getCell(7).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };

                    for (let c = 1; c <= 7; c++) {
                        r.getCell(c).border = borderThinBlack;
                        r.getCell(c).font = { name: 'Arial', size: 9 };
                    }
                }
            }

            const endDataRow = rowCount > 0 ? (startDataRow + rowCount - 1) : startDataRow;

            // Total Row
            const rTot = sheet.addRow(['Total', '', '', '', '', { formula: `SUM(F${startDataRow}:F${endDataRow})` }, '']);
            const totN = rTot.number;
            sheet.mergeCells(`A${totN}:E${totN}`);
            rTot.height = 20;
            rTot.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rTot.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rTot.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rTot.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
            rTot.getCell(6).numFmt = accountingNumFmt;
            for (let c = 1; c <= 7; c++) rTot.getCell(c).border = borderThinBlack;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 14;

            // 1. Tanggal Sukasada: Merge A:D, sejajar dan simetris tepat di atas blok tanda tangan kiri
            const rDate = sheet.addRow([`Sukasada, ${orderDateFormatted}`, '', '', '']);
            const dateN = rDate.number;
            sheet.mergeCells(`A${dateN}:D${dateN}`);
            rDate.getCell(1).font = { name: 'Arial', size: 9.5 };
            rDate.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rDate.height = 18;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 10;

            // 2. Baris Jabatan 1:
            // Blok Kiri (A:D lebar 68): Supplier [Nama Usaha]
            // Blok Kanan (E:G lebar 70): Pengawas Keuangan SPPG Buleleng Sukasada Tegallinggah
            const rSig1 = sheet.addRow([
                `Supplier ${supplier.namaUsaha || 'Rumah Tempe'}`, '', '', '',
                'Pengawas Keuangan\nSPPG Buleleng Sukasada Tegallinggah', '', ''
            ]);
            const s1N = rSig1.number;
            sheet.mergeCells(`A${s1N}:D${s1N}`);
            sheet.mergeCells(`E${s1N}:G${s1N}`);
            rSig1.getCell(1).font = { name: 'Arial', size: 9.5 };
            rSig1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            rSig1.getCell(5).font = { name: 'Arial', size: 9.5 };
            rSig1.getCell(5).alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            rSig1.height = 26;

            // Space TTD
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;

            // 3. Baris Nama 1:
            // Blok Kiri (A:D): Pemilik Supplier
            // Blok Kanan (E:G): I Gusti Ayu Made Padmi Swari, S.Ak.
            const rSig2 = sheet.addRow([
                supplier.namaPemilik || 'Noer Hakim', '', '', '',
                'I Gusti Ayu Made Padmi Swari, S.Ak.', '', ''
            ]);
            const s2N = rSig2.number;
            sheet.mergeCells(`A${s2N}:D${s2N}`);
            sheet.mergeCells(`E${s2N}:G${s2N}`);
            rSig2.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSig2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig2.getCell(5).font = { name: 'Arial', size: 9.5, bold: true };
            rSig2.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig2.height = 18;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 12;

            // 4. Baris Jabatan 2:
            // Blok Kiri (A:D): Kepala SPPG Buleleng Sukasada Tegallinggah
            // Blok Kanan (E:G): PIC Yayasan Pesantren Miftahul Ulum
            const rSig3 = sheet.addRow([
                'Kepala SPPG Buleleng Sukasada Tegallinggah', '', '', '',
                'PIC Yayasan Pesantren Miftahul Ulum', '', ''
            ]);
            const s3N = rSig3.number;
            sheet.mergeCells(`A${s3N}:D${s3N}`);
            sheet.mergeCells(`E${s3N}:G${s3N}`);
            rSig3.getCell(1).font = { name: 'Arial', size: 9.5 };
            rSig3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig3.getCell(5).font = { name: 'Arial', size: 9.5 };
            rSig3.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig3.height = 20;

            // Space TTD
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;

            // 5. Baris Nama 2:
            // Blok Kiri (A:D): I Gede Gelgel Abdiutama, S.Kom.
            // Blok Kanan (E:G): Susianah
            const rSig4 = sheet.addRow([
                'I Gede Gelgel Abdiutama, S.Kom.', '', '', '',
                'Susianah', '', ''
            ]);
            const s4N = rSig4.number;
            sheet.mergeCells(`A${s4N}:D${s4N}`);
            sheet.mergeCells(`E${s4N}:G${s4N}`);
            rSig4.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSig4.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig4.getCell(5).font = { name: 'Arial', size: 9.5, bold: true };
            rSig4.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig4.height = 18;
        }

        // =========================================================
        // C. SHEET REKAP AKHIR (GABUNGAN BAHAN BAKU & OPERASIONAL)
        // =========================================================
        function buildRekapAkhirSheet() {
            const sheetTitle = 'Rekap Akhir';
            const docTitle = 'REKAPITULASI AKHIR NOTA PESANAN (BB & OPERASIONAL)';

            const finalSheetName = sanitizeExcelSheetName(sheetTitle, existingSheetNames);
            const sheet = workbook.addWorksheet(finalSheetName, {
                views: [{ showGridLines: true }],
            });

            // Column Widths (A - H, 8 Kolom)
            sheet.columns = [
                { width: 6 },   // A: No
                { width: 36 },  // B: Uraian Pesanan
                { width: 26 },  // C: Nama Supplier
                { width: 11 },  // D: Kuantitas Jml
                { width: 11 },  // E: Kuantitas Satuan
                { width: 17 },  // F: Harga
                { width: 20 },  // G: Jumlah
                { width: 30 },  // H: Keterangan
            ];

            // Render Kop Surat
            renderKopSuratExcel(sheet, 8, logoBgnId, logoYayasanId);

            sheet.addRow([]); // Row 5 (gap)
            sheet.getRow(5).height = 10;

            // Row 6: Title
            const rTitle = sheet.addRow([docTitle]);
            sheet.mergeCells(`A${rTitle.number}:H${rTitle.number}`);
            rTitle.getCell(1).font = { name: 'Arial', size: 11, bold: true };
            rTitle.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rTitle.height = 20;

            sheet.addRow([]); // Row 7 (gap)
            sheet.getRow(7).height = 10;

            // Rows 8 - 12: Metadata Kiri & Kotak Porsi Kanan
            const porsiPk = Number(po.porsi_pk || 0);
            const porsiPb = Number(po.porsi_pb || 0);
            const totalPorsi = Number(po.total_porsi || (porsiPk + porsiPb) || 0);

            // Pagu Anggaran
            const costPk = Number(po.cost_pk || 8000);
            const costPb = Number(po.cost_pb || 10000);
            let paguNominal = Math.round((porsiPk * costPk) + (porsiPb * costPb));
            if (paguNominal === 0 && po.total_nominal) {
                paguNominal = Math.round(Number(po.total_nominal));
            }

            const catatanRancangMenu = po.catatan_rancang_menu && po.catatan_rancang_menu !== '-'
                ? po.catatan_rancang_menu
                : '-';

            const metaRows = [
                { label: 'Nama SPPG', val: 'SPPG Buleleng Sukasada Tegallinggah', boxLabel: 'Jumlah Porsi Kecil', boxVal: porsiPk, boxFmt: '#,##0' },
                { label: 'ID SPPG', val: 'QQCV0LUG', boxLabel: 'Jumlah Porsi Besar', boxVal: porsiPb, boxFmt: '#,##0' },
                { label: 'Nama Yayasan', val: 'Yayasan Pesantren Miftahul Ulum', boxLabel: 'Total Porsi', boxVal: totalPorsi, boxFmt: '#,##0' },
                { label: 'Hari/Tanggal Order', val: orderDateFormatted, boxLabel: 'Pagu Anggaran', boxVal: paguNominal, boxFmt: accountingNumFmt },
                { label: 'Hari/Tanggal Kirim', val: kirimDateFormatted, boxLabel: 'Catatan', boxVal: catatanRancangMenu, boxFmt: '@' },
            ];

            metaRows.forEach((m) => {
                const r = sheet.addRow([m.label, '', `:  ${m.val}`, '', '', m.boxLabel, '', m.boxVal]);
                const rn = r.number;
                sheet.mergeCells(`A${rn}:B${rn}`);
                sheet.mergeCells(`C${rn}:E${rn}`);
                sheet.mergeCells(`F${rn}:G${rn}`);

                r.getCell(1).font = { name: 'Arial', size: 9.5 };
                r.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
                r.getCell(3).font = { name: 'Arial', size: 9.5 };
                r.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };

                r.getCell(6).font = { name: 'Arial', size: 9.5 };
                r.getCell(6).border = borderThinBlack;
                r.getCell(7).border = borderThinBlack;
                r.getCell(6).alignment = { horizontal: 'left', vertical: 'middle' };

                const cBoxVal = r.getCell(8);
                cBoxVal.font = { name: 'Arial', size: 9.5 };
                cBoxVal.border = borderThinBlack;
                cBoxVal.alignment = { horizontal: m.boxFmt === '@' ? 'center' : 'right', vertical: 'middle', wrapText: true };
                if (m.boxFmt !== '@') {
                    cBoxVal.numFmt = m.boxFmt;
                }
                r.height = 18;
            });

            sheet.addRow([]); // Row 13 (gap)
            sheet.getRow(13).height = 10;

            // Row 14 & 15: Table Header
            const rHead1 = sheet.addRow(['No', 'Uraian Pesanan', 'Nama Supplier', 'Kuantitas', '', 'Harga', 'Jumlah', 'Keterangan']);
            const rHead2 = sheet.addRow(['', '', '', 'Jml', 'Satuan', '', '', '']);

            const h1N = rHead1.number;
            const h2N = rHead2.number;

            sheet.mergeCells(`A${h1N}:A${h2N}`);
            sheet.mergeCells(`B${h1N}:B${h2N}`);
            sheet.mergeCells(`C${h1N}:C${h2N}`);
            sheet.mergeCells(`D${h1N}:E${h1N}`);
            sheet.mergeCells(`F${h1N}:F${h2N}`);
            sheet.mergeCells(`G${h1N}:G${h2N}`);
            sheet.mergeCells(`H${h1N}:H${h2N}`);

            [rHead1, rHead2].forEach(r => {
                r.height = 20;
                r.font = { name: 'Arial', size: 9.5, bold: true };
                for (let c = 1; c <= 8; c++) {
                    const cell = r.getCell(c);
                    cell.fill = headerGrayFill;
                    cell.border = borderThinBlack;
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                }
            });

            // --- SECTION 1: BAHAN BAKU (BB) ---
            const rSecBB = sheet.addRow(['I. REKAPITULASI KEBUTUHAN BAHAN BAKU (BB)', '', '', '', '', '', '', '']);
            const secBbN = rSecBB.number;
            sheet.mergeCells(`A${secBbN}:H${secBbN}`);
            rSecBB.height = 20;
            rSecBB.getCell(1).font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF0F5132' } };
            rSecBB.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
            rSecBB.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE8F5E9' } };
            for (let c = 1; c <= 8; c++) rSecBB.getCell(c).border = borderThinBlack;

            const bbStartRow = sheet.rowCount + 1;
            if (bbItems.length === 0) {
                const rEmptyBB = sheet.addRow(['-', 'Tidak ada pengadaan bahan baku', '-', 0, '-', 0, 0, '-']);
                rEmptyBB.height = 19;
                for (let c = 1; c <= 8; c++) {
                    rEmptyBB.getCell(c).border = borderThinBlack;
                    rEmptyBB.getCell(c).font = { name: 'Arial', size: 9 };
                }
                rEmptyBB.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            } else {
                for (let i = 0; i < bbItems.length; i++) {
                    const it = bbItems[i];
                    const rNum = bbStartRow + i;

                    const gross = Number(it.gross_kg) || 0;
                    const stok = Number(it.stok_digunakan_kg || 0);
                    const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
                        ? Number(it.qty_beli_po_kg)
                        : Math.max(0, gross - stok);
                    const harga = Math.round(Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0)));
                    const supplierName = it.supplier?.nama_usaha || po.supplier?.nama_usaha || it.vendor || po.vendor || 'CV. Citra Lestari Abadi';
                    const satuan = it.satuan || 'kg';
                    const ket = resolveItemKeterangan(it);

                    const r = sheet.addRow([
                        i + 1,
                        it.nama || '',
                        supplierName,
                        qtyBeli,
                        satuan,
                        harga,
                        { formula: `ROUND(D${rNum}*F${rNum}, 0)`, result: Math.round(qtyBeli * harga) },
                        ket
                    ]);

                    r.height = 19;
                    r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(2).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(4).alignment = { horizontal: 'right', vertical: 'middle' };
                    const isQtyInt = Math.abs(qtyBeli - Math.round(qtyBeli)) < 0.001;
                    r.getCell(4).numFmt = isQtyInt ? '#,##0' : '#,##0.##';
                    r.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(6).numFmt = accountingNumFmt;
                    r.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(7).numFmt = accountingNumFmt;
                    r.getCell(8).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
                    for (let c = 1; c <= 8; c++) {
                        r.getCell(c).border = borderThinBlack;
                        r.getCell(c).font = { name: 'Arial', size: 9 };
                    }
                }
            }
            const bbEndRow = bbItems.length > 0 ? (bbStartRow + bbItems.length - 1) : bbStartRow;

            // Subtotal Bahan Baku (BB) Row
            const rSubBB = sheet.addRow(['Subtotal Bahan Baku (BB)', '', '', '', '', 'Rp', { formula: `SUM(G${bbStartRow}:G${bbEndRow})` }, '']);
            const subBbN = rSubBB.number;
            sheet.mergeCells(`A${subBbN}:E${subBbN}`);
            rSubBB.height = 20;
            rSubBB.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSubBB.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rSubBB.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rSubBB.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rSubBB.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rSubBB.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rSubBB.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rSubBB.getCell(c).border = borderThinBlack;

            // --- SECTION 2: BARANG OPERASIONAL (OPS) ---
            const rSecOPS = sheet.addRow(['II. REKAPITULASI KEBUTUHAN BARANG OPERASIONAL (OPS)', '', '', '', '', '', '', '']);
            const secOpsN = rSecOPS.number;
            sheet.mergeCells(`A${secOpsN}:H${secOpsN}`);
            rSecOPS.height = 20;
            rSecOPS.getCell(1).font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF5925DC' } };
            rSecOPS.getCell(1).alignment = { horizontal: 'left', vertical: 'middle' };
            rSecOPS.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF3F0FF' } };
            for (let c = 1; c <= 8; c++) rSecOPS.getCell(c).border = borderThinBlack;

            const opsStartRow = sheet.rowCount + 1;
            if (opsItems.length === 0) {
                const rEmptyOPS = sheet.addRow(['-', 'Tidak ada pengadaan barang operasional', '-', 0, '-', 0, 0, '-']);
                rEmptyOPS.height = 19;
                for (let c = 1; c <= 8; c++) {
                    rEmptyOPS.getCell(c).border = borderThinBlack;
                    rEmptyOPS.getCell(c).font = { name: 'Arial', size: 9 };
                }
                rEmptyOPS.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            } else {
                for (let i = 0; i < opsItems.length; i++) {
                    const it = opsItems[i];
                    const rNum = opsStartRow + i;

                    const gross = Number(it.gross_kg) || 0;
                    const stok = Number(it.stok_digunakan_kg || 0);
                    const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
                        ? Number(it.qty_beli_po_kg)
                        : Math.max(0, gross - stok);
                    const harga = Math.round(Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0)));
                    const supplierName = it.supplier?.nama_usaha || po.supplier?.nama_usaha || it.vendor || po.vendor || 'CV. Citra Lestari Abadi';
                    const satuan = it.satuan || 'Pcs';
                    const ket = resolveItemKeterangan(it);

                    const r = sheet.addRow([
                        i + 1,
                        it.nama || '',
                        supplierName,
                        qtyBeli,
                        satuan,
                        harga,
                        { formula: `ROUND(D${rNum}*F${rNum}, 0)`, result: Math.round(qtyBeli * harga) },
                        ket
                    ]);

                    r.height = 19;
                    r.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(2).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };
                    r.getCell(4).alignment = { horizontal: 'right', vertical: 'middle' };
                    const isQtyInt = Math.abs(qtyBeli - Math.round(qtyBeli)) < 0.001;
                    r.getCell(4).numFmt = isQtyInt ? '#,##0' : '#,##0.##';
                    r.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
                    r.getCell(6).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(6).numFmt = accountingNumFmt;
                    r.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
                    r.getCell(7).numFmt = accountingNumFmt;
                    r.getCell(8).alignment = { horizontal: 'left', vertical: 'middle', wrapText: true };
                    for (let c = 1; c <= 8; c++) {
                        r.getCell(c).border = borderThinBlack;
                        r.getCell(c).font = { name: 'Arial', size: 9 };
                    }
                }
            }
            const opsEndRow = opsItems.length > 0 ? (opsStartRow + opsItems.length - 1) : opsStartRow;

            // Subtotal OPS Row
            const rSubOPS = sheet.addRow(['Subtotal Barang Operasional (OPS)', '', '', '', '', 'Rp', { formula: `SUM(G${opsStartRow}:G${opsEndRow})` }, '']);
            const subOpsN = rSubOPS.number;
            sheet.mergeCells(`A${subOpsN}:E${subOpsN}`);
            rSubOPS.height = 20;
            rSubOPS.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSubOPS.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rSubOPS.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rSubOPS.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rSubOPS.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rSubOPS.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rSubOPS.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rSubOPS.getCell(c).border = borderThinBlack;

            // --- REKAPITULASI AKHIR TOTAL PENGELUARAN ---
            // 1. Total Pengeluaran (BB + OPS)
            const rTot = sheet.addRow(['Total Pengeluaran (BB + OPS)', '', '', '', '', 'Rp', { formula: `G${subBbN}+G${subOpsN}` }, '']);
            const totN = rTot.number;
            sheet.mergeCells(`A${totN}:E${totN}`);
            rTot.height = 22;
            rTot.getCell(1).font = { name: 'Arial', size: 10, bold: true };
            rTot.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rTot.getCell(6).font = { name: 'Arial', size: 10, bold: true };
            rTot.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rTot.getCell(7).font = { name: 'Arial', size: 10, bold: true };
            rTot.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rTot.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rTot.getCell(c).border = borderThinBlack;

            // 2. Pagu Anggaran
            const rPagu = sheet.addRow(['Pagu Anggaran', '', '', '', '', 'Rp', paguNominal, '']);
            const paguN = rPagu.number;
            sheet.mergeCells(`A${paguN}:E${paguN}`);
            rPagu.height = 20;
            rPagu.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rPagu.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rPagu.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rPagu.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rPagu.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rPagu.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rPagu.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rPagu.getCell(c).border = borderThinBlack;

            // 3. Sisa Anggaran
            const rSisa = sheet.addRow(['Sisa Anggaran', '', '', '', '', 'Rp', { formula: `G${paguN}-G${totN}` }, '']);
            const sisaN = rSisa.number;
            sheet.mergeCells(`A${sisaN}:E${sisaN}`);
            rSisa.height = 20;
            rSisa.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSisa.getCell(1).alignment = { horizontal: 'right', vertical: 'middle' };
            rSisa.getCell(6).font = { name: 'Arial', size: 9.5, bold: true };
            rSisa.getCell(6).alignment = { horizontal: 'center', vertical: 'middle' };
            rSisa.getCell(7).font = { name: 'Arial', size: 9.5, bold: true };
            rSisa.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' };
            rSisa.getCell(7).numFmt = '#,##0;(#,##0);"-"';
            for (let c = 1; c <= 8; c++) rSisa.getCell(c).border = borderThinBlack;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 14;

            // Tanggal Sukasada
            const rDate = sheet.addRow([`Sukasada, ${orderDateFormatted}`, '', '', '']);
            const dateN = rDate.number;
            sheet.mergeCells(`A${dateN}:D${dateN}`);
            rDate.getCell(1).font = { name: 'Arial', size: 9.5 };
            rDate.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rDate.height = 18;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 10;

            // Tanda Tangan Baris 1: Pengawas Keuangan SPPG & PIC Yayasan
            const rSig1 = sheet.addRow(['Pengawas Keuangan SPPG Buleleng Sukasada Tegallinggah', '', '', '', 'PIC Yayasan Pesantren Miftahul Ulum', '', '', '']);
            const sig1N = rSig1.number;
            sheet.mergeCells(`A${sig1N}:D${sig1N}`);
            sheet.mergeCells(`E${sig1N}:H${sig1N}`);
            rSig1.getCell(1).font = { name: 'Arial', size: 9.5 };
            rSig1.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig1.getCell(5).font = { name: 'Arial', size: 9.5 };
            rSig1.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig1.height = 20;

            // Space TTD
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;

            // Nama Penandatangan Baris 1
            const rSig2 = sheet.addRow(['I Gusti Ayu Made Padmi Swari, S.Ak.', '', '', '', 'Susianah', '', '', '']);
            const sig2N = rSig2.number;
            sheet.mergeCells(`A${sig2N}:D${sig2N}`);
            sheet.mergeCells(`E${sig2N}:H${sig2N}`);
            rSig2.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSig2.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig2.getCell(5).font = { name: 'Arial', size: 9.5, bold: true };
            rSig2.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig2.height = 18;

            sheet.addRow([]); // Gap row
            sheet.getRow(sheet.rowCount).height = 12;

            // Baris Jabatan 2: Kepala SPPG
            const rSig3 = sheet.addRow(['Kepala SPPG Buleleng Sukasada Tegallinggah', '', '', '', '', '', '', '']);
            const sig3N = rSig3.number;
            sheet.mergeCells(`A${sig3N}:H${sig3N}`);
            rSig3.getCell(1).font = { name: 'Arial', size: 9.5 };
            rSig3.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig3.height = 20;

            // Space TTD
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;
            sheet.addRow([]);
            sheet.getRow(sheet.rowCount).height = 16;

            // Nama Penandatangan Baris 2: Kepala SPPG
            const rSig4 = sheet.addRow(['I Gede Gelgel Abdiutama, S.Kom.', '', '', '', '', '', '', '']);
            const sig4N = rSig4.number;
            sheet.mergeCells(`A${sig4N}:H${sig4N}`);
            rSig4.getCell(1).font = { name: 'Arial', size: 9.5, bold: true };
            rSig4.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            rSig4.height = 18;
        }

        // 4. Generate Rekap Sheets (Rekap BB, Rekap OPS, dan Rekap Akhir)
        buildRekapSheet('BB');
        buildRekapSheet('OPS');
        buildRekapAkhirSheet();

        // 5. Generate Supplier Sheets untuk Bahan Baku (BB)
        // Penomoran urut start dari 001/PO/BB/DD/MM/YYYY
        if (bbSupplierGroups.length > 0) {
            bbSupplierGroups.forEach((grp, idx) => {
                const seqStr = String(idx + 1).padStart(3, '0');
                const poNumber = `${seqStr}/PO/BB/${orderDateSlash}`;
                buildSupplierSheet({
                    classification: 'BB',
                    poNumber,
                    supplier: grp.supplier,
                    items: grp.items,
                });
            });
        } else {
            // Default blank supplier sheet for BB
            const poNumber = `001/PO/BB/${orderDateSlash}`;
            buildSupplierSheet({
                classification: 'BB',
                poNumber,
                supplier: {
                    namaUsaha: po.supplier?.nama_usaha || po.vendor || 'Rumah Tempe',
                    namaPemilik: po.supplier?.nama_pemilik || 'Noer Hakim',
                    alamat: po.supplier?.alamat_lengkap || 'Jl. Pulau Sugara No. 31',
                },
                items: [],
            });
        }

        // 6. Generate Supplier Sheets untuk Operasional (OPS)
        // Penomoran urut start dari 001/PO/OPS/DD/MM/YYYY
        if (opsSupplierGroups.length > 0) {
            opsSupplierGroups.forEach((grp, idx) => {
                const seqStr = String(idx + 1).padStart(3, '0');
                const poNumber = `${seqStr}/PO/OPS/${orderDateSlash}`;
                buildSupplierSheet({
                    classification: 'OPS',
                    poNumber,
                    supplier: grp.supplier,
                    items: grp.items,
                });
            });
        }

        // 7. Write to Buffer & Trigger Download
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

    } catch (err) {
        console.error('Error exporting multi-sheet PO Excel:', err);
        alert('Terjadi kesalahan saat mengekspor laporan Excel PO: ' + err.message);
    }
}

export function exportPoWord(po) {
    if (!po) return;

    let totalNominal = 0;
    let totalGrossAll = 0;
    let totalStokAll = 0;
    let totalBeliAll = 0;

    const itemsRows = (po.items || []).map((it, idx) => {
        const gross = Number(it.gross_kg) || 0;
        const stok = Number(it.stok_digunakan_kg || 0);
        const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
            ? Number(it.qty_beli_po_kg)
            : Math.max(0, gross - stok);
        const harga = Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0));
        const subtotal = Number(it.subtotal_aktual !== undefined && it.subtotal_aktual !== null ? it.subtotal_aktual : Math.round(qtyBeli * harga));

        totalGrossAll += gross;
        totalStokAll += stok;
        totalBeliAll += qtyBeli;
        totalNominal += subtotal;

        const supName = it.supplier?.nama_usaha || (it.supplier_id ? `Supplier #${it.supplier_id}` : (po.supplier?.nama_usaha || po.vendor || '-'));
        const trxType = it.jenis_transaksi || po.jenis_transaksi || 'Bahan Baku';

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 5px;">${idx + 1}</td>
            <td style="border: 1px solid #94a3b8; padding: 5px; font-weight: bold;">${it.nama || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 5px;">${it.kategori || '-'}</td>
            <td style="border: 1px solid #94a3b8; padding: 5px; font-weight: 600;">${supName}</td>
            <td style="text-align: center; border: 1px solid #94a3b8; padding: 5px;">${trxType}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${gross.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${stok.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px; font-weight: bold;">${qtyBeli.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    const itemSuppliers = (po.items || []).map(i => i.supplier?.nama_usaha).filter(Boolean);
    const uniqueSuppliersList = Array.from(new Set(itemSuppliers));
    const displayVendor = uniqueSuppliersList.length > 0
        ? uniqueSuppliersList.join(', ')
        : (po.supplier ? (po.supplier.nama_usaha + (po.supplier.jenis_supplier ? ' (' + po.supplier.jenis_supplier + ')' : '')) : (po.vendor && po.vendor !== 'Rekanan Pangan SPPG' ? po.vendor : '-'));

    const displayJenisTrx = (po.items || []).map(i => i.jenis_transaksi).filter(Boolean);
    const uniqueTrxList = Array.from(new Set(displayJenisTrx));
    const displayTrx = uniqueTrxList.length > 0 ? uniqueTrxList.join(', ') : (po.jenis_transaksi || '-');

    const template = `
        <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset="utf-8">
            <title>Purchase Order ${po.id}</title>
            <style>
                body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.4; color: #000; }
                .kop-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
                .kop-title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin: 0; }
                .kop-sub { font-size: 11pt; font-weight: bold; margin: 3px 0; }
                .kop-desc { font-size: 9pt; margin: 0; }
                table.data { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
                table.data th { background-color: #f1f5f9; border: 1px solid #94a3b8; padding: 6px; font-size: 9.5pt; text-align: center; }
                table.data td { font-size: 9.5pt; }
                .info-table td { padding: 3px 0; font-size: 10.5pt; }
                .ttd-box { margin-top: 35px; width: 100%; }
            </style>
        </head>
        <body>
            ${generateKopHtml(null, { isForWord: true })}
            <div style="text-align: center; margin-top: 10px; margin-bottom: 16px;">
                <p style="font-size: 13pt; font-weight: bold; margin: 0; text-transform: uppercase;">SURAT PESANAN PEMBELIAN BAHAN BAKU / PURCHASE ORDER (PO)</p>
                <p style="font-size: 10pt; color: #475569; margin: 2px 0 0 0;">Program Makan Bergizi Gratis (MBG)</p>
            </div>

            <table class="info-table" style="width: 100%;">
                <tr>
                    <td style="width: 180px; font-weight: bold;">Nomor Purchase Order</td>
                    <td style="width: 10px;">:</td>
                    <td><strong>${po.id}</strong></td>
                    <td style="width: 160px; font-weight: bold;">Tanggal Distribusi</td>
                    <td style="width: 10px;">:</td>
                    <td>${formatTanggalIndoFull(po.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Referensi Work Order</td>
                    <td>:</td>
                    <td>${po.wo_id}</td>
                    <td style="font-weight: bold;">Vendor / Supplier</td>
                    <td>:</td>
                    <td><strong>${displayVendor}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Menu MBG</td>
                    <td>:</td>
                    <td>${po.menu}</td>
                    <td style="font-weight: bold;">Jenis Transaksi</td>
                    <td>:</td>
                    <td><strong>${displayTrx}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Sasaran Porsi</td>
                    <td>:</td>
                    <td>${Number(po.total_porsi || 0).toLocaleString('id-ID')} PM (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</td>
                    <td style="font-weight: bold;">Total Nilai PO</td>
                    <td>:</td>
                    <td><strong style="color: #047857; font-size: 11pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status Pembayaran</td>
                    <td>:</td>
                    <td><strong>${po.status_bayar || 'Belum Bayar'}</strong></td>
                    <td style="font-weight: bold;"></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>

            <h4 style="margin-top: 20px; margin-bottom: 5px;">Rincian Item Pembelian Bahan Baku</h4>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 25px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Supplier Rekanan</th>
                        <th>Transaksi</th>
                        <th>Resep Kotor</th>
                        <th>Dari Stok</th>
                        <th>Qty Beli PO</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal PO</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="10" style="text-align: center; padding: 8px;">Tidak ada bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; background-color: #f1f5f9;">
                        <td colspan="5" style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">TOTAL:</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${totalGrossAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${totalStokAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${totalBeliAll.toFixed(2)} kg</td>
                        <td style="border: 1px solid #94a3b8; padding: 5px;"></td>
                        <td style="text-align: right; border: 1px solid #94a3b8; padding: 5px;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                    </tr>
                </tfoot>
            </table>

            <table class="ttd-box" style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center;">
                        <p>Penyedia / Vendor:<br><strong>${displayVendor !== '-' ? displayVendor : 'Rekanan Bahan Pangan'}</strong></p>
                        <br><br><br>
                        <p>( .................................................... )</p>
                    </td>
                    <td style="width: 50%; text-align: center;">
                        <p>Pemesan & Verifikator:<br><strong>Bagian Keuangan SPPG</strong></p>
                        <br><br><br>
                        <p>( .................................................... )</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob(['\ufeff', template], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

export function exportPoPdf(po) {
    if (!po) return;

    let totalNominal = 0;
    let totalGrossAll = 0;
    let totalStokAll = 0;
    let totalBeliAll = 0;

    const itemsRows = (po.items || []).map((it, idx) => {
        const gross = Number(it.gross_kg) || 0;
        const stok = Number(it.stok_digunakan_kg || 0);
        const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
            ? Number(it.qty_beli_po_kg)
            : Math.max(0, gross - stok);
        const harga = Number(it.harga_aktual !== undefined && it.harga_aktual !== null ? it.harga_aktual : (it.harga_master || 0));
        const subtotal = Number(it.subtotal_aktual !== undefined && it.subtotal_aktual !== null ? it.subtotal_aktual : Math.round(qtyBeli * harga));

        totalGrossAll += gross;
        totalStokAll += stok;
        totalBeliAll += qtyBeli;
        totalNominal += subtotal;

        const supName = it.supplier?.nama_usaha || (it.supplier_id ? `Supplier #${it.supplier_id}` : (po.supplier?.nama_usaha || po.vendor || '-'));
        const trxType = it.jenis_transaksi || po.jenis_transaksi || 'Bahan Baku';

        return `
        <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td style="font-weight: 600;">${it.nama || '-'}</td>
            <td>${it.kategori || '-'}</td>
            <td style="font-weight: 600; color: #1e40af;">${supName}</td>
            <td style="text-align: center;">${trxType}</td>
            <td style="text-align: right;">${gross.toFixed(2)} kg</td>
            <td style="text-align: right; color: #1e40af;">${stok.toFixed(2)} kg</td>
            <td style="text-align: right; font-weight: bold; background-color: #ecfdf5;">${qtyBeli.toFixed(2)} kg</td>
            <td style="text-align: right;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
        </tr>
        `;
    }).join('');

    const itemSuppliers = (po.items || []).map(i => i.supplier?.nama_usaha).filter(Boolean);
    const uniqueSuppliersList = Array.from(new Set(itemSuppliers));
    const displayVendor = uniqueSuppliersList.length > 0
        ? uniqueSuppliersList.join(', ')
        : (po.supplier ? (po.supplier.nama_usaha + (po.supplier.jenis_supplier ? ' (' + po.supplier.jenis_supplier + ')' : '')) : (po.vendor && po.vendor !== 'Rekanan Pangan SPPG' ? po.vendor : '-'));

    const displayJenisTrx = (po.items || []).map(i => i.jenis_transaksi).filter(Boolean);
    const uniqueTrxList = Array.from(new Set(displayJenisTrx));
    const displayTrx = uniqueTrxList.length > 0 ? uniqueTrxList.join(', ') : (po.jenis_transaksi || '-');

    const printWindow = window.open('', '_blank', 'width=950,height=750');
    if (!printWindow) {
        alert('Mohon izinkan pop-up pada browser untuk mencetak/mengunduh PDF.');
        return;
    }

    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Order - ${po.id}</title>
            <style>
                @page { size: A4 landscape; margin: 12mm; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 9.5pt; color: #1e293b; margin: 0; }
                .kop { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
                .kop h2 { margin: 0; font-size: 13pt; color: #0f172a; text-transform: uppercase; }
                .kop p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
                .grid-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 14px; font-size: 9pt; }
                .grid-info div span:first-child { font-weight: bold; color: #64748b; display: inline-block; width: 140px; }
                table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 10px; }
                th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 6px; font-weight: bold; text-align: left; }
                td { border: 1px solid #e2e8f0; padding: 5px 6px; }
                .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 8pt; background: #dcfce7; color: #15803d; }
                .ttd { margin-top: 25px; display: flex; justify-content: space-between; page-break-inside: avoid; }
                .ttd-box { text-align: center; width: 220px; font-size: 9pt; }
                .ttd-line { margin-top: 45px; border-bottom: 1px solid #000; }
            </style>
        </head>
        <body>
            ${generateKopHtml(null, { isForWord: false })}
            <div style="text-align: center; margin-top: 8px; margin-bottom: 14px;">
                <h3 style="margin: 0; font-size: 13pt; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">SURAT PESANAN PEMBELIAN BAHAN BAKU / PURCHASE ORDER (PO) RESMI</h3>
                <p style="margin: 2px 0 0 0; font-size: 9pt; color: #64748b;">Program Makan Bergizi Gratis (MBG)</p>
            </div>

            <div class="grid-info">
                <div>
                    <div><span>Nomor PO:</span> <strong style="font-family: monospace;">${po.id}</strong></div>
                    <div><span>Ref Work Order:</span> ${po.wo_id}</div>
                    <div><span>Nama Menu:</span> <strong>${po.menu}</strong></div>
                    <div><span>Tanggal Distribusi:</span> ${formatTanggalIndoFull(po.tanggal)}</div>
                </div>
                <div>
                    <div><span>Supplier Rekanan:</span> <strong>${displayVendor}</strong></div>
                    <div><span>Jenis Transaksi:</span> <strong>${displayTrx}</strong></div>
                    <div><span>Sasaran PM:</span> ${Number(po.total_porsi || 0).toLocaleString('id-ID')} Porsi (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</div>
                    <div><span>Status Bayar:</span> <span class="badge">${po.status_bayar || 'Belum Bayar'}</span></div>
                    <div><span>Total Pembelian:</span> <strong style="color: #047857; font-size: 10.5pt;">${formatRupiahNum(po.total_nominal || totalNominal)}</strong></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 25px; text-align: center;">No</th>
                        <th>Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Supplier Rekanan</th>
                        <th style="text-align: center;">Transaksi</th>
                        <th style="text-align: right;">Resep Kotor</th>
                        <th style="text-align: right;">Dari Stok</th>
                        <th style="text-align: right;">Qty Beli PO</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: right;">Subtotal Pembelian</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows}
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: bold;">
                        <td colspan="5" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">${totalGrossAll.toFixed(2)} kg</td>
                        <td style="text-align: right; color: #1e40af;">${totalStokAll.toFixed(2)} kg</td>
                        <td style="text-align: right; color: #047857;">${totalBeliAll.toFixed(2)} kg</td>
                        <td></td>
                        <td style="text-align: right; color: #047857;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="ttd">
                <div class="ttd-box">
                    <p>Penyedia / Rekanan:<br><strong>${displayVendor !== '-' ? displayVendor : 'Rekanan Pangan SPPG'}</strong></p>
                    <div class="ttd-line"></div>
                </div>
                <div class="ttd-box">
                    <p>Pemesan & Verifikator:<br><strong>Akuntan Keuangan SPPG</strong></p>
                    <div class="ttd-line"></div>
                </div>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}

