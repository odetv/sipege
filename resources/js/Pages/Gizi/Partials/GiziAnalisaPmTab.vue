<script setup>
import { computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    School,
    UtensilsCrossed,
    Users,
    HeartPulse,
    PieChart,
    AlertCircle,
    UserCheck,
    CheckCircle2,
} from "lucide-vue-next";

const props = defineProps({
    kelompokList: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_kelompok: 0,
            total_penerima: 0,
            total_porsi_kecil: 0,
            total_porsi_besar: 0,
            total_sekolah: 0,
            total_posyandu: 0,
            kategori_breakdown: {},
        }),
    },
});

const totalPK = computed(() => {
    return (props.kelompokList || []).reduce(
        (acc, k) => acc + (Number(k.total_porsi_kecil) || 0),
        0,
    );
});

const totalPB = computed(() => {
    return (props.kelompokList || []).reduce(
        (acc, k) => acc + (Number(k.total_porsi_besar) || 0),
        0,
    );
});

const totalPM = computed(() => totalPK.value + totalPB.value);

// Rekapitulasi Alergi Master PM (Tab 2: Analisa PM)
const rekapAlergiMasterPm = computed(() => {
    const summary = {};
    (props.kelompokList || []).forEach((k) => {
        if (
            Array.isArray(k.keterangan_alergi) &&
            k.keterangan_alergi.length > 0
        ) {
            k.keterangan_alergi.forEach((item) => {
                const jenis =
                    typeof item === "string" ? item : item.jenis_alergi;
                if (!jenis) return;
                const cleanJenis = jenis.trim();
                if (!summary[cleanJenis]) {
                    summary[cleanJenis] = {
                        jenis_alergi: cleanJenis,
                        porsi_kecil: 0,
                        porsi_besar: 0,
                        total: 0,
                        kelompok_list: [],
                    };
                }
                const pk = Number(item.porsi_kecil) || 0;
                const pb = Number(item.porsi_besar) || 0;
                const totalItem = pk + pb;
                summary[cleanJenis].porsi_kecil += pk;
                summary[cleanJenis].porsi_besar += pb;
                summary[cleanJenis].total += totalItem;

                if (totalItem > 0) {
                    const existingKel = summary[cleanJenis].kelompok_list.find(
                        (x) => x.id === k.id,
                    );
                    if (existingKel) {
                        existingKel.porsi_kecil += pk;
                        existingKel.porsi_besar += pb;
                        existingKel.total += totalItem;
                    } else {
                        summary[cleanJenis].kelompok_list.push({
                            id: k.id,
                            nama_kelompok: k.nama_kelompok,
                            kategori: k.kategori,
                            desa_kelurahan: k.desa_kelurahan,
                            porsi_kecil: pk,
                            porsi_besar: pb,
                            total: totalItem,
                        });
                    }
                }
            });
        } else if (
            (Number(k.alergi_porsi_kecil) || 0) +
                (Number(k.alergi_porsi_besar) || 0) >
            0
        ) {
            const cleanJenis = "Alergi Khusus";
            if (!summary[cleanJenis]) {
                summary[cleanJenis] = {
                    jenis_alergi: cleanJenis,
                    porsi_kecil: 0,
                    porsi_besar: 0,
                    total: 0,
                    kelompok_list: [],
                };
            }
            const pk = Number(k.alergi_porsi_kecil) || 0;
            const pb = Number(k.alergi_porsi_besar) || 0;
            summary[cleanJenis].porsi_kecil += pk;
            summary[cleanJenis].porsi_besar += pb;
            summary[cleanJenis].total += pk + pb;
            summary[cleanJenis].kelompok_list.push({
                id: k.id,
                nama_kelompok: k.nama_kelompok,
                kategori: k.kategori,
                desa_kelurahan: k.desa_kelurahan,
                porsi_kecil: pk,
                porsi_besar: pb,
                total: pk + pb,
            });
        }
    });
    return Object.values(summary).filter((item) => item.total > 0);
});

