<script setup>
import { ref, computed } from "vue";
import { router, useForm, Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import {
    exportPoExcel,
    exportPoWord,
    exportPoPdf,
} from "@/Services/exportDocHelper";
import {
    Receipt,
    CheckCircle2,
    Clock,
    XCircle,
    Eye,
    ShieldCheck,
    Coins,
    Pencil,
    Edit3,
    Check,
    Printer,
    FileSpreadsheet,
    FileText,
    FilePenLine,
    Calendar,
    AlertCircle,
    Building2,
    X,
    Package,
    Store,
    Truck,
    Tag,
    ExternalLink,
    Search,
    Filter,
    User,
    Phone,
    MapPin,
    Boxes,
    Briefcase,
    Sparkles,
    AlertTriangle,
    Plus,
} from "lucide-vue-next";

const props = defineProps({
    poList: {
        type: Array,
        default: () => [],
    },
    suppliers: {
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

function formatGrossWeight(kg) {
    if (kg === null || kg === undefined || kg === "" || isNaN(Number(kg)))
        return "0 kg";
    const num = Number(kg);
    if (num <= 0) return "0 kg";
    if (num < 0.001) {
        return `${parseFloat(num.toFixed(4))} kg`;
    } else if (num < 0.01) {
        return `${parseFloat(num.toFixed(3))} kg`;
    } else {
        return `${parseFloat(num.toFixed(2))} kg`;
    }
}

function formatDateTimeIndo(dt) {
    if (!dt) return "-";
    try {
        const d = new Date(dt);
        if (isNaN(d.getTime())) return dt;
        const dateStr = d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
        const hours = String(d.getHours()).padStart(2, "0");
        const minutes = String(d.getMinutes()).padStart(2, "0");
        return `${dateStr}, ${hours}:${minutes} WIB`;
    } catch {
        return dt;
    }
}

// ─── Filter & Search State ───────────────────────────────────────────────────
const searchQuery = ref("");
const filterJenisTransaksi = ref("all");
const filterSupplier = ref("all");

const filteredPoList = computed(() => {
    const list = Array.isArray(props.poList) ? props.poList : [];
    return list.filter((po) => {
        const q = searchQuery.value.toLowerCase().trim();
        const matchesQuery =
            !q ||
            (po.id && po.id.toLowerCase().includes(q)) ||
            (po.wo_id && po.wo_id.toLowerCase().includes(q)) ||
            (po.menu && po.menu.toLowerCase().includes(q)) ||
            (po.vendor && po.vendor.toLowerCase().includes(q)) ||
            (po.supplier?.nama_usaha &&
                po.supplier.nama_usaha.toLowerCase().includes(q)) ||
            (po.items &&
                po.items.some((it) =>
                    it.supplier?.nama_usaha?.toLowerCase().includes(q),
                ));

        const matchesJenisTrx =
            filterJenisTransaksi.value === "all" ||
            po.jenis_transaksi === filterJenisTransaksi.value ||
            (po.items &&
                po.items.some(
                    (it) => it.jenis_transaksi === filterJenisTransaksi.value,
                ));

        const matchesSupplier =
            filterSupplier.value === "all" ||
            String(po.supplier_id) === String(filterSupplier.value) ||
            (po.items &&
                po.items.some(
                    (it) =>
                        String(it.supplier_id) === String(filterSupplier.value),
                ));

        return matchesQuery && matchesJenisTrx && matchesSupplier;
    });
});

// ─── Edit PO & Penetapan Supplier Per Item Modal State ────────────────────────
const showEditPoModal = ref(false);
const editingPo = ref(null);

const formPoItems = useForm({
    supplier_id: null,
    jenis_transaksi: "",
    vendor: "",
    items: [],
});

// Bulk apply controls
const bulkSupplierId = ref("");
const bulkJenisTransaksi = ref("");

function applyBulkToAll() {
    if (bulkSupplierId.value !== "") {
        const supId = bulkSupplierId.value
            ? Number(bulkSupplierId.value)
            : null;
        formPoItems.items.forEach((it) => {
            it.supplier_id = supId;
        });
        formPoItems.supplier_id = supId;
    }
    if (bulkJenisTransaksi.value !== "") {
        formPoItems.items.forEach((it) => {
            it.jenis_transaksi = bulkJenisTransaksi.value;
        });
        formPoItems.jenis_transaksi = bulkJenisTransaksi.value;
    }
}

function openEditPoModal(po) {
    editingPo.value = JSON.parse(JSON.stringify(po));
    if (!editingPo.value.items) {
        editingPo.value.items = [];
    }

    formPoItems.clearErrors();
    formPoItems.supplier_id = po.supplier_id || null;
    formPoItems.jenis_transaksi = po.jenis_transaksi || "";
    formPoItems.vendor = po.vendor || "";

    // Salin items dengan mapping per baris (default kosong jika belum pernah diisi)
    formPoItems.items = (editingPo.value.items || []).map((it) => {
        const gross = Number(it.gross_kg) || 0;
        const stok = Number(it.stok_digunakan_kg || 0);
        const qtyBeli = it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null
            ? Number(it.qty_beli_po_kg)
            : Math.max(0, gross - stok);
        const harga = Number(it.harga_aktual || it.harga_master || 0);
        const subtotal = it.subtotal_aktual !== undefined && it.subtotal_aktual !== null
            ? Number(it.subtotal_aktual)
            : Math.round(qtyBeli * harga);

        return {
            id: it.id,
            nama: it.nama,
            nama_po: it.nama_po || it.nama,
            kategori: it.kategori,
            sub_menu_key: it.sub_menu_key,
            nama_sub_menu: it.nama_sub_menu,
            tipe_porsi: it.tipe_porsi,
            tipe: it.tipe,
            jenis_alergi: it.jenis_alergi,
            gross_kg: gross,
            stok_digunakan_kg: stok,
            qty_beli_po_kg: qtyBeli,
            sumber_pengadaan: it.sumber_pengadaan || (stok > 0 ? (stok >= gross ? '100% Dari Stok' : 'Parsial Stok') : 'Beli PO'),
            harga_aktual: harga,
            subtotal_aktual: subtotal,
            supplier_id: it.supplier_id || null,
            jenis_transaksi: it.jenis_transaksi || "",
        };
    });

    bulkSupplierId.value = "";
    bulkJenisTransaksi.value = "";

    showEditPoModal.value = true;
}

function closeEditPoModal() {
    showEditPoModal.value = false;
    editingPo.value = null;
    formPoItems.reset();
}

// Cek kelengkapan pengisian seluruh baris bahan
const isAllRowsFilled = computed(() => {
    if (!formPoItems.items || formPoItems.items.length === 0) return false;
    return formPoItems.items.every(
        (it) => it.supplier_id && it.jenis_transaksi,
    );
});

const unassignedCount = computed(() => {
    if (!formPoItems.items) return 0;
    return formPoItems.items.filter(
        (it) => !it.supplier_id || !it.jenis_transaksi,
    ).length;
});

function submitPoSupplierUpdate() {
    if (!editingPo.value) return;

    // Sinkronkan nama vendor header jika ada supplier terpilih
    const firstAssigned = formPoItems.items.find((i) => i.supplier_id);
    if (firstAssigned && firstAssigned.supplier_id) {
        const found = props.suppliers.find(
            (s) => s.id === Number(firstAssigned.supplier_id),
        );
        if (found) {
            formPoItems.vendor = found.nama_usaha;
            formPoItems.supplier_id = found.id;
        }
    }

    const targetId = editingPo.value.db_id || editingPo.value.id;
    formPoItems.put(route("keuangan.po.update-supplier", targetId), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditPoModal();
        },
    });
}

// Helper badge dan hitungan status penetapan supplier di tabel
function countAssignedItems(po) {
    if (!po.items || po.items.length === 0)
        return { assigned: 0, total: 0, allAssigned: false };
    const assigned = po.items.filter(
        (it) => it.supplier_id && it.jenis_transaksi,
    ).length;
    const total = po.items.length;
    return {
        assigned,
        total,
        allAssigned: assigned === total && total > 0,
    };
}

// Helper ambil list supplier unik yang dipakai pada PO
function getUniqueSuppliers(po) {
    const list = [];
    const seen = new Set();

    if (po.items && Array.isArray(po.items)) {
        po.items.forEach((it) => {
            if (it.supplier && it.supplier.nama_usaha) {
                if (!seen.has(it.supplier.id)) {
                    seen.add(it.supplier.id);
                    list.push(it.supplier);
                }
            } else if (it.supplier_id) {
                const found = props.suppliers.find(
                    (s) => s.id === Number(it.supplier_id),
                );
                if (found && !seen.has(found.id)) {
                    seen.add(found.id);
                    list.push(found);
                } else if (!found && !seen.has(it.supplier_id)) {
                    seen.add(it.supplier_id);
                    list.push({
                        id: it.supplier_id,
                        nama_usaha: `Supplier #${it.supplier_id}`,
                    });
                }
            }
        });
    }

    if (list.length === 0 && po.supplier && po.supplier.nama_usaha) {
        list.push(po.supplier);
    } else if (
        list.length === 0 &&
        po.vendor &&
        po.vendor !== "Rekanan Pangan SPPG"
    ) {
        list.push({ id: po.supplier_id || "v", nama_usaha: po.vendor });
    }

    return list;
}

// Helper ambil list jenis transaksi unik pada PO
function getUniqueJenisTransaksi(po) {
    const set = new Set();
    if (po.items && Array.isArray(po.items)) {
        po.items.forEach((it) => {
            if (it.jenis_transaksi) {
                set.add(it.jenis_transaksi);
            }
        });
    }
    if (set.size === 0 && po.jenis_transaksi) {
        set.add(po.jenis_transaksi);
    }
    return Array.from(set);
}

function getSubMenuLabelForBahan(it) {
    const keyMap = {
        sub_menu_1: "Sub Menu 1",
        sub_menu_2: "Sub Menu 2",
        sub_menu_3: "Sub Menu 3",
        sub_menu_4: "Sub Menu 4",
        sub_menu_5: "Sub Menu 5",
    };
    const key = it.sub_menu_key || "sub_menu_1";
    const label = keyMap[key] || "Sub Menu 1";

    let namaMenu = it.nama_sub_menu || "";
    if (!namaMenu && editingPo.value) {
        namaMenu =
            editingPo.value[key] ||
            editingPo.value.raw?.[key] ||
            editingPo.value.raw?.work_order?.[key] ||
            "";
    }

    const isAlergi = it.tipe_porsi === "alergi" || it.tipe === "Alergi";
    return {
        label: isAlergi ? `${label} • Alergi` : label,
        namaMenu: namaMenu && namaMenu !== "-" ? namaMenu : "",
        isAlergi,
        jenisAlergi: it.jenis_alergi || "",
        badgeClass: isAlergi
            ? "bg-rose-100 text-rose-800 border-rose-300"
            : "bg-slate-100 text-slate-800 border-slate-300",
    };
}

const jenisSupplierBadgeClasses = {
    KDMP: "bg-emerald-50 text-emerald-700 border-emerald-200",
    Koperasi: "bg-blue-50 text-blue-700 border-blue-200",
    Bumdes: "bg-indigo-50 text-indigo-700 border-indigo-200",
    Bumdesma: "bg-violet-50 text-violet-700 border-violet-200",
    UMKM: "bg-amber-50 text-amber-700 border-amber-200",
    Lainnya: "bg-slate-50 text-slate-700 border-slate-200",
};

const showExportPoModal = ref(false);

function handleExportToolbarExcel() {
    if (!filteredPoList.value || filteredPoList.value.length === 0) {
        alert("Tidak ada data Purchase Order (PO) yang tersedia untuk diunduh.");
        return;
    }
    if (filteredPoList.value.length === 1) {
        exportPoExcel(filteredPoList.value[0]);
    } else {
        showExportPoModal.value = true;
    }
}

async function exportAllPosExcel() {
    if (!filteredPoList.value || filteredPoList.value.length === 0) return;
    for (const po of filteredPoList.value) {
        await exportPoExcel(po);
        await new Promise((r) => setTimeout(r, 600));
    }
    showExportPoModal.value = false;
}
</script>

<template>
    <div class="space-y-6">
        <!-- ─── TOOLBAR PENCARIAN & FILTER ─────────────────────────────────── -->
        <div
            class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex flex-col lg:flex-row gap-3 items-center justify-between"
        >
            <div class="relative w-full lg:w-96">
                <Search
                    class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari No. PO, Menu, Supplier/Vendor..."
                    class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                <!-- Filter Jenis Transaksi -->
                <div class="flex items-center gap-1.5">
                    <span
                        class="text-xs font-semibold text-slate-500 hidden sm:inline"
                        >Transaksi:</span
                    >
                    <select
                        v-model="filterJenisTransaksi"
                        class="text-xs sm:text-sm font-medium rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-700"
                    >
                        <option value="all">Semua Transaksi</option>
                        <option value="Bahan Baku">Bahan Baku</option>
                        <option value="Operasional">Operasional</option>
                    </select>
                </div>

                <!-- Filter Supplier -->
                <div
                    v-if="suppliers.length > 0"
                    class="flex items-center gap-1.5"
                >
                    <span
                        class="text-xs font-semibold text-slate-500 hidden sm:inline"
                        >Supplier:</span
                    >
                    <select
                        v-model="filterSupplier"
                        class="text-xs sm:text-sm font-medium rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-700 max-w-[170px] truncate"
                    >
                        <option value="all">Semua Supplier</option>
                        <option
                            v-for="s in suppliers"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.nama_usaha }}
                        </option>
                    </select>
                </div>

                <!-- Link ke Master Supplier -->
                <Link
                    :href="route('supplier.index')"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer"
                    title="Buka Data Master Supplier"
                >
                    <Store class="w-3.5 h-3.5 text-primary" />
                    <span>Master Supplier</span>
                </Link>

                <!-- Tombol Download Laporan Excel PO -->
                <button
                    type="button"
                    @click="handleExportToolbarExcel"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                    title="Download Laporan PO dalam Format Excel (.xlsx)"
                >
                    <FileSpreadsheet class="w-3.5 h-3.5" />
                    <span>Download Excel PO</span>
                </button>
            </div>
        </div>

        <!-- ─── TABEL UTAMA DAFTAR PO RESMI ─────────────────────────────────── -->
        <div
            class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[1100px] text-left text-xs border-collapse"
                >
                    <thead>
                        <tr
                            class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                        >
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[170px]">
                                Kode PO & Tanggal
                            </th>
                            <th class="py-3.5 px-5 min-w-[240px]">Nama Menu</th>
                            <th class="py-3.5 px-4 min-w-[230px]">
                                Status Rekanan & Transaksi
                            </th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">
                                Total PM
                            </th>
                            <th class="py-3.5 px-4 text-right min-w-[140px]">
                                Total Belanja (PO)
                            </th>
                            <th class="py-3.5 px-4 text-center min-w-[120px]">
                                Status Bayar
                            </th>
                            <th class="py-3.5 px-4 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(po, index) in filteredPoList"
                            :key="po.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- 1. No Urut -->
                            <td
                                class="py-4 px-4 text-center font-bold text-slate-400"
                            >
                                {{ index + 1 }}
                            </td>

                            <!-- 2. Kode PO, WO & Tanggal Distribusi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <p
                                        class="text-xs font-semibold text-slate-700 flex items-center gap-1"
                                    >
                                        <Calendar
                                            class="h-3 w-3 text-slate-400 shrink-0"
                                        />
                                        <span>{{
                                            formatTanggalIndo(po.tanggal)
                                        }}</span>
                                    </p>
                                    <div>
                                        <span
                                            class="font-bold text-xs text-primary bg-primary/10 px-2 py-0.5 rounded inline-block"
                                        >
                                            {{ po.id }}
                                            <p
                                                class="text-[11px] text-slate-500 font-medium"
                                            >
                                                Ref: {{ po.wo_id }}
                                            </p>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Nama Menu & Kandungan Nutrisi -->
                            <td class="py-4 px-4 max-w-xs">
                                <div class="space-y-1">
                                    <p
                                        class="font-bold text-slate-900 leading-snug text-xs sm:text-sm"
                                    >
                                        {{ po.menu }}
                                    </p>
                                    <!-- <div
                                        v-if="po.energi_pk || po.energi_pb"
                                        class="flex items-center gap-1.5 flex-wrap text-[11px] font-medium text-slate-500"
                                    >
                                        <span
                                            class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px]"
                                        >
                                            PK: {{ po.energi_pk || 0 }} kkal
                                        </span>
                                        <span
                                            class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold text-[10px]"
                                        >
                                            PB: {{ po.energi_pb || 0 }} kkal
                                        </span>
                                    </div> -->
                                </div>
                            </td>

                            <!-- 4. Rekanan Supplier & Transaksi (Status per Baris) -->
                            <td class="py-4 px-4">
                                <div class="space-y-1.5">
                                    <!-- Status mapping item -->
                                    <div
                                        v-if="
                                            countAssignedItems(po).allAssigned
                                        "
                                        class="flex items-center gap-1.5 text-emerald-700 font-bold text-[11px]"
                                    >
                                        <CheckCircle2
                                            class="w-3.5 h-3.5 text-emerald-600 shrink-0"
                                        />
                                        <span
                                            >{{
                                                countAssignedItems(po).total
                                            }}
                                            Bahan Lengkap</span
                                        >
                                    </div>
                                    <div
                                        v-else-if="
                                            countAssignedItems(po).assigned > 0
                                        "
                                        class="flex items-center gap-1.5 text-amber-700 font-bold text-[11px]"
                                    >
                                        <AlertTriangle
                                            class="w-3.5 h-3.5 text-amber-600 shrink-0"
                                        />
                                        <span
                                            >{{
                                                countAssignedItems(po).assigned
                                            }}/{{
                                                countAssignedItems(po).total
                                            }}
                                            Bahan Lengkap</span
                                        >
                                    </div>
                                    <div
                                        v-else
                                        class="flex items-center gap-1.5 text-amber-600 font-bold text-[11px]"
                                    >
                                        <AlertTriangle
                                            class="w-3.5 h-3.5 text-amber-500 shrink-0"
                                        />
                                        <span
                                            >0/{{
                                                countAssignedItems(po).total
                                            }}
                                            Bahan Lengkap</span
                                        >
                                    </div>

                                    <!-- List Supplier yang Digunakan -->
                                    <div
                                        v-if="getUniqueSuppliers(po).length > 0"
                                        class="space-y-1"
                                    >
                                        <div
                                            v-for="sup in getUniqueSuppliers(
                                                po,
                                            )"
                                            :key="sup.id || sup.nama_usaha"
                                            class="text-[11.5px] text-slate-800 font-semibold flex items-center gap-1.5"
                                        >
                                            <Store
                                                class="w-3.5 h-3.5 text-primary shrink-0"
                                            />
                                            <span
                                                class="truncate max-w-[200px]"
                                                :title="sup.nama_usaha"
                                            >
                                                {{ sup.nama_usaha }}
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="text-xs font-bold text-slate-400"
                                    >
                                        -
                                    </div>

                                    <!-- List Jenis Transaksi jika ada -->
                                    <div
                                        v-if="
                                            getUniqueJenisTransaksi(po).length >
                                            0
                                        "
                                        class="flex items-center gap-1 flex-wrap"
                                    >
                                        <span
                                            v-for="jt in getUniqueJenisTransaksi(
                                                po,
                                            )"
                                            :key="jt"
                                            :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border',
                                                jt === 'Operasional'
                                                    ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            ]"
                                        >
                                            {{ jt }}
                                        </span>
                                    </div>
                                    <div
                                        v-else
                                        class="text-xs font-bold text-slate-400"
                                    >
                                        -
                                    </div>
                                </div>
                            </td>

                            <!-- 5. Sasaran Porsi -->
                            <td class="py-4 px-4 text-center">
                                <span
                                    class="font-black text-slate-900 block text-xs"
                                >
                                    {{
                                        Number(
                                            po.total_porsi || 0,
                                        ).toLocaleString("id-ID")
                                    }}
                                    PM
                                </span>
                                <span
                                    class="text-[10px] text-slate-500 block mt-0.5"
                                >
                                    {{ po.porsi_pk || 0 }} PK /
                                    {{ po.porsi_pb || 0 }} PB
                                </span>
                            </td>

                            <!-- 6. Total Belanja (PO) -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div
                                    class="font-black text-slate-900 text-xs sm:text-[13px]"
                                >
                                    {{ formatRupiah(po.total_nominal) }}
                                </div>
                                <div
                                    class="text-[10.5px] font-bold text-slate-500 mt-0.5"
                                >
                                    {{
                                        po.items_count || po.items?.length || 0
                                    }}
                                    Item Bahan
                                </div>
                            </td>

                            <!-- 7. Status Bayar -->
                            <td class="py-4 px-4 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block whitespace-nowrap',
                                        po.status_bayar === 'Lunas'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                            : 'bg-amber-50 text-amber-700 border-amber-300',
                                    ]"
                                >
                                    {{ po.status_bayar || "Belum Bayar" }}
                                </span>
                            </td>

                            <!-- 8. Aksi (Gaya tombol Verifikasi PO) -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <div
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <!-- 1. Tombol EDIT PO & Tentukan Supplier (Hijau Emerald) -->
                                    <button
                                        type="button"
                                        @click="openEditPoModal(po)"
                                        class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Edit & Tetapkan Supplier serta Jenis Transaksi"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>

                                    <!-- 2. Export Excel (Emerald) -->
                                    <button
                                        type="button"
                                        @click="exportPoExcel(po)"
                                        class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download Laporan Excel Nota PO (.xlsx)"
                                    >
                                        <FileSpreadsheet class="h-4 w-4" />
                                    </button>

                                    <!-- 3. Export Word (Indigo) -->
                                    <button
                                        type="button"
                                        @click="exportPoWord(po)"
                                        class="h-8 w-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download Dokumen Word (.doc)"
                                    >
                                        <FilePenLine class="h-4 w-4" />
                                    </button>

                                    <!-- 4. Export PDF (Rose) -->
                                    <button
                                        type="button"
                                        @click="exportPoPdf(po)"
                                        class="h-8 w-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Download / Cetak PDF"
                                    >
                                        <FileText class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredPoList.length === 0">
                            <td
                                colspan="8"
                                class="p-12 text-center text-slate-400 font-medium"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2.5"
                                >
                                    <Receipt class="h-10 w-10 text-slate-300" />
                                    <p
                                        class="text-sm font-semibold text-slate-600"
                                    >
                                        {{
                                            searchQuery ||
                                            filterJenisTransaksi !== "all" ||
                                            filterSupplier !== "all"
                                                ? "Tidak ada PO yang cocok dengan filter pencarian."
                                                : "Belum ada Purchase Order (PO) resmi yang disetujui."
                                        }}
                                    </p>
                                    <p class="text-xs text-slate-400 max-w-sm">
                                        Silakan buka sub-menu
                                        <strong>Verifikasi PO</strong> untuk
                                        menyetujui pengajuan belanja dari Tim
                                        Gizi dan menerbitkannya ke Daftar PO
                                        resmi ini.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ─── MODAL EDIT PO & PENETAPAN SUPPLIER PER BARIS ITEM ────────────── -->
        <Modal
            :show="showEditPoModal"
            @close="closeEditPoModal"
            maxWidth="landscape"
        >
            <div
                v-if="editingPo"
                class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-200 text-slate-800 flex flex-col max-h-[90vh]"
            >
                <!-- Modal Header -->
                <div
                    class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between shrink-0"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-xs"
                        >
                            <Store class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-black text-slate-900">
                                    Edit Penetapan Supplier & Jenis Transaksi PO
                                    ({{ editingPo.id }})
                                </h3>
                                <Badge
                                    variant="outline"
                                    className="bg-emerald-50 text-emerald-700 border-emerald-300 font-bold"
                                >
                                    {{ editingPo.status_po }}
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Menu:
                                <strong class="text-slate-800">{{
                                    editingPo.menu
                                }}</strong>
                                • Ref WO:
                                <strong class="text-slate-800">{{
                                    editingPo.wo_id
                                }}</strong>
                                • Tanggal:
                                <span class="font-medium text-slate-700">{{
                                    formatTanggalIndo(editingPo.tanggal)
                                }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeEditPoModal"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div
                    class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1 text-xs sm:text-sm"
                >
                    <!-- Warning / Info jika master supplier kosong -->
                    <div
                        v-if="suppliers.length === 0"
                        class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start justify-between gap-3"
                    >
                        <div class="flex items-start gap-2.5">
                            <AlertCircle
                                class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
                            />
                            <div>
                                <h4 class="font-bold text-xs sm:text-sm">
                                    Belum ada Supplier di Master Data
                                </h4>
                                <p class="text-xs mt-0.5">
                                    Tambahkan master supplier rekanan SPPG
                                    terlebih dahulu di menu
                                    <strong>Supplier</strong> agar dapat dipilih
                                    pada setiap baris bahan baku.
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('supplier.index')"
                            class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shrink-0 inline-flex items-center gap-1.5 shadow-xs transition-colors"
                        >
                            <Plus class="w-3.5 h-3.5" /> Buka Menu Supplier
                        </Link>
                    </div>

                    <!-- Card Toolbar: TERAPKAN CEPAT KE SELURUH BARIS (BULK APPLY) -->
                    <div
                        v-if="suppliers.length > 0"
                        class="p-4 rounded-xl bg-slate-50/90 border border-slate-200 space-y-3"
                    >
                        <div class="flex items-center gap-2">
                            <Sparkles class="w-4 h-4 text-primary" />
                            <span
                                class="font-bold text-xs uppercase tracking-wider text-slate-700"
                            >
                                Terapkan Cepat ke Semua Baris Bahan (Opsional)
                            </span>
                        </div>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center"
                        >
                            <!-- Bulk Supplier -->
                            <div class="sm:col-span-6 space-y-1">
                                <label
                                    class="text-[11px] font-bold text-slate-600 block"
                                    >Pilih Supplier Rekanan Massal:</label
                                >
                                <select
                                    v-model="bulkSupplierId"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                >
                                    <option value="">
                                        -- Pilih Supplier --
                                    </option>
                                    <option
                                        v-for="s in suppliers"
                                        :key="s.id"
                                        :value="String(s.id)"
                                    >
                                        {{ s.nama_usaha }} [{{
                                            s.jenis_supplier
                                        }}] -
                                        {{
                                            s.kabupaten || s.provinsi || "Lokal"
                                        }}
                                    </option>
                                </select>
                            </div>

                            <!-- Bulk Jenis Transaksi -->
                            <div class="sm:col-span-3 space-y-1">
                                <label
                                    class="text-[11px] font-bold text-slate-600 block"
                                    >Jenis Transaksi Massal:</label
                                >
                                <select
                                    v-model="bulkJenisTransaksi"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2 px-3 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                >
                                    <option value="">
                                        -- Pilih Jenis Transaksi --
                                    </option>
                                    <option value="Bahan Baku">
                                        Bahan Baku
                                    </option>
                                    <option value="Operasional">
                                        Operasional
                                    </option>
                                </select>
                            </div>

                            <!-- Tombol Terapkan ke Semua -->
                            <div class="sm:col-span-3 sm:pt-4">
                                <button
                                    type="button"
                                    @click="applyBulkToAll"
                                    class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                                >
                                    <Check class="w-3.5 h-3.5" />
                                    <span>Terapkan ke Semua</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TABEL EDIT PER BARIS BAHAN BAKU -->
                    <div class="space-y-2">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                        >
                            <h4
                                class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                            >
                                <Package class="h-4 w-4 text-primary" />
                                <span
                                    >Rincian Bahan Baku & Penetapan Supplier Per
                                    Baris ({{
                                        formPoItems.items.length
                                    }}
                                    Item)</span
                                >
                            </h4>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="unassignedCount > 0"
                                    class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg flex items-center gap-1.5 animate-pulse"
                                >
                                    <AlertTriangle
                                        class="w-3.5 h-3.5 text-amber-600"
                                    />
                                    {{ unassignedCount }} baris belum lengkap
                                    diisi
                                </span>
                                <span
                                    v-else
                                    class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg flex items-center gap-1.5"
                                >
                                    <CheckCircle2
                                        class="w-3.5 h-3.5 text-emerald-600"
                                    />
                                    Semua baris lengkap
                                </span>
                            </div>
                        </div>

                        <div
                            class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs"
                        >
                            <table
                                class="w-full text-xs text-left border-collapse min-w-[1080px]"
                            >
                                <thead
                                    class="bg-slate-50/90 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]"
                                >
                                    <tr>
                                        <th class="p-3 w-10 text-center">NO</th>
                                        <th class="p-3 min-w-[160px]">
                                            BAHAN PANGAN & NAMA PO
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[120px]"
                                        >
                                            PERUNTUKAN
                                        </th>
                                        <th class="p-3 text-right min-w-[85px] bg-amber-50/40">
                                            RESEP GROSS
                                        </th>
                                        <th class="p-3 text-right min-w-[85px] bg-blue-50/40">
                                            DARI STOK
                                        </th>
                                        <th class="p-3 text-right min-w-[100px] bg-emerald-50/40">
                                            QTY BELI PO
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[100px]"
                                        >
                                            SUBTOTAL
                                        </th>
                                        <th class="p-3 min-w-[160px]">
                                            JENIS TRANSAKSI (WAJIB)
                                        </th>
                                        <th class="p-3 min-w-[220px]">
                                            SUPPLIER REKANAN (WAJIB)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(item, idx) in formPoItems.items"
                                        :key="item.id || idx"
                                        :class="[
                                            'transition-colors',
                                            !item.supplier_id ||
                                            !item.jenis_transaksi
                                                ? 'bg-amber-50/30 hover:bg-amber-50/50'
                                                : 'hover:bg-slate-50/60',
                                        ]"
                                    >
                                        <!-- 1. No -->
                                        <td
                                            class="p-3 text-center font-bold text-slate-400"
                                        >
                                            {{ idx + 1 }}
                                        </td>

                                        <!-- 2. Nama Bahan -->
                                        <td class="p-3">
                                            <div
                                                class="font-bold text-slate-900 leading-tight"
                                            >
                                                {{ item.nama }}
                                            </div>
                                            <div
                                                class="text-[11px] text-primary font-bold mt-0.5"
                                            >
                                                PO:
                                                {{ item.nama_po || item.nama }}
                                            </div>
                                            <span
                                                v-if="item.jenis_alergi"
                                                class="inline-block text-[9.5px] bg-rose-50 text-rose-800 border border-rose-200 px-1.5 py-0.2 rounded font-semibold mt-0.5"
                                            >
                                                Alergi: {{ item.jenis_alergi }}
                                            </span>
                                        </td>

                                        <!-- 3. Peruntukan / Sub Menu -->
                                        <td class="p-3 text-center">
                                            <div
                                                class="inline-flex flex-col items-center justify-center gap-1"
                                            >
                                                <!-- Jika bahan berasal dari beberapa Sub Menu sekaligus (dikelompokkan) -->
                                                <div
                                                    v-if="item.sub_menus && item.sub_menus.length > 1"
                                                    class="flex flex-wrap items-center justify-center gap-1 max-w-[190px]"
                                                >
                                                    <span
                                                        v-for="(sm, smIdx) in item.sub_menus"
                                                        :key="smIdx"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-black border shadow-2xs"
                                                        :class="sm.badgeClass || (sm.is_alergi ? 'bg-rose-100 text-rose-800 border-rose-300' : 'bg-slate-100 text-slate-800 border-slate-300')"
                                                        :title="sm.nama ? `${sm.label}: ${sm.nama}` : sm.label"
                                                    >
                                                        {{ sm.label }}
                                                    </span>
                                                </div>
                                                <span
                                                    v-else
                                                    class="px-2 py-0.5 rounded text-[10px] font-bold border inline-block"
                                                    :class="
                                                        getSubMenuLabelForBahan(
                                                            item,
                                                        ).badgeClass
                                                    "
                                                >
                                                    {{
                                                        getSubMenuLabelForBahan(
                                                            item,
                                                        ).label
                                                    }}
                                                </span>
                                                <span
                                                    v-if="
                                                        (item.nama_sub_menu || getSubMenuLabelForBahan(item).namaMenu) &&
                                                        (item.nama_sub_menu || getSubMenuLabelForBahan(item).namaMenu) !== '-'
                                                    "
                                                    class="text-[11px] font-bold text-slate-800 mt-0.5 max-w-[170px] leading-tight block text-center"
                                                    :title="item.nama_sub_menu || getSubMenuLabelForBahan(item).namaMenu"
                                                >
                                                    {{
                                                        item.nama_sub_menu ||
                                                        getSubMenuLabelForBahan(
                                                            item,
                                                        ).namaMenu
                                                    }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- 4. Resep Gross (Kg) -->
                                        <td
                                            class="p-3 text-right font-bold text-slate-900 whitespace-nowrap bg-amber-50/20"
                                        >
                                            {{
                                                formatGrossWeight(item.gross_kg)
                                            }}
                                        </td>

                                        <!-- 5. Dari Stok (Kg) -->
                                        <td
                                            class="p-3 text-right font-semibold text-blue-900 whitespace-nowrap bg-blue-50/20"
                                        >
                                            {{
                                                formatGrossWeight(item.stok_digunakan_kg || 0)
                                            }}
                                        </td>

                                        <!-- 6. Qty Beli PO (Kg) -->
                                        <td
                                            class="p-3 text-right font-black text-emerald-950 whitespace-nowrap bg-emerald-50/20"
                                        >
                                            <div>
                                                {{
                                                    formatGrossWeight(
                                                        item.qty_beli_po_kg !== undefined && item.qty_beli_po_kg !== null
                                                            ? item.qty_beli_po_kg
                                                            : Math.max(0, (item.gross_kg || 0) - (item.stok_digunakan_kg || 0))
                                                    )
                                                }}
                                            </div>
                                            <div class="mt-0.5">
                                                <span
                                                    v-if="(item.qty_beli_po_kg === 0 || (item.stok_digunakan_kg >= item.gross_kg && item.gross_kg > 0))"
                                                    class="inline-block text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800"
                                                >
                                                    100% Stok
                                                </span>
                                                <span
                                                    v-else-if="item.stok_digunakan_kg > 0"
                                                    class="inline-block text-[9px] font-bold px-1.5 py-0.2 rounded bg-blue-100 text-blue-800"
                                                >
                                                    Parsial
                                                </span>
                                            </div>
                                        </td>

                                        <!-- 7. Subtotal -->
                                        <td
                                            class="p-3 text-right font-black whitespace-nowrap"
                                            :class="item.subtotal_aktual === 0 ? 'text-slate-400' : 'text-emerald-900'"
                                        >
                                            {{
                                                formatRupiah(
                                                    item.subtotal_aktual,
                                                )
                                            }}
                                        </td>

                                        <!-- 8. Pilihan Jenis Transaksi Per Baris (Default Kosong, Wajib Diisi) -->
                                        <td class="p-3">
                                            <select
                                                v-model="item.jenis_transaksi"
                                                :class="[
                                                    'w-full rounded-lg border py-1.5 px-2.5 text-xs font-semibold transition-colors focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                    !item.jenis_transaksi
                                                        ? 'border-amber-300 bg-amber-50/70 text-amber-900'
                                                        : 'border-slate-200 bg-slate-50/50 text-slate-800',
                                                ]"
                                            >
                                                <option value="">
                                                    -- Pilih Jenis Transaksi --
                                                </option>
                                                <option value="Bahan Baku">
                                                    Bahan Baku
                                                </option>
                                                <option value="Operasional">
                                                    Operasional
                                                </option>
                                            </select>
                                        </td>

                                        <!-- 7. Pilihan Supplier Rekanan Per Baris (Default Kosong, Wajib Diisi) -->
                                        <td class="p-3">
                                            <select
                                                v-model="item.supplier_id"
                                                :class="[
                                                    'w-full rounded-lg border py-1.5 px-2.5 text-xs font-semibold transition-colors focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary',
                                                    !item.supplier_id
                                                        ? 'border-amber-300 bg-amber-50/70 text-amber-900'
                                                        : 'border-slate-200 bg-slate-50/50 text-slate-800',
                                                ]"
                                            >
                                                <option :value="null">
                                                    -- Pilih Supplier Rekanan --
                                                </option>
                                                <option
                                                    v-for="s in suppliers"
                                                    :key="s.id"
                                                    :value="s.id"
                                                >
                                                    {{ s.nama_usaha }} [{{
                                                        s.jenis_supplier
                                                    }}]
                                                </option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/80 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0"
                >
                    <div
                        class="flex items-center gap-2 w-full sm:w-auto justify-start"
                    >
                        <button
                            type="button"
                            @click="exportPoExcel(editingPo)"
                            class="px-3 py-2 text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                            title="Download Laporan Excel Nota PO (.xlsx)"
                        >
                            <FileSpreadsheet class="h-4 w-4" />
                            <span>Export Excel (.xlsx)</span>
                        </button>
                        <button
                            type="button"
                            @click="exportPoWord(editingPo)"
                            class="px-3 py-2 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FilePenLine class="h-4 w-4" />
                            <span>Export Word</span>
                        </button>
                        <button
                            type="button"
                            @click="exportPoPdf(editingPo)"
                            class="px-3 py-2 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        >
                            <FileText class="h-4 w-4" />
                            <span>Cetak PDF</span>
                        </button>
                    </div>

                    <div
                        class="flex items-center gap-3 w-full sm:w-auto justify-end"
                    >
                        <div
                            v-if="!isAllRowsFilled"
                            class="text-[11px] font-bold text-amber-700 flex items-center gap-1"
                        >
                            <AlertTriangle class="w-3.5 h-3.5 text-amber-600" />
                            <span>Semua baris wajib diisi</span>
                        </div>

                        <button
                            type="button"
                            @click="closeEditPoModal"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer"
                        >
                            Batal
                        </button>

                        <button
                            type="button"
                            @click="submitPoSupplierUpdate"
                            :disabled="
                                formPoItems.processing || !isAllRowsFilled
                            "
                            :title="
                                !isAllRowsFilled
                                    ? 'Harap lengkapi Supplier Rekanan dan Jenis Transaksi pada semua baris terlebih dahulu'
                                    : ''
                            "
                            class="px-5 py-2.5 text-xs font-bold bg-primary hover:bg-primary/95 text-white rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-98"
                        >
                            <Check class="w-4 h-4" />
                            <span>{{
                                formPoItems.processing
                                    ? "Menyimpan..."
                                    : "Simpan Penetapan PO & Supplier"
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- ─── MODAL PILIH EXPORT EXCEL PO ─────────────────────────────────── -->
        <Modal
            :show="showExportPoModal"
            @close="showExportPoModal = false"
            max-width="2xl"
        >
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shadow-2xs">
                            <FileSpreadsheet class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-slate-800">
                                Download Laporan Excel Nota PO
                            </h3>
                            <p class="text-xs text-slate-500">
                                Pilih dokumen PO yang ingin diunduh dalam format multi-sheet Excel (.xlsx)
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showExportPoModal = false"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="max-h-[55vh] overflow-y-auto space-y-2.5 pr-1">
                    <div
                        v-for="po in filteredPoList"
                        :key="po.id"
                        class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/20 transition-all flex items-center justify-between gap-3"
                    >
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-xs text-primary bg-primary/10 px-2 py-0.5 rounded">
                                    {{ po.id }}
                                </span>
                                <span class="text-xs font-semibold text-slate-700 truncate">
                                    {{ po.menu }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-slate-500">
                                <span class="flex items-center gap-1">
                                    <Calendar class="w-3 h-3 text-slate-400" />
                                    {{ formatTanggalIndo(po.tanggal) }}
                                </span>
                                <span>•</span>
                                <span class="font-semibold text-emerald-700">
                                    {{ formatRupiah(po.total_nominal) }}
                                </span>
                                <span>•</span>
                                <span>{{ po.items_count || (po.items ? po.items.length : 0) }} Bahan</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="exportPoExcel(po); showExportPoModal = false;"
                            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition-colors cursor-pointer"
                        >
                            <FileSpreadsheet class="w-3.5 h-3.5" />
                            <span>Unduh Excel</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 mt-5 border-t border-slate-100">
                    <button
                        type="button"
                        @click="exportAllPosExcel"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-colors cursor-pointer"
                    >
                        <FileSpreadsheet class="w-3.5 h-3.5" />
                        <span>Unduh Semua PO ({{ filteredPoList.length }} File)</span>
                    </button>

                    <button
                        type="button"
                        @click="showExportPoModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
