<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import PeriodDateFilterBar from "@/Components/PeriodDateFilterBar.vue";
import {
    SlidersHorizontal,
    Plus,
    Search,
    Lock,
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    ArrowDownLeft,
    ArrowUpRight,
    TrendingUp,
    TrendingDown,
    Wallet,
    RotateCcw,
    Edit3,
    Trash2,
    Calendar,
    ChevronDown,
    Info,
    FileSpreadsheet,
    FileText,
} from "lucide-vue-next";

const props = defineProps({
    saldoAwal: {
        type: Object,
        default: null,
    },
    penyesuaianList: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    formatRupiah: {
        type: Function,
        default: (val) => {
            const num = Number(val) || 0;
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(num);
        },
    },
    formatTanggalIndo: {
        type: Function,
        default: (dateStr) => {
            if (!dateStr) return "-";
            try {
                const date = new Date(dateStr);
                return new Intl.DateTimeFormat("id-ID", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                }).format(date);
            } catch {
                return dateStr;
            }
        },
    },
});

// Helper Tanggal Hari Ini
const getTodayStr = () => {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

// Pilihan Jenis Penyesuaian sesuai spesifikasi user
const DAFTAR_JENIS_PENYESUAIAN = [
    "Bahan Baku",
    "Operasional",
    "Insentif Fasilitas",
    "Dana Bantuan Pemerintah",
    "Pengembalian Dana",
];

// ─── FILTER TANGGAL & URL SYNC ───────────────────────────────────────────────
function getInitialFilterState() {
    const today = getTodayStr();
    if (typeof window === "undefined") {
        return {
            mode: "hari_ini",
            start: today,
            end: today,
            periodeId: "",
            isAllTime: false,
            arah: "semua",
            jenis: "semua",
            search: "",
        };
    }

    const params = new URLSearchParams(window.location.search);
    const urlMode = params.get("mode");
    const urlPeriodeId = params.get("periode_id");
    const urlTanggalMulai = params.get("tanggal_mulai");
    const urlTanggalSelesai = params.get("tanggal_selesai");
    const urlIsAllTime = params.get("is_all_time") === "1" || params.get("is_all_time") === "true";
    const urlArah = params.get("arah");
    const urlJenis = params.get("jenis");
    const urlSearch = params.get("search") || params.get("cari");

    let resolvedMode = "hari_ini";
    let resolvedStart = today;
    let resolvedEnd = today;
    let resolvedPeriodeId = "";
    let resolvedIsAllTime = false;

    if (urlIsAllTime || urlMode === "semua" || urlMode === "all") {
        resolvedIsAllTime = true;
        resolvedMode = "semua";
    } else if (urlMode || urlTanggalMulai || urlTanggalSelesai || urlPeriodeId) {
        const m = (urlMode || "").toLowerCase();
        if (m === "periode" || m === "periodik" || urlPeriodeId) {
            resolvedMode = "periode";
            resolvedPeriodeId = urlPeriodeId || "";
            resolvedStart = urlTanggalMulai || "";
            resolvedEnd = urlTanggalSelesai || resolvedStart || "";

            if ((!resolvedStart || !resolvedEnd) && resolvedPeriodeId && props.periodes) {
                const foundP = props.periodes.find((p) => String(p.id) === String(resolvedPeriodeId));
                if (foundP && foundP.tanggal_mulai && foundP.tanggal_selesai) {
                    resolvedStart = String(foundP.tanggal_mulai).substring(0, 10);
                    resolvedEnd = String(foundP.tanggal_selesai).substring(0, 10);
                }
            }
        } else if (m === "rentang" || m === "range" || m === "custom") {
            resolvedMode = "rentang";
            resolvedStart = urlTanggalMulai || today;
            resolvedEnd = urlTanggalSelesai || resolvedStart || today;
        } else {
            resolvedMode = "hari_ini";
            resolvedStart = urlTanggalMulai || today;
            resolvedEnd = urlTanggalSelesai || resolvedStart || today;
        }
    }

    return {
        mode: resolvedMode,
        start: resolvedStart,
        end: resolvedEnd,
        periodeId: resolvedPeriodeId,
        isAllTime: resolvedIsAllTime,
        arah: urlArah || "semua",
        jenis: urlJenis || "semua",
        search: urlSearch || "",
    };
}

const initialFilters = getInitialFilterState();

// State Filter Waktu
const filterStartDate = ref(initialFilters.start);
const filterEndDate = ref(initialFilters.end);
const filterDateMode = ref(initialFilters.mode);
const filterPeriodeId = ref(initialFilters.periodeId);
const isFilterAllTime = ref(initialFilters.isAllTime);

// State Filter Lainnya
const arahFilter = ref(initialFilters.arah); // 'semua', 'masuk', 'keluar'
const jenisFilter = ref(initialFilters.jenis); // 'semua' | DAFTAR_JENIS_PENYESUAIAN
const searchQuery = ref(initialFilters.search);

let isInitialMount = true;

function syncFilterToUrl() {
    if (typeof window === "undefined") return;
    if (isInitialMount && !window.location.search) return;

    const params = new URLSearchParams();

    if (isFilterAllTime.value) {
        params.set("mode", "semua");
        params.set("is_all_time", "1");
    } else {
        const normMode =
            filterDateMode.value === "today" || filterDateMode.value === "hari_ini"
                ? "hari_ini"
                : filterDateMode.value;

        if (normMode) params.set("mode", normMode);

        if (normMode === "periode" && filterPeriodeId.value && filterPeriodeId.value !== "all") {
            params.set("periode_id", String(filterPeriodeId.value));
        }

        if (filterStartDate.value) params.set("tanggal_mulai", filterStartDate.value);
        if (filterEndDate.value) params.set("tanggal_selesai", filterEndDate.value);
    }

    if (arahFilter.value && arahFilter.value !== "semua") {
        params.set("arah", arahFilter.value);
    }

    if (jenisFilter.value && jenisFilter.value !== "semua") {
        params.set("jenis", jenisFilter.value);
    }

    if (searchQuery.value && searchQuery.value.trim() !== "") {
        params.set("search", searchQuery.value.trim());
    }

    const currentQuery = window.location.search.replace(/^\?/, "");
    const newQuery = params.toString();

    if (currentQuery === newQuery) return;

    const newUrl = `${window.location.pathname}${newQuery ? `?${newQuery}` : ""}`;
    window.history.replaceState({}, "", newUrl);
}

function handleDateFilterChange(payload) {
    if (!payload) return;
    isInitialMount = false;
    filterStartDate.value = payload.start || payload.startDate || "";
    filterEndDate.value = payload.end || payload.endDate || "";
    filterDateMode.value = payload.mode || "hari_ini";
    filterPeriodeId.value = payload.periodeId !== undefined && payload.periodeId !== null ? String(payload.periodeId) : "";
    isFilterAllTime.value = Boolean(payload.isAllTime);
    syncFilterToUrl();
}

watch(arahFilter, () => {
    isInitialMount = false;
    syncFilterToUrl();
});

watch(jenisFilter, () => {
    isInitialMount = false;
    syncFilterToUrl();
});

let searchDebounceTimer = null;
watch(searchQuery, () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        isInitialMount = false;
        syncFilterToUrl();
    }, 250);
});

