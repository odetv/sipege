<script setup>
import { ref, computed, watch } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    UserCheck,
    Users,
    Plus,
    Pencil,
    Trash2,
    Search,
    Phone,
    Mail,
    Clock,
    DollarSign,
    Shield,
    X,
    Check,
    AlertCircle,
    CheckCircle2,
    Eye,
    Briefcase,
    BadgePercent,
    AlertTriangle,
    RefreshCw,
    MapPin,
    Calendar,
    CreditCard,
    Copy,
} from "lucide-vue-next";

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    petugas: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    daftarJabatan: { type: Array, default: () => [] },
    unitSppg: { type: Object, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ─── Preset Jam Kerja per Divisi (Mendukung Normal 1 Rentang & Kustom Multi-Shift) ──
const presetJamDivisi = {
    "Kepala SPPG": { type: "normal", mulai: "07:00", selesai: "16:00" },
    "PLOG": { type: "normal", mulai: "03:00", selesai: "12:00" },
    "PLOK": { type: "normal", mulai: "07:00", selesai: "16:00" },
    "Koordinator Lapangan": {
        type: "kustom",
        shifts: [
            { mulai: "06:00", selesai: "15:00" },
            { mulai: "17:00", selesai: "19:00" },
            { mulai: "02:00", selesai: "03:00" },
        ],
    },
    "Kepala Lapangan": {
        type: "kustom",
        shifts: [
            { mulai: "06:00", selesai: "15:00" },
            { mulai: "17:00", selesai: "19:00" },
            { mulai: "02:00", selesai: "03:00" },
        ],
    },
    "Chef": {
        type: "kustom",
        shifts: [
            { mulai: "01:00", selesai: "11:00" },
            { mulai: "17:00", selesai: "19:00" },
        ],
    },
    "Kepala Juru Masak (Chef)": {
        type: "kustom",
        shifts: [
            { mulai: "01:00", selesai: "11:00" },
            { mulai: "17:00", selesai: "19:00" },
        ],
    },
    "Pengolahan (Koordinator)": { type: "normal", mulai: "01:00", selesai: "09:00" },
    "Pengolahan": { type: "normal", mulai: "01:00", selesai: "09:00" },
    "Juru Masak": { type: "normal", mulai: "01:00", selesai: "09:00" },
    "Persiapan (Koordinator)": { type: "normal", mulai: "19:00", selesai: "03:00" },
    "Persiapan": { type: "normal", mulai: "19:00", selesai: "03:00" },
    "Pemorsian (Koordinator)": { type: "normal", mulai: "04:00", selesai: "12:00" },
    "Pemorsian": { type: "normal", mulai: "04:00", selesai: "12:00" },
    "Pengemudi (Koordinator 1)": { type: "normal", mulai: "06:00", selesai: "14:00" },
    "Pengemudi (Koordinator 2)": { type: "normal", mulai: "06:00", selesai: "14:00" },
    "Pengemudi": { type: "normal", mulai: "06:00", selesai: "14:00" },
    "Distribusi": { type: "normal", mulai: "06:00", selesai: "14:00" },
    "Cuci Ompreng (Koordinator)": { type: "normal", mulai: "12:00", selesai: "20:00" },
    "Cuci Ompreng": { type: "normal", mulai: "12:00", selesai: "20:00" },
    "Cuci Ompreng (a.k.a Admin)": { type: "normal", mulai: "12:00", selesai: "20:00" },
    "Kebersihan": {
        type: "kustom",
        shifts: [
            { mulai: "05:00", selesai: "13:00" },
            { mulai: "13:00", selesai: "21:00" },
        ],
    },
    "Petugas Kebersihan": {
        type: "kustom",
        shifts: [
            { mulai: "05:00", selesai: "13:00" },
            { mulai: "13:00", selesai: "21:00" },
        ],
    },
    "Keamanan": {
        type: "kustom",
        shifts: [
            { mulai: "06:00", selesai: "18:00" },
            { mulai: "18:00", selesai: "06:00" },
        ],
    },
    "Petugas Keamanan": {
        type: "kustom",
        shifts: [
            { mulai: "06:00", selesai: "18:00" },
            { mulai: "18:00", selesai: "06:00" },
        ],
    },
};

// Preset Jabatan Options Dropdown
const listOpsiJabatan = [
    "Kepala SPPG",
    "PLOG",
    "PLOK",
    "Koordinator Lapangan",
    "Chef",
    "Pengolahan (Koordinator)",
    "Pengolahan",
    "Persiapan (Koordinator)",
    "Persiapan",
    "Pemorsian (Koordinator)",
    "Pemorsian",
    "Pengemudi",
    "Pengemudi (Koordinator 1)",
    "Pengemudi (Koordinator 2)",
    "Cuci Ompreng",
    "Cuci Ompreng (Koordinator)",
    "Cuci Ompreng (a.k.a Admin)",
    "Kebersihan",
    "Keamanan",
];

// Preset Bank Options Dropdown
const listOpsiBank = [
    "BNI",
    "BRI",
    "BCA",
    "Mandiri",
    "BSI",
    "BTN",
    "BPD Bali",
    "CIMB Niaga",
    "Bank Danamon",
    "Bank Permata",
    "Lainnya",
];

// Helper Badge Divisi
function getJabatanBadgeClass(jabatan) {
    if (!jabatan) return "bg-slate-100 text-slate-700 border-slate-200";
    const j = jabatan.toLowerCase();
    if (j.includes("kepala sppg")) return "bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold";
    if (j.includes("plog") || j.includes("plok")) return "bg-teal-50 text-teal-700 border-teal-200 font-semibold";
    if (j.includes("lapangan")) return "bg-blue-50 text-blue-700 border-blue-200";
    if (j.includes("chef")) return "bg-amber-50 text-amber-700 border-amber-200";
    if (j.includes("pengolahan") || j.includes("masak")) return "bg-orange-50 text-orange-700 border-orange-200";
    if (j.includes("persiapan")) return "bg-yellow-50 text-yellow-800 border-yellow-200";
    if (j.includes("pemorsian")) return "bg-indigo-50 text-indigo-700 border-indigo-200";
    if (j.includes("pengemudi") || j.includes("distribusi")) return "bg-sky-50 text-sky-700 border-sky-200";
    if (j.includes("cuci")) return "bg-cyan-50 text-cyan-700 border-cyan-200";
    if (j.includes("kebersihan")) return "bg-purple-50 text-purple-700 border-purple-200";
    if (j.includes("keamanan")) return "bg-rose-50 text-rose-700 border-rose-200";
    return "bg-slate-50 text-slate-700 border-slate-200";
}

// ─── Formatters ───────────────────────────────────────────────────────────────
function formatRupiah(val) {
    if (val === null || val === undefined || isNaN(val)) return "Rp 0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(val);
}

function formatRibuan(num) {
    if (num === null || num === undefined || isNaN(num) || num === "") return "0";
    return new Intl.NumberFormat("id-ID").format(num);
}

function formatDateIndo(dateStr) {
    if (!dateStr) return "-";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    } catch {
        return dateStr;
    }
}

// ─── Filter & Search State ───────────────────────────────────────────────────
const searchQuery = ref("");
const selectedJabatanFilter = ref("all");
const selectedJkFilter = ref("all");
const selectedStatusFilter = ref("all"); // hanya all, Aktif, Nonaktif

const filteredPetugas = computed(() => {
    return props.petugas.filter((p) => {
        // Search text
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const matchNama = (p.nama || "").toLowerCase().includes(q);
            const matchNik = (p.nik || "").toLowerCase().includes(q);
            const matchTelp = (p.no_telp || "").toLowerCase().includes(q);
            const matchEmail = (p.email || "").toLowerCase().includes(q);
            const matchAlamat = (p.alamat || "").toLowerCase().includes(q);
            const matchJabatan = (p.jabatan || "").toLowerCase().includes(q);
            const matchBank = (p.jenis_bank || "").toLowerCase().includes(q);
            const matchRek = (p.nomor_rekening || "").toLowerCase().includes(q);
            if (!matchNama && !matchNik && !matchTelp && !matchEmail && !matchAlamat && !matchJabatan && !matchBank && !matchRek) {
                return false;
            }
        }

        // Filter Jabatan
        if (selectedJabatanFilter.value !== "all") {
            if (p.jabatan !== selectedJabatanFilter.value) {
                return false;
            }
        }

        // Filter Jenis Kelamin
        if (selectedJkFilter.value !== "all") {
            if (p.jenis_kelamin !== selectedJkFilter.value) {
                return false;
            }
        }

        // Filter Status (Hanya Aktif / Nonaktif)
        if (selectedStatusFilter.value !== "all") {
            if (p.status !== selectedStatusFilter.value) {
                return false;
            }
        }

        return true;
    });
});

