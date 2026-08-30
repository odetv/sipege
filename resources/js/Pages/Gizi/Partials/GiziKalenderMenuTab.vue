<script setup>
import { ref, computed } from "vue";
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
    ChevronLeft,
    ChevronRight,
    Utensils,
    CalendarCheck,
    CheckCircle2,
    Clock,
    Plus,
    UtensilsCrossed,
    X,
} from "lucide-vue-next";

const props = defineProps({
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (num) => {
            if (!num) return "Rp 0";
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                maximumFractionDigits: 0,
            }).format(num);
        },
    },
    formatTanggalIndo: {
        type: Function,
        default: (tgl) => {
            if (!tgl) return "-";
            try {
                const d = new Date(tgl);
                return d.toLocaleDateString("id-ID", {
                    day: "numeric",
                    month: "short",
                    year: "numeric",
                });
            } catch {
                return tgl;
            }
        },
    },
});

const emit = defineEmits(["openRancangMenu"]);

const kalenderBulan = ref("Agustus 2026");
const siklusAktif = ref("10 Hari");
const selectedKalenderItem = ref(null);

const defaultJadwalMenuBulan = ref([
    {
        tanggal: "2026-08-03",
        tglNo: 3,
        hari: "Senin",
        siklusKe: 1,
        namaMenu:
            "Nasi Kuning Ayam Suwir, Telur Balado, Tempe Orek, Tumis Buncis & Pisang",
        status: "Selesai",
        kaloriPK: 485,
        kaloriPB: 640,
        costPK: 9850,
        costPB: 13950,
        komponen: [
            "Beras Putih",
            "Ayam Suwir",
            "Telur Balado",
            "Tempe Orek",
            "Buncis",
            "Pisang Raja",
        ],
    },
    {
        tanggal: "2026-08-04",
        tglNo: 4,
        hari: "Selasa",
        siklusKe: 2,
        namaMenu:
            "Nasi Uduk, Semur Telur & Tahu, Bihun Goreng Sayur, Ketimun & Jeruk",
        status: "Selesai",
        kaloriPK: 460,
        kaloriPB: 610,
        costPK: 9200,
        costPB: 13100,
        komponen: [
            "Beras Uduk",
            "Semur Telur",
            "Tahu Semur",
            "Bihun Jagung",
            "Jeruk Manis",
        ],
    },
    {
        tanggal: "2026-08-05",
        tglNo: 5,
        hari: "Rabu",
        siklusKe: 3,
        namaMenu:
            "Nasi Putih, Ikan Goreng Tepung, Sayur Asem Komplit, Tempe Goreng & Semangka",
        status: "Selesai",
        kaloriPK: 475,
        kaloriPB: 630,
        costPK: 9500,
        costPB: 13500,
        komponen: [
            "Beras Putih",
            "Ikan Kembung",
            "Sayur Asem",
            "Tempe Kedelai",
            "Semangka Merah",
        ],
    },
    {
        tanggal: "2026-08-06",
        tglNo: 6,
        hari: "Kamis",
        siklusKe: 4,
        namaMenu:
            "Nasi Liwet Sunda, Ayam Bakar Madu, Tahu Bacem, Lalapan Sayur & Melon",
        status: "Selesai",
        kaloriPK: 490,
        kaloriPB: 650,
        costPK: 9900,
        costPB: 14000,
        komponen: [
            "Beras Liwet",
            "Ayam Bakar",
            "Tahu Bacem",
            "Lalap Ketimun",
            "Melon Hijau",
        ],
    },
    {
        tanggal: "2026-08-07",
        tglNo: 7,
        hari: "Jumat",
        siklusKe: 5,
        namaMenu:
            "Nasi Putih, Daging Rendang Padang, Sayur Nangka Gurih, Perkedel & Jeruk",
        status: "Selesai",
        kaloriPK: 510,
        kaloriPB: 680,
        costPK: 10400,
        costPB: 14800,
        komponen: [
            "Beras Putih",
            "Daging Sapi",
            "Sayur Nangka",
            "Perkedel Kentang",
            "Jeruk Pontianak",
        ],
    },
    {
        tanggal: "2026-08-10",
        tglNo: 10,
        hari: "Senin",
        siklusKe: 6,
        namaMenu:
            "Nasi Putih, Ayam Woku Belanga, Cah Kangkung Bakso, Tahu Isi & Pisang",
        status: "Selesai",
        kaloriPK: 470,
        kaloriPB: 620,
        costPK: 9400,
        costPB: 13300,
        komponen: [
            "Beras Putih",
            "Ayam Woku",
            "Cah Kangkung",
            "Tahu Isi Sayur",
            "Pisang Cavendish",
        ],
    },
    {
        tanggal: "2026-08-11",
        tglNo: 11,
        hari: "Selasa",
        siklusKe: 7,
        namaMenu:
            "Nasi Gurih, Telur Dadar Padang Tebal, Sambal Goreng Tempe & Pepaya",
        status: "Selesai",
        kaloriPK: 450,
        kaloriPB: 600,
        costPK: 8900,
        costPB: 12800,
        komponen: [
            "Beras Gurih",
            "Telur Bebek/Ayam",
            "Tempe Goreng",
            "Pepaya California",
        ],
    },
    {
        tanggal: "2026-08-12",
        tglNo: 12,
        hari: "Rabu",
        siklusKe: 8,
        namaMenu:
            "Nasi Putih, Soto Ayam Lamongan Komplit, Soun, Koya & Jeruk Manis",
        status: "Selesai",
        kaloriPK: 480,
        kaloriPB: 640,
        costPK: 9600,
        costPB: 13700,
        komponen: [
            "Beras Putih",
            "Daging Ayam",
            "Soun Kacang Hijau",
            "Telur Rebus",
            "Jeruk Manis",
        ],
    },
    {
        tanggal: "2026-08-13",
        tglNo: 13,
        hari: "Kamis",
        siklusKe: 9,
        namaMenu:
            "Nasi Kuning Manado, Cakalang Fufu Suwir, Sayur Ganemo, Perkedel Jagung",
        status: "Selesai",
        kaloriPK: 495,
        kaloriPB: 660,
        costPK: 9950,
        costPB: 14100,
        komponen: [
            "Beras Kuning",
            "Ikan Cakalang",
            "Sayur Melinjo/Ganemo",
            "Jagung Manis",
            "Semangka",
        ],
    },
    {
        tanggal: "2026-08-14",
        tglNo: 14,
        hari: "Jumat",
        siklusKe: 10,
        namaMenu:
            "Nasi Putih, Empal Gentong Daging, Sambal Goreng Tahu, Kerupuk & Apel",
        status: "Selesai",
        kaloriPK: 515,
        kaloriPB: 690,
        costPK: 10500,
        costPB: 14900,
        komponen: [
            "Beras Putih",
            "Daging Sapi",
            "Tahu Kuning",
            "Apel Malang",
        ],
    },
    {
        tanggal: "2026-08-25",
        tglNo: 25,
        hari: "Selasa",
        siklusKe: 1,
        namaMenu:
            "Ayam Goreng Lengkuas, Sayur Bayam Jagung Manis, Tempe Bacem & Pisang",
        status: "Aktif",
        kaloriPK: 465,
        kaloriPB: 685,
        costPK: 7850,
        costPB: 9900,
        komponen: [
            "Beras Putih",
            "Daging Ayam",
            "Bayam Segar",
            "Jagung Manis",
            "Tempe",
            "Pisang",
        ],
    },
    {
        tanggal: "2026-08-26",
        tglNo: 26,
        hari: "Rabu",
        siklusKe: 2,
        namaMenu:
            "Ikan Kembung Bakar Kecap, Tumis Buncis Wortel Tempe, Tahu & Jeruk",
        status: "Siap Produksi",
        kaloriPK: 470,
        kaloriPB: 690,
        costPK: 7920,
        costPB: 9850,
        komponen: [
            "Beras Putih",
            "Ikan Kembung",
            "Buncis",
            "Wortel",
            "Tahu",
            "Jeruk Manis",
        ],
    },
    {
        tanggal: "2026-08-27",
        tglNo: 27,
        hari: "Kamis",
        siklusKe: 3,
        namaMenu:
            "Semur Telur Ayam & Tahu Tempe, Sayur Sop Komplit & Pepaya Potong",
        status: "Draft",
        kaloriPK: 455,
        kaloriPB: 675,
        costPK: 7650,
        costPB: 9600,
        komponen: [
            "Beras Putih",
            "Telur Ayam",
            "Tahu",
            "Sayur Sop",
            "Pepaya",
        ],
    },
    {
        tanggal: "2026-08-28",
        tglNo: 28,
        hari: "Jumat",
        siklusKe: 4,
        namaMenu:
            "Daging Sapi Cincang Saus Tiram, Capcay Sayuran Segar, Tahu & Semangka",
        status: "Draft",
        kaloriPK: 480,
        kaloriPB: 710,
        costPK: 7980,
        costPB: 9980,
        komponen: [
            "Beras Putih",
            "Daging Sapi",
            "Capcay Sayur",
            "Tahu",
            "Semangka",
        ],
    },
]);

