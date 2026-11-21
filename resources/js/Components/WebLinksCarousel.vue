<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import {
    ExternalLink,
    Landmark,
    Layers,
    Users,
    ShoppingBag,
    BarChart3,
    Cpu,
    UtensilsCrossed,
    Wallet,
    Activity,
    ShieldCheck,
    Briefcase,
    HeartPulse,
    TrendingUp,
    ChevronLeft,
    ChevronRight,
    X,
} from "lucide-vue-next";

// Data web links utama (Survei Harga disatukan menjadi 1 kartu, cukup Nama dan Link saja)
const webLinks = [
    {
        id: "bgn",
        title: "BGN",
        domain: "bgn.go.id",
        url: "https://www.bgn.go.id/",
        icon: Landmark,
        iconBg: "bg-blue-600 text-white",
        cardBorder: "hover:border-blue-400 hover:shadow-blue-500/10",
    },
    {
        id: "sipgn",
        title: "SIPGN",
        domain: "sipgn.bgn.go.id",
        url: "https://sipgn.bgn.go.id/",
        icon: Layers,
        iconBg: "bg-indigo-600 text-white",
        cardBorder: "hover:border-indigo-400 hover:shadow-indigo-500/10",
    },
    {
        id: "siphr",
        title: "SIPHR",
        domain: "sipgn-siphr.bgn.go.id",
        url: "https://sipgn-siphr.bgn.go.id/",
        icon: Users,
        iconBg: "bg-teal-600 text-white",
        cardBorder: "hover:border-teal-400 hover:shadow-teal-500/10",
    },
    {
        id: "pop",
        title: "POP",
        domain: "pop-sipgn.bgn.go.id",
        url: "https://pop-sipgn.bgn.go.id/",
        icon: ShoppingBag,
        iconBg: "bg-emerald-600 text-white",
        cardBorder: "hover:border-emerald-400 hover:shadow-emerald-500/10",
    },
    {
        id: "mpm",
        title: "MPM",
        domain: "mpm-sipgn.bgn.go.id",
        url: "https://mpm-sipgn.bgn.go.id/",
        icon: BarChart3,
        iconBg: "bg-cyan-600 text-white",
        cardBorder: "hover:border-cyan-400 hover:shadow-cyan-500/10",
    },
    {
        id: "erp",
        title: "ERP BGN",
        domain: "erp-sipgn.bgn.go.id",
        url: "https://erp-sipgn.bgn.go.id/",
        icon: Cpu,
        iconBg: "bg-purple-600 text-white",
        cardBorder: "hover:border-purple-400 hover:shadow-purple-500/10",
    },
    {
        id: "menu-planner",
        title: "Menu Planner",
        domain: "sipgn-aimenu.bgn.go.id",
        url: "https://sipgn-aimenu.bgn.go.id/",
        icon: UtensilsCrossed,
        iconBg: "bg-amber-600 text-white",
        cardBorder: "hover:border-amber-400 hover:shadow-amber-500/10",
    },
    {
        id: "bni-direct",
        title: "BNI Direct",
        domain: "bnidirect.bni.co.id",
        url: "https://bnidirect.bni.co.id/",
        icon: Wallet,
        iconBg: "bg-sky-600 text-white",
        cardBorder: "hover:border-sky-400 hover:shadow-sky-500/10",
    },
    {
        id: "dashboard-bni",
        title: "Dashboard BNI BGN",
        domain: "dashboardbgn.bni.co.id",
        url: "https://dashboardbgn.bni.co.id/",
        icon: Activity,
        iconBg: "bg-blue-700 text-white",
        cardBorder: "hover:border-blue-400 hover:shadow-blue-500/10",
    },
    {
        id: "bpjs-tk",
        title: "SIPP BPJS Ketenagakerjaan",
        domain: "sipp.bpjsketenagakerjaan.go.id",
        url: "https://sipp.bpjsketenagakerjaan.go.id/",
        icon: ShieldCheck,
        iconBg: "bg-emerald-700 text-white",
        cardBorder: "hover:border-emerald-400 hover:shadow-emerald-500/10",
    },
    {
        id: "biro-sdmo",
        title: "Biro SDMO",
        domain: "birosdmo.bgn.go.id",
        url: "https://birosdmo.bgn.go.id/",
        icon: Briefcase,
        iconBg: "bg-violet-600 text-white",
        cardBorder: "hover:border-violet-400 hover:shadow-violet-500/10",
    },
    {
        id: "tauwas-care",
        title: "Tauwas Care",
        domain: "tauwascare.bgn.go.id",
        url: "https://tauwascare.bgn.go.id/",
        icon: HeartPulse,
        iconBg: "bg-rose-600 text-white",
        cardBorder: "hover:border-rose-400 hover:shadow-rose-500/10",
    },
    {
        id: "survei-harga",
        title: "Survei Harga Pasar",
        domain: "buleleng • kemendag • bi",
        isSurveiGroup: true,
        icon: TrendingUp,
        iconBg: "bg-teal-600 text-white",
        cardBorder: "hover:border-teal-400 hover:shadow-teal-500/10",
        sources: [
            {
                nama: "Disdagperinkopukm Kab. Buleleng",
                desc: "Pengumuman & data harga pasar daerah Kab. Buleleng",
                domain: "disdagperinkopukm.bulelengkab.go.id",
                url: "https://disdagperinkopukm.bulelengkab.go.id/informasi/tampil/pengumuman/",
            },
            {
                nama: "Kemendag RI - SP2KP",
                desc: "Sistem Pemantauan Pasar & Kebutuhan Pokok Nasional",
                domain: "sp2kp.kemendag.go.id",
                url: "https://sp2kp.kemendag.go.id/",
            },
            {
                nama: "Bank Indonesia - PIHPS Pangan",
                desc: "Pusat Informasi Harga Pangan Strategis Nasional",
                domain: "bi.go.id/hargapangan",
                url: "https://www.bi.go.id/hargapangan/",
            },
        ],
    },
];