function resetFilter() {
    searchQuery.value = "";
    selectedJabatanFilter.value = "all";
    selectedJkFilter.value = "all";
    selectedStatusFilter.value = "all";
}

// ─── Modal State: Form (Tambah/Edit) ──────────────────────────────────────────
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const currentPetugasId = ref(null);

// Input rupiah display (dengan titik pemisah ribuan)
const displayGajiBgn = ref("0");
const displayBonusMitra = ref("0");
const displayBpjsTk = ref("16.800");

// State validasi lokal real-time
const clientErrors = ref({});

const form = useForm({
    nama: "",
    nik: "",
    jenis_kelamin: "L",
    tempat_lahir: "",
    tanggal_lahir: "",
    alamat: "",
    no_telp: "",
    email: "",
    jabatan: "",
    jenis_bank: "BNI",
    nomor_rekening: "",
    jam_kerja: "",
    gaji_harian_bgn: 0,
    bonus_harian_mitra: 0,
    iuran_bpjs_tk: 16800,
    status: "Aktif", // hanya Aktif atau Nonaktif
    keterangan: "-",
});

// ─── State Jam Kerja (Hanya 2 Pilihan: Normal & Kustom) ──────────────────────
const tipeJamKerja = ref("normal"); // 'normal' | 'kustom'

// Normal (1 Rentang Jadwal berformat waktu)
const jamMulai = ref("07:00");
const jamSelesai = ref("16:00");

// Kustom (Multi-Shift Dinamis berformat waktu)
const customShifts = ref([
    { mulai: "06:00", selesai: "15:00" },
    { mulai: "17:00", selesai: "19:00" },
]);

// Helper jam berformat titik (05.00) sesuai data excel / database
function toDotTime(str) {
    if (!str) return "00.00";
    return str.replace(":", ".");
}

// Update string form.jam_kerja otomatis
function syncJamKerja() {
    if (tipeJamKerja.value === "kustom") {
        const valid = (customShifts.value || []).filter((s) => s.mulai && s.selesai);
        if (valid.length > 0) {
            form.jam_kerja = valid
                .map((s) => `${toDotTime(s.mulai)} - ${toDotTime(s.selesai)}`)
                .join(" & ");
        } else {
            form.jam_kerja = "";
        }
    } else {
        form.jam_kerja = `${toDotTime(jamMulai.value)} - ${toDotTime(jamSelesai.value)}`;
    }

    if (form.jam_kerja && clientErrors.value.jam_kerja) {
        delete clientErrors.value.jam_kerja;
    }
}

function addCustomShift() {
    customShifts.value.push({ mulai: "08:00", selesai: "16:00" });
    syncJamKerja();
}

function removeCustomShift(idx) {
    if (customShifts.value.length > 1) {
        customShifts.value.splice(idx, 1);
        syncJamKerja();
    }
}

function switchTipeJamKerja(tipe) {
    tipeJamKerja.value = tipe;
    if (tipe === "kustom") {
        if (!customShifts.value || customShifts.value.length === 0) {
            customShifts.value = [
                { mulai: jamMulai.value || "07:00", selesai: jamSelesai.value || "16:00" },
                { mulai: "17:00", selesai: "19:00" },
            ];
        }
    }
    syncJamKerja();
}

// Helper ekstraksi waktu HH:mm atau HH.mm
function parseTimes(str, cb) {
    if (!str) return;
    const matches = str.match(/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/g);
    if (matches && matches.length >= 2) {
        cb(
            matches[0].replace(".", ":").padStart(5, "0"),
            matches[1].replace(".", ":").padStart(5, "0")
        );
    }
}

// Parsing string jam kerja ke input form (Normal vs Kustom)
function parseJamKerjaToInputs(str) {
    if (!str || str.toLowerCase().includes("menyesuaikan")) {
        tipeJamKerja.value = "normal";
        jamMulai.value = "07:00";
        jamSelesai.value = "16:00";
        customShifts.value = [
            { mulai: "07:00", selesai: "16:00" },
            { mulai: "17:00", selesai: "19:00" },
        ];
        return;
    }

    // Cek apakah multi-shift (mengandung '&' atau '/')
    if (str.includes("&") || str.includes("/") || str.toLowerCase().includes("shift")) {
        tipeJamKerja.value = "kustom";
        const delimiter = str.includes("&") ? "&" : (str.includes("/") ? "/" : "&");
        const parts = str.split(delimiter).map((p) => p.trim()).filter(Boolean);
        const parsedShifts = [];

        parts.forEach((part) => {
            const matches = part.match(/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/g);
            if (matches && matches.length >= 2) {
                parsedShifts.push({
                    mulai: matches[0].replace(".", ":").padStart(5, "0"),
                    selesai: matches[1].replace(".", ":").padStart(5, "0"),
                });
            }
        });

        if (parsedShifts.length > 0) {
            customShifts.value = parsedShifts;
        } else {
            customShifts.value = [
                { mulai: "07:00", selesai: "16:00" },
                { mulai: "17:00", selesai: "19:00" },
            ];
        }
    } else {
        // Mode Normal (1 rentang)
        tipeJamKerja.value = "normal";
        const matches = str.match(/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/g);
        if (matches && matches.length >= 2) {
            jamMulai.value = matches[0].replace(".", ":").padStart(5, "0");
            jamSelesai.value = matches[1].replace(".", ":").padStart(5, "0");
        } else {
            jamMulai.value = "07:00";
            jamSelesai.value = "16:00";
        }
        customShifts.value = [
            { mulai: jamMulai.value, selesai: jamSelesai.value },
            { mulai: "17:00", selesai: "19:00" },
        ];
    }
}

// Handler saat Jabatan dropdown dipilih
function onJabatanChange() {
    if (!form.jabatan) return;
    delete clientErrors.value.jabatan;

    const preset = presetJamDivisi[form.jabatan];
    if (preset) {
        if (preset.type === "kustom") {
            tipeJamKerja.value = "kustom";
            customShifts.value = JSON.parse(JSON.stringify(preset.shifts));
        } else {
            tipeJamKerja.value = "normal";
            jamMulai.value = preset.mulai;
            jamSelesai.value = preset.selesai;
        }
        syncJamKerja();
    }
}

// Handler Nomor HP (wajib diawali 62 dan hanya angka)
function handleNoTelpInput(e) {
    let val = e.target.value.replace(/\D/g, ""); // hanya angka
    if (val.startsWith("0")) {
        val = "62" + val.slice(1);
    } else if (val.startsWith("8")) {
        val = "62" + val;
    } else if (!val.startsWith("62") && val.length > 0) {
        val = "62" + val;
    }
    form.no_telp = val;

    if (val.length < 10) {
        clientErrors.value.no_telp = "Nomor HP harus diawali dengan 62 dan minimal 10 digit angka.";
    } else {
        delete clientErrors.value.no_telp;
    }
}

// Handler NIK (hanya angka & harus 16 digit)
function handleNikInput(e) {
    const val = e.target.value.replace(/\D/g, "").slice(0, 16);
    form.nik = val;
    if (val.length !== 16) {
        clientErrors.value.nik = `NIK harus tepat 16 digit angka (saat ini: ${val.length} digit).`;
    } else {
        delete clientErrors.value.nik;
    }
}