const defaultJadwalList = ref([
    {
        tanggal: "2026-08-25",
        tglNo: 25,
        hari: "Selasa",
        siklusKe: 1,
        namaMenu: "Ayam Goreng Lengkuas, Sayur Bayam Jagung Manis, Tempe Bacem & Pisang",
        status: "Terkonfirmasi",
        kaloriPK: 465,
        kaloriPB: 685,
        costPK: 7850,
        costPB: 9900,
        komponen: ["Beras Putih", "Daging Ayam", "Bayam Segar", "Jagung Manis", "Tempe", "Pisang"],
    },
    {
        tanggal: "2026-08-26",
        tglNo: 26,
        hari: "Rabu",
        siklusKe: 2,
        namaMenu: "Ikan Kembung Bakar Kecap, Tumis Buncis Wortel Tempe, Tahu & Jeruk",
        status: "Siap Produksi",
        kaloriPK: 470,
        kaloriPB: 690,
        costPK: 7920,
        costPB: 9850,
        komponen: ["Beras Putih", "Ikan Kembung", "Buncis", "Wortel", "Tahu", "Jeruk"],
    },
    {
        tanggal: "2026-08-27",
        tglNo: 27,
        hari: "Kamis",
        siklusKe: 3,
        namaMenu: "Semur Telur Ayam & Tahu Tempe, Sayur Sop Komplit, Kerupuk & Pepaya",
        status: "Draft",
        kaloriPK: 455,
        kaloriPB: 675,
        costPK: 7650,
        costPB: 9600,
        komponen: ["Beras Putih", "Telur Ayam", "Tahu", "Tempe", "Sayur Sop", "Pepaya"],
    },
    {
        tanggal: "2026-08-28",
        tglNo: 28,
        hari: "Jumat",
        siklusKe: 4,
        namaMenu: "Daging Sapi Cincang Saus Tiram, Capcay Sayuran Segar, Tahu & Semangka",
        status: "Draft",
        kaloriPK: 480,
        kaloriPB: 710,
        costPK: 7980,
        costPB: 9980,
        komponen: ["Beras Putih", "Daging Sapi", "Capcay Sayur", "Tahu", "Semangka"],
    },
]);

