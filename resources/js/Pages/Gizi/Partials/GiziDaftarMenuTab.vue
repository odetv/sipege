<script setup>
import { ref, computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import {
    FileSpreadsheet,
    Users,
    Activity,
    Coins,
    Search,
    Plus,
    CheckCircle2,
    Clock,
    FileText,
    Printer,
    Send,
} from "lucide-vue-next";

defineProps({
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

const searchDaftarMenu = ref("");
const statusFilterDaftarMenu = ref("semua");

const daftarMenuList = ref([
    {
        id: "WO-MBG-20260825",
        nama: "Ayam Goreng Lengkuas, Sayur Bayam Jagung Manis, Tempe Bacem & Pisang Cavendish",
        tanggal: "2026-08-25",
        siklus: "Hari ke-1",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 465,
        protein_pk: 16.2,
        energi_pb: 685,
        protein_pb: 24.5,
        cost_pk: 7850,
        cost_pb: 9900,
        status_akg: "memenuhi",
        status_wo: "Terkonfirmasi",
        po_no: "PO-20260825-001",
    },
    {
        id: "WO-MBG-20260826",
        nama: "Ikan Kembung Bakar Kecap, Tumis Buncis Wortel Tempe, Tahu Goreng & Jeruk Manis",
        tanggal: "2026-08-26",
        siklus: "Hari ke-2",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 470,
        protein_pk: 16.8,
        energi_pb: 690,
        protein_pb: 25.1,
        cost_pk: 7920,
        cost_pb: 9850,
        status_akg: "memenuhi",
        status_wo: "Siap Produksi",
        po_no: "PO-20260826-002",
    },
    {
        id: "WO-MBG-20260827",
        nama: "Semur Telur Ayam & Tahu Tempe, Sayur Sop Komplit, Kerupuk & Pepaya Potong",
        tanggal: "2026-08-27",
        siklus: "Hari ke-3",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 455,
        protein_pk: 15.5,
        energi_pb: 675,
        protein_pb: 23.8,
        cost_pk: 7650,
        cost_pb: 9600,
        status_akg: "memenuhi",
        status_wo: "Draft WO",
        po_no: "PO-20260827-003",
    },
    {
        id: "WO-MBG-20260828",
        nama: "Daging Sapi Cincang Saus Tiram, Capcay Sayuran Segar, Tahu Bakso & Semangka",
        tanggal: "2026-08-28",
        siklus: "Hari ke-4",
        porsi_pk: 733,
        porsi_pb: 1175,
        total_porsi: 1908,
        energi_pk: 480,
        protein_pk: 17.5,
        energi_pb: 710,
        protein_pb: 26.2,
        cost_pk: 7980,
        cost_pb: 9980,
        status_akg: "memenuhi",
        status_wo: "Draft WO",
        po_no: "PO-20260828-004",
    },
]);

const filteredDaftarMenu = computed(() => {
    return daftarMenuList.value.filter((m) => {
        const matchSearch =
            !searchDaftarMenu.value ||
            m.id.toLowerCase().includes(searchDaftarMenu.value.toLowerCase()) ||
            m.nama
                .toLowerCase()
                .includes(searchDaftarMenu.value.toLowerCase()) ||
            m.tanggal.includes(searchDaftarMenu.value);
        const matchStatus =
            statusFilterDaftarMenu.value === "semua" ||
            m.status_wo
                .toLowerCase()
                .includes(statusFilterDaftarMenu.value.toLowerCase());
        return matchSearch && matchStatus;
    });
});
</script>

<template>
            <div class="space-y-6">
                <!-- Metrics Ringkasan Menu -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <Card className="bg-white border-slate-200/80 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100"
                            >
                                <FileSpreadsheet class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Total Menu Terencana
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-extrabold text-blue-900 mt-0.5"
                                >
                                    {{ daftarMenuList.length }} Menu WO
                                </h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200/80 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100"
                            >
                                <Users class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Total Porsi Sasaran
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-0.5"
                                >
                                    {{
                                        daftarMenuList
                                            .reduce(
                                                (acc, m) => acc + m.total_porsi,
                                                0,
                                            )
                                            .toLocaleString("id-ID")
                                    }}
                                    Porsi
                                </h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200/80 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100"
                            >
                                <Coins class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Rata-rata PK (Pagu: Rp 8rb)
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-bold text-amber-900 mt-0.5"
                                >
                                    Rp 7.850
                                    <span
                                        class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded"
                                        >Hemat</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white border-slate-200/80 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div
                                class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100"
                            >
                                <Utensils class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                                >
                                    Rata-rata PB (Pagu: Rp 10rb)
                                </p>
                                <h3
                                    class="text-lg sm:text-xl font-bold text-indigo-900 mt-0.5"
                                >
                                    Rp 9.832
                                    <span
                                        class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded"
                                        >Hemat</span
                                    >
                                </h3>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Card Tabel Daftar Menu -->
                <Card
                    className="bg-white border-slate-200 shadow-xs overflow-hidden"
                >
                    <CardHeader
                        className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <CardTitle
                                className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                            >
                                <FileSpreadsheet class="h-5 w-5 text-primary" />
                                <span
                                    >Daftar Menu & Rekap Work Order (WO)
                                    SPPG</span
                                >
                            </CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Seluruh formulasi menu dan work order produksi
                                makanan bergizi yang telah disusun.
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="relative w-48 sm:w-60">
                                <Search
                                    class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                                />
                                <input
                                    v-model="searchDaftarMenu"
                                    type="text"
                                    placeholder="Cari No. WO / Menu..."
                                    class="w-full pl-9 pr-3 py-1.5 rounded-lg text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                            <select
                                v-model="statusFilterDaftarMenu"
                                class="px-3 py-1.5 rounded-lg text-xs border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 font-medium"
                            >
                                <option value="semua">Semua Status</option>
                                <option value="Terkonfirmasi">
                                    Terkonfirmasi
                                </option>
                                <option value="Siap Produksi">
                                    Siap Produksi
                                </option>
                                <option value="Draft">Draft WO</option>
                            </select>
                            <Link
                                :href="route('gizi.rancang-menu')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-white hover:bg-primary/90 transition-all shadow-xs flex items-center gap-1.5 cursor-pointer"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                <span>Rancang Menu Baru</span>
                            </Link>
                        </div>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table
                            class="w-full min-w-[900px] text-left text-xs border-collapse"
                        >
                            <thead
                                class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10.5px]"
                            >
                                <tr>
                                    <th class="p-3.5">No. Work Order</th>
                                    <th class="p-3.5">Tanggal Distribusi</th>
                                    <th class="p-3.5">Nama & Komposisi Menu</th>
                                    <th class="p-3.5 text-center">
                                        Porsi Sasaran
                                    </th>
                                    <th class="p-3.5 text-center">
                                        Evaluasi AKG
                                    </th>
                                    <th class="p-3.5 text-right">
                                        Food Cost PK / PB
                                    </th>
                                    <th class="p-3.5 text-center">Status</th>
                                    <th class="p-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 text-slate-800"
                            >
                                <tr
                                    v-for="menu in filteredDaftarMenu"
                                    :key="menu.id"
                                    class="hover:bg-slate-50/70 transition-colors"
                                >
                                    <td
                                        class="p-3.5 font-mono font-bold text-primary"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ menu.id }}</span>
                                        </div>
                                        <span
                                            class="inline-block mt-0.5 text-[10px] text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded font-semibold"
                                        >
                                            {{ menu.siklus }}
                                        </span>
                                    </td>
                                    <td
                                        class="p-3.5 font-medium text-slate-600 whitespace-nowrap"
                                    >
                                        {{ formatTanggalIndo(menu.tanggal) }}
                                    </td>
                                    <td class="p-3.5 max-w-sm">
                                        <p
                                            class="font-bold text-slate-900 leading-snug"
                                        >
                                            {{ menu.nama }}
                                        </p>
                                        <div
                                            class="flex items-center gap-2 mt-1 text-[10.5px] text-slate-500 font-medium"
                                        >
                                            <span
                                                >PK: {{ menu.energi_pk }} kkal /
                                                {{ menu.protein_pk }}g
                                                Prot</span
                                            >
                                            <span>•</span>
                                            <span
                                                >PB: {{ menu.energi_pb }} kkal /
                                                {{ menu.protein_pb }}g
                                                Prot</span
                                            >
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <span
                                            class="font-black text-slate-900 block text-xs"
                                        >
                                            {{
                                                menu.total_porsi.toLocaleString(
                                                    "id-ID",
                                                )
                                            }}
                                            PM
                                        </span>
                                        <span
                                            class="text-[10px] text-slate-500 block"
                                        >
                                            {{ menu.porsi_pk }} PK /
                                            {{ menu.porsi_pb }} PB
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge
                                            variant="outline"
                                            className="bg-emerald-50 text-emerald-700 border-emerald-300 font-extrabold text-[10.5px]"
                                        >
                                            ✓ MEMENUHI AKG
                                        </Badge>
                                    </td>
                                    <td
                                        class="p-3.5 text-right whitespace-nowrap"
                                    >
                                        <div
                                            class="text-[11px] font-bold text-amber-800"
                                        >
                                            PK: {{ formatRupiah(menu.cost_pk) }}
                                        </div>
                                        <div
                                            class="text-[11px] font-bold text-indigo-800"
                                        >
                                            PB: {{ formatRupiah(menu.cost_pb) }}
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <Badge
                                            variant="outline"
                                            :className="
                                                menu.status_wo ===
                                                'Terkonfirmasi'
                                                    ? 'bg-blue-50 text-blue-700 border-blue-300 font-bold text-[10.5px]'
                                                    : menu.status_wo ===
                                                        'Siap Produksi'
                                                      ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold text-[10.5px]'
                                                      : 'bg-amber-50 text-amber-700 border-amber-300 font-bold text-[10.5px]'
                                            "
                                        >
                                            {{ menu.status_wo }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="p-3.5 text-center whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-1.5"
                                        >
                                            <Link
                                                :href="
                                                    route('gizi.rancang-menu')
                                                "
                                                class="px-2 py-1 rounded text-[11px] font-bold text-primary hover:bg-primary/10 border border-primary/20 transition-colors"
                                                title="Lihat / Edit Formulasi Menu"
                                            >
                                                Edit Resep
                                            </Link>
                                            <Link
                                                :href="
                                                    route('keuangan.daftar-po')
                                                "
                                                class="px-2 py-1 rounded text-[11px] font-bold text-emerald-700 hover:bg-emerald-50 border border-emerald-200 transition-colors"
                                                title="Lihat Purchase Order Terkait"
                                            >
                                                Lihat PO
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredDaftarMenu.length === 0">
                                    <td
                                        colspan="8"
                                        class="p-8 text-center text-slate-400 font-semibold"
                                    >
                                        Tidak ada menu yang sesuai dengan filter
                                        pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

</template>