// Handler Nomor Rekening (hanya angka)
function handleNomorRekeningInput(e) {
    const val = e.target.value.replace(/\D/g, "");
    form.nomor_rekening = val;
    if (val.length < 5) {
        clientErrors.value.nomor_rekening = "Nomor rekening minimal 5 digit angka.";
    } else {
        delete clientErrors.value.nomor_rekening;
    }
}

// Handler Input Nominal Rupiah (Otomatis titik ribuan)
function handleRupiahInput(field, rawString) {
    const cleanDigits = String(rawString).replace(/\D/g, "");
    const intVal = cleanDigits === "" ? 0 : parseInt(cleanDigits, 10);
    form[field] = intVal;

    const formatted = formatRibuan(intVal);
    if (field === "gaji_harian_bgn") displayGajiBgn.value = formatted;
    if (field === "bonus_harian_mitra") displayBonusMitra.value = formatted;
    if (field === "iuran_bpjs_tk") displayBpjsTk.value = formatted;

    delete clientErrors.value[field];
}

// Validasi menyeluruh frontend sebelum submit
function validateForm() {
    const errors = {};

    if (!form.nama || form.nama.trim().length < 3) {
        errors.nama = "Nama lengkap sesuai KTP wajib diisi (minimal 3 karakter).";
    }

    if (!form.nik || form.nik.length !== 16) {
        errors.nik = "NIK wajib diisi tepat 16 digit angka.";
    }

    if (!form.jenis_kelamin) {
        errors.jenis_kelamin = "Jenis kelamin wajib dipilih.";
    }

    if (!form.tempat_lahir || form.tempat_lahir.trim().length === 0) {
        errors.tempat_lahir = "Tempat lahir wajib diisi.";
    }

    if (!form.tanggal_lahir) {
        errors.tanggal_lahir = "Tanggal lahir wajib diisi.";
    }

    if (!form.alamat || form.alamat.trim().length === 0) {
        errors.alamat = "Alamat lengkap sesuai KTP wajib diisi.";
    }

    if (!form.no_telp || !form.no_telp.startsWith("62") || form.no_telp.length < 10) {
        errors.no_telp = "Nomor HP wajib diisi, diawali 62, dan minimal 10 digit (contoh: 6281234567890).";
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!form.email || !emailRegex.test(form.email)) {
        errors.email = "Email aktif wajib diisi dengan format valid (contoh: nama@domain.com).";
    }

    if (!form.jabatan) {
        errors.jabatan = "Jabatan / Divisi wajib dipilih dari dropdown.";
    }

    if (!form.jenis_bank || form.jenis_bank.trim().length === 0) {
        errors.jenis_bank = "Jenis bank payroll wajib dipilih atau diisi.";
    }

    if (!form.nomor_rekening || form.nomor_rekening.trim().length === 0) {
        errors.nomor_rekening = "Nomor rekening bank wajib diisi (hanya angka).";
    }

    if (tipeJamKerja.value === "normal") {
        if (!jamMulai.value || !jamSelesai.value) {
            errors.jam_kerja = "Jam mulai dan jam selesai wajib ditentukan.";
        }
    } else if (tipeJamKerja.value === "kustom") {
        if (!customShifts.value || customShifts.value.length === 0) {
            errors.jam_kerja = "Minimal 1 rentang waktu / shift harus diisi.";
        } else {
            const hasEmpty = customShifts.value.some((s) => !s.mulai || !s.selesai);
            if (hasEmpty) {
                errors.jam_kerja = "Semua jam mulai dan jam selesai shift wajib diisi lengkap.";
            }
        }
    }

    if (form.gaji_harian_bgn === null || form.gaji_harian_bgn === undefined || isNaN(form.gaji_harian_bgn)) {
        errors.gaji_harian_bgn = "Gaji harian BGN wajib diisi (hanya angka, bisa 0).";
    }

    if (form.bonus_harian_mitra === null || form.bonus_harian_mitra === undefined || isNaN(form.bonus_harian_mitra)) {
        errors.bonus_harian_mitra = "Bonus harian mitra wajib diisi (hanya angka, bisa 0).";
    }

    if (form.iuran_bpjs_tk === null || form.iuran_bpjs_tk === undefined || isNaN(form.iuran_bpjs_tk)) {
        errors.iuran_bpjs_tk = "Iuran BPJS TK wajib diisi (hanya angka, bisa 0).";
    }

    if (!form.status || !["Aktif", "Nonaktif"].includes(form.status)) {
        errors.status = "Status wajib dipilih (Aktif atau Nonaktif).";
    }

    if (!form.keterangan || form.keterangan.trim().length === 0) {
        errors.keterangan = "Keterangan wajib diisi (ketik '-' jika tidak ada keterangan).";
    }

    clientErrors.value = errors;
    return Object.keys(errors).length === 0;
}

function openCreateModal() {
    isEditing.value = false;
    currentPetugasId.value = null;
    clientErrors.value = {};
    form.reset();
    form.clearErrors();

    form.jenis_kelamin = "L";
    form.status = "Aktif";
    form.keterangan = "-";

    tipeJamKerja.value = "reguler";
    jamMulai.value = "07:00";
    jamSelesai.value = "16:00";
    shift1Label.value = "Shift Pagi-Sore";
    shift1Mulai.value = "05:00";
    shift1Selesai.value = "13:00";
    shift2Label.value = "Shift Sore-Malam";
    shift2Mulai.value = "13:00";
    shift2Selesai.value = "21:00";
    customJamKerja.value = "";
    syncJamKerja();

    form.gaji_harian_bgn = 0;
    form.bonus_harian_mitra = 0;
    form.iuran_bpjs_tk = 16800;

    form.jenis_bank = "BNI";
    form.nomor_rekening = "";
    displayGajiBgn.value = "0";
    displayBonusMitra.value = "0";
    displayBpjsTk.value = "16.800";

    isFormModalOpen.value = true;
}

function openEditModal(p) {
    isEditing.value = true;
    currentPetugasId.value = p.id;
    clientErrors.value = {};
    form.clearErrors();

    let tgl = p.tanggal_lahir;
    if (tgl && tgl.includes("T")) {
        tgl = tgl.split("T")[0];
    }

    form.nama = p.nama || "";
    form.nik = p.nik || "";
    form.jenis_kelamin = p.jenis_kelamin || "L";
    form.tempat_lahir = p.tempat_lahir || "";
    form.tanggal_lahir = tgl || "";
    form.alamat = p.alamat || "";
    form.no_telp = p.no_telp || "";
    form.email = p.email || "";
    form.jabatan = p.jabatan || "";
    form.jenis_bank = p.jenis_bank || "BNI";
    form.nomor_rekening = p.nomor_rekening || "";
    form.jam_kerja = p.jam_kerja || "";
    form.gaji_harian_bgn = p.gaji_harian_bgn ?? 0;
    form.bonus_harian_mitra = p.bonus_harian_mitra ?? 0;
    form.iuran_bpjs_tk = p.iuran_bpjs_tk ?? 16800;
    form.status = p.status === "Nonaktif" ? "Nonaktif" : "Aktif";
    form.keterangan = p.keterangan || "-";

    // parsing jam kerja
    parseJamKerjaToInputs(p.jam_kerja);
    syncJamKerja();

    // formatting display rupiah
    displayGajiBgn.value = formatRibuan(form.gaji_harian_bgn);
    displayBonusMitra.value = formatRibuan(form.bonus_harian_mitra);
    displayBpjsTk.value = formatRibuan(form.iuran_bpjs_tk);

    isFormModalOpen.value = true;
}

function closeFormModal() {
    isFormModalOpen.value = false;
    form.reset();
    form.clearErrors();
    clientErrors.value = {};
}

function submitForm() {
    syncJamKerja();
    if (!validateForm()) {
        return;
    }

    if (isEditing.value) {
        form.put(route("petugas.update", currentPetugasId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeFormModal();
            },
        });
    } else {
        form.post(route("petugas.store"), {
            preserveScroll: true,
            onSuccess: () => {
                closeFormModal();
            },
        });
    }
}

// ─── Modal State: Detail Profil Petugas ───────────────────────────────────────
const isDetailModalOpen = ref(false);
const detailPetugas = ref(null);

