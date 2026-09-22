<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import Button from "@/Components/ui/Button.vue";
import Modal from "@/Components/Modal.vue";
import GiziWorkOrderDetailModal from "./GiziWorkOrderDetailModal.vue";
import {
    exportWorkOrderExcel,
    exportWorkOrderWord,
    exportWorkOrderPdf,
    printWorkOrder,
} from "@/Services/exportDocHelper";
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
    FilePenLine,
    Printer,
    Send,
    Eye,
    Edit3,
    Trash2,
    AlertCircle,
    Utensils,
    School,
    ShieldAlert,
    X,
    Sparkles,
    Lock,
    XCircle,
    Calendar,
    Download,
    ChevronDown,
} from "lucide-vue-next";

const props = defineProps({
    workOrdersList: {
        type: Array,
        default: () => [],
    },
    formatRupiah: {
        type: Function,
        default: (num) => {
            const val = Number(num);
            if (!val || isNaN(val) || val <= 0) return "Rp 0";
            if (Number.isInteger(val)) {
                return "Rp " + val.toLocaleString("id-ID");
            }
            if (val < 1) {
                const decimals = val < 0.01 ? 3 : 2;
                return (
                    "Rp " +
                    val.toLocaleString("id-ID", {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: decimals,
                    })
                );
            }
            return (
                "Rp " +
                val.toLocaleString("id-ID", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                })
            );
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

// State Modal Detail
const showDetailModal = ref(false);
const selectedMenu = ref(null);

// State Modal Konfirmasi Hapus
const showDeleteConfirmModal = ref(false);
const menuToDelete = ref(null);
const isDeleting = ref(false);

// State dropdown download per baris
const openDownloadMenuId = ref(null);
const activeMenuForDownload = ref(null);
const downloadDropdownPos = ref({ top: 0, right: 0 });

function toggleDownloadMenu(menu, event) {
    if (openDownloadMenuId.value === menu.id) {
        closeDownloadMenu();
        return;
    }
    openDownloadMenuId.value = menu.id;
    activeMenuForDownload.value = menu;

    if (event?.currentTarget) {
        const rect = event.currentTarget.getBoundingClientRect();
        downloadDropdownPos.value = {
            top: rect.bottom + 6,
            right: Math.max(16, window.innerWidth - rect.right),
        };
    }
}

function closeDownloadMenu() {
    openDownloadMenuId.value = null;
    activeMenuForDownload.value = null;
}

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

// Helper ringkasan alergi per jenis dari kelompoks.detail_alergi
// Hanya tampilkan jenis alergi yang benar-benar ada di bahan/sub menu menu ini
function getAlergiRingkasan(menu) {
    // Kumpulkan jenis alergi yang benar-benar aktif/terdampak di menu ini
    const activeJenis = new Set();

    // 1. Dari items yang bertipe alergi
    (menu.items || []).forEach((it) => {
        if (it.tipe_porsi === "alergi" && it.jenis_alergi) {
            activeJenis.add(it.jenis_alergi.trim());
        }
    });

    // 2. Dari sub_menu_alergi jika ada
    const rawAlergi = menu.raw?.sub_menu_alergi || menu.sub_menu_alergi;
    if (rawAlergi) {
        if (Array.isArray(rawAlergi)) {
            rawAlergi.forEach((al) => {
                const ja = typeof al === "object" ? al?.jenis_alergi || al?.alergen : (typeof al === "string" ? al : null);
                if (ja) activeJenis.add(ja.trim());
            });
        } else if (typeof rawAlergi === "object") {
            Object.values(rawAlergi).forEach((list) => {
                if (Array.isArray(list)) {
                    list.forEach((al) => {
                        const ja = typeof al === "object" ? al?.jenis_alergi || al?.alergen : (typeof al === "string" ? al : null);
                        if (ja) activeJenis.add(ja.trim());
                    });
                }
            });
        }
    }

    // Jika menu ini TIDAK memiliki bahan / substitusi alergi terdampak, KOSONGKAN (jangan tampilkan apapun)
    if (activeJenis.size === 0) {
        return [];
    }

    const kelompoks = menu.kelompoks || [];
    const map = {};
    for (const kel of kelompoks) {
        if (!kel.is_menerima && kel.is_menerima !== undefined) continue;
        const detailAlergi = kel.detail_alergi || [];
        if (!Array.isArray(detailAlergi)) continue;
        for (const da of detailAlergi) {
            if (!da || !da.jenis_alergi) continue;
            const jenis = da.jenis_alergi.trim();
            // Hanya jenis alergi yang memang terdampak di menu ini
            if (!activeJenis.has(jenis)) continue;
            const jumlah = (Number(da.porsi_kecil) || 0) + (Number(da.porsi_besar) || 0);
            map[jenis] = (map[jenis] || 0) + jumlah;
        }
    }
    return Object.entries(map)
        .filter(([, jumlah]) => jumlah > 0)
        .map(([jenis, jumlah]) => ({ jenis, jumlah }));
}

// Helper hitung berat bersih total item
function getItemNetKg(it, menu) {
    if (it.total_net_kg !== undefined && Number(it.total_net_kg) > 0)
        return Number(it.total_net_kg);
    const pk =
        Number(
            it.gram_pk !== undefined ? it.gram_pk : it.gram_bersih_pk || 0,
        ) || 0;
    const pb =
        Number(
            it.gram_pb !== undefined ? it.gram_pb : it.gram_bersih_pb || 0,
        ) || 0;
    const targetPK =
        it.tipe_porsi === "alergi"
            ? Number(menu?.total_alergi_pk) || 1
            : Number(menu?.porsi_pk) || 0;
    const targetPB =
        it.tipe_porsi === "alergi"
            ? Number(menu?.total_alergi_pb) || 1
            : Number(menu?.porsi_pb) || 0;
    return (pk * targetPK + pb * targetPB) / 1000;
}

// Helper hitung kebutuhan kotor item jika data lama di db bernilai 0
function getItemGrossKg(it, menu) {
    const val =
        Number(
            it.total_gross_kg !== undefined ? it.total_gross_kg : it.gross_kg,
        ) || 0;
    if (val > 0) return val;
    const pk = Number(it.gram_pk) || 0;
    const pb = Number(it.gram_pb) || 0;
    const bdd = (Number(it.bdd) || 100) / 100;
    const buffer = 1 + (Number(it.buffer) || 0) / 100;
    const targetPK =
        it.tipe_porsi === "alergi"
            ? Number(menu?.total_alergi_pk) || 1
            : Number(menu?.porsi_pk) || 0;
    const targetPB =
        it.tipe_porsi === "alergi"
            ? Number(menu?.total_alergi_pb) || 1
            : Number(menu?.porsi_pb) || 0;
    const grossPK = ((pk / bdd) * buffer * targetPK) / 1000;
    const grossPB = ((pb / bdd) * buffer * targetPB) / 1000;
    return grossPK + grossPB;
}

function getItemSubtotal(it, menu) {
    const rawSubtotal =
        Number(
            it.subtotal_master !== undefined
                ? it.subtotal_master
                : it.subtotal_aktual,
        ) || 0;
    if (rawSubtotal > 0) return rawSubtotal;
    const gross = getItemGrossKg(it, menu);
    const harga = Number(it.harga_master || it.harga_aktual) || 0;
    const calc = Math.round(gross * harga);
    if (gross > 0 && harga > 0 && calc === 0) {
        return Math.ceil(gross * harga);
    }
    return calc;
}

function formatFullLogTimestamp(dt) {
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
        const seconds = String(d.getSeconds()).padStart(2, "0");
        return `${dateStr}, ${hours}:${minutes}:${seconds} WIB`;
    } catch {
        return dt;
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

const daftarMenuList = computed(() => {
    if (props.workOrdersList && props.workOrdersList.length > 0) {
        return props.workOrdersList.map((wo, i) => {
            const subMenus = [];
            [
                wo.sub_menu_1,
                wo.sub_menu_2,
                wo.sub_menu_3,
                wo.sub_menu_4,
                wo.sub_menu_5,
            ].forEach((sm) => {
                if (sm && typeof sm === "string" && sm.trim() !== "") {
                    subMenus.push(sm.trim());
                }
            });

            if (subMenus.length === 0 && wo.items && wo.items.length > 0) {
                const uniqueSubNames = [
                    ...new Set(
                        wo.items
                            .map((it) => it.nama_sub_menu)
                            .filter(Boolean),
                    ),
                ];
                if (uniqueSubNames.length > 0) {
                    subMenus.push(...uniqueSubNames);
                }
            }

            let alergiSubMenus = [];
            if (wo.sub_menu_alergi) {
                if (Array.isArray(wo.sub_menu_alergi)) {
                    alergiSubMenus = wo.sub_menu_alergi
                        .map((a) =>
                            typeof a === "string"
                                ? a
                                : a?.nama || a?.nama_menu || "",
                        )
                        .filter(Boolean);
                } else if (typeof wo.sub_menu_alergi === "object") {
                    alergiSubMenus = Object.values(wo.sub_menu_alergi)
                        .map((a) =>
                            typeof a === "string"
                                ? a
                                : a?.nama || a?.nama_menu || "",
                        )
                        .filter(Boolean);
                }
            }

            return {
                id: wo.nomor_wo,
                db_id: wo.id,
                uuid: wo.uuid || wo.id,
                nama: wo.nama_menu,
                sub_menus: subMenus,
                sub_menu_alergi: alergiSubMenus,
                tanggal:
                    typeof wo.tanggal_distribusi === "string"
                        ? wo.tanggal_distribusi
                        : wo.tanggal_distribusi
                          ? wo.tanggal_distribusi.substring(0, 10)
                          : "",
                siklus: "Hari ke-" + (wo.siklus_ke || i + 1),
                porsi_pk: wo.total_pk || 0,
                porsi_pb: wo.total_pb || 0,
                total_porsi: wo.total_pm || 0,
                total_kelompok:
                    wo.total_kelompok ||
                    (wo.kelompoks ? wo.kelompoks.length : 0),
                total_alergi: wo.total_alergi || 0,
                energi_pk: wo.akg_pk?.energi || 0,
                protein_pk: wo.akg_pk?.protein || 0,
                lemak_pk: wo.akg_pk?.lemak || 0,
                karbo_pk: wo.akg_pk?.karbohidrat || 0,
                serat_pk: wo.akg_pk?.serat || 0,
                energi_pb: wo.akg_pb?.energi || 0,
                protein_pb: wo.akg_pb?.protein || 0,
                lemak_pb: wo.akg_pb?.lemak || 0,
                karbo_pb: wo.akg_pb?.karbohidrat || 0,
                serat_pb: wo.akg_pb?.serat || 0,
                cost_pk: wo.food_cost_pk || 0,
                cost_pb: wo.food_cost_pb || 0,
                total_anggaran: wo.total_anggaran_master || 0,
                status_akg: "memenuhi",
                status_wo: wo.status || "Draft",
                catatan_keuangan: wo.catatan_keuangan || wo.catatan || "",
                created_at: wo.created_at,
                updated_at: wo.updated_at,
                diajukan_pada: wo.diajukan_pada,
                disetujui_pada: wo.disetujui_pada,
                ditolak_pada: wo.ditolak_pada,
                riwayat_verifikasi:
                    wo.riwayat_verifikasi ||
                    (wo.purchase_order
                        ? wo.purchase_order.riwayat_verifikasi
                        : []) ||
                    [],
                po_no: wo.purchase_order
                    ? wo.purchase_order.nomor_po
                    : "PO-" + wo.nomor_wo.replace("WO-MBG-", ""),
                items: wo.items || [],
                kelompoks: wo.kelompoks || [],
                raw: wo,
            };
        });
    }
    return [];
});

const filteredDaftarMenu = computed(() => {
    return daftarMenuList.value.filter((m) => {
        const query = (searchDaftarMenu.value || "").toLowerCase();
        const matchSearch =
            !query ||
            m.id.toLowerCase().includes(query) ||
            m.nama.toLowerCase().includes(query) ||
            m.tanggal.includes(query) ||
            m.sub_menus.some((sm) => sm.toLowerCase().includes(query));
        const matchStatus =
            statusFilterDaftarMenu.value === "semua" ||
            m.status_wo
                .toLowerCase()
                .includes(statusFilterDaftarMenu.value.toLowerCase());
        return matchSearch && matchStatus;
    });
});

function handleNewRancangMenu() {
    router.visit("/gizi/rancang-menu");
    emit("openRancangMenu");
}

function openDetailModal(m) {
    selectedMenu.value = m;
    showDetailModal.value = true;
}

function handleEditWo(m) {
    if (m.status_wo === "Diajukan ke Keuangan") {
        alert(
            "Rancangan menu ini sedang dalam proses verifikasi Keuangan dan tidak dapat diedit saat ini.",
        );
        return;
    }
    if (m.status_wo === "Siap Produksi" || m.status_wo === "Terverifikasi") {
        alert(
            "Rancangan menu ini telah disetujui/terverifikasi oleh Keuangan dan tidak dapat diedit lagi.",
        );
        return;
    }
    if (m.uuid || m.db_id) {
        router.visit("/gizi/rancang-menu?wo_id=" + (m.uuid || m.db_id));
    } else {
        emit("openRancangMenu");
    }
}

function confirmDeleteWo(m) {
    const statusLower = (m.status_wo || "").toLowerCase();
    const canDelete =
        statusLower === "draft" || statusLower.includes("ditolak");
    if (!canDelete) {
        alert(
            `Menu berstatus "${m.status_wo}" tidak dapat dihapus. Hanya menu yang belum siap produksi / belum di-ACC (Draft atau Ditolak) yang dapat dihapus.`,
        );
        return;
    }
    menuToDelete.value = m;
    showDeleteConfirmModal.value = true;
}

function executeDeleteWo() {
    if (!menuToDelete.value) return;
    const woTarget = menuToDelete.value.uuid || menuToDelete.value.db_id;
    if (woTarget) {
        isDeleting.value = true;
        router.delete("/gizi/work-order/" + woTarget, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleting.value = false;
                showDeleteConfirmModal.value = false;
                menuToDelete.value = null;
            },
            onError: () => {
                isDeleting.value = false;
            },
        });
    }
}
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
                            Total Menu
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-extrabold text-blue-900 mt-0.5"
                        >
                            {{ daftarMenuList.length }} Menu
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
                            Total Porsi
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-0.5"
                        >
                            {{
                                daftarMenuList
                                    .reduce(
                                        (acc, m) =>
                                            acc + (Number(m.total_porsi) || 0),
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
                            Pagu Porsi Kecil
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-bold text-amber-900 mt-0.5"
                        >
                            Rp 8.000
                            <span
                                class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded"
                                >Standar BGN</span
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
                            Pagu Porsi Besar
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-bold text-indigo-900 mt-0.5"
                        >
                            Rp 10.000
                            <span
                                class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1 py-0.5 rounded"
                                >Standar BGN</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Toolbar Kontrol & Tombol Aksi Rancang Menu -->
        <div
            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/90 shadow-2xs"
        >
            <div class="flex flex-1 items-center gap-2.5 flex-wrap">
                <!-- Search Box -->
                <div class="relative flex-1 min-w-[220px]">
                    <Search
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                    />
                    <input
                        v-model="searchDaftarMenu"
                        type="text"
                        placeholder="Cari menu, sub menu, kode WO, atau tanggal..."
                        class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/60 hover:bg-white transition-all text-slate-800 placeholder-slate-400 font-medium"
                    />
                    <button
                        v-if="searchDaftarMenu"
                        @click="searchDaftarMenu = ''"
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded-full cursor-pointer"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-auto">
                    <select
                        v-model="statusFilterDaftarMenu"
                        class="w-full sm:w-auto px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-700 transition-all cursor-pointer"
                    >
                        <option value="semua">Semua Status WO</option>
                        <option value="Draft">Draft</option>
                        <option value="Diajukan ke Keuangan">
                            Diajukan ke Keuangan
                        </option>
                        <option value="Siap Produksi">
                            Siap Produksi / Terverifikasi
                        </option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
            </div>

            <!-- Tombol Rancang Menu -->
            <div class="flex items-center gap-2 shrink-0">
                <Button
                    type="button"
                    @click="handleNewRancangMenu"
                    className="w-full sm:w-auto bg-primary hover:bg-primary/90 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                    <Plus class="h-4 w-4" />
                    <span>Rancang Menu</span>
                </Button>
            </div>
        </div>

        <!-- Card Tabel Utama Daftar Menu -->
        <div
            class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[1050px] text-left text-xs border-collapse"
                >
                    <thead>
                        <tr
                            class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                        >
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[180px]">
                                Kode WO & Tanggal Distribusi
                            </th>
                            <th class="py-3.5 px-5 min-w-[280px]">
                                Nama Menu & Sub Menu
                            </th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">
                                Total PM
                            </th>
                            <th class="py-3.5 px-4 min-w-[170px]">
                                Riwayat Waktu
                            </th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">
                                Status WO
                            </th>
                            <th class="py-3.5 px-4 text-center min-w-[210px]">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(menu, index) in filteredDaftarMenu"
                            :key="menu.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- 1. No Urut -->
                            <td
                                class="py-4 px-4 text-center font-bold text-slate-400"
                            >
                                {{ index + 1 }}
                            </td>

                            <!-- 2. Kode WO & Tanggal Distribusi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1.5">
                                    <div>
                                        <span
                                            class="font-bold text-xs text-primary bg-primary/10 px-2.5 py-0.5 rounded inline-block"
                                        >
                                            {{ menu.id }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-xs font-semibold text-slate-700 flex items-center gap-1.5"
                                    >
                                        <Calendar
                                            class="h-3.5 w-3.5 text-slate-400 shrink-0"
                                        />
                                        <span>{{
                                            formatTanggalIndo(menu.tanggal)
                                        }}</span>
                                    </p>
                                </div>
                            </td>

                            <!-- 3. Nama Menu & Sub Menu -->
                            <td class="py-4 px-5 max-w-md">
                                <div class="space-y-1.5">
                                    <h4
                                        class="font-bold text-slate-900 leading-snug text-xs sm:text-sm"
                                    >
                                        {{ menu.nama }}
                                    </h4>

                                    <!-- Daftar Sub Menu Chips -->
                                    <div
                                        v-if="
                                            menu.sub_menus &&
                                            menu.sub_menus.length > 0
                                        "
                                        class="flex items-center gap-1.5 flex-wrap pt-0.5"
                                    >
                                        <span
                                            v-for="(sm, smIdx) in menu.sub_menus"
                                            :key="smIdx"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-semibold bg-slate-100/90 text-slate-700 border border-slate-200/80"
                                        >
                                            {{ sm }}
                                        </span>
                                    </div>

                                    <!-- Sub Menu Alergi jika ada -->
                                    <div
                                        v-if="
                                            menu.sub_menu_alergi &&
                                            menu.sub_menu_alergi.length > 0
                                        "
                                        class="flex items-center gap-1.5 flex-wrap pt-0.5"
                                    >
                                        <span
                                            v-for="(
                                                sma, smaIdx
                                            ) in menu.sub_menu_alergi"
                                            :key="'al-' + smaIdx"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                                        >
                                            Pengganti Alergi: {{ sma }}
                                        </span>
                                    </div>

                                    <!-- Nutrisi Ringkas Tambahan (Fallback jika sub menu belum diisi) -->
                                    <div
                                        v-if="
                                            (!menu.sub_menus ||
                                                menu.sub_menus.length === 0) &&
                                            (menu.energi_pk || menu.energi_pb)
                                        "
                                        class="flex items-center gap-1.5 flex-wrap text-[10.5px] font-medium text-slate-500"
                                    >
                                        <span
                                            class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[10px]"
                                        >
                                            PK: {{ menu.energi_pk }} kkal
                                        </span>
                                        <span
                                            class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold text-[10px]"
                                        >
                                            PB: {{ menu.energi_pb }} kkal
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 4. Total PM -->
                            <td class="py-4 px-4 text-center">
                                <div
                                    class="font-black text-slate-900 text-xs sm:text-sm"
                                >
                                    {{
                                        Number(
                                            menu.total_porsi || 0,
                                        ).toLocaleString("id-ID")
                                    }}
                                    <span
                                        class="text-[11px] font-bold text-slate-500"
                                        >PM</span
                                    >
                                </div>
                                <div
                                    class="text-[10.5px] text-slate-500 font-medium mt-0.5"
                                >
                                    {{ menu.porsi_pk }} PK /
                                    {{ menu.porsi_pb }} PB
                                </div>
                                <!-- Ringkasan Alergi per Jenis (Hanya yang terdampak di menu ini) -->
                                <div
                                    v-if="getAlergiRingkasan(menu).length > 0"
                                    class="mt-1 flex flex-wrap gap-1 justify-center"
                                >
                                    <span
                                        v-for="(al, alIdx) in getAlergiRingkasan(menu)"
                                        :key="alIdx"
                                        class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        ⚠️ {{ al.jenis }}: {{ al.jumlah }} PM
                                    </span>
                                </div>
                            </td>

                            <!-- 5. Riwayat Waktu -->
                            <td
                                class="py-4 px-4 whitespace-nowrap text-[10.5px]"
                            >
                                <div class="text-slate-500">
                                    <span class="text-slate-400">Dibuat:</span>
                                    {{ formatDateTimeIndo(menu.created_at) }}
                                </div>
                                <div class="text-slate-500 mt-0.5">
                                    <span class="text-slate-400"
                                        >Diperbarui:</span
                                    >
                                    {{ formatDateTimeIndo(menu.updated_at) }}
                                </div>
                                <div
                                    v-if="
                                        menu.diajukan_pada &&
                                        menu.status_wo ===
                                            'Diajukan ke Keuangan'
                                    "
                                    class="text-blue-700 font-semibold mt-0.5"
                                >
                                    Diajukan:
                                    {{ formatDateTimeIndo(menu.diajukan_pada) }}
                                </div>
                                <div
                                    v-if="
                                        menu.disetujui_pada &&
                                        (menu.status_wo === 'Siap Produksi' ||
                                            menu.status_wo === 'Terverifikasi')
                                    "
                                    class="text-emerald-700 font-semibold mt-0.5"
                                >
                                    Disetujui:
                                    {{
                                        formatDateTimeIndo(menu.disetujui_pada)
                                    }}
                                </div>
                                <div
                                    v-if="
                                        menu.ditolak_pada &&
                                        menu.status_wo === 'Ditolak'
                                    "
                                    class="text-rose-700 font-semibold mt-0.5"
                                >
                                    Ditolak:
                                    {{ formatDateTimeIndo(menu.ditolak_pada) }}
                                </div>
                            </td>

                            <!-- 6. Status WO -->
                            <td class="py-4 px-4 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block whitespace-nowrap',
                                        menu.status_wo === 'Siap Produksi' ||
                                        menu.status_wo === 'Terverifikasi'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                            : menu.status_wo ===
                                                'Diajukan ke Keuangan'
                                              ? 'bg-blue-50 text-blue-700 border-blue-300'
                                              : menu.status_wo === 'Ditolak'
                                                ? 'bg-rose-50 text-rose-700 border-rose-300'
                                                : 'bg-amber-50 text-amber-700 border-amber-300',
                                    ]"
                                >
                                    {{ menu.status_wo }}
                                </span>
                            </td>

                            <!-- 7. Aksi -->
                            <td class="py-4 px-4 text-center whitespace-nowrap min-w-[190px]">
                                <div
                                    class="flex items-center justify-center gap-1.5 flex-nowrap whitespace-nowrap"
                                >
                                    <!-- 1. Tombol Lihat Detail -->
                                    <button
                                        type="button"
                                        @click="openDetailModal(menu)"
                                        class="h-8 w-8 shrink-0 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Lihat Detail Resep, Sasaran PM & Waktu"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>

                                    <!-- 2. Tombol Edit (Bisa jika Draft atau Ditolak Keuangan) -->
                                    <button
                                        v-if="
                                            menu.status_wo === 'Draft' ||
                                            menu.status_wo
                                                .toLowerCase()
                                                .includes('ditolak')
                                        "
                                        type="button"
                                        @click="handleEditWo(menu)"
                                        class="h-8 w-8 shrink-0 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        :title="
                                            menu.status_wo
                                                .toLowerCase()
                                                .includes('ditolak')
                                                ? 'Perbaiki & Edit Menu yang Ditolak'
                                                : 'Buka & Edit Formulasi Rancang Menu'
                                        "
                                    >
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <span
                                        v-else
                                        class="h-8 w-8 shrink-0 rounded-lg text-[10.5px] font-bold text-slate-400 bg-slate-100 border border-slate-200 flex items-center justify-center cursor-not-allowed shadow-2xs"
                                        :title="
                                            menu.status_wo ===
                                            'Diajukan ke Keuangan'
                                                ? 'Sedang diverifikasi Keuangan (Terkunci)'
                                                : 'Telah disetujui Keuangan (Terkunci)'
                                        "
                                    >
                                        <Lock
                                            class="h-3.5 w-3.5 text-slate-400"
                                        />
                                    </span>

                                    <!-- 3. Tombol Hapus (Bisa jika Draft atau Ditolak Keuangan) -->
                                    <button
                                        v-if="
                                            menu.status_wo === 'Draft' ||
                                            menu.status_wo
                                                .toLowerCase()
                                                .includes('ditolak')
                                        "
                                        type="button"
                                        @click="confirmDeleteWo(menu)"
                                        class="h-8 w-8 shrink-0 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Hapus Rancangan Menu Ini"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>

                                    <!-- Divider Pemisah -->
                                    <span
                                        class="h-4 w-px bg-slate-200 mx-0.5 shrink-0"
                                    ></span>

                                    <!-- Tombol Download Dropdown -->
                                    <div class="relative shrink-0">
                                        <button
                                            type="button"
                                            @click.stop="toggleDownloadMenu(menu, $event)"
                                            class="h-8 px-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80 flex items-center gap-1 shadow-2xs transition-colors cursor-pointer shrink-0"
                                            :class="{ 'bg-slate-200 text-slate-900 border-slate-400': openDownloadMenuId === menu.id }"
                                            title="Unduh Dokumen (Excel, Word, PDF)"
                                        >
                                            <Download class="h-3.5 w-3.5" />
                                            <ChevronDown class="h-3 w-3 opacity-60" />
                                        </button>
                                    </div>

                                    <!-- Tombol Cetak Langsung (Icon Printer) -->
                                    <button
                                        type="button"
                                        @click="printWorkOrder(menu)"
                                        class="h-8 w-8 shrink-0 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Cetak Menu Langsung (Print Preview)"
                                    >
                                        <Printer class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredDaftarMenu.length === 0">
                            <td
                                colspan="7"
                                class="p-10 text-center text-slate-400 font-semibold"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2"
                                >
                                    <FileSpreadsheet
                                        class="h-8 w-8 text-slate-300"
                                    />
                                    <p>
                                        Tidak ada menu yang sesuai dengan filter
                                        pencarian.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL PREVIEW DETAIL LENGKAP WORK ORDER (PARTIAL STEP REVIEW RANCANG MENU) -->
        <GiziWorkOrderDetailModal
            :show="showDetailModal"
            :work-order="selectedMenu"
            :format-rupiah="formatRupiah"
            :format-tanggal-indo="formatTanggalIndo"
            @close="showDetailModal = false"
        />

        <!-- MODAL KONFIRMASI HAPUS WORK ORDER -->
        <Modal
            :show="showDeleteConfirmModal"
            max-width="md"
            @close="showDeleteConfirmModal = false"
        >
            <div v-if="menuToDelete" class="p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div
                        class="h-12 w-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0"
                    >
                        <Trash2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-slate-900">
                            Hapus Menu & Work Order?
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>
                </div>

                <div
                    class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1"
                >
                    <p class="font-bold text-slate-800">
                        {{ menuToDelete.nama }}
                    </p>
                    <p class="text-slate-500 text-[11px]">
                        {{ menuToDelete.id }} •
                        {{ formatTanggalIndo(menuToDelete.tanggal) }}
                    </p>
                    <span
                        class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 mt-1"
                    >
                        Status: {{ menuToDelete.status_wo }}
                    </span>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showDeleteConfirmModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeDeleteWo"
                        :disabled="isDeleting"
                        class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>{{
                            isDeleting ? "Menghapus..." : "Ya, Hapus"
                        }}</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Floating Dropdown Download (Teleport to body agar tidak terpotong overflow tabel) -->
        <Teleport to="body">
            <template v-if="openDownloadMenuId && activeMenuForDownload">
                <!-- Overlay transparan untuk tutup dropdown -->
                <div
                    class="fixed inset-0 z-[9998]"
                    @click="closeDownloadMenu()"
                ></div>
                <div
                    class="fixed z-[9999] bg-white border border-slate-200 rounded-xl shadow-2xl w-44 overflow-hidden text-left"
                    :style="{
                        top: `${downloadDropdownPos.top}px`,
                        right: `${downloadDropdownPos.right}px`,
                    }"
                >
                    <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-100 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        Unduh Format:
                    </div>
                    <button
                        type="button"
                        @click="exportWorkOrderExcel(activeMenuForDownload); closeDownloadMenu()"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-[11.5px] font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors cursor-pointer text-left"
                    >
                        <FileSpreadsheet class="h-3.5 w-3.5 shrink-0" />
                        Excel (.xlsx)
                    </button>
                    <button
                        type="button"
                        @click="exportWorkOrderWord(activeMenuForDownload); closeDownloadMenu()"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-[11.5px] font-semibold text-indigo-700 hover:bg-indigo-50 transition-colors cursor-pointer text-left"
                    >
                        <FilePenLine class="h-3.5 w-3.5 shrink-0" />
                        Word (.docx)
                    </button>
                    <button
                        type="button"
                        @click="exportWorkOrderPdf(activeMenuForDownload); closeDownloadMenu()"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-[11.5px] font-semibold text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer text-left"
                    >
                        <FileText class="h-3.5 w-3.5 shrink-0" />
                        PDF (.pdf)
                    </button>
                </div>
            </template>
        </Teleport>
    </div>
</template>
