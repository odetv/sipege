<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    CalendarDays,
    CalendarRange,
    ChevronLeft,
    ChevronRight,
    Utensils,
    CalendarCheck,
    CheckCircle2,
    Clock,
    Plus,
    UtensilsCrossed,
    X,
    Calendar,
    Flame,
    Coins,
    Layers,
    Users,
    FileText,
    ExternalLink,
    Sparkles,
} from "lucide-vue-next";

const props = defineProps({
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (num) => {
            const val = Number(num);
            if (!val || isNaN(val) || val <= 0) return "Rp 0";
            if (Number.isInteger(val)) {
                return "Rp " + val.toLocaleString("id-ID");
            }
            if (val < 1) {
                const decimals = val < 0.01 ? 3 : 2;
                return (
                    "Rp " +
                    val.toLocaleString("id-ID", {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: decimals,
                    })
                );
            }
            return (
                "Rp " +
                val.toLocaleString("id-ID", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                })
            );
        },
    },
    formatTanggalIndo: {
        type: Function,
        default: (tgl) => {
            if (!tgl) return "-";
            try {
                const d = new Date(tgl);
                return d.toLocaleDateString("id-ID", {
                    weekday: "long",
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                });
            } catch {
                return tgl;
            }
        },
    },
});

const emit = defineEmits(["openRancangMenu"]);

const MONTH_NAMES = [
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

const DAY_NAMES = [
    "Senin",
    "Selasa",
    "Rabu",
    "Kamis",
    "Jumat",
    "Sabtu",
    "Minggu",
];

// ─── Mode Tampilan: 'periode' (Default Sesuai Permintaan) atau 'bulanan' ───
const viewMode = ref("periode");
const selectedPeriodeId = ref(null);
const selectedKalenderItem = ref(null);

// Urutkan periode dari yang paling baru (nomor_periode tertinggi / tanggal_selesai terbaru)
const sortedPeriodesDesc = computed(() => {
    return [...(props.periodes || [])].sort((a, b) => {
        const nA = a.nomor_periode ?? 0;
        const nB = b.nomor_periode ?? 0;
        if (nB !== nA) return nB - nA;
        return (b.tanggal_selesai || "").localeCompare(a.tanggal_selesai || "");
    });
});

// Periode terbaru
const latestPeriode = computed(() => {
    if (!sortedPeriodesDesc.value.length) return null;
    return sortedPeriodesDesc.value[0];
});

// Periode yang sedang aktif dilihat
const currentPeriode = computed(() => {
    if (!props.periodes || !props.periodes.length) return null;
    if (selectedPeriodeId.value) {
        const found = props.periodes.find(
            (p) => String(p.id) === String(selectedPeriodeId.value)
        );
        if (found) return found;
    }
    return latestPeriode.value;
});

// Otomatis arahkan ke periode terbaru saat data periode dimuat
watch(
    () => props.periodes,
    (newList) => {
        if (newList && newList.length > 0) {
            if (!selectedPeriodeId.value) {
                const latest = [...newList].sort((a, b) => {
                    const nA = a.nomor_periode ?? 0;
                    const nB = b.nomor_periode ?? 0;
                    if (nB !== nA) return nB - nA;
                    return (b.tanggal_selesai || "").localeCompare(a.tanggal_selesai || "");
                })[0];
                if (latest) {
                    selectedPeriodeId.value = latest.id;
                }
            }
        } else {
            viewMode.value = "bulanan";
        }
    },
    { immediate: true }
);

// Navigasi Periode (Sebelumnya & Berikutnya)
const currentPeriodeIndex = computed(() => {
    if (!currentPeriode.value || !sortedPeriodesDesc.value.length) return -1;
    return sortedPeriodesDesc.value.findIndex(
        (p) => String(p.id) === String(currentPeriode.value.id)
    );
});

const hasNewerPeriode = computed(() => currentPeriodeIndex.value > 0);
const hasOlderPeriode = computed(
    () =>
        currentPeriodeIndex.value >= 0 &&
        currentPeriodeIndex.value < sortedPeriodesDesc.value.length - 1
);

function goToNewerPeriode() {
    if (hasNewerPeriode.value) {
        selectedPeriodeId.value =
            sortedPeriodesDesc.value[currentPeriodeIndex.value - 1].id;
    }
}

function goToOlderPeriode() {
    if (hasOlderPeriode.value) {
        selectedPeriodeId.value =
            sortedPeriodesDesc.value[currentPeriodeIndex.value + 1].id;
    }
}

function goToLatestPeriode() {
    if (latestPeriode.value) {
        selectedPeriodeId.value = latestPeriode.value.id;
    }
}

function formatPeriodeRange(mulai, selesai) {
    if (!mulai || !selesai) return "—";
    const [ym, mm, dm] = mulai.split("-");
    const [ys, ms, ds] = selesai.split("-");
    const mNames = [
        "", "Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"
    ];
    if (mm === ms && ym === ys) {
        return `${parseInt(dm)}–${parseInt(ds)} ${mNames[parseInt(mm)]} ${ym}`;
    }
    return `${parseInt(dm)} ${mNames[parseInt(mm)]} – ${parseInt(ds)} ${mNames[parseInt(ms)]} ${ys}`;
}

const periodeStatusConfig = {
    aktif:       { label: "Aktif",        bg: "bg-emerald-50", text: "text-emerald-700", border: "border-emerald-200", dot: "bg-emerald-500" },
    selesai:     { label: "Selesai",      bg: "bg-slate-100",  text: "text-slate-600",   border: "border-slate-200",   dot: "bg-slate-400" },
    akan_datang: { label: "Akan Datang",  bg: "bg-blue-50",    text: "text-blue-700",    border: "border-blue-200",    dot: "bg-blue-400" },
};

// ─── State Mode Bulanan (Fallback/Alternatif) ───
const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth()); // 0 - 11

