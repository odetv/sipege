<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import {
    Tag,
    Printer,
    QrCode,
    Calendar,
    Clock,
    Building2,
    School,
    CheckCircle2,
    AlertCircle,
    ShieldCheck,
    Utensils,
    PackageCheck,
    Check,
} from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    kelompokList: {
        type: Array,
        default: () => [],
    },
});

// Setup parameter cetak label
const todayStr = new Date().toISOString().split("T")[0];
const tanggalProduksi = ref(todayStr);
const waktuMasak = ref("06:00 WITA");
const waktuBatasKonsumsi = ref("11:30 WITA");
const petunjukMenu = ref("Nasi Kuning Ayam Suwir & Telur Iris + Sayur Buncis");

// State kelompok terpilih (default: semua tercentang)
const selectedKelompokIds = ref(props.kelompokList.map((k) => k.id));

const isAllSelected = computed({
    get() {
        return (
            props.kelompokList.length > 0 &&
            selectedKelompokIds.value.length === props.kelompokList.length
        );
    },
    set(val) {
        if (val) {
            selectedKelompokIds.value = props.kelompokList.map((k) => k.id);
        } else {
            selectedKelompokIds.value = [];
        }
    },
});

const printableKelompokList = computed(() => {
    return props.kelompokList.filter((k) =>
        selectedKelompokIds.value.includes(k.id),
    );
});

function toggleKelompok(id) {
    const idx = selectedKelompokIds.value.indexOf(id);
    if (idx > -1) {
        selectedKelompokIds.value.splice(idx, 1);
    } else {
        selectedKelompokIds.value.push(id);
    }
}

function handlePrint() {
    window.print();
}

function formatTanggalIndo(dateStr) {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    return d.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}
</script>

