<script setup>
defineProps({
    appName: {
        type: String,
        default: "SIPEGE - Sistem Pengelolaan SPPG",
    },
    organization: {
        type: String,
        default: "Badan Gizi Nasional - Satuan Pelayanan Pemenuhan Gizi",
    },
});
</script>

<template>
    <footer
        class="border-t border-slate-200/80 bg-white py-3.5 px-4 sm:px-6 lg:px-8 text-xs text-slate-500 shrink-0 select-none"
    >
        <div
            class="flex flex-col sm:flex-row items-center justify-between gap-2"
        >
            <p>
                &copy; {{ new Date().getFullYear() }} {{ appName }}.
            </p>
            <p class="text-slate-400 text-[11px] sm:text-xs">
                {{ organization }}
            </p>
        </div>
    </footer>
</template>
