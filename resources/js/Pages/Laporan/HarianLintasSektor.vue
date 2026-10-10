<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    FileText,
    Copy,
    Check,
    Calendar,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    Share2,
    Download,
    Clock,
    School,
    HeartHandshake,
    Utensils,
    CheckCircle2,
    AlertCircle,
    Info,
    Send,
    Edit3,
    Eye,
    RotateCcw,
    ShieldAlert,
} from "lucide-vue-next";
import Button from "@/Components/ui/Button.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: () => ({}),
    },
    kopConfig: {
        type: Object,
        default: () => ({}),
    },
    kepalaSppg: {
        type: String,
        default: "",
    },
    selectedDate: {
        type: String,
        default: "",
    },
    todayDate: {
        type: String,
        default: "",
    },
    workOrder: {
        type: Object,
        default: null,
    },
    calendarWorkOrders: {
        type: Array,
        default: () => [],
    },
});

// Format Tanggal Indonesia (Hari, DD Bulan YYYY)
function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    const parts = dateStr.split("-");
    if (parts.length !== 3) return dateStr;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    const d = new Date(year, month, day);

    const days = [
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
    ];
    const months = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];

    const hari = days[d.getDay()];
    const tgl = String(day).padStart(2, "0");
    const bln = months[month];
    const thn = year;
    return `${hari}, ${tgl} ${bln} ${thn}`;
}

// Helper Title Case
function toTitleCase(str) {
    if (!str) return "";
    return str
        .toLowerCase()
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
}

// Identitas SPPG & Yayasan (Database original tanpa paksa uppercase)
const namaSppgDb = computed(() => {
    return (
        props.unitSppg?.nama ||
        props.kopConfig?.nama_unit ||
        "SPPG Buleleng Sukasada Tegallinggah"
    );
});

const namaYayasanDb = computed(() => {
    const raw =
        props.kopConfig?.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM";
    if (raw === raw.toUpperCase()) {
        return toTitleCase(raw);
    }
    return raw;
});

// Format UPPERCASE untuk Judul & Header Yayasan
const namaSppgUpper = computed(() => {
    return (
        props.kopConfig?.nama_instansi_1 ||
        namaSppgDb.value ||
        "SPPG BULELENG SUKASADA TEGALLINGGAH"
    ).toUpperCase();
});

const namaYayasanUpper = computed(() => {
    return (
        props.kopConfig?.nama_instansi_2 || "YAYASAN PESANTREN MIFTAHUL ULUM"
    ).toUpperCase();
});

const idSppg = computed(() => {
    return props.kopConfig?.id_sppg || props.unitSppg?.id_sppg || "QQCV0LUG";
});

const alamatSppg = computed(() => {
    return (
        props.unitSppg?.alamat_lengkap ||
        props.kopConfig?.alamat_lengkap ||
        "Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali"
    );
});

const namaKepalaSppg = computed(() => {
    return props.kepalaSppg || "I Gede Gelgel Abdiutama, S.Kom.";
});

// Jadwal Kegiatan Operasional (6 Tahapan)
const defaultJadwal = [
    { nama: "Persiapan", mulai: "19.00", selesai: "03.00" },
    { nama: "Pengolahan", mulai: "01.00", selesai: "09.00" },
    { nama: "Pemorsian", mulai: "04.00", selesai: "12.00" },
    { nama: "Uji Organolaptik", mulai: "05.00", selesai: "06.00" },
    { nama: "Distribusi", mulai: "06.00", selesai: "14.00" },
    { nama: "Pencucian Ompreng", mulai: "12.00", selesai: "20.00" },
];

const jadwalOperasionalList = computed(() => {
    const raw = props.workOrder?.jadwal_operasional;
    if (!raw) return defaultJadwal;
    const parsed = typeof raw === "string" ? JSON.parse(raw) : raw;
    if (!parsed || typeof parsed !== "object") return defaultJadwal;

    const keys = [
        "persiapan",
        "pengolahan",
        "pemorsian",
        "uji_organolaptik",
        "distribusi",
        "pencucian_ompreng",
    ];
    const labels = {
        persiapan: "Persiapan",
        pengolahan: "Pengolahan",
        pemorsian: "Pemorsian",
        uji_organolaptik: "Uji Organolaptik",
        distribusi: "Distribusi",
        pencucian_ompreng: "Pencucian Ompreng",
    };

    return keys.map((k) => {
        const item = parsed[k] || {};
        const def = defaultJadwal.find((d) => d.nama === labels[k]) || {};
        const mulaiRaw = item.mulai ? item.mulai.replace(":", ".") : def.mulai;
        const selesaiRaw = item.selesai
            ? item.selesai.replace(":", ".")
            : def.selesai;
        return {
            nama: labels[k],
            mulai: mulaiRaw,
            selesai: selesaiRaw,
        };
    });
});

// Kelompok Penerima Manfaat
const kelompokMenerima = computed(() => {
    const list = props.workOrder?.kelompoks || [];
    return list.filter((k) => k.is_menerima !== false);
});

// Helper untuk mengambil rincian penerima manfaat yang valid (prioritaskan yang memiliki angka > 0)
function getValidRincian(k) {
    const kel = k.kelompok || {};
    if (
        Array.isArray(k.rincian) &&
        k.rincian.some((r) => Number(r.total || 0) > 0)
    ) {
        return k.rincian;
    }
    if (Array.isArray(kel.rincian) && kel.rincian.length > 0) {
        return kel.rincian;
    }
    if (Array.isArray(k.rincian)) {
        return k.rincian;
    }
    return [];
}

// Sekolah (kategori !== 'Posyandu')
const sekolahList = computed(() => {
    return kelompokMenerima.value
        .filter((k) => {
            const kat = (
                k.kelompok?.kategori ||
                k.kategori ||
                ""
            ).toLowerCase();
            return kat !== "posyandu";
        })
        .map((k) => {
            const kel = k.kelompok || {};
            const nama = kel.nama_kelompok || k.nama_kelompok || "Sekolah";
            const rincian = getValidRincian(k);

            let siswa = 0;
            let guru = 0;
            let tendik = 0;

            if (Array.isArray(rincian) && rincian.length > 0) {
                rincian.forEach((r) => {
                    const sub = (r.sub_kategori || "").toLowerCase();
                    const tot =
                        Number(
                            r.total ??
                                Number(r.jumlah_laki_laki || 0) +
                                    Number(r.jumlah_perempuan || 0),
                        ) || 0;

                    // PENTING: Cek Tendik DULUAN sebelum Guru, karena 'tenaga kependidikan' mengandung kata 'pendidik'
                    if (
                        sub.includes("tendik") ||
                        sub.includes("tenaga kependidikan") ||
                        sub.includes("kependidikan") ||
                        sub.includes("satpam") ||
                        sub.includes("tata usaha") ||
                        sub.includes("tu") ||
                        sub.includes("penjaga")
                    ) {
                        tendik += tot;
                    } else if (
                        sub.includes("guru") ||
                        sub.includes("pendidik")
                    ) {
                        guru += tot;
                    } else {
                        siswa += tot;
                    }
                });
            }

            const totalPorsi =
                Number(k.total_penerima ?? siswa + guru + tendik) || 0;

            // Fallback jika rincian belum terisi namun ada porsi kecil/besar
            if (siswa === 0 && guru === 0 && tendik === 0 && totalPorsi > 0) {
                const pk = Number(k.porsi_kecil ?? k.total_porsi_kecil ?? 0);
                const pb = Number(k.porsi_besar ?? k.total_porsi_besar ?? 0);
                siswa = pk;
                guru = pb;
            }

            return {
                nama,
                siswa,
                guru,
                tendik,
                totalPorsi: totalPorsi || siswa + guru + tendik,
            };
        });
});