function openDetailModal(p) {
    detailPetugas.value = p;
    isDetailModalOpen.value = true;
}

function closeDetailModal() {
    isDetailModalOpen.value = false;
    detailPetugas.value = null;
}

// Salin nomor rekening ke clipboard
const copiedRekening = ref(false);
function copyRekening(text) {
    if (!text) return;
    navigator.clipboard.writeText(text);
    copiedRekening.value = true;
    setTimeout(() => {
        copiedRekening.value = false;
    }, 2000);
}

// ─── Modal State: Konfirmasi Hapus ────────────────────────────────────────────
const isDeleteModalOpen = ref(false);
const deletePetugas = ref(null);
const isDeleting = ref(false);

function confirmDelete(p) {
    deletePetugas.value = p;
    isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
    isDeleteModalOpen.value = false;
    deletePetugas.value = null;
}

function executeDelete() {
    if (!deletePetugas.value) return;
    isDeleting.value = true;
    router.delete(route("petugas.destroy", deletePetugas.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            closeDeleteModal();
        },
    });
}

// Kunci scroll body dan pastikan backdrop gelap menyelimuti seluruh layar saat modal aktif
watch(
    [isFormModalOpen, isDetailModalOpen, isDeleteModalOpen],
    ([formOpen, detailOpen, delOpen]) => {
        if (formOpen || detailOpen || delOpen) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }
);
</script>

