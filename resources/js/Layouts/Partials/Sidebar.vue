<script setup>
import { computed, ref, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
    Building2,
    Home,
    LayoutDashboard,
    LogOut,
    Users,
    UtensilsCrossed,
    Wallet,
    Tag,
    X,
    ChevronDown,
    Database,
    BarChart3,
    Utensils,
    CalendarDays,
    FileSpreadsheet,
    ClipboardList,
    Receipt,
    Coins,
    CreditCard,
    BookOpen,
    Landmark,
    Banknote,
    Package,
    Sliders,
    Building,
    FileCheck2,
    ShieldCheck,
    FileSignature,
    CalendarRange,
} from "lucide-vue-next";
import Button from "@/Components/ui/Button.vue";
import { formatNamaLengkap } from "@/Services/wilayah";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
    isMobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:isMobileOpen", "update:isCollapsed"]);

const page = usePage();

const isGiziActive = computed(() => {
    try {
        return route().current("gizi.*");
    } catch {
        return false;
    }
});

const isKeuanganActive = computed(() => {
    try {
        return route().current("keuangan.*");
    } catch {
        return false;
    }
});

// Default tertutup, hanya terbuka jika sub-menunya sedang aktif/dibuka
const isGiziExpanded = ref(isGiziActive.value);
const isKeuanganExpanded = ref(isKeuanganActive.value);

watch(
    () => page.url,
    () => {
        if (isGiziActive.value) {
            isGiziExpanded.value = true;
        }
        if (isKeuanganActive.value) {
            isKeuanganExpanded.value = true;
        }
    }
);

function toggleGiziMenu() {
    if (props.isCollapsed) {
        emit("update:isCollapsed", false);
        isGiziExpanded.value = true;
    } else {
        isGiziExpanded.value = !isGiziExpanded.value;
    }
}

function toggleKeuanganMenu() {
    if (props.isCollapsed) {
        emit("update:isCollapsed", false);
        isKeuanganExpanded.value = true;
    } else {
        isKeuanganExpanded.value = !isKeuanganExpanded.value;
    }
}

const currentUser = computed(() => {
    return props.user?.nama || props.user?.name
        ? props.user
        : page.props.auth?.user || {};
});

const fullName = computed(() => {
    if (currentUser.value.nama_lengkap) return currentUser.value.nama_lengkap;
    if (currentUser.value.nama) {
        return formatNamaLengkap(
            currentUser.value.nama,
            currentUser.value.gelar_depan,
            currentUser.value.gelar_belakang,
        );
    }
    return currentUser.value.name || "Pengguna";
});

const userInitials = computed(() => {
    const name = currentUser.value.nama || currentUser.value.name;
    if (!name) return "U";
    const words = name.trim().split(" ");
    if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
});

function closeMobile() {
    emit("update:isMobileOpen", false);
}

function logout() {
    router.post(route("logout"));
}
</script>

