<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    Users,
    Building2,
    Search,
    Filter,
    RotateCcw,
    MapPin,
    Eye,
    Edit3,
    Trash2,
    School,
    Phone,
    Mail,
    User,
    CheckCircle2,
    AlertCircle,
    HeartPulse,
    ChevronRight,
    ExternalLink,
    Plus,
    Utensils,
    Calendar,
    Clock,
    XCircle,
    Baby,
    BookOpen,
    GraduationCap,
    UserCheck,
    Briefcase,
    Heart,
    Smile,
    ShieldCheck,
    HeartHandshake,
    Landmark,
    Columns3,
    Check,
} from "lucide-vue-next";
import {
    KATEGORI_OPTIONS,
    JENIS_KEPEMILIKAN_OPTIONS,
    sortRincianByKategori,
} from "@/Services/penerimaManfaatConfig";
import { formatWilayahName, formatKabupatenName } from "@/Services/wilayah";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_laki_laki: 0,
            total_perempuan: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || "");
const selectedKategori = ref(props.filters.kategori || "");
const selectedKepemilikan = ref(props.filters.jenis_kepemilikan || "");

// Hitung total sekolah dan total posyandu
const totalSekolahCount = computed(() => {
    if (props.stats && props.stats.total_sekolah !== undefined) {
        return props.stats.total_sekolah;
    }
    return (props.kelompokList || []).filter((k) => k.kategori !== "Posyandu")
        .length;
});

const totalPosyanduCount = computed(() => {
    if (props.stats && props.stats.total_posyandu !== undefined) {
        return props.stats.total_posyandu;
    }
    return (props.kelompokList || []).filter((k) => k.kategori === "Posyandu")
        .length;
});

// Instant Reactive Filter (0ms Latency, Tanpa Loading/Roundtrip Server)
const filteredKelompokList = computed(() => {
    const list = props.kelompokList || [];
    const query = searchQuery.value.trim().toLowerCase();
    const kategori = selectedKategori.value;
    const kepemilikan = selectedKepemilikan.value;

    return list.filter((item) => {
        // 1. Filter Kategori
        if (kategori && item.kategori !== kategori) {
            return false;
        }

        // 2. Filter Kepemilikan (Status)
        if (kepemilikan && item.jenis_kepemilikan !== kepemilikan) {
            return false;
        }

        // 3. Filter Pencarian Teks
        if (query) {
            const nama = (item.nama_kelompok || "").toLowerCase();
            const kode = (item.kode_identitas || "").toLowerCase();
            const tipe = (item.tipe_identitas || "").toLowerCase();
            const kepala = (item.nama_kepala || "").toLowerCase();
            const telpKepala = (item.telepon_kepala || "").toLowerCase();
            const emailKepala = (item.email_kepala || "").toLowerCase();
            const pic = (item.nama_pic || "").toLowerCase();
            const telpPic = (item.telepon_pic || "").toLowerCase();
            const emailPic = (item.email_pic || "").toLowerCase();
            const alamat = (item.alamat_lengkap || "").toLowerCase();
            const desa = (item.desa_kelurahan || "").toLowerCase();
            const kecamatan = (item.kecamatan || "").toLowerCase();
            const kab = (item.kabupaten || "").toLowerCase();
            const alergi = Array.isArray(item.keterangan_alergi)
                ? item.keterangan_alergi
                      .map((a) =>
                          typeof a === "string" ? a : a?.jenis_alergi || "",
                      )
                      .join(" ")
                      .toLowerCase()
                : "";

            const isMatch =
                nama.includes(query) ||
                kode.includes(query) ||
                tipe.includes(query) ||
                kepala.includes(query) ||
                telpKepala.includes(query) ||
                emailKepala.includes(query) ||
                pic.includes(query) ||
                telpPic.includes(query) ||
                emailPic.includes(query) ||
                alamat.includes(query) ||
                desa.includes(query) ||
                kecamatan.includes(query) ||
                kab.includes(query) ||
                alergi.includes(query);

            if (!isMatch) return false;
        }

        return true;
    });
});

// Perhitungan Statistik 10 Kategori / Klasifikasi & Subtotal Sekolah/Posyandu
const categoryStats = computed(() => {
    const list = Array.isArray(props.kelompokList) ? props.kelompokList : [];

    const categories = {
        tk_ra: { label: "TK / RA", total: 0, pk: 0, pb: 0, color: "emerald", icon: Smile },
        sd_mi_1_3: { label: "SD / MI 1-3", fullLabel: "SD / MI (Kelas 1-3)", total: 0, pk: 0, pb: 0, color: "blue", icon: BookOpen },
        sd_mi_4_6: { label: "SD / MI 4-6", fullLabel: "SD / MI (Kelas 4-6)", total: 0, pk: 0, pb: 0, color: "indigo", icon: GraduationCap },
        smp_mts: { label: "SMP / MTs", total: 0, pk: 0, pb: 0, color: "sky", icon: School },
        sma_smk_ma: { label: "SMA / SMK / MA", total: 0, pk: 0, pb: 0, color: "violet", icon: GraduationCap },
        guru: { label: "Guru", total: 0, pk: 0, pb: 0, color: "amber", icon: UserCheck },
        tendik: { label: "Tenaga Kependidikan", total: 0, pk: 0, pb: 0, color: "slate", icon: Briefcase },
        balita: { label: "Balita", total: 0, pk: 0, pb: 0, color: "rose", icon: Baby },
        bumil: { label: "Bumil", fullLabel: "Ibu Hamil", total: 0, pk: 0, pb: 0, color: "pink", icon: Heart },
        busui: { label: "Busui", fullLabel: "Ibu Menyusui", total: 0, pk: 0, pb: 0, color: "purple", icon: HeartHandshake },
    };

    let totalSekolahPM = 0;
    let totalSekolahPK = 0;
    let totalSekolahPB = 0;
    let totalPosyanduPM = 0;
    let totalPosyanduPK = 0;
    let totalPosyanduPB = 0;

    for (const kpm of list) {
        const isPos = kpm.kategori === "Posyandu";
        const pmTot = Number(kpm.total_penerima) || 0;
        const pkTot = Number(kpm.total_porsi_kecil) || 0;
        const pbTot = Number(kpm.total_porsi_besar) || 0;

        if (isPos) {
            totalPosyanduPM += pmTot;
            totalPosyanduPK += pkTot;
            totalPosyanduPB += pbTot;
        } else {
            totalSekolahPM += pmTot;
            totalSekolahPK += pkTot;
            totalSekolahPB += pbTot;
        }

        const rincianList = Array.isArray(kpm.rincian) ? kpm.rincian : [];
        for (const r of rincianList) {
            const tot = Number(r.total) || (Number(r.jumlah_laki_laki) || 0) + (Number(r.jumlah_perempuan) || 0);
            const porsi = r.jenis_porsi;
            const sub = r.sub_kategori || "";
            const kat = kpm.kategori || "";

            let targetKey = null;

            if (["TK", "RA", "PAUD"].includes(kat) && sub === "Pelajar") {
                targetKey = "tk_ra";
            } else if (["Kelas 1", "Kelas 2", "Kelas 3"].includes(sub) && ["SD", "MI"].includes(kat)) {
                targetKey = "sd_mi_1_3";
            } else if (["Kelas 4", "Kelas 5", "Kelas 6"].includes(sub) && ["SD", "MI"].includes(kat)) {
                targetKey = "sd_mi_4_6";
            } else if (["Kelas 7", "Kelas 8", "Kelas 9"].includes(sub) || (["SMP", "MTs"].includes(kat) && sub.startsWith("Kelas"))) {
                targetKey = "smp_mts";
            } else if (["Kelas 10", "Kelas 11", "Kelas 12"].includes(sub) || (["SMA", "SMK", "MA", "MAK"].includes(kat) && sub.startsWith("Kelas"))) {
                targetKey = "sma_smk_ma";
            } else if (sub.includes("Guru")) {
                targetKey = "guru";
            } else if (sub.includes("Tenaga Kependidikan") || sub.includes("Satpam") || (sub.includes("Pendukung") && !sub.includes("Guru"))) {
                targetKey = "tendik";
            } else if (sub === "Balita") {
                targetKey = "balita";
            } else if (sub === "Ibu Hamil") {
                targetKey = "bumil";
            } else if (sub === "Ibu Menyusui") {
                targetKey = "busui";
            }

            if (targetKey && categories[targetKey]) {
                categories[targetKey].total += tot;
                if (porsi === "Porsi Kecil") {
                    categories[targetKey].pk += tot;
                } else {
                    categories[targetKey].pb += tot;
                }
            }
        }
    }

    return {
        categories,
        sekolahPM: { total: totalSekolahPM, pk: totalSekolahPK, pb: totalSekolahPB },
        posyanduPM: { total: totalPosyanduPM, pk: totalPosyanduPK, pb: totalPosyanduPB },
    };
});