// Posyandu 3B (Bumil, Busui, Balita) (kategori === 'Posyandu')
const posyanduList = computed(() => {
    return kelompokMenerima.value
        .filter((k) => {
            const kat = (
                k.kelompok?.kategori ||
                k.kategori ||
                ""
            ).toLowerCase();
            return kat === "posyandu";
        })
        .map((k) => {
            const kel = k.kelompok || {};
            const nama = kel.nama_kelompok || k.nama_kelompok || "Posyandu";
            const rincian = getValidRincian(k);

            let bumil = 0;
            let busui = 0;
            let balita = 0;

            if (Array.isArray(rincian) && rincian.length > 0) {
                rincian.forEach((r) => {
                    const sub = (r.sub_kategori || "").toLowerCase();
                    const tot =
                        Number(
                            r.total ??
                                Number(r.jumlah_laki_laki || 0) +
                                    Number(r.jumlah_perempuan || 0),
                        ) || 0;

                    if (sub.includes("hamil") || sub.includes("bumil")) {
                        bumil += tot;
                    } else if (
                        sub.includes("menyusui") ||
                        sub.includes("busui")
                    ) {
                        busui += tot;
                    } else {
                        balita += tot;
                    }
                });
            }

            const totalPorsi =
                Number(k.total_penerima ?? bumil + busui + balita) || 0;

            // Fallback jika rincian belum terisi
            if (bumil === 0 && busui === 0 && balita === 0 && totalPorsi > 0) {
                const pk = Number(k.porsi_kecil ?? k.total_porsi_kecil ?? 0);
                const pb = Number(k.porsi_besar ?? k.total_porsi_besar ?? 0);
                balita = pk;
                busui = pb;
            }

            return {
                nama,
                bumil,
                busui,
                balita,
                totalPorsi: totalPorsi || bumil + busui + balita,
            };
        });
});

const totalPorsiSekolah = computed(() => {
    return sekolahList.value.reduce((acc, it) => acc + it.totalPorsi, 0);
});

const totalPorsiPosyandu = computed(() => {
    return posyanduList.value.reduce((acc, it) => acc + it.totalPorsi, 0);
});

const totalPorsiKeseluruhan = computed(() => {
    return (
        Number(props.workOrder?.total_pm) ||
        totalPorsiSekolah.value + totalPorsiPosyandu.value
    );
});

// Menu MBG & Kandungan Gizi
const subMenuNormalList = computed(() => {
    const wo = props.workOrder || {};
    if (Array.isArray(wo.sub_menus) && wo.sub_menus.length > 0) {
        return wo.sub_menus.filter(Boolean);
    }
    const items = [
        wo.sub_menu_1,
        wo.sub_menu_2,
        wo.sub_menu_3,
        wo.sub_menu_4,
        wo.sub_menu_5,
    ].filter(Boolean);
    return items.length > 0
        ? items
        : wo.nama_menu
          ? [wo.nama_menu]
          : ["Nasi Putih", "Lauk Utama", "Lauk Pendamping", "Sayur", "Buah"];
});

function hasNutrisi(gizi) {
    if (!gizi) return false;
    const e = Number(
        gizi.energi ?? gizi.energy ?? gizi.energi_pk ?? gizi.energi_pb ?? 0,
    );
    const p = Number(
        gizi.protein ?? gizi.prot ?? gizi.prot_pk ?? gizi.prot_pb ?? 0,
    );
    const l = Number(gizi.lemak ?? gizi.lmk ?? gizi.lmk_pk ?? gizi.lmk_pb ?? 0);
    const k = Number(
        gizi.karbohidrat ?? gizi.karbo ?? gizi.karbo_pk ?? gizi.karbo_pb ?? 0,
    );
    const s = Number(gizi.serat ?? gizi.serat_pk ?? gizi.serat_pb ?? 0);
    return (
        Math.abs(e) > 0.001 ||
        Math.abs(p) > 0.001 ||
        Math.abs(l) > 0.001 ||
        Math.abs(k) > 0.001 ||
        Math.abs(s) > 0.001
    );
}

function matchAllergyKey(objKey, targetKey) {
    if (!objKey || !targetKey) return false;
    const cleanObj = String(objKey)
        .toLowerCase()
        .replace(/[^a-z0-9]/g, "");
    const cleanTarget = String(targetKey)
        .toLowerCase()
        .replace(/[^a-z0-9]/g, "");
    return (
        cleanObj === cleanTarget ||
        cleanObj.includes(cleanTarget) ||
        cleanTarget.includes(cleanObj)
    );
}

const akgPK = computed(() => {
    return (
        props.workOrder?.akg_pk || {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        }
    );
});

const akgPB = computed(() => {
    return (
        props.workOrder?.akg_pb || {
            energi: 0,
            protein: 0,
            lemak: 0,
            karbohidrat: 0,
            serat: 0,
        }
    );
});

const hasNormalPK = computed(() => hasNutrisi(akgPK.value));
const hasNormalPB = computed(() => hasNutrisi(akgPB.value));

function extractCleanJenis(val) {
    if (!val) return "";
    if (typeof val === "object" && val !== null) {
        return String(val.value || val.label || "").trim();
    }
    const str = String(val).trim();
    if (str.startsWith("{") && str.endsWith("}")) {
        try {
            const parsed = JSON.parse(str);
            return String(parsed.value || parsed.label || "").trim();
        } catch {
            return str;
        }
    }
    return str;
}