const totalMasterPmSiswaAlergi = computed(() => {
    return rekapAlergiMasterPm.value.reduce((s, a) => s + a.total, 0);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <Card className="bg-white border-slate-200 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100"
                    >
                        <School class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-[10.5px] font-bold text-slate-500 uppercase"
                        >
                            Kelompok Sasaran
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-black text-slate-900 mt-0.5"
                        >
                            {{ stats.total_kelompok }}
                            <span class="text-xs font-medium text-slate-500"
                                >({{ stats.total_sekolah || 0 }}
                                Sekolah /
                                {{ stats.total_posyandu || 0 }}
                                Posyandu)</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100"
                    >
                        <UtensilsCrossed class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-[10.5px] font-bold text-slate-500 uppercase"
                        >
                            Porsi Kecil (PK)
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-black text-amber-800 mt-0.5"
                        >
                            {{ totalPK.toLocaleString("id-ID") }}
                            <span class="text-xs font-medium text-slate-500"
                                >Porsi</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100"
                    >
                        <Layers class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-[10.5px] font-bold text-slate-500 uppercase"
                        >
                            Porsi Besar (PB)
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-black text-indigo-800 mt-0.5"
                        >
                            {{ totalPB.toLocaleString("id-ID") }}
                            <span class="text-xs font-medium text-slate-500"
                                >Porsi</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>

            <Card className="bg-white border-slate-200 shadow-xs">
                <CardContent className="p-4 flex items-center gap-3">
                    <div
                        class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                    <div>
                        <p
                            class="text-[10.5px] font-bold text-slate-500 uppercase"
                        >
                            Total PM Harian
                        </p>
                        <h3
                            class="text-lg sm:text-xl font-black text-emerald-800 mt-0.5"
                        >
                            {{ totalPM.toLocaleString("id-ID") }}
                            <span class="text-xs font-medium text-slate-500"
                                >Porsi</span
                            >
                        </h3>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Card Sebaran & Pemetaan Alergi Penerima Manfaat (Hanya Tampil Jika Ada Siswa Alergi) -->
        <Card
            v-if="rekapAlergiMasterPm.length > 0"
            className="bg-white border-rose-200/90 shadow-xs overflow-hidden"
        >
            <CardHeader
                className="p-4 sm:p-5 border-b border-rose-100 bg-rose-50/50"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <CardTitle
                                class="text-base sm:text-lg font-black text-rose-950 flex items-center gap-2"
                            >
                                <HeartPulse class="h-5 w-5 text-rose-600" />
                                <span
                                    >Sebaran & Pemetaan Siswa Alergi
                                    Terdaftar</span
                                >
                            </CardTitle>
                            <Badge
                                variant="outline"
                                class="bg-rose-100 text-rose-800 border-rose-300 font-extrabold text-xs"
                            >
                                Total
                                {{ totalMasterPmSiswaAlergi }} Siswa Alergi
                            </Badge>
                        </div>
                        <CardDescription
                            class="text-xs sm:text-sm text-rose-800/80 mt-0.5"
                        >
                            Daftar jenis alergi dan rincian kelompok sasaran
                            terdampak untuk perencanaan menu substitusi MBG.
                        </CardDescription>
                    </div>
                    <span
                        class="text-xs font-bold text-rose-900 px-3 py-1 bg-white rounded-xl border border-rose-200 shadow-2xs self-start sm:self-auto"
                    >
                        {{ rekapAlergiMasterPm.length }} Jenis Alergen
                    </span>
                </div>
            </CardHeader>
            <CardContent className="p-4 sm:p-5">
                <div class="flex flex-wrap gap-3.5">
                    <div
                        v-for="(al, alIdx) in rekapAlergiMasterPm"
                        :key="alIdx"
                        class="flex-1 min-w-[280px] sm:min-w-[320px] rounded-xl border border-slate-200/90 bg-slate-50/40 p-3.5 space-y-3 flex flex-col justify-start hover:border-rose-300 transition-colors shadow-2xs"
                    >
                        <!-- Header Jenis Alergen -->
                        <div
                            class="flex items-start justify-between gap-2 border-b border-slate-200/70 pb-2.5"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 rounded-full bg-rose-600 shrink-0"
                                ></span>
                                <h5 class="text-xs font-black text-slate-900">
                                    {{ al.jenis_alergi }}
                                </h5>
                            </div>
                            <Badge
                                class="bg-rose-600 text-white font-extrabold text-[10.5px] px-2 py-0.5 shadow-2xs"
                            >
                                {{ al.total }} Siswa
                            </Badge>
                        </div>

                        <!-- Rincian Porsi (PK vs PB) -->
                        <div
                            class="flex items-center justify-between text-xs bg-white p-2 rounded-lg border border-slate-200/70"
                        >
                            <span class="text-[11px] text-slate-500 font-medium"
                                >Distribusi Porsi:</span
                            >
                            <div
                                class="flex items-center gap-2 font-bold text-[11px]"
                            >
                                <span
                                    class="text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200"
                                >
                                    PK: {{ al.porsi_kecil }}
                                </span>
                                <span
                                    class="text-indigo-800 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200"
                                >
                                    PB: {{ al.porsi_besar }}
                                </span>
                            </div>
                        </div>

                        <!-- Kelompok Sasaran Terdampak (Rata Atas) -->
                        <div
                            class="space-y-1.5 flex-1 flex flex-col justify-start"
                        >
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                Kelompok Sasaran ({{ al.kelompok_list.length }}
                                Lokasi):
                            </p>
                            <div
                                class="space-y-1 min-h-[100px] max-h-[140px] overflow-y-auto pr-1 flex-1"
                            >
                                <div
                                    v-for="kel in al.kelompok_list"
                                    :key="kel.id"
                                    class="p-1.5 rounded-lg bg-white border border-slate-100 flex items-center justify-between text-xs gap-2"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="font-bold text-slate-900 truncate text-[11.5px]"
                                        >
                                            {{ kel.nama_kelompok }}
                                        </p>
                                        <p class="text-[10px] text-slate-400">
                                            {{ kel.desa_kelurahan }}
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span
                                            class="font-black text-rose-700 text-xs"
                                        >
                                            {{ kel.total }} Siswa
                                        </span>
                                        <span
                                            class="block text-[9.5px] text-slate-400 font-normal"
                                        >
                                            (PK: {{ kel.porsi_kecil }}, PB:
                                            {{ kel.porsi_besar }})
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Table Detail Rincian per Kelompok & Kategori -->
        <Card className="bg-white border-slate-200 shadow-xs overflow-hidden">
            <CardHeader
                className="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50"
            >
                <CardTitle
                    className="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2"
                >
                    <Users class="h-5 w-5 text-primary" />
                    <span
                        >Tabel Detail Jumlah Penerima Manfaat (PM) per Kategori
                        Sasaran</span
                    >
                </CardTitle>
                <CardDescription class="text-xs sm:text-sm">
                    Dasar kuota produksi harian MBG SPPG terklasifikasi
                    berdasarkan jenjang pendidikan dan kategori porsi.
                </CardDescription>
            </CardHeader>
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[700px] text-left text-xs border-collapse"
                >
                    <thead>
                        <tr
                            class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider select-none"
                        >
                            <th class="py-3.5 px-4">Nama Kelompok</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4 text-center">Laki-Laki</th>
                            <th class="py-3.5 px-4 text-center">Perempuan</th>
                            <th class="py-3.5 px-4 text-center">Porsi Kecil</th>
                            <th class="py-3.5 px-4 text-center">Porsi Besar</th>
                            <th class="py-3.5 px-4 text-center">Total PM</th>
                            <th class="py-3.5 px-4">Status Alergi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        <tr
                            v-for="k in kelompokList"
                            :key="k.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="p-3.5 font-bold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <span>{{ k.nama_kelompok }}</span>
                                </div>
                                <p
                                    class="text-[10.5px] text-slate-500 font-normal"
                                >
                                    {{ k.desa_kelurahan }},
                                    {{ k.kecamatan }}
                                </p>
                            </td>
                            <td class="p-3.5">
                                <Badge
                                    variant="outline"
                                    className="font-bold text-xs bg-slate-50"
                                >
                                    {{ k.kategori }}
                                </Badge>
                            </td>
                            <td
                                class="p-3.5 text-center font-semibold text-blue-700"
                            >
                                {{ k.total_laki_laki || 0 }}
                            </td>
                            <td
                                class="p-3.5 text-center font-semibold text-rose-700"
                            >
                                {{ k.total_perempuan || 0 }}
                            </td>
                            <td
                                class="p-3.5 text-center font-bold text-amber-800 bg-amber-50/30"
                            >
                                {{ k.total_porsi_kecil || 0 }}
                            </td>
                            <td
                                class="p-3.5 text-center font-bold text-indigo-800 bg-indigo-50/30"
                            >
                                {{ k.total_porsi_besar || 0 }}
                            </td>
                            <td
                                class="p-3.5 text-center font-black text-slate-900 text-sm"
                            >
                                {{ k.total_penerima || 0 }}
                            </td>
                            <td class="p-3.5">
                                <div
                                    v-if="
                                        k.alergi_porsi_kecil > 0 ||
                                        k.alergi_porsi_besar > 0
                                    "
                                    class="space-y-1.5"
                                >
                                    <span
                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded shadow-2xs"
                                    >
                                        <HeartPulse
                                            class="h-3 w-3 text-rose-600"
                                        />
                                        <span
                                            >{{
                                                (k.alergi_porsi_kecil || 0) +
                                                (k.alergi_porsi_besar || 0)
                                            }}
                                            ({{ k.alergi_porsi_kecil || 0 }}
                                            PK /
                                            {{ k.alergi_porsi_besar || 0 }}
                                            PB)</span
                                        >
                                    </span>
                                    <div
                                        v-if="
                                            k.keterangan_alergi &&
                                            k.keterangan_alergi.length > 0
                                        "
                                        class="flex items-center gap-1 flex-wrap"
                                    >
                                        <span
                                            v-for="(
                                                al, idx
                                            ) in k.keterangan_alergi"
                                            :key="idx"
                                            class="text-[10px] font-semibold text-slate-700 bg-slate-100 border border-slate-200/60 px-1.5 py-0.5 rounded"
                                        >
                                            {{
                                                typeof al === "string"
                                                    ? al
                                                    : `${al.jenis_alergi}: ${(al.porsi_kecil || 0) + (al.porsi_besar || 0)}`
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"
                                >
                                    <CheckCircle2 class="h-3 w-3" />
                                    Standar Normal (0 Alergi)
                                </span>
                            </td>
                        </tr>
                        <tr v-if="kelompokList.length === 0">
                            <td
                                colspan="8"
                                class="p-8 text-center text-slate-400 font-semibold"
                            >
                                Belum ada data kelompok penerima manfaat yang
                                terdaftar.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot
                        class="bg-slate-100/80 font-black text-slate-900 border-t-2 border-slate-300 text-xs"
                    >
                        <tr>
                            <td
                                colspan="4"
                                class="p-3.5 uppercase tracking-wider text-slate-700"
                            >
                                Total Produksi Porsi SPPG:
                            </td>
                            <td class="p-3.5 text-center text-amber-900">
                                {{ totalPK }}
                            </td>
                            <td class="p-3.5 text-center text-indigo-900">
                                {{ totalPB }}
                            </td>
                            <td
                                class="p-3.5 text-center text-emerald-950 text-sm"
                            >
                                {{ totalPM }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Card>
    </div>
</template>