<template>
    <Head title="Data Petugas SPPG - SIPEGE" />

    <AppLayout>
        <div class="space-y-6 pb-12">
            <!-- ─── Page Header ───────────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-2xs">
                            <UserCheck class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                                Data Petugas SPPG
                            </h1>
                            <p class="text-xs text-slate-500">
                                Manajemen personil operasional, rentang jam kerja, kontak resmi, dan rincian kompensasi
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center justify-center gap-2 px-3.5 py-2 w-full sm:w-auto rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer"
                    >
                        <Plus class="h-4 w-4" />
                        <span>Tambah Petugas</span>
                    </button>
                </div>
            </div>

            <!-- ─── Flash Alert Notification ──────────────────────────────── -->
            <div
                v-if="flash.success"
                class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <CheckCircle2 class="h-4 w-4 text-emerald-600 shrink-0" />
                    <span class="font-medium">{{ flash.success }}</span>
                </div>
                <button
                    type="button"
                    @click="flash.success = null"
                    class="text-emerald-600 hover:text-emerald-800 cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div
                v-if="flash.error"
                class="flex items-center justify-between p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-2xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2.5">
                    <AlertCircle class="h-4 w-4 text-rose-600 shrink-0" />
                    <span class="font-medium">{{ flash.error }}</span>
                </div>
                <button
                    type="button"
                    @click="flash.error = null"
                    class="text-rose-600 hover:text-rose-800 cursor-pointer"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- ─── KPI / Summary Cards (Responsive 2 Kolom di HP) ───────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-3.5">
                <!-- Card 1: Total Petugas -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                        <Users class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Total Personil
                        </p>
                        <p class="text-base sm:text-xl font-bold text-slate-900 leading-tight">
                            {{ summary.total_petugas ?? 0 }} <span class="text-xs font-normal text-slate-500">orang</span>
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            {{ summary.total_laki ?? 0 }} L • {{ summary.total_perempuan ?? 0 }} P
                        </p>
                    </div>
                </div>

                <!-- Card 2: Gaji Harian BGN -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-blue-50 text-blue-600 shrink-0">
                        <DollarSign class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Gaji Harian BGN
                        </p>
                        <p class="text-xs sm:text-base font-bold text-slate-900 leading-tight truncate">
                            {{ formatRupiah(summary.total_gaji_harian_bgn) }}
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Per hari kerja
                        </p>
                    </div>
                </div>

                <!-- Card 3: Bonus Harian Mitra -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-amber-50 text-amber-600 shrink-0">
                        <BadgePercent class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Bonus Mitra
                        </p>
                        <p class="text-xs sm:text-base font-bold text-slate-900 leading-tight truncate">
                            {{ formatRupiah(summary.total_bonus_harian_mitra) }}
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Subsidi insentif
                        </p>
                    </div>
                </div>

                <!-- Card 4: Iuran BPJS TK -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-violet-50 text-violet-600 shrink-0">
                        <Shield class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Iuran BPJS TK
                        </p>
                        <p class="text-xs sm:text-base font-bold text-slate-900 leading-tight truncate">
                            {{ formatRupiah(summary.total_bpjs_tk) }}
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Total iuran premi
                        </p>
                    </div>
                </div>

                <!-- Card 5: Estimasi Periodik (14 Hari) -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-teal-50 text-teal-600 shrink-0">
                        <Calendar class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Gaji Periodik
                        </p>
                        <p class="text-xs sm:text-base font-bold text-teal-700 leading-tight truncate">
                            {{ formatRupiah(summary.total_pengeluaran_periodik) }}
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Standar 14 hari
                        </p>
                    </div>
                </div>

                <!-- Card 6: Estimasi Biaya Bulanan Standar BGN (20 Hari) -->
                <div class="p-3 sm:p-4 rounded-xl bg-white border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3.5">
                    <div class="p-2 sm:p-3 rounded-lg bg-emerald-50 text-emerald-600 shrink-0">
                        <Clock class="h-4 w-4 sm:h-5 sm:w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] sm:text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                            Gaji Bulanan
                        </p>
                        <p class="text-xs sm:text-base font-bold text-emerald-700 leading-tight truncate">
                            {{ formatRupiah(summary.total_pengeluaran_bulanan_20 || (summary.total_pengeluaran_harian * 20)) }}
                        </p>
                        <p class="text-[9.5px] sm:text-[10px] text-slate-500 mt-0.5 truncate">
                            Standar 20 hari
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─── Search & Filter Bar ───────────────────────────────────── -->
            <div class="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, NIK, telp, email..."
                            class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-primary focus:border-primary placeholder:text-slate-400"
                        />
                    </div>

                    <!-- Filter Jabatan -->
                    <div>
                        <select
                            v-model="selectedJabatanFilter"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-primary focus:border-primary"
                        >
                            <option value="all">Semua Jabatan / Divisi</option>
                            <option
                                v-for="jab in listOpsiJabatan"
                                :key="jab"
                                :value="jab"
                            >
                                {{ jab }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Jenis Kelamin -->
                    <div>
                        <select
                            v-model="selectedJkFilter"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-primary focus:border-primary"
                        >
                            <option value="all">Semua Jenis Kelamin</option>
                            <option value="L">Laki-Laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <!-- Filter Status (Hanya 2: Aktif & Nonaktif) -->
                    <div class="flex items-center gap-2">
                        <select
                            v-model="selectedStatusFilter"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-700 focus:ring-1 focus:ring-primary focus:border-primary"
                        >
                            <option value="all">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>

                        <button
                            v-if="searchQuery || selectedJabatanFilter !== 'all' || selectedJkFilter !== 'all' || selectedStatusFilter !== 'all'"
                            type="button"
                            @click="resetFilter"
                            title="Reset Filter"
                            class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer shrink-0"
                        >
                            <RefreshCw class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                    <span>
                        Menampilkan <b>{{ filteredPetugas.length }}</b> dari total <b>{{ petugas.length }}</b> petugas
                    </span>
                    <span v-if="unitSppg" class="text-slate-400">
                        Unit SPPG: <span class="font-semibold text-slate-600">{{ unitSppg.nama_sppg }}</span> ({{ unitSppg.id_sppg }})
                    </span>
                </div>
            </div>

            <!-- ─── Data Table ────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-3.5 py-3 w-12 text-center">No</th>
                                <th class="px-4 py-3 min-w-[200px]">Personil & NIK</th>
                                <th class="px-4 py-3 min-w-[190px]">Jabatan & Jam Kerja</th>
                                <th class="px-4 py-3 min-w-[170px]">Kontak Resmi</th>
                                <th class="px-4 py-3 min-w-[150px]">Kompensasi Harian</th>
                                <th class="px-3.5 py-3 w-24 text-center">Status</th>
                                <th class="px-4 py-3 w-28 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="(p, idx) in filteredPetugas"
                                :key="p.id"
                                class="hover:bg-slate-50/80 transition-colors group"
                            >
                                <!-- No -->
                                <td class="px-3.5 py-3.5 text-center text-slate-400 font-medium text-[11px]">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Personil & NIK -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <div
                                            :class="[
                                                'h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5',
                                                p.jenis_kelamin === 'L'
                                                    ? 'bg-blue-50 text-blue-600 border border-blue-200'
                                                    : 'bg-rose-50 text-rose-600 border border-rose-200'
                                            ]"
                                        >
                                            {{ p.nama ? p.nama.charAt(0).toUpperCase() : 'P' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openDetailModal(p)"
                                                    class="font-bold text-slate-900 hover:text-primary transition-colors text-left truncate cursor-pointer"
                                                >
                                                    {{ p.nama }}
                                                </button>
                                                <span
                                                    :class="[
                                                        'text-[9.5px] px-1.5 py-0.5 rounded font-semibold',
                                                        p.jenis_kelamin === 'L' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700'
                                                    ]"
                                                >
                                                    {{ p.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-mono tracking-tight mt-0.5">
                                                NIK: {{ p.nik }}
                                            </div>
                                            <div class="text-[10.5px] text-slate-500 mt-0.5">
                                                {{ p.tempat_lahir }}, {{ formatDateIndo(p.tanggal_lahir) }}
                                                <span v-if="p.umur" class="text-slate-400">({{ p.umur }} th)</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Jabatan & Jam Kerja -->
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1.5">
                                        <div>
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded-md text-[11px] border',
                                                    getJabatanBadgeClass(p.jabatan)
                                                ]"
                                            >
                                                {{ p.jabatan }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-600">
                                            <Clock class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                            <span class="font-medium whitespace-pre-line leading-relaxed">
                                                {{ p.jam_kerja }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kontak Resmi & Rekening -->
                                <td class="px-4 py-3.5">
                                    <div class="space-y-1">
                                        <a
                                            v-if="p.no_telp"
                                            :href="`https://wa.me/${p.no_telp}`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 text-xs text-slate-700 hover:text-emerald-600 transition-colors font-mono"
                                        >
                                            <Phone class="h-3 w-3 text-emerald-500 shrink-0" />
                                            <span>+{{ p.no_telp }}</span>
                                        </a>
                                        <div v-if="p.email" class="flex items-center gap-1.5 text-[11px] text-slate-500 truncate max-w-[180px]">
                                            <Mail class="h-3 w-3 text-slate-400 shrink-0" />
                                            <span class="truncate" :title="p.email">{{ p.email }}</span>
                                        </div>
                                        <div
                                            v-if="p.nomor_rekening"
                                            class="flex items-center gap-1 text-[10.5px] text-slate-600 font-mono pt-1 border-t border-slate-100"
                                            :title="`Bank ${p.jenis_bank || 'BNI'} - ${p.nomor_rekening}`"
                                        >
                                            <CreditCard class="h-3 w-3 text-emerald-600 shrink-0" />
                                            <span class="font-bold text-slate-700">{{ p.jenis_bank || 'BNI' }}:</span>
                                            <span class="text-slate-800 font-medium">{{ p.nomor_rekening }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kompensasi Harian -->
                                <td class="px-4 py-3.5">
                                    <div class="space-y-0.5 text-[11px]">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-slate-400">BGN:</span>
                                            <span class="font-semibold text-slate-800">{{ formatRupiah(p.gaji_harian_bgn) }}</span>
                                        </div>
                                        <div v-if="p.bonus_harian_mitra > 0" class="flex items-center justify-between gap-2 text-amber-700">
                                            <span class="text-amber-500">Mitra:</span>
                                            <span class="font-semibold">+{{ formatRupiah(p.bonus_harian_mitra) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 text-[10px] text-slate-400 pt-0.5 border-t border-slate-100">
                                            <span>BPJS TK:</span>
                                            <span>{{ formatRupiah(p.iuran_bpjs_tk) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status (Hanya Aktif / Nonaktif) -->
                                <td class="px-3.5 py-3.5 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-semibold border',
                                            p.status === 'Aktif'
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                : 'bg-slate-100 text-slate-600 border-slate-200'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                p.status === 'Aktif' ? 'bg-emerald-500' : 'bg-slate-400'
                                            ]"
                                        ></span>
                                        {{ p.status }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="openDetailModal(p)"
                                            title="Lihat Detail Profil"
                                            class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors cursor-pointer"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEditModal(p)"
                                            title="Edit Data Petugas"
                                            class="p-1.5 rounded-md hover:bg-blue-50 text-blue-600 transition-colors cursor-pointer"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(p)"
                                            title="Hapus Petugas"
                                            class="p-1.5 rounded-md hover:bg-rose-50 text-rose-600 transition-colors cursor-pointer"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredPetugas.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Users class="h-8 w-8 text-slate-300 stroke-[1.5]" />
                                        <p class="text-sm font-medium text-slate-600">
                                            Tidak ada data petugas yang cocok
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            Coba ubah kata kunci pencarian atau reset filter.
                                        </p>
                                        <button
                                            type="button"
                                            @click="resetFilter"
                                            class="mt-2 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── Modal Form (Tambah / Edit Petugas) ───────────────────────── -->
        <Teleport to="body">
            <div
                v-if="isFormModalOpen"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat (Dim Seluruh Layar Termasuk Sidebar) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeFormModal"
                ></div>

                <div
                    class="relative z-10 w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-300 my-8 overflow-hidden animate-in zoom-in-95 duration-150"
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-lg bg-emerald-100 text-emerald-800">
                                <UserCheck class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    {{ isEditing ? "Edit Data Petugas" : "Tambah Petugas Baru" }}
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Semua field wajib diisi lengkap sesuai format resmi
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeFormModal"
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Section 1: Identitas Pribadi -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-100 flex items-center gap-1.5">
                                <Users class="h-3.5 w-3.5 text-primary" />
                                <span>1. Identitas Pribadi</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Nama Lengkap Sesuai KTP -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Nama Lengkap Sesuai KTP <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.nama"
                                        type="text"
                                        required
                                        placeholder="Contoh: I Gede Gelgel Abdiutama"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.nama || form.errors.nama ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    />
                                    <p v-if="clientErrors.nama || form.errors.nama" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.nama || form.errors.nama }}</span>
                                    </p>
                                </div>

                                <!-- NIK (Hanya Angka, 16 Digit) -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        NIK (16 Digit Angka) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        :value="form.nik"
                                        @input="handleNikInput"
                                        type="text"
                                        required
                                        maxlength="16"
                                        placeholder="Contoh: 5108052607020004"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs font-mono focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.nik || form.errors.nik ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    />
                                    <p v-if="clientErrors.nik || form.errors.nik" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.nik || form.errors.nik }}</span>
                                    </p>
                                </div>

                                <!-- Jenis Kelamin -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Jenis Kelamin <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.jenis_kelamin"
                                        required
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.jenis_kelamin || form.errors.jenis_kelamin ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    >
                                        <option value="L">Laki-Laki (L)</option>
                                        <option value="P">Perempuan (P)</option>
                                    </select>
                                    <p v-if="clientErrors.jenis_kelamin || form.errors.jenis_kelamin" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.jenis_kelamin || form.errors.jenis_kelamin }}</span>
                                    </p>
                                </div>

                                <!-- Tempat Lahir -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tempat Lahir <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.tempat_lahir"
                                        type="text"
                                        required
                                        placeholder="Contoh: Mataram / Singaraja"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.tempat_lahir || form.errors.tempat_lahir ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    />
                                    <p v-if="clientErrors.tempat_lahir || form.errors.tempat_lahir" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.tempat_lahir || form.errors.tempat_lahir }}</span>
                                    </p>
                                </div>

                                <!-- Tanggal Lahir -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Tanggal Lahir <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.tanggal_lahir"
                                        type="date"
                                        required
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.tanggal_lahir || form.errors.tanggal_lahir ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    />
                                    <p v-if="clientErrors.tanggal_lahir || form.errors.tanggal_lahir" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.tanggal_lahir || form.errors.tanggal_lahir }}</span>
                                    </p>
                                </div>

                                <!-- Alamat Lengkap Sesuai KTP -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Alamat Lengkap Sesuai KTP <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.alamat"
                                        rows="2"
                                        required
                                        placeholder="Alamat lengkap RT/RW, Banjar Dinas / Lingkungan, Desa/Kelurahan, Kecamatan"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.alamat || form.errors.alamat ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    ></textarea>
                                    <p v-if="clientErrors.alamat || form.errors.alamat" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.alamat || form.errors.alamat }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Kontak Resmi (Mulai 62xxxx) -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-100 flex items-center gap-1.5">
                                <Phone class="h-3.5 w-3.5 text-primary" />
                                <span>2. Kontak Resmi</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- No Telp WA (Mulai 62xxxx) -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Nomor Telepon / WA (Format 62xxxx) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-emerald-700 font-mono">
                                            +
                                        </span>
                                        <input
                                            :value="form.no_telp"
                                            @input="handleNoTelpInput"
                                            type="text"
                                            required
                                            placeholder="6281234567890"
                                            :class="[
                                                'w-full pl-7 pr-3 py-2 rounded-lg border text-xs font-mono focus:ring-1 focus:ring-primary focus:border-primary',
                                                clientErrors.no_telp || form.errors.no_telp ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                            ]"
                                        />
                                    </div>
                                    <p v-if="clientErrors.no_telp || form.errors.no_telp" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.no_telp || form.errors.no_telp }}</span>
                                    </p>
                                </div>

                                <!-- Email Aktif -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Email Aktif <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        placeholder="Contoh: nama@domain.com"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.email || form.errors.email ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    />
                                    <p v-if="clientErrors.email || form.errors.email" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.email || form.errors.email }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Jabatan & Jam Kerja (Mendukung Rolling Shift 2 Pilihan) -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-100 flex items-center gap-1.5">
                                <Briefcase class="h-3.5 w-3.5 text-primary" />
                                <span>3. Jabatan & Jam Kerja</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Jabatan / Divisi Dropdown (Tidak Boleh Diketik Manual) -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Jabatan / Divisi <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.jabatan"
                                        required
                                        @change="onJabatanChange"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.jabatan || form.errors.jabatan ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    >
                                        <option value="" disabled>-- Pilih Jabatan / Divisi --</option>
                                        <option
                                            v-for="opt in listOpsiJabatan"
                                            :key="opt"
                                            :value="opt"
                                        >
                                            {{ opt }}
                                        </option>
                                    </select>
                                    <p v-if="clientErrors.jabatan || form.errors.jabatan" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.jabatan || form.errors.jabatan }}</span>
                                    </p>
                                </div>

                                <!-- Status Petugas (Hanya Aktif dan Nonaktif) -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Status Petugas <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.status"
                                        required
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.status || form.errors.status ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    >
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                    <p v-if="clientErrors.status || form.errors.status" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.status || form.errors.status }}</span>
                                    </p>
                                </div>

                                <!-- Jam Kerja: Pilihan Mode (Normal vs Kustom) -->
                                <div class="sm:col-span-2">
                                    <div class="flex items-center justify-between mb-1.5 flex-wrap gap-1">
                                        <label class="block text-xs font-semibold text-slate-700">
                                            Sistem Jam Kerja Operasional <span class="text-rose-500">*</span>
                                        </label>
                                        <!-- Switch Mode Buttons (Normal vs Kustom) -->
                                        <div class="inline-flex rounded-lg p-0.5 bg-slate-100 border border-slate-200">
                                            <button
                                                type="button"
                                                @click="switchTipeJamKerja('normal')"
                                                :class="[
                                                    'px-3 py-1 text-[11px] font-semibold rounded-md transition-all cursor-pointer',
                                                    tipeJamKerja === 'normal'
                                                        ? 'bg-white text-emerald-700 shadow-xs'
                                                        : 'text-slate-500 hover:text-slate-800'
                                                ]"
                                            >
                                                Normal
                                            </button>
                                            <button
                                                type="button"
                                                @click="switchTipeJamKerja('kustom')"
                                                :class="[
                                                    'px-3 py-1 text-[11px] font-semibold rounded-md transition-all cursor-pointer',
                                                    tipeJamKerja === 'kustom'
                                                        ? 'bg-white text-emerald-700 shadow-xs'
                                                        : 'text-slate-500 hover:text-slate-800'
                                                ]"
                                            >
                                                Kustom
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mode 1: Normal (1 Rentang Jadwal) -->
                                    <div v-if="tipeJamKerja === 'normal'" class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                            <div class="flex items-center gap-2">
                                                <div class="relative w-full">
                                                    <span class="text-[10px] text-slate-500 uppercase font-bold block mb-0.5">Jam Mulai</span>
                                                    <input
                                                        v-model="jamMulai"
                                                        @input="syncJamKerja"
                                                        type="time"
                                                        required
                                                        class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-primary focus:border-primary bg-white"
                                                    />
                                                </div>
                                                <span class="text-xs font-bold text-slate-400 shrink-0 mt-4">s.d</span>
                                            </div>

                                            <div class="relative w-full">
                                                <span class="text-[10px] text-slate-500 uppercase font-bold block mb-0.5">Jam Selesai</span>
                                                <input
                                                    v-model="jamSelesai"
                                                    @input="syncJamKerja"
                                                    type="time"
                                                    required
                                                    class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-primary focus:border-primary bg-white"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mode 2: Kustom (Multi-Shift Dinamis Berformat Waktu) -->
                                    <div v-else-if="tipeJamKerja === 'kustom'" class="p-3.5 rounded-xl bg-teal-50/40 border border-teal-200 space-y-3">
                                        <div class="flex items-center justify-between pb-1 border-b border-teal-100">
                                            <span class="text-[11px] font-bold text-teal-800 flex items-center gap-1.5">
                                                <Clock class="h-3.5 w-3.5 text-teal-600" />
                                                <span>Rentang Waktu Kerja / Shift</span>
                                            </span>
                                            <button
                                                type="button"
                                                @click="addCustomShift"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-teal-700 bg-white hover:bg-teal-100 rounded-lg border border-teal-300 shadow-2xs transition-colors cursor-pointer"
                                            >
                                                <Plus class="h-3.5 w-3.5" />
                                                <span>Tambah Shift</span>
                                            </button>
                                        </div>

                                        <div class="space-y-2.5">
                                            <div
                                                v-for="(shift, idx) in customShifts"
                                                :key="idx"
                                                class="p-2.5 rounded-xl bg-white border border-teal-200 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5"
                                            >
                                                <div class="flex items-center gap-2 shrink-0">
                                                    <span class="inline-flex items-center justify-center h-6 px-2.5 rounded-md bg-teal-100 text-teal-800 text-[10px] font-black uppercase tracking-wider">
                                                        Shift {{ idx + 1 }}
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 gap-2 grow items-center">
                                                    <div class="relative w-full">
                                                        <span class="text-[10px] text-slate-500 uppercase font-bold block mb-0.5">Jam Mulai</span>
                                                        <input
                                                            v-model="shift.mulai"
                                                            @input="syncJamKerja"
                                                            type="time"
                                                            required
                                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                                        />
                                                    </div>
                                                    <div class="relative w-full">
                                                        <span class="text-[10px] text-slate-500 uppercase font-bold block mb-0.5">Jam Selesai</span>
                                                        <input
                                                            v-model="shift.selesai"
                                                            @input="syncJamKerja"
                                                            type="time"
                                                            required
                                                            class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold focus:ring-1 focus:ring-teal-500 focus:border-teal-500 bg-white"
                                                        />
                                                    </div>
                                                </div>

                                                <div class="shrink-0 flex items-center justify-end">
                                                    <button
                                                        v-if="customShifts.length > 1"
                                                        type="button"
                                                        @click="removeCustomShift(idx)"
                                                        class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer mt-2 sm:mt-0"
                                                        title="Hapus Shift"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preview Format Yang Tersimpan -->
                                    <div class="mt-2 p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs">
                                        <span class="text-emerald-800 font-medium flex items-center gap-1.5 shrink-0">
                                            <Clock class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                            <span>Format Jam Tersimpan:</span>
                                        </span>
                                        <span class="font-bold text-emerald-950 bg-white px-2.5 py-1 rounded border border-emerald-300 font-mono text-[11px] truncate ml-2">
                                            {{ form.jam_kerja || '-' }}
                                        </span>
                                    </div>
                                    <p v-if="clientErrors.jam_kerja || form.errors.jam_kerja" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.jam_kerja || form.errors.jam_kerja }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Rekening Bank Payroll -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-100 flex items-center gap-1.5">
                                <CreditCard class="h-3.5 w-3.5 text-primary" />
                                <span>4. Rekening Bank Payroll</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Jenis Bank -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Jenis Bank <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="space-y-1.5">
                                        <select
                                            v-model="form.jenis_bank"
                                            required
                                            :class="[
                                                'w-full px-3 py-2 rounded-lg border text-xs font-medium focus:ring-1 focus:ring-primary focus:border-primary',
                                                clientErrors.jenis_bank || form.errors.jenis_bank ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                            ]"
                                        >
                                            <option v-for="bank in listOpsiBank" :key="bank" :value="bank">
                                                Bank {{ bank }}
                                            </option>
                                        </select>
                                        <!-- Custom input jika memilih Lainnya -->
                                        <input
                                            v-if="form.jenis_bank === 'Lainnya'"
                                            v-model="form.jenis_bank"
                                            type="text"
                                            placeholder="Ketik nama bank lainnya..."
                                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-primary focus:border-primary"
                                        />
                                    </div>
                                    <p v-if="clientErrors.jenis_bank || form.errors.jenis_bank" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.jenis_bank || form.errors.jenis_bank }}</span>
                                    </p>
                                </div>

                                <!-- Nomor Rekening -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Nomor Rekening <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        :value="form.nomor_rekening"
                                        @input="handleNomorRekeningInput"
                                        type="text"
                                        required
                                        maxlength="30"
                                        placeholder="Contoh: 1972750642"
                                        :class="[
                                            'w-full px-3 py-2 rounded-lg border text-xs font-mono font-medium focus:ring-1 focus:ring-primary focus:border-primary',
                                            clientErrors.nomor_rekening || form.errors.nomor_rekening ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                        ]"
                                    />
                                    <p v-if="clientErrors.nomor_rekening || form.errors.nomor_rekening" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.nomor_rekening || form.errors.nomor_rekening }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Kompensasi Rupiah Otomatis Format Titik -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-100 flex items-center gap-1.5">
                                <DollarSign class="h-3.5 w-3.5 text-primary" />
                                <span>5. Kompensasi & BPJS (Otomatis Format Titik)</span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <!-- Gaji Harian BGN -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Gaji Harian BGN (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                        <input
                                            :value="displayGajiBgn"
                                            @input="e => handleRupiahInput('gaji_harian_bgn', e.target.value)"
                                            type="text"
                                            required
                                            placeholder="0"
                                            :class="[
                                                'w-full pl-9 pr-3 py-2 rounded-lg border text-xs font-mono font-semibold focus:ring-1 focus:ring-primary focus:border-primary',
                                                clientErrors.gaji_harian_bgn || form.errors.gaji_harian_bgn ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                            ]"
                                        />
                                    </div>
                                    <p v-if="clientErrors.gaji_harian_bgn || form.errors.gaji_harian_bgn" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.gaji_harian_bgn || form.errors.gaji_harian_bgn }}</span>
                                    </p>
                                </div>

                                <!-- Bonus Harian Mitra -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Bonus Harian Mitra (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                        <input
                                            :value="displayBonusMitra"
                                            @input="e => handleRupiahInput('bonus_harian_mitra', e.target.value)"
                                            type="text"
                                            required
                                            placeholder="0"
                                            :class="[
                                                'w-full pl-9 pr-3 py-2 rounded-lg border text-xs font-mono font-semibold focus:ring-1 focus:ring-primary focus:border-primary',
                                                clientErrors.bonus_harian_mitra || form.errors.bonus_harian_mitra ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                            ]"
                                        />
                                    </div>
                                    <p v-if="clientErrors.bonus_harian_mitra || form.errors.bonus_harian_mitra" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.bonus_harian_mitra || form.errors.bonus_harian_mitra }}</span>
                                    </p>
                                </div>

                                <!-- Iuran BPJS TK -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Iuran BPJS TK (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                        <input
                                            :value="displayBpjsTk"
                                            @input="e => handleRupiahInput('iuran_bpjs_tk', e.target.value)"
                                            type="text"
                                            required
                                            placeholder="16.800"
                                            :class="[
                                                'w-full pl-9 pr-3 py-2 rounded-lg border text-xs font-mono font-semibold focus:ring-1 focus:ring-primary focus:border-primary',
                                                clientErrors.iuran_bpjs_tk || form.errors.iuran_bpjs_tk ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                            ]"
                                        />
                                    </div>
                                    <p v-if="clientErrors.iuran_bpjs_tk || form.errors.iuran_bpjs_tk" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3 w-3 shrink-0" />
                                        <span>{{ clientErrors.iuran_bpjs_tk || form.errors.iuran_bpjs_tk }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Live Preview Estimasi Bulanan Standar BGN 20 Hari -->
                            <div class="mt-2.5 p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs">
                                <span class="text-emerald-800 font-medium flex items-center gap-1.5">
                                    <Clock class="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                    <span>Estimasi Gaji Bulanan BGN (x20 Hari Kerja):</span>
                                </span>
                                <span class="font-bold text-emerald-950 font-mono text-xs bg-white px-2 py-0.5 rounded border border-emerald-300">
                                    {{ formatRupiah(((form.gaji_harian_bgn || 0) + (form.bonus_harian_mitra || 0)) * 20) }}
                                </span>
                            </div>
                        </div>

                        <!-- Section 6: Keterangan -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 pb-1 border-b border-slate-100 flex items-center gap-1.5">
                                <Shield class="h-3.5 w-3.5 text-primary" />
                                <span>6. Keterangan Tambahan</span>
                            </h3>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Keterangan Tambahan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.keterangan"
                                type="text"
                                required
                                placeholder="Ketik '-' jika tidak ada keterangan khusus"
                                :class="[
                                    'w-full px-3 py-2 rounded-lg border text-xs focus:ring-1 focus:ring-primary focus:border-primary',
                                    clientErrors.keterangan || form.errors.keterangan ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'
                                ]"
                            />
                            <p v-if="clientErrors.keterangan || form.errors.keterangan" class="text-[11px] text-rose-600 font-medium mt-1 flex items-center gap-1">
                                <AlertCircle class="h-3 w-3 shrink-0" />
                                <span>{{ clientErrors.keterangan || form.errors.keterangan }}</span>
                            </p>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                            <button
                                type="button"
                                @click="closeFormModal"
                                class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-xs disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                <Check class="h-4 w-4" />
                                <span>{{ isEditing ? "Simpan Perubahan" : "Simpan Petugas" }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ─── Modal Detail Profil Petugas (Tampilan Kontras Tinggi & Bersih) ── -->
        <Teleport to="body">
            <div
                v-if="isDetailModalOpen && detailPetugas"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat (Dim Seluruh Layar Termasuk Sidebar) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeDetailModal"
                ></div>

                <div
                    class="relative z-10 w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-300 my-8 overflow-hidden animate-in zoom-in-95 duration-150"
                >
                    <!-- Header Card Detail (Solid Emerald Background & High Contrast) -->
                    <div
                        class="p-6 relative text-white"
                        style="background-color: #065f46; color: #ffffff;"
                    >
                        <button
                            type="button"
                            @click="closeDetailModal"
                            class="absolute top-4 right-4 p-1.5 rounded-lg text-white hover:bg-black/20 transition-colors cursor-pointer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                        <div class="flex items-center gap-4">
                            <div
                                class="h-14 w-14 rounded-2xl flex items-center justify-center text-2xl font-black text-white shrink-0 shadow-sm"
                                style="background-color: #047857; border: 2px solid #34d399;"
                            >
                                {{ detailPetugas.nama ? detailPetugas.nama.charAt(0).toUpperCase() : 'P' }}
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold truncate text-white">
                                    {{ detailPetugas.nama }}
                                </h2>
                                <p class="text-xs text-emerald-200 font-mono mt-0.5">
                                    NIK: {{ detailPetugas.nik }}
                                </p>
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                        style="background-color: #047857; color: #ecfdf5; border: 1px solid #10b981;"
                                    >
                                        {{ detailPetugas.jabatan }}
                                    </span>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[11px] font-bold text-slate-900"
                                        style="background-color: #ffffff;"
                                    >
                                        ● {{ detailPetugas.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Body Card Detail (Teks Tajam, Kontras Jelas) -->
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto text-xs bg-white text-slate-800">
                        <!-- Jam Kerja Banner -->
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-300 flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-amber-100 text-amber-800 shrink-0">
                                <Clock class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-bold text-amber-900 text-xs">Jadwal Jam Kerja Operasional</p>
                                <p class="text-sm font-extrabold text-amber-950 whitespace-pre-line mt-1">
                                    {{ detailPetugas.jam_kerja }}
                                </p>
                            </div>
                        </div>

                        <!-- Informasi Rekening Payroll -->
                        <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-lg bg-emerald-100 text-emerald-800 shrink-0">
                                    <CreditCard class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-[10.5px] font-bold text-emerald-800 uppercase tracking-wider">Rekening Payroll</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="px-2 py-0.5 rounded bg-emerald-200/80 text-emerald-900 font-bold text-xs">
                                            Bank {{ detailPetugas.jenis_bank || 'BNI' }}
                                        </span>
                                        <span class="text-sm font-mono font-extrabold text-slate-900">
                                            {{ detailPetugas.nomor_rekening || '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button
                                v-if="detailPetugas.nomor_rekening"
                                type="button"
                                @click="copyRekening(detailPetugas.nomor_rekening)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white border border-emerald-300 hover:bg-emerald-100/50 text-emerald-800 text-xs font-semibold transition-colors cursor-pointer"
                                title="Salin Nomor Rekening"
                            >
                                <Check v-if="copiedRekening" class="h-3.5 w-3.5 text-emerald-600" />
                                <Copy v-else class="h-3.5 w-3.5 text-emerald-700" />
                                <span>{{ copiedRekening ? "Tersalin!" : "Salin" }}</span>
                            </button>
                        </div>

                        <!-- Grid Data Pribadi -->
                        <div class="grid grid-cols-2 gap-4 pt-1">
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                <p class="text-slate-500 text-[11px] font-medium">Jenis Kelamin</p>
                                <p class="font-bold text-slate-900 text-xs mt-1">
                                    {{ detailPetugas.jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}
                                </p>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                <p class="text-slate-500 text-[11px] font-medium">Tempat & Tanggal Lahir</p>
                                <p class="font-bold text-slate-900 text-xs mt-1">
                                    {{ detailPetugas.tempat_lahir }}, {{ formatDateIndo(detailPetugas.tanggal_lahir) }}
                                    <span v-if="detailPetugas.umur" class="text-slate-500 font-normal">({{ detailPetugas.umur }} tahun)</span>
                                </p>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                <p class="text-slate-500 text-[11px] font-medium">Nomor WhatsApp Resmi</p>
                                <a
                                    :href="`https://wa.me/${detailPetugas.no_telp}`"
                                    target="_blank"
                                    class="font-bold text-emerald-700 hover:underline text-xs mt-1 flex items-center gap-1 font-mono"
                                >
                                    <Phone class="h-3.5 w-3.5 text-emerald-600" />
                                    <span>+{{ detailPetugas.no_telp }}</span>
                                </a>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                <p class="text-slate-500 text-[11px] font-medium">Email Aktif</p>
                                <p class="font-bold text-slate-900 text-xs mt-1 truncate" :title="detailPetugas.email">
                                    {{ detailPetugas.email }}
                                </p>
                            </div>

                            <div class="col-span-2 p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                                <p class="text-slate-500 text-[11px] font-medium">Alamat Lengkap Sesuai KTP</p>
                                <p class="font-semibold text-slate-900 text-xs mt-1 leading-relaxed">
                                    {{ detailPetugas.alamat }}
                                </p>
                            </div>
                        </div>

                        <!-- Rincian Finansial -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                            <p class="font-bold text-slate-900 text-xs border-b border-slate-200 pb-1.5 flex items-center gap-1.5">
                                <DollarSign class="h-4 w-4 text-emerald-600" />
                                <span>Rincian Finansial & Kompensasi</span>
                            </p>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600">Gaji Harian BGN:</span>
                                <span class="font-bold text-slate-900">{{ formatRupiah(detailPetugas.gaji_harian_bgn) }} / hari</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600">Bonus Harian Mitra:</span>
                                <span class="font-bold text-amber-700">{{ formatRupiah(detailPetugas.bonus_harian_mitra) }} / hari</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600">Iuran BPJS Ketenagakerjaan:</span>
                                <span class="font-bold text-slate-800">{{ formatRupiah(detailPetugas.iuran_bpjs_tk) }} / bulan</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-200 text-xs">
                                <span class="font-semibold text-emerald-800">Estimasi Bulanan Standar BGN (x20 Hari):</span>
                                <span class="font-black text-emerald-700 text-xs font-mono">
                                    {{ formatRupiah(((detailPetugas.gaji_harian_bgn || 0) + (detailPetugas.bonus_harian_mitra || 0)) * 20) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
                                <span class="font-semibold text-slate-700">Estimasi Periodik (x14 Hari):</span>
                                <span class="font-bold text-teal-700 text-xs">
                                    {{ formatRupiah(((detailPetugas.gaji_harian_bgn || 0) + (detailPetugas.bonus_harian_mitra || 0)) * 14) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
                                <span class="font-bold text-slate-800">Estimasi Total Bulanan (x28 Hari):</span>
                                <span class="font-black text-slate-900 text-sm">
                                    {{ formatRupiah(((detailPetugas.gaji_harian_bgn || 0) + (detailPetugas.bonus_harian_mitra || 0)) * 28) }}
                                </span>
                            </div>
                        </div>

                        <div v-if="detailPetugas.keterangan && detailPetugas.keterangan !== '-'" class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <p class="text-slate-500 text-[11px] font-medium">Keterangan Tambahan</p>
                            <p class="text-slate-800 font-semibold mt-0.5">
                                {{ detailPetugas.keterangan }}
                            </p>
                        </div>

                        <!-- Footer Action -->
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                            <button
                                type="button"
                                @click="() => { closeDetailModal(); openEditModal(detailPetugas); }"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-xs"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                                <span>Edit Petugas</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ─── Modal Konfirmasi Hapus ─────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="isDeleteModalOpen && deletePetugas"
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-200"
            >
                <!-- Backdrop Gelap Pekat (Dim Seluruh Layar Termasuk Sidebar) -->
                <div
                    class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity cursor-pointer"
                    @click="closeDeleteModal"
                ></div>

                <div class="relative z-10 w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-300 p-6 space-y-4 animate-in zoom-in-95 duration-150">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-full bg-rose-100 text-rose-700 shrink-0">
                            <AlertTriangle class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                Hapus Data Petugas?
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                        <p class="font-bold text-slate-900">{{ deletePetugas.nama }}</p>
                        <p class="text-slate-600 text-[11px] font-mono mt-0.5">NIK: {{ deletePetugas.nik }}</p>
                        <p class="text-slate-700 text-[11px] mt-0.5">Jabatan: {{ deletePetugas.jabatan }}</p>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            :disabled="isDeleting"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeDelete"
                            :disabled="isDeleting"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs disabled:opacity-50 transition-colors cursor-pointer"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span>{{ isDeleting ? "Menghapus..." : "Hapus Permanen" }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