const jadwalMenuBulan = computed(() => {
    if (props.workOrdersList && props.workOrdersList.length > 0) {
        return props.workOrdersList.map((wo, idx) => {
            const tglStr = typeof wo.tanggal_distribusi === 'string' ? wo.tanggal_distribusi.substring(0, 10) : '';
            const tglD = new Date(tglStr);
            const dayNames = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            const hariName = !isNaN(tglD.getDay()) ? dayNames[tglD.getDay()] : "Hari";
            
            const komponenList = [];
            if (wo.items && wo.items.length > 0) {
                wo.items.forEach(it => komponenList.push(it.nama_po || it.nama));
            } else {
                [wo.komponen_energi, wo.komponen_protein, wo.komponen_lemak, wo.komponen_karbohidrat, wo.komponen_serat]
                    .filter(Boolean)
                    .forEach(k => komponenList.push(k));
            }

            return {
                id: wo.nomor_wo,
                db_id: wo.id,
                tanggal: tglStr,
                tglNo: !isNaN(tglD.getDate()) ? tglD.getDate() : (idx + 1),
                hari: hariName,
                siklusKe: wo.siklus_ke || (idx + 1),
                namaMenu: wo.nama_menu,
                status: wo.status || "Draft",
                kaloriPK: wo.akg_pk?.energi ? Math.round(wo.akg_pk.energi) : 485,
                kaloriPB: wo.akg_pb?.energi ? Math.round(wo.akg_pb.energi) : 640,
                costPK: wo.food_cost_pk || 7850,
                costPB: wo.food_cost_pb || 9850,
                komponen: komponenList.length > 0 ? komponenList : ["Menu MBG"],
                raw: wo,
            };
        });
    }
    return defaultJadwalList.value;
});

function handleOpenMenuFromKalender(item) {
    if (item && item.db_id) {
        router.visit('/gizi/rancang-menu?wo_id=' + item.db_id);
    } else {
        emit('openRancangMenu');
    }
}