// Varian Khusus Alergi
const varianAlergiList = computed(() => {
    const wo = props.workOrder;
    if (!wo) return [];
    const subAlergi = wo.sub_menu_alergi || {};
    const items = wo.items || [];

    const alergiMap = {};

    if (Array.isArray(subAlergi)) {
        subAlergi.forEach((al) => {
            if (!al) return;
            const j = extractCleanJenis(al.jenis_alergi);
            if (!j) return;
            if (!alergiMap[j]) alergiMap[j] = { jenis: j, pengganti: {} };
            const key = al.sub_menu_key || "sub_menu_2";
            const penggantiStr =
                typeof al.menu_pengganti === "object" &&
                al.menu_pengganti !== null
                    ? al.menu_pengganti?.nama ||
                      al.menu_pengganti?.nama_menu ||
                      ""
                    : String(
                          al.menu_pengganti || al.nama || al.nama_menu || "-",
                      );
            alergiMap[j].pengganti[key] = penggantiStr || "-";
        });
    } else if (typeof subAlergi === "object") {
        Object.entries(subAlergi).forEach(([key, list]) => {
            if (Array.isArray(list)) {
                list.forEach((al) => {
                    if (!al) return;
                    const j = extractCleanJenis(al.jenis_alergi);
                    if (!j) return;
                    if (!alergiMap[j])
                        alergiMap[j] = { jenis: j, pengganti: {} };
                    const penggantiStr =
                        typeof al.menu_pengganti === "object" &&
                        al.menu_pengganti !== null
                            ? al.menu_pengganti?.nama ||
                              al.menu_pengganti?.nama_menu ||
                              ""
                            : String(
                                  al.menu_pengganti ||
                                      al.nama ||
                                      al.nama_menu ||
                                      "-",
                              );
                    alergiMap[j].pengganti[key] = penggantiStr || "-";
                });
            }
        });
    }

    items.forEach((it) => {
        if (it.tipe_porsi === "alergi") {
            const j = extractCleanJenis(it.jenis_alergi);
            if (j) {
                if (!alergiMap[j]) alergiMap[j] = { jenis: j, pengganti: {} };
                const key = it.sub_menu_key || "sub_menu_2";
                if (it.nama_sub_menu && !alergiMap[j].pengganti[key]) {
                    alergiMap[j].pengganti[key] = it.nama_sub_menu;
                }
            }
        }
    });

    if (wo.akg_alergi && typeof wo.akg_alergi === "object") {
        Object.keys(wo.akg_alergi).forEach((k) => {
            const j = extractCleanJenis(k);
            if (j && !alergiMap[j]) {
                alergiMap[j] = { jenis: j, pengganti: {} };
            }
        });
    }

    return Object.values(alergiMap)
        .map((al) => {
            const baseItems = subMenuNormalList.value;
            const menuList = baseItems
                .map((baseName, idx) => {
                    const subKey = `sub_menu_${idx + 1}`;
                    return al.pengganti[subKey] || baseName || "";
                })
                .filter(Boolean);

            let tempPK = null;
            let tempPB = null;
            let foundSpecific = false;

            if (wo.akg_alergi && typeof wo.akg_alergi === "object") {
                let specificAkg = wo.akg_alergi[al.jenis];
                if (!specificAkg) {
                    const found = Object.entries(wo.akg_alergi).find(([k]) =>
                        matchAllergyKey(k, al.jenis),
                    );
                    if (found) specificAkg = found[1];
                }

                if (specificAkg && typeof specificAkg === "object") {
                    foundSpecific = true;
                    const rawPK =
                        specificAkg.akg_pk ??
                        (specificAkg.energi_pk !== undefined
                            ? specificAkg
                            : null);
                    const rawPB =
                        specificAkg.akg_pb ??
                        (specificAkg.energi_pb !== undefined
                            ? specificAkg
                            : null);

                    if (rawPK && typeof rawPK === "object") {
                        tempPK = {
                            energi: Number(
                                rawPK.energi ??
                                    rawPK.energy ??
                                    rawPK.energi_pk ??
                                    0,
                            ),
                            protein: Number(
                                rawPK.protein ??
                                    rawPK.prot ??
                                    rawPK.prot_pk ??
                                    0,
                            ),
                            lemak: Number(
                                rawPK.lemak ?? rawPK.lmk ?? rawPK.lmk_pk ?? 0,
                            ),
                            karbohidrat: Number(
                                rawPK.karbohidrat ??
                                    rawPK.karbo ??
                                    rawPK.karbo_pk ??
                                    0,
                            ),
                            serat: Number(rawPK.serat ?? rawPK.serat_pk ?? 0),
                        };
                    }

                    if (rawPB && typeof rawPB === "object") {
                        tempPB = {
                            energi: Number(
                                rawPB.energi ??
                                    rawPB.energy ??
                                    rawPB.energi_pb ??
                                    0,
                            ),
                            protein: Number(
                                rawPB.protein ??
                                    rawPB.prot ??
                                    rawPB.prot_pb ??
                                    0,
                            ),
                            lemak: Number(
                                rawPB.lemak ?? rawPB.lmk ?? rawPB.lmk_pb ?? 0,
                            ),
                            karbohidrat: Number(
                                rawPB.karbohidrat ??
                                    rawPB.karbo ??
                                    rawPB.karbo_pb ??
                                    0,
                            ),
                            serat: Number(rawPB.serat ?? rawPB.serat_pb ?? 0),
                        };
                    }
                }
            }

            // Jika tidak ada data spesifik akg_alergi sama sekali untuk alergen ini,
            // gunakan nilai normal HANYA jika porsi normal tersebut memang memiliki nilai nutrisi (> 0)
            if (!foundSpecific) {
                if (hasNormalPK.value) {
                    tempPK = { ...akgPK.value };
                }
                if (hasNormalPB.value) {
                    tempPB = { ...akgPB.value };
                }
            }

            // Porsi hanya dianggap ada jika bernilai > 0 (jika nilainya 0 semua, maka hasPK/hasPB = false)
            const hasPK = hasNutrisi(tempPK);
            const hasPB = hasNutrisi(tempPB);

            return {
                jenis: al.jenis,
                menuList:
                    menuList.length > 0 ? menuList : subMenuNormalList.value,
                akgPK: tempPK || {
                    energi: 0,
                    protein: 0,
                    lemak: 0,
                    karbohidrat: 0,
                    serat: 0,
                },
                akgPB: tempPB || {
                    energi: 0,
                    protein: 0,
                    lemak: 0,
                    karbohidrat: 0,
                    serat: 0,
                },
                hasPK,
                hasPB,
            };
        })
        .filter((al) => al.hasPK || al.hasPB);
});