// Gandakan list untuk infinite loop otomatis
const infiniteLinks = [...webLinks, ...webLinks];

const scrollContainer = ref(null);
const isHovered = ref(false);
const isDragging = ref(false);
const isManualScrolling = ref(false);
const showSurveiModal = ref(false);
const activeSurveiItem = ref(null);

let currentScroll = 0;
let isInitialized = false;
let startX = 0;
let startScrollLeft = 0;
let dragMoved = 0;
let animationFrameId = null;
let manualScrollTimer = null;

// Kecepatan jalan santai dan pelan (0.4 pixel per frame)
const SCROLL_SPEED = 0.4;

function animate() {
    if (scrollContainer.value) {
        const el = scrollContainer.value;
        const halfWidth = el.scrollWidth / 2;

        if (halfWidth > 0) {
            if (!isInitialized) {
                currentScroll = el.scrollLeft;
                isInitialized = true;
            }

            // Jalankan hanya bila tidak sedang di-hover, tidak di-drag, dan tidak sedang manual scroll
            if (!isHovered.value && !isDragging.value && !isManualScrolling.value) {
                currentScroll += SCROLL_SPEED;
                if (currentScroll >= halfWidth) {
                    currentScroll -= halfWidth;
                }
                el.scrollLeft = currentScroll;
            } else {
                // Selaraskan posisi scroll jika user sedang menggeser manual
                currentScroll = el.scrollLeft;
            }
        }
    }
    animationFrameId = requestAnimationFrame(animate);
}

// Mouse dragging handlers
function onMouseDown(e) {
    if (!scrollContainer.value) return;
    isDragging.value = true;
    startX = e.pageX - scrollContainer.value.offsetLeft;
    startScrollLeft = scrollContainer.value.scrollLeft;
    dragMoved = 0;
}

function onMouseMove(e) {
    if (!isDragging.value || !scrollContainer.value) return;
    e.preventDefault();
    const x = e.pageX - scrollContainer.value.offsetLeft;
    const walk = x - startX;
    dragMoved = Math.abs(walk);
    scrollContainer.value.scrollLeft = startScrollLeft - walk;
    currentScroll = scrollContainer.value.scrollLeft;
}

function onMouseUp() {
    isDragging.value = false;
}

function onCardClick(e, item) {
    // Jika user menggeser (drag) lebih dari 6px, batalkan klik
    if (dragMoved > 6) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }

    if (item.isSurveiGroup) {
        activeSurveiItem.value = item;
        showSurveiModal.value = true;
        return;
    }

    if (item.url) {
        window.open(item.url, "_blank", "noopener,noreferrer");
    }
}

