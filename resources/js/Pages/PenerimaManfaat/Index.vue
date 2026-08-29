<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import {
    Users,
    Plus,
    AlertCircle,
    ChevronRight,
    Smile,
    BookOpen,
    GraduationCap,
    School,
    UserCheck,
    Briefcase,
    Baby,
    Heart,
    HeartHandshake,
} from "lucide-vue-next";

// Partials
import PenerimaManfaatStats from "@/Pages/PenerimaManfaat/Partials/PenerimaManfaatStats.vue";
import PenerimaManfaatClassification from "@/Pages/PenerimaManfaat/Partials/PenerimaManfaatClassification.vue";
import PenerimaManfaatFilterBar from "@/Pages/PenerimaManfaat/Partials/PenerimaManfaatFilterBar.vue";
import PenerimaManfaatTable from "@/Pages/PenerimaManfaat/Partials/PenerimaManfaatTable.vue";
import PenerimaManfaatDetailModal from "@/Pages/PenerimaManfaat/Partials/PenerimaManfaatDetailModal.vue";
import PenerimaManfaatDeleteModal from "@/Pages/PenerimaManfaat/Partials/PenerimaManfaatDeleteModal.vue";

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
});

// Search & Filter state
const searchQuery = ref("");
const selectedKategori = ref("");
const selectedKepemilikan = ref("");

// Perhitungan Total Sekolah & Posyandu
const totalSekolahCount = computed(() => {
    const list = Array.isArray(props.kelompokList) ? props.kelompokList : [];
    return list.filter((item) => item.kategori !== "Posyandu").length;
});

const totalPosyanduCount = computed(() => {
    const list = Array.isArray(props.kelompokList) ? props.kelompokList : [];
    return list.filter((item) => item.kategori === "Posyandu").length;
});

// Client-side realtime search & filter
const filteredKelompokList = computed(() => {
    const list = Array.isArray(props.kelompokList) ? props.kelompokList : [];

    return list.filter((item) => {
        // 1. Filter Kategori
        if (
            selectedKategori.value &&
            item.kategori !== selectedKategori.value
        ) {
            return false;
        }

        // 2. Filter Jenis Kepemilikan
        if (
            selectedKepemilikan.value &&
            item.jenis_kepemilikan !== selectedKepemilikan.value
        ) {
            return false;
        }

        // 3. Filter Search Query
        if (searchQuery.value && searchQuery.value.trim() !== "") {
            const query = searchQuery.value.toLowerCase().trim();
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
                          typeof a === "string"
                              ? a.toLowerCase()
                              : (a.jenis_alergi || "").toLowerCase(),
                      )
                      .join(" ")
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
            <PenerimaManfaatStats
                :stats="stats"
                :kelompok-list="kelompokList"
                :total-sekolah-count="totalSekolahCount"
                :total-posyandu-count="totalPosyanduCount"
                :category-stats="categoryStats"
            />

            <!-- ================= 10 KLASIFIKASI PENERIMA MANFAAT ================= -->
            <PenerimaManfaatClassification
                :category-groups="categoryGroups"
            />

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
                    <PenerimaManfaatFilterBar
                        v-model:search-query="searchQuery"
                        v-model:selected-kategori="selectedKategori"
                        v-model:selected-kepemilikan="selectedKepemilikan"
                        :visible-columns="visibleColumns"
                        :column-definitions="COLUMN_DEFINITIONS"
                        :visible-column-count="visibleColumnCount"
                        @reset-filters="resetFilters"
                        @toggle-all-columns="toggleAllColumns"
                        @reset-columns="resetColumns"
                    />

                    <!-- ================= TABLE DATA KELOMPOK ================= -->
                    <PenerimaManfaatTable
                        :filtered-kelompok-list="filteredKelompokList"
                        :visible-columns="visibleColumns"
                        :visible-column-count="visibleColumnCount"
                        @open-detail="openDetail"
                        @confirm-delete="confirmDelete"
                        @reset-filters="resetFilters"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- ================= MODAL DETAIL KELOMPOK ================= -->
        <PenerimaManfaatDetailModal
            :is-open="isDetailOpen"
            :active-kelompok="activeKelompok"
            @close="closeDetail"
        />

        <!-- ================= MODAL KONFIRMASI HAPUS ================= -->
        <PenerimaManfaatDeleteModal
            :is-open="isDeleteOpen"
            :deleting-kelompok="deletingKelompok"
            :is-deleting="isDeleting"
            @close="closeDeleteModal"
            @confirm="executeDelete"
        />
    </AppLayout>
</template>