function resetFilters() {
    isInitialMount = false;
    searchQuery.value = "";
    arahFilter.value = "semua";
    jenisFilter.value = "semua";
    filterStartDate.value = getTodayStr();
    filterEndDate.value = getTodayStr();
    filterDateMode.value = "hari_ini";
    filterPeriodeId.value = "";
    isFilterAllTime.value = false;
    syncFilterToUrl();
}

function handlePopState() {
    const s = getInitialFilterState();
    filterStartDate.value = s.start;
    filterEndDate.value = s.end;
    filterDateMode.value = s.mode;
    filterPeriodeId.value = s.periodeId;
    isFilterAllTime.value = s.isAllTime;
    arahFilter.value = s.arah;
    jenisFilter.value = s.jenis;
    searchQuery.value = s.search;
}

onMounted(() => {
    window.addEventListener("popstate", handlePopState);
});

onUnmounted(() => {
    window.removeEventListener("popstate", handlePopState);
});

// ─── STATE SALDO AWAL (DAPAT DIATUR, DIUBAH, ATAU DITARIK KEMBALI) ───────────
const showSaldoAwalConfirmModal = ref(false);
const isSubmittingSaldoAwal = ref(false);

const saldoAwalForm = ref({
    tanggal: getTodayStr(),
    nominal: "",
    nominalDisplay: "",
    keterangan: "Saldo Awal Kas / Bank Unit SPPG",
});