<template>
    <AppLayout
        title="Label"
        subtitle="Generator & Pencetakan Label Kemasan Box Makanan SPPG"
        :user="user"
        :unit-sppg="unitSppg"
    >
        <Head title="Label" />

        <div class="space-y-6">
            <!-- 1. Header Control Panel (Disembunyikan saat Print) -->
            <div class="print:hidden space-y-6">
                <Card className="bg-white border-slate-200/80 shadow-xs">
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <div>
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <Tag class="h-5 w-5 text-primary" />
                                    <span
                                        >Konfigurasi & Parameter Label
                                        Kemasan</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Sesuaikan tanggal produksi, waktu batas aman
                                    konsumsi, dan pilih kelompok sasaran sebelum
                                    mencetak.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                @click="handlePrint"
                                :disabled="printableKelompokList.length === 0"
                                className="h-10 px-4 bg-primary hover:bg-primary/90 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-sm cursor-pointer shrink-0"
                            >
                                <Printer class="h-4 w-4" />
                                <span
                                    >Cetak Label ({{
                                        printableKelompokList.length
                                    }}
                                    Kelompok)</span
                                >
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5 space-y-4">
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                        >
                            <!-- Tanggal Produksi -->
                            <div class="space-y-1.5">
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Calendar
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <span>Tanggal Distribusi:</span>
                                </label>
                                <input
                                    type="date"
                                    v-model="tanggalProduksi"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2"
                                />
                            </div>

                            <!-- Waktu Masak -->
                            <div class="space-y-1.5">
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Clock class="h-3.5 w-3.5 text-primary" />
                                    <span>Waktu Selesai Masak:</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="waktuMasak"
                                    placeholder="Contoh: 06:00 WITA"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2"
                                />
                            </div>

                            <!-- Waktu Batas Konsumsi -->
                            <div class="space-y-1.5">
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <AlertCircle
                                        class="h-3.5 w-3.5 text-amber-600"
                                    />
                                    <span>Batas Aman Konsumsi:</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="waktuBatasKonsumsi"
                                    placeholder="Contoh: 11:30 WITA"
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2 text-amber-900 bg-amber-50/40"
                                />
                            </div>

                            <!-- Menu Makanan -->
                            <div class="space-y-1.5">
                                <label
                                    class="text-xs font-bold text-slate-700 flex items-center gap-1.5"
                                >
                                    <Utensils
                                        class="h-3.5 w-3.5 text-emerald-600"
                                    />
                                    <span>Menu Makanan Hari Ini:</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="petunjukMenu"
                                    placeholder="Ringkasan Menu..."
                                    class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:ring-primary focus:border-primary p-2"
                                />
                            </div>
                        </div>

                        <!-- Filter Kelompok Terpilih -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700"
                                    >Pilih Sasaran Kelompok yang Dicetak:</span
                                >
                                <label
                                    class="flex items-center gap-1.5 text-xs font-bold text-primary cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="isAllSelected"
                                        class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4"
                                    />
                                    <span
                                        >Pilih Semua Kelompok ({{
                                            kelompokList.length
                                        }})</span
                                    >
                                </label>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="k in kelompokList"
                                    :key="k.id"
                                    type="button"
                                    @click="toggleKelompok(k.id)"
                                    :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-semibold border transition-all cursor-pointer flex items-center gap-1.5',
                                        selectedKelompokIds.includes(k.id)
                                            ? 'bg-primary/10 text-primary border-primary/30 shadow-2xs font-bold'
                                            : 'bg-slate-50 text-slate-400 border-slate-200 opacity-60 line-through',
                                    ]"
                                >
                                    <Check
                                        v-if="
                                            selectedKelompokIds.includes(k.id)
                                        "
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <span>{{ k.nama_kelompok }}</span>
                                    <span
                                        class="text-[10px] text-slate-500 bg-white px-1.5 py-0.2 rounded border border-slate-200"
                                    >
                                        {{ k.total_penerima }} porsi
                                    </span>
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- 2. Printable Label Grid Cards -->
            <div class="space-y-4">
                <div class="flex items-center justify-between print:hidden">
                    <h3
                        class="text-sm font-bold text-slate-800 flex items-center gap-2"
                    >
                        <PackageCheck class="h-4 w-4 text-primary" />
                        <span
                            >Pratinjau Label Cetak ({{
                                printableKelompokList.length
                            }}
                            Kartu Label)</span
                        >
                    </h3>
                    <span class="text-xs text-slate-500"
                        >Format stiker kemasan box standar SPPG</span
                    >
                </div>

                <!-- Print Container Grid -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-4 print:grid-cols-2 print:gap-4"
                >
                    <div
                        v-for="k in printableKelompokList"
                        :key="k.id"
                        class="bg-white rounded-xl border-2 border-slate-300 p-4 shadow-sm relative overflow-hidden flex flex-col justify-between print:border-slate-800 print:shadow-none print:break-inside-avoid print:p-3"
                    >
                        <!-- Header Label: SPPG Unit Info -->
                        <div
                            class="border-b-2 border-slate-200 pb-2.5 flex items-start justify-between gap-3"
                        >
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="h-9 w-9 rounded-lg bg-primary text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0 print:border print:border-slate-800"
                                >
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4
                                        class="font-extrabold text-xs sm:text-sm text-slate-900 leading-tight uppercase"
                                    >
                                        {{
                                            unitSppg?.nama ||
                                            "SPPG UNIT LAYANAN"
                                        }}
                                    </h4>
                                    <p
                                        class="text-[10px] text-slate-500 font-semibold mt-0.5"
                                    >
                                        ID: {{ unitSppg?.id_sppg || "-" }} •
                                        {{ unitSppg?.kabupaten || "Buleleng" }}
                                    </p>
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                className="bg-emerald-50 text-emerald-800 border-emerald-300 font-extrabold text-[10px] shrink-0 uppercase"
                            >
                                SEHAT & BERGIZI
                            </Badge>
                        </div>

                        <!-- Middle: Kelompok Penerima & Porsi Breakdown -->
                        <div class="py-3 space-y-2.5">
                            <div>
                                <span
                                    class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider block"
                                    >Tujuan Distribusi Kelompok:</span
                                >
                                <h3
                                    class="text-sm sm:text-base font-black text-slate-900 leading-tight mt-0.5"
                                >
                                    {{ k.nama_kelompok }}
                                </h3>
                                <p
                                    class="text-[11px] text-slate-600 font-medium"
                                >
                                    {{
                                        k.alamat_lengkap ||
                                        `${k.desa_kelurahan}, ${k.kecamatan}`
                                    }}
                                </p>
                            </div>

                            <!-- Porsi Matrix Badge Grid -->
                            <div
                                class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-center print:bg-white print:border-slate-400"
                            >
                                <div
                                    class="p-1 rounded bg-amber-50/70 border border-amber-200/80 print:bg-white"
                                >
                                    <span
                                        class="text-[9px] font-bold text-amber-800 uppercase block"
                                        >Porsi Kecil</span
                                    >
                                    <span
                                        class="text-sm font-black text-amber-900"
                                        >{{ k.total_porsi_kecil || 0 }} PK</span
                                    >
                                </div>
                                <div
                                    class="p-1 rounded bg-indigo-50/70 border border-indigo-200/80 print:bg-white"
                                >
                                    <span
                                        class="text-[9px] font-bold text-indigo-800 uppercase block"
                                        >Porsi Besar</span
                                    >
                                    <span
                                        class="text-sm font-black text-indigo-900"
                                        >{{ k.total_porsi_besar || 0 }} PB</span
                                    >
                                </div>
                                <div
                                    class="p-1 rounded bg-emerald-50/70 border border-emerald-200/80 print:bg-white"
                                >
                                    <span
                                        class="text-[9px] font-bold text-emerald-800 uppercase block"
                                        >Total Porsi</span
                                    >
                                    <span
                                        class="text-sm font-black text-emerald-950"
                                        >{{ k.total_penerima || 0 }} Box</span
                                    >
                                </div>
                            </div>

                            <!-- Menu & Ketentuan Tanggal -->
                            <div
                                class="text-[10.5px] space-y-1 bg-white p-2 rounded border border-slate-100"
                            >
                                <p
                                    class="text-slate-700 font-semibold truncate"
                                >
                                    <strong class="text-slate-900"
                                        >Menu:</strong
                                    >
                                    {{ petunjukMenu }}
                                </p>
                                <div
                                    class="flex items-center justify-between text-slate-600 text-[10px]"
                                >
                                    <span
                                        >Tgl:
                                        <strong>{{
                                            formatTanggalIndo(tanggalProduksi)
                                        }}</strong></span
                                    >
                                    <span
                                        >Masak:
                                        <strong>{{ waktuMasak }}</strong></span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Footer: Best Before & Hygiene Notice -->
                        <div
                            class="border-t-2 border-slate-200 pt-2 flex items-center justify-between text-[10px]"
                        >
                            <div
                                class="flex items-center gap-1 text-rose-700 font-extrabold"
                            >
                                <Clock class="h-3.5 w-3.5 shrink-0" />
                                <span
                                    >Batas Konsumsi: Maks
                                    {{ waktuBatasKonsumsi }}</span
                                >
                            </div>
                            <div
                                class="flex items-center gap-1 text-slate-500 font-medium"
                            >
                                <ShieldCheck
                                    class="h-3.5 w-3.5 text-emerald-600 shrink-0"
                                />
                                <span>Higienis & Halal</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="printableKelompokList.length === 0"
                    class="p-8 text-center bg-white rounded-xl border border-slate-200 text-slate-400 font-semibold print:hidden"
                >
                    Tidak ada kelompok sasaran yang dipilih untuk dicetak.
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Sembunyikan elemen layout yang tidak perlu saat cetak */
    body {
        background: #ffffff !important;
        font-family: inherit !important;
    }
    aside,
    header,
    footer,
    .print\:hidden {
        display: none !important;
    }
    main,
    .max-w-7xl,
    .space-y-6 {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
}
</style>