// Watch workOrdersList to automatically jump to the latest work order month if available in monthly mode
watch(
    () => props.workOrdersList,
    (newList) => {
        if (newList && newList.length > 0) {
            const latest = newList[0]?.tanggal_distribusi;
            if (latest) {
                const d = new Date(latest);
                if (!isNaN(d.getTime())) {
                    currentYear.value = d.getFullYear();
                    currentMonth.value = d.getMonth();
                }
            }
        }
    },
    { immediate: true }
);

function prevMonth() {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

function nextMonth() {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
}

function goToToday() {
    const today = new Date();
    currentYear.value = today.getFullYear();
    currentMonth.value = today.getMonth();
}

// ─── Map work orders into dictionary by 'YYYY-MM-DD' ───
const workOrdersMap = computed(() => {
    const map = {};
    (props.workOrdersList || []).forEach((wo) => {
        if (!wo || !wo.tanggal_distribusi) return;
        const dateStr =
            typeof wo.tanggal_distribusi === "string"
                ? wo.tanggal_distribusi.substring(0, 10)
                : new Date(wo.tanggal_distribusi)
                      .toISOString()
                      .substring(0, 10);

        if (!map[dateStr]) {
            map[dateStr] = [];
        }

        const komponenList = [];
        if (wo.items && wo.items.length > 0) {
            wo.items.forEach((it) => {
                const name = it.nama_po || it.nama || it.bahan_nama;
                if (name && !komponenList.includes(name))
                    komponenList.push(name);
            });
        } else {
            [
                wo.sub_menu_1 ?? wo.komponen_energi,
                wo.sub_menu_2 ?? wo.komponen_protein,
                wo.sub_menu_3 ?? wo.komponen_lemak,
                wo.sub_menu_4 ?? wo.komponen_karbohidrat,
                wo.sub_menu_5 ?? wo.komponen_serat,
            ]
                .filter(Boolean)
                .forEach((k) => komponenList.push(k));
        }

        const subMenus = [
            { label: "Sub Menu 1", val: wo.sub_menu_1 },
            { label: "Sub Menu 2", val: wo.sub_menu_2 },
            { label: "Sub Menu 3", val: wo.sub_menu_3 },
            { label: "Sub Menu 4", val: wo.sub_menu_4 },
            { label: "Sub Menu 5", val: wo.sub_menu_5 },
        ].filter((s) => s.val && s.val.trim());

        map[dateStr].push({
            id: wo.nomor_wo,
            db_id: wo.id,
            uuid: wo.uuid || wo.id,
            nomor_wo: wo.nomor_wo,
            tanggal: dateStr,
            namaMenu: wo.nama_menu || "Menu Makan Bergizi Gratis",
            siklusKe: wo.siklus_ke || 1,
            status: wo.status || "Draft",
            kaloriPK: wo.akg_pk?.energi ? Math.round(wo.akg_pk.energi) : null,
            kaloriPB: wo.akg_pb?.energi ? Math.round(wo.akg_pb.energi) : null,
            proteinPK: wo.akg_pk?.protein
                ? Number(wo.akg_pk.protein).toFixed(1)
                : null,
            proteinPB: wo.akg_pb?.protein
                ? Number(wo.akg_pb.protein).toFixed(1)
                : null,
            costPK: Number(wo.food_cost_pk) || 0,
            costPB: Number(wo.food_cost_pb) || 0,
            totalPorsi:
                (Number(wo.total_porsi_kecil) || 0) +
                (Number(wo.total_porsi_besar) || 0),
            porsiPK: Number(wo.total_porsi_kecil) || 0,
            porsiPB: Number(wo.total_porsi_besar) || 0,
            komponen: komponenList.length > 0 ? komponenList : ["Menu MBG"],
            subMenus: subMenus,
            sub_menu_1: wo.sub_menu_1 || "",
            sub_menu_2: wo.sub_menu_2 || "",
            sub_menu_3: wo.sub_menu_3 || "",
            sub_menu_4: wo.sub_menu_4 || "",
            sub_menu_5: wo.sub_menu_5 || "",
            raw: wo,
        });
    });
    return map;
});

// ─── 1. Perhitungan Grid Kalender Mode Periode (Senin - Minggu) ───
const periodeCalendarDays = computed(() => {
    if (
        !currentPeriode.value ||
        !currentPeriode.value.tanggal_mulai ||
        !currentPeriode.value.tanggal_selesai
    ) {
        return [];
    }

    const startStr = currentPeriode.value.tanggal_mulai;
    const endStr = currentPeriode.value.tanggal_selesai;

    const startDate = new Date(startStr + "T00:00:00");
    const endDate = new Date(endStr + "T00:00:00");

    // Align grid start ke hari Senin (Senin=0, Minggu=6)
    let startDayOfWeek = startDate.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const gridStartDate = new Date(startDate);
    gridStartDate.setDate(gridStartDate.getDate() - startDayOfWeek);

    // Align grid end ke hari Minggu
    let endDayOfWeek = endDate.getDay() - 1;
    if (endDayOfWeek === -1) endDayOfWeek = 6;
    const daysToAdd = 6 - endDayOfWeek;

    const gridEndDate = new Date(endDate);
    gridEndDate.setDate(gridEndDate.getDate() + daysToAdd);

    const days = [];
    const todayStr = new Date().toISOString().substring(0, 10);
    const cur = new Date(gridStartDate);

    while (cur <= gridEndDate) {
        const y = cur.getFullYear();
        const m = String(cur.getMonth() + 1).padStart(2, "0");
        const d = String(cur.getDate()).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;

        const isInPeriode = dateStr >= startStr && dateStr <= endStr;
        const menusOnDate = workOrdersMap.value[dateStr] || [];

        days.push({
            date: new Date(cur),
            dateStr: dateStr,
            dayNumber: cur.getDate(),
            monthIndex: cur.getMonth(),
            monthName: MONTH_NAMES[cur.getMonth()],
            year: y,
            isInPeriode: isInPeriode,
            isCurrentMonth: cur.getMonth() === startDate.getMonth(),
            isToday: dateStr === todayStr,
            menus: menusOnDate,
            hasMenu: menusOnDate.length > 0,
        });

        cur.setDate(cur.getDate() + 1);
    }

    return days;
});

// ─── 2. Perhitungan Grid Kalender Mode Bulanan (Senin - Minggu) ───
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const days = [];
    const todayStr = new Date().toISOString().substring(0, 10);

    // 1. Previous month days (padding)
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const dayNum = prevMonthLastDay - i;
        const prevDate = new Date(year, month - 1, dayNum);
        const y = prevDate.getFullYear();
        const m = String(prevDate.getMonth() + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const menusOnDate = workOrdersMap.value[dateStr] || [];

        days.push({
            date: prevDate,
            dateStr: dateStr,
            dayNumber: dayNum,
            monthIndex: prevDate.getMonth(),
            monthName: MONTH_NAMES[prevDate.getMonth()],
            year: y,
            isInPeriode: true,
            isCurrentMonth: false,
            isPrevMonth: true,
            isNextMonth: false,
            isToday: dateStr === todayStr,
            menus: menusOnDate,
            hasMenu: menusOnDate.length > 0,
        });
    }

    // 2. Current month days
    const totalDaysInMonth = lastDay.getDate();
    for (let dayNum = 1; dayNum <= totalDaysInMonth; dayNum++) {
        const curDate = new Date(year, month, dayNum);
        const y = year;
        const m = String(month + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const menusOnDate = workOrdersMap.value[dateStr] || [];

        days.push({
            date: curDate,
            dateStr: dateStr,
            dayNumber: dayNum,
            monthIndex: month,
            monthName: MONTH_NAMES[month],
            year: y,
            isInPeriode: true,
            isCurrentMonth: true,
            isPrevMonth: false,
            isNextMonth: false,
            isToday: dateStr === todayStr,
            menus: menusOnDate,
            hasMenu: menusOnDate.length > 0,
        });
    }

    // 3. Next month days (padding)
    const remainingDays = (7 - (days.length % 7)) % 7;
    for (let dayNum = 1; dayNum <= remainingDays; dayNum++) {
        const nextDate = new Date(year, month + 1, dayNum);
        const y = nextDate.getFullYear();
        const m = String(nextDate.getMonth() + 1).padStart(2, "0");
        const d = String(dayNum).padStart(2, "0");
        const dateStr = `${y}-${m}-${d}`;
        const menusOnDate = workOrdersMap.value[dateStr] || [];

        days.push({
            date: nextDate,
            dateStr: dateStr,
            dayNumber: dayNum,
            monthIndex: nextDate.getMonth(),
            monthName: MONTH_NAMES[nextDate.getMonth()],
            year: y,
            isInPeriode: true,
            isCurrentMonth: false,
            isPrevMonth: false,
            isNextMonth: true,
            isToday: dateStr === todayStr,
            menus: menusOnDate,
            hasMenu: menusOnDate.length > 0,
        });
    }

    return days;
});

