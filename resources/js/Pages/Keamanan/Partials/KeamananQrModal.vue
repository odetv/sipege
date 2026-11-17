<script setup>
import { ref, computed } from 'vue';
import jsPDF from 'jspdf';
import { X, QrCode, Copy, Check, Download, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    url: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: 'QR Code Form Tim Keamanan',
    },
    subtitle: {
        type: String,
        default: 'Scan pakai kamera HP untuk langsung input',
    },
    unitSppg: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close']);

const isCopied = ref(false);
const isDownloading = ref(false);

function copyLink() {
    if (!props.url) return;
    navigator.clipboard.writeText(props.url).then(() => {
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 3000);
    });
}

const qrCodeUrl = computed(() => {
    if (!props.url) return '';
    return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(props.url)}`;
});

async function getQrImageBase64(url) {
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=${encodeURIComponent(url)}`;
    try {
        const dataUrl = await new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = 600;
                canvas.height = 600;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, 600, 600);
                ctx.drawImage(img, 0, 0, 600, 600);
                resolve(canvas.toDataURL('image/png'));
            };
            img.onerror = reject;
            img.src = qrUrl;
        });
        return dataUrl;
    } catch {
        try {
            const res = await fetch(qrUrl);
            const blob = await res.blob();
            return await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        } catch (e) {
            console.error('Gagal mengambil gambar QR:', e);
            return null;
        }
    }
}

