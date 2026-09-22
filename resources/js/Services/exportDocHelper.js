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

    const rT4Pk = sheet1.addRow([1, 'Porsi Kecil (PK - PAUD/TK & SD 1-3)', '', data.totalPK, 8000, `${data.totalPK} PM × Rp 8.000`, '', data.paguNominalPK]);
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

    const rT4Pb = sheet1.addRow([2, 'Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)', '', data.totalPB, 10000, `${data.totalPB} PM × Rp 10.000`, '', data.paguNominalPB]);
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
// 2. EXPORT WORK ORDER WORD (.DOCX) DENGAN DOCX (LANDSCAPE & RAPI)
// -------------------------------------------------------------
export async function exportWorkOrderWord(wo) {
    const data = buildWorkOrderFullExportData(wo);
    const sppgName = data.sppgName;
    const filename = `${data.noWO}_${(data.namaMenu || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.docx`;

    // Convert raw base64 to Uint8Array for docx ImageRun
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

    const docxBorderNone = {
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

    // Helper Docx Cell dengan explicit sizing pada TextRun & DXA width
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
        size = 15, // 7.5pt Arial (kompak, rapi, terbaca jelas di landscape)
        shading = null,
        borders = docxBorderThin,
    }) {
        const cellChildren = children || [
            new Paragraph({
                alignment: align,
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
        };
        if (width) {
            config.width = { size: width, type: WidthType.DXA };
        }
        if (colSpan > 1) {
            config.columnSpan = colSpan;
        }
        if (rowSpan > 1) {
            config.rowSpan = rowSpan;
        }
        if (shading) {
            config.shading = shading;
        }
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
            },
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        new TableCell({
                            width: { size: 1800, type: WidthType.DXA },
                            borders: docxBorderNone,
                            verticalAlign: VerticalAlign.CENTER,
                            children: [
                                new Paragraph({
                                    children: [
                                        new ImageRun({
                                            data: bgnBytes,
                                            transformation: { width: 56, height: 56 },
                                        }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 11750, type: WidthType.DXA },
                            borders: docxBorderNone,
                            verticalAlign: VerticalAlign.CENTER,
                            children: [
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'SPPG BULELENG SUKASADA TEGALLINGGAH', bold: true, size: 21, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'YAYASAN PESANTREN MIFTAHUL ULUM', bold: true, size: 19, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali', size: 16, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                }),
                                new Paragraph({
                                    children: [
                                        new TextRun({ text: 'E-mail: sppgsukasadategallinggah@gmail.com', italic: true, size: 16, font: 'Arial' }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 1800, type: WidthType.DXA },
                            borders: docxBorderNone,
                            verticalAlign: VerticalAlign.CENTER,
                            children: [
                                new Paragraph({
                                    children: [
                                        new ImageRun({
                                            data: yayasanBytes,
                                            transformation: { width: 56, height: 56 },
                                        }),
                                    ],
                                    alignment: AlignmentType.CENTER,
                                }),
                            ],
                        }),
                    ],
                }),
            ],
        });
    }

    // ==========================================
    // SECTION 1: SHEET 1 (PERENCANAAN PRODUKSI)
    // ==========================================
    const sheet1Children = [
        createKopDocxTable(),
        new Paragraph({
            children: [
                new TextRun({ text: 'LAPORAN PERENCANAAN PRODUKSI MAKAN BERGIZI GRATIS', bold: true, size: 22, color: '1E3A8A', font: 'Arial' }),
            ],
            alignment: AlignmentType.CENTER,
            spacing: { before: 180, after: 30 },
        }),
        new Paragraph({
            children: [
                new TextRun({ text: sppgName, bold: true, size: 18, color: '4B5563', font: 'Arial' }),
            ],
            alignment: AlignmentType.CENTER,
            spacing: { before: 0, after: 150 },
        }),

        // A. Informasi Perencanaan
        new Paragraph({
            children: [new TextRun({ text: 'A. Informasi Perencanaan', bold: true, size: 18, font: 'Arial' })],
            spacing: { before: 140, after: 80 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderNone,
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No. Perencanaan Produksi', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({ text: data.noWO, width: 4825, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: 'Tanggal Distribusi Menu', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({ text: data.tglDist, width: 4825, bold: true, color: '1E3A8A', size: 16, borders: docxBorderNone }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Status Menu', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({ text: data.statusMenu, width: 4825, bold: true, color: '047857', size: 16, borders: docxBorderNone }),
                        docxCell({ text: 'Nama Menu Produksi', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({ text: data.namaMenu, width: 4825, bold: true, size: 16, borders: docxBorderNone }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Database Pangan', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({ text: data.dbPangan, width: 4825, size: 16, borders: docxBorderNone }),
                        docxCell({ text: 'Total Sasaran PM', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({ text: `${data.totalPM.toLocaleString('id-ID')} PM (${data.totalPK.toLocaleString('id-ID')} PK / ${data.totalPB.toLocaleString('id-ID')} PB)`, width: 4825, bold: true, size: 16, borders: docxBorderNone }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rincian Sub Menu', width: 2600, bold: true, size: 16, borders: docxBorderNone }),
                        docxCell({ text: ':', width: 250, align: AlignmentType.CENTER, size: 16, borders: docxBorderNone }),
                        docxCell({
                            width: 12500,
                            colSpan: 4,
                            borders: docxBorderNone,
                            children: (data.subMenusList && data.subMenusList.length > 0)
                                ? data.subMenusList.map((sm, idx) => new Paragraph({
                                    children: [new TextRun({ text: `${idx + 1}. ${sm.nama}`, size: 16, font: 'Arial' })],
                                    spacing: { before: 15, after: 15 },
                                }))
                                : [new Paragraph({ children: [new TextRun({ text: '-', size: 16, font: 'Arial' })] })],
                        }),
                    ],
                }),
            ],
        }),

        new Paragraph({ spacing: { before: 120 } }),

        // B. Data Penerima Manfaat Terdistribusi
        new Paragraph({
            children: [new TextRun({ text: 'B. Data Penerima Manfaat Terdistribusi', bold: true, size: 18, font: 'Arial' })],
            spacing: { before: 120, after: 60 },
        }),

        // Tabel 1: KPM Menerima (Total 15350 dxa)
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 1. Data Penerima Manfaat yang Menerima Menu', bold: true, color: '047857', size: 16, font: 'Arial' })],
            spacing: { before: 60, after: 40 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 450, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Status', width: 1100, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Nama KPM', width: 4300, bold: true, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Kategori', width: 1200, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Jumlah PK', width: 1100, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Jumlah PB', width: 1100, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Total PM', width: 1300, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Keterangan Alergi (Menu Ini)', width: 4800, bold: true, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                    ],
                }),
                ...data.kpmMenerima.map((k, idx) =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: 450, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: 'Menerima', width: 1100, bold: true, color: '047857', align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.nama, width: 4300, bold: true, size: 15 }),
                            docxCell({ text: k.kategori, width: 1200, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.pk.toLocaleString('id-ID'), width: 1100, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.pb.toLocaleString('id-ID'), width: 1100, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.total.toLocaleString('id-ID'), width: 1300, bold: true, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.keterangan_alergi_terdampak || '-', width: 4800, size: 14 }),
                        ],
                    })
                ),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rekap Total (Menerima):', colSpan: 4, width: 7050, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: data.rekapMenerima.pk.toLocaleString('id-ID'), width: 1100, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: data.rekapMenerima.pb.toLocaleString('id-ID'), width: 1100, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: `${data.rekapMenerima.total.toLocaleString('id-ID')} PM`, width: 1300, bold: true, align: AlignmentType.CENTER, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: '', width: 4800, shading: { fill: 'DCFCE7', type: ShadingType.CLEAR }, size: 15 }),
                    ],
                }),
            ],
        }),

        new Paragraph({ spacing: { before: 120 } }),

        // Tabel 2: KPM Tidak Menerima (Total 15350 dxa)
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 2. Data Penerima Manfaat yang Tidak Menerima Menu (Libur/Off)', bold: true, color: 'B91C1C', size: 16, font: 'Arial' })],
            spacing: { before: 60, after: 40 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 500, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Status', width: 1400, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Nama KPM', width: 6450, bold: true, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Kategori', width: 1800, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Jumlah PK', width: 1600, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Jumlah PB', width: 1600, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Total PM', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                    ],
                }),
                ...(data.kpmTidakMenerima.length === 0 ? [
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({
                                text: 'Seluruh KPM menerima pelayanan makanan pada tanggal ini (Nihil kelompok libur).',
                                colSpan: 7,
                                width: 15350,
                                italic: true,
                                align: AlignmentType.CENTER,
                                color: '64748B',
                                size: 15,
                            }),
                        ],
                    }),
                ] : data.kpmTidakMenerima.map((k, idx) =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: 500, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: 'Libur', width: 1400, bold: true, color: 'B91C1C', align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.nama, width: 6450, bold: true, size: 15 }),
                            docxCell({ text: k.kategori, width: 1800, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.pk.toLocaleString('id-ID'), width: 1600, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.pb.toLocaleString('id-ID'), width: 1600, align: AlignmentType.CENTER, size: 15 }),
                            docxCell({ text: k.total.toLocaleString('id-ID'), width: 2000, bold: true, align: AlignmentType.CENTER, size: 15 }),
                        ],
                    })
                )),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rekap Total (Tidak Menerima):', colSpan: 4, width: 10150, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: data.rekapTidakMenerima.pk.toLocaleString('id-ID'), width: 1600, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: data.rekapTidakMenerima.pb.toLocaleString('id-ID'), width: 1600, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: `${data.rekapTidakMenerima.total.toLocaleString('id-ID')} PM`, width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 15 }),
                    ],
                }),
            ],
        }),

        new Paragraph({ spacing: { before: 120 } }),

        // Tabel 3: Ringkasan Sasaran Porsi Normal dan Alergi (Dynamic cols, sum = 15350)
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi', bold: true, size: 16, font: 'Arial' })],
            spacing: { before: 60, after: 40 },
        }),
        (() => {
            const totalCols = 2 + (data.activeAllergens.length * 2);
            const baseColW = Math.floor(15350 / totalCols);
            const remColW = 15350 - (baseColW * (totalCols - 1));

            const getW = (colIdx) => colIdx === totalCols - 1 ? remColW : baseColW;

            let colTracker = 0;
            const row1Cells = [
                docxCell({ text: 'Sasaran Normal', colSpan: 2, width: getW(0) + getW(1), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 15 }),
            ];
            colTracker += 2;

            data.activeAllergens.forEach((al) => {
                const w1 = getW(colTracker);
                const w2 = getW(colTracker + 1);
                colTracker += 2;
                row1Cells.push(
                    docxCell({ text: `Alergi ${al.jenis}`, colSpan: 2, width: w1 + w2, bold: true, color: '991B1B', align: AlignmentType.CENTER, shading: { fill: 'FEF2F2', type: ShadingType.CLEAR }, size: 15 })
                );
            });

            let colTracker2 = 0;
            const row2Cells = [
                docxCell({ text: 'PK', width: getW(colTracker2++), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                docxCell({ text: 'PB', width: getW(colTracker2++), bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
            ];
            data.activeAllergens.forEach(() => {
                row2Cells.push(docxCell({ text: 'PK', width: getW(colTracker2++), bold: true, color: '991B1B', align: AlignmentType.CENTER, shading: { fill: 'FEF2F2', type: ShadingType.CLEAR }, size: 14 }));
                row2Cells.push(docxCell({ text: 'PB', width: getW(colTracker2++), bold: true, color: '991B1B', align: AlignmentType.CENTER, shading: { fill: 'FEF2F2', type: ShadingType.CLEAR }, size: 14 }));
            });

            let colTracker3 = 0;
            const row3Cells = [
                docxCell({ text: data.normalPK.toLocaleString('id-ID'), width: getW(colTracker3++), bold: true, align: AlignmentType.CENTER, size: 15 }),
                docxCell({ text: data.normalPB.toLocaleString('id-ID'), width: getW(colTracker3++), bold: true, align: AlignmentType.CENTER, size: 15 }),
            ];
            data.activeAllergens.forEach((al) => {
                row3Cells.push(docxCell({ text: al.pk.toLocaleString('id-ID'), width: getW(colTracker3++), align: AlignmentType.CENTER, size: 15 }));
                row3Cells.push(docxCell({ text: al.pb.toLocaleString('id-ID'), width: getW(colTracker3++), align: AlignmentType.CENTER, size: 15 }));
            });

            return new Table({
                width: { size: 15350, type: WidthType.DXA },
                borders: docxBorderThin,
                rows: [
                    new TableRow({ tableHeader: true, cantSplit: true, children: row1Cells }),
                    new TableRow({ tableHeader: true, cantSplit: true, children: row2Cells }),
                    new TableRow({ cantSplit: true, children: row3Cells }),
                ],
            });
        })(),

        new Paragraph({ spacing: { before: 120 } }),

        // Tabel 4: Batas Pagu Anggaran (Total 15350 dxa)
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 4. Perhitungan Mencari Batas Pagu Anggaran (PK Rp 8.000 dan PB Rp 10.000)', bold: true, size: 16, font: 'Arial' })],
            spacing: { before: 60, after: 40 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 500, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Kategori Sasaran Porsi', width: 4850, bold: true, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Jumlah Sasaran (PM)', width: 2200, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Standar Pagu / Porsi', width: 2200, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Rumus Perhitungan', width: 2800, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Total Batas Pagu Anggaran', width: 2800, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 15 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: '1', width: 500, align: AlignmentType.CENTER, size: 15 }),
                        docxCell({ text: 'Porsi Kecil (PK - PAUD/TK & SD 1-3)', width: 4850, bold: true, size: 15 }),
                        docxCell({ text: `${data.totalPK.toLocaleString('id-ID')} PM`, width: 2200, align: AlignmentType.CENTER, size: 15 }),
                        docxCell({ text: formatRupiahNum(data.paguRatePK), width: 2200, align: AlignmentType.RIGHT, size: 15 }),
                        docxCell({ text: `${data.totalPK.toLocaleString('id-ID')} × Rp 8.000`, width: 2800, align: AlignmentType.CENTER, size: 15 }),
                        docxCell({ text: formatRupiahNum(data.paguNominalPK), width: 2800, bold: true, align: AlignmentType.RIGHT, size: 15 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: '2', width: 500, align: AlignmentType.CENTER, size: 15 }),
                        docxCell({ text: 'Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)', width: 4850, bold: true, size: 15 }),
                        docxCell({ text: `${data.totalPB.toLocaleString('id-ID')} PM`, width: 2200, align: AlignmentType.CENTER, size: 15 }),
                        docxCell({ text: formatRupiahNum(data.paguRatePB), width: 2200, align: AlignmentType.RIGHT, size: 15 }),
                        docxCell({ text: `${data.totalPB.toLocaleString('id-ID')} × Rp 10.000`, width: 2800, align: AlignmentType.CENTER, size: 15 }),
                        docxCell({ text: formatRupiahNum(data.paguNominalPB), width: 2800, bold: true, align: AlignmentType.RIGHT, size: 15 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Total Batas Pagu Anggaran MBG:', colSpan: 2, width: 5350, bold: true, align: AlignmentType.RIGHT, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: `${data.totalPM.toLocaleString('id-ID')} PM`, width: 2200, bold: true, align: AlignmentType.CENTER, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: '', width: 2200, shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: 'Pagu PK + Pagu PB', width: 2800, align: AlignmentType.CENTER, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 15 }),
                        docxCell({ text: formatRupiahNum(data.paguTotal), width: 2800, bold: true, align: AlignmentType.RIGHT, color: '1E3A8A', shading: { fill: 'DBEAFE', type: ShadingType.CLEAR }, size: 15 }),
                    ],
                }),
            ],
        }),
    ];

    // ==========================================
    // SECTION 2: SHEET 2 (FORMULA MAKANAN, FOOD COST, GIZI)
    // ==========================================
    const colW_A = [420, 1600, 2200, 1600, 550, 750, 850, 750, 750, 780, 780, 550, 550, 900, 1100, 1200, 800];

    const sheet2Children = [
        createKopDocxTable(),
        new Paragraph({
            children: [
                new TextRun({ text: 'LAPORAN FORMULA MAKANAN & KEBUTUHAN BELANJA MBG', bold: true, size: 22, color: '047857', font: 'Arial' }),
            ],
            alignment: AlignmentType.CENTER,
            spacing: { before: 180, after: 30 },
        }),
        new Paragraph({
            children: [
                new TextRun({ text: sppgName, bold: true, size: 18, color: '4B5563', font: 'Arial' }),
            ],
            alignment: AlignmentType.CENTER,
            spacing: { before: 0, after: 150 },
        }),

        // A. Pemilihan Bahan Pangan
        new Paragraph({
            children: [new TextRun({ text: 'A. Pemilihan Bahan Pangan', bold: true, size: 18, font: 'Arial' })],
            spacing: { before: 140, after: 80 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: colW_A[0], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Sub Menu', width: colW_A[1], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Bahan Master', width: colW_A[2], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Nama PO', width: colW_A[3], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Sat', width: colW_A[4], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Jenis', width: colW_A[5], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Peruntukan', width: colW_A[6], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Berat Bersih (g)', colSpan: 2, width: colW_A[7] + colW_A[8], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Berat Kotor (kg)', colSpan: 2, width: colW_A[9] + colW_A[10], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'BDD', width: colW_A[11], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Buf', width: colW_A[12], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'PO (Kg)', width: colW_A[13], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Harga', width: colW_A[14], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Subtotal', width: colW_A[15], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Ket', width: colW_A[16], rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'PK', width: colW_A[7], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: 'PB', width: colW_A[8], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: 'PK', width: colW_A[9], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                        docxCell({ text: 'PB', width: colW_A[10], bold: true, align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 13 }),
                    ],
                }),
                ...data.formattedItems.map(it =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(it.no), width: colW_A[0], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: it.sub_menu, width: colW_A[1], size: 14 }),
                            docxCell({ text: it.bahan_master, width: colW_A[2], bold: true, size: 14 }),
                            docxCell({ text: it.nama_po, width: colW_A[3], size: 14 }),
                            docxCell({ text: it.satuan, width: colW_A[4], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: it.jenis, width: colW_A[5], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: it.peruntukan, width: colW_A[6], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(it.gram_pk), width: colW_A[7], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(it.gram_pb), width: colW_A[8], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: it.gross_kg_pk.toFixed(2), width: colW_A[9], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: it.gross_kg_pb.toFixed(2), width: colW_A[10], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: `${it.bdd}%`, width: colW_A[11], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: `${it.buffer}%`, width: colW_A[12], align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: it.total_gross.toFixed(2), width: colW_A[13], bold: true, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: formatRupiahNum(it.harga), width: colW_A[14], align: AlignmentType.RIGHT, size: 14 }),
                            docxCell({ text: formatRupiahNum(it.subtotal), width: colW_A[15], bold: true, align: AlignmentType.RIGHT, size: 14 }),
                            docxCell({ text: it.keterangan || '-', width: colW_A[16], size: 13 }),
                        ],
                    })
                ),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Total Rekap Kebutuhan:', colSpan: 7, width: colW_A.slice(0, 7).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: `${data.totalNetKg.toFixed(1)}kg`, colSpan: 2, width: colW_A[7] + colW_A[8], align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: `${data.totalGrossKg.toFixed(1)}kg`, colSpan: 2, width: colW_A[9] + colW_A[10], align: AlignmentType.CENTER, shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: '', colSpan: 2, width: colW_A[11] + colW_A[12], shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: `${data.totalGrossKg.toFixed(1)}kg`, width: colW_A[13], bold: true, align: AlignmentType.CENTER, color: '15803D', shading: { fill: 'F0FDF4', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: '', width: colW_A[14], shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: formatRupiahNum(data.totalBelanja), width: colW_A[15], bold: true, align: AlignmentType.RIGHT, color: '1E3A8A', shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: '', width: colW_A[16], shading: { fill: 'F1F5F9', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Batas Pagu Anggaran:', colSpan: 13, width: colW_A.slice(0, 13).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FFFBEB', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: formatRupiahNum(data.paguTotal), colSpan: 4, width: colW_A.slice(13).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FFFBEB', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Total Belanja Formulasi:', colSpan: 13, width: colW_A.slice(0, 13).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, shading: { fill: 'F0FDF4', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: formatRupiahNum(data.totalBelanja), colSpan: 4, width: colW_A.slice(13).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, color: '1E3A8A', shading: { fill: 'F0FDF4', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Selisih / Efisiensi Anggaran:', colSpan: 13, width: colW_A.slice(0, 13).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, shading: { fill: data.selisihPagu >= 0 ? 'DCFCE7' : 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: `${formatRupiahNum(data.selisihPagu)} ${data.selisihPagu >= 0 ? '(Sisa Pagu)' : '(Melebihi Pagu)'}`, colSpan: 4, width: colW_A.slice(13).reduce((a, b) => a + b, 0), bold: true, align: AlignmentType.RIGHT, color: data.selisihPagu >= 0 ? '15803D' : 'B91C1C', shading: { fill: data.selisihPagu >= 0 ? 'DCFCE7' : 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
            ],
        }),

        new Paragraph({ spacing: { before: 140 } }),

        // B. Food Cost
        new Paragraph({
            children: [new TextRun({ text: 'B. Food Cost', bold: true, size: 18, font: 'Arial' })],
            spacing: { before: 140, after: 60 },
        }),

        // Tabel Food Cost Normal (Total 15350 dxa: 500, 4350, 2000, 2300, 2300, 1950, 1950)
        new Paragraph({
            children: [new TextRun({ text: 'Tabel 1. Rincian Food Cost Per Sub Menu Normal', bold: true, color: '047857', size: 16, font: 'Arial' })],
            spacing: { before: 60, after: 40 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 500, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Sub Menu', width: 4350, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Jumlah Bahan Baku', width: 2000, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Food Cost (Rp)', colSpan: 2, width: 4600, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Persentase (%)', colSpan: 2, width: 3900, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'PK', width: 2300, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'PB', width: 2300, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'PK', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'PB', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                ...data.foodCostNormalList.map((row, idx) =>
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: 500, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: row.sub_menu, width: 4350, bold: true, size: 14 }),
                            docxCell({ text: `${row.count} Bahan`, width: 2000, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: formatRupiahNum(row.cost_pk), width: 2300, align: AlignmentType.RIGHT, size: 14 }),
                            docxCell({ text: formatRupiahNum(row.cost_pb), width: 2300, align: AlignmentType.RIGHT, size: 14 }),
                            docxCell({ text: String(row.percent_pk.toFixed(1)), width: 1950, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(row.percent_pb.toFixed(1)), width: 1950, align: AlignmentType.CENTER, size: 14 }),
                        ],
                    })
                ),
                new TableRow({
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'Rekap Total Food Cost Normal:', colSpan: 3, width: 6850, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: formatRupiahNum(data.totalFcPKNormal), width: 2300, bold: true, align: AlignmentType.RIGHT, color: '0369A1', shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: formatRupiahNum(data.totalFcPBNormal), width: 2300, bold: true, align: AlignmentType.RIGHT, color: '0369A1', shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: '100', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: '100', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'E0F2FE', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
            ],
        }),

        // Allergen Food Cost Tables
        ...data.allergenFoodCostTables.flatMap((alt, tIdx) => [
            new Paragraph({ spacing: { before: 120 } }),
            new Paragraph({
                children: [new TextRun({ text: `Tabel ${tIdx + 2}: Rincian Food Cost Per Sub Menu Alergi ${alt.jenis_alergi}`, bold: true, color: 'B91C1C', size: 16, font: 'Arial' })],
                spacing: { before: 60, after: 40 },
            }),
            new Table({
                width: { size: 15350, type: WidthType.DXA },
                borders: docxBorderThin,
                rows: [
                    new TableRow({
                        tableHeader: true,
                        cantSplit: true,
                        children: [
                            docxCell({ text: 'No', width: 500, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'Sub Menu', width: 4350, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'Jumlah Bahan Baku', width: 2000, rowSpan: 2, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'Food Cost (Rp)', colSpan: 2, width: 4600, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'Persentase (%)', colSpan: 2, width: 3900, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                        ],
                    }),
                    new TableRow({
                        tableHeader: true,
                        cantSplit: true,
                        children: [
                            docxCell({ text: 'PK', width: 2300, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'PB', width: 2300, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'PK', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: 'PB', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                        ],
                    }),
                    ...alt.list.map((row, idx) =>
                        new TableRow({
                            cantSplit: true,
                            children: [
                                docxCell({ text: String(idx + 1), width: 500, align: AlignmentType.CENTER, size: 14 }),
                                docxCell({ text: row.sub_menu, width: 4350, bold: true, size: 14 }),
                                docxCell({ text: `${row.count} Bahan`, width: 2000, align: AlignmentType.CENTER, size: 14 }),
                                docxCell({ text: formatRupiahNum(row.cost_pk), width: 2300, align: AlignmentType.RIGHT, size: 14 }),
                                docxCell({ text: formatRupiahNum(row.cost_pb), width: 2300, align: AlignmentType.RIGHT, size: 14 }),
                                docxCell({ text: String(row.percent_pk.toFixed(1)), width: 1950, align: AlignmentType.CENTER, size: 14 }),
                                docxCell({ text: String(row.percent_pb.toFixed(1)), width: 1950, align: AlignmentType.CENTER, size: 14 }),
                            ],
                        })
                    ),
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: `Rekap Total Food Cost Alergi ${alt.jenis_alergi}:`, colSpan: 3, width: 6850, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: formatRupiahNum(alt.total_pk), width: 2300, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: formatRupiahNum(alt.total_pb), width: 2300, bold: true, align: AlignmentType.RIGHT, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: '100', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: '100', width: 1950, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FEE2E2', type: ShadingType.CLEAR }, size: 14 }),
                        ],
                    }),
                ],
            }),
        ]),

        new Paragraph({ spacing: { before: 140 } }),

        // C. Kandungan Gizi (1 Kolom Peruntukan Porsi, Total 15350 dxa)
        new Paragraph({
            children: [new TextRun({ text: 'C. Kandungan Gizi', bold: true, size: 18, font: 'Arial' })],
            spacing: { before: 140, after: 60 },
        }),
        new Paragraph({
            children: [new TextRun({ text: 'Tabel: Rincian Kandungan Gizi', bold: true, size: 16, font: 'Arial' })],
            spacing: { before: 60, after: 40 },
        }),
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderThin,
            rows: [
                new TableRow({
                    tableHeader: true,
                    cantSplit: true,
                    children: [
                        docxCell({ text: 'No', width: 500, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Peruntukan Porsi', width: 4350, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Jenis PM', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Energi (kkal)', width: 1700, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Protein (g)', width: 1700, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Lemak (g)', width: 1700, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Karbohidrat (g)', width: 1700, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                        docxCell({ text: 'Serat (g)', width: 1700, bold: true, align: AlignmentType.CENTER, shading: { fill: 'F8FAFC', type: ShadingType.CLEAR }, size: 14 }),
                    ],
                }),
                ...data.giziList.flatMap((g, idx) => [
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: String(idx + 1), width: 500, rowSpan: 2, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: g.peruntukan, width: 4350, rowSpan: 2, bold: true, size: 14 }),
                            docxCell({ text: 'PK', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'FFFBEB', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: String(g.pk.energi), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pk.protein), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pk.lemak), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pk.karbo), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pk.serat), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                        ],
                    }),
                    new TableRow({
                        cantSplit: true,
                        children: [
                            docxCell({ text: 'PB', width: 2000, bold: true, align: AlignmentType.CENTER, shading: { fill: 'EFF6FF', type: ShadingType.CLEAR }, size: 14 }),
                            docxCell({ text: String(g.pb.energi), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pb.protein), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pb.lemak), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pb.karbo), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                            docxCell({ text: String(g.pb.serat), width: 1700, align: AlignmentType.CENTER, size: 14 }),
                        ],
                    }),
                ]),
            ],
        }),

        new Paragraph({ spacing: { before: 180 } }),

        // Tanda Tangan (3 Kolom, Total 15350 dxa)
        new Table({
            width: { size: 15350, type: WidthType.DXA },
            borders: docxBorderNone,
            rows: [
                new TableRow({
                    cantSplit: true,
                    children: [
                        new TableCell({
                            width: { size: 5116, type: WidthType.DXA },
                            borders: docxBorderNone,
                            children: [
                                new Paragraph({ children: [new TextRun({ text: 'Direncanakan Oleh:', size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                                new Paragraph({ children: [new TextRun({ text: 'Tim Ahli Gizi SPPG', bold: true, size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                                new Paragraph({ text: '', spacing: { before: 500 } }),
                                new Paragraph({ children: [new TextRun({ text: '( ............................................ )', bold: true, size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 5117, type: WidthType.DXA },
                            borders: docxBorderNone,
                            children: [
                                new Paragraph({ children: [new TextRun({ text: 'Diperiksa Oleh:', size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                                new Paragraph({ children: [new TextRun({ text: 'Petugas Keuangan / Akuntan', bold: true, size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                                new Paragraph({ text: '', spacing: { before: 500 } }),
                                new Paragraph({ children: [new TextRun({ text: '( ............................................ )', bold: true, size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            ],
                        }),
                        new TableCell({
                            width: { size: 5117, type: WidthType.DXA },
                            borders: docxBorderNone,
                            children: [
                                new Paragraph({ children: [new TextRun({ text: 'Mengetahui & Menyetujui:', size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                                new Paragraph({ children: [new TextRun({ text: 'Kepala SPPG', bold: true, size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                                new Paragraph({ text: '', spacing: { before: 500 } }),
                                new Paragraph({ children: [new TextRun({ text: '( ............................................ )', bold: true, size: 16, font: 'Arial' })], alignment: AlignmentType.CENTER }),
                            ],
                        }),
                    ],
                }),
            ],
        }),
    ];

    // Buat Dokumen dengan 2 Section Landscape Beresolusi Tinggi
    const doc = new Document({
        sections: [
            {
                properties: {
                    page: {
                        size: { orientation: PageOrientation.LANDSCAPE },
                        margin: { top: 720, right: 720, bottom: 720, left: 720 },
                    },
                },
                children: sheet1Children,
            },
            {
                properties: {
                    page: {
                        size: { orientation: PageOrientation.LANDSCAPE },
                        margin: { top: 720, right: 720, bottom: 720, left: 720 },
                    },
                },
                children: sheet2Children,
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
// 3. GENERATE HTML (UNTUK PRINT & PREVIEW PDF)
// -------------------------------------------------------------
export function generateWorkOrderHtml(wo, forPrint = false) {
    const data = buildWorkOrderFullExportData(wo);
    const sppgName = data.sppgName;

    // Rows KPM Menerima
    const rowsKpmMenerima = data.kpmMenerima.map((k, idx) => {
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${idx + 1}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold; color: #047857;">Menerima</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${k.nama}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; text-align: center;">${k.kategori}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${k.pk.toLocaleString('id-ID')}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${k.pb.toLocaleString('id-ID')}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${k.total.toLocaleString('id-ID')}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8pt;">${k.keterangan_alergi_terdampak || '-'}</td>
        </tr>
        `;
    }).join('');

    // Rows KPM Tidak Menerima
    const rowsKpmTidakMenerima = data.kpmTidakMenerima.length > 0 ? data.kpmTidakMenerima.map((k, idx) => {
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${idx + 1}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold; color: #b91c1c;">Tidak Menerima (Libur)</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${k.nama}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; text-align: center;">${k.kategori}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${k.pk.toLocaleString('id-ID')}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${k.pb.toLocaleString('id-ID')}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${k.total.toLocaleString('id-ID')}</td>
        </tr>
        `;
    }).join('') : `
        <tr>
            <td colspan="7" style="text-align: center; border: 1px solid #cbd5e1; padding: 8px; color: #64748b; font-style: italic;">Seluruh KPM menerima pelayanan makanan pada tanggal ini (Nihil kelompok libur).</td>
        </tr>
    `;

    // Tabel 3 Ringkasan Alergi (Header 2 Baris: Merge Normal & Alergi, Baris 2 PK & PB)
    let thTabel3_row1 = '<th colspan="2" style="border: 1px solid #94a3b8; padding: 5px; background-color: #f1f5f9; text-align: center;">Sasaran Normal</th>';
    let thTabel3_row2 = '<th style="border: 1px solid #94a3b8; padding: 4px; background-color: #f1f5f9; text-align: center; width: 65px;">PK</th><th style="border: 1px solid #94a3b8; padding: 4px; background-color: #f1f5f9; text-align: center; width: 65px;">PB</th>';
    let tdTabel3 = `<td style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;">${data.normalPK.toLocaleString('id-ID')}</td><td style="border: 1px solid #cbd5e1; padding: 5px; text-align: center; font-weight: bold;">${data.normalPB.toLocaleString('id-ID')}</td>`;

    data.activeAllergens.forEach(al => {
        thTabel3_row1 += `<th colspan="2" style="border: 1px solid #94a3b8; padding: 5px; background-color: #fef2f2; color: #991b1b; text-align: center;">Alergi ${al.jenis}</th>`;
        thTabel3_row2 += '<th style="border: 1px solid #94a3b8; padding: 4px; background-color: #fef2f2; color: #991b1b; text-align: center; width: 65px;">PK</th><th style="border: 1px solid #94a3b8; padding: 4px; background-color: #fef2f2; color: #991b1b; text-align: center; width: 65px;">PB</th>';
        tdTabel3 += `<td style="border: 1px solid #cbd5e1; padding: 5px; text-align: center;">${al.pk.toLocaleString('id-ID')}</td><td style="border: 1px solid #cbd5e1; padding: 5px; text-align: center;">${al.pb.toLocaleString('id-ID')}</td>`;
    });

    // Rows Bahan
    const rowsBahan = data.formattedItems.map(it => {
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.no}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px;">${it.sub_menu}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${it.bahan_master}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px;">${it.nama_po}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.satuan}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.jenis}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.peruntukan}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.gram_pk}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.gram_pb}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.gross_kg_pk.toFixed(2)}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.gross_kg_pb.toFixed(2)}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.bdd}%</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${it.buffer}%</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold; background-color: #f0fdf4;">${it.total_gross.toFixed(2)} ${it.satuan}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(it.harga)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${formatRupiahNum(it.subtotal)}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8pt;">${it.keterangan}</td>
        </tr>
        `;
    }).join('');

    // Rows FC Normal
    const rowsFcNormal = data.foodCostNormalList.map((row, idx) => {
        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${row.sub_menu}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${row.count} Bahan</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(row.cost_pk)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(row.cost_pb)}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${row.percent_pk.toFixed(1)}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${row.percent_pb.toFixed(1)}</td>
        </tr>
        `;
    }).join('');

    // Allergen FC Tables
    const allergenFcTablesHtml = data.allergenFoodCostTables.map((alt, tIdx) => {
        const altRows = alt.list.map((row, idx) => {
            return `
            <tr>
                <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${idx + 1}</td>
                <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${row.sub_menu}</td>
                <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${row.count} Bahan</td>
                <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(row.cost_pk)}</td>
                <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(row.cost_pb)}</td>
                <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${row.percent_pk.toFixed(1)}</td>
                <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${row.percent_pb.toFixed(1)}</td>
            </tr>
            `;
        }).join('');

        return `
        <div style="height: 12px;"></div>
        <h4 style="margin: 0 0 4px; font-size: 9.5pt; color: #b91c1c;">Tabel ${tIdx + 2}: Rincian Food Cost Per Sub Menu Alergi ${alt.jenis_alergi}</h4>
        <table>
            <thead>
                <tr style="background-color: #fee2e2; color: #991b1b;">
                    <th rowspan="2" style="width: 30px;">No</th>
                    <th rowspan="2">Sub Menu</th>
                    <th rowspan="2" style="width: 120px;">Jumlah Bahan Baku</th>
                    <th colspan="2" style="text-align: center;">Food Cost (Rp)</th>
                    <th colspan="2" style="text-align: center;">Persentase (%)</th>
                </tr>
                <tr style="background-color: #fee2e2; color: #991b1b;">
                    <th style="width: 110px; text-align: center;">PK</th>
                    <th style="width: 110px; text-align: center;">PB</th>
                    <th style="width: 75px; text-align: center;">PK</th>
                    <th style="width: 75px; text-align: center;">PB</th>
                </tr>
            </thead>
            <tbody>
                ${altRows}
            </tbody>
            <tfoot>
                <tr style="background-color: #fee2e2; font-weight: bold;">
                    <td colspan="3" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Rekap Total Food Cost Alergi ${alt.jenis_alergi}:</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(alt.total_pk)}</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(alt.total_pb)}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">100</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">100</td>
                </tr>
            </tfoot>
        </table>
        `;
    }).join('');

    // Rows Gizi (Tanpa Kolom Status, Peruntukan Porsi 1 Kolom Saja)
    const rowsGizi = data.giziList.map((g, idx) => {
        return `
        <tr>
            <td rowspan="2" style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: middle;">${idx + 1}</td>
            <td rowspan="2" style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold; vertical-align: middle;">${g.peruntukan}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold; background-color: #fffbeb;">PK</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pk.energi}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pk.protein}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pk.lemak}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pk.karbo}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pk.serat}</td>
        </tr>
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold; background-color: #eff6ff;">PB</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pb.energi}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pb.protein}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pb.lemak}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pb.karbo}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${g.pb.serat}</td>
        </tr>
        `;
    }).join('');

    return `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Work Order - ${data.noWO}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 8.5pt; color: #1e293b; margin: 0; padding: 10px; background: #fff; line-height: 1.35; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pdf-sheet { width: 100%; box-sizing: border-box; background: #fff; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 4px; margin-bottom: 6px; }
        th { background-color: #f8fafc; border: 1px solid #cbd5e1; padding: 5px 6px; font-weight: bold; text-align: center; }
        td { border: 1px solid #cbd5e1; padding: 4px 6px; }
        .sheet-banner { background-color: #1e3a8a; color: #fff; padding: 8px 12px; font-size: 11pt; font-weight: bold; text-align: center; margin: 10px 0; border-radius: 4px; }
        .sec-head { font-size: 10pt; font-weight: bold; color: #0f172a; margin-top: 12px; margin-bottom: 4px; }
        .gap-row { height: 12px; }
        .page-break { page-break-before: always; margin-top: 24px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9pt; }
        .info-table td { border: none; padding: 3px 5px; }
        .ttd-container { width: 100%; margin-top: 28px; border: none; page-break-inside: avoid; }
        .ttd-container td { border: none; text-align: center; }
    </style>
</head>
<body>
    <!-- SHEET 1 -->
    <div class="pdf-sheet" id="pdf-sheet-1">
        ${generateKopHtml(null, { mode: 'print' })}
        
        <div class="sheet-banner">
            <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px;">LAPORAN PERENCANAAN PRODUKSI MAKAN BERGIZI GRATIS</div>
            <div style="font-size: 10.5pt; font-weight: 600; opacity: 0.95; margin-top: 3px;">${sppgName}</div>
        </div>

        <div class="sec-head">A. Informasi Perencanaan</div>
        <table class="info-table">
            <tr>
                <td style="width: 170px; font-weight: bold;">No. Perencanaan Produksi</td>
                <td style="width: 12px; text-align: center;">:</td>
                <td style="font-family: monospace; font-weight: bold;">${data.noWO}</td>
                <td style="width: 170px; font-weight: bold;">Tanggal Distribusi Menu</td>
                <td style="width: 12px; text-align: center;">:</td>
                <td style="font-weight: bold; color: #1e3a8a;">${data.tglDist}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Status Menu</td>
                <td style="text-align: center;">:</td>
                <td><strong style="color: #047857;">${data.statusMenu}</strong></td>
                <td style="font-weight: bold;">Nama Menu Produksi</td>
                <td style="text-align: center;">:</td>
                <td><strong>${data.namaMenu}</strong></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Database Pangan</td>
                <td style="text-align: center;">:</td>
                <td>${data.dbPangan}</td>
                <td style="font-weight: bold;">Total Sasaran PM</td>
                <td style="text-align: center;">:</td>
                <td><strong>${data.totalPM.toLocaleString('id-ID')} PM</strong> (PK: ${data.totalPK.toLocaleString('id-ID')}, PB: ${data.totalPB.toLocaleString('id-ID')})</td>
            </tr>
            <tr>
                <td style="font-weight: bold; vertical-align: top;">Rincian Sub Menu</td>
                <td style="text-align: center; vertical-align: top;">:</td>
                <td colspan="4" style="vertical-align: top; line-height: 1.5;">${data.subMenusList && data.subMenusList.length > 0 ? data.subMenusList.map((sm, idx) => `<div>${idx + 1}. ${sm.nama}</div>`).join('') : '-'}</td>
            </tr>
        </table>

        <div class="gap-row"></div>

        <div class="sec-head">B. Data Penerima Manfaat Terdistribusi</div>

        <div class="gap-row"></div>

        <p style="margin: 0 0 4px; font-weight: bold; color: #047857;">Tabel 1. Data Penerima Manfaat yang Menerima Menu</p>
        <table>
            <thead>
                <tr style="background-color: #dcfce7;">
                    <th style="width: 25px;">No</th>
                    <th style="width: 70px;">Status</th>
                    <th>Nama KPM</th>
                    <th style="width: 60px;">Kategori</th>
                    <th style="width: 70px;">Jumlah PK</th>
                    <th style="width: 70px;">Jumlah PB</th>
                    <th style="width: 75px;">Total PM</th>
                    <th>Keterangan Alergi (Menu Ini)</th>
                </tr>
            </thead>
            <tbody>
                ${rowsKpmMenerima}
            </tbody>
            <tfoot>
                <tr style="background-color: #dcfce7; font-weight: bold;">
                    <td colspan="4" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Rekap Total (Menerima):</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.rekapMenerima.pk.toLocaleString('id-ID')}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.rekapMenerima.pb.toLocaleString('id-ID')}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.rekapMenerima.total.toLocaleString('id-ID')} PM</td>
                    <td style="border: 1px solid #cbd5e1; padding: 4px 5px;"></td>
                </tr>
            </tfoot>
        </table>

        <div class="gap-row"></div>

        <p style="margin: 0 0 4px; font-weight: bold; color: #b91c1c;">Tabel 2. Data Penerima Manfaat yang Tidak Menerima Menu (Libur/Off)</p>
        <table>
            <thead>
                <tr style="background-color: #fee2e2;">
                    <th style="width: 25px;">No</th>
                    <th style="width: 90px;">Status</th>
                    <th>Nama KPM</th>
                    <th style="width: 60px;">Kategori</th>
                    <th style="width: 70px;">Jumlah PK</th>
                    <th style="width: 70px;">Jumlah PB</th>
                    <th style="width: 75px;">Total PM</th>
                </tr>
            </thead>
            <tbody>
                ${rowsKpmTidakMenerima}
            </tbody>
            <tfoot>
                <tr style="background-color: #fee2e2; font-weight: bold;">
                    <td colspan="4" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Rekap Total (Tidak Menerima):</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.rekapTidakMenerima.pk.toLocaleString('id-ID')}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.rekapTidakMenerima.pb.toLocaleString('id-ID')}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.rekapTidakMenerima.total.toLocaleString('id-ID')} PM</td>
                </tr>
            </tfoot>
        </table>

        <div class="gap-row"></div>

        <p style="margin: 0 0 4px; font-weight: bold;">Tabel 3. Ringkasan Sasaran Porsi Normal dan Alergi</p>
        <table>
            <thead>
                <tr>
                    ${thTabel3_row1}
                </tr>
                <tr>
                    ${thTabel3_row2}
                </tr>
            </thead>
            <tbody>
                <tr>
                    ${tdTabel3}
                </tr>
            </tbody>
        </table>

        <div class="gap-row"></div>

        <p style="margin: 0 0 4px; font-weight: bold;">Tabel 4. Perhitungan Mencari Batas Pagu Anggaran (PK Rp 8.000 dan PB Rp 10.000)</p>
        <table>
            <thead>
                <tr style="background-color: #f8fafc;">
                    <th style="width: 25px;">No</th>
                    <th>Kategori Sasaran Porsi</th>
                    <th style="width: 110px;">Jumlah Sasaran (PM)</th>
                    <th style="width: 120px;">Standar Pagu / Porsi</th>
                    <th style="width: 160px;">Rumus Perhitungan</th>
                    <th style="width: 140px;">Total Batas Pagu Anggaran</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">1</td>
                    <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">Porsi Kecil (PK - PAUD/TK & SD 1-3)</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.totalPK.toLocaleString('id-ID')} PM</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(data.paguRatePK)}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-family: monospace;">${data.totalPK} × Rp 8.000</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${formatRupiahNum(data.paguNominalPK)}</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">2</td>
                    <td style="border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">Porsi Besar (PB - SD 4-6, SMP, SMA, Tendik)</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.totalPB.toLocaleString('id-ID')} PM</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(data.paguRatePB)}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; font-family: monospace;">${data.totalPB} × Rp 10.000</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; font-weight: bold;">${formatRupiahNum(data.paguNominalPB)}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background-color: #dbeafe; font-weight: bold; color: #1e3a8a;">
                    <td colspan="2" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Total Batas Pagu Anggaran MBG:</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.totalPM.toLocaleString('id-ID')} PM</td>
                    <td style="border: 1px solid #cbd5e1; padding: 4px 5px;"></td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">Pagu PK + Pagu PB</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(data.paguTotal)}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="page-break"></div>

    <!-- SHEET 2 -->
    <div class="pdf-sheet" id="pdf-sheet-2">
        ${generateKopHtml(null, { mode: 'print' })}

        <div class="sheet-banner" style="background-color: #047857;">
            <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px;">LAPORAN FORMULA MAKANAN & KEBUTUHAN BELANJA MBG</div>
            <div style="font-size: 10.5pt; font-weight: 600; opacity: 0.95; margin-top: 3px;">${sppgName}</div>
        </div>

        <div class="sec-head">A. Pemilihan Bahan Pangan</div>
        <table>
            <thead>
                <tr style="background-color: #f8fafc;">
                    <th rowspan="2" style="width: 25px;">No</th>
                    <th rowspan="2">Sub Menu</th>
                    <th rowspan="2">Bahan Master</th>
                    <th rowspan="2">Nama PO</th>
                    <th rowspan="2" style="width: 35px;">Sat</th>
                    <th rowspan="2" style="width: 60px;">Jenis</th>
                    <th rowspan="2" style="width: 70px;">Peruntukan</th>
                    <th colspan="2" style="text-align: center;">Berat Bersih (g)</th>
                    <th colspan="2" style="text-align: center;">Berat Kotor (kg)</th>
                    <th rowspan="2" style="width: 40px;">BDD</th>
                    <th rowspan="2" style="width: 40px;">Buffer</th>
                    <th rowspan="2" style="width: 75px; background-color: #f0fdf4;">Kebutuhan (PO)</th>
                    <th rowspan="2" style="width: 80px;">Harga/Satuan</th>
                    <th rowspan="2" style="width: 90px;">Subtotal</th>
                    <th rowspan="2">Keterangan</th>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <th style="width: 50px; text-align: center;">PK</th>
                    <th style="width: 50px; text-align: center;">PB</th>
                    <th style="width: 60px; text-align: center;">PK</th>
                    <th style="width: 60px; text-align: center;">PB</th>
                </tr>
            </thead>
            <tbody>
                ${rowsBahan}
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="7" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Total Rekap Kebutuhan:</td>
                    <td colspan="2" style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.totalNetKg.toFixed(2)} kg</td>
                    <td colspan="2" style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">${data.totalGrossKg.toFixed(2)} kg</td>
                    <td colspan="2" style="border: 1px solid #cbd5e1; padding: 4px 5px;"></td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px; background-color: #f0fdf4; color: #15803d;">${data.totalGrossKg.toFixed(2)} Kg</td>
                    <td style="border: 1px solid #cbd5e1; padding: 4px 5px;"></td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; color: #1e3a8a;">${formatRupiahNum(data.totalBelanja)}</td>
                    <td style="border: 1px solid #cbd5e1; padding: 4px 5px;"></td>
                </tr>
                <tr style="background-color: #fffbeb; font-weight: bold;">
                    <td colspan="13" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Batas Pagu Anggaran:</td>
                    <td colspan="4" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">${formatRupiahNum(data.paguTotal)}</td>
                </tr>
                <tr style="background-color: #f0fdf4; font-weight: bold;">
                    <td colspan="13" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Total Belanja Formulasi:</td>
                    <td colspan="4" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; color: #1e3a8a;">${formatRupiahNum(data.totalBelanja)}</td>
                </tr>
                <tr style="background-color: ${data.selisihPagu >= 0 ? '#dcfce7' : '#fee2e2'}; font-weight: bold;">
                    <td colspan="13" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Selisih / Efisiensi Anggaran:</td>
                    <td colspan="4" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; color: ${data.selisihPagu >= 0 ? '#15803d' : '#b91c1c'};">${formatRupiahNum(data.selisihPagu)} ${data.selisihPagu >= 0 ? '(Sisa Pagu)' : '(Melebihi Pagu)'}</td>
                </tr>
            </tfoot>
        </table>

        <div class="gap-row"></div>

        <div class="sec-head">B. Food Cost</div>

        <p style="margin: 0 0 4px; font-weight: bold; color: #047857;">Tabel 1. Rincian Food Cost Per Sub Menu Normal</p>
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
            <tbody>
                ${rowsFcNormal}
            </tbody>
            <tfoot>
                <tr style="background-color: #e0f2fe; font-weight: bold;">
                    <td colspan="3" style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px;">Rekap Total Food Cost Normal:</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; color: #0369a1;">${formatRupiahNum(data.totalFcPKNormal)}</td>
                    <td style="text-align: right; border: 1px solid #cbd5e1; padding: 4px 5px; color: #0369a1;">${formatRupiahNum(data.totalFcPBNormal)}</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">100</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; padding: 4px 5px;">100</td>
                </tr>
            </tfoot>
        </table>

        ${allergenFcTablesHtml}

        <div class="gap-row"></div>

        <div class="sec-head">C. Kandungan Gizi</div>
        <p style="margin: 0 0 4px; font-weight: bold;">Tabel: Rincian Kandungan Gizi</p>
        <table>
            <thead>
                <tr style="background-color: #f8fafc;">
                    <th style="width: 25px;">No</th>
                    <th>Peruntukan Porsi</th>
                    <th style="width: 110px;">Jenis PM</th>
                    <th style="width: 100px;">Energi (kkal)</th>
                    <th style="width: 90px;">Protein (g)</th>
                    <th style="width: 90px;">Lemak (g)</th>
                    <th style="width: 110px;">Karbohidrat (g)</th>
                    <th style="width: 90px;">Serat (g)</th>
                </tr>
            </thead>
            <tbody>
                ${rowsGizi}
            </tbody>
        </table>

        <div class="gap-row"></div>

        <table class="ttd-container">
            <tr>
                <td style="width: 33%;">
                    <p style="margin: 0; font-size: 8.5pt;">Direncanakan Oleh:<br><strong>Tim Ahli Gizi SPPG</strong></p>
                    <br><br><br>
                    <p style="margin: 0; font-size: 8.5pt; font-weight: bold;">( ............................................ )</p>
                </td>
                <td style="width: 33%;">
                    <p style="margin: 0; font-size: 8.5pt;">Diperiksa Oleh:<br><strong>Petugas Keuangan / Akuntan</strong></p>
                    <br><br><br>
                    <p style="margin: 0; font-size: 8.5pt; font-weight: bold;">( ............................................ )</p>
                </td>
                <td style="width: 34%;">
                    <p style="margin: 0; font-size: 8.5pt;">Mengetahui & Menyetujui:<br><strong>Kepala SPPG</strong></p>
                    <br><br><br>
                    <p style="margin: 0; font-size: 8.5pt; font-weight: bold;">( ............................................ )</p>
                </td>
            </tr>
        </table>
    </div>

    ${forPrint ? `
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    ` : ''}
</body>
</html>`;
}

// -------------------------------------------------------------
// 4. EXPORT WORK ORDER PDF (.PDF) DENGAN IFRAME & DISCRETE PAGES
// -------------------------------------------------------------
export async function exportWorkOrderPdf(wo) {
    const data = buildWorkOrderFullExportData(wo);
    const filename = `${data.noWO}_${(data.namaMenu || 'Menu').replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;

    // Buat iframe tersembunyi agar CSS, Fonts, dan Style tabel dirender utuh oleh browser
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.top = '-99999px';
    iframe.style.left = '-99999px';
    iframe.style.width = '1200px';
    iframe.style.height = '2200px';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    try {
        const fullHtml = generateWorkOrderHtml(wo, false);
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(fullHtml);
        iframeDoc.close();

        // Tunggu sejenak agar layout rendering dan font stabil
        await new Promise(r => setTimeout(r, 250));

        const doc = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4',
        });

        const pageWidth = doc.internal.pageSize.getWidth(); // 297 mm
        const pageHeight = doc.internal.pageSize.getHeight(); // 210 mm
        const margin = 8;
        const printWidth = pageWidth - (margin * 2); // 281 mm
        const maxPrintHeight = pageHeight - (margin * 2); // 194 mm

        const sheet1El = iframeDoc.getElementById('pdf-sheet-1');
        const sheet2El = iframeDoc.getElementById('pdf-sheet-2');

        const sheetsToRender = [];
        if (sheet1El && sheet2El) {
            sheetsToRender.push(sheet1El, sheet2El);
        } else {
            sheetsToRender.push(iframeDoc.body);
        }

        for (let i = 0; i < sheetsToRender.length; i++) {
            if (i > 0) {
                doc.addPage('a4', 'landscape');
            }
            const el = sheetsToRender[i];
            const canvas = await html2canvas(el, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                windowWidth: 1200,
            });

            const imgData = canvas.toDataURL('image/jpeg', 0.95);
            const imgHeight = (canvas.height * printWidth) / canvas.width;

            if (imgHeight <= maxPrintHeight) {
                doc.addImage(imgData, 'JPEG', margin, margin, printWidth, imgHeight);
            } else {
                // Jika sheet panjang (misal daftar bahan sangat banyak), paginasi rapi
                let heightLeft = imgHeight;
                let position = margin;
                doc.addImage(imgData, 'JPEG', margin, position, printWidth, imgHeight);
                heightLeft -= maxPrintHeight;

                while (heightLeft > 0) {
                    position = heightLeft - imgHeight + margin;
                    doc.addPage('a4', 'landscape');
                    doc.addImage(imgData, 'JPEG', margin, position, printWidth, imgHeight);
                    heightLeft -= maxPrintHeight;
                }
            }
        }

        doc.save(filename);
    } catch (err) {
        console.error('Gagal mengunduh file PDF via jsPDF:', err);
        printWorkOrder(wo);
    } finally {
        if (iframe && iframe.parentNode) {
            document.body.removeChild(iframe);
        }
    }
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

export function exportPoExcel(po) {
    const filename = `${po.id || 'PO-MBG'}_${(po.menu || 'Belanja').replace(/[^a-zA-Z0-9]/g, '_')}.xls`;
    const items = po.items || [];

    let totalNominal = 0;
    let totalGrossAll = 0;
    let totalStokAll = 0;
    let totalBeliAll = 0;

    let itemsRows = items.map((it, idx) => {
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

        let statusKet = 'Beli PO';
        if (qtyBeli === 0 || (stok >= gross && gross > 0)) {
            statusKet = '100% Dari Stok';
        } else if (stok > 0) {
            statusKet = 'Parsial Stok';
        }

        return `
        <tr>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${idx + 1}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${it.nama || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px;">${it.kategori || '-'}</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-weight: 600; color: #1e40af;">${supName}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${trxType}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${gross.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; color: #1e40af;">${stok.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold; background-color: #ecfdf5;">${qtyBeli.toFixed(2)} kg</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px;">${formatRupiahNum(harga)}</td>
            <td style="text-align: right; border: 1px solid #cbd5e1; padding: 6px; font-weight: bold;">${formatRupiahNum(subtotal)}</td>
            <td style="text-align: center; border: 1px solid #cbd5e1; padding: 6px;">${statusKet}</td>
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
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Purchase Order</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
            <style>
                body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #94a3b8; padding: 8px; }
                .title { font-size: 16pt; font-weight: bold; text-align: center; color: #0f172a; margin-bottom: 4px; }
                .subtitle { font-size: 12pt; text-align: center; color: #475569; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="title">SURAT PESANAN / PURCHASE ORDER (PO) RESMI</div>
            <div class="subtitle">Satuan Pelayanan Program Gizi (SPPG) - MBG</div>
            
            <table style="margin-bottom: 20px; width: 600px;">
                <tr>
                    <td style="font-weight: bold; width: 180px;">Nomor Purchase Order</td>
                    <td>: ${po.id || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Referensi Work Order</td>
                    <td>: ${po.wo_id || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Nama Menu</td>
                    <td>: ${po.menu || '-'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tanggal Distribusi</td>
                    <td>: ${formatTanggalIndoFull(po.tanggal)}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Vendor / Rekanan</td>
                    <td>: ${displayVendor}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Jenis Transaksi</td>
                    <td>: <strong>${displayTrx}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Sasaran PM</td>
                    <td>: ${Number(po.total_porsi || 0).toLocaleString('id-ID')} Porsi (${po.porsi_pk || 0} PK / ${po.porsi_pb || 0} PB)</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status PO / Pembayaran</td>
                    <td>: ${po.status_po || 'Disetujui'} / ${po.status_bayar || 'Belum Bayar'}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Total Belanja PO</td>
                    <td>: <strong>${formatRupiahNum(po.total_nominal || totalNominal)}</strong></td>
                </tr>
            </table>

            <h3 style="margin-top: 20px; color: #1e293b;">RINCIAN BAHAN BAKU, PENGALIHAN STOK & NILAI PEMBELIAN PO</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th>Kategori</th>
                        <th>Supplier Rekanan</th>
                        <th>Jenis Transaksi</th>
                        <th>Resep Kotor (Kg)</th>
                        <th>Dari Stok (Kg)</th>
                        <th>Qty Beli PO (Kg)</th>
                        <th>Harga Satuan Aktual</th>
                        <th>Subtotal Pembelian</th>
                        <th>Status Pengadaan</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows || '<tr><td colspan="11" style="text-align: center; padding: 10px;">Tidak ada data bahan</td></tr>'}
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: bold;">
                        <td colspan="5" style="text-align: right; border: 1px solid #cbd5e1; padding: 8px;">TOTAL KESELURUHAN:</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px;">${totalGrossAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; color: #1e40af;">${totalStokAll.toFixed(2)} kg</td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; color: #047857;">${totalBeliAll.toFixed(2)} kg</td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px;"></td>
                        <td style="text-align: right; border: 1px solid #cbd5e1; padding: 8px; font-weight: bold; color: #047857;">${formatRupiahNum(po.total_nominal || totalNominal)}</td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px;"></td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([template], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
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