// ─── Statistik Periode Terpilih ───
const currentPeriodeStats = computed(() => {
    if (!currentPeriode.value) {
        return {
            totalMenus: 0,
            totalPorsi: 0,
            totalPK: 0,
            totalPB: 0,
            completed: 0,
            ready: 0,
            draft: 0,
            totalHari: 0,
        };
    }
    const startStr = currentPeriode.value.tanggal_mulai;
    const endStr = currentPeriode.value.tanggal_selesai;

    let totalMenus = 0;
    let totalPorsi = 0;
    let totalPK = 0;
    let totalPB = 0;
    let completed = 0;
    let ready = 0;
    let draft = 0;

    Object.entries(workOrdersMap.value).forEach(([tgl, menus]) => {
        if (tgl >= startStr && tgl <= endStr) {
            menus.forEach((m) => {
                totalMenus++;
                totalPorsi += m.totalPorsi;
                totalPK += m.porsiPK;
                totalPB += m.porsiPB;
                if (m.status === "Selesai") completed++;
                else if (
                    m.status === "Siap Produksi" ||
                    m.status === "Terkonfirmasi"
                )
                    ready++;
                else draft++;
            });
        }
    });

    const start = new Date(startStr + "T00:00:00");
    const end = new Date(endStr + "T00:00:00");
    const diffTime = Math.abs(end - start);
    const totalHari = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;

    return {
        totalMenus,
        totalPorsi,
        totalPK,
        totalPB,
        completed,
        ready,
        draft,
        totalHari,
    };
});

