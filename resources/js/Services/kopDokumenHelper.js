/**
 * Service & Helper Terpusat untuk Kop Dokumen Kedinasan SPPG (Universal Letterhead)
 * Digunakan oleh seluruh modul aplikasi (Aset Digital, Work Order, PO, Laporan, dsb)
 */

export const STORAGE_KEY_KOP = 'sipege_active_kop_dokumen';

/**
 * Konfigurasi Default Kop Dokumen Resmi SPPG
 * Sesuai format standar:
 * - Logo Kiri: BGN (Badan Gizi Nasional)
 * - Baris 1: SPPG BULELENG SUKASADA TEGALLINGGAH
 * - Baris 2: YAYASAN PESANTREN MIFTAHUL ULUM
 * - Baris 3: Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali
 * - Baris 4: E-mail: sppgsukasadategallinggah@gmail.com
 * - Logo Kanan: Logo Yayasan Pesantren Miftahul Ulum
 * - Garis Ganda Kedinasan
 */
export function getDefaultKopConfig(unitSppg = null) {
    return {
        nama_instansi_1: 'SPPG BULELENG SUKASADA TEGALLINGGAH',
        nama_instansi_2: 'YAYASAN PESANTREN MIFTAHUL ULUM',
        nama_unit: unitSppg?.nama || 'SPPG Buleleng Sukasada Tegallinggah',
        kode_unit: unitSppg?.kode_sppg || '51.08.05.2013.03',
        id_sppg: unitSppg?.id_sppg || 'QQCV0LUG',
        alamat_lengkap: unitSppg?.alamat_lengkap || 'Jl. Raya Angling Darma, Desa Tegallinggah, Kec. Sukasada, Kab. Buleleng, Bali',
        desa_kelurahan: unitSppg?.desa_kelurahan || 'Tegallinggah',
        kecamatan: unitSppg?.kecamatan || 'Sukasada',
        kabupaten: unitSppg?.kabupaten || 'Buleleng',
        provinsi: unitSppg?.provinsi || 'Bali',
        kode_pos: '',
        telepon: '',
        whatsapp: '',
        email: 'sppgsukasadategallinggah@gmail.com',
        website: '',
        logo_kiri_url: '/images/logo/BGN_LOGO_MAIN.png',
        logo_kanan_url: '/images/logo/Logo_Yayasan.png',
        layout_logo: 'dual', // 'dual' | 'kiri' | 'tengah'
        gaya_garis: 'ganda_kedinasan', // 'ganda_kedinasan' | 'tunggal_tebal' | 'modern_aksen' | 'minimalis'
        template_style: 'klasik_formal', // 'klasik_formal' (Times New Roman) | 'kedinasan_resmi' (Arial) | 'modern_sppg'
        is_aktif: true,
    };
}

/**
 * Mengambil Konfigurasi Kop Dokumen yang Aktif
 * Memprioritaskan data dari window.__KOP_CONFIG__, props server, atau localStorage
 */
export function getActiveKopConfig(initialServerConfig = null) {
    if (initialServerConfig && typeof initialServerConfig === 'object' && initialServerConfig.nama_instansi_1) {
        saveKopConfigToLocal(initialServerConfig);
        return initialServerConfig;
    }

    if (typeof window !== 'undefined') {
        if (window.__KOP_CONFIG__) {
            return window.__KOP_CONFIG__;
        }
        try {
            const raw = localStorage.getItem(STORAGE_KEY_KOP);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && (parsed.nama_instansi_1 || parsed.nama_unit)) {
                    window.__KOP_CONFIG__ = parsed;
                    return parsed;
                }
            }
        } catch (e) {
            console.warn('Gagal membaca kop config dari localStorage:', e);
        }
    }

    return getDefaultKopConfig();
}

/**
 * Simpan konfigurasi kop ke localStorage untuk akses cepat lintas halaman
 */
export function saveKopConfigToLocal(config) {
    if (typeof window !== 'undefined') {
        window.__KOP_CONFIG__ = config;
        try {
            localStorage.setItem(STORAGE_KEY_KOP, JSON.stringify(config));
        } catch (e) {
            console.warn('Gagal menyimpan kop config ke localStorage:', e);
        }
    }
}

/**
 * Generate CSS Style untuk Garis Pembatas Kop Dokumen
 */
export function getKopBorderStyle(gayaGaris = 'ganda_kedinasan', isForWord = false) {
    switch (gayaGaris) {
        case 'ganda_kedinasan':
            return 'border-top: 3px solid #000; border-bottom: 1px solid #000; height: 2px; margin-top: 6px; margin-bottom: 14px;';
        case 'tunggal_tebal':
            return 'border-bottom: 2.5px solid #0f172a; margin-top: 6px; margin-bottom: 14px;';
        case 'modern_aksen':
            return 'border-bottom: 3.5px solid #0f766e; margin-top: 6px; margin-bottom: 14px;';
        case 'minimalis':
            return 'border-bottom: 1px solid #94a3b8; margin-top: 6px; margin-bottom: 14px;';
        default:
            return 'border-top: 3px solid #000; border-bottom: 1px solid #000; height: 2px; margin-top: 6px; margin-bottom: 14px;';
    }
}

/**
 * Menghasilkan HTML Lengkap Kop Dokumen Resmi
 * Mendukung mode 'print', 'word', 'preview'
 * DENGAN JAMINAN TULISAN TIDAK TER-WRAP (SINGLE LINE CLEAN)
 */
