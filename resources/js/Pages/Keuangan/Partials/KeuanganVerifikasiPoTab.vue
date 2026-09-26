<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
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
    formatGrossQty,
    getGroupedUnitList,
    formatGroupedUnitSummary,
    SATUAN_LIST,
} from "@/Services/satuanConfig";
import {
    ShieldCheck,
    Receipt,
    CheckCircle2,
    Clock,
    XCircle,
    Eye,
    Coins,
    Edit3,
    Check,
    Printer,
    FileSpreadsheet,
    Calendar,
    AlertCircle,
    Building2,
    X,
    FileText,
    ArrowRight,
    Send,
    Package,
    Search,
    Users,
    ChevronLeft,
    ChevronRight,
    ArrowRightLeft,
} from "lucide-vue-next";

const props = defineProps({
    verifikasiPoList: {
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

function getPortionUnit(satuan = "Kg") {
    const s = (satuan || "Kg").toLowerCase().trim();
    if (s === "kg" || s === "g" || s === "gram") return "g";
    if (s === "l" || s === "liter" || s === "ml") return "ml";
    return satuan || "g";
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

function getCatatanRancangMenu(po) {
    if (po.catatan_rancang_menu && po.catatan_rancang_menu.trim()) {
        return po.catatan_rancang_menu.trim();
    }
    if (po.catatan && po.catatan.trim()) {
        return po.catatan.trim();
    }
    if (po.raw?.catatan && po.raw.catatan.trim()) {
        return po.raw.catatan.trim();
    }
    if (po.raw?.work_order?.catatan && po.raw.work_order.catatan.trim()) {
        return po.raw.work_order.catatan.trim();
    }
    return "";
}

const selectedPo = ref(null);
const showDetailModal = ref(false);
const isProcessing = ref(false);
const isReadOnlyMode = ref(false);
const inputCatatanBaru = ref("");
const searchQuery = ref("");
const statusFilter = ref("semua");

const mainTableRef = ref(null);
const detailTableRef = ref(null);

const isDraggingTable = ref(false);
const dragStartX = ref(0);
const dragStartScrollLeft = ref(0);

const isDraggingMainTable = ref(false);
const dragMainStartX = ref(0);
const dragMainStartScrollLeft = ref(0);

const scrollMainTable = (direction) => {
    if (!mainTableRef.value) return;
    const amount = direction === "left" ? -350 : 350;
    mainTableRef.value.scrollBy({ left: amount, behavior: "smooth" });
};

const scrollDetailTable = (direction) => {
    if (!detailTableRef.value) return;
    const amount = direction === "left" ? -400 : 400;
    detailTableRef.value.scrollBy({ left: amount, behavior: "smooth" });
};

// Laptop Trackpad / Mouse Wheel Horizontal Scroll
const onDetailTableWheel = (e) => {
    const el = detailTableRef.value;
    if (!el) return;

    // Trackpad horizontal gesture (two fingers sliding left/right)
    if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
        el.scrollLeft += e.deltaX;
        e.preventDefault();
        return;
    }

    // Shift + vertical wheel to scroll horizontally
    if (e.shiftKey && e.deltaY !== 0) {
        el.scrollLeft += e.deltaY;
        e.preventDefault();
        return;
    }
};

const onMainTableWheel = (e) => {
    const el = mainTableRef.value;
    if (!el) return;

    if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
        el.scrollLeft += e.deltaX;
        e.preventDefault();
        return;
    }

    if (e.shiftKey && e.deltaY !== 0) {
        el.scrollLeft += e.deltaY;
        e.preventDefault();
        return;
    }
};

// Mouse Drag to Scroll for Detail Table
const onTableMouseDown = (e) => {
    const target = e.target;
    if (target.closest("input, select, button, textarea, a, label")) return;

    const el = detailTableRef.value;
    if (!el) return;

    isDraggingTable.value = true;
    dragStartX.value = e.pageX - el.offsetLeft;
    dragStartScrollLeft.value = el.scrollLeft;
};

// Mouse Drag to Scroll for Main Table
const onMainTableMouseDown = (e) => {
    const target = e.target;
    if (target.closest("input, select, button, textarea, a, label")) return;

    const el = mainTableRef.value;
    if (!el) return;

    isDraggingMainTable.value = true;
    dragMainStartX.value = e.pageX - el.offsetLeft;
    dragMainStartScrollLeft.value = el.scrollLeft;
};

const onWindowMouseMove = (e) => {
    if (isDraggingTable.value && detailTableRef.value) {
        e.preventDefault();
        const x = e.pageX - detailTableRef.value.offsetLeft;
        const walk = (x - dragStartX.value) * 1.5;
        detailTableRef.value.scrollLeft = dragStartScrollLeft.value - walk;
    }

    if (isDraggingMainTable.value && mainTableRef.value) {
        e.preventDefault();
        const x = e.pageX - mainTableRef.value.offsetLeft;
        const walk = (x - dragMainStartX.value) * 1.5;
        mainTableRef.value.scrollLeft = dragMainStartScrollLeft.value - walk;
    }
};

const onWindowMouseUp = () => {
    isDraggingTable.value = false;
    isDraggingMainTable.value = false;
};

onMounted(() => {
    window.addEventListener("mousemove", onWindowMouseMove);
    window.addEventListener("mouseup", onWindowMouseUp);
});

onUnmounted(() => {
    window.removeEventListener("mousemove", onWindowMouseMove);
    window.removeEventListener("mouseup", onWindowMouseUp);
});





const activeList = computed(() => {
    return Array.isArray(props.verifikasiPoList) ? props.verifikasiPoList : [];
});

const filteredList = computed(() => {
    return activeList.value.filter((po) => {
        // Status filter
        if (
            statusFilter.value !== "semua" &&
            po.status_po !== statusFilter.value
        ) {
            return false;
        }

        // Search query
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const woId = (po.wo_id || "").toLowerCase();
            const poId = (po.id || "").toLowerCase();
            const menu = (po.menu || "").toLowerCase();
            const tgl = (po.tanggal || "").toLowerCase();
            const vendor = (po.vendor || "").toLowerCase();
            return (
                woId.includes(q) ||
                poId.includes(q) ||
                menu.includes(q) ||
                tgl.includes(q) ||
                vendor.includes(q)
            );
        }

        return true;
    });
});

// Summary Metrics
const totalPengajuanCount = computed(() => activeList.value.length);
const totalSasaranPm = computed(() =>
    activeList.value.reduce(
        (acc, po) => acc + (Number(po.total_porsi || po.total_pm) || 0),
        0,
    ),
);
const totalNominalMaster = computed(() =>
    activeList.value.reduce(
        (acc, po) =>
            acc + (Number(po.total_nominal_master || po.total_nominal) || 0),
        0,
    ),
);
const countMenunggu = computed(
    () =>
        activeList.value.filter(
            (po) =>
                po.status_po === "Menunggu Verifikasi" ||
                po.status_po === "Diajukan ke Keuangan",
        ).length,
);
const countDraft = computed(
    () =>
        activeList.value.filter((po) => po.status_po === "Draft Verifikasi")
            .length,
);
const countDitolak = computed(
    () => activeList.value.filter((po) => po.status_po === "Ditolak").length,
);

function openVerificationModal(po, readOnly = false) {
    selectedPo.value = JSON.parse(JSON.stringify(po));
    if (!selectedPo.value.items) {
        selectedPo.value.items = [];
    }
    selectedPo.value.items.forEach((item) => {
        const gross = Number(item.gross_kg) || 0;
        if (
            item.stok_digunakan_kg === undefined ||
            item.stok_digunakan_kg === null
        ) {
            item.stok_digunakan_kg = 0;
        } else {
            item.stok_digunakan_kg = Number(item.stok_digunakan_kg) || 0;
        }
        if (item.qty_beli_po_kg === undefined || item.qty_beli_po_kg === null) {
            item.qty_beli_po_kg = Math.max(
                0,
                parseFloat((gross - item.stok_digunakan_kg).toFixed(4)),
            );
        } else {
            item.qty_beli_po_kg = Number(item.qty_beli_po_kg);
        }
        if (!item.sumber_pengadaan) {
            if (item.stok_digunakan_kg >= gross && gross > 0) {
                item.sumber_pengadaan = "100% Dari Stok";
            } else if (item.stok_digunakan_kg > 0) {
                item.sumber_pengadaan = "Parsial Stok";
            } else {
                item.sumber_pengadaan = "Beli PO";
            }
        }
    });
    selectedPo.value.riwayat_verifikasi =
        po.riwayat_verifikasi ||
        po.raw?.riwayat_verifikasi ||
        po.raw?.work_order?.riwayat_verifikasi ||
        [];
    inputCatatanBaru.value = "";
    isReadOnlyMode.value = readOnly;
    showDetailModal.value = true;
}

function onStokChange(item) {
    const gross = Number(item.gross_kg) || 0;
    let stok = Number(item.stok_digunakan_kg);
    if (isNaN(stok) || stok < 0) stok = 0;
    if (stok > gross) stok = gross;
    item.stok_digunakan_kg = parseFloat(stok.toFixed(4));
    item.qty_beli_po_kg = Math.max(
        0,
        parseFloat((gross - item.stok_digunakan_kg).toFixed(4)),
    );
    if (item.stok_digunakan_kg >= gross && gross > 0) {
        item.sumber_pengadaan = "100% Dari Stok";
    } else if (item.stok_digunakan_kg > 0) {
        item.sumber_pengadaan = "Parsial Stok";
    } else {
        item.sumber_pengadaan = "Beli PO";
    }
}

function setItemFullStok(item) {
    const gross = Number(item.gross_kg) || 0;
    item.stok_digunakan_kg = gross;
    item.qty_beli_po_kg = 0;
    item.sumber_pengadaan = "100% Dari Stok";
}

function setItemResetStok(item) {
    const gross = Number(item.gross_kg) || 0;
    item.stok_digunakan_kg = 0;
    item.qty_beli_po_kg = gross;
    item.sumber_pengadaan = "Beli PO";
}

function bulkSetAllFullBeli() {
    if (!selectedPo.value?.items) return;
    selectedPo.value.items.forEach((item) => {
        setItemResetStok(item);
    });
}

function bulkSetAllFullStok() {
    if (!selectedPo.value?.items) return;
    selectedPo.value.items.forEach((item) => {
        setItemFullStok(item);
    });
}

function getItemSubtotalAktual(item) {
    const qtyBeli =
        item.qty_beli_po_kg !== undefined && item.qty_beli_po_kg !== null
            ? Number(item.qty_beli_po_kg)
            : Math.max(
                  0,
                  (Number(item.gross_kg) || 0) -
                      (Number(item.stok_digunakan_kg) || 0),
              );
    const harga =
        Number(
            item.harga_aktual !== undefined &&
                item.harga_aktual !== null &&
                item.harga_aktual !== ""
                ? item.harga_aktual
                : item.harga_master,
        ) || 0;
    if (qtyBeli <= 0) return 0;
    let sub = Math.round(qtyBeli * harga);
    if (qtyBeli > 0 && harga > 0 && sub === 0) sub = Math.ceil(qtyBeli * harga);
    return sub;
}

const grandTotalMasterBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => {
        const gross = Number(item.gross_kg) || 0;
        const harga = Number(item.harga_master) || 0;
        let sub = Math.round(gross * harga);
        if (gross > 0 && harga > 0 && sub === 0) sub = Math.ceil(gross * harga);
        return acc + sub;
    }, 0);
});