function onSaldoAwalNominalInput(e) {
    let raw = e.target.value.replace(/[^0-9]/g, "");
    if (!raw) {
        saldoAwalForm.value.nominal = "";
        saldoAwalForm.value.nominalDisplay = "";
        return;
    }
    const num = parseInt(raw, 10);
    saldoAwalForm.value.nominal = num;
    saldoAwalForm.value.nominalDisplay = "Rp " + num.toLocaleString("id-ID");
}

function openSaldoAwalConfirm() {
    if (!saldoAwalForm.value.nominal || Number(saldoAwalForm.value.nominal) < 0) {
        alert("Silakan masukkan nominal saldo awal yang valid.");
        return;
    }
    showSaldoAwalConfirmModal.value = true;
}

function submitSaldoAwal() {
    isSubmittingSaldoAwal.value = true;
    router.post(
        route("keuangan.saldo-awal.store"),
        {
            tanggal: saldoAwalForm.value.tanggal,
            nominal: saldoAwalForm.value.nominal,
            keterangan: saldoAwalForm.value.keterangan,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showSaldoAwalConfirmModal.value = false;
                isSubmittingSaldoAwal.value = false;
            },
            onError: () => {
                isSubmittingSaldoAwal.value = false;
            },
        }
    );
}

// Modal Ubah Saldo Awal
const showEditSaldoAwalModal = ref(false);
const isSubmittingEditSaldoAwal = ref(false);
const editSaldoAwalForm = ref({
    tanggal: "",
    nominal: "",
    nominalDisplay: "",
    keterangan: "",
});

function openEditSaldoAwal() {
    if (!props.saldoAwal) return;
    const nom = Number(props.saldoAwal.nominal) || 0;
    editSaldoAwalForm.value = {
        tanggal: props.saldoAwal.tanggal || getTodayStr(),
        nominal: nom,
        nominalDisplay: "Rp " + nom.toLocaleString("id-ID"),
        keterangan: props.saldoAwal.keterangan || "Saldo Awal Kas / Bank Unit SPPG",
    };
    showEditSaldoAwalModal.value = true;
}

function onEditSaldoAwalNominalInput(e) {
    let raw = e.target.value.replace(/[^0-9]/g, "");
    if (!raw) {
        editSaldoAwalForm.value.nominal = "";
        editSaldoAwalForm.value.nominalDisplay = "";
        return;
    }
    const num = parseInt(raw, 10);
    editSaldoAwalForm.value.nominal = num;
    editSaldoAwalForm.value.nominalDisplay = "Rp " + num.toLocaleString("id-ID");
}

function submitUpdateSaldoAwal() {
    if (editSaldoAwalForm.value.nominal === "" || Number(editSaldoAwalForm.value.nominal) < 0) {
        alert("Silakan masukkan nominal saldo awal yang valid.");
        return;
    }
    isSubmittingEditSaldoAwal.value = true;
    router.put(
        route("keuangan.saldo-awal.update"),
        {
            tanggal: editSaldoAwalForm.value.tanggal,
            nominal: editSaldoAwalForm.value.nominal,
            keterangan: editSaldoAwalForm.value.keterangan,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditSaldoAwalModal.value = false;
                isSubmittingEditSaldoAwal.value = false;
            },
            onError: () => {
                isSubmittingEditSaldoAwal.value = false;
            },
        }
    );
}

// Modal Tarik / Reset Saldo Awal
const showTarikSaldoAwalModal = ref(false);
const isSubmittingTarikSaldoAwal = ref(false);

function confirmTarikSaldoAwal() {
    showTarikSaldoAwalModal.value = true;
}

