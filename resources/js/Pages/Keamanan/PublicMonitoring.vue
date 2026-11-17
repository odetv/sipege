<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
    ShieldCheck,
    ShieldAlert,
    Shield,
    Clock,
    Calendar,
    Users,
    MapPin,
    Navigation,
    RefreshCw,
    Maximize,
    Minimize,
    ExternalLink,
    AlertTriangle,
    Check,
    LogOut,
    LogIn,
    FileText,
    Smartphone,
    Sun,
    Flame,
    Cloud,
    Wind,
    Snowflake,
    CloudRain,
    CloudLightning,
    PhoneCall,
    Radio,
    ArrowLeftRight,
    ChevronDown,
    ChevronUp,
    ListChecks,
    Sparkles,
} from "lucide-vue-next";
import MiniGoogleMap from "./Partials/MiniGoogleMap.vue";
import KeamananChecklistModal from "./Partials/KeamananChecklistModal.vue";

const props = defineProps({
    unitSppg: {
        type: Object,
        default: null,
    },
    token: {
        type: String,
        required: true,
    },
    todayDate: {
        type: String,
        default: () => new Date().toISOString().split("T")[0],
    },
    serverTimeWita: {
        type: String,
        default: "00:00:00",
    },
    currentActiveSession: {
        type: String,
        default: "",
    },
    sesiList: {
        type: Array,
        default: () => [],
    },
    petugasMonitoringList: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_terlapor: 0,
            total_insiden: 0,
            count_sedang_keluar: 0,
            status_keseluruhan: "Aman",
            current_active_session: "",
            active_session_report_count: 0,
        }),
    },
});

// Sesi Aktif Saat Ini
const activeSession = computed(() => {
    return (
        props.currentActiveSession ||
        props.summary?.current_active_session ||
        ""
    );
});

// State Expand/Collapse Sesi (Default Tertutup Semua / Collapsed)
const expandedSessions = ref({});

function toggleSession(jam) {
    expandedSessions.value[jam] = !expandedSessions.value[jam];
}

function expandAll() {
    props.sesiList.forEach((s) => {
        expandedSessions.value[s.jam] = true;
    });
}

function collapseAll() {
    expandedSessions.value = {};
}

function expandActive() {
    if (activeSession.value) {
        expandedSessions.value[activeSession.value] = true;
    }
}

// Hitung Jarak Geodesik (Haversine) dari Titik Koordinat ke SPPG
function getDistanceFromSppg(lat, lng) {
    if (!lat || !lng) return null;
    const sppgLat = props.unitSppg?.latitude
        ? Number(props.unitSppg.latitude)
        : null;
    const sppgLng = props.unitSppg?.longitude
        ? Number(props.unitSppg.longitude)
        : null;
    if (
        sppgLat === null ||
        sppgLng === null ||
        isNaN(sppgLat) ||
        isNaN(sppgLng)
    )
        return null;

    const latNum = Number(lat);
    const lngNum = Number(lng);
    if (isNaN(latNum) || isNaN(lngNum)) return null;

    const R = 6371000; // Radius bumi dalam meter
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(latNum - sppgLat);
    const dLng = toRad(lngNum - sppgLng);
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(sppgLat)) *
            Math.cos(toRad(latNum)) *
            Math.sin(dLng / 2) *
            Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distanceMeter = Math.round(R * c);

    if (distanceMeter >= 1000) {
        const km = (distanceMeter / 1000).toFixed(2).replace(".", ",");
        return `${km} km`;
    }
    return `${distanceMeter} m`;
}

// Realtime Clock Running
const liveTime = ref(props.serverTimeWita);
const liveDate = ref("");
let clockTimer = null;
let autoRefreshTimer = null;
const isRefreshing = ref(false);
const refreshCountdown = ref(15);
let countdownTimer = null;

function updateClock() {
    try {
        const now = new Date();
        liveTime.value = now
            .toLocaleTimeString("id-ID", {
                timeZone: "Asia/Makassar",
                hour12: false,
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            })
            .replace(/\./g, ":");
        liveDate.value = now.toLocaleDateString("id-ID", {
            timeZone: "Asia/Makassar",
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } catch {
        // fallback
    }
}

function refreshData() {
    isRefreshing.value = true;
    router.reload({
        only: [
            "petugasMonitoringList",
            "summary",
            "serverTimeWita",
            "sesiList",
            "currentActiveSession",
        ],
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
            refreshCountdown.value = 15;
        },
    });
}

// Fullscreen Toggle (Cocok untuk Smart TV / Layar Monitor Pos)
const isFullscreen = ref(false);
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement
            .requestFullscreen()
            .then(() => {
                isFullscreen.value = true;
            })
            .catch(() => {});
    } else {
        if (document.exitFullscreen) {
            document
                .exitFullscreen()
                .then(() => {
                    isFullscreen.value = false;
                })
                .catch(() => {});
        }
    }
}

// Modal Checklist Detail
const showChecklistModal = ref(false);
const activeChecklistDetail = ref(null);
function openChecklist(detail) {
    activeChecklistDetail.value = detail;
    showChecklistModal.value = true;
}

// Cuaca Helpers
function getCuacaBadge(cuaca) {
    switch (cuaca) {
        case "Cerah":
            return {
                label: "Cerah",
                icon: Sun,
                color: "text-amber-700 bg-amber-50 border-amber-200",
            };
        case "Panas":
            return {
                label: "Panas",
                icon: Flame,
                color: "text-orange-700 bg-orange-50 border-orange-200",
            };
        case "Berawan":
            return {
                label: "Berawan",
                icon: Cloud,
                color: "text-slate-700 bg-slate-100 border-slate-200",
            };
        case "Berangin":
            return {
                label: "Berangin",
                icon: Wind,
                color: "text-teal-700 bg-teal-50 border-teal-200",
            };
        case "Mendung":
            return {
                label: "Mendung",
                icon: Cloud,
                color: "text-slate-700 bg-slate-100 border-slate-300",
            };
        case "Dingin":
            return {
                label: "Dingin",
                icon: Snowflake,
                color: "text-sky-700 bg-sky-50 border-sky-200",
            };
        case "Hujan":
            return {
                label: "Hujan",
                icon: CloudRain,
                color: "text-blue-700 bg-blue-50 border-blue-200",
            };
        case "Badai":
            return {
                label: "Badai",
                icon: CloudLightning,
                color: "text-purple-700 bg-purple-50 border-purple-200",
            };
        default:
            return {
                label: cuaca || "Cerah",
                icon: Sun,
                color: "text-amber-700 bg-amber-50 border-amber-200",
            };
    }
}

