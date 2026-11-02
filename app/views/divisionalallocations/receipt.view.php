<?php
/**
 * Official Fund Allocation Receipt View — Divisional Treasurer
 * Styled for printing and PDF generation.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Disbursement Receipt — <?= htmlspecialchars($transfer->reference_no) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f8fafc; color: #1e293b; padding: 40px 20px; }
        .receipt-card {
            max-width: 780px; margin: 0 auto; background: #ffffff;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0; overflow: hidden;
        }
        .receipt-strip { height: 8px; background: #3154d8; }
        .receipt-header {
            padding: 30px; border-bottom: 2px dashed #cbd5e1;
            display: flex; justify-content: space-between; align-items: flex-start;
        }
        .header-brand h1 { font-size: 22px; color: #3154d8; margin-bottom: 4px; }
        .header-brand p { font-size: 13px; color: #64748b; }
        .badge-ref {
            background: #eff6ff; color: #1d4ed8; font-weight: 700;
            padding: 6px 14px; border-radius: 8px; font-size: 14px;
            letter-spacing: 0.5px; display: inline-block; margin-bottom: 6px;
        }
        .badge-status {
            display: inline-block; font-size: 12px; font-weight: 600;
            padding: 4px 10px; border-radius: 20px;
            background: <?= ($transfer->status === 'Completed') ? '#dcfce7; color:#15803d;' : '#fef9c3; color:#a16207;' ?>;
        }
        .receipt-amount-band {
            background: #f1f5f9; padding: 24px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .amount-title { font-size: 12px; font-weight: 700; color: #64748b; letter-spacing: 1px; }
        .amount-num { font-size: 28px; font-weight: 800; color: #3154d8; margin-top: 4px; }
        .amount-date { font-size: 14px; color: #475569; font-weight: 500; }
        .receipt-body { padding: 30px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .info-card {
            border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px;
            background: #ffffff;
        }
        .info-card h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
        .data-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .data-row .k { color: #64748b; }
        .data-row .v { font-weight: 600; text-align: right; color: #0f172a; }
        .purpose-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 16px; margin-top: 10px; font-size: 13px; line-height: 1.6;
        }
        .receipt-footer {
            padding: 24px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b;
        }
        .print-btn {
            background: #3154d8; color: #fff; border: none; padding: 10px 20px;
            border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .print-btn:hover { background: #2444bd; }
        @media print {
            body { padding: 0; background: #fff; }
            .receipt-card { box-shadow: none; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="receipt-card">
        <div class="receipt-strip"></div>

        <div class="receipt-header">
            <div class="header-brand">
                <h1>National Youth Services Council (NYSC)</h1>
                <p><?= htmlspecialchars($receiptSubtitle ?? 'Divisional Secretariat — Official Club Fund Disbursement Voucher') ?></p>
            </div>
            <div style="text-align: right;">
                <div class="badge-ref"><?= htmlspecialchars($transfer->reference_no) ?></div>
                <div><span class="badge-status">&bull; <?= htmlspecialchars($transfer->status) ?></span></div>
            </div>
        </div>

        <div class="receipt-amount-band">
            <div>
                <div class="amount-title">DISBURSEMENT AMOUNT</div>
                <div class="amount-num">LKR <?= number_format((float)$transfer->amount, 2) ?></div>
            </div>
            <div class="amount-date"><?= date('Y-m-d - h:i A', strtotime($transfer->created_at)) ?></div>
        </div>

        <div class="receipt-body">
            <div class="grid-2">
                <div class="info-card">
                    <h3>Recipient &amp; Allocation Details</h3>
                    <div class="data-row"><span class="k">Recipient Club:</span><span class="v"><?= htmlspecialchars($transfer->club_name) ?></span></div>
                    <div class="data-row"><span class="k">Division:</span><span class="v"><?= htmlspecialchars($division->division_name ?? 'Divisional Secretariat') ?></span></div>
                    <div class="data-row"><span class="k">Fund Category:</span><span class="v"><?= htmlspecialchars($transfer->fund_category ?: 'General Allocation') ?></span></div>
                    <div class="data-row"><span class="k">Transfer Date:</span><span class="v"><?= date('F j, Y', strtotime($transfer->transfer_date)) ?></span></div>
                </div>

                <div class="info-card">
                    <h3>Payment &amp; Source Account</h3>
                    <div class="data-row"><span class="k">Reference No:</span><span class="v"><?= htmlspecialchars($transfer->reference_no) ?></span></div>
                    <div class="data-row"><span class="k">Disbursement Method:</span><span class="v"><?= ($transfer->disbursement_method === 'RTGS') ? 'Bank Transfer (Direct / RTGS)' : 'Cheque / SLIPS' ?></span></div>
                    <div class="data-row"><span class="k">Source Bank:</span><span class="v"><?= htmlspecialchars($transfer->bank_name ?? 'Divisional Account') ?></span></div>
                    <div class="data-row"><span class="k">Source Account:</span><span class="v"><?= htmlspecialchars($transfer->account_number ?? '—') ?></span></div>
                    <div class="data-row"><span class="k">Authorized By:</span><span class="v"><?= htmlspecialchars($transfer->authorized_by_name ?? 'Divisional Treasurer') ?></span></div>
                </div>
            </div>

            <div class="info-card">
                <h3>Purpose &amp; Allocation Description</h3>
                <div class="purpose-box">
                    <?= nl2br(htmlspecialchars($transfer->purpose_description)) ?>
                </div>
            </div>
        </div>

        <div class="receipt-footer">
            <div>
                Generated by YouthNexus Pulse &bull; Divisional Financial Settlement System
            </div>
            <div class="no-print">
                <button class="print-btn" onclick="window.print()">Print Voucher / Save PDF</button>
            </div>
        </div>
    </div>

</body>
</html>