function executeTarikSaldoAwal() {
    isSubmittingTarikSaldoAwal.value = true;
    router.delete(route("keuangan.saldo-awal.destroy"), {
        preserveScroll: true,
        onSuccess: () => {
            showTarikSaldoAwalModal.value = false;
            isSubmittingTarikSaldoAwal.value = false;
        },
        onError: () => {
            isSubmittingTarikSaldoAwal.value = false;
        },
    });
}

// ─── STATE PENYESUAIAN KEUANGAN (MASUK & KELUAR) ──────────────────────────────
const showModalPenyesuaian = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSubmittingPenyesuaian = ref(false);

const formPenyesuaian = ref({
    tanggal: getTodayStr(),
    arah: "keluar", // 'masuk' atau 'keluar'
    jenis: "Bahan Baku",
    jumlah: "",
    jumlahDisplay: "",
    alasan: "",
});

function onJumlahInput(e) {
    let raw = e.target.value.replace(/[^0-9]/g, "");
    if (!raw) {
        formPenyesuaian.value.jumlah = "";
        formPenyesuaian.value.jumlahDisplay = "";
        return;
    }
    const num = parseInt(raw, 10);
    formPenyesuaian.value.jumlah = num;
    formPenyesuaian.value.jumlahDisplay = "Rp " + num.toLocaleString("id-ID");
}

function openTambahPenyesuaian() {
    isEditing.value = false;
    editingId.value = null;
    formPenyesuaian.value = {
        tanggal: getTodayStr(),
        arah: "keluar",
        jenis: "Bahan Baku",
        jumlah: "",
        jumlahDisplay: "",
        alasan: "",
    };
    showModalPenyesuaian.value = true;
}

function openEditPenyesuaian(item) {
    isEditing.value = true;
    editingId.value = item.id;
    const num = Number(item.jumlah) || 0;
    formPenyesuaian.value = {
        tanggal: item.tanggal || getTodayStr(),
        arah: item.arah || "keluar",
        jenis: item.jenis || "Bahan Baku",
        jumlah: num,
        jumlahDisplay: num ? "Rp " + num.toLocaleString("id-ID") : "",
        alasan: item.alasan || "",
    };
    showModalPenyesuaian.value = true;
}

function submitPenyesuaian() {
    if (!formPenyesuaian.value.tanggal) {
        alert("Tanggal wajib diisi.");
        return;
    }
    if (!formPenyesuaian.value.jumlah || Number(formPenyesuaian.value.jumlah) <= 0) {
        alert("Jumlah nominal wajib diisi dan lebih dari 0.");
        return;
    }
    if (!formPenyesuaian.value.alasan || !formPenyesuaian.value.alasan.trim()) {
        alert("Alasan penyesuaian wajib diisi.");
        return;
    }

    isSubmittingPenyesuaian.value = true;

    const payload = {
        tanggal: formPenyesuaian.value.tanggal,
        arah: formPenyesuaian.value.arah,
        jenis: formPenyesuaian.value.jenis,
        jumlah: formPenyesuaian.value.jumlah,
        alasan: formPenyesuaian.value.alasan.trim(),
    };

    if (isEditing.value && editingId.value) {
        router.put(route("keuangan.penyesuaian.update", editingId.value), payload, {
            preserveScroll: true,
            onSuccess: () => {
                showModalPenyesuaian.value = false;
                isSubmittingPenyesuaian.value = false;
            },
            onError: () => {
                isSubmittingPenyesuaian.value = false;
            },
        });
    } else {
        router.post(route("keuangan.penyesuaian.store"), payload, {
            preserveScroll: true,
            onSuccess: () => {
                showModalPenyesuaian.value = false;
                isSubmittingPenyesuaian.value = false;
            },
            onError: () => {
                isSubmittingPenyesuaian.value = false;
            },
        });
    }
}