async function downloadQrPdf() {
    if (!props.url || isDownloading.value) return;
    isDownloading.value = true;

    try {
        const qrBase64 = await getQrImageBase64(props.url);

        const doc = new jsPDF({
            orientation: 'portrait',
            unit: 'mm',
            format: 'a4',
        });

        const pageWidth = 210;
        const pageHeight = 297;
        const unitName = (props.unitSppg?.nama || 'SPPG (Satuan Pelayanan Program Gizi)').toUpperCase();

        // 1. Frame / Border Luar
        doc.setDrawColor(203, 213, 225); // slate-300
        doc.setLineWidth(0.8);
        doc.roundedRect(12, 12, pageWidth - 24, pageHeight - 24, 4, 4);

        // 2. Header Box Atas
        doc.setFillColor(15, 23, 42); // slate-900
        doc.roundedRect(12, 12, pageWidth - 24, 26, 4, 4, 'F');
        doc.rect(12, 30, pageWidth - 24, 8, 'F'); // menutupi rounded bawah header

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(255, 255, 255);
        doc.text(unitName, pageWidth / 2, 23, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(148, 163, 184); // slate-400
        doc.text('SISTEM INFORMASI & MONITORING KEAMANAN SPPG', pageWidth / 2, 31, { align: 'center' });

        // Garis batas emerald
        doc.setDrawColor(16, 185, 129); // emerald-500
        doc.setLineWidth(1.5);
        doc.line(12, 38, pageWidth - 12, 38);

        // 3. Judul Dokumen & Subtitle
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(18);
        doc.setTextColor(15, 23, 42);
        const titleLines = doc.splitTextToSize(props.title || 'QR Code Keamanan', 170);
        doc.text(titleLines, pageWidth / 2, 54, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(100, 116, 139);
        const subtitleLines = doc.splitTextToSize(props.subtitle || 'Arahkan kamera smartphone ke QR Code ini untuk membuka tautan.', 160);
        doc.text(subtitleLines, pageWidth / 2, 63, { align: 'center' });

        // 4. Box QR Code Besar
        const qrBoxSize = 120;
        const qrBoxX = (pageWidth - qrBoxSize) / 2;
        const qrBoxY = 74;

        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(226, 232, 240); // slate-200
        doc.setLineWidth(0.8);
        doc.roundedRect(qrBoxX, qrBoxY, qrBoxSize, qrBoxSize, 6, 6, 'FD');

        if (qrBase64) {
            const qrImgSize = 104;
            const qrImgX = (pageWidth - qrImgSize) / 2;
            const qrImgY = qrBoxY + 8;
            doc.addImage(qrBase64, 'PNG', qrImgX, qrImgY, qrImgSize, qrImgSize);
        }

        // 5. Box Petunjuk Pemindaian
        const infoBoxY = 202;
        doc.setFillColor(240, 253, 244); // emerald-50
        doc.setDrawColor(187, 247, 208); // emerald-200
        doc.setLineWidth(0.5);
        doc.roundedRect(25, infoBoxY, pageWidth - 50, 16, 3, 3, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(22, 101, 52); // emerald-800
        doc.text('PETUNJUK SCAN:', pageWidth / 2, infoBoxY + 6, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(21, 128, 61); // emerald-700
        doc.text('Buka Kamera HP / Aplikasi Pemindai QR Code, lalu arahkan ke gambar di atas.', pageWidth / 2, infoBoxY + 11.5, { align: 'center' });

        // 6. Box Link URL Manual
        const urlBoxY = 224;
        doc.setFillColor(248, 250, 252); // slate-50
        doc.setDrawColor(226, 232, 240); // slate-200
        doc.setLineWidth(0.5);
        doc.roundedRect(25, urlBoxY, pageWidth - 50, 24, 3, 3, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text('LINK AKSES LANGSUNG (JIKA TIDAK BISA PINDAI QR):', 30, urlBoxY + 7);

        doc.setFont('courier', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(30, 41, 59);
        const urlLines = doc.splitTextToSize(props.url || '-', 150);
        doc.text(urlLines, 30, urlBoxY + 14);

        // 7. Footer Dokumen
        const now = new Date();
        const dateStr = now.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
        const timeStr = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });

        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.4);
        doc.line(25, 262, pageWidth - 25, 262);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(148, 163, 184);
        doc.text(`Dokumen Resmi Keamanan SPPG • Dicetak otomatis pada ${dateStr} pukul ${timeStr} WITA`, pageWidth / 2, 269, { align: 'center' });

        // Simpan File PDF
        const safeTitle = (props.title || 'QR_Code').replace(/[^a-zA-Z0-9_-]/g, '_');
        doc.save(`${safeTitle}.pdf`);
    } catch (err) {
        console.error('Gagal membuat PDF QR:', err);
    } finally {
        isDownloading.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/75 backdrop-blur-xs overflow-y-auto animate-in fade-in duration-150"
            style="z-index: 99999;"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden my-auto flex flex-col"
            >
                <!-- Modal Header -->
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                            <QrCode class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">
                                {{ title }}
                            </h3>
                            <p class="text-[11px] text-slate-300">
                                {{ subtitle }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="h-8 w-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- QR Display Area -->
                <div class="p-6 text-center space-y-4">
                    <div>
                        <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            {{ unitSppg?.nama || 'SPPG' }}
                        </span>
                        <h4 class="text-base font-black text-slate-900 mt-2">
                            {{ title }}
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5 max-w-xs mx-auto">
                            {{ subtitle }}
                        </p>
                    </div>

                    <!-- QR Image -->
                    <div class="inline-block p-4 bg-white rounded-2xl border-2 border-slate-200 shadow-sm mx-auto">
                        <img
                            :src="qrCodeUrl"
                            alt="QR Code"
                            class="w-48 h-48 sm:w-56 sm:h-56 mx-auto object-contain"
                        />
                    </div>

                    <!-- URL Box -->
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-left space-y-1.5">
                        <span class="text-[10.5px] font-semibold text-slate-500 block">Link Akses:</span>
                        <div class="flex items-center gap-1.5">
                            <input
                                type="text"
                                readonly
                                :value="url"
                                class="w-full text-xs font-mono font-bold text-slate-800 bg-white border border-slate-200 rounded-lg p-1.5"
                            />
                            <button
                                type="button"
                                @click="copyLink"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 cursor-pointer shrink-0 transition-colors"
                                title="Salin link ke clipboard"
                            >
                                <Check v-if="isCopied" class="h-3.5 w-3.5" />
                                <Copy v-else class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
                    <button
                        type="button"
                        @click="downloadQrPdf"
                        :disabled="isDownloading"
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer transition-colors flex items-center gap-1.5 shadow-2xs disabled:opacity-50"
                        title="Download dokumen QR Code dalam format PDF"
                    >
                        <Loader2 v-if="isDownloading" class="h-3.5 w-3.5 animate-spin" />
                        <Download v-else class="h-3.5 w-3.5" />
                        <span>{{ isDownloading ? 'Mengunduh PDF...' : 'Download QR' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold cursor-pointer transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
