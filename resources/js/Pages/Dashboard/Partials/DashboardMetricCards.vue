<script setup>
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Badge from "@/Components/ui/Badge.vue";
import { FileBadge, MapPin, Activity, Shield } from "lucide-vue-next";
import { formatTanggalIndo } from "@/lib/utils";

defineProps({
    user: {
        type: Object,
        required: true,
    },
    unitSppg: {
        type: Object,
        default: null,
    },
    statusVariant: {
        type: String,
        default: "secondary",
    },
});
</script>

<template>
    <!-- Metric Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Metric 1: ID SPPG -->
        <Card
            className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
        >
            <CardContent
                className="p-5 h-full flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500">ID SPPG</p>
                    <h3
                        class="text-xl font-bold font-mono text-primary mt-1 truncate"
                    >
                        {{ unitSppg?.id_sppg || "-" }}
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                        Kode: {{ unitSppg?.kode_sppg || "-" }}
                    </p>
                </div>
                <div
                    class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
                >
                    <FileBadge class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- Metric 2: Lokasi SPPG -->
        <Card
            className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
        >
            <CardContent
                className="p-5 h-full flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500">
                        Lokasi SPPG
                    </p>
                    <h3
                        class="text-sm font-bold text-slate-900 mt-1 line-clamp-2 leading-tight"
                    >
                        {{
                            [
                                unitSppg?.desa_kelurahan,
                                unitSppg?.kecamatan,
                                unitSppg?.kabupaten,
                                unitSppg?.provinsi,
                                unitSppg?.kode_pos,
                            ]
                                .filter(Boolean)
                                .join(", ") || "-"
                        }}
                    </h3>
                </div>
                <div
                    class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"
                >
                    <MapPin class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- Metric 3: Status Operasional -->
        <Card
            className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
        >
            <CardContent
                className="p-5 h-full flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500">
                        Status Operasional
                    </p>
                    <div class="mt-1">
                        <Badge
                            :variant="statusVariant"
                            className="text-xs font-bold"
                        >
                            {{ unitSppg?.status || "Belum Operasional" }}
                        </Badge>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1 truncate">
                        {{
                            unitSppg?.tanggal_operasional
                                ? `Mulai: ${formatTanggalIndo(
                                      unitSppg.tanggal_operasional,
                                  )}`
                                : "-"
                        }}
                    </p>
                </div>
                <div
                    class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"
                >
                    <Activity class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>

        <!-- Metric 4: Kepala SPPG -->
        <Card
            className="bg-white border-slate-200/80 h-full flex flex-col justify-center shadow-xs"
        >
            <CardContent
                className="p-5 h-full flex items-center justify-between gap-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-500">
                        Kepala SPPG
                    </p>
                    <h3 class="text-sm font-bold text-slate-900 mt-1 truncate">
                        {{ user.nama_lengkap }}
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 leading-tight">
                        NIK: {{ user.nik || "-" }}
                        <span v-if="user.nip" class="block"
                            >NIP: {{ user.nip }}</span
                        >
                    </p>
                </div>
                <div
                    class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"
                >
                    <Shield class="h-5 w-5" />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