const grandTotalGrossKg = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce(
        (acc, item) => acc + (Number(item.gross_kg) || 0),
        0,
    );
});

const grandTotalStokKg = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce(
        (acc, item) => acc + (Number(item.stok_digunakan_kg) || 0),
        0,
    );
});

const grandTotalBeliPoKg = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => {
        const qtyBeli =
            item.qty_beli_po_kg !== undefined && item.qty_beli_po_kg !== null
                ? Number(item.qty_beli_po_kg)
                : Math.max(
                      0,
                      (Number(item.gross_kg) || 0) -
                          (Number(item.stok_digunakan_kg) || 0),
                  );
        return acc + qtyBeli;
    }, 0);
});

const totalAktualBiaya = computed(() => {
    if (!selectedPo.value?.items) return 0;
    return selectedPo.value.items.reduce((acc, item) => {
        return acc + getItemSubtotalAktual(item);
    }, 0);
});

// Sub Menu Komponen mapping
const subMenuKomponen = computed(() => {
    const raw = selectedPo.value?.raw || selectedPo.value || {};
    return {
        sub_menu_1:
            raw.sub_menu_1 ||
            selectedPo.value?.sub_menu_1 ||
            selectedPo.value?.raw?.work_order?.sub_menu_1 ||
            "",
        sub_menu_2:
            raw.sub_menu_2 ||
            selectedPo.value?.sub_menu_2 ||
            selectedPo.value?.raw?.work_order?.sub_menu_2 ||
            "",
        sub_menu_3:
            raw.sub_menu_3 ||
            selectedPo.value?.sub_menu_3 ||
            selectedPo.value?.raw?.work_order?.sub_menu_3 ||
            "",
        sub_menu_4:
            raw.sub_menu_4 ||
            selectedPo.value?.sub_menu_4 ||
            selectedPo.value?.raw?.work_order?.sub_menu_4 ||
            "",
        sub_menu_5:
            raw.sub_menu_5 ||
            selectedPo.value?.sub_menu_5 ||
            selectedPo.value?.raw?.work_order?.sub_menu_5 ||
            "",
    };
});

function getSubMenuLabelForBahan(it) {
    const keyMap = {
        sub_menu_1: "Sub Menu 1",
        sub_menu_2: "Sub Menu 2",
        sub_menu_3: "Sub Menu 3",
        sub_menu_4: "Sub Menu 4",
        sub_menu_5: "Sub Menu 5",
    };
    const rawKey = it.sub_menu_key || "sub_menu_1";
    const label = keyMap[rawKey] || "Sub Menu 1";
    const namaMenu =
        it.nama_sub_menu ||
        subMenuKomponen.value[rawKey] ||
        selectedPo.value?.[rawKey] ||
        selectedPo.value?.raw?.[rawKey] ||
        selectedPo.value?.raw?.work_order?.[rawKey] ||
        "";
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

// Aksi 1: Simpan sebagai Draft Verifikasi (Tetap di Verifikasi PO)
function saveAsDraftVerification() {
    if (!selectedPo.value) return;
    isProcessing.value = true;

    router.post(
        "/keuangan/po/" + selectedPo.value.db_id + "/verifikasi",
        {
            status_po: "Draft Verifikasi",
            catatan: inputCatatanBaru.value,
            items: selectedPo.value.items,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showDetailModal.value = false;
            },
            onError: () => {
                isProcessing.value = false;
            },
        },
    );
}

