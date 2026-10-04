<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, router } from "@inertiajs/vue3";
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
        props.kopConfig?.nama_instansi_2 ||
        "YAYASAN PESANTREN MIFTAHUL ULUM"
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
    { nama: "Pengolahan", mulai: "02.00", selesai: "10.00" },
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
    if (Array.isArray(k.rincian) && k.rincian.some((r) => Number(r.total || 0) > 0)) {
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
                    } else if (sub.includes("guru") || sub.includes("pendidik")) {
                        guru += tot;
                    } else {
                        siswa += tot;
                    }
                });
            }

            const totalPorsi = Number(
                k.total_penerima ?? siswa + guru + tendik,
            ) || 0;

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

            const totalPorsi = Number(
                k.total_penerima ?? bumil + busui + balita,
            ) || 0;

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

// Varian Khusus Alergi
const varianAlergiList = computed(() => {
    const wo = props.workOrder;
    if (!wo) return [];
    const subAlergi = wo.sub_menu_alergi || {};
    const items = wo.items || [];

    const alergiMap = {};

    if (Array.isArray(subAlergi)) {
        subAlergi.forEach((al) => {
            if (!al || !al.jenis_alergi) return;
            const j = al.jenis_alergi.trim();
            if (!alergiMap[j]) alergiMap[j] = { jenis: j, pengganti: {} };
            const key = al.sub_menu_key || "sub_menu_2";
            alergiMap[j].pengganti[key] =
                al.menu_pengganti || al.nama || al.nama_menu || "-";
        });
    } else if (typeof subAlergi === "object") {
        Object.entries(subAlergi).forEach(([key, list]) => {
            if (Array.isArray(list)) {
                list.forEach((al) => {
                    if (!al || !al.jenis_alergi) return;
                    const j = al.jenis_alergi.trim();
                    if (!alergiMap[j])
                        alergiMap[j] = { jenis: j, pengganti: {} };
                    alergiMap[j].pengganti[key] =
                        al.menu_pengganti || al.nama || al.nama_menu || "-";
                });
            }
        });
    }

    items.forEach((it) => {
        if (it.tipe_porsi === "alergi" && it.jenis_alergi) {
            const j = it.jenis_alergi.trim();
            if (!alergiMap[j]) alergiMap[j] = { jenis: j, pengganti: {} };
            const key = it.sub_menu_key || "sub_menu_2";
            if (it.nama_sub_menu && !alergiMap[j].pengganti[key]) {
                alergiMap[j].pengganti[key] = it.nama_sub_menu;
            }
        }
    });

    return Object.values(alergiMap).map((al) => {
        const menuList = [
            al.pengganti["sub_menu_1"] || wo.sub_menu_1 || "",
            al.pengganti["sub_menu_2"] || wo.sub_menu_2 || "",
            al.pengganti["sub_menu_3"] || wo.sub_menu_3 || "",
            al.pengganti["sub_menu_4"] || wo.sub_menu_4 || "",
            al.pengganti["sub_menu_5"] || wo.sub_menu_5 || "",
        ].filter(Boolean);

        return {
            jenis: al.jenis,
            menuList:
                menuList.length > 0 ? menuList : subMenuNormalList.value,
            akgPK: akgPK.value,
            akgPB: akgPB.value,
        };
    });
});

