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

const isGiziExpanded = ref(true);

function toggleGiziMenu() {
    if (props.isCollapsed) {
        emit("update:isCollapsed", false);
        isGiziExpanded.value = true;
    } else {
        isGiziExpanded.value = !isGiziExpanded.value;
    }
}

const page = usePage();
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
            'fixed inset-y-0 left-0 z-50 h-screen bg-white border-r border-slate-200/90 flex flex-col justify-between transition-all duration-300 ease-in-out select-none',
            // Tampilan Mobile: Selalu w-64, slide in/out dengan transform
            'w-64',
            isMobileOpen ? 'translate-x-0' : '-translate-x-full',
            // Tampilan Desktop: Static, lebar mengikuti isCollapsed
            'lg:static lg:h-screen lg:shrink-0 lg:translate-x-0',
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

                    <!-- Sub-menu Items (TKPI, Analisa PM, Buat Menu, Kalender Menu) -->
                    <div
                        v-if="!isCollapsed && isGiziExpanded"
                        class="pl-4 pr-1 py-1 space-y-1 border-l-2 border-slate-100 ml-5 my-1 animate-in fade-in slide-in-from-top-1 duration-150"
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

                        <!-- Sub-menu 3: Buat Menu -->
                        <Link
                            :href="route('gizi.buat-menu')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('gizi.buat-menu') || (route().current('gizi.index') && page.props.activeTab === 'buat-menu')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <Utensils class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Buat Menu</span>
                        </Link>

                        <!-- Sub-menu 4: Kalender Menu -->
                        <Link
                            :href="route('gizi.kalender-menu')"
                            :class="[
                                'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                                route().current('gizi.kalender-menu') || (route().current('gizi.index') && page.props.activeTab === 'kalender-menu')
                                    ? 'bg-primary/10 text-primary font-bold shadow-2xs'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">Kalender Menu</span>
                        </Link>
                    </div>
                </div>

                <!-- 4. Menu Keuangan -->
                <Link
                    :href="route('keuangan.index')"
                    :title="isCollapsed ? 'Keuangan' : ''"
                    :class="[
                        'flex items-center rounded-lg text-sm font-semibold transition-colors cursor-pointer',
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
                    <div
                        v-if="route().current('keuangan.*')"
                        :class="[
                            'h-2 w-2 rounded-full bg-primary animate-pulse shrink-0',
                            isCollapsed
                                ? 'inline-block lg:hidden'
                                : 'inline-block',
                        ]"
                    ></div>
                </Link>

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

            <!-- Tombol Halaman Utama (Beranda) -->
            <Link
                :href="route('home')"
                :title="isCollapsed ? 'Halaman Utama' : ''"
                :class="[
                    'w-full flex items-center justify-center gap-2 text-xs font-semibold text-slate-700 border border-slate-200 hover:bg-slate-100 hover:text-slate-900 bg-white shadow-2xs cursor-pointer transition-all rounded-lg',
                    isCollapsed ? 'h-9 px-3 lg:h-10 lg:p-0' : 'h-9 px-3',
                ]"
            >
                <Home class="h-4 w-4 shrink-0 text-slate-500" />
                <span :class="[isCollapsed ? 'inline lg:hidden' : 'inline']">
                    Halaman Utama
                </span>
            </Link>

            <!-- Tombol Keluar (Logout) -->
            <Button
                type="button"
                variant="outline"
                @click="logout"
                :title="isCollapsed ? 'Keluar' : ''"
                :class="[
                    'w-full flex items-center justify-center gap-2 text-xs font-semibold text-rose-600 border-rose-200 hover:bg-rose-50 hover:text-rose-700 bg-white shadow-2xs cursor-pointer transition-all',
                    isCollapsed ? 'h-9 px-3 lg:h-10 lg:p-0' : 'h-9 px-3',
                ]"
            >
                <LogOut class="h-4 w-4 shrink-0" />
                <span :class="[isCollapsed ? 'inline lg:hidden' : 'inline']"
                    >Keluar</span
                >
            </Button>
        </div>
    </aside>
</template>