<template>
    <!-- ================= SHADCN SIDEBAR (EXPAND / COLLAPSE) ================= -->
    <aside
        :class="[
            'fixed inset-y-0 left-0 z-[9999] h-screen bg-white border-r border-slate-200/90 flex flex-col justify-between transition-all duration-300 ease-in-out select-none',
            // Tampilan Mobile: Selalu w-64, slide in/out dengan transform
            'w-64',
            isMobileOpen ? 'translate-x-0' : '-translate-x-full',
            // Tampilan Desktop: Static, lebar mengikuti isCollapsed
            'lg:static lg:h-screen lg:shrink-0 lg:translate-x-0 lg:z-auto',
            isCollapsed ? 'lg:w-20' : 'lg:w-64',
        ]"
    >
        <!-- ATAS: Logo & Menu Utama -->
        <div class="flex flex-col flex-1 min-h-0">
            <!-- Logo & Header Sidebar -->
            <div
                :class="[
                    'h-16 border-b border-slate-100 flex items-center shrink-0 transition-all duration-200 justify-between px-4',
                    isCollapsed
                        ? 'lg:justify-center lg:px-2'
                        : 'lg:justify-between lg:px-4',
                ]"
            >
                <Link
                    :href="route('dashboard')"
                    :class="[
                        'flex items-center group overflow-hidden gap-3',
                        isCollapsed
                            ? 'lg:justify-center lg:w-full lg:gap-0'
                            : '',
                    ]"
                    :title="isCollapsed ? 'SIPEGE' : ''"
                >
                    <div
                        class="h-9 w-9 rounded-xl bg-primary text-white flex items-center justify-center shadow-md shadow-primary/20 transition-transform group-hover:scale-105 shrink-0"
                    >
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div
                        :class="[
                            'transition-opacity duration-200 whitespace-nowrap overflow-hidden',
                            isCollapsed ? 'block lg:hidden' : 'block',
                        ]"
                    >
                        <span
                            class="font-extrabold text-lg tracking-tight text-slate-900 leading-none block"
                            >SIPEGE</span
                        >
                        <span
                            class="text-[11px] text-slate-500 font-medium leading-none"
                            >Sistem Pengelolaan SPPG</span
                        >
                    </div>
                </Link>

                <!-- Mobile Close Button -->
                <button
                    type="button"
                    @click="closeMobile"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 lg:hidden cursor-pointer shrink-0"
                    aria-label="Tutup Menu"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- MENU UTAMA -->
            <div class="px-3 py-5 space-y-1 overflow-y-auto flex-1">
                <p
                    :class="[
                        'px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2 transition-opacity',
                        isCollapsed ? 'block lg:hidden' : 'block',
                    ]"
                >
                    Menu Utama
                </p>

                <!-- 1. Dashboard -->
                <Link
                    :href="route('dashboard')"
                    :title="isCollapsed ? 'Dashboard' : ''"
                    :class="[
                        'flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer',
                        route().current('dashboard')
                            ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                        isCollapsed
                            ? 'px-3.5 py-2.5 gap-3 lg:justify-center lg:p-2.5 lg:h-10 lg:w-full lg:gap-0'
                            : 'px-3.5 py-2.5 gap-3',
                    ]"
                >
                    <LayoutDashboard class="h-4 w-4 shrink-0" />
                    <span
                        :class="[
                            'flex-1 truncate',
                            isCollapsed ? 'inline lg:hidden' : 'inline',
                        ]"
                        >Dashboard</span
                    >
                    <div
                        v-if="route().current('dashboard')"
                        :class="[
                            'h-2 w-2 rounded-full bg-primary animate-pulse shrink-0',
                            isCollapsed
                                ? 'inline-block lg:hidden'
                                : 'inline-block',
                        ]"
                    ></div>
                </Link>

                <!-- 2. Menu Penerima Manfaat -->
                <Link
                    :href="route('penerima-manfaat.index')"
                    :title="isCollapsed ? 'Penerima Manfaat' : ''"
                    :class="[
                        'flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer',
                        route().current('penerima-manfaat.*')
                            ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                        isCollapsed
                            ? 'px-3.5 py-2.5 gap-3 lg:justify-center lg:p-2.5 lg:h-10 lg:w-full lg:gap-0'
                            : 'px-3.5 py-2.5 gap-3',
                    ]"
                >
                    <Users class="h-4 w-4 shrink-0" />
                    <span
                        :class="[
                            'flex-1 truncate',
                            isCollapsed ? 'inline lg:hidden' : 'inline',
                        ]"
                        >Penerima Manfaat</span
                    >
                    <div
                        v-if="route().current('penerima-manfaat.*')"
                        :class="[
                            'h-2 w-2 rounded-full bg-primary animate-pulse shrink-0',
                            isCollapsed
                                ? 'inline-block lg:hidden'
                                : 'inline-block',
                        ]"
                    ></div>
                </Link>

                <!-- 3. Menu Gizi (Accordion with Submenu) -->
                <div class="space-y-0.5">
                    <!-- Parent Gizi Button -->
                    <button
                        type="button"
                        @click="toggleGiziMenu"
                        :title="isCollapsed ? 'Gizi' : ''"
                        :class="[
                            'w-full flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer text-left',
                            route().current('gizi.*')
                                ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            isCollapsed
                                ? 'px-3.5 py-2.5 gap-3 lg:justify-center lg:p-2.5 lg:h-10 lg:w-full lg:gap-0'
                                : 'px-3.5 py-2.5 gap-3',
                        ]"
                    >
                        <UtensilsCrossed class="h-4 w-4 shrink-0" />
                        <span
                            :class="[
                                'flex-1 truncate',
                                isCollapsed ? 'inline lg:hidden' : 'inline',
                            ]"
                            >Gizi</span
                        >
                        <ChevronDown
                            :class="[
                                'h-3.5 w-3.5 shrink-0 transition-transform duration-200 text-slate-400',
                                isCollapsed ? 'hidden' : 'block',
                                isGiziExpanded ? 'rotate-180 text-primary' : '',
                            ]"
                        />
                    </button>

                    <!-- Sub-menu Items: TKPI, Analisa PM, Daftar Menu, Buat Menu (Perencanaan, Formula Gizi) -->
                    <div
                        v-if="!isCollapsed && isGiziExpanded"
                        class="pl-3 pr-1 py-1 space-y-1 border-l-2 border-slate-100 ml-5 my-1 animate-in fade-in slide-in-from-top-1 duration-150"
                    >
                        <!-- Sub-menu 1: TKPI -->
                        <Link
                            :href="route('gizi.tkpi')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('gizi.tkpi') || (route().current('gizi.index') && (page.props.activeTab === 'tkpi' || !page.props.activeTab))
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Database class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">TKPI</span>
                        </Link>

                        <!-- Sub-menu 2: Analisa PM -->
                        <Link
                            :href="route('gizi.analisa-pm')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('gizi.analisa-pm') || (route().current('gizi.index') && page.props.activeTab === 'analisa-pm')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <BarChart3 class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Analisa PM</span>
                        </Link>

                        <!-- Sub-menu 3: Daftar Menu -->
                        <Link
                            :href="route('gizi.daftar-menu')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('gizi.daftar-menu') || route().current('gizi.kalender-menu') || (route().current('gizi.index') && (page.props.activeTab === 'daftar-menu' || page.props.activeTab === 'kalender-menu'))
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Daftar Menu</span>
                        </Link>

                        <!-- Sub-menu 4: Rancang Menu -->
                        <Link
                            :href="route('gizi.rancang-menu')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('gizi.rancang-menu') || route().current('gizi.buat-menu') || (route().current('gizi.index') && (page.props.activeTab === 'rancang-menu' || page.props.activeTab === 'buat-menu'))
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Utensils class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Rancang Menu</span>
                        </Link>
                    </div>
                </div>

                <!-- 4. Menu Keuangan (Accordion with Submenu) -->
                <div class="space-y-0.5">
                    <!-- Parent Keuangan Button -->
                    <button
                        type="button"
                        @click="toggleKeuanganMenu"
                        :title="isCollapsed ? 'Keuangan' : ''"
                        :class="[
                            'w-full flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer text-left',
                            route().current('keuangan.*')
                                ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            isCollapsed
                                ? 'px-3.5 py-2.5 gap-3 lg:justify-center lg:p-2.5 lg:h-10 lg:w-full lg:gap-0'
                                : 'px-3.5 py-2.5 gap-3',
                        ]"
                    >
                        <Wallet class="h-4 w-4 shrink-0" />
                        <span
                            :class="[
                                'flex-1 truncate',
                                isCollapsed ? 'inline lg:hidden' : 'inline',
                            ]"
                            >Keuangan</span
                        >
                        <ChevronDown
                            :class="[
                                'h-3.5 w-3.5 shrink-0 transition-transform duration-200 text-slate-400',
                                isCollapsed ? 'hidden' : 'block',
                                isKeuanganExpanded ? 'rotate-180 text-primary' : '',
                            ]"
                        />
                    </button>

                    <!-- Sub-menu Items under Keuangan: 12 Sub-menu Lengkap -->
                    <div
                        v-if="!isCollapsed && isKeuanganExpanded"
                        class="pl-3 pr-1 py-1 space-y-1 border-l-2 border-slate-100 ml-5 my-1 animate-in fade-in slide-in-from-top-1 duration-150"
                    >
                        <!-- 1. Anggaran -->
                        <Link
                            :href="route('keuangan.anggaran')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.anggaran') || (route().current('keuangan.index') && (page.props.activeTab === 'anggaran' || !page.props.activeTab))
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Coins class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Anggaran</span>
                        </Link>

                        <!-- 2. Daftar PO -->
                        <Link
                            :href="route('keuangan.daftar-po')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.daftar-po') || route().current('keuangan.daftar_po') || (route().current('keuangan.*') && (page.props.activeTab === 'daftar-po' || page.props.activeTab === 'daftar_po'))
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Receipt class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Daftar PO</span>
                        </Link>

                        <!-- 3. Transaksi -->
                        <Link
                            :href="route('keuangan.transaksi')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.transaksi') || (route().current('keuangan.*') && page.props.activeTab === 'transaksi')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <CreditCard class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Transaksi</span>
                        </Link>

                        <!-- 4. BKU -->
                        <Link
                            :href="route('keuangan.bku')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bku') || (route().current('keuangan.*') && page.props.activeTab === 'bku')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <BookOpen class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BKU</span>
                        </Link>

                        <!-- 5. BP Bank -->
                        <Link
                            :href="route('keuangan.bp-bank')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bp-bank') || (route().current('keuangan.*') && page.props.activeTab === 'bp-bank')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Landmark class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BP Bank</span>
                        </Link>

                        <!-- 6. BP Petty Cash -->
                        <Link
                            :href="route('keuangan.bp-petty-cash')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bp-petty-cash') || (route().current('keuangan.*') && page.props.activeTab === 'bp-petty-cash')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Banknote class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BP Petty Cash</span>
                        </Link>

                        <!-- 7. BP Bahan Baku -->
                        <Link
                            :href="route('keuangan.bp-bahan-baku')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bp-bahan-baku') || (route().current('keuangan.*') && page.props.activeTab === 'bp-bahan-baku')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Package class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BP Bahan Baku</span>
                        </Link>

                        <!-- 8. BP Operasional -->
                        <Link
                            :href="route('keuangan.bp-operasional')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bp-operasional') || (route().current('keuangan.*') && page.props.activeTab === 'bp-operasional')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Sliders class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BP Operasional</span>
                        </Link>

                        <!-- 9. BP Fasilitas -->
                        <Link
                            :href="route('keuangan.bp-fasilitas')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bp-fasilitas') || (route().current('keuangan.*') && page.props.activeTab === 'bp-fasilitas')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Building class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BP Fasilitas</span>
                        </Link>

                        <!-- 10. LPA -->
                        <Link
                            :href="route('keuangan.lpa')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.lpa') || (route().current('keuangan.*') && page.props.activeTab === 'lpa')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <FileCheck2 class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">LPA</span>
                        </Link>

                        <!-- 11. SPTJ -->
                        <Link
                            :href="route('keuangan.sptj')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.sptj') || (route().current('keuangan.*') && page.props.activeTab === 'sptj')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <ShieldCheck class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">SPTJ</span>
                        </Link>

                        <!-- 12. BAPSD -->
                        <Link
                            :href="route('keuangan.bapsd')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('keuangan.bapsd') || (route().current('keuangan.*') && page.props.activeTab === 'bapsd')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <FileSignature class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">BAPSD</span>
                        </Link>
                    </div>
                </div>

                <!-- 5. Menu Label -->
                <Link
                    :href="route('label.index')"
                    :title="isCollapsed ? 'Label' : ''"
                    :class="[
                        'flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer',
                        route().current('label.*')
                            ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                        isCollapsed
                            ? 'px-3.5 py-2.5 gap-3 lg:justify-center lg:p-2.5 lg:h-10 lg:w-full lg:gap-0'
                            : 'px-3.5 py-2.5 gap-3',
                    ]"
                >
                    <Tag class="h-4 w-4 shrink-0" />
                    <span
                        :class="[
                            'flex-1 truncate',
                            isCollapsed ? 'inline lg:hidden' : 'inline',
                        ]"
                        >Label</span
                    >
                    <div
                        v-if="route().current('label.*')"
                        :class="[
                            'h-2 w-2 rounded-full bg-primary animate-pulse shrink-0',
                            isCollapsed
                                ? 'inline-block lg:hidden'
                                : 'inline-block',
                        ]"
                    ></div>
                </Link>

                <!-- 6. Menu Periode -->
                <Link
                    :href="route('periode.index')"
                    :title="isCollapsed ? 'Periode' : ''"
                    :class="[
                        'flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer',
                        route().current('periode.*')
                            ? 'bg-primary/10 text-primary border border-primary/20 shadow-xs'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                        isCollapsed
                            ? 'px-3.5 py-2.5 gap-3 lg:justify-center lg:p-2.5 lg:h-10 lg:w-full lg:gap-0'
                            : 'px-3.5 py-2.5 gap-3',
                    ]"
                >
                    <CalendarRange class="h-4 w-4 shrink-0" />
                    <span
                        :class="[
                            'flex-1 truncate',
                            isCollapsed ? 'inline lg:hidden' : 'inline',
                        ]"
                        >Periode</span
                    >
                    <div
                        v-if="route().current('periode.*')"
                        :class="[
                            'h-2 w-2 rounded-full bg-primary animate-pulse shrink-0',
                            isCollapsed
                                ? 'inline-block lg:hidden'
                                : 'inline-block',
                        ]"
                    ></div>
                </Link>
            </div>
        </div>

        <!-- BAWAH: Profil Pengguna (Avatar) & Tombol Keluar -->
        <div
            class="p-3 border-t border-slate-100 bg-slate-50/50 space-y-2 shrink-0"
        >
            <!-- User Info Card (Full pada Mobile, di Desktop tersembunyi saat isCollapsed) -->
            <div
                :class="[
                    'items-center gap-3 p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs',
                    isCollapsed ? 'flex lg:hidden' : 'flex',
                ]"
            >
                <div
                    class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-xs"
                >
                    {{ userInitials }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-900 leading-tight">
                        {{ fullName }}
                    </p>
                    <p class="text-[11px] text-slate-500 leading-tight mt-0.5">
                        {{ currentUser.email }}
                    </p>
                    <div class="mt-1">
                        <span
                            class="inline-flex items-center px-1.5 py-0.2 text-[10px] font-semibold uppercase tracking-wider rounded bg-slate-100 text-slate-600"
                        >
                            {{ currentUser.role || "Guest" }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- User Info Card (Hanya Avatar di Desktop saat isCollapsed) -->
            <div
                v-if="isCollapsed"
                :title="`${fullName} (${currentUser.email})`"
                class="hidden lg:flex justify-center p-1 cursor-pointer"
            >
                <div
                    class="h-10 w-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-sm flex items-center justify-center shadow-xs"
                >
                    {{ userInitials }}
                </div>
            </div>

            <!-- 2 Tombol 1 Baris: Kiri Icon Halaman Utama (Tanpa Teks), Kanan Tombol Keluar -->
            <div
                :class="[
                    'items-center gap-2',
                    isCollapsed ? 'flex flex-col lg:flex-col' : 'flex',
                ]"
            >
                <!-- Tombol Halaman Utama (Icon Saja) -->
                <Link
                    :href="route('home')"
                    title="Halaman Utama"
                    :class="[
                        'flex items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 hover:text-slate-900 shadow-2xs cursor-pointer transition-all shrink-0',
                        isCollapsed ? 'h-9 w-full lg:h-10 lg:w-full' : 'h-9 w-10',
                    ]"
                >
                    <Home class="h-4 w-4 text-slate-600" />
                </Link>

                <!-- Tombol Keluar (Tetap ada Bacaan Teks Keluar) -->
                <Button
                    type="button"
                    variant="outline"
                    @click="logout"
                    title="Keluar"
                    :class="[
                        'flex items-center justify-center gap-1.5 text-xs font-semibold text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700 bg-white shadow-2xs cursor-pointer transition-all rounded-lg',
                        isCollapsed
                            ? 'h-9 w-full lg:h-10 lg:w-full p-0'
                            : 'h-9 flex-1 px-3',
                    ]"
                >
                    <LogOut class="h-4 w-4 shrink-0" />
                    <span :class="[isCollapsed ? 'inline lg:hidden' : 'inline']">
                        Keluar
                    </span>
                </Button>
            </div>
        </div>
    </aside>
</template>
