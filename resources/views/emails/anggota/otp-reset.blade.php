<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password PUK OMRON</title>
    <!-- Modern, professional email styling with mobile-first approach -->
    <style>
        /* Reset & base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background: #f2f6fc;
            padding: 24px 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .email-container {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px -12px rgba(0, 20, 40, 0.15), 0 8px 24px -6px rgba(0, 20, 40, 0.05);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        /* Inner content with padding */
        .email-content {
            padding: 40px 32px 32px;
        }

        /* Header / brand */
        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-header h2 {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0b2b44;
            margin: 0;
            display: inline-block;
            background: linear-gradient(135deg, #0b2b44 0%, #1a5fb4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Greeting */
        .greeting {
            font-size: 16px;
            color: #1e3a5f;
            font-weight: 500;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .greeting span {
            font-weight: 600;
            color: #0b2b44;
        }

        .message-text {
            font-size: 15px;
            color: #34597a;
            line-height: 1.6;
            margin-bottom: 24px;
            font-weight: 400;
        }

        /* Code block — modern glassmorphism style */
        .code-block {
            background: #eef4fc;
            background-image: radial-gradient(circle at 10% 20%, rgba(26, 95, 180, 0.03) 0%, transparent 60%);
            border-radius: 20px;
            padding: 24px 16px;
            margin: 28px 0 32px;
            text-align: center;
            border: 1px solid rgba(26, 95, 180, 0.08);
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.6), 0 4px 12px rgba(26, 95, 180, 0.06);
            position: relative;
        }

        .code-block .code-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #3d6b94;
            margin-bottom: 8px;
            display: block;
        }

        .code-block .code-value {
            font-size: 40px;
            font-weight: 700;
            letter-spacing: 12px;
            color: #0b2b44;
            background: #ffffff;
            padding: 12px 8px;
            border-radius: 16px;
            display: inline-block;
            min-width: 200px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Small text, notes */
        .note-text {
            font-size: 13.5px;
            color: #5f7d99;
            line-height: 1.6;
            margin: 16px 0 6px;
        }

        .note-text strong {
            color: #1a3e5c;
            font-weight: 600;
        }


        /* Footer (exactly as requested, but enhanced inside container) */
        .footer-wrapper {
            background: #f9fafb;
            border-radius: 0 0 24px 24px;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
            border-top: 1px solid #eef2f6;
        }

        .footer-wrapper strong {
            color: #1f2937;
            font-weight: 600;
        }

        .footer-wrapper .footer-address {
            margin-top: 4px;
            color: #4b6a88;
        }

        .footer-wrapper .footer-address span {
            display: inline-block;
            margin: 0 4px;
        }

        .footer-wrapper .footer-auto {
            margin-top: 10px;
            color: #9ca3af;
            font-size: 12px;
        }

        .footer-wrapper .footer-copy {
            margin-top: 8px;
            font-size: 11px;
            color: #9ca3af;
        }

        /* Responsive: mobile first */
        @media (max-width: 480px) {
            body {
                padding: 12px 10px;
            }
            .email-content {
                padding: 28px 20px 20px;
            }
            .brand-header h2 {
                font-size: 22px;
            }
            .code-block .code-value {
                font-size: 32px;
                letter-spacing: 10px;
                min-width: 160px;
                padding: 10px 6px;
            }
            .code-block {
                padding: 18px 12px;
                margin: 20px 0 24px;
            }
            .greeting {
                font-size: 15px;
            }
            .message-text {
                font-size: 14px;
            }
            .note-text {
                font-size: 12.5px;
            }
            .footer-wrapper {
                padding: 16px 16px;
                font-size: 11px;
            }
        }

        @media (max-width: 380px) {
            .code-block .code-value {
                font-size: 28px;
                letter-spacing: 6px;
                min-width: 140px;
            }
            .email-content {
                padding: 20px 14px;
            }
        }

        /* small extra polish */
        .highlight-icon {
            display: inline-block;
            margin-right: 6px;
        }
        .footer-wrapper .footer-address .sep {
            color: #cbd5e1;
            padding: 0 4px;
        }

        /* compatibility for email clients */
        .email-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .email-container td {
            vertical-align: top;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- main content -->
        <div class="email-content">
            <!-- Brand header -->
            <div class="brand-header">
                <h2>PUK OMRON</h2>             
            </div>

            <!-- Greeting & message -->
            <div class="greeting">
                Halo <span>{{ $namaLengkap ?? 'Pengguna' }}</span>,
            </div>
            <div class="message-text">
                Gunakan kode berikut untuk reset password akun Anda:
            </div>

            <!-- Code block -->
            <div class="code-block">
                <span class="code-label">Kode Verifikasi</span>
                <div class="code-value">{{ $kode ?? 'ABCDEF' }}</div>
            </div>

            <!-- Notes -->
            <div class="note-text">
                <strong>⏱️ Berlaku selama 10 menit.</strong> Jangan bagikan kode ini kepada siapa pun, termasuk pihak yang mengatasnamakan PUK OMRON.
            </div>
            <div class="note-text" style="margin-top: 6px;">
                Jika Anda tidak meminta reset password, abaikan email ini.
            </div>

        </div>

        <!-- FOOTER (exactly as requested, with modern touch) -->
        <div class="footer-wrapper">

            <div class="footer-address">
                📍 PUK SPEE FSPMI PT OMRON<br>
                📧 {{ env('MAIL_FROM_ADDRESS', 'noreply@omron.co.id') }}
            </div>

            <div class="footer-auto">
                Email ini dikirim otomatis oleh sistem.<br>
                Mohon tidak membalas email ini.
            </div>

            <div class="footer-copy">
                © {{ date('Y') }} PUK SPEE FSPMI PT OMRON
            </div>
        </div>
        <!-- END FOOTER -->
    </div>
</body>
</html>