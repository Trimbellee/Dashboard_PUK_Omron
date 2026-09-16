<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>

<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:20px 0;">
        <tr>
            <td align="center">

                <!-- Container -->
                <table width="600" cellpadding="0" cellspacing="0"
                       style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.05);max-width:600px;width:100%;">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:#ffffff;padding:25px;text-align:center;
                                   border-top:30px solid #2563eb;">

                            <img src="{{ asset('puk_omron.PNG') }}"
                                 alt="PUK OMRON"
                                 style="max-width:300px;margin-bottom:15px;width:100%;height:auto;">

                            <!-- GARIS BIRU -->
                            <div style="width:180px;height:2px;background:#2563eb;
                                        margin:0 auto 15px auto;border-radius:2px;">
                            </div>

                            <h1 style="margin:0;font-size:18px;color:#1f2937;font-weight:bold;">
                                DASHBOARD PUK OMRON
                            </h1>

                            <div style="font-size:13px;color:#6b7280;margin-top:4px;">
                                Sistem Pengingat Agenda
                            </div>
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:25px;color:#333333;font-size:14px;line-height:1.6;">

                            {{-- GREETING --}}
                            <p style="margin-bottom: 25px; color: #1f2937; font-size: 15px; line-height: 1.6;">
                                Halo <strong style="color: #2563eb; font-weight: 700;">{{ $recipient->role ?? 'Tim' }}</strong>,
                                <br>
                                <span style="color: #6b7280; font-size: 14px;">Berikut adalah pengingat untuk agenda yang akan datang:</span>
                            </p>

                            {{-- EVENT CARD --}}
                            <div style="background: #ffffff; 
                                        border-radius: 12px; 
                                        padding: 0; 
                                        margin-bottom: 25px;
                                        border: 2px solid #2563eb;
                                        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
                                        overflow: hidden;">

                                {{-- Header Card --}}
                                <div style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
                                            padding: 18px 24px;
                                            border-bottom: 3px solid #1e3a8a;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="background: rgba(255,255,255,0.2);
                                                    color: white;
                                                    width: 40px;
                                                    height: 40px;
                                                    border-radius: 50%;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    font-size: 20px;
                                                    flex-shrink: 0;
                                                    border: 2px solid rgba(255,255,255,0.3);">
                                            📌
                                        </div>
                                        <div>
                                            <h2 style="margin: 0; 
                                                       font-size: 20px; 
                                                       font-weight: 700; 
                                                       color: #ffffff; 
                                                       line-height: 1.3;
                                                       text-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                                                {{ $event->title }}
                                            </h2>
                                        </div>
                                    </div>
                                </div>

                                {{-- Body Card --}}
                                <div style="padding: 20px 24px 24px 24px; background: #fafbff;">
                                    {{-- Detail Ringkas --}}
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px;">
                                        @php
                                            $start = \Carbon\Carbon::parse($event->start_date);
                                            $end = \Carbon\Carbon::parse($event->end_date);
                                            
                                            // Format tanggal dengan hari
                                            $startDay = $start->locale('id')->translatedFormat('l');
                                            $endDay = $end->locale('id')->translatedFormat('l');
                                            $startDate = $start->format('d');
                                            $endDate = $end->format('d');
                                            $startMonth = $start->locale('id')->translatedFormat('F');
                                            $endMonth = $end->locale('id')->translatedFormat('F');
                                            $startYear = $start->format('Y');
                                            $endYear = $end->format('Y');
                                            
                                            // Format tanggal final
                                            if ($start->format('Y-m-d') == $end->format('Y-m-d')) {
                                                // Satu hari: Kamis, 16 Juli 2026
                                                $dateDisplay = $startDay . ', ' . $startDate . ' ' . $startMonth . ' ' . $startYear;
                                            } elseif ($start->format('Y-m') == $end->format('Y-m')) {
                                                // Satu bulan: Kamis-Sabtu, 16-18 Juli 2026
                                                $dateDisplay = $startDay . '-' . $endDay . ', ' . $startDate . '-' . $endDate . ' ' . $startMonth . ' ' . $startYear;
                                            } elseif ($start->format('Y') == $end->format('Y')) {
                                                // Beda bulan, tahun sama: Kamis, 16 Juli s/d Sabtu, 01 Agustus 2026
                                                $dateDisplay = $startDay . ', ' . $startDate . ' ' . $startMonth . ' s/d ' . $endDay . ', ' . $endDate . ' ' . $endMonth . ' ' . $startYear;
                                            } else {
                                                // Beda tahun: Kamis, 16 Juli 2025 s/d Sabtu, 01 Agustus 2026
                                                $dateDisplay = $startDay . ', ' . $startDate . ' ' . $startMonth . ' ' . $startYear . ' s/d ' . $endDay . ', ' . $endDate . ' ' . $endMonth . ' ' . $endYear;
                                            }
                                            
                                            // Format waktu
                                            $timeDisplay = '--:--';
                                            if ($event->start_time) {
                                                $timeDisplay = \Carbon\Carbon::parse($event->start_time)->format('H:i');
                                            }
                                            
                                            $endTimeDisplay = '';
                                            if ($event->end_time) {
                                                $endTimeDisplay = \Carbon\Carbon::parse($event->end_time)->format('H:i');
                                            }
                                            
                                            // Tampilan waktu final
                                            if ($endTimeDisplay) {
                                                $finalTimeDisplay = $timeDisplay . ' s/d ' . $endTimeDisplay;
                                            } elseif ($timeDisplay !== '--:--') {
                                                $finalTimeDisplay = $timeDisplay . ' s/d selesai';
                                            } else {
                                                $finalTimeDisplay = '--:--';
                                            }

                                            // Tentukan catatan berdasarkan kategori
                                            $isMeeting = strtolower($event->category) == 'meeting';
                                            $noteText = $isMeeting 
                                                ? 'Mengingat pentingnya agenda tersebut, diharapkan kehadirannya tepat waktu.'
                                                : 'Mohon untuk mempersiapkan diri dan menyelesaikan tugas sesuai dengan jadwal yang telah ditentukan.';
                                            $noteIcon = $isMeeting ? '👥' : '📋';
                                            $noteColor = $isMeeting ? '#1e40af' : '#065f46';
                                            $noteBg = $isMeeting ? 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)' : 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)';
                                            $noteBorder = $isMeeting ? '#2563eb' : '#22c55e';
                                        @endphp

                                        {{-- Tanggal --}}
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">📆</span>
                                            <div style="flex: 1;">
                                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Tanggal</div>
                                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                                    {{ $dateDisplay }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Waktu --}}
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">⏰</span>
                                            <div>
                                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Waktu</div>
                                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                                    {{ $finalTimeDisplay }}
                                                    <span style="font-size: 11px; color: #94a3b8; font-weight: 400;">WIB</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Tempat --}}
                                        @if($event->location)
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">📍</span>
                                            <div>
                                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Tempat</div>
                                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                                    {{ $event->location }}
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Bidang --}}
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">📂</span>
                                            <div>
                                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Bidang</div>
                                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                                    {{ $event->bidang ?? '-' }}
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Kategori --}}
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">🏷️</span>
                                            <div>
                                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Agenda</div>
                                                <div style="font-size: 14px; font-weight: 600; color: #1e293b;">
                                                    <span style="background: {{ $isMeeting ? '#dbeafe' : '#e2e8f0' }}; 
                                                                 padding: 2px 14px; 
                                                                 border-radius: 20px; 
                                                                 font-size: 12px; 
                                                                 font-weight: 600;
                                                                 color: {{ $isMeeting ? '#1e40af' : '#475569' }};
                                                                 display: inline-block;">
                                                        {{ $event->category }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Deskripsi --}}
                                        @if($event->description)
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">📝</span>
                                            <div style="flex: 1;">
                                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Keterangan</div>
                                                <div style="font-size: 14px; font-weight: 500; color: #1e293b;">
                                                    {{ $event->description }}
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- RECIPIENTS --}}
                            <div style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
                                        border-radius: 12px;
                                        padding: 16px 20px;
                                        margin-bottom: 20px;
                                        border: 1px solid #dbeafe;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <span style="font-size: 14px;">📧</span>
                                    <span style="font-weight: 700; color: #1e293b; font-size: 13px;">Dikirim ke:</span>
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    @foreach($recipients as $r)
                                        <span style="display: inline-flex; align-items: center; gap: 4px;
                                                     background: linear-gradient(135deg, #2563eb, #3b82f6);
                                                     color: #ffffff;
                                                     padding: 4px 14px;
                                                     border-radius: 20px;
                                                     font-size: 11px;
                                                     font-weight: 600;
                                                     box-shadow: 0 1px 4px rgba(37, 99, 235, 0.2);">
                                            <span style="font-size: 10px;">👤</span>
                                            {{ $r->role }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- NOTE --}}
                            <div style="background: {{ $noteBg }};
                                        border-radius: 12px;
                                        padding: 14px 18px;
                                        border-left: 4px solid {{ $noteBorder }};
                                        margin-top: 4px;">
                                <div style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span style="font-size: 18px;">{{ $noteIcon }}</span>
                                    <div>
                                        <div style="font-weight: 700; color: {{ $noteColor }}; font-size: 13px; margin-bottom: 2px;">
                                            Catatan Penting
                                        </div>
                                        <p style="margin: 0; color: {{ $noteColor }}; font-size: 13px; line-height: 1.6;">
                                            {{ $noteText }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- TIMESTAMP --}}
                            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center;">
                                <span style="font-size: 11px; color: #94a3b8;">
                                    📨 Dikirim pada: {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WIB
                                </span>
                            </div>

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
                                © {{ date('Y') }} PUK SPEE FSPMI PT OMRON
                            </div>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>