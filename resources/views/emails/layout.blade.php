<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:20px 0;">
<tr>
<td align="center">

    <!-- Container -->
    <table width="600" cellpadding="0" cellspacing="0" 
           style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

      
        <!-- HEADER -->
        <tr>
            <td style="background:#ffffff;padding:25px;text-align:center;
                    border-top:30px solid #2563eb;">
                
                <img src="{{ asset('puk_omron.PNG') }}" 
                    alt="PUK OMRON" 
                    style="max-width:300px;margin-bottom:15px;">

                <!-- GARIS BIRU -->
                <div style="width:180px;height:2px;background:#2563eb;
                            margin:0 auto 15px auto;border-radius:2px;">
                </div>

                <h1 style="margin:0;font-size:18px;color:#1f2937;font-weight:bold;">
                    DASHBOARD PUK OMRON
                </h1>

                <div style="font-size:13px;color:#6b7280;margin-top:4px;">
                    Sistem Informasi Pengajuan Dana
                </div>
            </td>
        </tr>

        <!-- CONTENT -->
        <tr>
            <td style="padding:25px;color:#333333;font-size:14px;line-height:1.6;">
                @yield('content')
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td style="background:#f9fafb;padding:20px;text-align:center;
                       font-size:12px;color:#6b7280;line-height:1.6;">

                <strong style="color:#1f2937;">
                    {{ config('app.name') }}
                </strong><br>

                📍 PUK SPEE FSPMI PT OMRON<br>
                📧 {{ env('MAIL_FROM_ADDRESS') }}

                <div style="margin-top:10px;color:#9ca3af;">
                    Email ini dikirim otomatis oleh sistem.<br>
                    Mohon tidak membalas email ini.
                </div>

                <div style="margin-top:8px;font-size:11px;color:#9ca3af;">
                    © {{ date('Y') }} PUK OMRON • Internal System
                </div>

            </td>
        </tr>

    </table>

</td>
</tr>
</table>

</body>
</html>
