<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading ?? 'Pemberitahuan Akun SIPEGE' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: #1e293b;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f1f5f9;
            padding: 30px 10px;
        }
        .main-card {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }
        .header {
            background: linear-gradient(135deg, #3730a3 0%, #4f46e5 50%, #2563eb 100%);
            padding: 36px 30px 28px;
            text-align: center;
            color: #ffffff;
        }
        .logo-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 10px 18px;
            margin-bottom: 12px;
        }
        .logo-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: #ffffff;
            margin: 0;
        }
        .header-subheading {
            font-size: 12px;
            color: #e0e7ff;
            margin-top: 4px;
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        .header-heading {
            font-size: 19px;
            font-weight: 800;
            color: #ffffff;
            margin: 16px 0 0 0;
        }
        .content {
            padding: 32px 30px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 14px;
        }
        .body-text {
            font-size: 14px;
            line-height: 1.65;
            color: #334155;
            margin: 0 0 24px;
        }
        .cta-container {
            text-align: center;
            margin: 28px 0;
        }
        .btn-cta {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 34px;
            text-decoration: none;
            border-radius: 14px;
            box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.35);
            letter-spacing: 0.02em;
        }
        .btn-cta:hover {
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%);
        }
        .fallback-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            margin-top: 24px;
            font-size: 12px;
            color: #64748b;
            word-break: break-all;
            line-height: 1.5;
        }
        .fallback-box a {
            color: #4f46e5;
            text-decoration: underline;
        }
        .notice-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 14px;
            margin-top: 20px;
            font-size: 12px;
            line-height: 1.5;
            color: #92400e;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 22px 30px;
            text-align: center;
            font-size: 11.5px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .footer strong {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main-card">
            <!-- Header -->
            <div class="header">
                <div class="logo-badge">
                    <span class="logo-title">SIPEGE</span>
                </div>
                <div class="header-subheading">{{ $subheading ?? 'Sistem Pengelolaan SPPG - Badan Gizi Nasional' }}</div>
                <div class="header-heading">{{ $heading ?? 'Pemberitahuan Akun' }}</div>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="greeting">{{ $greeting ?? 'Halo,' }}</div>
                <div class="body-text">{!! nl2br(e($body)) !!}</div>

                @if(!empty($actionUrl) && !empty($buttonText))
                <div class="cta-container">
                    <a href="{{ $actionUrl }}" class="btn-cta" target="_blank">{{ $buttonText }}</a>
                </div>

                <div class="fallback-box">
                    Jika tombol di atas tidak dapat ditekan, salin dan tempelkan tautan berikut ke peramban (browser) Anda:<br>
                    <a href="{{ $actionUrl }}" target="_blank">{{ $actionUrl }}</a>
                </div>
                @endif

                @if(!empty($footerNote))
                <div class="notice-box">
                    <strong>Catatan Keamanan:</strong> {!! nl2br(e($footerNote)) !!}
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                Email ini dikirim secara otomatis oleh <strong>SIPEGE</strong> untuk keperluan verifikasi dan keamanan akun Anda.<br>
                &copy; {{ date('Y') }} Badan Gizi Nasional (BGN) Republik Indonesia. Seluruh hak cipta dilindungi.
            </div>
        </div>
    </div>
</body>
</html>