function openSurveiUrl(url) {
    showSurveiModal.value = false;
    window.open(url, "_blank", "noopener,noreferrer");
}

// Touch events for mobile/tablet
function onTouchStart() {
    isDragging.value = true;
    dragMoved = 0;
}

function onTouchMove() {
    dragMoved += 1;
}

function onTouchEnd() {
    setTimeout(() => {
        isDragging.value = false;
    }, 100);
}

// Hitung langkah geser selebar 1 card + gap
function getCardStep() {
    if (!scrollContainer.value) return 280;
    const cardEl = scrollContainer.value.querySelector("[data-card]");
    if (cardEl) {
        return cardEl.getBoundingClientRect().width + 16;
    }
    return scrollContainer.value.clientWidth / 4 + 16;
}

// Geser Panah Kiri Manual
function scrollLeftManual() {
    if (!scrollContainer.value) return;
    isManualScrolling.value = true;
    clearTimeout(manualScrollTimer);

    const el = scrollContainer.value;
    const halfWidth = el.scrollWidth / 2;
    const step = getCardStep();

    let target = el.scrollLeft - step;
    if (target < 0) {
        el.scrollLeft += halfWidth;
        currentScroll = el.scrollLeft;
        target = el.scrollLeft - step;
    }

    el.scrollTo({ left: target, behavior: "smooth" });
    currentScroll = target;

    manualScrollTimer = setTimeout(() => {
        isManualScrolling.value = false;
        if (scrollContainer.value) {
            currentScroll = scrollContainer.value.scrollLeft;
        }
    }, 700);
}

// Geser Panah Kanan Manual
function scrollRightManual() {
    if (!scrollContainer.value) return;
    isManualScrolling.value = true;
    clearTimeout(manualScrollTimer);

    const el = scrollContainer.value;
    const halfWidth = el.scrollWidth / 2;
    const step = getCardStep();

    let target = el.scrollLeft + step;
    if (target >= halfWidth * 2) {
        el.scrollLeft -= halfWidth;
        currentScroll = el.scrollLeft;
        target = el.scrollLeft + step;
    }

    el.scrollTo({ left: target, behavior: "smooth" });
    currentScroll = target;

    manualScrollTimer = setTimeout(() => {
        isManualScrolling.value = false;
        if (scrollContainer.value) {
            currentScroll = scrollContainer.value.scrollLeft;
        }
    }, 700);
}

onMounted(() => {
    animationFrameId = requestAnimationFrame(animate);
});

onBeforeUnmount(() => {
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
    if (manualScrollTimer) {
        clearTimeout(manualScrollTimer);
    }
});
</script>