// Pengelompokan Kategori untuk Visual Wrapper Card yang Terstruktur
const categoryGroups = computed(() => {
    const raw = categoryStats.value.categories;
    return [
        {
            key: "tk_ra",
            title: "TK / RA",
            badge: "PAUD",
            spanClass: "col-span-1 md:col-span-1 lg:col-span-1",
            gridClass: "grid-cols-1",
            total: raw.tk_ra.total,
            pk: raw.tk_ra.pk,
            pb: raw.tk_ra.pb,
            cards: [
                { ...raw.tk_ra, displayTitle: "Pelajar TK / RA" },
            ],
        },
        {
            key: "sd_mi",
            title: "SD / MI",
            badge: "Sekolah Dasar",
            spanClass: "col-span-1 md:col-span-2 lg:col-span-2",
            gridClass: "grid-cols-1 sm:grid-cols-2",
            total: raw.sd_mi_1_3.total + raw.sd_mi_4_6.total,
            pk: raw.sd_mi_1_3.pk + raw.sd_mi_4_6.pk,
            pb: raw.sd_mi_1_3.pb + raw.sd_mi_4_6.pb,
            cards: [
                { ...raw.sd_mi_1_3, displayTitle: "Kelas 1 - 3" },
                { ...raw.sd_mi_4_6, displayTitle: "Kelas 4 - 6" },
            ],
        },
        {
            key: "smp_mts",
            title: "SMP / MTs",
            badge: "Menengah Pertama",
            spanClass: "col-span-1 md:col-span-1 lg:col-span-1",
            gridClass: "grid-cols-1",
            total: raw.smp_mts.total,
            pk: raw.smp_mts.pk,
            pb: raw.smp_mts.pb,
            cards: [
                { ...raw.smp_mts, displayTitle: "Kelas 7 - 9" },
            ],
        },
        {
            key: "sma_smk_ma",
            title: "SMA / SMK / MA",
            badge: "Menengah Atas",
            spanClass: "col-span-1 md:col-span-1 lg:col-span-1",
            gridClass: "grid-cols-1",
            total: raw.sma_smk_ma.total,
            pk: raw.sma_smk_ma.pk,
            pb: raw.sma_smk_ma.pb,
            cards: [
                { ...raw.sma_smk_ma, displayTitle: "Kelas 10 - 12" },
            ],
        },
        {
            key: "ptk",
            title: "Pendidik & Tenaga Kependidikan",
            badge: "Tenaga Sekolah",
            spanClass: "col-span-1 md:col-span-2 lg:col-span-2",
            gridClass: "grid-cols-1 sm:grid-cols-2",
            total: raw.guru.total + raw.tendik.total,
            pk: raw.guru.pk + raw.tendik.pk,
            pb: raw.guru.pb + raw.tendik.pb,
            cards: [
                { ...raw.guru, displayTitle: "Guru" },
                { ...raw.tendik, displayTitle: "Tenaga Kependidikan" },
            ],
        },
        {
            key: "posyandu",
            title: "Sasaran Posyandu",
            badge: "Ibu & Anak",
            spanClass: "col-span-1 md:col-span-2 lg:col-span-3",
            gridClass: "grid-cols-1 sm:grid-cols-3",
            total: raw.balita.total + raw.bumil.total + raw.busui.total,
            pk: raw.balita.pk + raw.bumil.pk + raw.busui.pk,
            pb: raw.balita.pb + raw.bumil.pb + raw.busui.pb,
            cards: [
                { ...raw.balita, displayTitle: "Balita" },
                { ...raw.bumil, displayTitle: "Ibu Hamil" },
                { ...raw.busui, displayTitle: "Ibu Menyusui" },
            ],
        },
    ];
});

// State & Konfigurasi Visibilitas Kolom Tabel (Default Tampilkan Semua Kolom)
const isColumnDropdownOpen = ref(false);

const COLUMN_DEFINITIONS = [
    { key: "no", label: "No", default: true },
    { key: "kelompok", label: "Kelompok", default: true },
    { key: "kontak", label: "Kontak", default: true },
    { key: "alamat", label: "Alamat", default: true },
    { key: "gender", label: "Gender (L/P)", default: true },
    { key: "porsi", label: "Porsi (PK/PB)", default: true },
    { key: "total", label: "Total Porsi", default: true },
    { key: "waktu", label: "Waktu Daftar & Perbaharui", default: true },
    { key: "aksi", label: "Aksi", default: true },
];

const visibleColumns = ref({
    no: true,
    kelompok: true,
    kontak: true,
    alamat: true,
    gender: true,
    porsi: true,
    total: true,
    waktu: true,
    aksi: true,
});

const visibleColumnCount = computed(() => {
    return Object.values(visibleColumns.value).filter(Boolean).length;
});

function toggleAllColumns(val) {
    COLUMN_DEFINITIONS.forEach((col) => {
        visibleColumns.value[col.key] = val;
    });
}

function resetColumns() {
    COLUMN_DEFINITIONS.forEach((col) => {
        visibleColumns.value[col.key] = col.default;
    });
}

// Detail Modal state
const isDetailOpen = ref(false);
const activeKelompok = ref(null);

// Delete state
const isDeleteOpen = ref(false);
const deletingKelompok = ref(null);
const isDeleting = ref(false);

function applyFilters() {
    // Fungsi ini tetap disediakan untuk keyboard enter / tombol, 
    // namun filter sudah otomatis berjalan instan secara realtime via computed
}

function resetFilters() {
    searchQuery.value = "";
    selectedKategori.value = "";
    selectedKepemilikan.value = "";
}

function openDetail(item) {
    activeKelompok.value = item;
    isDetailOpen.value = true;
}

function closeDetail() {
    isDetailOpen.value = false;
    activeKelompok.value = null;
}

function confirmDelete(item) {
    deletingKelompok.value = item;
    isDeleteOpen.value = true;
}

function closeDeleteModal() {
    isDeleteOpen.value = false;
    deletingKelompok.value = null;
}