// ─── Statistik Bulan Terpilih (Mode Bulanan) ───
const monthStats = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    const yStr = String(year);
    const mStr = String(month + 1).padStart(2, "0");
    const prefix = `${yStr}-${mStr}`;

    let totalMenuCount = 0;
    let totalPorsiCount = 0;
    let totalPkCount = 0;
    let totalPbCount = 0;
    let completedCount = 0;
    let readyCount = 0;
    let draftCount = 0;

    Object.entries(workOrdersMap.value).forEach(([tgl, menus]) => {
        if (tgl.startsWith(prefix)) {
            menus.forEach((m) => {
                totalMenuCount++;
                totalPorsiCount += m.totalPorsi;
                totalPkCount += m.porsiPK;
                totalPbCount += m.porsiPB;
                if (m.status === "Selesai") completedCount++;
                else if (
                    m.status === "Siap Produksi" ||
                    m.status === "Terkonfirmasi"
                )
                    readyCount++;
                else draftCount++;
            });
        }
    });

    return {
        totalMenus: totalMenuCount,
        totalPorsi: totalPorsiCount,
        totalPK: totalPkCount,
        totalPB: totalPbCount,
        completed: completedCount,
        ready: readyCount,
        draft: draftCount,
    };
});

// Active display computed
const displayedDays = computed(() => {
    return viewMode.value === "periode" && currentPeriode.value
        ? periodeCalendarDays.value
        : calendarDays.value;
});

const displayedStats = computed(() => {
    return viewMode.value === "periode" && currentPeriode.value
        ? currentPeriodeStats.value
        : monthStats.value;
});

function handleCellClick(cell) {
    if (cell.hasMenu && cell.menus.length > 0) {
        selectedKalenderItem.value = cell.menus[0];
    } else if (
        viewMode.value === "periode"
            ? cell.isInPeriode
            : cell.isCurrentMonth
    ) {
        router.visit("/gizi/rancang-menu?tanggal=" + cell.dateStr);
    }
}

function handleOpenMenuInRancang(item) {
    if (item && (item.uuid || item.db_id)) {
        router.visit("/gizi/rancang-menu?wo_id=" + (item.uuid || item.db_id));
    } else {
        emit("openRancangMenu");
    }
}
</script>

