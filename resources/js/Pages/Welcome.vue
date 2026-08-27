<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    LogIn,
    UserPlus,
    Building2,
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
    <Head title="Sistem Pengelolaan SPPG" />

    <div
        class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-blue-50/40 flex flex-col justify-between text-slate-900"
    >
        <!-- Navbar -->
        <header
            class="border-b border-slate-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-50"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between"
            >
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-lg bg-primary flex items-center justify-center text-white shadow-md shadow-primary/20"
                    >
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h1
                            class="font-bold text-lg leading-tight tracking-tight text-slate-900"
                        >
                            SIPEGE
                        </h1>
                        <p
                            class="text-[11px] text-slate-500 font-medium leading-none"
                        >
                            Sistem Pengelolaan SPPG
                        </p>
                    </div>
                </div>

                <!-- Auth Navigation -->
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                    >
                        <Button
                            variant="default"
                            className="flex items-center gap-2"
                        >
                            <span>Dashboard</span>
                        </Button>
                    </Link>
                    <template v-else>
                        <Link :href="route('login')">
                            <Button
                                variant="outline"
                                className="flex items-center gap-2"
                            >
                                <LogIn class="h-4 w-4 text-slate-600" />
                                <span>Masuk</span>
                            </Button>
                        </Link>

                        <Link :href="route('register')">
                            <Button
                                variant="default"
                                className="flex items-center gap-2 shadow-sm"
                            >
                                <UserPlus class="h-4 w-4" />
                                <span>Daftar</span>
                            </Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Hero / Content -->
        <main
            class="max-w-4xl mx-auto px-4 py-16 sm:py-24 text-center flex flex-col items-center justify-center my-auto"
        >
            <Badge
                variant="info"
                className="mb-4 px-3 py-1 text-xs font-semibold uppercase tracking-wider"
            >
                SIPEGE
            </Badge>

            <h1
                class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-tight mb-6"
            >
                Sistem Pengelolaan SPPG <br class="hidden sm:inline" />
            </h1>

            <p
                class="text-lg sm:text-xl text-slate-600 max-w-3xl mx-auto mb-10 leading-relaxed"
            >
                Sistem informasi dan pengelolaan SPPG terpadu untuk memudahkan
                pendataan dan pengelolaan data unit SPPG, pengelolaan gizi, dan
                keuangan.
            </p>

            <!-- Feature cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full text-left">
                <Card className="border-slate-200/90 shadow-sm bg-white/90">
                    <CardContent className="p-5">
                        <div
                            class="h-10 w-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-3"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-base mb-1">
                            Penerima Manfaat
                        </h3>
                        <p class="text-sm text-slate-500">
                            Kelola data kelompok dan rincian penerima manfaat
                            SPPG berdasarkan kategori dan subkategori.
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-slate-200/90 shadow-sm bg-white/90">
                    <CardContent className="p-5">
                        <div
                            class="h-10 w-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center mb-3"
                        >
                            <UtensilsCrossed class="h-5 w-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-base mb-1">
                            Gizi
                        </h3>
                        <p class="text-sm text-slate-500">
                            Pencatatan menu makanan bergizi, jadwal distribusi,
                            dan pemantauan status gizi penerima manfaat.
                        </p>
                    </CardContent>
                </Card>

                <Card className="border-slate-200/90 shadow-sm bg-white/90">
                    <CardContent className="p-5">
                        <div
                            class="h-10 w-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3"
                        >
                            <Wallet class="h-5 w-5" />
                        </div>
                        <h3 class="font-bold text-slate-900 text-base mb-1">
                            Keuangan
                        </h3>
                        <p class="text-sm text-slate-500">
                            Pengelolaan anggaran, realisasi belanja, dan laporan
                            keuangan unit SPPG secara transparan.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500"
        >
            <div
                class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2"
            >
                <p>
                    &copy; {{ new Date().getFullYear() }} SIPEGE - Sistem
                    Pengelolaan SPPG.
                </p>
                <p class="text-slate-400">
                    Badan Gizi Nasional - Satuan Pelayanan Pemenuhan Gizi
                </p>
            </div>
        </footer>
    </div>
</template>
