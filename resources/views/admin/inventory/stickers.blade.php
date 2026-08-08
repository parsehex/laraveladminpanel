<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Print QR Stickers</title>
    <style>
        @page { margin: 0; size: 60mm auto; }
        * { box-sizing: border-box; }
        body {
            width: 60mm;
            margin: 0;
            padding: 0;
            background: #f4f6f8;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            text-align: center;
        }
        .sticker {
            width: 60mm;
            min-height: 72mm;
            padding: 3.2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            page-break-after: always;
            background: #fff;
        }
        .sticker:last-child { page-break-after: auto; }
        .label-card {
            width: 100%;
            min-height: 64mm;
            border: 0.45mm solid #111827;
            border-radius: 2.5mm;
            padding: 2.8mm 2.4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            width: 100%;
            padding-bottom: 1.8mm;
            border-bottom: 0.25mm solid #d1d5db;
            font-size: 9pt;
            font-weight: 800;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
        }
        .qr-wrap {
            margin: 2.5mm 0 1.5mm;
            padding: 1.5mm;
            border: 0.25mm solid #e5e7eb;
            border-radius: 2mm;
        }
        .qr-code {
            width: 38mm;
            height: 38mm;
        }
        .barcode-wrap {
            width: 100%;
            margin-top: 2mm;
            padding-top: 1.5mm;
            text-align: center;
        }

        .barcode {
            width: 100%;
            height: 13mm;
            display: block;
        }
        .details {
            width: 100%;
            display: grid;
            gap: 1mm;
            text-align: left;
        }
        .unit-label {
            margin: 0;
            text-align: center;
            font-size: 13pt;
            font-weight: 800;
            line-height: 1.1;
        }
        .meta-row {
            display: grid;
            grid-template-columns: 12mm 1fr;
            gap: 1.5mm;
            align-items: start;
            font-size: 7.8pt;
            line-height: 1.15;
        }
        .meta-label {
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
        }
        .meta-value {
            min-width: 0;
            overflow-wrap: anywhere;
            font-weight: 700;
            color: #111827;
        }
        .footer-code {
            width: 100%;
            margin-top: 1.5mm;
            padding-top: 1.2mm;
            border-top: 0.25mm solid #d1d5db;
            color: #4b5563;
            font-size: 7pt;
            font-weight: 700;
        }

        @media screen {
            body {
                width: auto;
                min-height: 100vh;
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                align-items: flex-start;
                justify-content: center;
                padding: 24px;
            }
            .sticker {
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
                page-break-after: auto;
            }
        }

        @media print {
            body {
                background: #fff;
            }
            .sticker {
                box-shadow: none;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
</head>
<body>
@foreach($items as $item)
    <div class="sticker">
        <div class="label-card">
            <div class="brand">Ben's Appliances</div>
            <div class="qr-wrap">
                <div class="qr-code" id="qrcode-{{ $item->id }}" data-url="{{ route('admin.inventory.show', $item) }}"></div>
            </div>
            <div class="details">
                <p class="unit-label">{{ $item->unit_label ?: 'Appliance #'.$item->id }}</p>
                <div class="meta-row">
                    <span class="meta-label">Model</span>
                    <span class="meta-value">{{ $item->model?->model_number ?? 'N/A' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Serial</span>
                    <span class="meta-value">{{ $item->serial_number ?: 'N/A' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Truck</span>
                    <span class="meta-value">{{ $item->truck?->name ?? 'N/A' }}</span>
                </div>
                 <div class="barcode-wrap">
                    <svg
                        class="barcode"
                        data-value="{{ $item->model?->model_number ?? '' }}"
                    ></svg>
                </div>
            </div>
            <div class="footer-code">Appliance ID: {{ $item->id }}</div>
        </div>
    </div>
@endforeach
<script>
    window.onload = function () {
        document.querySelectorAll('.qr-code').forEach(function (element) {
            new QRCode(element, {
                text: element.dataset.url,
                width: 144,
                height: 144,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        });

         document.querySelectorAll('.barcode').forEach(function (element) {
            const value = element.dataset.value;

            if (value) {
                JsBarcode(element, value, {
                    format: 'CODE128',
                    width: 1.5,
                    height: 40,
                    displayValue: true,
                    fontSize: 10,
                    margin: 0,
                    textMargin: 2
                });
            }
        });


        setTimeout(function () {
            window.print();
        }, 500);
    };
</script>
</body>
</html>
