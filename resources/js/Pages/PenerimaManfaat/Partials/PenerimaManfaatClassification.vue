<script setup>
defineProps({
    categoryGroups: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <!-- ================= 10 KLASIFIKASI PENERIMA MANFAAT (TERBUNGKUS PER KATEGORI) ================= -->
    <div class="space-y-3.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 px-0.5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <span>Klasifikasi Penerima Manfaat</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/60">
                    10 Jenis Terklasifikasi
                </span>
            </h3>
            <span class="text-[11px] text-slate-400 font-medium">
                Dikelompokkan Berdasarkan Jenjang & Kategori Sasaran
            </span>
        </div>

        <!-- Grid Group Kategori -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
            <div
                v-for="group in categoryGroups"
                :key="group.key"
                :class="[
                    'p-3.5 sm:p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex flex-col justify-between transition-all hover:shadow-xs hover:border-slate-300',
                    group.spanClass,
                ]"
            >
                <!-- Header Wrapper Kategori -->
                <div class="flex items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="h-2 w-2 rounded-full bg-blue-600 shrink-0"></span>
                        <h4 class="text-xs font-bold text-slate-900 truncate">
                            {{ group.title }}
                        </h4>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200/70">
                            {{ group.total.toLocaleString("id-ID") }} Porsi
                        </span>
                    </div>
                </div>

                <!-- Sub-cards di dalam kategori -->
                <div :class="['grid gap-2.5 flex-1', group.gridClass]">
                    <div
                        v-for="(card, cIdx) in group.cards"
                        :key="cIdx"
                        class="p-3 rounded-xl bg-slate-50/80 border border-slate-200/70 hover:bg-white hover:border-blue-300 hover:shadow-2xs transition-all flex flex-col justify-between gap-2.5 group"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold text-slate-700 truncate" :title="card.displayTitle || card.label">
                                    {{ card.displayTitle || card.label }}
                                </p>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-lg sm:text-xl font-black text-slate-900 group-hover:text-blue-700 transition-colors">
                                        {{ card.total.toLocaleString("id-ID") }}
                                    </span>
                                    <span class="text-[10px] font-medium text-slate-400">Porsi</span>
                                </div>
                            </div>
                            <div
                                class="h-7 w-7 rounded-lg bg-white text-blue-600 border border-slate-200/80 flex items-center justify-center shrink-0 shadow-2xs group-hover:border-blue-200 group-hover:bg-blue-50 transition-colors"
                            >
                                <component :is="card.icon" class="h-3.5 w-3.5" />
                            </div>
                        </div>

                        <!-- Modern Elegant PK & PB Badges -->
                        <div class="pt-2 border-t border-slate-200/60 flex items-center gap-1.5 w-full">
                            <!-- Badge PK -->
                            <div
                                class="flex items-center justify-between gap-1 flex-1 min-w-0 px-2 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-900 shadow-2xs"
                                title="Porsi Kecil"
                            >
                                <span class="inline-flex items-center gap-1 font-bold text-amber-700 text-[10px] shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                    PK
                                </span>
                                <span class="font-extrabold text-amber-950 font-mono text-[11px] truncate">
                                    {{ card.pk.toLocaleString("id-ID") }}
                                </span>
                            </div>

                            <!-- Badge PB -->
                            <div
                                class="flex items-center justify-between gap-1 flex-1 min-w-0 px-2 py-1 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-900 shadow-2xs"
                                title="Porsi Besar"
                            >
                                <span class="inline-flex items-center gap-1 font-bold text-blue-700 text-[10px] shrink-0">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                    PB
                                </span>
                                <span class="font-extrabold text-blue-950 font-mono text-[11px] truncate">
                                    {{ card.pb.toLocaleString("id-ID") }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