</script>

<template>
            <div class="space-y-6">
                <!-- Header & Info Siklus -->
                <Card className="bg-white border-slate-200 shadow-xs">
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
                    >
                        <div
                            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                        >
                            <div>
                                <CardTitle
                                    class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                                >
                                    <CalendarDays
                                        class="h-5 w-5 text-primary"
                                    />
                                    <span
                                        >Kalender Siklus Menu Harian MBG
                                        SPPG</span
                                    >
                                </CardTitle>
                                <CardDescription
                                    class="text-xs sm:text-sm mt-0.5"
                                >
                                    Jadwal rotasi siklus 10 hari menu makan
                                    bergizi gratis, status verifikasi gizi, dan
                                    target porsi produksi harian.
                                </CardDescription>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="px-3 py-1 text-xs font-extrabold rounded-lg bg-blue-50 text-blue-700 border border-blue-200"
                                >
                                    Siklus: 10 Hari Bergantian
                                </span>
                                <span
                                    class="px-3 py-1 text-xs font-extrabold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    Total 1.908 Porsi / Hari
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-5 space-y-5">
                        <!-- Bulan & Navigasi -->
                        <div
                            class="flex items-center justify-between pb-3 border-b border-slate-100"
                        >
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900">
                                    {{ kalenderBulan }}
                                </h3>
                                <Badge
                                    variant="outline"
                                    class="bg-primary/5 text-primary border-primary/20 text-[11px] font-bold"
                                >
                                    Bulan Berjalan
                                </Badge>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Button
                                    type="button"
                                    className="h-8 px-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    className="h-8 px-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Grid Jadwal Kalender Menu (Card Grid per Tanggal) -->
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
                        >
                            <div
                                v-for="(item, idx) in jadwalMenuBulan"
                                :key="idx"
                                @click="selectedKalenderItem = item"
                                :class="[
                                    'p-4 rounded-xl border transition-all cursor-pointer flex flex-col justify-between gap-3 shadow-2xs hover:shadow-md',
                                    item.status === 'Aktif Hari Ini'
                                        ? 'bg-blue-50/70 border-primary shadow-sm ring-2 ring-primary/20'
                                        : 'bg-white border-slate-200 hover:border-primary/40',
                                ]"
                            >
                                <!-- Header Card Tanggal & Siklus -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="[
                                                'h-9 w-9 rounded-xl flex items-center justify-center font-black text-sm',
                                                item.status === 'Aktif Hari Ini'
                                                    ? 'bg-primary text-white shadow-xs'
                                                    : 'bg-slate-100 text-slate-800 font-bold',
                                            ]"
                                        >
                                            {{ item.tglNo }}
                                        </span>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-900 leading-tight"
                                            >
                                                {{ item.hari }},
                                                {{ item.tanggal }}
                                            </p>
                                            <p
                                                class="text-[10.5px] font-semibold text-primary"
                                            >
                                                Siklus Hari Ke-{{
                                                    item.siklusKe
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 text-[10px] font-black rounded-full border',
                                            item.status === 'Aktif Hari Ini'
                                                ? 'bg-blue-600 text-white border-blue-600 animate-pulse'
                                                : item.status ===
                                                    'Siap Produksi'
                                                  ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                  : item.status === 'Selesai'
                                                    ? 'bg-slate-100 text-slate-700 border-slate-200'
                                                    : 'bg-amber-50 text-amber-800 border-amber-200',
                                        ]"
                                    >
                                        {{ item.status }}
                                    </span>
                                </div>

                                <!-- Nama Menu -->
                                <div class="space-y-1">
                                    <p
                                        class="text-xs font-bold text-slate-900 line-clamp-2 leading-relaxed"
                                    >
                                        {{ item.namaMenu }}
                                    </p>
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        <span
                                            v-for="(
                                                k, kidx
                                            ) in item.komponen.slice(0, 4)"
                                            :key="kidx"
                                            class="px-1.5 py-0.5 text-[10px] rounded bg-slate-100 text-slate-600 font-medium"
                                        >
                                            {{ k }}
                                        </span>
                                        <span
                                            v-if="item.komponen.length > 4"
                                            class="px-1.5 py-0.5 text-[10px] rounded bg-slate-100 text-slate-500 font-semibold"
                                        >
                                            +{{ item.komponen.length - 4 }} lagi
                                        </span>
                                    </div>
                                </div>

                                <!-- Ringkasan AKG & Food Cost -->
                                <div
                                    class="pt-2.5 border-t border-slate-100/90 grid grid-cols-2 gap-2 text-[11px]"
                                >
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 block uppercase"
                                            >Porsi Kecil (PK)</span
                                        >
                                        <span class="font-black text-amber-900"
                                            >{{ item.kaloriPK }} kkal</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-500 block"
                                            >{{
                                                formatRupiah(item.costPK)
                                            }}</span
                                        >
                                    </div>
                                    <div class="bg-slate-50 p-2 rounded-lg">
                                        <span
                                            class="text-[10px] font-bold text-slate-400 block uppercase"
                                            >Porsi Besar (PB)</span
                                        >
                                        <span class="font-black text-indigo-900"
                                            >{{ item.kaloriPB }} kkal</span
                                        >
                                        <span
                                            class="text-[10px] text-slate-500 block"
                                            >{{
                                                formatRupiah(item.costPB)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

        <!-- Modal / Popup Preview Detail Menu Tanggal Terpilih -->
        <Modal
            :show="!!selectedKalenderItem"
            @close="selectedKalenderItem = null"
            maxWidth="lg"
        >
            <div
                v-if="selectedKalenderItem"
                class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-200"
            >
                <div
                    class="p-4 sm:p-5 border-b border-slate-100 flex flex-row items-center justify-between bg-slate-50/70"
                >
                    <div>
                        <span
                            class="text-xs font-extrabold text-primary uppercase tracking-wider"
                        >
                            Siklus Ke-{{
                                selectedKalenderItem.siklusKe
                            }}
                            • {{ selectedKalenderItem.hari }}
                        </span>
                        <h3
                            class="text-base font-bold text-slate-900 mt-0.5"
                        >
                            {{ selectedKalenderItem.tanggal }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="selectedKalenderItem = null"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-4 sm:p-5 space-y-4 text-xs">
                    <div>
                        <p
                            class="text-[11px] font-bold text-slate-400 uppercase"
                        >
                            Nama Paket Menu MBG
                        </p>
                        <p
                            class="text-sm font-bold text-slate-900 mt-0.5"
                        >
                            {{ selectedKalenderItem.namaMenu }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-[11px] font-bold text-slate-400 uppercase mb-1.5"
                        >
                            Komponen Bahan Baku
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="(
                                    komp, kidx
                                ) in selectedKalenderItem.komponen"
                                :key="kidx"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-800 border border-blue-100"
                            >
                                {{ komp }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100"
                    >
                        <div
                            class="p-3 bg-amber-50/70 border border-amber-100 rounded-xl space-y-1"
                        >
                            <p
                                class="text-[10.5px] font-bold text-amber-900 uppercase"
                            >
                                Porsi Kecil (PK)
                            </p>
                            <p
                                class="text-sm font-black text-amber-950"
                            >
                                {{ selectedKalenderItem.kaloriPK }} kkal
                            </p>
                            <p class="text-xs font-bold text-amber-800">
                                {{
                                    formatRupiah(
                                        selectedKalenderItem.costPK,
                                    )
                                }}
                                / porsi
                            </p>
                            <p class="text-[10px] text-amber-700">
                                Target: 880 Porsi
                            </p>
                        </div>
                        <div
                            class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl space-y-1"
                        >
                            <p
                                class="text-[10.5px] font-bold text-indigo-900 uppercase"
                            >
                                Porsi Besar (PB)
                            </p>
                            <p
                                class="text-sm font-black text-indigo-950"
                            >
                                {{ selectedKalenderItem.kaloriPB }} kkal
                            </p>
                            <p
                                class="text-xs font-bold text-indigo-800"
                            >
                                {{
                                    formatRupiah(
                                        selectedKalenderItem.costPB,
                                    )
                                }}
                                / porsi
                            </p>
                            <p class="text-[10px] text-indigo-700">
                                Target: 1.028 Porsi
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100"
                    >
                        <Button
                            type="button"
                            @click="
                                emit('openRancangMenu');
                                selectedKalenderItem = null;
                            "
                            className="bg-primary hover:bg-primary/90 text-white text-xs font-bold px-4 h-9 cursor-pointer shadow-xs rounded-xl flex items-center"
                        >
                            <UtensilsCrossed class="h-3.5 w-3.5 mr-1.5" />
                            Buka di Formulasi Menu
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