// Generator Narasi Teks Otomatis Persis Format yang Diminta
const defaultNarasiText = computed(() => {
    const tglIndo = formatTanggalIndo(
        props.selectedDate || props.workOrder?.tanggal_distribusi,
    );

    let lines = [];
    lines.push(`*PENYALURAN MBG ${namaSppgUpper.value} ${namaYayasanUpper.value}*`);
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
        `*2.  Adapun rincian pembagian MBG sebanyak ${totalPorsiSekolah.value} porsi untuk ${sekolahList.value.length} Sekolah dan ${totalPorsiPosyandu.value} untuk ${posyanduList.value.length} Posyandu 3B (Bumil, Busui, Balita) dengan total ${totalPorsiKeseluruhan.value} Penerima Manfaat dan rincian sebagai berikut:*`,
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
    lines.push(`*3.  Menu MBG Sekolah dan 3B (Bumil, Busui, Balita)*`);
    lines.push(``);
    lines.push(`*Porsi Menu Normal*`);
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

    // Porsi Menu Alergi Jika Ada
    if (varianAlergiList.value.length > 0) {
        varianAlergiList.value.forEach((al) => {
            lines.push(``);
            lines.push(`*Porsi Menu Alergi ${al.jenis}*`);
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
            lines.push(`- Karbohidrat: ${al.akgPK.karbohidrat || 0} gr`);
            lines.push(`- Serat: ${al.akgPK.serat || 0} gr`);

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
            lines.push(`- Karbohidrat: ${al.akgPB.karbohidrat || 0} gr`);
            lines.push(`- Serat: ${al.akgPB.serat || 0} gr`);
        });
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
// KALENDER PICKER INTERAKTIF SESUAI STYLE WORK ORDER
// =========================================================================
const showDatePickerPopover = ref(false);
const datePickerContainerRef = ref(null);

const NAMA_BULAN_PICKER = [
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

// Inisialisasi bulan & tahun picker dari selectedDate
function getInitialYearMonth() {
    if (props.selectedDate) {
        const parts = props.selectedDate.split("-").map(Number);
        if (parts[0] && parts[1]) {
            return { year: parts[0], month: parts[1] - 1 };
        }
    }
    const now = new Date();
    return { year: now.getFullYear(), month: now.getMonth() };
}

const initialYM = getInitialYearMonth();
const pickerCalendarYear = ref(initialYM.year);
const pickerCalendarMonth = ref(initialYM.month);

const pickerMonthLabel = computed(() => {
    return `${NAMA_BULAN_PICKER[pickerCalendarMonth.value]} ${pickerCalendarYear.value}`;
});

function syncPickerMonthWithSelected() {
    if (props.selectedDate) {
        const parts = props.selectedDate.split("-").map(Number);
        if (parts[0] && parts[1]) {
            pickerCalendarYear.value = parts[0];
            pickerCalendarMonth.value = parts[1] - 1;
        }
    }
}

function toggleDatePickerPopover() {
    showDatePickerPopover.value = !showDatePickerPopover.value;
    if (showDatePickerPopover.value) {
        syncPickerMonthWithSelected();
    }
}

function prevPickerMonth() {
    if (pickerCalendarMonth.value === 0) {
        pickerCalendarMonth.value = 11;
        pickerCalendarYear.value -= 1;
    } else {
        pickerCalendarMonth.value -= 1;
    }
}

function nextPickerMonth() {
    if (pickerCalendarMonth.value === 11) {
        pickerCalendarMonth.value = 0;
        pickerCalendarYear.value += 1;
    } else {
        pickerCalendarMonth.value += 1;
    }
}

// Map Work Order per tanggal untuk indikator kalender
const woMapByDate = computed(() => {
    const map = {};
    (props.calendarWorkOrders || []).forEach((cWo) => {
        if (cWo && cWo.tanggal) {
            map[cWo.tanggal] = cWo;
        }
    });
    return map;
});

const pickerCalendarDays = computed(() => {
    const year = pickerCalendarYear.value;
    const month = pickerCalendarMonth.value;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Monday index 0, Sunday index 6
    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const days = [];
    const today = props.todayDate;
    const selected = props.selectedDate;

    // Previous month padding
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const dayNum = prevMonthLastDay - i;
        const prevDate = new Date(year, month - 1, dayNum);
        const y = prevDate.getFullYear();
        const m = String(prevDate.getMonth() + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const woInfo = woMapByDate.value[dateStr] || null;

        days.push({
            dateStr,
            dayNumber: dayNum,
            isCurrentMonth: false,
            isToday: dateStr === today,
            isSelected: dateStr === selected,
            hasWo: !!woInfo,
            woInfo,
        });
    }

    // Current month days
    const totalDays = lastDay.getDate();
    for (let dayNum = 1; dayNum <= totalDays; dayNum++) {
        const y = year;
        const m = String(month + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const woInfo = woMapByDate.value[dateStr] || null;

        days.push({
            dateStr,
            dayNumber: dayNum,
            isCurrentMonth: true,
            isToday: dateStr === today,
            isSelected: dateStr === selected,
            hasWo: !!woInfo,
            woInfo,
        });
    }

    // Next month padding to complete grid
    const remaining = (7 - (days.length % 7)) % 7;
    for (let i = 1; i <= remaining; i++) {
        const nextDate = new Date(year, month + 1, i);
        const y = nextDate.getFullYear();
        const m = String(nextDate.getMonth() + 1).padStart(2, "0");
        const d = String(i).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const woInfo = woMapByDate.value[dateStr] || null;

        days.push({
            dateStr,
            dayNumber: i,
            isCurrentMonth: false,
            isToday: dateStr === today,
            isSelected: dateStr === selected,
            hasWo: !!woInfo,
            woInfo,
        });
    }

    return days;
});

function handleSelectPickerDate(day) {
    if (!day || !day.dateStr || !day.hasWo) return;
    showDatePickerPopover.value = false;
    handleSelectDate(day.dateStr);
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

// Click outside handler untuk menutup popover kalender
function handleClickOutside(event) {
    if (
        datePickerContainerRef.value &&
        !datePickerContainerRef.value.contains(event.target)
    ) {
        showDatePickerPopover.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});

// Tab Mode Tampilan: Narasi vs Visual Cards
const activeTab = ref("narasi");
</script>

<template>
    <AppLayout title="Laporan Harian Lintas Sektor">
        <Head title="Laporan Harian Lintas Sektor" />

        <div class="space-y-6 pb-12 max-w-7xl mx-auto">
            <!-- HERO HEADER BANNER -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-primary/95 to-slate-950 p-6 sm:p-8 text-white shadow-xl border border-slate-800"
            >
                <div
                    class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-primary/20 blur-3xl pointer-events-none"
                ></div>
                <div
                    class="absolute right-32 bottom-0 h-48 w-48 rounded-full bg-emerald-500/10 blur-2xl pointer-events-none"
                ></div>

                <div
                    class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6"
                >
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-white/10 text-emerald-300 border border-white/10 backdrop-blur-xs"
                            >
                                <FileText class="w-3.5 h-3.5" />
                                <span>Laporan Resmi MBG</span>
                            </span>
                            <span
                                v-if="workOrder"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-primary/30 text-white border border-primary/40"
                            >
                                <Utensils class="w-3.5 h-3.5" />
                                <span>{{ workOrder.nama_menu }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-500/30"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                <span>Belum Ada WO di Tanggal Ini</span>
                            </span>
                        </div>
                        <h1
                            class="text-2xl sm:text-3xl font-black tracking-tight text-white leading-tight"
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
                    <div class="flex items-center gap-3 shrink-0 flex-wrap">
                        <Button
                            type="button"
                            @click="copyToClipboard"
                            :class="[
                                'h-11 px-5 rounded-xl font-black shadow-lg transition-all duration-200 gap-2 cursor-pointer',
                                isCopied
                                    ? 'bg-emerald-500 hover:bg-emerald-600 text-white'
                                    : 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-emerald-900/30',
                            ]"
                        >
                            <Check
                                v-if="isCopied"
                                class="w-5 h-5 animate-in zoom-in-50 duration-200"
                            />
                            <Copy v-else class="w-5 h-5" />
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
                            class="h-11 px-4 rounded-xl font-bold bg-white/10 hover:bg-white/20 text-white border-white/20 cursor-pointer gap-2 backdrop-blur-xs"
                            title="Bagikan Teks Langsung ke WhatsApp"
                        >
                            <Send class="w-4 h-4 text-emerald-400" />
                            <span class="hidden sm:inline">WhatsApp</span>
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            @click="downloadTxtFile"
                            class="h-11 px-4 rounded-xl font-bold bg-white/10 hover:bg-white/20 text-white border-white/20 cursor-pointer gap-2 backdrop-blur-xs"
                            title="Unduh Berkas .txt"
                        >
                            <Download class="w-4 h-4 text-sky-400" />
                            <span class="hidden sm:inline">Unduh TXT</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- SELECTOR TANGGAL WORK ORDER DENGAN KALENDER CUSTOM STYLE WO -->
            <div
                class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Tombol Cepat: Hari Ini -->
                    <button
                        type="button"
                        @click="handleSelectDate(todayDate)"
                        :class="[
                            'px-3.5 py-2 rounded-xl text-xs font-black transition-all cursor-pointer border flex items-center gap-1.5',
                            selectedDate === todayDate
                                ? 'bg-primary text-white border-primary shadow-xs'
                                : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100',
                        ]"
                    >
                        <Calendar class="w-3.5 h-3.5" />
                        <span>Hari Ini</span>
                    </button>

                    <!-- CUSTOM INTERACTIVE DATEPICKER SESUAI STYLE WORK ORDER -->
                    <div ref="datePickerContainerRef" class="relative">
                        <!-- Trigger Button -->
                        <button
                            type="button"
                            @click.stop="toggleDatePickerPopover"
                            class="h-10 px-3.5 bg-white border border-slate-300 hover:border-primary rounded-xl text-xs font-bold text-slate-800 flex items-center gap-2.5 shadow-2xs transition-all cursor-pointer focus:outline-hidden focus:ring-2 focus:ring-primary/20"
                            title="Klik untuk membuka Kalender Distribusi MBG"
                        >
                            <div class="w-6 h-6 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                                <Calendar class="w-3.5 h-3.5" />
                            </div>
                            <span class="font-black text-slate-900 text-xs sm:text-[13px]">
                                {{ formatTanggalIndo(selectedDate) }}
                            </span>
                            <span
                                v-if="workOrder"
                                class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                WO: {{ workOrder.nama_menu }}
                            </span>
                            <span
                                v-else
                                class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-slate-100 text-slate-500"
                            >
                                Tidak Ada WO
                            </span>
                            <ChevronDown
                                class="w-4 h-4 text-slate-400 transition-transform duration-200 ml-1"
                                :class="showDatePickerPopover ? 'rotate-180 text-primary' : ''"
                            />
                        </button>

                        <!-- POPOVER PANEL KALENDER STYLE WORK ORDER -->
                        <div
                            v-if="showDatePickerPopover"
                            class="absolute z-50 top-full left-0 mt-2 w-[320px] sm:w-[350px] bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 space-y-3 animate-in fade-in zoom-in-95 duration-150"
                        >
                            <!-- Header Navigasi Bulan -->
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <button
                                    type="button"
                                    @click.stop="prevPickerMonth"
                                    class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-600 transition cursor-pointer"
                                    title="Bulan Sebelumnya"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <div class="text-xs font-black text-slate-900 tracking-wide flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5 text-primary" />
                                    <span>{{ pickerMonthLabel }}</span>
                                </div>
                                <button
                                    type="button"
                                    @click.stop="nextPickerMonth"
                                    class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-600 transition cursor-pointer"
                                    title="Bulan Berikutnya"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Header Nama Hari (Sen - Min) -->
                            <div class="grid grid-cols-7 gap-1 text-center text-[10.5px] font-bold text-slate-500 uppercase">
                                <span class="py-1">Sen</span>
                                <span class="py-1">Sel</span>
                                <span class="py-1">Rab</span>
                                <span class="py-1">Kam</span>
                                <span class="py-1">Jum</span>
                                <span class="py-1 text-amber-600">Sab</span>
                                <span class="py-1 text-rose-600">Min</span>
                            </div>

                            <!-- Grid Tanggal -->
                            <div class="grid grid-cols-7 gap-1 text-center">
                                <button
                                    v-for="(day, dIdx) in pickerCalendarDays"
                                    :key="'picker-day-' + dIdx + '-' + day.dateStr"
                                    type="button"
                                    @click.stop="handleSelectPickerDate(day)"
                                    :disabled="!day.hasWo"
                                    :title="
                                        day.hasWo
                                            ? `ADA WO: ${day.woInfo.nama_menu} (${day.woInfo.total_pm} Porsi) - Klik untuk membuka`
                                            : `Tidak ada data WO pada ${day.dateStr}`
                                    "
                                    class="h-9 relative rounded-xl text-xs font-bold transition flex flex-col items-center justify-center select-none"
                                    :class="[
                                        day.isSelected
                                            ? 'bg-primary text-white font-black shadow-md z-10 cursor-pointer'
                                            : day.hasWo
                                              ? 'bg-emerald-50 text-emerald-900 border border-emerald-300 font-black hover:bg-emerald-100 cursor-pointer shadow-2xs'
                                              : 'text-slate-300 bg-slate-50/40 border border-transparent cursor-not-allowed opacity-35 pointer-events-none select-none',
                                        day.isToday && !day.isSelected
                                            ? 'ring-2 ring-primary/40 font-black'
                                            : '',
                                    ]"
                                >
                                    <span>{{ day.dayNumber }}</span>

                                    <!-- Dot Indikator Ada WO -->
                                    <span
                                        v-if="day.hasWo && !day.isSelected"
                                        class="absolute bottom-1 w-1.5 h-1.5 rounded-full bg-emerald-600"
                                    ></span>
                                </button>
                            </div>

                            <!-- Legend / Petunjuk -->
                            <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] sm:text-[10.5px] text-slate-500 font-medium">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded bg-emerald-100 border border-emerald-300"></span>
                                    <span>Ada WO</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded bg-slate-200 opacity-60"></span>
                                    <span>Tidak Ada WO (Nonaktif)</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded bg-primary"></span>
                                    <span>Dipilih</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Cepat WO yang Terdata -->
                <div v-if="calendarWorkOrders.length > 0" class="flex items-center gap-2">
                    <label for="laporanQuickSelectWo" class="text-xs font-bold text-slate-500 whitespace-nowrap hidden sm:inline">
                        Daftar WO:
                    </label>
                    <select
                        id="laporanQuickSelectWo"
                        :value="selectedDate"
                        @change="(e) => handleSelectDate(e.target.value)"
                        class="h-10 px-3 text-xs font-bold rounded-xl border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-800 bg-white shadow-2xs max-w-[280px] sm:max-w-[340px] truncate"
                    >
                        <option
                            v-for="cWo in calendarWorkOrders"
                            :key="cWo.id"
                            :value="cWo.tanggal"
                        >
                            {{ cWo.tanggal }} — {{ cWo.nama_menu }} ({{ cWo.total_pm }} Porsi)
                        </option>
                    </select>
                </div>
            </div>

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
                            class="relative bg-slate-900 text-emerald-300 p-5 sm:p-7 rounded-2xl font-mono text-xs sm:text-sm leading-relaxed overflow-x-auto whitespace-pre-wrap selection:bg-emerald-500 selection:text-slate-950 shadow-inner border border-slate-800"
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Menu Normal PK -->
                        <div
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
                                Varian Khusus Menu Alergi ({{ varianAlergiList.length }} Jenis)
                            </h4>
                        </div>

                        <div
                            v-for="al in varianAlergiList"
                            :key="al.jenis"
                            class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 space-y-3"
                        >
                            <div class="flex items-center justify-between border-b border-amber-200/60 pb-2">
                                <span class="text-xs font-black text-amber-950 flex items-center gap-1.5">
                                    <ShieldAlert class="w-4 h-4 text-amber-600" />
                                    <span>Porsi Menu {{ al.jenis }}</span>
                                </span>
                                <span class="text-[10.5px] font-bold px-2 py-0.5 rounded-md bg-white border border-amber-200 text-amber-800">
                                    Menu Modifikasi Alergen
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Menu Alergi PK -->
                                <div class="p-3.5 rounded-xl bg-white border border-amber-200/90 shadow-2xs space-y-2.5">
                                    <span class="text-xs font-black text-slate-900 block">
                                        Menu {{ al.jenis }} Porsi Kecil (PK)
                                    </span>
                                    <div class="space-y-1 text-xs text-slate-700">
                                        <div
                                            v-for="(sm, idx) in al.menuList"
                                            :key="idx"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                            <span>{{ sm }}</span>
                                        </div>
                                    </div>
                                    <div
                                        class="pt-2 border-t border-slate-100 text-[11px] text-slate-600 grid grid-cols-3 sm:grid-cols-5 gap-1 font-medium"
                                    >
                                        <span>Energi: <strong>{{ al.akgPK.energi || 0 }} Kkal</strong></span>
                                        <span>Protein: <strong>{{ al.akgPK.protein || 0 }}g</strong></span>
                                        <span>Lemak: <strong>{{ al.akgPK.lemak || 0 }}g</strong></span>
                                        <span>Karbo: <strong>{{ al.akgPK.karbohidrat || 0 }}g</strong></span>
                                        <span>Serat: <strong>{{ al.akgPK.serat || 0 }}g</strong></span>
                                    </div>
                                </div>

                                <!-- Menu Alergi PB -->
                                <div class="p-3.5 rounded-xl bg-white border border-amber-200/90 shadow-2xs space-y-2.5">
                                    <span class="text-xs font-black text-slate-900 block">
                                        Menu {{ al.jenis }} Porsi Besar (PB)
                                    </span>
                                    <div class="space-y-1 text-xs text-slate-700">
                                        <div
                                            v-for="(sm, idx) in al.menuList"
                                            :key="idx"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600 shrink-0"></span>
                                            <span>{{ sm }}</span>
                                        </div>
                                    </div>
                                    <div
                                        class="pt-2 border-t border-slate-100 text-[11px] text-slate-600 grid grid-cols-3 sm:grid-cols-5 gap-1 font-medium"
                                    >
                                        <span>Energi: <strong>{{ al.akgPB.energi || 0 }} Kkal</strong></span>
                                        <span>Protein: <strong>{{ al.akgPB.protein || 0 }}g</strong></span>
                                        <span>Lemak: <strong>{{ al.akgPB.lemak || 0 }}g</strong></span>
                                        <span>Karbo: <strong>{{ al.akgPB.karbohidrat || 0 }}g</strong></span>
                                        <span>Serat: <strong>{{ al.akgPB.serat || 0 }}g</strong></span>
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