// Aksi 2: Setujui & Lanjutkan ke Daftar PO Resmi
function approveAndProceedToPoList() {
    if (!selectedPo.value) return;
    isProcessing.value = true;

    router.post(
        "/keuangan/po/" + selectedPo.value.db_id + "/verifikasi",
        {
            status_po: "Terverifikasi",
            catatan: inputCatatanBaru.value,
            items: selectedPo.value.items,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showDetailModal.value = false;
                // Navigasi ke Daftar PO resmi
                router.visit("/keuangan/daftar-po");
            },
            onError: () => {
                isProcessing.value = false;
            },
        },
    );
}

// Aksi 3: Tolak PO
function rejectPo() {
    if (!selectedPo.value) return;
    if (!selectedPo.value.catatan && !inputCatatanBaru.value.trim()) {
        alert(
            "Harap masukkan catatan/alasan penolakan agar Tim Gizi dapat melakukan perbaikan.",
        );
        return;
    }
    isProcessing.value = true;

    router.post(
        "/keuangan/po/" + selectedPo.value.db_id + "/verifikasi",
        {
            status_po: "Ditolak",
            catatan: inputCatatanBaru.value,
            items: selectedPo.value.items,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isProcessing.value = false;
                showDetailModal.value = false;
            },
            onError: () => {
                isProcessing.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="space-y-6">
        <!-- Metrics Ringkasan Pengajuan PO -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Card 1: Total Pengajuan PO -->
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
                            Pengajuan Masuk
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-extrabold text-blue-900 mt-0.5"
                        >
                            {{ totalPengajuanCount }} PO
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 2: Total Sasaran PM -->
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
                            Total Sasaran PM
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-0.5"
                        >
                            {{ totalSasaranPm.toLocaleString("id-ID") }} Porsi
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 3: Est. Total Belanja Master -->
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
                            Est. Belanja Master
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-bold text-amber-900 mt-0.5"
                        >
                            {{ formatRupiah(totalNominalMaster) }}
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 4: Status Verifikasi / Menunggu Verifikasi -->
            <Card className="bg-white border-slate-200/80 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100"
                    >
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            Menunggu Telaah
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-bold text-indigo-900 mt-0.5 flex items-center gap-1.5 flex-wrap"
                        >
                            <span>{{ countMenunggu }} PO</span>
                            <span
                                v-if="countDraft > 0"
                                class="text-[10px] text-amber-700 font-bold bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded"
                            >
                                {{ countDraft }} Draft
                            </span>
                            <span
                                v-if="countDitolak > 0"
                                class="text-[10px] text-rose-700 font-bold bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded"
                            >
                                {{ countDitolak }} Ditolak
                            </span>
                        </h3>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Toolbar Filter & Pencarian -->
        <div
            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/90 shadow-2xs"
        >
            <div class="flex flex-1 items-center gap-2.5 flex-wrap">
                <!-- Search Box -->
                <div class="relative flex-1 min-w-[240px]">
                    <Search
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari menu, nomor PO, kode WO, atau tanggal..."
                        class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 focus:border-primary focus:ring-2 focus:ring-primary/20 bg-slate-50/60 hover:bg-white transition-all text-slate-800 placeholder-slate-400 font-medium"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded-full cursor-pointer"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-auto">
                    <select
                        v-model="statusFilter"
                        class="w-full sm:w-auto px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-700 transition-all cursor-pointer"
                    >
                        <option value="semua">Semua Status Verifikasi</option>
                        <option value="Menunggu Verifikasi">
                            Menunggu Verifikasi
                        </option>
                        <option value="Draft Verifikasi">
                            Draft Verifikasi
                        </option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
            </div>

            <!-- Scroll Navigation Controls for Main Table -->
            <div class="flex items-center gap-1 self-end sm:self-center shrink-0">
                <button
                    type="button"
                    @click="scrollMainTable('left')"
                    class="h-8 w-8 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                    title="Geser tabel ke kiri"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    @click="scrollMainTable('right')"
                    class="h-8 w-8 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                    title="Geser tabel ke kanan"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Card: Tabel Utama Verifikasi PO -->
        <div
            class="border border-slate-200/90 rounded-2xl overflow-hidden shadow-2xs bg-white w-full max-w-full min-w-0"
        >
            <div
                ref="mainTableRef"
                @wheel="onMainTableWheel"
                @mousedown="onMainTableMouseDown"
                :class="[
                    'overflow-x-auto w-full max-w-full table-scroll-container custom-horizontal-scrollbar select-text overscroll-x-contain',
                    isDraggingMainTable ? 'cursor-grabbing select-none' : 'cursor-grab'
                ]"
                style="overscroll-behavior-x: contain; touch-action: pan-x pan-y;"
            >
                <table
                    class="w-full min-w-[1050px] text-left text-xs border-collapse"
                >
                    <thead>
                        <tr
                            class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                        >
                            <th class="py-3.5 px-4 w-12 text-center">No</th>
                            <th class="py-3.5 px-4 min-w-[170px]">
                                Kode WO & Tanggal
                            </th>
                            <th class="py-3.5 px-5 min-w-[260px]">Nama Menu</th>
                            <th class="py-3.5 px-4 text-center min-w-[130px]">
                                Total PM
                            </th>
                            <th class="py-3.5 px-4 text-right min-w-[140px]">
                                Est. Belanja Bahan
                            </th>
                            <th class="py-3.5 px-4 min-w-[170px]">
                                Waktu Pengajuan
                            </th>
                            <th class="py-3.5 px-4 text-center min-w-[120px]">
                                Status Verifikasi
                            </th>
                            <th
                                class="py-3.5 px-4 text-center w-36 sticky right-0 z-20 bg-slate-50 border-l border-slate-200/90 shadow-[-4px_0_8px_-3px_rgba(0,0,0,0.06)]"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="(po, index) in filteredList"
                            :key="po.id"
                            class="group hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- 1. No Urut -->
                            <td
                                class="py-4 px-4 text-center font-bold text-slate-400"
                            >
                                {{ index + 1 }}
                            </td>

                            <!-- 2. Kode WO & Tanggal Distribusi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <div>
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
                                        <span
                                            class="font-bold text-xs text-primary bg-primary/10 px-2 py-0.5 rounded inline-block mt-1"
                                        >
                                            {{ po.wo_id }}
                                            <div
                                                class="text-[10.5px] text-slate-500 font-medium"
                                            >
                                                No. PO:
                                                <span
                                                    class="font-bold text-slate-700"
                                                    >{{ po.id }}</span
                                                >
                                            </div>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Nama Menu & Kandungan Nutrisi -->
                            <td class="py-4 px-5 max-w-sm">
                                <div class="space-y-1">
                                    <p
                                        class="font-bold text-slate-900 leading-snug text-xs sm:text-sm"
                                    >
                                        {{ po.menu }}
                                    </p>
                                </div>
                            </td>

                            <!-- 4. Sasaran Porsi -->
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

                            <!-- 5. Estimasi Belanja Bahan -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div
                                    class="font-black text-slate-900 text-xs sm:text-[13px]"
                                >
                                    {{
                                        formatRupiah(
                                            po.total_nominal_master ||
                                                po.total_nominal,
                                        )
                                    }}
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

                            <!-- 6. Waktu Pengajuan -->
                            <td
                                class="py-4 px-4 whitespace-nowrap text-[10.5px]"
                            >
                                <div class="text-slate-600">
                                    <span class="text-slate-400"
                                        >Diajukan:</span
                                    >
                                    {{ formatDateTimeIndo(po.created_at) }}
                                </div>
                                <div
                                    v-if="
                                        po.diverifikasi_pada &&
                                        po.status_po === 'Ditolak'
                                    "
                                    class="text-rose-600 font-semibold mt-0.5"
                                >
                                    Ditolak:
                                    {{
                                        formatDateTimeIndo(po.diverifikasi_pada)
                                    }}
                                </div>
                                <div
                                    v-else-if="po.diverifikasi_pada"
                                    class="text-emerald-700 font-semibold mt-0.5"
                                >
                                    Diverifikasi:
                                    {{
                                        formatDateTimeIndo(po.diverifikasi_pada)
                                    }}
                                </div>
                            </td>

                            <!-- 7. Status Verifikasi -->
                            <td class="py-4 px-4 text-center">
                                <span
                                    :class="[
                                        'px-2.5 py-1 text-[10.5px] font-bold rounded-lg border inline-block whitespace-nowrap',
                                        po.status_po === 'Draft Verifikasi'
                                            ? 'bg-amber-50 text-amber-700 border-amber-300'
                                            : po.status_po === 'Ditolak'
                                              ? 'bg-rose-50 text-rose-700 border-rose-300'
                                              : 'bg-blue-50 text-blue-700 border-blue-300 animate-pulse',
                                    ]"
                                >
                                    {{ po.status_po }}
                                </span>
                            </td>

                            <!-- 8. Aksi (Sticky Right) -->
                            <td
                                class="py-4 px-4 text-center whitespace-nowrap sticky right-0 z-10 bg-white group-hover:bg-slate-50 border-l border-slate-200/90 shadow-[-4px_0_8px_-3px_rgba(0,0,0,0.06)]"
                            >
                                <div
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <!-- Verifikasi Belanja (Hijau) -->
                                    <button
                                        v-if="
                                            po.status_po ===
                                                'Menunggu Verifikasi' ||
                                            po.status_po ===
                                                'Diajukan ke Keuangan' ||
                                            po.status_po === 'Draft Verifikasi'
                                        "
                                        type="button"
                                        @click="
                                            openVerificationModal(po, false)
                                        "
                                        class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Verifikasi & Telaah Belanja PO"
                                    >
                                        <ShieldCheck class="h-4 w-4" />
                                    </button>

                                    <!-- Detail / Lihat Hasil (Biru) -->
                                    <button
                                        type="button"
                                        @click="openVerificationModal(po, true)"
                                        class="h-8 w-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200/80 flex items-center justify-center shadow-2xs transition-colors cursor-pointer"
                                        title="Lihat Detail & Catatan Riwayat"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredList.length === 0">
                            <td
                                colspan="8"
                                class="p-12 text-center text-slate-400 font-medium"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2.5"
                                >
                                    <ShieldCheck
                                        class="h-10 w-10 text-slate-300"
                                    />
                                    <p
                                        class="text-sm font-semibold text-slate-600"
                                    >
                                        {{
                                            activeList.length === 0
                                                ? "Tidak ada pengajuan PO yang perlu diverifikasi."
                                                : "Tidak ditemukan pengajuan PO yang sesuai dengan kriteria pencarian / filter."
                                        }}
                                    </p>
                                    <p class="text-xs text-slate-400 max-w-sm">
                                        {{
                                            activeList.length === 0
                                                ? "Ketika Tim Gizi mengajukan rancangan menu pada modul Gizi, pengajuan belanja bahan baku akan muncul di halaman ini."
                                                : "Coba ubah kata kunci pencarian atau ganti status filter yang dipilih."
                                        }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Verifikasi Detail Pembelian Bahan (Akuntan) -->
        <Modal
            :show="showDetailModal"
            @close="showDetailModal = false"
            maxWidth="landscape"
        >
            <div
                v-if="selectedPo"
                class="bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-200 text-slate-800 flex flex-col max-h-[88vh] w-full max-w-full min-w-0"
            >
                <!-- Modal Header -->
                <div
                    class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between shrink-0"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
                        >
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-black text-slate-900">
                                    {{
                                        isReadOnlyMode
                                            ? "Detail Pengajuan PO"
                                            : "Form Telaah & Verifikasi PO"
                                    }}
                                    ({{ selectedPo.id }})
                                </h3>
                                <Badge
                                    variant="outline"
                                    :className="
                                        selectedPo.status_po === 'Ditolak'
                                            ? 'bg-rose-50 text-rose-700 border-rose-300 font-bold'
                                            : 'bg-blue-50 text-blue-700 border-blue-300 font-bold'
                                    "
                                >
                                    {{ selectedPo.status_po }}
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Referensi WO:
                                <strong class="text-slate-800">{{
                                    selectedPo.wo_id
                                }}</strong>
                                • Menu: {{ selectedPo.menu }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 sm:p-6 space-y-6 overflow-y-auto flex-1 min-w-0 max-w-full">
                    <!-- Banner Info Mode -->
                    <div
                        v-if="!isReadOnlyMode"
                        class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2.5 text-xs text-blue-900"
                    >
                        <AlertCircle
                            class="h-4 w-4 text-blue-600 shrink-0 mt-0.5"
                        />
                        <div>
                            <span class="font-bold"
                                >Instruksi Verifikasi Akuntan:</span
                            >
                            Periksa kuantitas kebutuhan kotor (kg) dan sesuaikan
                            harga satuan aktual belanja pasar/supplier rekanan
                            SPPG. Anda dapat menyimpan sebagai
                            <strong>Draft Verifikasi</strong> atau langsung
                            <strong>Setujui & Lanjutkan ke Daftar PO</strong>.
                        </div>
                    </div>
                    <div
                        v-else-if="selectedPo.status_po === 'Ditolak'"
                        class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-2.5 text-xs text-rose-900"
                    >
                        <XCircle
                            class="h-4 w-4 text-rose-600 shrink-0 mt-0.5"
                        />
                        <div>
                            <span class="font-bold">Status Penolakan PO:</span>
                            Pengajuan PO ini telah ditolak dan sedang menunggu
                            revisi/perbaikan bahan dari Tim Gizi.
                        </div>
                    </div>

                    <!-- Detail Info PO & Waktu Lengkap (6 Ringkasan Utama) -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs"
                    >
                        <div>
                            <span
                                class="text-slate-400 text-[10px] uppercase font-bold block"
                                >Tanggal Distribusi</span
                            >
                            <span class="font-bold text-slate-800">{{
                                formatTanggalIndo(selectedPo.tanggal)
                            }}</span>
                        </div>
                        <div>
                            <span
                                class="text-slate-400 text-[10px] uppercase font-bold block"
                                >Waktu Pengajuan</span
                            >
                            <span class="font-bold text-slate-800">{{
                                formatDateTimeIndo(selectedPo.created_at)
                            }}</span>
                        </div>
                        <div
                            class="bg-amber-50/60 p-2 rounded-lg border border-amber-200/60"
                        >
                            <span
                                class="text-amber-800 text-[10px] uppercase font-bold block mb-0.5"
                                >Resep Gizi (Gross)</span
                            >
                            <div class="flex flex-wrap items-center gap-1">
                                <span
                                    v-for="(grp, gIdx) in getGroupedUnitList(selectedPo.items, 'gross_kg')"
                                    :key="gIdx"
                                    class="px-1.5 py-0.5 rounded bg-white border border-amber-200 text-amber-950 text-xs font-black shadow-2xs"
                                >
                                    {{ grp.label }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="bg-blue-50/60 p-2 rounded-lg border border-blue-200/60"
                        >
                            <span
                                class="text-blue-800 text-[10px] uppercase font-bold block mb-0.5"
                                >Pengalihan Stok</span
                            >
                            <div class="flex flex-wrap items-center gap-1">
                                <span
                                    v-for="(grp, gIdx) in getGroupedUnitList(selectedPo.items, 'stok_digunakan_kg')"
                                    :key="gIdx"
                                    class="px-1.5 py-0.5 rounded bg-white border border-blue-200 text-blue-950 text-xs font-black shadow-2xs"
                                >
                                    {{ grp.label }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="bg-emerald-50/60 p-2 rounded-lg border border-emerald-200/60"
                        >
                            <span
                                class="text-emerald-800 text-[10px] uppercase font-bold block mb-0.5"
                                >Total Belanja PO</span
                            >
                            <div class="flex flex-wrap items-center gap-1">
                                <span
                                    v-for="(grp, gIdx) in getGroupedUnitList(selectedPo.items, (it) => it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null ? it.qty_beli_po_kg : Math.max(0, (it.gross_kg || 0) - (it.stok_digunakan_kg || 0)))"
                                    :key="gIdx"
                                    class="px-1.5 py-0.5 rounded bg-white border border-emerald-300 text-emerald-950 text-xs font-black shadow-2xs"
                                >
                                    {{ grp.label }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="bg-emerald-100/70 p-2 rounded-lg border border-emerald-300"
                        >
                            <span
                                class="text-emerald-900 text-[10px] uppercase font-bold block"
                                >Total Aktual PO</span
                            >
                            <span
                                class="font-black text-emerald-950 text-xs sm:text-sm"
                                >{{ formatRupiah(totalAktualBiaya) }}</span
                            >
                        </div>
                    </div>

                    <!-- Card Section: Rekapitulasi Kebutuhan Bahan Pangan & Order Pembelian (PO) -->
                    <div class="space-y-3 min-w-0 max-w-full w-full">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3"
                        >
                            <div>
                                <h4
                                    class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2"
                                >
                                    <Package class="h-4 w-4 text-primary" />
                                    <span
                                        >5. Rekapitulasi Kebutuhan Bahan Pangan
                                        & Order Pembelian (PO)</span
                                    >
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Bahan pangan otomatis dikelompokkan & tertotal per bahan database pangan tanpa duplikasi antar sub menu. Standar gramasi resep tetap utuh. Tentukan porsi pengalihan stok gudang SPPG atau kuantitas belanja PO aktual.
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs"
                                >
                                    {{ selectedPo.items.length }} Bahan Baku
                                    Terdaftar
                                </span>
                                <!-- Quick Bulk Action Buttons -->
                                <div
                                    v-if="!isReadOnlyMode"
                                    class="flex items-center gap-1.5"
                                >
                                    <button
                                        type="button"
                                        @click="bulkSetAllFullBeli"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 cursor-pointer transition-colors shadow-2xs"
                                        title="Reset semua baris menjadi 100% Belanja PO (0 kg stok)"
                                    >
                                        Semua Full Beli
                                    </button>
                                    <button
                                        type="button"
                                        @click="bulkSetAllFullStok"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-300 cursor-pointer transition-colors shadow-2xs"
                                        title="Alihkan semua baris menjadi 100% dipenuhi dari stok gudang"
                                    >
                                        Semua 100% Stok
                                    </button>
                                </div>

                                <!-- Horizontal Scroll Buttons for Detail Table -->
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="scrollDetailTable('left')"
                                        class="h-7 w-7 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 shadow-2xs flex items-center justify-center transition-colors cursor-pointer"
                                        title="Geser tabel ke kiri"
                                    >
                                        <ChevronLeft class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="scrollDetailTable('right')"
                                        class="h-7 w-7 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 shadow-2xs flex items-center justify-center transition-colors cursor-pointer"
                                        title="Geser tabel ke kanan"
                                    >
                                        <ChevronRight class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Detail Bahan Baku PO (Style Rancang Menu) -->
                        <div
                            ref="detailTableRef"
                            @wheel="onDetailTableWheel"
                            @mousedown="onTableMouseDown"
                            :class="[
                                'overflow-auto max-h-[54vh] rounded-xl border border-slate-200 bg-white shadow-2xs max-w-full w-full table-scroll-container custom-horizontal-scrollbar select-text overscroll-x-contain',
                                isDraggingTable ? 'cursor-grabbing select-none' : 'cursor-grab'
                            ]"
                            style="overscroll-behavior-x: contain; touch-action: pan-x pan-y;"
                        >
                            <table
                                class="w-full text-xs text-left border-collapse min-w-[1350px]"
                            >
                                <thead
                                    class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px] sticky top-0 z-20 shadow-xs"
                                >
                                    <tr>
                                        <th
                                            class="p-3 w-10 text-center sticky left-0 top-0 z-30 bg-slate-100 border-r border-slate-200/60"
                                        >
                                            NO
                                        </th>
                                        <th
                                            class="p-3 min-w-[190px] sm:min-w-[220px] sticky left-10 top-0 z-30 bg-slate-100 border-r border-slate-200 shadow-[4px_0_8px_-3px_rgba(0,0,0,0.06)]"
                                        >
                                            BAHAN PANGAN & NAMA DI PO
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[130px]"
                                        >
                                            PERUNTUKAN / SUB MENU
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[100px]"
                                        >
                                            TIPE PORSI
                                        </th>
                                        <th class="p-3 min-w-[100px]">
                                            KATEGORI
                                        </th>
                                        <th class="p-3 text-center min-w-[80px]">
                                            SATUAN
                                        </th>
                                        <th class="p-3 text-center min-w-[110px]">
                                            JENIS
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[95px]"
                                        >
                                            GRAMASI / PORSI
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[85px]"
                                        >
                                            BDD / BUFFER
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[120px] bg-amber-50/40"
                                        >
                                            KEBUTUHAN KOTOR (RESEP)
                                        </th>
                                        <th
                                            class="p-3 text-center min-w-[145px] bg-blue-50/50"
                                        >
                                            STOK GUDANG
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[130px] bg-emerald-50/40"
                                        >
                                            QTY BELI PO
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[110px]"
                                        >
                                            HARGA MASTER
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[135px]"
                                        >
                                            HARGA SATUAN (RP)
                                        </th>
                                        <th
                                            class="p-3 text-right min-w-[125px] bg-emerald-50/30"
                                        >
                                            SUBTOTAL PO (RP)
                                        </th>
                                        <th
                                            class="p-3 text-left min-w-[130px] whitespace-normal break-words"
                                        >
                                            KETERANGAN
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr
                                        v-for="(b, i) in selectedPo.items"
                                        :key="b.id || i"
                                        class="group hover:bg-slate-50/60 transition-colors"
                                    >
                                        <!-- 1. NO (Sticky Left) -->
                                        <td
                                            class="p-3 text-center font-bold text-slate-500 sticky left-0 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-200/60"
                                        >
                                            {{ i + 1 }}
                                        </td>

                                        <!-- 2. BAHAN PANGAN & NAMA DI PO (Sticky Left) -->
                                        <td
                                            class="p-3 sticky left-10 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-200 shadow-[4px_0_8px_-3px_rgba(0,0,0,0.06)]"
                                        >
                                            <div
                                                class="font-black text-slate-900 leading-tight"
                                            >
                                                {{ b.nama }}
                                            </div>
                                            <div
                                                class="text-[11px] text-primary font-bold mt-0.5"
                                            >
                                                PO: {{ b.nama_po || b.nama }}
                                            </div>
                                            <span
                                                v-if="b.alergen"
                                                class="inline-block text-[9.5px] bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.2 rounded font-semibold mt-1"
                                            >
                                                Alergen: {{ b.alergen }}
                                            </span>
                                        </td>

                                        <!-- 3. PERUNTUKAN / SUB MENU -->
                                        <td class="p-3 text-center">
                                            <div
                                                class="inline-flex flex-col items-center gap-1"
                                            >
                                                <!-- Jika bahan berasal dari beberapa Sub Menu sekaligus (dikelompokkan) -->
                                                <div
                                                    v-if="b.sub_menus && b.sub_menus.length > 1"
                                                    class="flex flex-wrap items-center justify-center gap-1 max-w-[200px]"
                                                >
                                                    <span
                                                        v-for="(sm, smIdx) in b.sub_menus"
                                                        :key="smIdx"
                                                        class="px-1.5 py-0.5 rounded text-[10px] font-black border shadow-2xs"
                                                        :class="sm.badgeClass || (sm.is_alergi ? 'bg-rose-100 text-rose-800 border-rose-300' : 'bg-slate-100 text-slate-800 border-slate-300')"
                                                        :title="sm.nama ? `${sm.label}: ${sm.nama} (${formatGrossQty(sm.gross_kg, b.satuan)})` : sm.label"
                                                    >
                                                        {{ sm.label }}
                                                    </span>
                                                </div>
                                                <!-- Jika hanya 1 Sub Menu -->
                                                <span
                                                    v-else
                                                    class="px-2 py-0.5 rounded text-[10.5px] font-black border"
                                                    :class="
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).badgeClass
                                                    "
                                                >
                                                    {{
                                                        getSubMenuLabelForBahan(
                                                            b,
                                                        ).label
                                                    }}
                                                </span>

                                                <span
                                                    v-if="
                                                        (b.nama_sub_menu || getSubMenuLabelForBahan(b).namaMenu) &&
                                                        (b.nama_sub_menu || getSubMenuLabelForBahan(b).namaMenu) !== '-'
                                                    "
                                                    class="text-[10px] text-slate-600 font-bold mt-0.5 max-w-[170px] truncate block text-center"
                                                    :title="b.nama_sub_menu || getSubMenuLabelForBahan(b).namaMenu"
                                                >
                                                    {{ b.nama_sub_menu || getSubMenuLabelForBahan(b).namaMenu }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- 4. TIPE PORSI -->
                                        <td class="p-3 text-center">
                                            <span
                                                :class="[
                                                    'px-2.5 py-0.5 text-[10px] font-bold rounded-md border inline-block',
                                                    b.tipe_porsi === 'alergi' ||
                                                    b.tipe === 'Alergi'
                                                        ? 'bg-rose-50 text-rose-800 border-rose-200'
                                                        : 'bg-slate-50 text-slate-700 border-slate-200',
                                                ]"
                                            >
                                                {{
                                                    b.tipe_porsi === 'alergi' ||
                                                    b.tipe === 'Alergi'
                                                        ? 'Alergi: ' +
                                                          (b.jenis_alergi ||
                                                              'Khusus')
                                                        : 'Normal'
                                                }}
                                            </span>
                                        </td>

                                        <!-- 5. KATEGORI -->
                                        <td
                                            class="p-3 text-slate-600 text-[11px] font-medium"
                                        >
                                            {{ b.kategori || 'Lainnya' }}
                                        </td>

                                        <!-- 6. SATUAN -->
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-black">
                                                {{ b.satuan || 'Kg' }}
                                            </span>
                                        </td>

                                        <!-- 7. JENIS -->
                                        <td class="p-3 text-center">
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded text-[10px] font-bold border inline-flex items-center gap-1',
                                                    (b.jenis || 'bahan_baku') === 'operasional'
                                                        ? 'bg-purple-50 text-purple-800 border-purple-200'
                                                        : 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                ]"
                                            >
                                                {{ (b.jenis || 'bahan_baku') === 'operasional' ? '📦 Operasional' : '🥗 Bahan Baku' }}
                                            </span>
                                        </td>

                                        <!-- 8. GRAMASI / PORSI -->
                                        <td
                                            class="p-3 text-center font-bold text-slate-800 whitespace-nowrap"
                                        >
                                            <div>
                                                {{ b.gram_pk || 0 }} {{ getPortionUnit(b.satuan) }} /
                                                {{ b.gram_pb || 0 }} {{ getPortionUnit(b.satuan) }}
                                            </div>
                                            <span
                                                v-if="b.sub_menus && b.sub_menus.length > 1"
                                                class="text-[9px] text-blue-600 font-semibold block mt-0.5"
                                            >
                                                (Total {{ b.sub_menus.length }} Komponen)
                                            </span>
                                        </td>

                                        <!-- 9. BDD / BUFFER -->
                                        <td
                                            class="p-3 text-center text-[11px] text-slate-600 whitespace-nowrap"
                                        >
                                            {{ b.bdd || 100 }}% / +{{
                                                b.buffer || 0
                                            }}%
                                        </td>

                                        <!-- 10. KEBUTUHAN KOTOR (RESEP - UTUH TIDAK BERUBAH) -->
                                        <td
                                            class="p-3 text-right font-black text-slate-900 whitespace-nowrap bg-amber-50/20"
                                        >
                                            <div
                                                class="text-xs font-black text-slate-900"
                                            >
                                                {{
                                                    formatGrossQty(
                                                        b.gross_kg,
                                                        b.satuan
                                                    )
                                                }}
                                            </div>
                                            <span
                                                v-if="b.sub_menus && b.sub_menus.length > 1"
                                                class="text-[9px] text-blue-700 font-bold block"
                                            >
                                                Total Gabungan ({{ b.sub_menus.length }} Sub Menu)
                                            </span>
                                            <span
                                                v-else
                                                class="text-[9.5px] text-slate-400 font-semibold block"
                                            >
                                                Standar Resep
                                            </span>
                                        </td>

                                        <!-- 11. PENGALIHAN DARI STOK GUDANG -->
                                        <td
                                            class="p-2.5 text-center bg-blue-50/30"
                                        >
                                            <div
                                                v-if="!isReadOnlyMode"
                                                class="space-y-1"
                                            >
                                                <div
                                                    class="relative flex items-center min-w-[105px] max-w-[130px] mx-auto"
                                                >
                                                    <input
                                                        v-model.number="
                                                            b.stok_digunakan_kg
                                                        "
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        :max="b.gross_kg"
                                                        @input="onStokChange(b)"
                                                        @change="
                                                            onStokChange(b)
                                                        "
                                                        class="w-full pr-8 pl-2 py-1 text-right text-xs font-black border border-blue-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 text-blue-900 shadow-2xs"
                                                    />
                                                    <span
                                                        class="absolute right-2 text-[10px] text-slate-400 font-bold pointer-events-none"
                                                        >{{ b.satuan || 'Kg' }}</span
                                                    >
                                                </div>
                                                <div
                                                    class="flex items-center justify-center gap-1"
                                                >
                                                    <button
                                                        type="button"
                                                        @click="
                                                            setItemFullStok(b)
                                                        "
                                                        class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-blue-100 hover:bg-blue-200 text-blue-800 transition-colors cursor-pointer"
                                                        title="Alihkan seluruh kebutuhan dari stok"
                                                    >
                                                        100% Stok
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="
                                                            setItemResetStok(b)
                                                        "
                                                        class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer"
                                                        title="Reset tanpa stok (0)"
                                                    >
                                                        0 {{ b.satuan || 'Kg' }}
                                                    </button>
                                                </div>
                                            </div>
                                            <div
                                                v-else
                                                class="font-bold text-slate-800"
                                            >
                                                {{
                                                    formatGrossQty(
                                                        b.stok_digunakan_kg ||
                                                            0,
                                                        b.satuan
                                                    )
                                                }}
                                            </div>
                                        </td>

                                        <!-- 12. QTY BELI PO -->
                                        <td
                                            class="p-3 text-right whitespace-nowrap bg-emerald-50/20"
                                        >
                                            <div
                                                class="font-black text-xs text-slate-900"
                                            >
                                                {{
                                                    formatGrossQty(
                                                        b.qty_beli_po_kg !==
                                                            undefined &&
                                                            b.qty_beli_po_kg !==
                                                                null
                                                            ? b.qty_beli_po_kg
                                                            : Math.max(
                                                                  0,
                                                                  (b.gross_kg ||
                                                                      0) -
                                                                      (b.stok_digunakan_kg ||
                                                                          0),
                                                              ),
                                                        b.satuan
                                                    )
                                                }}
                                            </div>
                                            <div class="mt-0.5">
                                                <span
                                                    v-if="
                                                        b.qty_beli_po_kg ===
                                                            0 ||
                                                        (b.stok_digunakan_kg >=
                                                            b.gross_kg &&
                                                            b.gross_kg > 0)
                                                    "
                                                    class="inline-block text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                >
                                                    100% Dari Stok
                                                </span>
                                                <span
                                                    v-else-if="
                                                        b.stok_digunakan_kg > 0
                                                    "
                                                    class="inline-block text-[9.5px] font-bold px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 border border-blue-200"
                                                >
                                                    Parsial Stok (-{{
                                                        formatGrossQty(
                                                            b.stok_digunakan_kg,
                                                            b.satuan
                                                        )
                                                    }})
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-[9.5px] text-slate-400 font-semibold"
                                                >
                                                    Full Beli PO
                                                </span>
                                            </div>
                                        </td>

                                        <!-- 13. HARGA MASTER -->
                                        <td
                                            class="p-3 text-right text-slate-600 whitespace-nowrap"
                                        >
                                            {{ formatRupiah(b.harga_master) }} / {{ b.satuan || 'Kg' }}
                                        </td>

                                        <!-- 14. HARGA SATUAN AKTUAL (INPUT / READONLY) -->
                                        <td class="p-2 text-right">
                                            <div
                                                v-if="!isReadOnlyMode"
                                                class="relative flex items-center min-w-[125px]"
                                            >
                                                <span
                                                    class="absolute left-2.5 text-[11px] text-slate-400 font-bold"
                                                    >Rp</span
                                                >
                                                <input
                                                    v-model.number="
                                                        b.harga_aktual
                                                    "
                                                    type="number"
                                                    step="100"
                                                    class="w-full pl-8 pr-2 py-1 text-right text-xs font-bold border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/30 shadow-2xs"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="font-bold text-slate-800 whitespace-nowrap"
                                            >
                                                {{
                                                    formatRupiah(
                                                        b.harga_aktual ||
                                                            b.harga_master,
                                                    )
                                                }} / {{ b.satuan || 'Kg' }}
                                            </div>
                                        </td>

                                        <!-- 15. SUBTOTAL PO AKTUAL -->
                                        <td
                                            class="p-3 text-right font-black whitespace-nowrap bg-emerald-50/20"
                                            :class="
                                                getItemSubtotalAktual(b) === 0
                                                    ? 'text-slate-400'
                                                    : 'text-emerald-900'
                                            "
                                        >
                                            <div class="text-xs font-black">
                                                {{
                                                    formatRupiah(
                                                        getItemSubtotalAktual(
                                                            b,
                                                        ),
                                                    )
                                                }}
                                            </div>
                                            <span
                                                v-if="
                                                    getItemSubtotalAktual(b) ===
                                                        0 &&
                                                    b.gross_kg > 0 &&
                                                    b.stok_digunakan_kg >=
                                                        b.gross_kg
                                                "
                                                class="text-[9.5px] font-bold text-emerald-700 block"
                                            >
                                                (Bebas Biaya PO)
                                            </span>
                                        </td>

                                        <!-- 16. KETERANGAN / CATATAN SPESIFIKASI BAHAN -->
                                        <td
                                            class="p-3 text-slate-600 align-middle text-xs min-w-[140px] whitespace-normal break-words"
                                        >
                                            <div
                                                v-if="
                                                    b.keterangan &&
                                                    b.keterangan !== '-'
                                                "
                                                class="bg-slate-50 border border-slate-200/90 rounded-lg p-2 text-slate-700 leading-relaxed shadow-2xs"
                                            >
                                                <p
                                                    class="text-[11px] italic text-slate-700 font-medium"
                                                >
                                                    "{{ b.keterangan }}"
                                                </p>
                                            </div>
                                            <span
                                                v-else
                                                class="text-slate-400 font-bold text-xs"
                                                >-</span
                                            >
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot
                                    class="bg-white font-bold border-t border-slate-200 text-xs sticky bottom-0 z-20 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]"
                                >
                                    <tr>
                                        <!-- Col 1: NO Sticky Left -->
                                        <td
                                            class="p-3 sticky left-0 bottom-0 z-30 bg-slate-50 border-r border-slate-200/60 text-center font-bold text-slate-400"
                                        >
                                            -
                                        </td>
                                        <!-- Col 2: BAHAN PANGAN Sticky Left -->
                                        <td
                                            class="p-3 sticky left-10 bottom-0 z-30 bg-slate-50 border-r border-slate-200 shadow-[4px_0_8px_-3px_rgba(0,0,0,0.06)] font-black text-slate-800 text-[11px] uppercase tracking-wider"
                                        >
                                            Total Rekapitulasi
                                        </td>
                                        <!-- Col 3-9: (7 columns) Sub Menu, Tipe, Kategori, Satuan, Jenis, Gramasi, BDD -->
                                        <td
                                            colspan="7"
                                            class="p-3.5 text-right uppercase text-[11px] text-slate-600 font-extrabold bg-slate-50"
                                        >
                                            Kebutuhan & Pembelian PO:
                                        </td>
                                        <!-- 10. Resep Gross Grouped Chips -->
                                        <td
                                            class="p-3.5 text-right font-black text-slate-900 bg-amber-50/40 whitespace-nowrap align-middle"
                                        >
                                            <div class="flex flex-col items-end justify-center gap-1">
                                                <div
                                                    v-for="(grp, gIdx) in getGroupedUnitList(selectedPo.items, 'gross_kg')"
                                                    :key="gIdx"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md bg-white border border-amber-200 text-amber-950 text-xs font-black shadow-2xs"
                                                >
                                                    <span>{{ grp.label }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- 11. Stok Grouped Chips -->
                                        <td
                                            class="p-3.5 text-right font-black text-blue-900 bg-blue-50/50 whitespace-nowrap align-middle"
                                        >
                                            <div class="flex flex-col items-end justify-center gap-1">
                                                <div
                                                    v-for="(grp, gIdx) in getGroupedUnitList(selectedPo.items, 'stok_digunakan_kg')"
                                                    :key="gIdx"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md bg-white border border-blue-200 text-blue-950 text-xs font-black shadow-2xs"
                                                >
                                                    <span>{{ grp.label }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- 12. Beli PO Grouped Chips -->
                                        <td
                                            class="p-3.5 text-right font-black text-emerald-900 bg-emerald-50/40 whitespace-nowrap align-middle"
                                        >
                                            <div class="flex flex-col items-end justify-center gap-1">
                                                <div
                                                    v-for="(grp, gIdx) in getGroupedUnitList(selectedPo.items, (it) => it.qty_beli_po_kg !== undefined && it.qty_beli_po_kg !== null ? it.qty_beli_po_kg : Math.max(0, (it.gross_kg || 0) - (it.stok_digunakan_kg || 0)))"
                                                    :key="gIdx"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md bg-white border border-emerald-300 text-emerald-950 text-xs font-black shadow-2xs"
                                                >
                                                    <span>{{ grp.label }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- 13. Harga Master Grand Total -->
                                        <td
                                            class="p-3.5 text-right text-slate-600 whitespace-nowrap align-middle font-bold"
                                        >
                                            {{
                                                formatRupiah(
                                                    grandTotalMasterBiaya,
                                                )
                                            }}
                                        </td>
                                        <!-- 14. Label Total Aktual -->
                                        <td
                                            class="p-3.5 text-right text-[11px] text-slate-500 font-bold whitespace-nowrap align-middle"
                                        >
                                            Total Aktual PO:
                                        </td>
                                        <!-- 15. Total Aktual Nilai Rupiah -->
                                        <td
                                            class="p-3.5 text-right font-black text-emerald-950 text-sm whitespace-nowrap bg-emerald-100/70 align-middle"
                                        >
                                            {{ formatRupiah(totalAktualBiaya) }}
                                        </td>
                                        <!-- 16. Empty cell for keterangan -->
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Riwayat Log Catatan & Verifikasi PO -->
                    <div
                        class="space-y-3 p-4 rounded-xl bg-slate-50 border border-slate-200"
                    >
                        <div class="flex items-center justify-between">
                            <h4
                                class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5"
                            >
                                <Clock class="h-4 w-4 text-primary" />
                                <span
                                    >Riwayat Log Catatan & Telaah Verifikasi ({{
                                        selectedPo.riwayat_verifikasi?.length ||
                                        (selectedPo.catatan ? 1 : 0)
                                    }}
                                    Entri)</span
                                >
                            </h4>
                        </div>

                        <!-- Daftar Log Riwayat -->
                        <div
                            v-if="
                                selectedPo.riwayat_verifikasi &&
                                selectedPo.riwayat_verifikasi.length > 0
                            "
                            class="space-y-2.5 max-h-48 overflow-y-auto pr-1"
                        >
                            <div
                                v-for="(
                                    log, lIdx
                                ) in selectedPo.riwayat_verifikasi"
                                :key="lIdx"
                                :class="[
                                    'p-3 rounded-xl border text-xs space-y-1',
                                    log.status === 'Ditolak'
                                        ? 'bg-rose-50/70 border-rose-200 text-rose-950'
                                        : log.status === 'Terverifikasi' ||
                                            log.status === 'Siap Produksi'
                                          ? 'bg-emerald-50/70 border-emerald-200 text-emerald-950'
                                          : 'bg-white border-slate-200 text-slate-900',
                                ]"
                            >
                                <div
                                    class="flex items-center justify-between gap-2 flex-wrap text-[11px]"
                                >
                                    <div
                                        class="flex items-center gap-1.5 font-bold"
                                    >
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded text-[10px] font-black',
                                                log.status === 'Ditolak'
                                                    ? 'bg-rose-200 text-rose-900'
                                                    : log.status ===
                                                            'Terverifikasi' ||
                                                        log.status ===
                                                            'Siap Produksi'
                                                      ? 'bg-emerald-200 text-emerald-900'
                                                      : 'bg-blue-100 text-blue-800',
                                            ]"
                                        >
                                            {{ log.status }}
                                        </span>
                                        <span class="text-slate-800"
                                            >{{ log.user_nama }} ({{
                                                log.role || "Pengguna"
                                            }})</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center gap-1 text-slate-500 text-[10.5px] bg-white/80 px-2 py-0.5 rounded border border-slate-200/60 shadow-2xs"
                                    >
                                        <Clock
                                            class="h-3 w-3 text-slate-400 shrink-0"
                                        />
                                        <span>{{
                                            formatFullLogTimestamp(log.waktu)
                                        }}</span>
                                    </div>
                                </div>
                                <p
                                    class="text-xs font-medium pl-1 leading-relaxed whitespace-pre-wrap"
                                >
                                    {{
                                        log.catatan ||
                                        "(Tidak ada catatan tertulis)"
                                    }}
                                </p>
                            </div>
                        </div>
                        <div
                            v-else-if="selectedPo.catatan"
                            class="p-3 bg-white rounded-xl border border-slate-200 text-xs"
                        >
                            <span
                                class="text-slate-500 font-bold block text-[10.5px]"
                                >Catatan Terakhir:</span
                            >
                            <p class="font-medium text-slate-800 mt-0.5">
                                {{ selectedPo.catatan }}
                            </p>
                        </div>
                        <div
                            v-else
                            class="text-xs text-slate-400 font-medium py-1"
                        >
                            Belum ada riwayat catatan pada pengajuan PO ini.
                        </div>

                        <!-- Form Tambah Catatan Verifikator -->
                        <div
                            v-if="!isReadOnlyMode"
                            class="pt-2 border-t border-slate-200 space-y-1.5"
                        >
                            <label
                                class="text-xs font-bold text-slate-800 block"
                            >
                                Tambah Catatan Verifikasi / Instruksi Revisi:
                            </label>
                            <textarea
                                v-model="inputCatatanBaru"
                                rows="2"
                                placeholder="Tulis instruksi revisi, alasan penolakan, atau persetujuan harga belanja di sini..."
                                class="w-full p-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 shadow-2xs font-medium"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0"
                >
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 rounded-xl cursor-pointer w-full sm:w-auto"
                    >
                        Tutup
                    </button>
                    <div
                        v-if="!isReadOnlyMode"
                        class="flex items-center gap-2 flex-wrap w-full sm:w-auto justify-end"
                    >
                        <!-- 1. Tolak PO -->
                        <button
                            type="button"
                            @click="rejectPo"
                            :disabled="isProcessing"
                            class="px-4 py-2 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl cursor-pointer disabled:opacity-50"
                        >
                            Tolak PO
                        </button>

                        <!-- 2. Simpan Draft Verifikasi -->
                        <button
                            type="button"
                            @click="saveAsDraftVerification"
                            :disabled="isProcessing"
                            class="px-4 py-2 text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            <span>Simpan Draft Verifikasi</span>
                        </button>

                        <!-- 3. Setujui & Lanjutkan ke Daftar PO -->
                        <button
                            type="button"
                            @click="approveAndProceedToPoList"
                            :disabled="isProcessing"
                            class="px-5 py-2 text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <span>Setujui & Lanjutkan ke Daftar PO</span>
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