// Generator Narasi Teks Otomatis Persis Format yang Diminta
const defaultNarasiText = computed(() => {
    const tglIndo = formatTanggalIndo(
        props.selectedDate || props.workOrder?.tanggal_distribusi,
    );

    let lines = [];
    lines.push(
        `*PENYALURAN MBG ${namaSppgUpper.value} ${namaYayasanUpper.value}*`,
    );
    lines.push(``);
    lines.push(``);
    lines.push(
        `Mohon ijin melaporkan kegiatan *${namaSppgDb.value}* *${namaYayasanDb.value}* pada hari *${tglIndo}* sebagai berikut:`,
    );
    lines.push(``);
    lines.push(`*${namaYayasanUpper.value}*`);
    lines.push(`- Nama SPPG: ${namaSppgDb.value}`);
    lines.push(`- ID SPPG: ${idSppg.value}`);
    lines.push(`- Alamat SPPG: ${alamatSppg.value}`);
    lines.push(`- Nama Kepala SPPG: ${namaKepalaSppg.value}`);
    lines.push(``);

    // 1. Waktu Kegiatan Operasional
    lines.push(`*1.  Waktu Kegiatan Operasional:*`);
    jadwalOperasionalList.value.forEach((j) => {
        lines.push(`- Pukul ${j.mulai} s.d ${j.selesai} ${j.nama}`);
    });
    lines.push(``);

    // 2. Rincian Pembagian MBG (3B: Bumil, Busui, Balita)
    lines.push(
        `*2.  Adapun rincian pembagian MBG sebanyak ${totalPorsiSekolah.value} porsi untuk ${sekolahList.value.length} Sekolah dan ${totalPorsiPosyandu.value} porsi untuk ${posyanduList.value.length} Posyandu 3B (Bumil, Busui, Balita) dengan total ${totalPorsiKeseluruhan.value} Penerima Manfaat dan rincian sebagai berikut:*`,
    );
    lines.push(``);

    lines.push(`*Sekolah:*`);
    if (sekolahList.value.length > 0) {
        sekolahList.value.forEach((s) => {
            lines.push(
                `- ${s.nama} (Siswa: ${s.siswa} + Guru: ${s.guru} + Tendik: ${s.tendik} = ${s.totalPorsi} Porsi)`,
            );
        });
    } else {
        lines.push(`- Tidak ada penyaluran ke sekolah pada hari ini`);
    }
    lines.push(``);

    lines.push(`*Posyandu 3B (Bumil, Busui, Balita):*`);
    if (posyanduList.value.length > 0) {
        posyanduList.value.forEach((p) => {
            lines.push(
                `- ${p.nama} (Bumil: ${p.bumil} + Busui: ${p.busui} + Balita: ${p.balita} = ${p.totalPorsi} Porsi)`,
            );
        });
    } else {
        lines.push(`- Tidak ada penyaluran ke posyandu pada hari ini`);
    }
    lines.push(``);

    // 3. Menu MBG Sekolah dan 3B (Bumil, Busui, Balita)
    if (
        hasNormalPK.value ||
        hasNormalPB.value ||
        varianAlergiList.value.length > 0
    ) {
        lines.push(`*3.  Menu MBG Sekolah dan 3B (Bumil, Busui, Balita)*`);
        lines.push(``);

        if (hasNormalPK.value || hasNormalPB.value) {
            lines.push(`*Porsi Menu Normal*`);

            if (hasNormalPK.value) {
                lines.push(`*Menu Normal Porsi Kecil:*`);
                subMenuNormalList.value.forEach((sm) => {
                    lines.push(`- ${sm}`);
                });
                lines.push(`*Kandungan Gizi Menu Normal Porsi Kecil:*`);
                lines.push(`- Energi: ${akgPK.value.energi || 0} Kkal`);
                lines.push(`- Protein: ${akgPK.value.protein || 0} gr`);
                lines.push(`- Lemak: ${akgPK.value.lemak || 0} gr`);
                lines.push(`- Karbohidrat: ${akgPK.value.karbohidrat || 0} gr`);
                lines.push(`- Serat: ${akgPK.value.serat || 0} gr`);
            }

            if (hasNormalPB.value) {
                if (hasNormalPK.value) lines.push(``);
                lines.push(`*Menu Normal Porsi Besar:*`);
                subMenuNormalList.value.forEach((sm) => {
                    lines.push(`- ${sm}`);
                });
                lines.push(`*Kandungan Gizi Menu Normal Porsi Besar:*`);
                lines.push(`- Energi: ${akgPB.value.energi || 0} Kkal`);
                lines.push(`- Protein: ${akgPB.value.protein || 0} gr`);
                lines.push(`- Lemak: ${akgPB.value.lemak || 0} gr`);
                lines.push(`- Karbohidrat: ${akgPB.value.karbohidrat || 0} gr`);
                lines.push(`- Serat: ${akgPB.value.serat || 0} gr`);
            }
        }

        // Porsi Menu Alergi Jika Ada
        if (varianAlergiList.value.length > 0) {
            varianAlergiList.value.forEach((al) => {
                lines.push(``);
                lines.push(`*Porsi Menu Alergi ${al.jenis}*`);

                if (al.hasPK) {
                    lines.push(`*Menu Alergi ${al.jenis} Porsi Kecil:*`);
                    al.menuList.forEach((sm) => {
                        lines.push(`- ${sm}`);
                    });
                    lines.push(
                        `*Kandungan Gizi Menu Alergi ${al.jenis} Porsi Kecil:*`,
                    );
                    lines.push(`- Energi: ${al.akgPK.energi || 0} Kkal`);
                    lines.push(`- Protein: ${al.akgPK.protein || 0} gr`);
                    lines.push(`- Lemak: ${al.akgPK.lemak || 0} gr`);
                    lines.push(
                        `- Karbohidrat: ${al.akgPK.karbohidrat || 0} gr`,
                    );
                    lines.push(`- Serat: ${al.akgPK.serat || 0} gr`);
                }

                if (al.hasPB) {
                    if (al.hasPK) lines.push(``);
                    lines.push(`*Menu Alergi ${al.jenis} Porsi Besar:*`);
                    al.menuList.forEach((sm) => {
                        lines.push(`- ${sm}`);
                    });
                    lines.push(
                        `*Kandungan Gizi Menu Alergi ${al.jenis} Porsi Besar:*`,
                    );
                    lines.push(`- Energi: ${al.akgPB.energi || 0} Kkal`);
                    lines.push(`- Protein: ${al.akgPB.protein || 0} gr`);
                    lines.push(`- Lemak: ${al.akgPB.lemak || 0} gr`);
                    lines.push(
                        `- Karbohidrat: ${al.akgPB.karbohidrat || 0} gr`,
                    );
                    lines.push(`- Serat: ${al.akgPB.serat || 0} gr`);
                }
            });
        }
    }

    lines.push(``);
    lines.push(`*4. Kejadian Menonjol: Nihil*`);
    lines.push(``);
    lines.push(`*Demikian UMP*`);

    return lines.join("\n");
});

// Mode Edit Teks Manual
const isEditing = ref(false);
const customNarasiText = ref("");

const activeNarasiText = computed(() => {
    if (isEditing.value || customNarasiText.value) {
        return customNarasiText.value;
    }
    return defaultNarasiText.value;
});

function handleStartEdit() {
    customNarasiText.value = activeNarasiText.value;
    isEditing.value = true;
}

function handleResetEdit() {
    customNarasiText.value = "";
    isEditing.value = false;
}

// Fitur Salin ke Clipboard
const isCopied = ref(false);
let copyTimeout = null;

async function copyToClipboard() {
    try {
        await navigator.clipboard.writeText(activeNarasiText.value);
        isCopied.value = true;
        if (copyTimeout) clearTimeout(copyTimeout);
        copyTimeout = setTimeout(() => {
            isCopied.value = false;
        }, 2500);
    } catch {
        const textArea = document.createElement("textarea");
        textArea.value = activeNarasiText.value;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        isCopied.value = true;
        if (copyTimeout) clearTimeout(copyTimeout);
        copyTimeout = setTimeout(() => {
            isCopied.value = false;
        }, 2500);
    }
}

// Kirim ke WhatsApp
function shareToWhatsApp() {
    const textEncoded = encodeURIComponent(activeNarasiText.value);
    const waUrl = `https://api.whatsapp.com/send?text=${textEncoded}`;
    window.open(waUrl, "_blank");
}

