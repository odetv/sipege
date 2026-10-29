<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    LogIn,
    Users,
    UtensilsCrossed,
    Wallet,
} from "lucide-vue-next";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Sistem Pengelolaan SPPG - Badan Gizi Nasional" />

    <div
        class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 flex flex-col justify-between text-slate-900"
    >
        <!-- Navbar -->
        <header
            class="border-b border-slate-200/80 bg-white/85 backdrop-blur-md sticky top-0 z-50 shadow-2xs"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6 min-h-[5rem] sm:min-h-[5.75rem] flex items-center justify-between gap-4"
            >
                <!-- Logo BGN di Header -->
                <Link :href="'/'" class="flex items-center gap-3 sm:gap-4 group shrink-0 min-w-0">
                    <img
                        src="/images/logo/BGN_LOGO_MAIN.png"
                        alt="Logo Badan Gizi Nasional"
                        class="h-10 w-10 sm:h-12 sm:w-12 object-contain drop-shadow-xs transition-transform duration-200 group-hover:scale-105 shrink-0"
                    />
                    <div class="min-w-0">
                        <h1
                            class="font-extrabold text-lg sm:text-2xl leading-tight tracking-tight text-slate-900 group-hover:text-primary transition-colors"
                        >
                            SIPEGE
                        </h1>
                        <p
                            class="text-xs sm:text-sm text-slate-500 font-medium leading-none truncate max-w-[150px] sm:max-w-none mt-1"
                        >
                            Sistem Pengelolaan SPPG
                        </p>
                    </div>
                </Link>

                <!-- Auth Navigation: Hanya 1 Tombol Masuk -->
                <nav class="flex items-center gap-3 shrink-0">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                    >
                        <Button
                            variant="default"
                            size="default"
                            className="flex items-center gap-2 text-xs sm:text-sm h-10 sm:h-11 px-5 sm:px-6 rounded-xl font-bold shadow-xs hover:shadow transition-all"
                        >
                            <span>Dashboard</span>
                        </Button>
                    </Link>
                    <template v-else>
                        <Link :href="route('login')">
                            <Button
                                variant="default"
                                size="default"
                                className="flex items-center gap-2 text-xs sm:text-sm h-10 sm:h-11 px-5 sm:px-6 rounded-xl font-bold shadow-xs hover:shadow transition-all"
                            >
                                <LogIn class="h-4 w-4" />
                                <span>Masuk</span>
                            </Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Hero / Content -->
        <main
            class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-16 md:py-20 text-center flex flex-col items-center justify-center my-auto w-full"
        >
            <Badge
                variant="info"
                className="mb-3 sm:mb-4 px-2.5 sm:px-3 py-0.5 sm:py-1 text-[11px] sm:text-xs font-semibold uppercase tracking-wider rounded-full"
            >
                SIPEGE
            </Badge>

            <h1
                class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight mb-3 sm:mb-6"
            >
                Sistem Pengelolaan SPPG <br class="hidden sm:inline" />
            </h1>

            <p
                class="text-sm sm:text-base md:text-xl text-slate-600 max-w-2xl mx-auto mb-6 sm:mb-10 leading-relaxed"
            >
                Sistem informasi dan pengelolaan SPPG terpadu untuk memudahkan
                pendataan dan pengelolaan data unit SPPG, pengelolaan gizi, dan
                keuangan.
            </p>

            <!-- Feature cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-6 w-full text-left">
                <Card className="border-slate-200/90 shadow-2xs sm:shadow-sm bg-white/95 rounded-xl sm:rounded-2xl transition-all hover:shadow-md">
                    <CardContent className="p-4 sm:p-5">
                        <div
                            class="h-9 w-9 sm:h-10 sm:w-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-2.5 sm:mb-3"
                        >
                            <Users class="h-4 w-4 sm:h-5 sm:w-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base mb-1">
                            Penerima Manfaat
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Kelola data kelompok dan rincian penerima manfaat
                            SPPG berdasarkan kategori dan subkategori.
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-slate-200/90 shadow-2xs sm:shadow-sm bg-white/95 rounded-xl sm:rounded-2xl transition-all hover:shadow-md">
                    <CardContent className="p-4 sm:p-5">
                        <div
                            class="h-9 w-9 sm:h-10 sm:w-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center mb-2.5 sm:mb-3"
                        >
                            <UtensilsCrossed class="h-4 w-4 sm:h-5 sm:w-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base mb-1">
                            Gizi
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Pencatatan menu makanan bergizi, jadwal distribusi,
                            dan pemantauan status gizi penerima manfaat.
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-slate-200/90 shadow-2xs sm:shadow-sm bg-white/95 rounded-xl sm:rounded-2xl transition-all hover:shadow-md">
                    <CardContent className="p-4 sm:p-5">
                        <div
                            class="h-9 w-9 sm:h-10 sm:w-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2.5 sm:mb-3"
                        >
                            <Wallet class="h-4 w-4 sm:h-5 sm:w-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base mb-1">
                            Keuangan
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Pengelolaan anggaran, realisasi belanja, dan laporan
                            keuangan unit SPPG secara transparan.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white py-4 sm:py-6 text-center text-[11px] sm:text-xs text-slate-500"
        >
            <div
                class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-1.5 sm:gap-2"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }} SIPEGE - Sistem
                    Pengelolaan SPPG.
                </p>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <img
                        src="/images/logo/BGN_LOGO_MAIN.png"
                        alt="Logo BGN"
                        class="h-3.5 w-3.5 sm:h-4 sm:w-4 object-contain opacity-75"
                    />
                    <p class="text-slate-500">
                        Badan Gizi Nasional - Satuan Pelayanan Pemenuhan Gizi
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