export function generateKopHtml(customConfig = null, options = {}) {
    const config = customConfig || getActiveKopConfig();
    const {
        mode = 'print', // 'print' | 'word' | 'preview'
        isForWord = false,
        showBorder = true,
        logoKiriSize = 75,
        logoKananSize = 75,
        compact = false,
    } = options;

    const instansi1 = (config.nama_instansi_1 || 'SPPG BULELENG SUKASADA TEGALLINGGAH').toUpperCase();
    const instansi2 = (config.nama_instansi_2 || 'YAYASAN PESANTREN MIFTAHUL ULUM').toUpperCase();
    const namaUnit = (config.nama_unit || '').toUpperCase();
    const alamat = config.alamat_lengkap || '';

    // Cek apakah nama unit perlu ditampilkan sebagai baris tambahan
    const showUnitLine = Boolean(
        namaUnit &&
        namaUnit !== instansi1 &&
        namaUnit !== instansi2 &&
        !instansi1.includes(namaUnit)
    );

    // Format kontak baris 4 (E-mail dan kontak lain jika diisi)
    const contactParts = [];
    if (config.telepon) contactParts.push(`Telp: ${config.telepon}`);
    if (config.whatsapp) contactParts.push(`WA: ${config.whatsapp}`);
    if (config.email) contactParts.push(`E-mail: ${config.email}`);
    if (config.website) contactParts.push(`Web: ${config.website}`);

    const kontakStr = contactParts.length > 0 ? contactParts.join(' | ') : (config.email ? `E-mail: ${config.email}` : '');

    const logoKiriUrl = config.logo_kiri_url || '/images/logo/BGN_LOGO_MAIN.png';
    const logoKananUrl = config.logo_kanan_url || '/images/logo/Logo_Yayasan.png';
    const layout = config.layout_logo || 'dual';
    const hasRightLogo = layout === 'dual' && Boolean(logoKananUrl);

    // Tipografi berdasarkan template_style
    const isSans = config.template_style === 'modern_sppg';
    const fontFamily = isSans ? "'Segoe UI', Arial, sans-serif" : "'Times New Roman', Times, serif";

    const titleSize1 = compact ? '12pt' : '14pt';
    const titleSize2 = compact ? '11pt' : '13pt';
    const unitSize = compact ? '10pt' : '11.5pt';
    const addrSize = compact ? '8.5pt' : '10pt';
    const contactSize = compact ? '8.5pt' : '10pt';

    // Double border HTML kompatibel Word & Browser
    let borderHtml = '';
    if (showBorder) {
        if (isForWord || mode === 'word') {
            borderHtml = `
            <table style="width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; border: none;">
                <tr><td style="border-bottom: 3pt solid #000000; padding: 0; height: 1px; font-size: 1pt; line-height: 1pt;">&nbsp;</td></tr>
                <tr><td style="border-bottom: 0.75pt solid #000000; padding: 0; height: 2px; font-size: 1pt; line-height: 1pt;">&nbsp;</td></tr>
            </table>
            `;
        } else {
            borderHtml = `
            <div class="kop-border-line" style="margin-top: 8px; margin-bottom: 14px; width: 100%;">
                <div style="border-bottom: 3px solid #000000; height: 1px; line-height: 1px; font-size: 1px;">&nbsp;</div>
                <div style="border-bottom: 1px solid #000000; height: 2px; line-height: 1px; font-size: 1px; margin-top: 2px;">&nbsp;</div>
            </div>
            `;
        }
    }

    return `
    <div class="kop-surat-wrapper" style="width: 100%; font-family: ${fontFamily}; color: #000000;">
        <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 2px;">
            <tr style="border: none;">
                <td style="width: ${logoKiriSize + 10}px; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <img src="${logoKiriUrl}" style="max-height: ${logoKiriSize}px; max-width: ${logoKiriSize}px; height: auto; width: auto; object-fit: contain;" alt="Logo BGN" />
                </td>
                <td style="text-align: center; vertical-align: middle; border: none; padding: 0 10px; white-space: nowrap;">
                    <div style="font-size: ${titleSize1}; font-weight: bold; letter-spacing: 0.5px; line-height: 1.25; text-transform: uppercase; margin: 0; color: #000000; white-space: nowrap;">
                        ${instansi1}
                    </div>
                    <div style="font-size: ${titleSize2}; font-weight: bold; letter-spacing: 0.5px; line-height: 1.25; text-transform: uppercase; margin-top: 3px; color: #000000; white-space: nowrap;">
                        ${instansi2}
                    </div>
                    ${showUnitLine ? `
                    <div style="font-size: ${unitSize}; font-weight: bold; letter-spacing: 0.5px; line-height: 1.25; text-transform: uppercase; margin-top: 2px; color: #000000; white-space: nowrap;">
                        ${namaUnit}
                    </div>
                    ` : ''}
                    ${alamat ? `<div style="font-size: ${addrSize}; margin-top: 4px; line-height: 1.35; color: #000000; white-space: nowrap;">${alamat}</div>` : ''}
                    ${kontakStr ? `<div style="font-size: ${contactSize}; margin-top: 2px; line-height: 1.35; color: #000000; white-space: nowrap;">${kontakStr}</div>` : ''}
                </td>
                ${hasRightLogo ? `
                <td style="width: ${logoKananSize + 10}px; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <img src="${logoKananUrl}" style="max-height: ${logoKananSize}px; max-width: ${logoKananSize}px; height: auto; width: auto; object-fit: contain;" alt="Logo SPPG" />
                </td>
                ` : `
                <td style="width: ${layout === 'dual' ? (logoKiriSize + 10) + 'px' : '10px'}; border: none; padding: 0;"></td>
                `}
            </tr>
        </table>
        ${borderHtml}
    </div>
    `;
}