// Unduh Teks Berkas .txt
function downloadTxtFile() {
    const filename = `Laporan_Harian_MBG_${props.selectedDate || "hari_ini"}.txt`;
    const element = document.createElement("a");
    const file = new Blob([activeNarasiText.value], { type: "text/plain" });
    element.href = URL.createObjectURL(file);
    element.download = filename;
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

// =========================================================================
// FILTER WAKTU & DISTRIBUSI WO (REUSABLE PERIODDATEFILTERBAR)
// =========================================================================
const page = usePage();
const allPeriodes = computed(() => page?.props?.periodes || []);

function findPeriodeForDate(dateStr) {
    if (!dateStr || !allPeriodes.value || allPeriodes.value.length === 0) return null;
    return (
        allPeriodes.value.find((p) => {
            const s = String(p.tanggal_mulai || "").substring(0, 10);
            const e = String(p.tanggal_selesai || "").substring(0, 10);
            return dateStr >= s && dateStr <= e;
        }) || null
    );
}

// Inisialisasi awal ID periode: cari berdasarkan selectedDate, jika tidak ada cari dari WO terbaru
const initialPeriode =
    findPeriodeForDate(props.selectedDate) ||
    (props.calendarWorkOrders?.[0]?.tanggal
        ? findPeriodeForDate(props.calendarWorkOrders[0].tanggal)
        : null);

const filterStartDate = ref(
    props.selectedDate ||
        (initialPeriode?.tanggal_mulai
            ? String(initialPeriode.tanggal_mulai).substring(0, 10)
            : props.todayDate) ||
        "",
);
const filterEndDate = ref(
    props.selectedDate ||
        (initialPeriode?.tanggal_selesai
            ? String(initialPeriode.tanggal_selesai).substring(0, 10)
            : props.todayDate) ||
        "",
);
const filterMode = ref(
    props.selectedDate === props.todayDate ? "hari_ini" : "periode",
);
const filterPeriodeId = ref(initialPeriode ? String(initialPeriode.id) : "");
const filterIsAllTime = ref(false);

// Sinkronisasi jika props.selectedDate berubah dari luar/navigasi
watch(
    () => props.selectedDate,
    (newDate) => {
        if (!newDate) return;
        if (filterMode.value === "hari_ini" && newDate !== props.todayDate) {
            filterMode.value = "periode";
        }
        const p = findPeriodeForDate(newDate);
        if (p) {
            filterPeriodeId.value = String(p.id);
            if (filterMode.value === "periode") {
                filterStartDate.value = String(p.tanggal_mulai).substring(0, 10);
                filterEndDate.value = String(p.tanggal_selesai).substring(0, 10);
            }
        }
    },
    { immediate: true },
);

// Map Work Order per tanggal untuk lookup cepat
const woMapByDate = computed(() => {
    const map = {};
    (props.calendarWorkOrders || []).forEach((cWo) => {
        if (cWo && cWo.tanggal) {
            map[cWo.tanggal] = cWo;
        }
    });
    return map;
});

// Daftar Work Order yang aktif berdasarkan rentang waktu yang dipilih
const filteredCalendarWorkOrders = computed(() => {
    const allList = props.calendarWorkOrders || [];
    if (allList.length === 0) return [];
    if (filterIsAllTime.value) return allList;

    if (filterMode.value === "hari_ini") {
        return allList.filter((cWo) => cWo.tanggal === props.todayDate);
    }

    const start = filterStartDate.value;
    const end = filterEndDate.value;
    if (!start && !end) return allList;

    return allList.filter((cWo) => {
        if (!cWo.tanggal) return false;
        if (start && cWo.tanggal < start) return false;
        if (end && cWo.tanggal > end) return false;
        return true;
    });
});

// Nilai terpilih untuk dropdown select WO (mencegah dropdown kosong jika props.selectedDate tidak ada di filter)
const currentWoSelectValue = computed(() => {
    const list = filteredCalendarWorkOrders.value;
    if (!list || list.length === 0) return "";
    const found = list.find((cWo) => cWo.tanggal === props.selectedDate);
    return found ? found.tanggal : list[0].tanggal;
});

// Handler saat filter periode / rentang tanggal berubah
function onDateFilterChange(filter) {
    if (!filter) return;

    const filterStart = filter.start || filter.startDate || "";
    const filterEnd = filter.end || filter.endDate || "";

    if (filter.mode === "hari_ini" && !filter.isAllTime) {
        if (props.selectedDate !== props.todayDate) {
            handleSelectDate(props.todayDate);
        }
        return;
    }

    if (filter.mode === "periode") {
        // Cari seluruh WO yang ada di periode terpilih
        const wosInPeriode = (props.calendarWorkOrders || []).filter((cWo) => {
            if (!cWo.tanggal) return false;
            if (filterStart && cWo.tanggal < filterStart) return false;
            if (filterEnd && cWo.tanggal > filterEnd) return false;
            return true;
        });

        if (wosInPeriode.length > 0) {
            // WO terbaru di periode ini adalah index 0 (karena calendarWorkOrders urut descending)
            const latestInPeriode = wosInPeriode[0];
            // Jika tanggal yang terpilih saat ini bukan salah satu WO di periode ini,
            // langsung arahkan ke WO terbaru di periode tersebut!
            const isCurrentDateInPeriodeWo = wosInPeriode.some(
                (w) => w.tanggal === props.selectedDate,
            );
            if (!isCurrentDateInPeriodeWo) {
                handleSelectDate(latestInPeriode.tanggal);
            }
            return;
        }

        // Jika di periode yang dipilih BELUM ada WO sama sekali:
        // Otomatis cari WO terbaru di database dan loncat ke periode tersebut
        const allWos = props.calendarWorkOrders || [];
        if (allWos.length > 0) {
            const overallLatestWo = allWos[0];
            const targetP = findPeriodeForDate(overallLatestWo.tanggal);
            if (targetP) {
                filterPeriodeId.value = String(targetP.id);
                filterStartDate.value = String(targetP.tanggal_mulai).substring(0, 10);
                filterEndDate.value = String(targetP.tanggal_selesai).substring(0, 10);
            }
            handleSelectDate(overallLatestWo.tanggal);
            return;
        }

        // Fallback jika memang tidak ada data WO sama sekali di sistem
        if (filterStart && props.selectedDate !== filterStart) {
            handleSelectDate(filterStart);
        }
        return;
    }

    // Untuk mode Rentang / All Time
    const wosInRange = (props.calendarWorkOrders || []).filter((cWo) => {
        if (!cWo.tanggal) return false;
        if (!filter.isAllTime && filterStart && cWo.tanggal < filterStart) return false;
        if (!filter.isAllTime && filterEnd && cWo.tanggal > filterEnd) return false;
        return true;
    });

    const isCurrentInWoList = wosInRange.some(
        (w) => w.tanggal === props.selectedDate,
    );
    if (!isCurrentInWoList && wosInRange.length > 0) {
        handleSelectDate(wosInRange[0].tanggal);
    } else if (!isCurrentInWoList && filterStart) {
        handleSelectDate(filterStart);
    }
}

// Navigasi Pilihan Tanggal
function handleSelectDate(date) {
    if (!date) return;
    router.get(
        route("laporan.harian-lintas-sektor"),
        { tanggal: date },
        { preserveState: true, preserveScroll: true },
    );
}

// Tab Mode Tampilan: Narasi vs Visual Cards
const activeTab = ref("narasi");
</script>

<template>
    <AppLayout title="Laporan Harian Lintas Sektor">
        <Head title="Laporan Harian Lintas Sektor" />

        <div class="space-y-6 pb-12 max-w-7xl mx-auto">
            <!-- HERO HEADER BANNER (SELARAS DENGAN RANCANG MENU MBG) -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 p-5 sm:p-6 text-white shadow-sm border border-slate-800"
            >
                <div
                    class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"
                ></div>
                <div
                    class="absolute right-32 bottom-0 h-48 w-48 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"
                ></div>

                <div
                    class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6"
                >
                    <div class="space-y-2 max-w-2xl">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 backdrop-blur-xs"
                            >
                                <FileText class="w-3.5 h-3.5" />
                                <span>Laporan Resmi MBG</span>
                            </span>
                            <span
                                v-if="workOrder"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30"
                            >
                                <Utensils class="w-3.5 h-3.5" />
                                <span>{{ workOrder.nama_menu }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-400/30"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>Belum Ada WO di Tanggal Ini</span>
                            </span>
                        </div>
                        <h1
                            class="text-xl sm:text-2xl font-black tracking-tight text-white leading-tight"
                        >
                            Laporan Harian Lintas Sektor
                        </h1>
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed"
                        >
                            Format narasi resmi penyaluran Program Makan Bergizi
                            Gratis (MBG) untuk pelaporan instan ke Forkopimda,
                            Satgas, Pemda, dan Stakeholder Lintas Sektor.
                        </p>
                    </div>

                    <!-- Tombol Cepat Salin di Header -->
                    <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                        <Button
                            type="button"
                            @click="copyToClipboard"
                            :class="[
                                'h-10 px-4 rounded-xl font-bold shadow-xs transition-all duration-200 gap-2 cursor-pointer text-xs sm:text-sm',
                                isCopied
                                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                    : 'bg-primary hover:bg-primary/90 text-white shadow-primary/20',
                            ]"
                        >
                            <Check
                                v-if="isCopied"
                                class="w-4 h-4 animate-in zoom-in-50 duration-200"
                            />
                            <Copy v-else class="w-4 h-4" />
                            <span>{{
                                isCopied
                                    ? "Tersalin ke Clipboard!"
                                    : "Salin Teks"
                            }}</span>
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            @click="shareToWhatsApp"
                            class="h-10 px-3.5 rounded-xl font-bold bg-white/10 hover:bg-white/15 text-white border-white/20 cursor-pointer gap-2 backdrop-blur-xs text-xs sm:text-sm"
                            title="Bagikan Teks Langsung ke WhatsApp"
                        >
                            <Send class="w-3.5 h-3.5 text-emerald-400" />
                            <span class="hidden sm:inline">WhatsApp</span>
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            @click="downloadTxtFile"
                            class="h-10 px-3.5 rounded-xl font-bold bg-white/10 hover:bg-white/15 text-white border-white/20 cursor-pointer gap-2 backdrop-blur-xs text-xs sm:text-sm"
                            title="Unduh Berkas .txt"
                        >
                            <Download class="w-3.5 h-3.5 text-sky-400" />
                            <span class="hidden sm:inline">Unduh TXT</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- FILTER WAKTU & DISTRIBUSI WO: REUSABLE PERIODDATEFILTERBAR -->
            <PeriodDateFilterBar
                v-model:startDate="filterStartDate"
                v-model:endDate="filterEndDate"
                v-model:mode="filterMode"
                v-model:periodeId="filterPeriodeId"
                v-model:isAllTime="filterIsAllTime"
                :showArchive="false"
                :autoInit="false"
                @change="onDateFilterChange"
            >
                <template #right>
                    <!-- Dropdown Pilihan WO yang Ada di Rentang/Periode Terpilih -->
                    <div
                        v-if="filteredCalendarWorkOrders.length > 0"
                        class="flex items-center gap-2"
                    >
                        <label
                            for="laporanQuickSelectWo"
                            class="text-xs font-bold text-slate-600 whitespace-nowrap hidden lg:inline"
                        >
                            Daftar WO:
                        </label>
                        <select
                            id="laporanQuickSelectWo"
                            :value="currentWoSelectValue"
                            @change="(e) => handleSelectDate(e.target.value)"
                            class="h-9 sm:h-10 px-3 text-xs font-bold rounded-xl border border-emerald-300 hover:border-emerald-500 text-slate-800 bg-white shadow-2xs max-w-[240px] sm:max-w-[340px] truncate outline-none cursor-pointer focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option
                                v-for="cWo in filteredCalendarWorkOrders"
                                :key="cWo.id"
                                :value="cWo.tanggal"
                            >
                                {{ formatTanggalIndo(cWo.tanggal) }} — {{ cWo.nama_menu }} ({{
                                    cWo.total_pm
                                }} Porsi)
                            </option>
                        </select>
                    </div>
                    <div
                        v-else
                        class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-500 text-xs font-medium border border-slate-200 whitespace-nowrap"
                    >
                        Tidak ada WO di rentang ini
                    </div>
                </template>
            </PeriodDateFilterBar>

            <!-- TABS PILIHAN TAMPILAN: TEKS NARASI VS RINGKASAN VISUAL -->
            <div
                class="flex items-center justify-between border-b border-slate-200 pb-2 flex-wrap gap-3"
            >
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="activeTab = 'narasi'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-black transition-all cursor-pointer flex items-center gap-2',
                            activeTab === 'narasi'
                                ? 'bg-primary text-white shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                        ]"
                    >
                        <FileText class="w-4 h-4" />
                        <span>Teks Redaksi</span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'visual'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-black transition-all cursor-pointer flex items-center gap-2',
                            activeTab === 'visual'
                                ? 'bg-primary text-white shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                        ]"
                    >
                        <Eye class="w-4 h-4" />
                        <span>Pratinjau Visual</span>
                    </button>
                </div>

                <!-- Tombol Edit / Reset Teks saat di tab Narasi -->
                <div
                    v-if="activeTab === 'narasi'"
                    class="flex items-center gap-2"
                >
                    <button
                        v-if="!isEditing"
                        type="button"
                        @click="handleStartEdit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        <Edit3 class="w-3.5 h-3.5" />
                        <span>Sesuaikan Redaksi</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="handleResetEdit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Kembalikan Narasi Asli</span>
                    </button>
                </div>
            </div>

            <!-- ALERT PERINGATAN JIKA TIDAK ADA WORK ORDER DI TANGGAL TERPILIH -->
            <div
                v-if="!workOrder"
                class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3 text-xs"
            >
                <AlertCircle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div class="space-y-1">
                    <p class="font-bold">
                        Belum ada data Work Order (Rancangan Menu) pada tanggal
                        {{ formatTanggalIndo(selectedDate) }}.
                    </p>
                    <p class="text-amber-800 leading-relaxed">
                        Teks narasi di bawah ini menggunakan template standar
                        SPPG. Anda dapat memilih tanggal lain yang memiliki Work
                        Order melalui kalender di atas atau menyusun rancangan
                        menu terlebih dahulu melalui menu
                        <strong>Gizi &gt; Rancang Menu</strong>.
                    </p>
                </div>
            </div>

            <!-- VIEW 1: TEKS NARASI SIAP SALIN (UTAMA) -->
            <div v-if="activeTab === 'narasi'" class="space-y-4">
                <div
                    class="relative bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden"
                >
                    <!-- Header Action Toolbar -->
                    <div
                        class="bg-slate-50/80 px-4 sm:px-6 py-3 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"
                            ></span>
                            <span
                                class="text-xs font-black text-slate-800 uppercase tracking-wider"
                            >
                                Narasi Siap di Salin
                            </span>
                            <span
                                v-if="isEditing"
                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300"
                            >
                                Mode Kustom Aktif
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                @click="copyToClipboard"
                                :class="[
                                    'h-8 px-3.5 text-xs font-black gap-1.5 rounded-lg cursor-pointer transition-colors',
                                    isCopied
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-primary hover:bg-primary/90 text-white',
                                ]"
                            >
                                <Check v-if="isCopied" class="w-3.5 h-3.5" />
                                <Copy v-else class="w-3.5 h-3.5" />
                                <span>{{
                                    isCopied ? "Tersalin!" : "Salin Teks"
                                }}</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Area Teks Narasi -->
                    <div class="p-4 sm:p-6 lg:p-7">
                        <textarea
                            v-if="isEditing"
                            v-model="customNarasiText"
                            rows="28"
                            class="w-full font-mono text-xs sm:text-sm text-slate-800 p-4 rounded-2xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-primary/20 focus:border-primary leading-relaxed bg-slate-50/50"
                            placeholder="Sesuaikan narasi di sini..."
                        ></textarea>
                        <div
                            v-else
                            class="relative bg-slate-50/80 text-slate-800 p-5 sm:p-7 rounded-2xl font-mono text-xs sm:text-sm leading-relaxed overflow-x-auto whitespace-pre-wrap selection:bg-primary/20 selection:text-slate-900 shadow-2xs border border-slate-200"
                        >
                            {{ activeNarasiText }}
                        </div>
                    </div>

                    <!-- Footer Action Bar -->
                    <div
                        class="bg-slate-50 px-4 sm:px-6 py-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 flex-wrap gap-3"
                    >
                        <span>
                            Karakter:
                            <strong>{{ activeNarasiText.length }}</strong> •
                            Format kompatibel dengan WhatsApp Web & Desktop.
                        </span>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="shareToWhatsApp"
                                class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1 cursor-pointer"
                            >
                                <Send class="w-3.5 h-3.5" />
                                <span>Kirim via WhatsApp</span>
                            </button>
                            <span class="text-slate-300">•</span>
                            <button
                                type="button"
                                @click="downloadTxtFile"
                                class="text-xs font-bold text-sky-700 hover:text-sky-800 flex items-center gap-1 cursor-pointer"
                            >
                                <Download class="w-3.5 h-3.5" />
                                <span>Unduh File .txt</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: PRATINJAU VISUAL & KARTU REKAPITULASI -->
            <div v-else class="space-y-6">
                <!-- GRID 1: IDENTITAS SPPG & WAKTU KEGIATAN OPERASIONAL -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Kartu Identitas SPPG -->
                    <div
                        class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-3.5"
                    >
                        <div
                            class="flex items-center gap-2 border-b border-slate-100 pb-3"
                        >
                            <div
                                class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold"
                            >
                                <Info class="w-4 h-4" />
                            </div>
                            <h3
                                class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider"
                            >
                                Identitas Unit SPPG & Yayasan
                            </h3>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div
                                class="flex justify-between py-1 border-b border-slate-50"
                            >
                                <span class="text-slate-500 font-medium"
                                    >Yayasan:</span
                                >
                                <span
                                    class="font-bold text-slate-900 text-right"
                                    >{{ namaYayasanDb }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between py-1 border-b border-slate-50"
                            >
                                <span class="text-slate-500 font-medium"
                                    >Nama SPPG:</span
                                >
                                <span
                                    class="font-bold text-slate-900 text-right"
                                    >{{ namaSppgDb }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between py-1 border-b border-slate-50"
                            >
                                <span class="text-slate-500 font-medium"
                                    >ID SPPG:</span
                                >
                                <span
                                    class="font-bold text-primary font-mono text-right"
                                    >{{ idSppg }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between py-1 border-b border-slate-50"
                            >
                                <span class="text-slate-500 font-medium"
                                    >Alamat:</span
                                >
                                <span
                                    class="font-bold text-slate-900 text-right max-w-xs"
                                    >{{ alamatSppg }}</span
                                >
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500 font-medium"
                                    >Kepala SPPG:</span
                                >
                                <span
                                    class="font-black text-slate-900 text-right"
                                    >{{ namaKepalaSppg }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Kartu 1: Waktu Kegiatan Operasional -->
                    <div
                        class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-3.5"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 pb-3"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold"
                                >
                                    <Clock class="w-4 h-4" />
                                </div>
                                <h3
                                    class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider"
                                >
                                    Waktu Kegiatan Operasional
                                </h3>
                            </div>
                        </div>
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"
                        >
                            <div
                                v-for="j in jadwalOperasionalList"
                                :key="j.nama"
                                class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between"
                            >
                                <span class="font-bold text-slate-800">{{
                                    j.nama
                                }}</span>
                                <span
                                    class="font-mono text-[11px] font-black text-primary px-2 py-0.5 bg-white rounded-md border border-slate-200"
                                >
                                    {{ j.mulai }} - {{ j.selesai }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- GRID 2: RINCIAN SEKOLAH & POSYANDU 3B -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- Sekolah -->
                    <div
                        class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-3.5"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 pb-3"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold"
                                >
                                    <School class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3
                                        class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider"
                                    >
                                        Sasaran Sekolah
                                    </h3>
                                    <span class="text-[11px] text-slate-500">
                                        {{ sekolahList.length }} Sekolah •
                                        {{ totalPorsiSekolah }} Porsi
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            <div
                                v-for="s in sekolahList"
                                :key="s.nama"
                                class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-xs"
                            >
                                <div
                                    class="font-bold text-slate-900 flex justify-between"
                                >
                                    <span>{{ s.nama }}</span>
                                    <span class="font-black text-primary"
                                        >{{ s.totalPorsi }} Porsi</span
                                    >
                                </div>
                                <div
                                    class="text-[11px] text-slate-500 flex items-center gap-2 flex-wrap"
                                >
                                    <span
                                        >Siswa:
                                        <strong class="text-slate-700">{{
                                            s.siswa
                                        }}</strong></span
                                    >
                                    <span>•</span>
                                    <span
                                        >Guru:
                                        <strong class="text-slate-700">{{
                                            s.guru
                                        }}</strong></span
                                    >
                                    <span>•</span>
                                    <span
                                        >Tendik:
                                        <strong class="text-slate-700">{{
                                            s.tendik
                                        }}</strong></span
                                    >
                                </div>
                            </div>
                            <div
                                v-if="sekolahList.length === 0"
                                class="text-center py-6 text-slate-400 text-xs"
                            >
                                Tidak ada data sekolah penerima
                            </div>
                        </div>
                    </div>

                    <!-- Posyandu 3B -->
                    <div
                        class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-3.5"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 pb-3"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold"
                                >
                                    <HeartHandshake class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3
                                        class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider"
                                    >
                                        Posyandu 3B (Bumil, Busui, Balita)
                                    </h3>
                                    <span class="text-[11px] text-slate-500">
                                        {{ posyanduList.length }} Posyandu •
                                        {{ totalPorsiPosyandu }} Porsi
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            <div
                                v-for="p in posyanduList"
                                :key="p.nama"
                                class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-xs"
                            >
                                <div
                                    class="font-bold text-slate-900 flex justify-between"
                                >
                                    <span>{{ p.nama }}</span>
                                    <span class="font-black text-rose-700"
                                        >{{ p.totalPorsi }} Porsi</span
                                    >
                                </div>
                                <div
                                    class="text-[11px] text-slate-500 flex items-center gap-2 flex-wrap"
                                >
                                    <span
                                        >Bumil:
                                        <strong class="text-slate-700">{{
                                            p.bumil
                                        }}</strong></span
                                    >
                                    <span>•</span>
                                    <span
                                        >Busui:
                                        <strong class="text-slate-700">{{
                                            p.busui
                                        }}</strong></span
                                    >
                                    <span>•</span>
                                    <span
                                        >Balita:
                                        <strong class="text-slate-700">{{
                                            p.balita
                                        }}</strong></span
                                    >
                                </div>
                            </div>
                            <div
                                v-if="posyanduList.length === 0"
                                class="text-center py-6 text-slate-400 text-xs"
                            >
                                Tidak ada data posyandu penerima
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MENU MBG & KANDUNGAN GIZI -->
                <div
                    class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs space-y-4"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold"
                            >
                                <Utensils class="w-4 h-4" />
                            </div>
                            <h3
                                class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider"
                            >
                                Menu MBG & Kandungan Gizi
                            </h3>
                        </div>
                    </div>

                    <div
                        v-if="hasNormalPK || hasNormalPB"
                        :class="[
                            'grid gap-4',
                            hasNormalPK && hasNormalPB
                                ? 'grid-cols-1 md:grid-cols-2'
                                : 'grid-cols-1',
                        ]"
                    >
                        <!-- Menu Normal PK -->
                        <div
                            v-if="hasNormalPK"
                            class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3"
                        >
                            <span
                                class="text-xs font-black text-slate-900 block"
                            >
                                Porsi Kecil (PK)
                            </span>
                            <div class="space-y-1 text-xs text-slate-700">
                                <div
                                    v-for="(sm, idx) in subMenuNormalList"
                                    :key="idx"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"
                                    ></span>
                                    <span>{{ sm }}</span>
                                </div>
                            </div>
                            <div
                                class="pt-2 border-t border-slate-200 text-[11px] text-slate-600 grid grid-cols-3 sm:grid-cols-5 gap-1.5 font-medium"
                            >
                                <span
                                    >Energi:
                                    <strong
                                        >{{ akgPK.energi || 0 }} Kkal</strong
                                    ></span
                                >
                                <span
                                    >Protein:
                                    <strong
                                        >{{ akgPK.protein || 0 }}g</strong
                                    ></span
                                >
                                <span
                                    >Lemak:
                                    <strong
                                        >{{ akgPK.lemak || 0 }}g</strong
                                    ></span
                                >
                                <span
                                    >Karbo:
                                    <strong
                                        >{{ akgPK.karbohidrat || 0 }}g</strong
                                    ></span
                                >
                                <span
                                    >Serat:
                                    <strong
                                        >{{ akgPK.serat || 0 }}g</strong
                                    ></span
                                >
                            </div>
                        </div>

                        <!-- Menu Normal PB -->
                        <div
                            v-if="hasNormalPB"
                            class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3"
                        >
                            <span
                                class="text-xs font-black text-slate-900 block"
                            >
                                Porsi Besar (PB)
                            </span>
                            <div class="space-y-1 text-xs text-slate-700">
                                <div
                                    v-for="(sm, idx) in subMenuNormalList"
                                    :key="idx"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"
                                    ></span>
                                    <span>{{ sm }}</span>
                                </div>
                            </div>
                            <div
                                class="pt-2 border-t border-slate-200 text-[11px] text-slate-600 grid grid-cols-3 sm:grid-cols-5 gap-1.5 font-medium"
                            >
                                <span
                                    >Energi:
                                    <strong
                                        >{{ akgPB.energi || 0 }} Kkal</strong
                                    ></span
                                >
                                <span
                                    >Protein:
                                    <strong
                                        >{{ akgPB.protein || 0 }}g</strong
                                    ></span
                                >
                                <span
                                    >Lemak:
                                    <strong
                                        >{{ akgPB.lemak || 0 }}g</strong
                                    ></span
                                >
                                <span
                                    >Karbo:
                                    <strong
                                        >{{ akgPB.karbohidrat || 0 }}g</strong
                                    ></span
                                >
                                <span
                                    >Serat:
                                    <strong
                                        >{{ akgPB.serat || 0 }}g</strong
                                    ></span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder jika seluruh nutrisi normal bernilai 0 dan tidak ada alergi -->
                    <div
                        v-if="
                            !hasNormalPK &&
                            !hasNormalPB &&
                            varianAlergiList.length === 0
                        "
                        class="text-center py-6 text-slate-400 text-xs"
                    >
                        Belum ada data porsi dan kandungan gizi yang tersedia
                        untuk tanggal ini.
                    </div>

                    <!-- Porsi Khusus Menu Alergi (Jika Ada) -->
                    <div
                        v-if="varianAlergiList.length > 0"
                        class="pt-4 border-t border-slate-200/80 space-y-3.5"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold"
                            >
                                <ShieldAlert class="w-3.5 h-3.5" />
                            </div>
                            <h4
                                class="text-xs font-black text-amber-950 uppercase tracking-wider"
                            >
                                Varian Khusus Menu Alergi ({{
                                    varianAlergiList.length
                                }}
                                Jenis)
                            </h4>
                        </div>

                        <div
                            v-for="al in varianAlergiList"
                            :key="al.jenis"
                            class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b border-amber-200/60 pb-2"
                            >
                                <span
                                    class="text-xs font-black text-amber-950 flex items-center gap-1.5"
                                >
                                    <ShieldAlert
                                        class="w-4 h-4 text-amber-600"
                                    />
                                    <span>Porsi Menu {{ al.jenis }}</span>
                                </span>
                                <span
                                    class="text-[10.5px] font-bold px-2 py-0.5 rounded-md bg-white border border-amber-200 text-amber-800"
                                >
                                    Menu Modifikasi Alergen
                                </span>
                            </div>

                            <div
                                :class="[
                                    'grid gap-4',
                                    al.hasPK && al.hasPB
                                        ? 'grid-cols-1 md:grid-cols-2'
                                        : 'grid-cols-1',
                                ]"
                            >
                                <!-- Menu Alergi PK -->
                                <div
                                    v-if="al.hasPK"
                                    class="p-3.5 rounded-xl bg-white border border-amber-200/90 shadow-2xs space-y-2.5"
                                >
                                    <span
                                        class="text-xs font-black text-slate-900 block"
                                    >
                                        Menu {{ al.jenis }} Porsi Kecil (PK)
                                    </span>
                                    <div
                                        class="space-y-1 text-xs text-slate-700"
                                    >
                                        <div
                                            v-for="(sm, idx) in al.menuList"
                                            :key="idx"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"
                                            ></span>
                                            <span>{{ sm }}</span>
                                        </div>
                                    </div>
                                    <div
                                        class="pt-2 border-t border-slate-100 text-[11px] text-slate-600 grid grid-cols-3 sm:grid-cols-5 gap-1 font-medium"
                                    >
                                        <span
                                            >Energi:
                                            <strong
                                                >{{
                                                    al.akgPK.energi || 0
                                                }}
                                                Kkal</strong
                                            ></span
                                        >
                                        <span
                                            >Protein:
                                            <strong
                                                >{{
                                                    al.akgPK.protein || 0
                                                }}g</strong
                                            ></span
                                        >
                                        <span
                                            >Lemak:
                                            <strong
                                                >{{
                                                    al.akgPK.lemak || 0
                                                }}g</strong
                                            ></span
                                        >
                                        <span
                                            >Karbo:
                                            <strong
                                                >{{
                                                    al.akgPK.karbohidrat || 0
                                                }}g</strong
                                            ></span
                                        >
                                        <span
                                            >Serat:
                                            <strong
                                                >{{
                                                    al.akgPK.serat || 0
                                                }}g</strong
                                            ></span
                                        >
                                    </div>
                                </div>

                                <!-- Menu Alergi PB -->
                                <div
                                    v-if="al.hasPB"
                                    class="p-3.5 rounded-xl bg-white border border-amber-200/90 shadow-2xs space-y-2.5"
                                >
                                    <span
                                        class="text-xs font-black text-slate-900 block"
                                    >
                                        Menu {{ al.jenis }} Porsi Besar (PB)
                                    </span>
                                    <div
                                        class="space-y-1 text-xs text-slate-700"
                                    >
                                        <div
                                            v-for="(sm, idx) in al.menuList"
                                            :key="idx"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full bg-amber-600 shrink-0"
                                            ></span>
                                            <span>{{ sm }}</span>
                                        </div>
                                    </div>
                                    <div
                                        class="pt-2 border-t border-slate-100 text-[11px] text-slate-600 grid grid-cols-3 sm:grid-cols-5 gap-1 font-medium"
                                    >
                                        <span
                                            >Energi:
                                            <strong
                                                >{{
                                                    al.akgPB.energi || 0
                                                }}
                                                Kkal</strong
                                            ></span
                                        >
                                        <span
                                            >Protein:
                                            <strong
                                                >{{
                                                    al.akgPB.protein || 0
                                                }}g</strong
                                            ></span
                                        >
                                        <span
                                            >Lemak:
                                            <strong
                                                >{{
                                                    al.akgPB.lemak || 0
                                                }}g</strong
                                            ></span
                                        >
                                        <span
                                            >Karbo:
                                            <strong
                                                >{{
                                                    al.akgPB.karbohidrat || 0
                                                }}g</strong
                                            ></span
                                        >
                                        <span
                                            >Serat:
                                            <strong
                                                >{{
                                                    al.akgPB.serat || 0
                                                }}g</strong
                                            ></span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
