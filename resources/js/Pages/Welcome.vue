<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import Badge from "@/Components/ui/Badge.vue";
import WebLinksCarousel from "@/Components/WebLinksCarousel.vue";
import { LogIn, LogOut } from "lucide-vue-next";

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

                <!-- Auth Navigation -->
                <nav class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <template v-if="$page.props.auth?.user">
                        <Link :href="route('dashboard')">
                            <Button
                                variant="default"
                                size="default"
                                className="flex items-center gap-2 text-xs sm:text-sm h-10 sm:h-11 px-5 sm:px-6 rounded-xl font-bold shadow-xs hover:shadow transition-all"
                            >
                                <span>Dashboard</span>
                            </Button>
                        </Link>

                        <!-- Tombol Logout (Hanya Icon) di sebelah kanan tombol Dashboard -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="h-10 w-10 sm:h-11 sm:w-11 flex items-center justify-center rounded-xl bg-white border border-slate-200/90 hover:border-rose-300 hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition-all shadow-2xs hover:shadow-xs cursor-pointer shrink-0"
                            title="Keluar / Logout"
                            aria-label="Keluar / Logout"
                        >
                            <LogOut class="w-4 h-4 sm:w-5 sm:h-5" />
                        </Link>
                    </template>
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

        <!-- Main Content -->
        <main class="w-full flex-1 flex flex-col justify-center my-auto py-6 sm:py-10">
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 text-center flex flex-col items-center justify-center w-full"
            >
                <Badge
                    variant="info"
                    className="mb-3 sm:mb-4 px-2.5 sm:px-3 py-0.5 sm:py-1 text-[11px] sm:text-xs font-semibold uppercase tracking-wider rounded-full"
                >
                    SIPEGE
                </Badge>

                <h1
                    class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight mb-2 sm:mb-4"
                >
                    Sistem Pengelolaan SPPG <br class="hidden sm:inline" />
                </h1>

                <p
                    class="text-sm sm:text-base md:text-lg text-slate-600 max-w-2xl mx-auto mb-6 sm:mb-10 leading-relaxed"
                >
                    Sistem informasi dan pengelolaan SPPG terpadu untuk memudahkan
                    pendataan dan pengelolaan data unit SPPG, pengelolaan gizi, dan
                    keuangan.
                </p>

                <!-- Carousel Card Link Web Penting (Menggantikan 3 card lama, 4 card sekali muncul, panah di ujung kiri & kanan) -->
                <div class="w-full">
                    <WebLinksCarousel />
                </div>
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
