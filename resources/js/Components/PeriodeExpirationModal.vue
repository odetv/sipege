<script setup>
import { ref, computed, onMounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import {
    CalendarClock,
    CalendarX,
    CalendarPlus,
    Clock,
    AlertTriangle,
    X,
    ArrowRight,
    Sparkles,
    CheckCircle2,
    CalendarDays,
} from "lucide-vue-next";

const page = usePage();
const alertData = computed(() => page.props.periodeAlert || null);

const showModal = ref(false);

onMounted(() => {
    if (!alertData.value || !alertData.value.should_show) return;

    // Jangan munculkan pop up jika pengguna sedang aktif membuka halaman manajemen periode
    if (
        typeof window !== "undefined" &&
        window.location.pathname.includes("/pengaturan/periode")
    ) {
        return;
    }

    // Identifikasi sesi untuk H-1, H-0, atau expired periode
    const sessionKey = `sipege_periode_alert_seen_p${alertData.value.periode_id}_d${alertData.value.days_remaining}`;
    
    // Saat pertama kali web dibuka dalam sesi browser (sessionStorage belum ada flag)
    const isDismissed = sessionStorage.getItem(sessionKey);
    if (!isDismissed) {
        // Beri jeda 400ms agar halaman awal tampil stabil terlebih dahulu
        setTimeout(() => {
            showModal.value = true;
        }, 400);
    }
});

function closeModal() {
    showModal.value = false;
    if (alertData.value) {
        const sessionKey = `sipege_periode_alert_seen_p${alertData.value.periode_id}_d${alertData.value.days_remaining}`;
        sessionStorage.setItem(sessionKey, "1");
    }
}

function handleBuatPeriode() {
    closeModal();
    router.visit(route("periode.index", { action: "tambah" }));
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showModal && alertData"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-3 sm:p-5 bg-slate-900/65 backdrop-blur-xs overflow-y-auto overscroll-contain"
                @click.self="closeModal"
            >
                <Transition
                    enter-active-class="transition duration-300 ease-out transform"
                    enter-from-class="opacity-0 scale-95 translate-y-2 sm:translate-y-3"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in transform"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2 sm:translate-y-3"
                >
                    <div
                        v-if="showModal"
                        class="relative w-full max-w-lg bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-100 overflow-hidden text-slate-800 flex flex-col max-h-[calc(100dvh-1.5rem)] sm:max-h-[88dvh] my-auto"
                    >
                        <!-- Top Banner / Header Visual (Shrink 0: Selalu utuh & tidak terpotong) -->
                        <div
                            :class="[
                                'relative p-4 sm:p-6 text-white overflow-hidden shrink-0',
                                alertData.is_expired
                                    ? 'bg-gradient-to-br from-rose-600 via-rose-700 to-red-800'
                                    : alertData.is_last_day
                                    ? 'bg-gradient-to-br from-orange-500 via-amber-600 to-rose-600'
                                    : 'bg-gradient-to-br from-amber-500 via-amber-600 to-orange-600',
                            ]"
                        >
                            <!-- Decorative background glow & shapes -->
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="absolute -bottom-8 -left-8 w-28 h-28 bg-black/10 rounded-full blur-xl pointer-events-none"></div>

                            <!-- Tombol Close pojok kanan atas -->
                            <button
                                type="button"
                                @click="closeModal"
                                class="absolute top-3 right-3 sm:top-4 sm:right-4 p-1.5 sm:p-2 rounded-full bg-black/20 hover:bg-black/35 text-white/90 hover:text-white transition-all cursor-pointer z-20"
                                title="Tutup pop up"
                            >
                                <X class="h-4 w-4 sm:h-5 sm:w-5" />
                            </button>

                            <div class="flex items-start gap-3 sm:gap-4 relative z-10 pr-6 sm:pr-8">
                                <!-- Icon Badge -->
                                <div
                                    class="h-11 w-11 sm:h-13 sm:w-13 rounded-xl sm:rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center shrink-0 shadow-md text-white mt-0.5"
                                >
                                    <CalendarX v-if="alertData.is_expired" class="h-5 w-5 sm:h-6 sm:w-6" />
                                    <Clock v-else-if="alertData.is_last_day" class="h-5 w-5 sm:h-6 sm:w-6 animate-pulse" />
                                    <AlertTriangle v-else class="h-5 w-5 sm:h-6 sm:w-6 animate-bounce" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <!-- Status Pill -->
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider bg-white/25 border border-white/35 text-white shadow-2xs mb-1.5">
                                        <span v-if="alertData.is_expired">🚨 Telah Berakhir</span>
                                        <span v-else-if="alertData.is_last_day">⏰ Hari Terakhir (H-0)</span>
                                        <span v-else>⏳ Segera Berakhir (H-1)</span>
                                    </div>

                                    <h3 class="text-base sm:text-lg font-black text-white leading-tight">
                                        {{ alertData.is_expired ? 'Masa Periode Telah Berakhir!' : 'Masa Periode Segera Berakhir!' }}
                                    </h3>
                                    <p class="text-[11px] sm:text-xs text-white/90 font-medium mt-1 leading-snug">
                                        Periode berikutnya belum dibuat. Harap persiapkan periode baru agar alur operasional MBG tetap berjalan lancar.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Body (Scrollable jika layar HP pendek/kecil) -->
                        <div class="flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 space-y-3.5 sm:space-y-4">
                            <!-- Kartu Rincian Periode Berjalan -->
                            <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 pb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 sm:h-7 sm:w-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                            <CalendarDays class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                        </div>
                                        <div>
                                            <span class="text-[9.5px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Periode Saat Ini</span>
                                            <h4 class="text-xs sm:text-sm font-black text-slate-900 leading-tight">
                                                Periode {{ alertData.nomor_periode }}
                                            </h4>
                                        </div>
                                    </div>
                                    
                                    <!-- Badge Sisa Hari -->
                                    <div
                                        :class="[
                                            'px-2.5 py-1 rounded-lg sm:rounded-xl text-[10.5px] sm:text-xs font-black shadow-2xs border text-center shrink-0',
                                            alertData.is_expired
                                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                : alertData.is_last_day
                                                ? 'bg-orange-50 text-orange-700 border-orange-200'
                                                : 'bg-amber-50 text-amber-800 border-amber-200',
                                        ]"
                                    >
                                        <span v-if="alertData.is_expired">
                                            Lewat {{ Math.abs(alertData.days_remaining) }} Hari
                                        </span>
                                        <span v-else-if="alertData.is_last_day">
                                            Hari Ini Hari Terakhir
                                        </span>
                                        <span v-else>
                                            Tinggal 1 Hari Lagi
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-white p-2 sm:p-2.5 rounded-lg sm:rounded-xl border border-slate-200/60 shadow-2xs">
                                        <span class="text-[9.5px] sm:text-[10px] font-bold text-slate-400 block mb-0.5">Tanggal Mulai</span>
                                        <span class="text-[11px] sm:text-xs font-bold text-slate-700 leading-tight block">{{ alertData.tanggal_mulai_indo }}</span>
                                    </div>
                                    <div class="bg-white p-2 sm:p-2.5 rounded-lg sm:rounded-xl border border-slate-200/60 shadow-2xs">
                                        <span class="text-[9.5px] sm:text-[10px] font-bold text-slate-400 block mb-0.5">Tanggal Selesai</span>
                                        <span class="text-[11px] sm:text-xs font-black text-slate-900 leading-tight block">{{ alertData.tanggal_selesai_indo }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Peringatan Periode Yang Akan Datang Belum Ada -->
                            <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-amber-50/80 border border-amber-200/80 flex items-start gap-2.5 sm:gap-3 text-amber-900">
                                <div class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg sm:rounded-xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                                    <CalendarPlus class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                </div>
                                <div class="text-[11px] sm:text-xs space-y-1 min-w-0">
                                    <h5 class="font-black text-amber-950 flex items-center gap-1.5 flex-wrap">
                                        <span>Periode {{ alertData.next_nomor_periode }} Belum Dibuat</span>
                                        <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-amber-200 text-amber-900 font-extrabold uppercase">Wajib</span>
                                    </h5>
                                    <p class="text-amber-800 leading-relaxed font-normal">
                                        Sistem mendeteksi belum ada periode yang akan datang. Pembuatan Work Order (WO), rancang menu, dan distribusi makanan bergizi gratis memerlukan periode baru.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons (Shrink 0: Selalu tampil di bagian bawah tanpa terpotong) -->
                        <div class="shrink-0 p-3.5 sm:p-5 border-t border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row items-center gap-2">
                            <button
                                type="button"
                                @click="handleBuatPeriode"
                                class="w-full sm:flex-1 py-2.5 sm:py-3 px-4 rounded-xl sm:rounded-2xl bg-primary hover:bg-primary/90 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md shadow-primary/25 hover:shadow-primary/35 transition-all cursor-pointer active:scale-98"
                            >
                                <CalendarPlus class="h-4 w-4 shrink-0" />
                                <span>Buat Periode Baru Sekarang</span>
                                <ArrowRight class="h-4 w-4 shrink-0" />
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="w-full sm:w-auto py-2.5 sm:py-3 px-4 rounded-xl sm:rounded-2xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs sm:text-sm border border-slate-200 shadow-2xs transition-all cursor-pointer"
                            >
                                Ingatkan Nanti
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