function executeDelete() {
    if (!deletingKelompok.value) return;
    isDeleting.value = true;
    router.delete(
        route(
            "penerima-manfaat.destroy",
            deletingKelompok.value.uid || deletingKelompok.value.id,
        ),
        {
            onFinish: () => {
                isDeleting.value = false;
                closeDeleteModal();
            },
        },
    );
}

function getKategoriBadgeColor(kategori) {
    switch (kategori) {
        case "TK":
        case "RA":
        case "PAUD":
            return "bg-amber-50 text-amber-700 border-amber-200";
        case "SD":
        case "MI":
            return "bg-rose-50 text-rose-700 border-rose-200";
        case "SMP":
        case "MTs":
            return "bg-sky-50 text-sky-700 border-sky-200";
        case "SMA":
        case "SMK":
        case "MA":
        case "MAK":
            return "bg-indigo-50 text-indigo-700 border-indigo-200";
        case "Posyandu":
            return "bg-emerald-50 text-emerald-700 border-emerald-200";
        default:
            return "bg-slate-100 text-slate-700 border-slate-200";
    }
}

function formatDateTimeWita(dateString) {
    if (!dateString) return "-";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "-";

    const dtf = new Intl.DateTimeFormat("id-ID", {
        timeZone: "Asia/Makassar",
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hourCycle: "h23",
    });

    const parts = dtf.formatToParts(date);
    const getPart = (type) => parts.find((p) => p.type === type)?.value || "";

    const weekday = getPart("weekday");
    const day = getPart("day");
    const month = getPart("month");
    const year = getPart("year");
    const hour = getPart("hour");
    const minute = getPart("minute");
    const second = getPart("second");

    return `${weekday}, ${day} ${month} ${year}, ${hour}:${minute}:${second} WITA`;
}
</script>