// ─── MODAL HAPUS PENYESUAIAN ─────────────────────────────────────────────────
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(item) {
    itemToDelete.value = item;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!itemToDelete.value) return;
    isDeleting.value = true;
    router.delete(route("keuangan.penyesuaian.destroy", itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
}

// ─── COMPUTED DATA FILTERED & STATS ──────────────────────────────────────────
// Saring berdasarkan tanggal/periode
const dateFilteredList = computed(() => {
    return (props.penyesuaianList || []).filter((item) => {
        if (!isFilterAllTime.value) {
            const tgl = String(item.tanggal || "").substring(0, 10);
            if (tgl) {
                if (filterStartDate.value && tgl < filterStartDate.value) return false;
                if (filterEndDate.value && tgl > filterEndDate.value) return false;
            }
        }
        return true;
    });
});

// Saring akhir (Tanggal + Arah + Jenis + Search)
const finalFilteredList = computed(() => {
    return dateFilteredList.value.filter((item) => {
        if (arahFilter.value !== "semua" && item.arah !== arahFilter.value) {
            return false;
        }

        if (jenisFilter.value !== "semua" && item.jenis !== jenisFilter.value) {
            return false;
        }

        if (searchQuery.value && searchQuery.value.trim() !== "") {
            const q = searchQuery.value.toLowerCase().trim();
            const no = (item.nomor_penyesuaian || "").toLowerCase();
            const jenis = (item.jenis || "").toLowerCase();
            const alasan = (item.alasan || "").toLowerCase();
            const user = (item.user_name || "").toLowerCase();
            return no.includes(q) || jenis.includes(q) || alasan.includes(q) || user.includes(q);
        }

        return true;
    });
});

// KPI Summary
const nominalSaldoAwal = computed(() => {
    return props.saldoAwal ? Number(props.saldoAwal.nominal) || 0 : 0;
});

const totalMasuk = computed(() => {
    const list = dateFilteredList.value.filter((it) => it.arah === "masuk");
    return {
        nominal: list.reduce((sum, it) => sum + (Number(it.jumlah) || 0), 0),
        count: list.length,
    };
});

const totalKeluar = computed(() => {
    const list = dateFilteredList.value.filter((it) => it.arah === "keluar");
    return {
        nominal: list.reduce((sum, it) => sum + (Number(it.jumlah) || 0), 0),
        count: list.length,
    };
});

const netPenyesuaian = computed(() => {
    return totalMasuk.value.nominal - totalKeluar.value.nominal;
});

const totalSaldoBerjalan = computed(() => {
    return nominalSaldoAwal.value + netPenyesuaian.value;
});
</script>

<template>
    <div class="space-y-6">
        <!-- ─── BANNER INFO: SALDO AWAL KAS / BANK TERPUSAT ─── -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
            <div class="flex items-center gap-3.5">
                <div class="h-11 w-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-200 shadow-2xs">
                    <Wallet class="h-5 w-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs sm:text-sm font-bold text-slate-800">Saldo Awal Kas / Bank:</span>
                        <span class="text-base sm:text-lg font-black text-emerald-700">{{ formatRupiah(props.saldoAwal?.nominal) }}</span>
                        <span v-if="props.saldoAwal" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <CheckCircle2 class="h-3 w-3 text-emerald-600" /> Ditetapkan
                        </span>
                        <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            Belum Ditetapkan
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Fitur kelola Saldo Awal kini dikelola terpusat di menu <strong class="text-slate-700">Kas & Bank &gt; Saldo Awal</strong>. Halaman ini khusus untuk penyesuaian koreksi saldo.
                    </p>
                </div>
            </div>
            <Link
                :href="route('kas-bank.saldo-awal')"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all shadow-2xs cursor-pointer shrink-0"
            >
                <span>Kelola di Kas & Bank</span>
                <ArrowUpRight class="h-4 w-4 text-slate-500" />
            </Link>
        </div>
        <!-- ─── SECTION 2: KPI SUMMARY CARDS ───────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Saldo Awal -->
            <div class="p-5 rounded-2xl border border-slate-200 bg-white shadow-2xs select-none">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Saldo Awal</span>
                    <div class="h-8 w-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                        <Lock class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-slate-900">
                        {{ formatRupiah(nominalSaldoAwal) }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">
                        {{ props.saldoAwal ? 'Telah Ditetapkan' : 'Belum Ditetapkan' }}
                    </span>
                </div>
            </div>

            <!-- Card 2: Total Penyesuaian Masuk (+) -->
            <div class="p-5 rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-white to-emerald-50/30 shadow-2xs select-none">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Penyesuaian Masuk (+)</span>
                    <div class="h-8 w-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <TrendingUp class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-emerald-700">
                        {{ formatRupiah(totalMasuk.nominal) }}
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">
                        {{ totalMasuk.count }} Catatan Uang Masuk
                    </span>
                </div>
            </div>

            <!-- Card 3: Total Penyesuaian Keluar (-) -->
            <div class="p-5 rounded-2xl border border-rose-200/80 bg-gradient-to-br from-white to-rose-50/30 shadow-2xs select-none">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-800">Penyesuaian Keluar (-)</span>
                    <div class="h-8 w-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                        <TrendingDown class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-rose-700">
                        {{ formatRupiah(totalKeluar.nominal) }}
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">
                        {{ totalKeluar.count }} Catatan Uang Keluar
                    </span>
                </div>
            </div>

            <!-- Card 4: Saldo Berjalan (Kas + Penyesuaian Net) -->
            <div class="p-5 rounded-2xl border border-blue-200/80 bg-gradient-to-br from-white to-blue-50/30 shadow-2xs select-none">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Saldo Akhir Penyesuaian</span>
                    <div class="h-8 w-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                        <Wallet class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-xl font-black text-blue-900">
                        {{ formatRupiah(totalSaldoBerjalan) }}
                    </div>
                    <span class="text-[11px] font-semibold" :class="netPenyesuaian >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        Net: {{ netPenyesuaian >= 0 ? '+' : '' }}{{ formatRupiah(netPenyesuaian) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ─── SECTION 3: FILTER WAKTU (PERIODDATEFILTERBAR & URL SYNC) ────────── -->
        <PeriodDateFilterBar
            v-model:startDate="filterStartDate"
            v-model:endDate="filterEndDate"
            v-model:mode="filterDateMode"
            v-model:periodeId="filterPeriodeId"
            v-model:isAllTime="isFilterAllTime"
            :periodes="periodes"
            @change="handleDateFilterChange"
        />

        <!-- ─── SECTION 4: TOOLBAR (FILTER ARAH, JENIS, SEARCH, TOMBOL TAMBAH) ─── -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <!-- Filter Arah & Jenis -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Filter Arah (Semua, Masuk, Keluar) -->
                <div class="inline-flex items-center h-10 rounded-xl bg-slate-100 p-1 border border-slate-200/80 shadow-2xs">
                    <button
                        type="button"
                        @click="arahFilter = 'semua'"
                        class="h-full px-3 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center justify-center select-none"
                        :class="[
                            arahFilter === 'semua'
                                ? 'bg-white text-slate-900 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Semua Arah
                    </button>
                    <button
                        type="button"
                        @click="arahFilter = 'masuk'"
                        class="h-full px-3 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1 justify-center select-none"
                        :class="[
                            arahFilter === 'masuk'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'text-slate-600 hover:text-emerald-700'
                        ]"
                    >
                        <ArrowDownLeft class="h-3 w-3" />
                        <span>Uang Masuk</span>
                    </button>
                    <button
                        type="button"
                        @click="arahFilter = 'keluar'"
                        class="h-full px-3 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1 justify-center select-none"
                        :class="[
                            arahFilter === 'keluar'
                                ? 'bg-rose-600 text-white shadow-xs'
                                : 'text-slate-600 hover:text-rose-700'
                        ]"
                    >
                        <ArrowUpRight class="h-3 w-3" />
                        <span>Uang Keluar</span>
                    </button>
                </div>

                <!-- Filter Jenis Pengeluaran / Penyesuaian -->
                <div class="relative">
                    <select
                        v-model="jenisFilter"
                        class="h-10 pl-3 pr-8 rounded-xl bg-white border border-slate-200/90 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer appearance-none shadow-2xs"
                    >
                        <option value="semua">Semua Jenis</option>
                        <option v-for="j in DAFTAR_JENIS_PENYESUAIAN" :key="j" :value="j">
                            {{ j }}
                        </option>
                    </select>
                    <ChevronDown class="h-3.5 w-3.5 text-slate-500 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                </div>

                <!-- Tombol Reset Filter -->
                <button
                    v-if="arahFilter !== 'semua' || jenisFilter !== 'semua' || searchQuery"
                    type="button"
                    @click="resetFilters"
                    class="h-10 px-3 text-xs font-bold text-slate-500 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors flex items-center gap-1 cursor-pointer"
                    title="Reset Filter"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    <span>Reset</span>
                </button>
            </div>

            <!-- Search Bar & Tombol Tambah Penyesuaian -->
            <div class="flex items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <Search class="h-3.5 w-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari no. bukti, jenis, alasan..."
                        class="h-10 w-full pl-9 pr-3 rounded-xl bg-white border border-slate-200/90 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none shadow-2xs"
                    />
                </div>

                <button
                    type="button"
                    @click="openTambahPenyesuaian"
                    class="h-10 px-4 rounded-xl bg-primary hover:bg-primary/90 text-white font-extrabold text-xs flex items-center gap-2 cursor-pointer shadow-sm hover:shadow transition-all shrink-0"
                >
                    <Plus class="h-4 w-4" />
                    <span>Tambah Penyesuaian</span>
                </button>
            </div>
        </div>

        <!-- ─── SECTION 5: TABEL DAFTAR PENYESUAIAN KEUANGAN ────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <SlidersHorizontal class="h-4 w-4 text-primary" />
                        Riwayat Penyesuaian Keuangan
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                            {{ finalFilteredList.length }} Data
                        </span>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Menampilkan penyesuaian uang masuk & keluar sesuai filter aktif
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-600 select-none">
                            <th class="py-3 px-3.5 text-center w-12">No</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Tanggal</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">No. Bukti / Penyesuaian</th>
                            <th class="py-3 px-3.5 text-center whitespace-nowrap">Arah</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Jenis Penyesuaian</th>
                            <th class="py-3 px-3.5 text-right whitespace-nowrap">Jumlah (Rp)</th>
                            <th class="py-3 px-3.5 min-w-[200px]">Alasan Penyesuaian</th>
                            <th class="py-3 px-3.5 whitespace-nowrap">Petugas</th>
                            <th class="py-3 px-3.5 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <tr
                            v-for="(item, idx) in finalFilteredList"
                            :key="item.id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <td class="py-3 px-3.5 text-center text-slate-400 font-bold">
                                {{ idx + 1 }}
                            </td>
                            <td class="py-3 px-3.5 whitespace-nowrap font-semibold text-slate-800">
                                {{ formatTanggalIndo(item.tanggal) }}
                            </td>
                            <td class="py-3 px-3.5 whitespace-nowrap font-mono text-[11px] font-bold text-slate-600">
                                {{ item.nomor_penyesuaian }}
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <span
                                    v-if="item.arah === 'masuk'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                >
                                    <ArrowDownLeft class="h-3 w-3 text-emerald-600" />
                                    Uang Masuk
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-800 border border-rose-200"
                                >
                                    <ArrowUpRight class="h-3 w-3 text-rose-600" />
                                    Uang Keluar
                                </span>
                            </td>
                            <td class="py-3 px-3.5 whitespace-nowrap font-bold text-slate-800">
                                {{ item.jenis }}
                            </td>
                            <td
                                class="py-3 px-3.5 text-right whitespace-nowrap font-extrabold text-sm tracking-tight"
                                :class="item.arah === 'masuk' ? 'text-emerald-700' : 'text-rose-700'"
                            >
                                {{ item.arah === 'masuk' ? '+' : '-' }}{{ formatRupiah(item.jumlah) }}
                            </td>
                            <td class="py-3 px-3.5 text-slate-600 leading-relaxed">
                                {{ item.alasan }}
                            </td>
                            <td class="py-3 px-3.5 whitespace-nowrap text-slate-500 text-[11px]">
                                {{ item.user_name || 'Admin' }}
                            </td>
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="openEditPenyesuaian(item)"
                                        class="h-8 w-8 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Edit Penyesuaian"
                                    >
                                        <Edit3 class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmDelete(item)"
                                        class="h-8 w-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Hapus Penyesuaian"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="finalFilteredList.length === 0">
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <SlidersHorizontal class="h-6 w-6" />
                                </div>
                                <p class="text-xs font-semibold text-slate-600">Belum ada data penyesuaian keuangan</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Klik tombol "Tambah Penyesuaian" untuk mencatat uang masuk atau uang keluar.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── MODAL 1: TAMBAH / EDIT PENYESUAIAN KEUANGAN ─────────────────────── -->
        <Modal :show="showModalPenyesuaian" @close="showModalPenyesuaian = false" max-width="md">
            <div class="p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <SlidersHorizontal class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">
                                {{ isEditing ? 'Edit Penyesuaian Keuangan' : 'Tambah Penyesuaian Keuangan' }}
                            </h3>
                            <p class="text-[11px] text-slate-400">
                                Catat penyesuaian uang masuk atau uang keluar kas/bank SPPG
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitPenyesuaian" class="space-y-3.5">
                    <!-- 1. Tanggal -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="formPenyesuaian.tanggal"
                            type="date"
                            required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                        />
                    </div>

                    <!-- 2. Arah: Uang Masuk atau Uang Keluar -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Arah Penyesuaian <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="formPenyesuaian.arah = 'masuk'"
                                class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer select-none"
                                :class="[
                                    formPenyesuaian.arah === 'masuk'
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-300'
                                ]"
                            >
                                <ArrowDownLeft class="h-4 w-4" />
                                <span>Uang Masuk (+)</span>
                            </button>

                            <button
                                type="button"
                                @click="formPenyesuaian.arah = 'keluar'"
                                class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer select-none"
                                :class="[
                                    formPenyesuaian.arah === 'keluar'
                                        ? 'bg-rose-600 text-white border-rose-600 shadow-sm'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-rose-300'
                                ]"
                            >
                                <ArrowUpRight class="h-4 w-4" />
                                <span>Uang Keluar (-)</span>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Jenis Penyesuaian (Bahan Baku, Operasional, Insentif Fasilitas, Dana Bantuan Pemerintah, Pengembalian Dana) -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Jenis {{ formPenyesuaian.arah === 'keluar' ? 'Pengeluaran' : 'Penyesuaian' }} <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="formPenyesuaian.jenis"
                            required
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all cursor-pointer"
                        >
                            <option v-for="j in DAFTAR_JENIS_PENYESUAIAN" :key="j" :value="j">
                                {{ j }}
                            </option>
                        </select>
                    </div>

                    <!-- 4. Jumlah (Rp) -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Jumlah Nominal (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            :value="formPenyesuaian.jumlahDisplay"
                            @input="onJumlahInput"
                            type="text"
                            required
                            placeholder="Rp 0"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-black text-slate-900 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                        />
                    </div>

                    <!-- 5. Alasan -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Alasan Penyesuaian <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="formPenyesuaian.alasan"
                            rows="3"
                            required
                            placeholder="Tuliskan alasan atau dasar pencatatan penyesuaian keuangan ini..."
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showModalPenyesuaian = false"
                            class="px-4 py-2 text-xs font-bold rounded-xl text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmittingPenyesuaian"
                            class="px-4 py-2 text-xs font-bold rounded-xl bg-primary hover:bg-primary/90 text-white transition-all cursor-pointer shadow-sm disabled:opacity-50"
                        >
                            {{ isSubmittingPenyesuaian ? 'Menyimpan...' : (isEditing ? 'Perbarui Penyesuaian' : 'Simpan Penyesuaian') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ─── MODAL 3: KONFIRMASI HAPUS PENYESUAIAN ───────────────────────────── -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-5 space-y-4 text-xs text-center">
                <div class="h-12 w-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                    <Trash2 class="h-6 w-6" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Hapus Penyesuaian?</h3>
                    <p class="text-slate-500 mt-1">
                        Apakah Anda yakin ingin menghapus catatan penyesuaian
                        <span class="font-bold text-slate-800">{{ itemToDelete?.nomor_penyesuaian }}</span>
                        ({{ itemToDelete?.jenis }} - {{ formatRupiah(itemToDelete?.jumlah) }})?
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        :disabled="isDeleting"
                        class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl cursor-pointer disabled:opacity-50"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