// Insights Pengecekan
function extractChecklistSummary(dp) {
    if (!dp) return null;
    const unsafeAreas = (dp.lingkungan_kerja || [])
        .filter((a) => a.status === "Tidak Aman")
        .map((a) => a.label);

    const highlights = [];
    (dp.pengecekan_umum || []).forEach((u) => {
        if (u.status === "Tidak Aman" || u.status === "Ada") {
            highlights.push({
                label: u.label,
                status: u.status,
                uraian: u.uraian,
            });
        }
    });

    return {
        allAreasSafe: unsafeAreas.length === 0,
        unsafeAreas,
        highlights,
        catatanTambahan: dp.catatan_tambahan || "",
    };
}

const formattedToday = computed(() => {
    try {
        const d = new Date(props.todayDate + "T00:00:00");
        return d.toLocaleDateString("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } catch {
        return props.todayDate;
    }
});

function formatIndoDate(dateStr) {
    if (!dateStr) return formattedToday.value;
    try {
        const d = new Date(
            dateStr.includes("T") ? dateStr : dateStr + "T00:00:00",
        );
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    } catch {
        return dateStr;
    }
}

function formatRelativeTime(str) {
    if (!str) return "Baru saja";
    return String(str)
        .replace(/hours?\s+ago/gi, "jam yang lalu")
        .replace(/minutes?\s+ago/gi, "menit yang lalu")
        .replace(/seconds?\s+ago/gi, "detik yang lalu")
        .replace(/days?\s+ago/gi, "hari yang lalu")
        .replace(/weeks?\s+ago/gi, "minggu yang lalu")
        .replace(/months?\s+ago/gi, "bulan yang lalu")
        .replace(/years?\s+ago/gi, "tahun yang lalu")
        .replace(/an?\s+hour\s+ago/gi, "1 jam yang lalu")
        .replace(/an?\s+minute\s+ago/gi, "1 menit yang lalu")
        .replace(/a\s+moment\s+ago/gi, "baru saja")
        .replace(/just now/gi, "baru saja");
}

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);

    // Auto-refresh setiap 15 detik
    autoRefreshTimer = setInterval(refreshData, 15000);

    countdownTimer = setInterval(() => {
        if (refreshCountdown.value > 1) {
            refreshCountdown.value--;
        } else {
            refreshCountdown.value = 15;
        }
    }, 1000);
});

onBeforeUnmount(() => {
    if (clockTimer) clearInterval(clockTimer);
    if (autoRefreshTimer) clearInterval(autoRefreshTimer);
    if (countdownTimer) clearInterval(countdownTimer);
});
</script>