<template>
    <AppLayout
        title="Penerima Manfaat"
        subtitle="Kelola Data Kelompok dan Rincian Penerima Manfaat SPPG"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Penerima Manfaat" />

        <div class="space-y-6">
            <!-- Alert jika Unit SPPG belum dikonfigurasi -->
            <div
                v-if="!unitSppg"
                class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3 shadow-xs"
            >
                <AlertCircle class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                <div class="text-sm">
                    <p class="font-bold">Unit SPPG Belum Dikonfigurasi</p>
                    <p class="mt-0.5 text-amber-800 text-xs">
                        Untuk menambahkan kelompok penerima manfaat, pastikan
                        data Unit SPPG sudah dilengkapi di halaman Dashboard.
                    </p>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-primary hover:underline"
                    >
                        <span>Ke Pengaturan Unit SPPG</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <!-- ================= 5 RINGKASAN UTAMA METRIC CARDS ================= -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
                <!-- 1. Total Kelompok -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-4 sm:p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500 truncate">
                                Total Kelompok
                            </p>
                            <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                                <span class="text-lg sm:text-xl font-bold text-blue-700">
                                    {{ totalSekolahCount.toLocaleString("id-ID") }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-blue-600"
                                        >Sekolah</span
                                    >
                                </span>
                                <span class="text-slate-300 font-light">/</span>
                                <span class="text-lg sm:text-xl font-bold text-emerald-700">
                                    {{ totalPosyanduCount.toLocaleString("id-ID") }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-emerald-600"
                                        >Posyandu</span
                                    >
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Total: <strong class="text-slate-700">{{ (stats.total_kelompok || (props.kelompokList ? props.kelompokList.length : 0)).toLocaleString("id-ID") }}</strong> Satuan PM
                            </p>
                        </div>
                        <div
                            class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <School class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. PM Sekolah & Posyandu -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-4 sm:p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500 truncate">
                                PM Sekolah & Posyandu
                            </p>
                            <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                                <span class="text-lg sm:text-xl font-bold text-blue-700">
                                    {{ categoryStats.sekolahPM.total.toLocaleString("id-ID") }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-blue-600"
                                        >Sekolah</span
                                    >
                                </span>
                                <span class="text-slate-300 font-light">/</span>
                                <span class="text-lg sm:text-xl font-bold text-emerald-700">
                                    {{ categoryStats.posyanduPM.total.toLocaleString("id-ID") }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-emerald-600"
                                        >Posyandu</span
                                    >
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Total: <strong class="text-slate-700">{{ (categoryStats.sekolahPM.total + categoryStats.posyanduPM.total).toLocaleString("id-ID") }}</strong> Porsi
                            </p>
                        </div>
                        <div
                            class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <Building2 class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- 3. Rincian Porsi -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-4 sm:p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500 truncate">
                                Rincian Porsi
                            </p>
                            <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                                <span class="text-lg sm:text-xl font-bold text-amber-700">
                                    {{
                                        (
                                            stats.total_porsi_kecil || 0
                                        ).toLocaleString("id-ID")
                                    }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-amber-600"
                                        >Kecil</span
                                    >
                                </span>
                                <span class="text-slate-300 font-light">/</span>
                                <span class="text-lg sm:text-xl font-bold text-blue-700">
                                    {{
                                        (
                                            stats.total_porsi_besar || 0
                                        ).toLocaleString("id-ID")
                                    }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-blue-600"
                                        >Besar</span
                                    >
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Porsi Kecil & Porsi Besar
                            </p>
                        </div>
                        <div
                            class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <Utensils class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- 4. Rincian Gender -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-4 sm:p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500 truncate">
                                Rincian Gender
                            </p>
                            <div class="flex items-baseline gap-1.5 sm:gap-2 mt-1 flex-wrap">
                                <span class="text-lg sm:text-xl font-bold text-sky-700">
                                    {{
                                        (
                                            stats.total_laki_laki || 0
                                        ).toLocaleString("id-ID")
                                    }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-sky-600"
                                        >L</span
                                    >
                                </span>
                                <span class="text-slate-300 font-light">/</span>
                                <span class="text-lg sm:text-xl font-bold text-pink-700">
                                    {{
                                        (
                                            stats.total_perempuan || 0
                                        ).toLocaleString("id-ID")
                                    }}
                                    <span
                                        class="text-[10px] font-sans font-bold text-pink-600"
                                        >P</span
                                    >
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Laki-Laki & Perempuan
                            </p>
                        </div>
                        <div
                            class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <User class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- 5. Total Penerima -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-4 sm:p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500 truncate">
                                Total Penerima Manfaat
                            </p>
                            <h3
                                class="text-xl sm:text-2xl font-bold text-primary mt-1 truncate"
                            >
                                {{
                                    (stats.total_penerima || 0).toLocaleString("id-ID")
                                }}
                            </h3>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Porsi Penerima Manfaat
                            </p>
                        </div>
                        <div
                            class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ================= 10 KLASIFIKASI PENERIMA MANFAAT (TERBUNGKUS PER KATEGORI) ================= -->
            <div class="space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 px-0.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                        <span>Klasifikasi Penerima Manfaat</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/60">
                            10 Jenis Terklasifikasi
                        </span>
                    </h3>
                    <span class="text-[11px] text-slate-400 font-medium">
                        Dikelompokkan Berdasarkan Jenjang & Kategori Sasaran
                    </span>
                </div>

                <!-- Grid Group Kategori -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
                    <div
                        v-for="group in categoryGroups"
                        :key="group.key"
                        :class="[
                            'p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex flex-col justify-between transition-all hover:shadow-xs hover:border-slate-300',
                            group.spanClass,
                        ]"
                    >
                        <!-- Header Wrapper Kategori -->
                        <div class="flex items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="h-2 w-2 rounded-full bg-blue-600 shrink-0"></span>
                                <h4 class="text-xs font-bold text-slate-900 truncate">
                                    {{ group.title }}
                                </h4>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200/70">
                                    {{ group.total.toLocaleString("id-ID") }} Porsi
                                </span>
                            </div>
                        </div>

                        <!-- Sub-cards di dalam kategori -->
                        <div :class="['grid gap-2.5 flex-1', group.gridClass]">
                            <div
                                v-for="(card, cIdx) in group.cards"
                                :key="cIdx"
                                class="p-3 rounded-xl bg-slate-50/80 border border-slate-200/70 hover:bg-white hover:border-blue-300 hover:shadow-2xs transition-all flex flex-col justify-between gap-2.5 group"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-bold text-slate-700 truncate" :title="card.displayTitle || card.label">
                                            {{ card.displayTitle || card.label }}
                                        </p>
                                        <div class="flex items-baseline gap-1 mt-0.5">
                                            <span class="text-lg sm:text-xl font-black text-slate-900 group-hover:text-blue-700 transition-colors">
                                                {{ card.total.toLocaleString("id-ID") }}
                                            </span>
                                            <span class="text-[10px] font-medium text-slate-400">Porsi</span>
                                        </div>
                                    </div>
                                    <div
                                        class="h-7 w-7 rounded-lg bg-white text-blue-600 border border-slate-200/80 flex items-center justify-center shrink-0 shadow-2xs group-hover:border-blue-200 group-hover:bg-blue-50 transition-colors"
                                    >
                                        <component :is="card.icon" class="h-3.5 w-3.5" />
                                    </div>
                                </div>

                                <!-- Modern Elegant PK & PB Badges -->
                                <div class="pt-2 border-t border-slate-200/60 flex items-center gap-1.5 w-full">
                                    <!-- Badge PK -->
                                    <div
                                        class="flex items-center justify-between gap-1 flex-1 min-w-0 px-2 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-900 shadow-2xs"
                                        title="Porsi Kecil"
                                    >
                                        <span class="inline-flex items-center gap-1 font-bold text-amber-700 text-[10px] shrink-0">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                            PK
                                        </span>
                                        <span class="font-extrabold text-amber-950 font-mono text-[11px] truncate">
                                            {{ card.pk.toLocaleString("id-ID") }}
                                        </span>
                                    </div>

                                    <!-- Badge PB -->
                                    <div
                                        class="flex items-center justify-between gap-1 flex-1 min-w-0 px-2 py-1 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-900 shadow-2xs"
                                        title="Porsi Besar"
                                    >
                                        <span class="inline-flex items-center gap-1 font-bold text-blue-700 text-[10px] shrink-0">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                            PB
                                        </span>
                                        <span class="font-extrabold text-blue-950 font-mono text-[11px] truncate">
                                            {{ card.pb.toLocaleString("id-ID") }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= MAIN DATA CARD ================= -->
            <Card className="bg-white border-slate-200/80 shadow-xs">
                <!-- Header Card -->
                <CardHeader
                    className="border-b border-slate-100 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div>
                        <CardTitle
                            className="text-base sm:text-lg font-bold flex items-center gap-2.5 text-slate-900"
                        >
                            <div
                                class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0"
                            >
                                <Users class="h-4 w-4" />
                            </div>
                            <span>Daftar Kelompok Penerima Manfaat</span>
                        </CardTitle>
                        <CardDescription
                            className="text-xs text-slate-500 mt-1 pl-10.5 capitalize"
                        >
                            Daftar seluruh satuan penerima manfaat SPPG
                        </CardDescription>
                    </div>

                    <div class="shrink-0 pl-10.5 sm:pl-0">
                        <Link
                            :href="route('penerima-manfaat.create')"
                            class="inline-flex items-center gap-2 h-10 px-4 text-xs font-semibold rounded-lg bg-primary hover:bg-primary/90 text-white shadow-xs transition-colors cursor-pointer"
                        >
                            <Plus class="h-4 w-4 stroke-[3]" />
                            <span>Tambah Kelompok</span>
                        </Link>
                    </div>
                </CardHeader>

                <CardContent className="p-5 sm:p-6 space-y-6">
                    <!-- ================= FILTER & SEARCH BAR ================= -->
                    <div
                        class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80"
                    >
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-3"
                        >
                            <!-- Kiri: Pencarian Instan (Proporsional tidak terlalu lebar) -->
                            <div class="relative w-full md:w-72 lg:w-80 shrink-0">
                                <div
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                                >
                                    <Search class="h-4 w-4 text-slate-400" />
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari nama kelompok, NPSN, PIC, dll..."
                                    class="block w-full h-10 rounded-lg border border-slate-200 bg-white pl-10 pr-9 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all shadow-2xs"
                                />
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    @click="searchQuery = ''"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
                                    title="Bersihkan Pencarian"
                                >
                                    <XCircle class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Kanan: Filter Kategori, Filter Status, Opsi Kolom, Reset Filter -->
                            <div
                                class="flex items-center gap-2.5 flex-wrap justify-start md:justify-end w-full md:w-auto"
                            >
                                <!-- Filter Kategori -->
                                <div class="w-full sm:w-44 lg:w-48">
                                    <select
                                        v-model="selectedKategori"
                                        class="w-full h-10 px-3 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-700 shadow-2xs cursor-pointer"
                                    >
                                        <option value="">Semua Kategori</option>
                                        <option
                                            v-for="kat in KATEGORI_OPTIONS"
                                            :key="kat.value"
                                            :value="kat.value"
                                        >
                                            {{ kat.value }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Filter Status -->
                                <div class="w-full sm:w-36 lg:w-40">
                                    <select
                                        v-model="selectedKepemilikan"
                                        class="w-full h-10 px-3 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-700 shadow-2xs cursor-pointer"
                                    >
                                        <option value="">Semua Status</option>
                                        <option
                                            v-for="j in JENIS_KEPEMILIKAN_OPTIONS"
                                            :key="j.value"
                                            :value="j.value"
                                        >
                                            {{ j.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Dropdown Visibilitas Kolom Tabel -->
                                <div class="relative">
                                    <button
                                        type="button"
                                        @click="isColumnDropdownOpen = !isColumnDropdownOpen"
                                        class="h-10 px-3 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 flex items-center justify-center gap-1.5 transition-colors cursor-pointer shrink-0 shadow-2xs"
                                        :class="{ 'ring-2 ring-primary/20 border-primary text-primary': isColumnDropdownOpen }"
                                        title="Pilih kolom yang ingin ditampilkan"
                                    >
                                        <Columns3 class="h-3.5 w-3.5 text-slate-500" />
                                        <span>Kolom</span>
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ visibleColumnCount }}
                                        </span>
                                    </button>

                                    <!-- Backdrop penutup dropdown jika klik di luar -->
                                    <div
                                        v-if="isColumnDropdownOpen"
                                        class="fixed inset-0 z-40"
                                        @click="isColumnDropdownOpen = false"
                                    ></div>

                                    <!-- Panel Menu Checklist Kolom -->
                                    <div
                                        v-if="isColumnDropdownOpen"
                                        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 p-3 z-50 animate-in fade-in zoom-in-95 duration-150 space-y-2.5"
                                    >
                                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                            <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                                <Columns3 class="h-3.5 w-3.5 text-primary" />
                                                Tampilkan Kolom
                                            </span>
                                            <div class="flex items-center gap-1 text-[11px]">
                                                <button
                                                    type="button"
                                                    @click="toggleAllColumns(true)"
                                                    class="text-primary hover:underline font-semibold cursor-pointer"
                                                >
                                                    Semua
                                                </button>
                                                <span class="text-slate-300">|</span>
                                                <button
                                                    type="button"
                                                    @click="resetColumns"
                                                    class="text-slate-500 hover:underline cursor-pointer"
                                                >
                                                    Reset
                                                </button>
                                            </div>
                                        </div>

                                        <div class="space-y-1 max-h-64 overflow-y-auto pr-0.5">
                                            <label
                                                v-for="col in COLUMN_DEFINITIONS"
                                                :key="col.key"
                                                class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer text-xs select-none"
                                            >
                                                <input
                                                    type="checkbox"
                                                    v-model="visibleColumns[col.key]"
                                                    class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary/20 cursor-pointer"
                                                />
                                                <span :class="visibleColumns[col.key] ? 'font-semibold text-slate-800' : 'text-slate-400'">
                                                    {{ col.label }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Reset Filter -->
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="h-10 px-3 text-xs font-semibold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 flex items-center justify-center transition-colors cursor-pointer shrink-0 shadow-2xs"
                                    title="Reset Filter"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ================= TABLE SECTION (DENGAN PADDING & JARAK LEGA) ================= -->
                    <div
                        class="rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs bg-white"
                    >
                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-left text-xs border-collapse"
                            >
                                <thead
                                    class="bg-slate-50/90 border-b border-slate-200"
                                >
                                    <tr>
                                        <th
                                            v-if="visibleColumns.no"
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-12"
                                        >
                                            No
                                        </th>
                                        <th
                                            v-if="visibleColumns.kelompok"
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[240px]"
                                        >
                                            Kelompok
                                        </th>
                                        <th
                                            v-if="visibleColumns.kontak"
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[200px]"
                                        >
                                            Kontak
                                        </th>
                                        <th
                                            v-if="visibleColumns.alamat"
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[220px]"
                                        >
                                            Alamat
                                        </th>
                                        <th
                                            v-if="visibleColumns.gender"
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-32"
                                        >
                                            Gender (L/P)
                                        </th>
                                        <th
                                            v-if="visibleColumns.porsi"
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-36"
                                        >
                                            Porsi (PK/PB)
                                        </th>
                                        <th
                                            v-if="visibleColumns.total"
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-24"
                                        >
                                            Total
                                        </th>
                                        <th
                                            v-if="visibleColumns.waktu"
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[240px]"
                                        >
                                            Waktu Daftar & Perbaharui
                                        </th>
                                        <th
                                            v-if="visibleColumns.aksi"
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-28"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 bg-white"
                                >
                                    <tr
                                        v-for="(item, index) in filteredKelompokList"
                                        :key="item.id"
                                        class="hover:bg-slate-50/60 transition-colors"
                                    >
                                        <!-- No -->
                                        <td
                                            v-if="visibleColumns.no"
                                            class="py-4 px-4 text-center font-medium text-slate-400"
                                        >
                                            {{ index + 1 }}
                                        </td>

                                        <!-- Kelompok -->
                                        <td v-if="visibleColumns.kelompok" class="py-4 px-5">
                                            <div class="space-y-1.5">
                                                <div
                                                    class="flex items-center gap-1.5 flex-wrap"
                                                >
                                                    <span
                                                        :class="[
                                                            'px-2 py-0.5 text-[10px] font-bold rounded-full border',
                                                            getKategoriBadgeColor(
                                                                item.kategori,
                                                            ),
                                                        ]"
                                                    >
                                                        {{ item.kategori }}
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'px-1.5 py-0.5 text-[10px] font-semibold rounded',
                                                            item.jenis_kepemilikan ===
                                                            'Negeri'
                                                                ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                                                : 'bg-amber-50 text-amber-700 border border-amber-200',
                                                        ]"
                                                    >
                                                        {{
                                                            item.jenis_kepemilikan
                                                        }}
                                                    </span>
                                                    <span
                                                        v-if="item.kategori === 'Posyandu' && item.jumlah_kader"
                                                        class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-sky-50 text-sky-700 border border-sky-200"
                                                        title="Jumlah kader penanggung jawab posyandu (tidak masuk hitungan PM)"
                                                    >
                                                        {{ item.jumlah_kader }} Kader
                                                    </span>
                                                </div>

                                                <p
                                                    class="font-bold text-slate-900 text-sm leading-snug"
                                                >
                                                    {{ item.nama_kelompok }}
                                                </p>

                                                <div
                                                    class="flex items-center gap-1.5 text-xs text-slate-500"
                                                >
                                                    <span
                                                        class="font-semibold text-slate-600"
                                                        >{{
                                                            item.tipe_identitas
                                                        }}:</span
                                                    >
                                                    <span>{{
                                                        item.kode_identitas
                                                    }}</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kontak -->
                                        <td v-if="visibleColumns.kontak" class="py-4 px-5">
                                            <div class="space-y-2 text-xs">
                                                <div>
                                                    <p
                                                        class="font-semibold text-slate-800 flex items-center gap-1"
                                                    >
                                                        <User
                                                            class="h-3 w-3 text-slate-400"
                                                        />
                                                        <span
                                                            >Kepala:
                                                            {{
                                                                item.nama_kepala
                                                            }}</span
                                                        >
                                                    </p>
                                                    <p
                                                        class="text-slate-500 flex items-center gap-1 mt-0.5"
                                                    >
                                                        <Phone
                                                            class="h-2.5 w-2.5 text-slate-400"
                                                        />
                                                        <span
                                                            >+{{
                                                                item.telepon_kepala
                                                            }}</span
                                                        >
                                                    </p>
                                                </div>

                                                <div
                                                    class="pt-1.5 border-t border-slate-100"
                                                >
                                                    <p
                                                        class="font-semibold text-slate-800 flex items-center gap-1"
                                                    >
                                                        <User
                                                            class="h-3 w-3 text-slate-400"
                                                        />
                                                        <span
                                                            >PIC:
                                                            {{
                                                                item.nama_pic
                                                            }}</span
                                                        >
                                                    </p>
                                                    <p
                                                        class="text-slate-500 flex items-center gap-1 mt-0.5"
                                                    >
                                                        <Phone
                                                            class="h-2.5 w-2.5 text-slate-400"
                                                        />
                                                        <span
                                                            >+{{
                                                                item.telepon_pic
                                                            }}</span
                                                        >
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Wilayah & Alamat -->
                                        <td v-if="visibleColumns.alamat" class="py-4 px-5">
                                            <div class="space-y-1">
                                                <p
                                                    class="font-medium text-slate-800 flex items-center gap-1"
                                                >
                                                    <MapPin
                                                        class="h-3.5 w-3.5 text-rose-500 shrink-0"
                                                    />
                                                    <span
                                                        >{{
                                                            formatWilayahName(
                                                                item.desa_kelurahan,
                                                            )
                                                        }},
                                                        {{
                                                            formatWilayahName(
                                                                item.kecamatan,
                                                            )
                                                        }},
                                                        {{
                                                            formatKabupatenName(
                                                                item.kabupaten,
                                                            )
                                                        }},
                                                        {{
                                                            formatWilayahName(
                                                                item.provinsi,
                                                            )
                                                        }}
                                                        ({{ item.kode_pos }})
                                                    </span>
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500 leading-tight"
                                                >
                                                    📍
                                                    <a
                                                        :href="`https://www.google.com/maps?q=${item.latitude},${item.longitude}`"
                                                        target="_blank"
                                                    >
                                                        {{
                                                            Number(
                                                                item.latitude,
                                                            ).toFixed(5)
                                                        }},
                                                        {{
                                                            Number(
                                                                item.longitude,
                                                            ).toFixed(5)
                                                        }}
                                                    </a>
                                                </p>
                                            </div>
                                        </td>

                                        <!-- Penerima (L / P) -->
                                        <td v-if="visibleColumns.gender" class="py-4 px-4 text-center">
                                            <div
                                                class="inline-grid grid-cols-2 gap-1 text-xs min-w-[100px]"
                                            >
                                                <span
                                                    class="py-0.5 px-1.5 rounded bg-sky-50 text-sky-700 border border-sky-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                                    title="Laki-Laki"
                                                >
                                                    L: {{ item.total_laki_laki }}
                                                </span>
                                                <span
                                                    class="py-0.5 px-1.5 rounded bg-pink-50 text-pink-700 border border-pink-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                                    title="Perempuan"
                                                >
                                                    P: {{ item.total_perempuan }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Porsi (Kecil / Besar) & Alergi -->
                                        <td v-if="visibleColumns.porsi" class="py-4 px-4 text-center">
                                            <div
                                                class="inline-flex flex-col gap-1 min-w-[120px]"
                                            >
                                                <!-- Baris PK & PB (Bagi 2 Sama Lebar & 1 Baris) -->
                                                <div
                                                    class="grid grid-cols-2 gap-1 text-xs"
                                                >
                                                    <span
                                                        class="py-0.5 px-1.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                                        title="Porsi Kecil"
                                                    >
                                                        PK: {{ item.total_porsi_kecil ?? 0 }}
                                                    </span>
                                                    <span
                                                        class="py-0.5 px-1.5 rounded bg-blue-50 text-blue-800 border border-blue-200 font-bold whitespace-nowrap text-center flex items-center justify-center"
                                                        title="Porsi Besar"
                                                    >
                                                        PB: {{ item.total_porsi_besar ?? 0 }}
                                                    </span>
                                                </div>

                                                <!-- Baris Alergi (Rata Kanan-Kiri Penuh Sejajar PK+PB) -->
                                                <div
                                                    v-if="
                                                        (item.alergi_porsi_kecil > 0 ||
                                                        item.alergi_porsi_besar > 0)
                                                    "
                                                    class="w-full"
                                                >
                                                    <span
                                                        class="w-full py-0.5 px-1.5 rounded text-[10.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center gap-1 whitespace-nowrap text-center shadow-2xs"
                                                        :title="`Alergi Makanan: ${item.alergi_porsi_kecil || 0} PK, ${item.alergi_porsi_besar || 0} PB`"
                                                    >
                                                        <HeartPulse
                                                            class="h-3 w-3 text-rose-600 shrink-0"
                                                        />
                                                        <span>Alergi: {{ (item.alergi_porsi_kecil || 0) + (item.alergi_porsi_besar || 0) }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Total Penerima -->
                                        <td v-if="visibleColumns.total" class="py-4 px-4 text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20"
                                            >
                                                {{ item.total_penerima }}
                                            </span>
                                        </td>

                                        <!-- Terdaftar Pada & Terakhir Diperbaharui -->
                                        <td v-if="visibleColumns.waktu" class="py-4 px-5">
                                            <div
                                                class="space-y-1.5 text-[11px]"
                                            >
                                                <div>
                                                    <div
                                                        class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                                    >
                                                        <Calendar
                                                            class="h-3 w-3 text-slate-400 shrink-0"
                                                        />
                                                        <span>Terdaftar:</span>
                                                    </div>
                                                    <p
                                                        class="font-medium text-slate-800 leading-tight mt-0.5 whitespace-nowrap"
                                                    >
                                                        {{
                                                            formatDateTimeWita(
                                                                item.created_at,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="pt-1.5 border-t border-slate-100"
                                                >
                                                    <div
                                                        class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider"
                                                    >
                                                        <Clock
                                                            class="h-3 w-3 text-slate-400 shrink-0"
                                                        />
                                                        <span>Diperbarui:</span>
                                                    </div>
                                                    <p
                                                        class="font-medium text-slate-800 leading-tight mt-0.5 whitespace-nowrap"
                                                    >
                                                        {{
                                                            formatDateTimeWita(
                                                                item.updated_at,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Aksi (Tombol Elegan) -->
                                        <td v-if="visibleColumns.aksi" class="py-4 px-4 text-center">
                                            <div
                                                class="flex items-center justify-center gap-1.5"
                                            >
                                                <button
                                                    type="button"
                                                    @click="openDetail(item)"
                                                    class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-primary flex items-center justify-center transition-colors cursor-pointer shadow-2xs"
                                                    title="Lihat Detail"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </button>
                                                <Link
                                                    :href="
                                                        route(
                                                            'penerima-manfaat.edit',
                                                            item.uid || item.id,
                                                        )
                                                    "
                                                    class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-amber-600 flex items-center justify-center transition-colors cursor-pointer shadow-2xs"
                                                    title="Edit Data"
                                                >
                                                    <Edit3 class="h-4 w-4" />
                                                </Link>
                                                <button
                                                    type="button"
                                                    @click="confirmDelete(item)"
                                                    class="h-8 w-8 rounded-lg border border-slate-200 bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center transition-colors cursor-pointer shadow-2xs"
                                                    title="Hapus"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty State (Dengan Padding Lega) -->
                                    <tr v-if="filteredKelompokList.length === 0">
                                        <td
                                            :colspan="visibleColumnCount || 1"
                                            class="py-16 px-6 text-center"
                                        >
                                            <!-- Kasus 1: Ada data tetapi terfilter -->
                                            <div
                                                v-if="kelompokList.length > 0"
                                                class="flex flex-col items-center justify-center max-w-md mx-auto space-y-4"
                                            >
                                                <div
                                                    class="h-16 w-16 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shadow-xs"
                                                >
                                                    <Search class="h-8 w-8 text-amber-500" />
                                                </div>
                                                <div class="space-y-1">
                                                    <h3
                                                        class="text-sm font-bold text-slate-900"
                                                    >
                                                        Tidak Ada Kelompok yang Cocok
                                                    </h3>
                                                    <p
                                                        class="text-xs text-slate-500 max-w-sm leading-relaxed"
                                                    >
                                                        Tidak ada data yang sesuai dengan kata kunci pencarian atau kombinasi filter saat ini.
                                                    </p>
                                                    <div class="pt-2">
                                                        <button
                                                            type="button"
                                                            @click="resetFilters"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 shadow-2xs transition-colors cursor-pointer"
                                                        >
                                                            <RotateCcw class="h-3.5 w-3.5" />
                                                            <span>Reset Filter</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Kasus 2: Database benar-benar kosong -->
                                            <div
                                                v-else
                                                class="flex flex-col items-center justify-center max-w-md mx-auto space-y-4"
                                            >
                                                <div
                                                    class="h-16 w-16 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shadow-xs"
                                                >
                                                    <School class="h-8 w-8" />
                                                </div>
                                                <div class="space-y-1">
                                                    <h3
                                                        class="text-sm font-bold text-slate-900"
                                                    >
                                                        Belum Ada Kelompok
                                                        Penerima Manfaat
                                                    </h3>
                                                    <p
                                                        class="text-xs text-slate-500 max-w-sm leading-relaxed"
                                                    >
                                                        Daftarkan data kelompok
                                                        pertama Anda untuk mulai
                                                        memetakan penerima
                                                        manfaat SPPG.
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ================= MODAL DETAIL KELOMPOK ================= -->
        <Modal :show="isDetailOpen" @close="closeDetail" max-width="3xl">
            <div v-if="activeKelompok" class="p-6 space-y-6">
                <!-- Modal Header -->
                <div
                    class="flex items-start justify-between border-b border-slate-100 pb-4"
                >
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-xl font-bold text-slate-900">
                                {{ activeKelompok.nama_kelompok }}
                            </h3>
                            <span
                                :class="[
                                    'px-2 py-0.5 text-xs font-bold rounded-full border',
                                    getKategoriBadgeColor(
                                        activeKelompok.kategori,
                                    ),
                                ]"
                            >
                                {{ activeKelompok.kategori }}
                            </span>
                            <span
                                :class="[
                                    'px-2 py-0.5 text-xs font-medium rounded',
                                    activeKelompok.jenis_kepemilikan ===
                                    'Negeri'
                                        ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                        : 'bg-amber-50 text-amber-700 border border-amber-200',
                                ]"
                            >
                                {{ activeKelompok.jenis_kepemilikan }}
                            </span>
                            <span
                                v-if="activeKelompok.kategori === 'Posyandu' && activeKelompok.jumlah_kader"
                                class="px-2 py-0.5 text-xs font-bold rounded-full bg-sky-50 text-sky-700 border border-sky-200"
                                title="Jumlah kader penanggung jawab (tidak dihitung sebagai PM)"
                            >
                                {{ activeKelompok.jumlah_kader }} Kader
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ activeKelompok.tipe_identitas }}:
                            {{ activeKelompok.kode_identitas }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeDetail"
                        class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-100 cursor-pointer transition duration-150 ease-in-out"
                    >
                        ✕
                    </button>
                </div>

                <!-- Kontak & Alamat Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- KS & PIC Card -->
                    <div
                        class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3"
                    >
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"
                        >
                            <User class="h-3.5 w-3.5 text-primary" />
                            <span>Kontak Satuan</span>
                        </h4>

                        <div class="space-y-2 text-xs">
                            <!-- Khusus Posyandu: Jumlah Kader Card -->
                            <div
                                v-if="activeKelompok.kategori === 'Posyandu'"
                                class="p-2.5 rounded-lg bg-sky-50/80 border border-sky-200 flex items-center justify-between"
                            >
                                <div>
                                    <p class="text-[11px] font-bold text-sky-900 uppercase flex items-center gap-1.5">
                                        <Users class="h-3 w-3 text-sky-600" />
                                        <span>Kader Posyandu</span>
                                    </p>
                                    <p class="text-[10px] text-sky-700 mt-0.5">
                                        Penanggung jawab (tidak dihitung PM)
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-sky-600 text-white shadow-2xs">
                                    {{ activeKelompok.jumlah_kader || 0 }} Orang
                                </span>
                            </div>

                            <div
                                class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                            >
                                <p
                                    class="text-[11px] font-bold text-slate-400 uppercase"
                                >
                                    Kepala Satuan / Pengelola
                                </p>
                                <p class="font-bold text-slate-900 mt-0.5">
                                    {{ activeKelompok.nama_kepala }}
                                </p>
                                <p
                                    class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                                >
                                    <Mail class="h-3 w-3 text-slate-400" />
                                    <span>{{
                                        activeKelompok.email_kepala
                                    }}</span>
                                </p>
                                <p
                                    class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                                >
                                    <Phone class="h-3 w-3 text-slate-400" />
                                    <span
                                        >+{{
                                            activeKelompok.telepon_kepala
                                        }}</span
                                    >
                                </p>
                            </div>

                            <div
                                class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                            >
                                <p
                                    class="text-[11px] font-bold text-slate-400 uppercase"
                                >
                                    PIC (Petugas Lapangan)
                                </p>
                                <p class="font-bold text-slate-900 mt-0.5">
                                    {{ activeKelompok.nama_pic }}
                                </p>
                                <p
                                    class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                                >
                                    <Mail class="h-3 w-3 text-slate-400" />
                                    <span>{{ activeKelompok.email_pic }}</span>
                                </p>
                                <p
                                    class="text-slate-600 text-[11px] mt-0.5 flex items-center gap-1"
                                >
                                    <Phone class="h-3 w-3 text-slate-400" />
                                    <span
                                        >+{{ activeKelompok.telepon_pic }}</span
                                    >
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat & Lokasi Card -->
                    <div
                        class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3"
                    >
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"
                        >
                            <MapPin class="h-3.5 w-3.5 text-rose-500" />
                            <span>Alamat & Geografis</span>
                        </h4>

                        <div class="space-y-2 text-xs">
                            <div
                                class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                            >
                                <p
                                    class="text-[10px] font-bold text-slate-400 uppercase"
                                >
                                    Alamat Lengkap
                                </p>
                                <p class="text-[11px] text-slate-500 mt-1">
                                    {{ activeKelompok.alamat_lengkap }},
                                    Desa/Kelurahan
                                    {{
                                        formatWilayahName(
                                            activeKelompok.desa_kelurahan,
                                        )
                                    }}, Kecamatan
                                    {{
                                        formatWilayahName(
                                            activeKelompok.kecamatan,
                                        )
                                    }}, Kabupaten
                                    {{
                                        formatKabupatenName(
                                            activeKelompok.kabupaten,
                                        )
                                    }}, Provinsi
                                    {{
                                        formatWilayahName(
                                            activeKelompok.provinsi,
                                        )
                                    }}
                                    ({{ activeKelompok.kode_pos }})
                                </p>
                            </div>

                            <div
                                class="p-2.5 rounded-lg bg-white border border-slate-200/70 flex items-center justify-between"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-bold text-slate-400 uppercase"
                                    >
                                        Titik Koordinat
                                    </p>
                                    <p
                                        class="text-xs font-bold text-slate-800 mt-0.5"
                                    >
                                        {{
                                            Number(
                                                activeKelompok.latitude,
                                            ).toFixed(6)
                                        }},
                                        {{
                                            Number(
                                                activeKelompok.longitude,
                                            ).toFixed(6)
                                        }}
                                    </p>
                                </div>
                                <a
                                    :href="`https://www.google.com/maps?q=${activeKelompok.latitude},${activeKelompok.longitude}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded bg-slate-100 hover:bg-slate-200 text-slate-700"
                                >
                                    <span>Google Maps</span>
                                    <ExternalLink class="h-3 w-3" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABEL RINCIAN JUMLAH PENERIMA MANFAAT -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5"
                        >
                            <Users class="h-4 w-4 text-primary" />
                            <span>Rincian Jumlah Penerima Manfaat</span>
                        </h4>

                        <div
                            class="flex flex-wrap items-center gap-1.5 text-xs"
                        >
                            <span
                                class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold"
                            >
                                K: {{ activeKelompok.total_porsi_kecil ?? 0 }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200 font-bold"
                            >
                                B: {{ activeKelompok.total_porsi_besar ?? 0 }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 font-bold"
                            >
                                L: {{ activeKelompok.total_laki_laki }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded bg-pink-50 text-pink-700 border border-pink-200 font-bold"
                            >
                                P: {{ activeKelompok.total_perempuan }}
                            </span>
                            <span
                                class="px-2.5 py-0.5 rounded bg-primary text-white font-extrabold shadow-xs"
                            >
                                Total: {{ activeKelompok.total_penerima }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="border border-slate-200 rounded-xl overflow-hidden"
                    >
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase"
                                >
                                    <th class="py-2.5 px-4 w-12 text-center">
                                        No
                                    </th>
                                    <th class="py-2.5 px-4">Sub Kategori</th>
                                    <th class="py-2.5 px-4 text-center w-28">
                                        Jenis Porsi
                                    </th>
                                    <th class="py-2.5 px-4 text-center w-24">
                                        Laki-Laki
                                    </th>
                                    <th class="py-2.5 px-4 text-center w-24">
                                        Perempuan
                                    </th>
                                    <th class="py-2.5 px-4 text-center w-24">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(
                                        rincian, idx
                                    ) in sortRincianByKategori(
                                        activeKelompok.rincian,
                                        activeKelompok.kategori,
                                    )"
                                    :key="rincian.id || idx"
                                    class="hover:bg-slate-50/50"
                                >
                                    <td
                                        class="py-2.5 px-4 text-center text-slate-400 font-medium"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 font-semibold text-slate-800"
                                    >
                                        {{ rincian.sub_kategori }}
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <span
                                            v-if="
                                                rincian.jenis_porsi ===
                                                'Porsi Kecil'
                                            "
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                        >
                                            Porsi Kecil
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200"
                                        >
                                            Porsi Besar
                                        </span>
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-center font-medium text-sky-700"
                                    >
                                        {{ rincian.jumlah_laki_laki }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-center font-medium text-pink-700"
                                    >
                                        {{ rincian.jumlah_perempuan }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-center font-bold text-slate-900"
                                    >
                                        {{ rincian.total }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr
                                    class="bg-slate-50 font-bold border-t border-slate-200 text-xs"
                                >
                                    <td
                                        colspan="3"
                                        class="py-2.5 px-4 text-right uppercase tracking-wider text-slate-600"
                                    >
                                        Total Penerima Manfaat
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-center text-sky-700"
                                    >
                                        {{ activeKelompok.total_laki_laki }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-center text-pink-700"
                                    >
                                        {{ activeKelompok.total_perempuan }}
                                    </td>
                                    <td
                                        class="py-2.5 px-4 text-center text-primary font-extrabold"
                                    >
                                        {{ activeKelompok.total_penerima }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- DATA ALERGI MAKANAN DI MODAL -->
                <div
                    class="p-4 rounded-xl bg-rose-50/50 border border-rose-200/70 space-y-3"
                >
                    <div
                        class="flex items-center justify-between flex-wrap gap-2"
                    >
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-rose-900 flex items-center gap-1.5"
                        >
                            <HeartPulse class="h-4 w-4 text-rose-600" />
                            <span>Data Alergi Makanan & Kebutuhan Khusus</span>
                        </h4>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span
                                class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100/80 text-amber-900 border border-amber-200"
                            >
                                PK: {{ activeKelompok.alergi_porsi_kecil || 0 }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100/80 text-blue-900 border border-blue-200"
                            >
                                PB: {{ activeKelompok.alergi_porsi_besar || 0 }}
                            </span>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-black bg-white text-rose-700 border border-rose-200 shadow-2xs"
                            >
                                Total:
                                {{
                                    (activeKelompok.alergi_porsi_kecil || 0) +
                                    (activeKelompok.alergi_porsi_besar || 0)
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Tabel Klasifikasi Alergi per Satuan Jenis -->
                    <div
                        v-if="
                            activeKelompok.keterangan_alergi &&
                            activeKelompok.keterangan_alergi.length > 0
                        "
                        class="border border-rose-200/80 rounded-lg overflow-hidden bg-white mt-2"
                    >
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr
                                    class="bg-rose-100/50 text-rose-900 font-bold text-[10.5px] uppercase border-b border-rose-200/70"
                                >
                                    <th class="py-2 px-3 w-10 text-center">No</th>
                                    <th class="py-2 px-3">Jenis Alergi / Pantangan</th>
                                    <th class="py-2 px-3 text-center w-32">
                                        Porsi Kecil (PK)
                                    </th>
                                    <th class="py-2 px-3 text-center w-32">
                                        Porsi Besar (PB)
                                    </th>
                                    <th class="py-2 px-3 text-center w-28">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-100/60 text-slate-800">
                                <tr
                                    v-for="(al, idx) in activeKelompok.keterangan_alergi"
                                    :key="idx"
                                    class="hover:bg-rose-50/30"
                                >
                                    <td
                                        class="py-2 px-3 text-center text-slate-400 font-medium"
                                    >
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-2 px-3 font-semibold text-slate-900">
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            <span>{{
                                                typeof al === "string"
                                                    ? al
                                                    : al.jenis_alergi
                                            }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="py-2 px-3 text-center font-bold text-amber-800"
                                    >
                                        {{
                                            typeof al === "string"
                                                ? "-"
                                                : (al.porsi_kecil || 0)
                                        }}
                                    </td>
                                    <td
                                        class="py-2 px-3 text-center font-bold text-blue-800"
                                    >
                                        {{
                                            typeof al === "string"
                                                ? "-"
                                                : (al.porsi_besar || 0)
                                        }}
                                    </td>
                                    <td
                                        class="py-2 px-3 text-center font-extrabold text-slate-900"
                                    >
                                        {{
                                            typeof al === "string"
                                                ? "-"
                                                : (Number(al.porsi_kecil) || 0) +
                                                  (Number(al.porsi_besar) || 0)
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-xs text-slate-400 italic">
                        Tidak ada riwayat alergi makanan khusus yang dilaporkan pada kelompok ini.
                    </p>
                </div>

                <!-- Footer Modal -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100"
                >
                    <div class="text-[11px] text-slate-500 space-y-0.5">
                        <p>
                            <span class="font-semibold text-slate-700"
                                >Terdaftar:</span
                            >
                            {{ formatDateTimeWita(activeKelompok.created_at) }}
                        </p>
                        <p>
                            <span class="font-semibold text-slate-700"
                                >Terakhir Diperbarui:</span
                            >
                            {{ formatDateTimeWita(activeKelompok.updated_at) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="
                                route(
                                    'penerima-manfaat.edit',
                                    activeKelompok.uid || activeKelompok.id,
                                )
                            "
                            class="px-4 py-2 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-600 text-white shadow-xs"
                        >
                            Edit Data
                        </Link>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="closeDetail"
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ================= MODAL KONFIRMASI HAPUS ================= -->
        <Modal :show="isDeleteOpen" @close="closeDeleteModal" max-width="md">
            <div v-if="deletingKelompok" class="p-6 space-y-4">
                <div
                    class="h-12 w-12 rounded-full bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center mx-auto"
                >
                    <Trash2 class="h-6 w-6" />
                </div>
                <div class="text-center space-y-1">
                    <h3 class="text-base font-bold text-slate-900">
                        Hapus Kelompok Penerima Manfaat?
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Anda yakin ingin menghapus data kelompok
                        <strong class="text-slate-800"
                            >"{{ deletingKelompok.nama_kelompok }}"</strong
                        >? Seluruh rincian jumlah penerima manfaat terkait juga
                        akan dihapus permanen.
                    </p>
                </div>

                <div class="flex items-center justify-center gap-3 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="closeDeleteModal"
                        :disabled="isDeleting"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        className="bg-rose-600 hover:bg-rose-700 text-white"
                    >
                        {{ isDeleting ? "Menghapus..." : "Ya, Hapus Kelompok" }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