<template>
    <div class="relative flex items-center w-full gap-2 sm:gap-3 py-1">
        <!-- Tombol Panah Kiri di ujung kiri kartu -->
        <button
            type="button"
            @click="scrollLeftManual"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
            class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-200/90 shadow-2xs sm:shadow-xs flex items-center justify-center transition-all cursor-pointer hover:border-blue-500 hover:text-blue-600 hover:scale-105 shrink-0 z-20"
            title="Geser ke Kiri"
        >
            <ChevronLeft class="h-5 w-5" />
        </button>

        <!-- Track Container (4 card sekali muncul di layar desktop lg:) -->
        <div class="relative flex-1 min-w-0 overflow-hidden">
            <!-- Subtle Left Gradient -->
            <div
                class="pointer-events-none absolute left-0 top-0 bottom-0 w-4 sm:w-6 bg-gradient-to-r from-slate-50 to-transparent z-10"
            ></div>

            <!-- Scrollable Track -->
            <div
                ref="scrollContainer"
                class="no-scrollbar flex gap-3 sm:gap-4 overflow-x-auto py-2.5 px-1 select-none cursor-grab active:cursor-grabbing"
                @mouseenter="isHovered = true"
                @mouseleave="isHovered = false; isDragging = false"
                @mousedown="onMouseDown"
                @mousemove="onMouseMove"
                @mouseup="onMouseUp"
                @touchstart="onTouchStart"
                @touchmove="onTouchMove"
                @touchend="onTouchEnd"
            >
                <div
                    v-for="(item, idx) in infiniteLinks"
                    :key="item.id + '-' + idx"
                    data-card
                    @click="onCardClick($event, item)"
                    class="w-[78%] sm:w-[calc((100%-1rem)/2)] md:w-[calc((100%-2*0.75rem)/3)] lg:w-[calc((100%-3*1rem)/4)] shrink-0 bg-white rounded-2xl border border-slate-200/90 p-4 sm:p-5 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between group relative cursor-pointer text-left min-h-[120px] sm:min-h-[130px]"
                    :class="item.cardBorder"
                >
                    <!-- Baris Atas: Icon di Kiri & Tombol Buka Putih di Pojok Kanan Atas -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <div
                            class="h-9 w-9 sm:h-10 sm:w-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs transition-transform duration-200 group-hover:scale-105"
                            :class="item.iconBg"
                        >
                            <component :is="item.icon" class="h-4.5 w-4.5 sm:h-5 sm:w-5" />
                        </div>

                        <!-- Tombol Buka Putih di Pojok Kanan Atas -->
                        <div
                            class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg bg-white border border-slate-200/90 text-slate-500 flex items-center justify-center shadow-2xs transition-all duration-200 group-hover:border-blue-400 group-hover:text-blue-600 group-hover:scale-105 shrink-0"
                            :title="item.isSurveiGroup ? 'Pilih survei pasar' : 'Buka link web'"
                        >
                            <ExternalLink class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        </div>
                    </div>

                    <!-- Baris Bawah: Cukup Nama & Link Saja (Tanpa judul baru di bawahnya) -->
                    <div class="min-w-0 mt-auto">
                        <h3 class="font-extrabold text-slate-900 text-sm sm:text-base group-hover:text-blue-600 transition-colors leading-snug break-words">
                            {{ item.title }}
                        </h3>
                        <p class="text-[11px] sm:text-xs font-mono text-slate-400 group-hover:text-blue-500 transition-colors break-all mt-1">
                            {{ item.domain }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Subtle Right Gradient -->
            <div
                class="pointer-events-none absolute right-0 top-0 bottom-0 w-4 sm:w-6 bg-gradient-to-l from-slate-50 to-transparent z-10"
            ></div>
        </div>

        <!-- Tombol Panah Kanan di ujung kanan kartu -->
        <button
            type="button"
            @click="scrollRightManual"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
            class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-200/90 shadow-2xs sm:shadow-xs flex items-center justify-center transition-all cursor-pointer hover:border-blue-500 hover:text-blue-600 hover:scale-105 shrink-0 z-20"
            title="Geser ke Kanan"
        >
            <ChevronRight class="h-5 w-5" />
        </button>

        <!-- Modal Pilihan Survei Harga Pasar (Disdagperinkopukm Buleleng, Kemendag RI, Bank Indonesia) -->
        <div
            v-if="showSurveiModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
            @click.self="showSurveiModal = false"
        >
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-5 space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-teal-600 text-white flex items-center justify-center shadow-2xs">
                            <TrendingUp class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <h4 class="font-extrabold text-slate-900 text-base">Survei Harga Pasar</h4>
                            <p class="text-xs text-slate-500">Pilih portal harga pangan yang ingin dibuka:</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showSurveiModal = false"
                        class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-2.5">
                    <button
                        v-for="(src, sIdx) in activeSurveiItem?.sources || []"
                        :key="sIdx"
                        type="button"
                        @click="openSurveiUrl(src.url)"
                        class="w-full text-left p-3.5 rounded-xl border border-slate-200/90 hover:border-teal-400 hover:bg-teal-50/40 transition-all flex items-center justify-between gap-3 group cursor-pointer"
                    >
                        <div class="min-w-0">
                            <span class="font-bold text-slate-900 text-xs sm:text-sm group-hover:text-teal-700 transition-colors block">
                                {{ src.nama }}
                            </span>
                            <span class="text-[11px] font-mono text-slate-400 block mt-0.5">
                                {{ src.domain }}
                            </span>
                        </div>
                        <div class="h-7 w-7 rounded-lg bg-slate-100 group-hover:bg-teal-600 text-slate-500 group-hover:text-white flex items-center justify-center shrink-0 transition-colors">
                            <ExternalLink class="h-3.5 w-3.5" />
                        </div>
                    </button>
                </div>

                <div class="pt-2 flex justify-end">
                    <button
                        type="button"
                        @click="showSurveiModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