<template>
    <div class="space-y-5">
        <!-- Header & Navigasi Kalender Utama -->
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/60"
            >
                <div
                    class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                >
                    <div>
                        <CardTitle
                            class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                        >
                            <CalendarDays class="h-5 w-5 text-primary" />
                            <span>Kalender Siklus Menu Harian MBG SPPG</span>
                        </CardTitle>
                        <CardDescription class="text-xs sm:text-sm mt-0.5">
                            Jadwal rotasi kalender menu riil, distribusi harian,
                            status produksi, dan review detail paket menu.
                        </CardDescription>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <!-- Mode Switcher: Periode vs Bulanan -->
                        <div class="inline-flex rounded-xl bg-slate-100/90 p-1 border border-slate-200/80 shadow-2xs">
                            <button
                                type="button"
                                @click="viewMode = 'periode'"
                                :class="[
                                    'px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 cursor-pointer',
                                    viewMode === 'periode'
                                        ? 'bg-white text-primary shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <CalendarRange class="h-3.5 w-3.5" />
                                <span>Tampilan Periode</span>
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'bulanan'"
                                :class="[
                                    'px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5 cursor-pointer',
                                    viewMode === 'bulanan'
                                        ? 'bg-white text-primary shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <CalendarDays class="h-3.5 w-3.5" />
                                <span>Tampilan Bulanan</span>
                            </button>
                        </div>

                        <!-- Summary Badges -->
                        <div class="flex items-center gap-2">
                            <span
                                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs flex items-center gap-1.5"
                            >
                                <Utensils class="h-3.5 w-3.5 text-blue-600" />
                                <span>{{ displayedStats.totalMenus }} Menu Terjadwal</span>
                            </span>
                            <span
                                v-if="displayedStats.totalPorsi > 0"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs flex items-center gap-1.5"
                            >
                                <Users class="h-3.5 w-3.5 text-emerald-600" />
                                <span>
                                    {{ displayedStats.totalPorsi.toLocaleString("id-ID") }} Porsi
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="p-4 sm:p-5 space-y-4">
                <!-- ═════════ TOOLBAR 1: MODE PERIODE (DEFAULT) ═════════ -->
                <div
                    v-if="viewMode === 'periode'"
                    class="space-y-3 pb-3 border-b border-slate-100"
                >
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Info Periode Aktif / Terpilih -->
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20 shadow-xs">
                                <span class="text-base font-black">P{{ currentPeriode?.nomor_periode }}</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                                        Periode {{ currentPeriode?.nomor_periode }}
                                    </h3>
                                    <!-- Status Badge -->
                                    <span
                                        v-if="currentPeriode"
                                        :class="[
                                            'px-2.5 py-0.5 text-xs font-bold rounded-lg border flex items-center gap-1.5 shadow-2xs',
                                            periodeStatusConfig[currentPeriode.status]?.bg || 'bg-slate-50',
                                            periodeStatusConfig[currentPeriode.status]?.text || 'text-slate-600',
                                            periodeStatusConfig[currentPeriode.status]?.border || 'border-slate-200'
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="periodeStatusConfig[currentPeriode.status]?.dot || 'bg-slate-400'" />
                                        {{ periodeStatusConfig[currentPeriode.status]?.label || currentPeriode.status }}
                                    </span>
                                    <span
                                        v-if="currentPeriode?.id === latestPeriode?.id"
                                        class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded-md bg-primary text-white shadow-2xs"
                                    >
                                        Terbaru
                                    </span>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
                                    <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                    <span>{{ formatPeriodeRange(currentPeriode?.tanggal_mulai, currentPeriode?.tanggal_selesai) }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-primary font-bold">{{ currentPeriodeStats.totalHari }} Hari ({{ Math.round(currentPeriodeStats.totalHari / 7) }} Minggu)</span>
                                </p>
                            </div>
                        </div>

                        <!-- Periode Selector & Nav Buttons -->
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Dropdown Pilih Periode -->
                            <div class="relative">
                                <select
                                    v-model="selectedPeriodeId"
                                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 pr-8 text-xs font-semibold text-slate-700 shadow-2xs hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition cursor-pointer"
                                >
                                    <option
                                        v-for="p in sortedPeriodesDesc"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        Periode {{ p.nomor_periode }} ({{ formatPeriodeRange(p.tanggal_mulai, p.tanggal_selesai) }}) {{ p.id === latestPeriode?.id ? '★ Terbaru' : (p.status === 'aktif' ? '● Aktif' : '') }}
                                    </option>
                                </select>
                            </div>

                            <!-- Tombol Lompat ke Periode Terbaru jika belum di terbaru -->
                            <Button
                                v-if="currentPeriode?.id !== latestPeriode?.id"
                                type="button"
                                @click="goToLatestPeriode"
                                className="h-9 px-3 bg-primary/10 border border-primary/20 text-primary hover:bg-primary/20 text-xs font-bold rounded-xl cursor-pointer transition shadow-2xs"
                            >
                                <Sparkles class="h-3.5 w-3.5 mr-1" />
                                Periode Terbaru
                            </Button>

                            <!-- Stepper Prev / Next Periode -->
                            <div class="flex items-center gap-1">
                                <Button
                                    type="button"
                                    @click="goToOlderPeriode"
                                    :disabled="!hasOlderPeriode"
                                    className="h-9 px-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl flex items-center gap-1 cursor-pointer shadow-2xs disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold"
                                    title="Periode Sebelumnya"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                    <span class="hidden sm:inline">Sebelumnya</span>
                                </Button>
                                <Button
                                    type="button"
                                    @click="goToNewerPeriode"
                                    :disabled="!hasNewerPeriode"
                                    className="h-9 px-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl flex items-center gap-1 cursor-pointer shadow-2xs disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold"
                                    title="Periode Berikutnya"
                                >
                                    <span class="hidden sm:inline">Berikutnya</span>
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Status Ringkas Periode -->
                    <div class="flex items-center gap-2 flex-wrap pt-1 text-xs">
                        <span class="text-slate-400 font-medium">Status Menu Periode ini:</span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200/80">
                            {{ currentPeriodeStats.ready }} Siap Produksi
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 font-semibold border border-amber-200/80">
                            {{ currentPeriodeStats.draft }} Draft
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold border border-slate-200">
                            {{ currentPeriodeStats.completed }} Selesai
                        </span>
                    </div>
                </div>

                <!-- ═════════ TOOLBAR 2: MODE BULANAN (ALTERNATIF) ═════════ -->
                <div
                    v-else
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100"
                >
                    <div class="flex items-center gap-2.5">
                        <h3
                            class="text-lg sm:text-xl font-black text-slate-900 tracking-tight"
                        >
                            {{ MONTH_NAMES[currentMonth] }} {{ currentYear }}
                        </h3>
                        <Badge
                            variant="outline"
                            class="bg-primary/5 text-primary border-primary/20 text-xs font-bold"
                        >
                            {{ monthStats.totalMenus }} Menu Aktif
                        </Badge>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            @click="goToToday"
                            className="h-8 px-3 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold rounded-xl cursor-pointer"
                        >
                            Hari Ini
                        </Button>
                        <div class="flex items-center gap-1">
                            <Button
                                type="button"
                                @click="prevMonth"
                                className="h-8 w-8 p-0 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl flex items-center justify-center cursor-pointer shadow-2xs"
                                title="Bulan Sebelumnya"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                @click="nextMonth"
                                className="h-8 w-8 p-0 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl flex items-center justify-center cursor-pointer shadow-2xs"
                                title="Bulan Berikutnya"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Legend Visual -->
                <div
                    class="flex items-center gap-4 text-xs text-slate-500 flex-wrap pt-1"
                >
                    <div class="flex items-center gap-1.5">
                        <span
                            class="h-3 w-3 rounded-md bg-white border-2 border-primary shadow-2xs"
                        ></span>
                        <span class="font-medium"
                            >Ada Jadwal Menu (Klik untuk Review)</span
                        >
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span
                            class="h-3 w-3 rounded-md border border-slate-300 bg-[repeating-linear-gradient(45deg,#f8fafc,#f8fafc_3px,#e2e8f0_3px,#e2e8f0_6px)]"
                        ></span>
                        <span class="font-medium">Belum Ada Menu (Klik untuk Buat Menu)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span
                            class="h-3 w-3 rounded-md bg-blue-100 border border-blue-400"
                        ></span>
                        <span class="font-medium">Hari Ini</span>
                    </div>
                </div>

                <!-- Grid Kalender (7 Kolom: Senin - Minggu) -->
                <div
                    class="border border-slate-200 rounded-2xl overflow-hidden shadow-2xs bg-slate-100/60"
                >
                    <!-- Header Hari -->
                    <div
                        class="grid grid-cols-7 bg-slate-100 border-b border-slate-200 text-center text-xs font-bold text-slate-700 select-none"
                    >
                        <div
                            v-for="(dayName, dIdx) in DAY_NAMES"
                            :key="dIdx"
                            :class="[
                                'py-2.5 px-1 border-r border-slate-200 last:border-r-0',
                                dIdx >= 5
                                    ? 'text-rose-600 bg-rose-50/40'
                                    : 'text-slate-700',
                            ]"
                        >
                            {{ dayName }}
                        </div>
                    </div>

                    <!-- Grid Tanggal -->
                    <div class="grid grid-cols-7 gap-px bg-slate-200">
                        <div
                            v-for="(cell, cIdx) in displayedDays"
                            :key="cIdx"
                            @click="handleCellClick(cell)"
                            :class="[
                                'min-h-[120px] sm:min-h-[140px] p-2 sm:p-2.5 transition-all flex flex-col justify-between relative group',
                                // Di luar periode pada Mode Periode
                                viewMode === 'periode' && !cell.isInPeriode
                                    ? 'bg-slate-100/60 opacity-30 select-none cursor-default'
                                    : cell.hasMenu
                                      ? [
                                            'bg-white cursor-pointer hover:bg-blue-50/40 hover:ring-2 hover:ring-primary/40 hover:z-10 shadow-2xs',
                                            cell.isToday
                                                ? 'ring-2 ring-blue-500 bg-blue-50/30'
                                                : '',
                                        ]
                                      : [
                                            // Tanggal Kosong
                                            'bg-[repeating-linear-gradient(45deg,#f8fafc,#f8fafc_8px,#f1f5f9_8px,#f1f5f9_16px)] cursor-pointer select-none opacity-85 hover:opacity-100 hover:bg-slate-50',
                                            viewMode === 'bulanan' && !cell.isCurrentMonth
                                                ? 'opacity-40'
                                                : '',
                                        ],
                            ]"
                        >
                            <!-- Baris Atas Kotak Tanggal: No Tanggal & Badge Status/Siklus -->
                            <div class="flex items-start justify-between gap-1">
                                <span
                                    :class="[
                                        'h-7 min-w-[28px] px-1.5 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 transition-colors',
                                        cell.isToday
                                            ? 'bg-blue-600 text-white shadow-xs font-black'
                                            : cell.hasMenu
                                              ? 'bg-slate-900 text-white font-extrabold shadow-2xs'
                                              : (viewMode === 'periode' ? cell.isInPeriode : cell.isCurrentMonth)
                                                ? 'text-slate-600 font-bold bg-white/90 border border-slate-200/70'
                                                : 'text-slate-300 font-normal',
                                    ]"
                                >
                                    {{ cell.dayNumber }}<span v-if="viewMode === 'periode' && cell.isInPeriode" class="text-[10px] ml-0.5 opacity-70">{{ cell.monthName?.substring(0, 3) }}</span>
                                </span>

                                <!-- Badges jika ada menu -->
                                <div
                                    v-if="cell.hasMenu"
                                    class="flex flex-col items-end gap-1"
                                >
                                    <span
                                        class="px-1.5 py-0.5 text-[9.5px] font-black rounded-md bg-blue-50 text-blue-800 border border-blue-200 shrink-0"
                                    >
                                        Siklus {{ cell.menus[0]?.siklusKe }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-1.5 py-0.5 text-[9px] font-extrabold rounded-md border shrink-0',
                                            cell.menus[0]?.status === 'Selesai'
                                                ? 'bg-slate-100 text-slate-700 border-slate-200'
                                                : cell.menus[0]?.status ===
                                                        'Siap Produksi' ||
                                                    cell.menus[0]?.status ===
                                                        'Terkonfirmasi'
                                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                  : 'bg-amber-50 text-amber-800 border-amber-200',
                                        ]"
                                    >
                                        {{ cell.menus[0]?.status }}
                                    </span>
                                </div>
                                <div
                                    v-else-if="(viewMode === 'periode' ? cell.isInPeriode : cell.isCurrentMonth)"
                                    class="text-right"
                                >
                                    <span
                                        class="text-[9px] font-medium text-slate-400 bg-white/70 px-1.5 py-0.5 rounded border border-slate-200/50"
                                    >
                                        Kosong
                                    </span>
                                </div>
                            </div>

                            <!-- Konten Tengah: Jika Ada Menu vs Jika Tanggal Kosong -->
                            <div v-if="cell.hasMenu" class="my-1.5 space-y-1.5">
                                <div
                                    v-for="(m, mIdx) in cell.menus.slice(0, 1)"
                                    :key="mIdx"
                                    class="p-2 rounded-xl bg-slate-50/90 border border-slate-200/80 hover:border-blue-300 transition-colors space-y-1"
                                >
                                    <p
                                        class="text-xs font-bold text-slate-900 line-clamp-2 leading-tight"
                                        :title="m.namaMenu"
                                    >
                                        {{ m.namaMenu }}
                                    </p>

                                    <!-- Sub-menu pills -->
                                    <div class="flex flex-wrap gap-1 pt-0.5">
                                        <span
                                            v-for="(
                                                sub, sIdx
                                            ) in m.subMenus.slice(0, 2)"
                                            :key="sIdx"
                                            class="px-1.5 py-0.5 text-[9.5px] rounded bg-white text-slate-700 font-medium border border-slate-200/60 truncate max-w-[120px]"
                                        >
                                            {{ sub.val }}
                                        </span>
                                        <span
                                            v-if="m.subMenus.length > 2"
                                            class="px-1.5 py-0.5 text-[9px] rounded bg-slate-200/70 text-slate-600 font-bold"
                                        >
                                            +{{ m.subMenus.length - 2 }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- State Tanggal Kosong (Zebra Text + Action Buat Menu) -->
                            <div v-else class="my-auto py-2.5 text-center">
                                <span
                                    v-if="viewMode === 'periode' && !cell.isInPeriode"
                                    class="text-[10px] text-slate-300 font-normal select-none"
                                >
                                    Di luar periode
                                </span>
                                <div v-else class="space-y-1">
                                    <span
                                        class="text-[10px] text-slate-400 font-medium italic select-none block"
                                    >
                                        Tidak ada jadwal
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 text-[9.5px] text-primary font-bold opacity-0 group-hover:opacity-100 transition-opacity bg-primary/10 px-2 py-0.5 rounded-md"
                                    >
                                        <Plus class="h-3 w-3" /> Buat Menu
                                    </span>
                                </div>
                            </div>

                            <!-- Baris Bawah: Footer Ringkasan AKG & Cost jika Ada Menu -->
                            <div
                                v-if="cell.hasMenu && cell.menus[0]"
                                class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500 font-semibold"
                            >
                                <span
                                    v-if="
                                        cell.menus[0].kaloriPK ||
                                        cell.menus[0].kaloriPB
                                    "
                                    class="text-amber-800 flex items-center gap-0.5"
                                >
                                    <Flame class="h-3 w-3 text-amber-600" />
                                    <span
                                        >{{
                                            cell.menus[0].kaloriPK ||
                                            cell.menus[0].kaloriPB
                                        }}
                                        kkal</span
                                    >
                                </span>
                                <span v-else class="text-slate-400 text-[9.5px]"
                                    >Klik Review</span
                                >

                                <span
                                    v-if="
                                        cell.menus[0].costPK ||
                                        cell.menus[0].costPB
                                    "
                                    class="text-emerald-800 font-bold"
                                >
                                    {{
                                        formatRupiah(
                                            cell.menus[0].costPK ||
                                                cell.menus[0].costPB,
                                        )
                                    }}
                                </span>
                            </div>
                            <div v-else class="h-1"></div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Modal Review Detail Menu Tanggal Terpilih -->
        <Modal
            :show="!!selectedKalenderItem"
            @close="selectedKalenderItem = null"
            maxWidth="xl"
        >
            <div
                v-if="selectedKalenderItem"
                class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-200"
            >
                <!-- Modal Header -->
                <div
                    class="p-4 sm:p-5 border-b border-slate-100 flex flex-row items-center justify-between bg-slate-50/80"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20"
                        >
                            <CalendarCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="text-xs font-black text-primary uppercase tracking-wider"
                                >
                                    Siklus Ke-{{
                                        selectedKalenderItem.siklusKe
                                    }}
                                </span>
                                <Badge
                                    variant="outline"
                                    :class="[
                                        'text-[10.5px] font-bold px-2 py-0.5 rounded-md',
                                        selectedKalenderItem.status ===
                                        'Selesai'
                                            ? 'bg-slate-100 text-slate-700 border-slate-200'
                                            : selectedKalenderItem.status ===
                                                    'Siap Produksi' ||
                                                selectedKalenderItem.status ===
                                                    'Terkonfirmasi'
                                              ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                              : 'bg-amber-50 text-amber-800 border-amber-200',
                                    ]"
                                >
                                    {{ selectedKalenderItem.status }}
                                </Badge>
                            </div>
                            <h3
                                class="text-base font-black text-slate-900 mt-0.5"
                            >
                                {{
                                    formatTanggalIndo(
                                        selectedKalenderItem.tanggal,
                                    )
                                }}
                            </h3>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="selectedKalenderItem = null"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div
                    class="p-4 sm:p-6 space-y-5 text-xs max-h-[75vh] overflow-y-auto"
                >
                    <!-- Nomor Work Order & Nama Menu -->
                    <div
                        class="p-4 rounded-xl bg-blue-50/60 border border-blue-100 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[11px] font-bold text-blue-700 uppercase tracking-wider"
                            >
                                Nomor Work Order (WO)
                            </span>
                            <span
                                class="font-mono text-xs font-bold text-slate-800 bg-white px-2 py-0.5 rounded border border-blue-200"
                            >
                                {{
                                    selectedKalenderItem.nomor_wo ||
                                    selectedKalenderItem.id
                                }}
                            </span>
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-bold text-slate-500 uppercase"
                            >
                                Paket Menu Utama MBG
                            </p>
                            <h4
                                class="text-base font-black text-slate-900 mt-0.5"
                            >
                                {{ selectedKalenderItem.namaMenu }}
                            </h4>
                        </div>
                    </div>

                    <!-- Rincian Komponen Sub Menu 1 s/d 5 -->
                    <div class="space-y-2">
                        <p
                            class="text-[11px] font-bold text-slate-500 uppercase flex items-center gap-1.5"
                        >
                            <UtensilsCrossed
                                class="h-3.5 w-3.5 text-slate-600"
                            />
                            <span>Rincian Komponen Sub Menu</span>
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="(
                                    sub, sIdx
                                ) in selectedKalenderItem.subMenus"
                                :key="sIdx"
                                class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-2"
                            >
                                <span
                                    class="h-5 w-5 rounded-lg bg-white border border-slate-200 text-slate-700 text-[10.5px] font-black flex items-center justify-center shrink-0 mt-0.5"
                                >
                                    {{ sIdx + 1 }}
                                </span>
                                <div>
                                    <p
                                        class="text-[10px] font-bold text-slate-400 uppercase"
                                    >
                                        {{ sub.label }}
                                    </p>
                                    <p class="text-xs font-bold text-slate-900">
                                        {{ sub.val }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Target Porsi Produksi -->
                    <div
                        class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[11px] font-bold text-slate-700 uppercase flex items-center gap-1.5"
                            >
                                <Users class="h-3.5 w-3.5 text-primary" />
                                <span>Alokasi Target Produksi PM</span>
                            </span>
                            <span class="font-black text-slate-900 text-sm">
                                {{
                                    selectedKalenderItem.totalPorsi > 0
                                        ? selectedKalenderItem.totalPorsi.toLocaleString(
                                              "id-ID",
                                          )
                                        : "1.910"
                                }}
                                Porsi
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-center text-xs">
                            <div
                                class="bg-white p-2 rounded-lg border border-slate-200/80"
                            >
                                <span
                                    class="text-[10.5px] text-amber-800 font-bold block"
                                    >Porsi Kecil (PK)</span
                                >
                                <span class="text-sm font-black text-amber-950">
                                    {{
                                        selectedKalenderItem.porsiPK > 0
                                            ? selectedKalenderItem.porsiPK
                                            : "882"
                                    }}
                                    Porsi
                                </span>
                            </div>
                            <div
                                class="bg-white p-2 rounded-lg border border-slate-200/80"
                            >
                                <span
                                    class="text-[10.5px] text-indigo-800 font-bold block"
                                    >Porsi Besar (PB)</span
                                >
                                <span
                                    class="text-sm font-black text-indigo-950"
                                >
                                    {{
                                        selectedKalenderItem.porsiPB > 0
                                            ? selectedKalenderItem.porsiPB
                                            : "1.028"
                                    }}
                                    Porsi
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rincian Nutrisi AKG & Food Cost -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Card Porsi Kecil (PK) -->
                        <div
                            class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2"
                        >
                            <div
                                class="flex items-center justify-between border-b border-amber-200/70 pb-1.5"
                            >
                                <span
                                    class="text-[11px] font-black text-amber-950 uppercase"
                                >
                                    Porsi Kecil (PK)
                                </span>
                                <span class="text-xs font-bold text-amber-900">
                                    {{
                                        selectedKalenderItem.kaloriPK
                                            ? `${selectedKalenderItem.kaloriPK} kkal`
                                            : "485 kkal"
                                    }}
                                </span>
                            </div>
                            <div class="space-y-1 text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-600"
                                        >Estimasi Food Cost:</span
                                    >
                                    <span class="font-bold text-amber-950">
                                        {{
                                            formatRupiah(
                                                selectedKalenderItem.costPK ||
                                                    7850,
                                            )
                                        }}
                                        / porsi
                                    </span>
                                </div>
                                <div
                                    v-if="selectedKalenderItem.proteinPK"
                                    class="flex justify-between"
                                >
                                    <span class="text-slate-600">Protein:</span>
                                    <span class="font-bold text-slate-800"
                                        >{{
                                            selectedKalenderItem.proteinPK
                                        }}
                                        g</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Card Porsi Besar (PB) -->
                        <div
                            class="p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2"
                        >
                            <div
                                class="flex items-center justify-between border-b border-indigo-200/70 pb-1.5"
                            >
                                <span
                                    class="text-[11px] font-black text-indigo-950 uppercase"
                                >
                                    Porsi Besar (PB)
                                </span>
                                <span class="text-xs font-bold text-indigo-900">
                                    {{
                                        selectedKalenderItem.kaloriPB
                                            ? `${selectedKalenderItem.kaloriPB} kkal`
                                            : "640 kkal"
                                    }}
                                </span>
                            </div>
                            <div class="space-y-1 text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-600"
                                        >Estimasi Food Cost:</span
                                    >
                                    <span class="font-bold text-indigo-950">
                                        {{
                                            formatRupiah(
                                                selectedKalenderItem.costPB ||
                                                    9850,
                                            )
                                        }}
                                        / porsi
                                    </span>
                                </div>
                                <div
                                    v-if="selectedKalenderItem.proteinPB"
                                    class="flex justify-between"
                                >
                                    <span class="text-slate-600">Protein:</span>
                                    <span class="font-bold text-slate-800"
                                        >{{
                                            selectedKalenderItem.proteinPB
                                        }}
                                        g</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 sm:p-5 border-t border-slate-100 flex items-center justify-between gap-3 bg-slate-50/80"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="selectedKalenderItem = null"
                        className="bg-white border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-bold px-4 h-9 cursor-pointer rounded-xl"
                    >
                        Tutup
                    </Button>
                    <Button
                        type="button"
                        @click="handleOpenMenuInRancang(selectedKalenderItem)"
                        className="bg-primary hover:bg-primary/90 text-white text-xs font-bold px-4 h-9 cursor-pointer shadow-xs rounded-xl flex items-center gap-1.5"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        <span>Buka di Formulasi Menu</span>
                    </Button>
                </div>
            </div>
        </Modal>
    </div>
</template>
