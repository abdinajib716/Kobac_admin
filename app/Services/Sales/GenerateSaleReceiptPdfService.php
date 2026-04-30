<?php

namespace App\Services\Sales;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateSaleReceiptPdfService
{
    public function generate(Sale $sale): array
    {
        $sale->loadMissing(['business', 'branch', 'customer', 'items', 'payments', 'createdBy']);

        $path = 'receipts/' . $sale->business_id . '/sale_' . $sale->id . '.pdf';

        $pdf = Pdf::loadView('exports.sales-receipt', [
            'sale' => $sale,
            'business' => $sale->business,
            'branch' => $sale->branch,
            'customer' => $sale->customer,
            'items' => $sale->items,
            'payments' => $sale->payments,
            'cashier' => $sale->createdBy,
            'currency' => $sale->business?->currency ?? 'USD',
            'generated_at' => now()->toIso8601String(),
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        if ($sale->receipt_pdf_path !== $path) {
            $sale->forceFill(['receipt_pdf_path' => $path])->save();
        }

        $url = asset('storage/' . $path);

        return [
            'file_path' => $path,
            'download_url' => $url,
            'share_url' => $url,
            'print_url' => $url,
        ];
    }
}
