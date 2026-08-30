<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    Activity,
    Utensils,
    ChefHat,
    Receipt,
    CheckCircle2,
    Clock,
    AlertCircle,
    ArrowRight,
    Sparkles,
    Calendar,
    Users,
    ChevronRight,
    TrendingUp,
    ShieldCheck,
    Truck,
    Edit3,
    FileText,
    Check,
    Flame,
} from "lucide-vue-next";

const props = defineProps({
    workOrders: {
        type: Array,
        default: () => [],
    },
});

// Format ISO string date today 'YYYY-MM-DD'
function getTodayDateString() {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const todayStr = getTodayDateString();
const selectedWoId = ref(null);

// Automatically pick today's WO or closest active WO on mount
function initDefaultWo() {
    if (!props.workOrders || props.workOrders.length === 0) return;
    
    // 1. Look for WO matching today's date
    const todayWo = props.workOrders.find(w => w.tanggal === todayStr);
    if (todayWo) {
        selectedWoId.value = todayWo.id;
        return;
    }

    // 2. Look for upcoming WO (tanggal >= today)
    const upcomingWo = [...props.workOrders]
        .filter(w => w.tanggal && w.tanggal >= todayStr)
        .sort((a, b) => a.tanggal.localeCompare(b.tanggal))[0];
    if (upcomingWo) {
        selectedWoId.value = upcomingWo.id;
        return;
    }

    // 3. Fallback to latest WO
    selectedWoId.value = props.workOrders[0].id;
}

onMounted(() => {
    initDefaultWo();
});

watch(() => props.workOrders, () => {
    if (!selectedWoId.value) {
        initDefaultWo();
    }
}, { immediate: true });

const activeWorkOrder = computed(() => {
    if (!props.workOrders || props.workOrders.length === 0) return null;
    if (selectedWoId.value) {
        const found = props.workOrders.find((w) => w.id === selectedWoId.value);
        if (found) return found;
    }
    return props.workOrders[0];
});

// Check whether the active WO is for today, upcoming, or past
const dateStatus = computed(() => {
    if (!activeWorkOrder.value || !activeWorkOrder.value.tanggal) return null;
    const tgl = activeWorkOrder.value.tanggal;
    if (tgl === todayStr) {
        return { label: "Menu Hari Ini", type: "today", badgeClass: "bg-emerald-50 text-emerald-700 border-emerald-300" };
    } else if (tgl > todayStr) {
        return { label: "Menu Mendatang", type: "upcoming", badgeClass: "bg-blue-50 text-blue-700 border-blue-300" };
    } else {
        return { label: "Riwayat Distribusi", type: "past", badgeClass: "bg-slate-100 text-slate-600 border-slate-300" };
    }
});

// Calculate current step (1 to 5) and progress percentage
const trackingState = computed(() => {
    const wo = activeWorkOrder.value;
    if (!wo) {
        return {
            currentStep: 0,
            percent: 0,
            statusLabel: "Belum Ada Rencana",
            statusColor: "slate",
            isRejected: false,
        };
    }

    const s = (wo.status || "").toLowerCase();
    const isRejected = s.includes("ditolak");
    const stepNum = Number(wo.current_step) || (wo.items_count > 0 ? 2 : 1);

    if (s === "siap produksi" || s === "terverifikasi" || s === "disetujui") {
        return {
            currentStep: 5,
            percent: 100,
            statusLabel: "Siap Produksi & Distribusi",
            statusColor: "emerald",
            isRejected: false,
        };
    }

    if (s === "diajukan ke keuangan") {
        return {
            currentStep: 4,
            percent: 75,
            statusLabel: "Dalam Verifikasi Keuangan",
            statusColor: "blue",
            isRejected: false,
        };
    }

    if (isRejected) {
        return {
            currentStep: 3,
            percent: 50,
            statusLabel: "Perlu Perbaikan (Ditolak)",
            statusColor: "rose",
            isRejected: true,
        };
    }

    // Status: Draft (Step 1, 2, or 3)
    if (stepNum === 1) {
        return {
            currentStep: 1,
            percent: 20,
            statusLabel: "Draft: Perencanaan Sasaran",
            statusColor: "amber",
            isRejected: false,
        };
    }

    if (stepNum === 2) {
        return {
            currentStep: 2,
            percent: 40,
            statusLabel: "Draft: Formulasi Gizi",
            statusColor: "amber",
            isRejected: false,
        };
    }

    return {
        currentStep: 3,
        percent: 60,
        statusLabel: "Draft: Siap Diajukan",
        statusColor: "amber",
        isRejected: false,
    };
});

const steps = computed(() => {
    const { currentStep, isRejected } = trackingState.value;
    const wo = activeWorkOrder.value;
    const stepNum = Number(wo?.current_step) || (wo?.items_count > 0 ? 2 : 1);

    return [
        {
            id: 1,
            title: "Perencanaan Sasaran",
            subtitle: "Kuantitas PK & PB",
            desc: wo ? `${Number(wo.total_porsi || 0).toLocaleString("id-ID")} PM (${wo.porsi_pk || 0} PK / ${wo.porsi_pb || 0} PB)` : "Analisa data sasaran",
            icon: Users,
            isCompleted: currentStep >= 1,
            isActive: currentStep === 1,
            isWarning: false,
        },
        {
            id: 2,
            title: "Formulasi Gizi TKPI",
            subtitle: "Standar AKG & Resep",
            desc: (wo?.items_count > 0 || currentStep >= 2) ? `${wo?.items_count || 0} Bahan Baku Pangan` : "Belum Ada Formulasi Resep",
            isCompleted: currentStep >= 2 && (wo?.items_count > 0 || stepNum >= 2),
            isActive: currentStep === 2 || (currentStep === 1),
            isWarning: false,
            icon: Utensils,
        },
        {
            id: 3,
            title: "Pengajuan Purchase Order",
            subtitle: "Estimasi Anggaran Belanja",
            desc: wo?.diajukan_pada ? `Diajukan ${wo.diajukan_pada.split(' ')[0]}` : (isRejected ? "Revisi Diperlukan" : (currentStep >= 3 ? "Draft Belanja Disusun" : "Menunggu Formulasi")),
            isCompleted: currentStep >= 4 && !isRejected,
            isActive: currentStep === 3,
            isWarning: isRejected,
            icon: Receipt,
        },
        {
            id: 4,
            title: "Verifikasi Keuangan",
            subtitle: "Telaah Anggaran & Rekanan",
            desc: isRejected ? "Ditolak Verifikator" : (currentStep >= 5 ? "Disetujui Keuangan" : (currentStep === 4 ? "Sedang Ditelaah" : "Menunggu Pengajuan")),
            isCompleted: currentStep >= 5,
            isActive: currentStep === 4 || isRejected,
            isWarning: isRejected,
            icon: ShieldCheck,
        },
        {
            id: 5,
            title: "Siap Produksi",
            subtitle: "PO Resmi & Jadwal Masak",
            desc: currentStep >= 5 ? "PO Resmi Terbit & Siap Masak" : "Menunggu Persetujuan",
            isCompleted: currentStep >= 5,
            isActive: currentStep === 5,
            isWarning: false,
            icon: ChefHat,
        },
    ];
});

function formatTanggalIndo(tgl) {
    if (!tgl) return "-";
    try {
        const d = new Date(tgl);
        return d.toLocaleDateString("id-ID", {
            weekday: "short",
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    } catch {
        return tgl;
    }
}
</script>

<template>
    <div class="mb-6">
        <Card class="border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden bg-white max-w-full">
            <!-- Header Bar -->
            <div class="p-4 sm:p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4 overflow-hidden">
                <div class="flex items-start sm:items-center gap-3 min-w-0">
                    <div class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-2xs mt-0.5 sm:mt-0">
                        <Activity class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
                                Tracking Progres Work Order & Produksi MBG
                            </h3>
                            <span
                                v-if="activeWorkOrder"
                                :class="[
                                    'px-2 py-0.5 text-[10.5px] font-bold rounded-md border inline-flex items-center gap-1 shadow-2xs',
                                    trackingState.statusColor === 'emerald' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' :
                                    trackingState.statusColor === 'blue' ? 'bg-blue-50 text-blue-700 border-blue-300' :
                                    trackingState.statusColor === 'rose' ? 'bg-rose-50 text-rose-700 border-rose-300' :
                                    'bg-amber-50 text-amber-700 border-amber-300'
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="[
                                    trackingState.statusColor === 'emerald' ? 'bg-emerald-500' :
                                    trackingState.statusColor === 'blue' ? 'bg-blue-500 animate-pulse' :
                                    trackingState.statusColor === 'rose' ? 'bg-rose-500' :
                                    'bg-amber-500 animate-pulse'
                                ]"></span>
                                {{ trackingState.statusLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pantau tahapan perencanaan gizi, verifikasi belanja bahan baku, hingga status siap produksi makanan bergizi.
                        </p>
                    </div>
                </div>

                <!-- WO Selector & Shortcut Link ke Daftar Menu Gizi -->
                <div class="flex items-center gap-2 w-full md:w-auto flex-wrap sm:flex-nowrap min-w-0">
                    <div v-if="workOrders.length > 1" class="relative flex-1 sm:flex-none min-w-0 w-full sm:w-auto">
                        <select
                            v-model="selectedWoId"
                            class="w-full sm:w-auto max-w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2 pr-8 text-slate-700 hover:border-slate-300 focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-2xs transition-colors cursor-pointer truncate"
                        >
                            <option
                                v-for="w in workOrders"
                                :key="w.id"
                                :value="w.id"
                            >
                                {{ w.tanggal === todayStr ? '⭐ Hari Ini - ' : '' }}{{ w.id }} • {{ w.nama }} ({{ formatTanggalIndo(w.tanggal) }})
                            </option>
                        </select>
                    </div>

                    <Link
                        :href="route('gizi.daftar-menu')"
                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition-colors shadow-2xs cursor-pointer shrink-0"
                        title="Buka Halaman Tabel Daftar Menu"
                    >
                        <span>Daftar Menu</span>
                        <ChevronRight class="h-3.5 w-3.5 text-slate-400" />
                    </Link>
                </div>
            </div>

            <!-- Content Area -->
            <div v-if="activeWorkOrder" class="p-5 sm:p-6 space-y-6">
                <!-- Info Banner Menu Aktif -->
                <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono font-bold text-primary bg-primary/10 px-2 py-0.5 rounded text-[11px]">
                                {{ activeWorkOrder.id }}
                            </span>
                            <span class="font-bold text-slate-900 text-sm">
                                {{ activeWorkOrder.nama }}
                            </span>
                            <span
                                v-if="dateStatus"
                                :class="['px-2 py-0.5 text-[10px] font-bold rounded-md border', dateStatus.badgeClass]"
                            >
                                {{ dateStatus.label }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-500 font-medium text-[11.5px] flex-wrap">
                            <span class="flex items-center gap-1">
                                <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                Distribusi: <strong>{{ formatTanggalIndo(activeWorkOrder.tanggal) }}</strong>
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <Users class="h-3.5 w-3.5 text-slate-400" />
                                Sasaran: <strong>{{ Number(activeWorkOrder.total_porsi || 0).toLocaleString('id-ID') }} Porsi</strong> ({{ activeWorkOrder.porsi_pk || 0 }} PK / {{ activeWorkOrder.porsi_pb || 0 }} PB)
                            </span>
                            <span v-if="activeWorkOrder.po">•</span>
                            <span v-if="activeWorkOrder.po" class="font-mono font-bold text-emerald-700">
                                Ref PO: {{ activeWorkOrder.po.id }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Percentage Pill -->
                    <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                        <div class="text-right">
                            <span class="text-[10.5px] text-slate-400 font-bold block uppercase tracking-wider">Penyelesaian</span>
                            <span class="text-base font-black text-slate-900 leading-none">
                                {{ trackingState.percent }}%
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center p-1 shadow-2xs">
                            <div
                                class="h-full w-full rounded-lg flex items-center justify-center text-xs font-black"
                                :class="[
                                    trackingState.statusColor === 'emerald' ? 'bg-emerald-500 text-white' :
                                    trackingState.statusColor === 'blue' ? 'bg-blue-600 text-white' :
                                    trackingState.statusColor === 'rose' ? 'bg-rose-500 text-white' :
                                    'bg-amber-500 text-white'
                                ]"
                            >
                                {{ trackingState.percent }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Penolakan jika Status Ditolak -->
                <div
                    v-if="trackingState.isRejected"
                    class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1"
                >
                    <div class="flex items-center gap-2 font-bold text-rose-900">
                        <AlertCircle class="h-4 w-4 shrink-0 text-rose-600" />
                        <span>Work Order Memerlukan Perbaikan / Ditolak oleh Bagian Keuangan:</span>
                    </div>
                    <p class="pl-6 text-rose-700 italic">
                        "{{ activeWorkOrder.catatan_keuangan || 'Periksa kembali formulasi bahan pangan atau pagu biaya belanja.' }}"
                    </p>
                    <div class="pl-6 pt-1">
                        <Link
                            :href="route('gizi.daftar-menu')"
                            class="inline-flex items-center gap-1 font-bold text-rose-700 hover:text-rose-900 underline"
                        >
                            <span>Buka Daftar Menu & Perbaiki Sekarang</span>
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- A. MOBILE VIEW (< sm): Elegant Vertical Stepper with Icon on the Left -->
                <div class="sm:hidden space-y-3.5 relative pt-1 pb-1">
                    <div
                        v-for="(step, sIdx) in steps"
                        :key="'mobile-' + step.id"
                        class="relative flex items-start gap-3"
                    >
                        <!-- Left: Circle Node & Vertical Connector Line -->
                        <div class="relative flex flex-col items-center self-stretch shrink-0">
                            <!-- Node Circle -->
                            <div
                                class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-xs shrink-0 transition-all border-2 shadow-xs z-10"
                                :class="[
                                    step.isWarning
                                        ? 'border-rose-600 bg-rose-600 text-white ring-4 ring-rose-100 animate-pulse'
                                        : step.isCompleted
                                          ? (trackingState.percent === 100
                                              ? 'border-emerald-600 bg-emerald-600 text-white ring-4 ring-emerald-100 shadow-emerald-200'
                                              : (step.id === 5 
                                                  ? 'border-emerald-600 bg-emerald-600 text-white ring-4 ring-emerald-100 shadow-emerald-200' 
                                                  : 'border-primary bg-primary text-white ring-4 ring-primary/20'))
                                          : step.isActive
                                            ? 'border-amber-500 bg-amber-500 text-white ring-4 ring-amber-100 animate-pulse'
                                            : 'border-slate-300 bg-slate-100 text-slate-400'
                                ]"
                            >
                                <Check v-if="step.isCompleted && !step.isWarning" class="h-5 w-5 stroke-[3] text-white" />
                                <AlertCircle v-else-if="step.isWarning" class="h-5 w-5 text-white" />
                                <component v-else :is="step.icon" class="h-4 w-4" />
                            </div>

                            <!-- Vertical Connector Line to Next Node -->
                            <div
                                v-if="sIdx < steps.length - 1"
                                class="w-0.5 flex-1 my-1 rounded-full transition-all"
                                :class="[
                                    step.isCompleted && steps[sIdx + 1].isCompleted
                                        ? (trackingState.percent === 100 ? 'bg-emerald-500' : 'bg-primary')
                                        : 'bg-slate-200'
                                ]"
                            ></div>
                        </div>

                        <!-- Right: Step Description Card Box -->
                        <div
                            class="flex-1 p-3.5 rounded-xl text-left bg-slate-50/90 border shadow-2xs transition-all flex flex-col justify-between space-y-1.5 min-w-0"
                            :class="[
                                trackingState.percent === 100
                                    ? 'border-slate-200/80 hover:border-slate-300'
                                    : (step.isWarning
                                        ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-300/40'
                                        : (step.isActive
                                            ? 'border-primary/50 bg-primary/5 ring-2 ring-primary/20 shadow-xs'
                                            : (!step.isCompleted ? 'border-slate-200/60 opacity-80' : 'border-slate-200/80 hover:border-slate-300')))
                            ]"
                        >
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10.5px] font-bold text-slate-400">0{{ step.id }}.</span>
                                    <span class="text-xs font-bold text-slate-900 truncate">
                                        {{ step.title }}
                                    </span>
                                </div>
                                <p class="text-[11px] font-medium text-slate-500 mt-0.5">
                                    {{ step.subtitle }}
                                </p>
                            </div>
                            <div>
                                <span
                                    :class="[
                                        'inline-block text-[10px] font-bold px-2 py-0.5 rounded border',
                                        step.isWarning
                                            ? 'bg-rose-100 text-rose-700 border-rose-200'
                                            : step.isCompleted
                                              ? (trackingState.percent === 100
                                                  ? 'bg-emerald-100/80 text-emerald-800 border-emerald-200'
                                                  : 'bg-emerald-50 text-emerald-700 border-emerald-200')
                                              : step.isActive
                                                ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                : 'bg-slate-100 text-slate-500 border-slate-200'
                                    ]"
                                >
                                    {{ step.desc }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- B. DESKTOP VIEW (sm:block): 5-Column Horizontal Pipeline Stepper -->
                <div class="hidden sm:block pt-2 pb-2">
                    <!-- 1. Node & Connector Header Row -->
                    <div class="relative flex items-center justify-between mb-4 px-3 sm:px-8">
                        <!-- Horizontal Gray Track Line Container -->
                        <div class="absolute left-8 right-8 sm:left-12 sm:right-12 top-1/2 -translate-y-1/2 h-1 bg-slate-200 z-0 overflow-hidden rounded-full">
                            <!-- Horizontal Colored Active Progress Line -->
                            <div
                                class="h-full rounded-full transition-all duration-700 ease-out"
                                :style="{ width: trackingState.percent === 100 ? '100%' : `${((trackingState.currentStep - 1) / 4) * 100}%` }"
                                :class="[
                                    trackingState.percent === 100 ? 'bg-emerald-500' :
                                    trackingState.statusColor === 'emerald' ? 'bg-emerald-500' :
                                    trackingState.statusColor === 'rose' ? 'bg-rose-500' :
                                    'bg-primary'
                                ]"
                            ></div>
                        </div>

                        <!-- Step Circular Nodes -->
                        <div
                            v-for="(step, sIdx) in steps"
                            :key="'node-' + step.id"
                            class="relative z-10 flex flex-col items-center"
                        >
                            <div
                                class="h-11 w-11 rounded-full flex items-center justify-center font-bold text-xs shrink-0 transition-all border-2 shadow-xs"
                                :class="[
                                    step.isWarning
                                        ? 'border-rose-600 bg-rose-600 text-white ring-4 ring-rose-100 animate-pulse'
                                        : step.isCompleted
                                          ? (trackingState.percent === 100
                                              ? 'border-emerald-600 bg-emerald-600 text-white ring-4 ring-emerald-100 shadow-emerald-200'
                                              : (step.id === 5 
                                                  ? 'border-emerald-600 bg-emerald-600 text-white ring-4 ring-emerald-100 shadow-emerald-200' 
                                                  : 'border-primary bg-primary text-white ring-4 ring-primary/20'))
                                          : step.isActive
                                            ? 'border-amber-500 bg-amber-500 text-white ring-4 ring-amber-100 animate-pulse'
                                            : 'border-slate-300 bg-slate-100 text-slate-400'
                                ]"
                            >
                                <Check v-if="step.isCompleted && !step.isWarning" class="h-5 w-5 stroke-[3] text-white" />
                                <AlertCircle v-else-if="step.isWarning" class="h-5 w-5 text-white" />
                                <component v-else :is="step.icon" class="h-5 w-5" />
                            </div>
                        </div>
                    </div>

                    <!-- 2. Step Text Descriptions Grid (Uniform Boxes, Left-Aligned) -->
                    <div class="grid grid-cols-5 gap-3 pt-1">
                        <div
                            v-for="(step, sIdx) in steps"
                            :key="'text-' + step.id"
                            class="p-3.5 rounded-xl text-left bg-slate-50/90 border shadow-2xs transition-all flex flex-col justify-between space-y-2"
                            :class="[
                                trackingState.percent === 100
                                    ? 'border-slate-200/80 hover:border-slate-300'
                                    : (step.isWarning
                                        ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-300/40'
                                        : (step.isActive
                                            ? 'border-primary/50 bg-primary/5 ring-2 ring-primary/20 shadow-xs'
                                            : (!step.isCompleted ? 'border-slate-200/60 opacity-80' : 'border-slate-200/80 hover:border-slate-300')))
                            ]"
                        >
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10.5px] font-bold text-slate-400">0{{ step.id }}.</span>
                                    <span class="text-xs font-bold text-slate-900 truncate">
                                        {{ step.title }}
                                    </span>
                                </div>
                                <p class="text-[11px] font-medium text-slate-500 truncate mt-0.5">
                                    {{ step.subtitle }}
                                </p>
                            </div>
                            <div>
                                <span
                                    :class="[
                                        'inline-block text-[10px] font-bold px-2 py-0.5 rounded border',
                                        step.isWarning
                                            ? 'bg-rose-100 text-rose-700 border-rose-200'
                                            : step.isCompleted
                                              ? (trackingState.percent === 100
                                                  ? 'bg-emerald-100/80 text-emerald-800 border-emerald-200'
                                                  : 'bg-emerald-50 text-emerald-700 border-emerald-200')
                                              : step.isActive
                                                ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                : 'bg-slate-100 text-slate-500 border-slate-200'
                                    ]"
                                >
                                    {{ step.desc }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Quick Action CTA -->
                <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-start gap-2 text-slate-600 font-medium">
                        <Sparkles class="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
                        <span v-if="trackingState.currentStep === 5">
                            Work Order telah terverifikasi penuh dan Purchase Order resmi siap untuk pengadaan & distribusi dapur.
                        </span>
                        <span v-else-if="trackingState.currentStep === 4">
                            Work Order sedang dalam proses penelaahan anggaran dan harga aktual oleh Bagian Keuangan.
                        </span>
                        <span v-else-if="trackingState.isRejected">
                            Silakan lakukan revisi pada daftar menu agar dapat diajukan kembali untuk persetujuan.
                        </span>
                        <span v-else>
                            Lengkapi rincian formulasi gizi dan ajukan ke Bagian Keuangan untuk penerbitan PO.
                        </span>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto pt-0.5 sm:pt-0">
                        <Link
                            v-if="trackingState.currentStep >= 5"
                            :href="route('keuangan.daftar-po')"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer"
                        >
                            <Receipt class="h-3.5 w-3.5" />
                            <span>Lihat PO Resmi</span>
                        </Link>
                        <Link
                            v-else-if="trackingState.currentStep === 4"
                            :href="route('keuangan.verifikasi-po')"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer"
                        >
                            <ShieldCheck class="h-3.5 w-3.5" />
                            <span>Buka Verifikasi PO</span>
                        </Link>
                        <Link
                            v-else
                            :href="route('gizi.daftar-menu')"
                            class="px-4 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer"
                        >
                            <Edit3 class="h-3.5 w-3.5" />
                            <span>Buka Menu & Lanjutkan</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State jika Belum Ada WO -->
            <div v-else class="p-8 text-center space-y-3">
                <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto shadow-2xs">
                    <Utensils class="h-6 w-6" />
                </div>
                <div class="max-w-md mx-auto">
                    <h4 class="text-sm font-bold text-slate-800">Belum Ada Perencanaan Work Order (WO)</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        Mulai rancang menu makanan bergizi gratis untuk mengaktifkan pelacakan alur produksi hingga penerbitan PO resmi.
                    </p>
                </div>
                <div class="pt-2">
                    <Link
                        :href="route('gizi.rancang-menu')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary hover:bg-primary/90 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer"
                    >
                        <ChefHat class="h-4 w-4" />
                        <span>Mulai Rancang Menu Sekarang</span>
                    </Link>
                </div>
            </div>
        </Card>
    </div>
</template>