<template>
    <div
        class="min-h-screen bg-slate-100/70 text-slate-800 font-sans pb-16 antialiased selection:bg-emerald-100 selection:text-emerald-900"
    >
        <Head
            :title="
                'Monitoring Petugas Keamanan SPPG - ' +
                (unitSppg?.nama || 'SPPG')
            "
        />

        <!-- COMMAND CENTER HEADER (Tema Biru Langit Elegan Berkontras Tinggi Seperti Lapor Keamanan) -->
        <header
            class="bg-gradient-to-r from-sky-900 via-sky-800 to-blue-900 text-white shadow-md border-b-4 border-sky-500 sticky top-0 z-30"
        >
            <div class="w-full px-4 sm:px-6 lg:px-8 py-3.5 sm:py-4">
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-3"
                >
                    <!-- Title & SPPG -->
                    <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
                        <div
                            class="relative h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-white/15 border border-white/25 text-white flex items-center justify-center shrink-0 shadow-xs overflow-hidden"
                            title="Live Streaming Monitoring Aktif"
                        >
                            <!-- Animated Streaming Radar / Pulse Ripple Effect -->
                            <span
                                class="absolute inset-0 rounded-2xl bg-emerald-400/20 animate-ping opacity-60"
                            ></span>
                            <span
                                class="absolute inset-1 rounded-xl bg-emerald-400/10 animate-pulse"
                            ></span>

                            <!-- Streaming Broadcast Icon -->
                            <Radio
                                class="h-5 w-5 sm:h-6 sm:w-6 text-emerald-300 relative z-10 animate-pulse shrink-0 drop-shadow-[0_0_8px_rgba(52,211,153,0.7)]"
                            />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1
                                class="text-sm sm:text-base md:text-lg font-black tracking-tight leading-snug truncate sm:whitespace-normal text-white"
                            >
                                Monitoring Petugas Keamanan SPPG
                            </h1>
                            <p
                                class="text-[11px] sm:text-xs text-sky-200 truncate mt-0.5"
                            >
                                {{
                                    unitSppg?.nama ||
                                    "Satuan Pelayanan Program Gizi"
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Live Clock & Control Actions (Glassmorphism Elegan pada Background Biru) -->
                    <div
                        class="w-full md:w-auto flex items-center gap-2 sm:gap-2.5 pt-2.5 md:pt-0 border-t border-white/15 md:border-none"
                    >
                        <!-- Digital Clock & Hari / Tanggal (Simetris & Rapi, mengisi proporsional pada HP) -->
                        <div
                            class="flex-1 md:flex-initial h-11 px-2.5 sm:px-3.5 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 shadow-xs flex items-center gap-2 sm:gap-2.5 transition-colors min-w-0"
                        >
                            <div
                                class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center shrink-0"
                            >
                                <Clock
                                    class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-emerald-300"
                                />
                            </div>
                            <div
                                class="flex flex-col justify-center leading-none min-w-0"
                            >
                                <div class="flex items-center gap-1 sm:gap-1.5">
                                    <span
                                        class="text-xs sm:text-sm font-black font-mono tracking-wider text-white"
                                    >
                                        {{ liveTime }}
                                    </span>
                                    <span
                                        class="text-[9px] font-mono font-black text-emerald-300 bg-emerald-500/20 border border-emerald-400/30 px-1.5 py-0.5 rounded uppercase leading-none"
                                    >
                                        WITA
                                    </span>
                                </div>
                                <span
                                    class="text-[9.5px] sm:text-[10px] font-medium text-sky-200 truncate mt-1 leading-none"
                                >
                                    {{ liveDate || formattedToday }}
                                </span>
                            </div>
                        </div>

                        <!-- Tombol Segarkan / Sinkron -->
                        <button
                            type="button"
                            @click="refreshData"
                            :disabled="isRefreshing"
                            class="h-11 px-2.5 sm:px-3.5 rounded-xl text-xs font-bold bg-white/15 hover:bg-white/25 active:bg-white/30 text-white border border-white/20 shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
                            title="Segarkan data monitoring sekarang"
                        >
                            <RefreshCw
                                class="h-3.5 w-3.5 text-sky-300 shrink-0"
                                :class="isRefreshing ? 'animate-spin' : ''"
                            />
                            <span
                                class="text-[11.5px] sm:text-xs font-bold leading-none whitespace-nowrap text-white"
                                >{{
                                    isRefreshing
                                        ? "Sinkron..."
                                        : `Sync (${refreshCountdown}s)`
                                }}</span
                            >
                        </button>

                        <!-- Fullscreen Toggle -->
                        <button
                            type="button"
                            @click="toggleFullscreen"
                            class="h-11 w-11 rounded-xl bg-white/15 hover:bg-white/25 active:bg-white/30 text-white border border-white/20 shadow-xs transition-all cursor-pointer flex items-center justify-center shrink-0"
                            title="Layar Penuh (Cocok untuk Smart TV / Display Monitor Pos)"
                        >
                            <Minimize
                                v-if="isFullscreen"
                                class="h-4 w-4 text-sky-300"
                            />
                            <Maximize v-else class="h-4 w-4 text-white" />
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTAINER BODY (Full Width Monitor View) -->
        <main
            class="w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4 sm:space-y-6"
        >
            <!-- TOP 3 STATUS KPI BAR (KONDISI, 24 JAM PATROLI, & SESI AKTIF SAAT INI) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 sm:gap-4">
                <!-- 1. Status Kondisi SPPG -->
                <div
                    class="bg-white rounded-2xl border p-3.5 sm:p-4 shadow-xs transition-colors flex flex-col justify-between"
                    :class="
                        summary.status_keseluruhan === 'Tidak Aman'
                            ? 'border-rose-300 bg-rose-50/40'
                            : 'border-slate-200 hover:border-emerald-300'
                    "
                >
                    <div
                        class="flex items-center justify-between text-xs text-slate-500 mb-1"
                    >
                        <span
                            class="font-bold uppercase tracking-wider text-[10px]"
                            >Kondisi Lingkungan SPPG</span
                        >
                        <ShieldAlert
                            v-if="summary.status_keseluruhan === 'Tidak Aman'"
                            class="h-4 w-4 text-rose-600"
                        />
                        <ShieldCheck v-else class="h-4 w-4 text-emerald-600" />
                    </div>
                    <div
                        class="flex-1 flex flex-col items-center justify-center text-center py-2 sm:py-2.5"
                    >
                        <div
                            class="text-2xl sm:text-3xl font-black tracking-tight flex items-center justify-center gap-1.5"
                            :class="
                                summary.status_keseluruhan === 'Tidak Aman'
                                    ? 'text-rose-600'
                                    : 'text-emerald-700'
                            "
                        >
                            <span
                                v-if="
                                    summary.status_keseluruhan !== 'Tidak Aman'
                                "
                                class="text-emerald-600 text-3xl sm:text-4xl leading-none font-black"
                                >✓</span
                            >
                            <span v-else class="text-rose-600 leading-none"
                                >⚠️</span
                            >
                            <span>{{
                                summary.status_keseluruhan === "Tidak Aman"
                                    ? `${summary.total_insiden} Kendala`
                                    : "Aman"
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Patroli 24 Jam Terlapor -->
                <div
                    class="bg-white rounded-2xl border border-slate-200 hover:border-blue-300 p-3.5 sm:p-4 shadow-xs transition-colors flex flex-col justify-between"
                >
                    <div
                        class="flex items-center justify-between text-xs text-slate-500 mb-1"
                    >
                        <span
                            class="font-bold uppercase tracking-wider text-[10px]"
                            >Pengecekan Terisi Hari Ini</span
                        >
                        <Clock class="h-4 w-4 text-blue-600" />
                    </div>
                    <div class="text-base sm:text-xl font-black text-slate-900">
                        {{
                            summary.total_interval_terisi ??
                            summary.total_terlapor
                        }}
                        <span class="text-xs text-slate-500 font-normal"
                            >/ 24 Jam</span
                        >
                        <span
                            v-if="
                                summary.total_terlapor >
                                (summary.total_interval_terisi ??
                                    summary.total_terlapor)
                            "
                            class="text-[11px] font-bold text-blue-600 block mt-0.5"
                        >
                            ({{ summary.total_terlapor }} Total Laporan Masuk)
                        </span>
                    </div>
                    <div
                        class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden"
                    >
                        <div
                            class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                            :style="{
                                width:
                                    Math.min(
                                        100,
                                        Math.round(
                                            ((summary.total_interval_terisi ??
                                                summary.total_terlapor) /
                                                24) *
                                                100,
                                        ),
                                    ) + '%',
                            }"
                        ></div>
                    </div>
                </div>

                <!-- 3. Sesi Jam Aktif Saat Ini -->
                <div
                    class="bg-white rounded-2xl border border-slate-200 hover:border-blue-300 p-3.5 sm:p-4 shadow-xs transition-colors flex flex-col justify-between"
                >
                    <div
                        class="flex items-center justify-between text-xs text-slate-500 mb-1"
                    >
                        <span
                            class="font-bold uppercase tracking-wider text-[10px] text-emerald-800 flex items-center gap-1.5"
                        >
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"
                                ></span>
                            </span>
                            <span>Sesi Jam Aktif Saat Ini</span>
                        </span>
                        <span
                            class="text-[9.5px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300"
                        >
                            WITA
                        </span>
                    </div>
                    <div
                        class="text-base sm:text-xl font-black text-emerald-950 font-mono tracking-tight"
                    >
                        {{ activeSession || "Memuat Sesi..." }}
                    </div>
                    <div
                        class="flex items-center justify-between gap-1 mt-1 text-[10.5px] sm:text-[11px]"
                    >
                        <span
                            v-if="
                                (summary.active_session_report_count || 0) > 0
                            "
                            class="font-black text-emerald-700 flex items-center gap-1"
                        >
                            <Check class="h-3 w-3" />
                            <span
                                >{{
                                    summary.active_session_report_count
                                }}
                                Laporan Masuk di Sesi Ini</span
                            >
                        </span>
                        <span v-else class="text-slate-500 font-medium">
                            Belum Ada Laporan di Sesi Ini
                        </span>
                    </div>
                </div>
            </div>

            <!-- SECTION PETUGAS KEAMANAN (MASING-MASING PETUGAS LAPORAN TERAKHIR) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-1">
                    <h2
                        class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                    >
                        <Shield class="h-4 w-4 text-emerald-600" />
                        <span>Data Terkini Petugas Keamanan</span>
                    </h2>
                    <span class="text-[10.5px] text-slate-500">
                        Otomatis tersinkron dari formulir pengecekan petugas
                    </span>
                </div>

                <!-- GRID KARTU MASING-MASING PETUGAS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 sm:gap-5">
                    <div
                        v-for="pet in petugasMonitoringList"
                        :key="pet.id"
                        class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-xs hover:shadow-md transition-shadow overflow-hidden flex flex-col justify-between"
                    >
                        <!-- CARD HEADER: IDENTITAS & STATUS KEBERADAAN -->
                        <div
                            class="p-3.5 sm:p-5 bg-slate-50/70 border-b border-slate-100"
                        >
                            <div
                                class="flex items-start justify-between gap-2.5 sm:gap-3"
                            >
                                <div
                                    class="flex items-center gap-2.5 sm:gap-3 min-w-0"
                                >
                                    <div
                                        class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl sm:rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center font-black text-sm sm:text-base shrink-0 shadow-2xs"
                                    >
                                        {{ pet.nama.charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3
                                            class="text-xs sm:text-base font-black text-slate-900 truncate"
                                        >
                                            {{ pet.nama }}
                                        </h3>
                                        <p
                                            class="text-[11px] sm:text-xs text-slate-500 truncate"
                                        >
                                            {{ pet.jabatan }}
                                            <span
                                                v-if="pet.no_telp"
                                                class="text-slate-400 ml-1"
                                                >• {{ pet.no_telp }}</span
                                            >
                                        </p>
                                    </div>
                                </div>

                                <!-- Badge Keberadaan Terkini Sesuai Input Formulir Petugas -->
                                <div class="shrink-0 text-right">
                                    <!-- 1. Dinas Luar / Sedang Keluar -->
                                    <span
                                        v-if="
                                            pet.status_kode ===
                                                'sedang_keluar' ||
                                            pet.status_keberadaan ===
                                                'Sedang Keluar'
                                        "
                                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[10.5px] font-black bg-amber-50 text-amber-700 border border-amber-200 animate-pulse"
                                        :title="
                                            'Sedang dinas luar ke: ' +
                                            (pet.posisi_terkini ||
                                                pet.izin_aktif?.lokasi_tujuan ||
                                                '-')
                                        "
                                    >
                                        <LogOut class="h-3 w-3" />
                                        <span class="truncate max-w-[170px]"
                                            >Dinas Luar:
                                            {{
                                                pet.posisi_terkini ||
                                                pet.izin_aktif?.lokasi_tujuan ||
                                                "Keluar"
                                            }}</span
                                        >
                                    </span>

                                    <!-- 2. Lepas Jaga -->
                                    <span
                                        v-else-if="
                                            pet.status_kode === 'lepas_jaga'
                                        "
                                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[10.5px] font-bold bg-slate-100 text-slate-700 border border-slate-300"
                                        title="Petugas telah melakukan serah terima Lepas Jaga"
                                    >
                                        <ArrowLeftRight
                                            class="h-3 w-3 text-slate-500"
                                        />
                                        <span>Lepas Jaga</span>
                                    </span>

                                    <!-- 3. Belum Ada Laporan Hari Ini -->
                                    <span
                                        v-else-if="
                                            pet.status_kode ===
                                                'belum_ada_laporan' ||
                                            (!pet.posisi_terkini &&
                                                pet.total_laporan_hari_ini ===
                                                    0 &&
                                                !pet.serah_terima_terkini)
                                        "
                                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[10.5px] font-medium bg-slate-100 text-slate-500 border border-slate-200"
                                        title="Belum ada laporan formulir yang dikirim oleh petugas ini hari ini"
                                    >
                                        <Clock class="h-3 w-3 text-slate-400" />
                                        <span>Belum Ada Laporan</span>
                                    </span>

                                    <!-- 4. Ada Laporan / Sedang di Lokasi yang Diinput di Formulir -->
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 sm:gap-1.5 px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[10.5px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        :title="
                                            'Posisi terakhir dari input formulir: ' +
                                            (pet.posisi_terkini ||
                                                pet.status_label ||
                                                'Pos Penjagaan')
                                        "
                                    >
                                        <Check class="h-3 w-3" />
                                        <span class="truncate max-w-[190px]">
                                            {{
                                                (
                                                    pet.posisi_terkini ||
                                                    pet.status_label ||
                                                    ""
                                                )
                                                    .toLowerCase()
                                                    .startsWith("di ")
                                                    ? pet.posisi_terkini ||
                                                      pet.status_label
                                                    : "Di " +
                                                      (pet.posisi_terkini ||
                                                          pet.status_label ||
                                                          "Pos Penjagaan")
                                            }}
                                        </span>
                                    </span>

                                    <span
                                        class="text-[9.5px] sm:text-[10px] text-slate-400 block mt-0.5 sm:mt-1 font-mono"
                                    >
                                        {{ pet.total_laporan_hari_ini }} laporan
                                        hari ini
                                    </span>
                                </div>
                            </div>

                            <!-- Status Serah Terima Terkini (Masuk Jaga / Lepas Jaga) -->
                            <div
                                v-if="pet.serah_terima_terkini"
                                class="mt-2.5 p-2 sm:p-2.5 rounded-xl border text-xs flex items-center justify-between gap-2 flex-wrap transition-all"
                                :class="
                                    pet.serah_terima_terkini.jenis ===
                                    'Masuk Jaga'
                                        ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950'
                                        : 'bg-slate-100 border-slate-300 text-slate-800'
                                "
                            >
                                <div
                                    class="flex items-center gap-1.5 min-w-0 flex-wrap"
                                >
                                    <span
                                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider shrink-0 flex items-center gap-1 shadow-2xs"
                                        :class="
                                            pet.serah_terima_terkini.jenis ===
                                            'Masuk Jaga'
                                                ? 'bg-emerald-600 text-white'
                                                : 'bg-slate-700 text-white'
                                        "
                                    >
                                        <ArrowLeftRight
                                            class="h-2.5 w-2.5 shrink-0"
                                        />
                                        <span>{{
                                            pet.serah_terima_terkini.jenis
                                        }}</span>
                                    </span>
                                    <span
                                        class="text-[11px] font-bold text-slate-800"
                                    >
                                        Pukul
                                        {{ pet.serah_terima_terkini.waktu }}
                                    </span>
                                    <span
                                        class="text-[10.5px] text-slate-500 font-medium truncate max-w-[150px]"
                                    >
                                        •
                                        {{
                                            pet.serah_terima_terkini.lokasi_nama
                                        }}
                                    </span>
                                </div>
                                <span
                                    v-if="pet.serah_terima_terkini.catatan"
                                    class="text-[10.5px] text-slate-600 italic truncate max-w-[200px]"
                                    :title="pet.serah_terima_terkini.catatan"
                                >
                                    💬 "{{ pet.serah_terima_terkini.catatan }}"
                                </span>
                            </div>

                            <!-- Alert Izin Keluar Sedang Berlangsung -->
                            <div
                                v-if="
                                    pet.izin_aktif &&
                                    pet.izin_aktif.status === 'Sedang Keluar'
                                "
                                class="mt-3 p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1.5"
                            >
                                <div
                                    class="flex items-center justify-between font-bold text-amber-800 flex-wrap gap-1"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <LogOut
                                            class="h-3.5 w-3.5 text-amber-600"
                                        />
                                        <span
                                            >Sedang Keluar ke:
                                            {{
                                                pet.izin_aktif.lokasi_tujuan
                                            }}</span
                                        >
                                    </div>
                                    <span
                                        class="text-[10px] font-mono bg-amber-100 text-amber-800 px-2 py-0.5 rounded font-bold"
                                    >
                                        Durasi:
                                        {{
                                            pet.izin_aktif
                                                .durasi_berjalan_text || "-"
                                        }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-amber-800/90 italic">
                                    "{{ pet.izin_aktif.keperluan }}" (Keluar
                                    pukul {{ pet.izin_aktif.waktu_keluar }})
                                </p>
                            </div>
                        </div>

                        <!-- CARD BODY: LAPORAN TERAKHIR & PETA GOOGLE -->
                        <div class="p-3.5 sm:p-5 space-y-3 sm:space-y-4 flex-1">
                            <!-- JIKA PETUGAS SUDAH MEMILIKI LAPORAN -->
                            <template v-if="pet.laporan_terakhir">
                                <!-- Info Bar Laporan Terakhir -->
                                <div
                                    class="p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2 flex-wrap"
                                    >
                                        <div
                                            class="flex items-center gap-2 flex-wrap"
                                        >
                                            <span
                                                class="text-xs font-black text-slate-800 font-mono bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs shrink-0"
                                            >
                                                {{ pet.laporan_terakhir.jam }}
                                            </span>
                                            <span
                                                class="text-[11px] text-slate-600 font-semibold"
                                            >
                                                {{
                                                    pet.laporan_terakhir
                                                        .hari_tanggal ||
                                                    formatIndoDate(
                                                        pet.laporan_terakhir
                                                            .tanggal,
                                                    )
                                                }}
                                                <span
                                                    class="text-slate-400 font-normal"
                                                    >Pukul</span
                                                >
                                                {{
                                                    pet.laporan_terakhir
                                                        .waktu_cek || "-"
                                                }}
                                                WITA
                                            </span>
                                        </div>

                                        <!-- Badge Cuaca & Kondisi -->
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                v-if="
                                                    pet.laporan_terakhir
                                                        .data_pengecekan?.cuaca
                                                "
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10.5px] font-bold border"
                                                :class="
                                                    getCuacaBadge(
                                                        pet.laporan_terakhir
                                                            .data_pengecekan
                                                            .cuaca,
                                                    ).color
                                                "
                                            >
                                                <component
                                                    :is="
                                                        getCuacaBadge(
                                                            pet.laporan_terakhir
                                                                .data_pengecekan
                                                                .cuaca,
                                                        ).icon
                                                    "
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <span>{{
                                                    pet.laporan_terakhir
                                                        .data_pengecekan.cuaca
                                                }}</span>
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10.5px] font-black border"
                                                :class="
                                                    pet.laporan_terakhir
                                                        .kondisi ===
                                                    'Tidak Aman'
                                                        ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                        : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                "
                                            >
                                                <AlertTriangle
                                                    v-if="
                                                        pet.laporan_terakhir
                                                            .kondisi ===
                                                        'Tidak Aman'
                                                    "
                                                    class="h-3 w-3"
                                                />
                                                <Check v-else class="h-3 w-3" />
                                                <span>{{
                                                    pet.laporan_terakhir.kondisi
                                                }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Ringkasan Temuan Form -->
                                    <div
                                        v-if="
                                            pet.laporan_terakhir.data_pengecekan
                                        "
                                        class="text-xs space-y-1.5 pt-1.5 border-t border-slate-200"
                                    >
                                        <!-- 21 Area Status -->
                                        <div
                                            class="flex items-center justify-between text-[11px]"
                                        >
                                            <span class="text-slate-500"
                                                >Pengecekan Semua Area:</span
                                            >
                                            <span
                                                v-if="
                                                    extractChecklistSummary(
                                                        pet.laporan_terakhir
                                                            .data_pengecekan,
                                                    )?.allAreasSafe
                                                "
                                                class="text-emerald-700 font-bold"
                                            >
                                                ✓ Seluruh Area Kerja Aman
                                            </span>
                                            <span
                                                v-else
                                                class="text-rose-700 font-bold"
                                            >
                                                ⚠️
                                                {{
                                                    extractChecklistSummary(
                                                        pet.laporan_terakhir
                                                            .data_pengecekan,
                                                    )?.unsafeAreas.length
                                                }}
                                                Area Bermasalah ({{
                                                    extractChecklistSummary(
                                                        pet.laporan_terakhir
                                                            .data_pengecekan,
                                                    )?.unsafeAreas.join(", ")
                                                }})
                                            </span>
                                        </div>

                                        <!-- Pengecekan Umum Highlights -->
                                        <div
                                            v-if="
                                                extractChecklistSummary(
                                                    pet.laporan_terakhir
                                                        .data_pengecekan,
                                                )?.highlights.length > 0
                                            "
                                            class="flex flex-wrap gap-1 pt-1"
                                        >
                                            <span
                                                v-for="(
                                                    h, hIdx
                                                ) in extractChecklistSummary(
                                                    pet.laporan_terakhir
                                                        .data_pengecekan,
                                                )?.highlights"
                                                :key="hIdx"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                            >
                                                ⚠️ {{ h.label }}: {{ h.status }}
                                                {{
                                                    h.uraian
                                                        ? "(" + h.uraian + ")"
                                                        : ""
                                                }}
                                            </span>
                                        </div>

                                        <!-- Catatan Tambahan -->
                                        <div
                                            v-if="
                                                extractChecklistSummary(
                                                    pet.laporan_terakhir
                                                        .data_pengecekan,
                                                )?.catatanTambahan
                                            "
                                            class="text-[11px] text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs"
                                        >
                                            <span
                                                class="font-bold text-slate-900"
                                                >Keterangan:</span
                                            >
                                            <span class="ml-1 text-slate-600">{{
                                                extractChecklistSummary(
                                                    pet.laporan_terakhir
                                                        .data_pengecekan,
                                                )?.catatanTambahan
                                            }}</span>
                                        </div>

                                        <!-- Tombol Lihat Rincian Checklist -->
                                        <div
                                            class="pt-1 flex items-center justify-between"
                                        >
                                            <span
                                                class="text-[10px] text-slate-400 font-medium"
                                            >
                                                {{
                                                    formatRelativeTime(
                                                        pet.laporan_terakhir
                                                            .relative_time,
                                                    )
                                                }}
                                            </span>

                                            <button
                                                type="button"
                                                @click="
                                                    openChecklist(
                                                        pet.laporan_terakhir,
                                                    )
                                                "
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 active:bg-indigo-200 text-indigo-700 border border-indigo-200 text-[10.5px] font-bold cursor-pointer transition-colors shadow-2xs"
                                            >
                                                <FileText class="h-3 w-3" />
                                                <span>Checklist Lengkap</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <p class="text-xs text-slate-600">
                                            <span
                                                class="font-bold text-slate-900"
                                                >Keterangan:</span
                                            >
                                            <span class="ml-1">{{
                                                pet.laporan_terakhir
                                                    .uraian_kejadian || "-"
                                            }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- TAMPILAN PETA GOOGLE STYLE TITIK TERAKHIR PETUGAS -->
                                <div class="space-y-1.5">
                                    <div
                                        class="flex items-center justify-between text-xs text-slate-500"
                                    >
                                        <span
                                            class="font-bold uppercase tracking-wider text-[10px] flex items-center gap-1.5"
                                        >
                                            <MapPin
                                                class="h-3.5 w-3.5 text-rose-500"
                                            />
                                            <span
                                                >Titik Lokasi Terkunci
                                                Terakhir</span
                                            >
                                        </span>
                                        <span
                                            v-if="
                                                pet.laporan_terakhir.latitude &&
                                                pet.laporan_terakhir.longitude
                                            "
                                            class="text-[10px] font-mono font-bold text-emerald-600"
                                        >
                                            GPS Akurat ✓
                                        </span>
                                    </div>

                                    <div
                                        v-if="
                                            pet.laporan_terakhir.latitude &&
                                            pet.laporan_terakhir.longitude
                                        "
                                    >
                                        <MiniGoogleMap
                                            :latitude="
                                                pet.laporan_terakhir.latitude
                                            "
                                            :longitude="
                                                pet.laporan_terakhir.longitude
                                            "
                                            :label="
                                                'Titik Lokasi: ' +
                                                (pet.posisi_terkini ||
                                                    pet.laporan_terakhir
                                                        .lokasi_nama ||
                                                    pet.nama)
                                            "
                                            legend-label="Lokasi Petugas"
                                            :sppg-latitude="unitSppg?.latitude"
                                            :sppg-longitude="
                                                unitSppg?.longitude
                                            "
                                            :sppg-label="
                                                unitSppg?.nama || 'Pos SPPG'
                                            "
                                            height="220px"
                                        />
                                        <div
                                            v-if="
                                                getDistanceFromSppg(
                                                    pet.laporan_terakhir
                                                        .latitude,
                                                    pet.laporan_terakhir
                                                        .longitude,
                                                )
                                            "
                                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                                        >
                                            <Navigation
                                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                                            />
                                            <span>
                                                Jarak lokasi Petugas ke SPPG:
                                                <strong
                                                    class="font-black text-emerald-950"
                                                    >{{
                                                        getDistanceFromSppg(
                                                            pet.laporan_terakhir
                                                                .latitude,
                                                            pet.laporan_terakhir
                                                                .longitude,
                                                        )
                                                    }}</strong
                                                >
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-center text-xs text-slate-400 italic"
                                    >
                                        Belum ada koordinat GPS tercatat untuk
                                        petugas ini.
                                    </div>
                                </div>
                            </template>

                            <!-- JIKA BELUM ADA LAPORAN -->
                            <template v-else>
                                <div
                                    class="h-full min-h-[260px] flex flex-col items-center justify-center p-8 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200 text-center space-y-2"
                                >
                                    <Clock
                                        class="h-8 w-8 text-slate-400 mx-auto"
                                    />
                                    <h4
                                        class="text-sm font-bold text-slate-700"
                                    >
                                        Belum Ada Laporan Terkirim
                                    </h4>
                                    <p
                                        class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed"
                                    >
                                        Petugas ini belum mengirimkan laporan
                                        patroli pengecekan jam melalui formulir
                                        mandiri smartphone hari ini.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 24 SESI PATROLI HARIAN (EXPAND & COLLAPSE) -->
            <div class="space-y-3 pt-3">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                >
                    <div>
                        <h2
                            class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                        >
                            <ListChecks class="h-4 w-4 text-blue-600" />
                            <span>Laporan Pengecekan Tiap Jam</span>
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Klik tiap sesi untuk melihat rincian lengkap laporan
                            pengecekan.
                        </p>
                    </div>

                    <!-- Tombol Kontrol Cepat: Buka Sesi Aktif, Buka Semua, Tutup Semua -->
                    <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                        <button
                            type="button"
                            @click="expandActive"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                            title="Buka rincian sesi jam yang sedang aktif saat ini"
                        >
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"
                                ></span>
                            </span>
                            <span>Buka Sesi Aktif</span>
                        </button>
                        <button
                            type="button"
                            @click="expandAll"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition-colors cursor-pointer"
                        >
                            Buka Semua
                        </button>
                        <button
                            type="button"
                            @click="collapseAll"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition-colors cursor-pointer"
                        >
                            Tutup Semua
                        </button>
                    </div>
                </div>

                <!-- DAFTAR 24 SESI -->
                <div class="space-y-2.5">
                    <div
                        v-for="sesi in props.sesiList"
                        :key="sesi.jam"
                        class="bg-white rounded-2xl border transition-all duration-200 overflow-hidden shadow-2xs"
                        :class="[
                            sesi.is_active || sesi.jam === activeSession
                                ? 'border-emerald-400 ring-2 ring-emerald-400/20 shadow-xs'
                                : sesi.report_count > 0
                                  ? 'border-slate-200 hover:border-slate-300'
                                  : 'border-slate-200/70 opacity-90',
                        ]"
                    >
                        <!-- HEADER BAR SESI (CLICKABLE UNTUK TOGGLE EXPAND/COLLAPSE) -->
                        <div
                            @click="toggleSession(sesi.jam)"
                            class="p-3 sm:p-4 flex items-center justify-between gap-2.5 cursor-pointer select-none transition-colors"
                            :class="[
                                sesi.is_active || sesi.jam === activeSession
                                    ? 'bg-emerald-50/50 hover:bg-emerald-50/80'
                                    : expandedSessions[sesi.jam]
                                      ? 'bg-slate-50/80'
                                      : 'hover:bg-slate-50/60',
                            ]"
                        >
                            <!-- Kiri: Jam Sesi & Badge Aktif -->
                            <div
                                class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1"
                            >
                                <div
                                    class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0"
                                    :class="
                                        sesi.is_active ||
                                        sesi.jam === activeSession
                                            ? 'bg-emerald-600 text-white shadow-2xs'
                                            : sesi.report_count > 0
                                              ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                              : 'bg-slate-100 text-slate-400'
                                    "
                                >
                                    <Clock class="h-4 w-4" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-center gap-1.5 sm:gap-2 flex-wrap"
                                    >
                                        <span
                                            class="font-mono font-black text-xs sm:text-sm text-slate-900 tracking-wide"
                                        >
                                            {{ sesi.jam }}
                                        </span>

                                        <span
                                            class="text-[10px] sm:text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md"
                                        >
                                            {{
                                                sesi.hari_tanggal ||
                                                formattedToday
                                            }}
                                        </span>

                                        <!-- Badge Sesi Aktif Saat Ini -->
                                        <span
                                            v-if="
                                                sesi.is_active ||
                                                sesi.jam === activeSession
                                            "
                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-white shadow-2xs shrink-0"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-white animate-ping"
                                            ></span>
                                            <span>Sesi Aktif</span>
                                        </span>
                                    </div>

                                    <!-- Nama Petugas yang Telah Mengisi -->
                                    <p
                                        v-if="sesi.petugas_names?.length > 0"
                                        class="text-[10px] sm:text-[10.5px] text-slate-500 truncate mt-0.5"
                                    >
                                        Petugas Melapor:
                                        <strong class="text-slate-700">{{
                                            sesi.petugas_names.join(", ")
                                        }}</strong>
                                    </p>
                                    <p
                                        v-else
                                        class="text-[10px] sm:text-[10.5px] text-slate-400 italic mt-0.5"
                                    >
                                        Belum ada petugas melapor
                                    </p>
                                </div>
                            </div>

                            <!-- Kanan: Status & Badge Jumlah Laporan & Chevron -->
                            <div
                                class="flex items-center gap-2 sm:gap-3 shrink-0"
                            >
                                <!-- Badge Kondisi (jika ada laporan) -->
                                <template v-if="sesi.report_count > 0">
                                    <span
                                        v-if="sesi.status === 'Tidak Aman'"
                                        class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-300"
                                    >
                                        <AlertTriangle class="h-3 w-3" />
                                        <span>Ada Kendala</span>
                                    </span>
                                    <span
                                        v-else
                                        class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <Check class="h-3 w-3" />
                                        <span>Aman</span>
                                    </span>
                                </template>

                                <!-- Badge Jumlah Laporan -->
                                <span
                                    v-if="sesi.report_count > 0"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] sm:text-[11px] font-black bg-blue-50 text-blue-800 border border-blue-200 shadow-2xs"
                                >
                                    <span>{{ sesi.report_count }} Laporan</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-[10.5px] font-medium bg-slate-100 text-slate-400"
                                >
                                    <span>0 Laporan</span>
                                </span>

                                <!-- Tombol Chevron Collapse / Expand -->
                                <div
                                    class="h-7 w-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors"
                                >
                                    <ChevronUp
                                        v-if="expandedSessions[sesi.jam]"
                                        class="h-4 w-4"
                                    />
                                    <ChevronDown v-else class="h-4 w-4" />
                                </div>
                            </div>
                        </div>

                        <!-- BODY SESI (LENGKAP SEMUA LAPORAN DI SESI INI) -->
                        <div
                            v-if="expandedSessions[sesi.jam]"
                            class="p-3.5 sm:p-5 border-t border-slate-100 bg-slate-50/40 space-y-4 animate-in fade-in duration-150"
                        >
                            <!-- JIKA ADA LAPORAN DI SESI INI -->
                            <div
                                v-if="sesi.reports && sesi.reports.length > 0"
                                class="space-y-4"
                            >
                                <div
                                    v-for="(rep, rIdx) in sesi.reports"
                                    :key="rep.id || rIdx"
                                    class="bg-white rounded-2xl border border-slate-200 p-3.5 sm:p-5 shadow-xs space-y-3"
                                >
                                    <!-- Baris Atas Laporan: Identitas Petugas, Waktu Tepat & Badge -->
                                    <div
                                        class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5 flex-wrap"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="h-6 w-6 rounded-lg bg-blue-100 text-blue-800 font-black text-xs flex items-center justify-center shrink-0"
                                            >
                                                #{{ rIdx + 1 }}
                                            </span>
                                            <div>
                                                <span
                                                    class="font-extrabold text-slate-900 text-xs sm:text-sm block"
                                                >
                                                    {{
                                                        rep.petugas_nama ||
                                                        "Petugas Keamanan"
                                                    }}
                                                </span>
                                                <span
                                                    class="text-[10.5px] text-slate-500 font-mono block"
                                                >
                                                    Waktu Rekam:
                                                    <strong
                                                        class="text-slate-800"
                                                        >{{
                                                            rep.hari_tanggal ||
                                                            formatIndoDate(
                                                                rep.tanggal,
                                                            )
                                                        }}
                                                        Pukul
                                                        {{
                                                            rep.waktu_cek || "-"
                                                        }}
                                                        WITA</strong
                                                    >
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Badge Cuaca & Status Kondisi -->
                                        <div
                                            class="flex items-center gap-1.5 flex-wrap"
                                        >
                                            <span
                                                v-if="
                                                    rep.data_pengecekan?.cuaca
                                                "
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border"
                                                :class="
                                                    getCuacaBadge(
                                                        rep.data_pengecekan
                                                            .cuaca,
                                                    ).color
                                                "
                                            >
                                                <component
                                                    :is="
                                                        getCuacaBadge(
                                                            rep.data_pengecekan
                                                                .cuaca,
                                                        ).icon
                                                    "
                                                    class="h-3 w-3 shrink-0"
                                                />
                                                <span>{{
                                                    rep.data_pengecekan.cuaca
                                                }}</span>
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10.5px] font-black border"
                                                :class="
                                                    rep.kondisi === 'Tidak Aman'
                                                        ? 'bg-rose-50 text-rose-700 border-rose-300'
                                                        : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                "
                                            >
                                                <AlertTriangle
                                                    v-if="
                                                        rep.kondisi ===
                                                        'Tidak Aman'
                                                    "
                                                    class="h-3 w-3"
                                                />
                                                <Check v-else class="h-3 w-3" />
                                                <span>{{ rep.kondisi }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Status 21 Area & Pengecekan Umum -->
                                    <div
                                        v-if="rep.data_pengecekan"
                                        class="space-y-2 text-xs"
                                    >
                                        <div
                                            class="flex items-center justify-between text-[11px] p-2 rounded-xl bg-slate-50 border border-slate-200"
                                        >
                                            <span
                                                class="text-slate-600 font-medium"
                                                >Pengecekan Area Kerja:</span
                                            >
                                            <span
                                                v-if="
                                                    extractChecklistSummary(
                                                        rep.data_pengecekan,
                                                    )?.allAreasSafe
                                                "
                                                class="text-emerald-700 font-bold"
                                            >
                                                ✓ Seluruh Area Kerja Aman
                                            </span>
                                            <span
                                                v-else
                                                class="text-rose-700 font-bold"
                                            >
                                                ⚠️
                                                {{
                                                    extractChecklistSummary(
                                                        rep.data_pengecekan,
                                                    )?.unsafeAreas.length
                                                }}
                                                Area Bermasalah:
                                                {{
                                                    extractChecklistSummary(
                                                        rep.data_pengecekan,
                                                    )?.unsafeAreas.join(", ")
                                                }}
                                            </span>
                                        </div>

                                        <!-- Highlights Umum -->
                                        <div
                                            v-if="
                                                extractChecklistSummary(
                                                    rep.data_pengecekan,
                                                )?.highlights.length > 0
                                            "
                                            class="flex flex-wrap gap-1"
                                        >
                                            <span
                                                v-for="(
                                                    h, hIdx
                                                ) in extractChecklistSummary(
                                                    rep.data_pengecekan,
                                                )?.highlights"
                                                :key="hIdx"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                            >
                                                ⚠️ {{ h.label }}: {{ h.status }}
                                                {{
                                                    h.uraian
                                                        ? "(" + h.uraian + ")"
                                                        : ""
                                                }}
                                            </span>
                                        </div>

                                        <!-- Catatan Tambahan Petugas -->
                                        <div
                                            v-if="
                                                extractChecklistSummary(
                                                    rep.data_pengecekan,
                                                )?.catatanTambahan
                                            "
                                            class="text-[11px] text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-200"
                                        >
                                            <span
                                                class="font-bold text-slate-900"
                                                >Keterangan:</span
                                            >
                                            <span class="ml-1 text-slate-600">{{
                                                extractChecklistSummary(
                                                    rep.data_pengecekan,
                                                )?.catatanTambahan
                                            }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-slate-600">
                                        <span class="font-bold text-slate-900"
                                            >Keterangan:</span
                                        >
                                        <span class="ml-1">{{
                                            rep.uraian_kejadian ||
                                            "Situasi aman terkendali."
                                        }}</span>
                                    </div>

                                    <!-- Lokasi & Peta Google Mini -->
                                    <div
                                        class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                    >
                                        <div
                                            class="flex items-center gap-1.5 text-xs text-slate-700"
                                        >
                                            <MapPin
                                                class="h-3.5 w-3.5 text-rose-500 shrink-0"
                                            />
                                            <span
                                                >Posisi:
                                                <strong>{{
                                                    rep.lokasi_nama ||
                                                    "Pos Penjagaan SPPG"
                                                }}</strong></span
                                            >
                                            <span
                                                v-if="
                                                    rep.latitude &&
                                                    rep.longitude
                                                "
                                                class="text-[10px] text-slate-400 font-mono"
                                            >
                                                ({{ rep.latitude.toFixed(4) }},
                                                {{ rep.longitude.toFixed(4) }})
                                            </span>
                                        </div>

                                        <button
                                            type="button"
                                            @click="openChecklist(rep)"
                                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 active:bg-indigo-200 text-indigo-700 border border-indigo-200 text-[11px] font-bold cursor-pointer transition-colors shadow-2xs self-start sm:self-auto"
                                        >
                                            <FileText class="h-3.5 w-3.5" />
                                            <span>Lihat Checklist</span>
                                        </button>
                                    </div>

                                    <!-- Peta Google Mini Lokasi Pengecekan -->
                                    <div
                                        v-if="rep.latitude && rep.longitude"
                                        class="pt-1"
                                    >
                                        <MiniGoogleMap
                                            :latitude="rep.latitude"
                                            :longitude="rep.longitude"
                                            :label="
                                                'Titik Lokasi Pengecekan: ' +
                                                (rep.lokasi_nama ||
                                                    'Pos Penjagaan')
                                            "
                                            legend-label="Lokasi Petugas"
                                            :sppg-latitude="unitSppg?.latitude"
                                            :sppg-longitude="
                                                unitSppg?.longitude
                                            "
                                            :sppg-label="
                                                unitSppg?.nama || 'Pos SPPG'
                                            "
                                            height="210px"
                                        />
                                        <div
                                            v-if="
                                                getDistanceFromSppg(
                                                    rep.latitude,
                                                    rep.longitude,
                                                )
                                            "
                                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 shadow-2xs"
                                        >
                                            <Navigation
                                                class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                                            />
                                            <span>
                                                Jarak lokasi Petugas ke SPPG:
                                                <strong
                                                    class="font-black text-emerald-950"
                                                    >{{
                                                        getDistanceFromSppg(
                                                            rep.latitude,
                                                            rep.longitude,
                                                        )
                                                    }}</strong
                                                >
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- JIKA BELUM ADA LAPORAN DI SESI INI -->
                            <div
                                v-else
                                class="p-6 bg-white rounded-2xl border border-dashed border-slate-200 text-center space-y-1.5"
                            >
                                <Clock class="h-6 w-6 text-slate-300 mx-auto" />
                                <p class="text-xs font-bold text-slate-600">
                                    Belum Ada Laporan di Sesi {{ sesi.jam }}
                                </p>
                                <p
                                    class="text-[11px] text-slate-400 max-w-sm mx-auto"
                                >
                                    Laporan yang dikirimkan oleh petugas jaga
                                    melalui formulir mandiri pada rentang jam
                                    ini akan otomatis tersinkron dan tampil di
                                    sini.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL DETAIL CHECKLIST 21 AREA -->
        <KeamananChecklistModal
            :show="showChecklistModal"
            :detail="activeChecklistDetail"
            @close="showChecklistModal = false"
        />
    </div>
</template>
