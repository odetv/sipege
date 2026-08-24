<script setup>
import { ref } from "vue";
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

// Detail Modal state
const isDetailOpen = ref(false);
const activeKelompok = ref(null);

// Delete state
const isDeleteOpen = ref(false);
const deletingKelompok = ref(null);
const isDeleting = ref(false);

function applyFilters() {
    router.get(
        route("penerima-manfaat.index"),
        {
            search: searchQuery.value || undefined,
            kategori: selectedKategori.value || undefined,
            jenis_kepemilikan: selectedKepemilikan.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetFilters() {
    searchQuery.value = "";
    selectedKategori.value = "";
    selectedKepemilikan.value = "";
    applyFilters();
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

            <!-- ================= 4 METRIC STAT CARDS (ALL UNIFORM BLUE THEME) ================= -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Kelompok -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500">
                                Total Kelompok
                            </p>
                            <h3
                                class="text-2xl font-bold text-slate-900 mt-1 truncate"
                            >
                                {{
                                    stats.total_kelompok.toLocaleString("id-ID")
                                }}
                            </h3>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Satuan Penerima Manfaat
                            </p>
                        </div>
                        <div
                            class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <School class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Rincian Porsi -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500">
                                Rincian Porsi
                            </p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-xl font-bold text-amber-700">
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
                                <span class="text-xl font-bold text-blue-700">
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
                            class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <Utensils class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Rincian Gender -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500">
                                Rincian Gender
                            </p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-xl font-bold text-sky-700">
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
                                <span class="text-xl font-bold text-pink-700">
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
                            class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <User class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Penerima -->
                <Card
                    className="bg-white border-slate-200/80 shadow-xs hover:shadow-md transition-shadow"
                >
                    <CardContent
                        className="p-5 flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-500">
                                Total Penerima Manfaat
                            </p>
                            <h3
                                class="text-2xl font-bold text-primary mt-1 truncate"
                            >
                                {{
                                    stats.total_penerima.toLocaleString("id-ID")
                                }}
                            </h3>
                            <p
                                class="text-[11px] text-slate-400 mt-0.5 truncate"
                            >
                                Porsi Penerima Manfaat
                            </p>
                        </div>
                        <div
                            class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-2xs"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </CardContent>
                </Card>
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
                    <!-- ================= FILTER & SEARCH BAR (3 KOLOM HORIZONTAL DI DESKTOP) ================= -->
                    <div
                        class="bg-slate-50/80 p-4 rounded-xl border border-slate-200/80"
                    >
                        <div
                            class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center"
                        >
                            <!-- Kolom 1: Pencarian -->
                            <div class="relative w-full">
                                <div
                                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                                >
                                    <Search class="h-4 w-4 text-slate-400" />
                                </div>
                                <input
                                    v-model="searchQuery"
                                    @keyup.enter="applyFilters"
                                    type="text"
                                    placeholder="Cari Kelompok..."
                                    class="block w-full h-10 rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all shadow-2xs"
                                />
                            </div>

                            <!-- Kolom 2: Filter Kategori -->
                            <div class="w-full">
                                <select
                                    v-model="selectedKategori"
                                    @change="applyFilters"
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

                            <!-- Kolom 3: Filter Status + Tombol Filter & Reset -->
                            <div class="flex items-center gap-2 w-full">
                                <select
                                    v-model="selectedKepemilikan"
                                    @change="applyFilters"
                                    class="flex-1 min-w-0 h-10 px-3 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-slate-700 shadow-2xs cursor-pointer"
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

                                <button
                                    type="button"
                                    @click="applyFilters"
                                    class="h-10 px-4 text-xs font-semibold rounded-lg bg-primary hover:bg-primary/90 text-white flex items-center justify-center gap-1.5 transition-colors cursor-pointer shrink-0 shadow-2xs"
                                >
                                    <Filter class="h-3.5 w-3.5" />
                                    <span>Filter</span>
                                </button>

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
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-12"
                                        >
                                            No
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[240px]"
                                        >
                                            Kelompok
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[200px]"
                                        >
                                            Kontak
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[220px]"
                                        >
                                            Alamat
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-32"
                                        >
                                            Gender (L/P)
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-36"
                                        >
                                            Porsi (PK/PB)
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-4 text-center text-[11px] font-bold text-slate-600 uppercase tracking-wider w-24"
                                        >
                                            Total
                                        </th>
                                        <th
                                            scope="col"
                                            class="py-4 px-5 text-left text-[11px] font-bold text-slate-600 uppercase tracking-wider min-w-[240px]"
                                        >
                                            Waktu Daftar & Perbaharui
                                        </th>
                                        <th
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
                                        v-for="(item, index) in kelompokList"
                                        :key="item.id"
                                        class="hover:bg-slate-50/60 transition-colors"
                                    >
                                        <td
                                            class="py-4 px-4 text-center font-medium text-slate-400"
                                        >
                                            {{ index + 1 }}
                                        </td>

                                        <!-- Kelompok -->
                                        <td class="py-4 px-5">
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
                                        <td class="py-4 px-5">
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
                                        <td class="py-4 px-5">
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
                                        <td class="py-4 px-4 text-center">
                                            <div
                                                class="inline-flex items-center gap-1 text-xs"
                                            >
                                                <span
                                                    class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 font-semibold"
                                                    title="Laki-Laki"
                                                >
                                                    L:
                                                    {{ item.total_laki_laki }}
                                                </span>
                                                <span
                                                    class="px-2 py-0.5 rounded bg-pink-50 text-pink-700 border border-pink-200 font-semibold"
                                                    title="Perempuan"
                                                >
                                                    P:
                                                    {{ item.total_perempuan }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Porsi (Kecil / Besar) -->
                                        <td class="py-4 px-4 text-center">
                                            <div
                                                class="inline-flex items-center gap-1 text-xs"
                                            >
                                                <span
                                                    class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-semibold"
                                                    title="Porsi Kecil"
                                                >
                                                    PK:
                                                    {{
                                                        item.total_porsi_kecil ??
                                                        0
                                                    }}
                                                </span>
                                                <span
                                                    class="px-2 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200 font-semibold"
                                                    title="Porsi Besar"
                                                >
                                                    PB:
                                                    {{
                                                        item.total_porsi_besar ??
                                                        0
                                                    }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Total Penerima -->
                                        <td class="py-4 px-4 text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20"
                                            >
                                                {{ item.total_penerima }}
                                            </span>
                                        </td>

                                        <!-- Terdaftar Pada & Terakhir Diperbaharui -->
                                        <td class="py-4 px-5">
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
                                        <td class="py-4 px-4 text-center">
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
                                    <tr v-if="kelompokList.length === 0">
                                        <td
                                            colspan="9"
                                            class="py-16 px-6 text-center"
                                        >
                                            <div
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
                            <div
                                class="p-2.5 rounded-lg bg-white border border-slate-200/70"
                            >
                                <p
                                    class="text-[11px] font-bold text-slate-400 uppercase"
                                >
                                    Kepala Satuan
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
                                    PIC
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
