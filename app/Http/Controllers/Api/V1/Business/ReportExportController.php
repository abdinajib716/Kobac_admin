<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Customer;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends BaseController
{
    /**
     * Export business reports in PDF / Excel.
     * GET /api/v1/business/reports/export?report=stock&format=xlsx
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report' => 'required|string|in:stock,customers,vendors,profit_loss,activity',
            'format' => 'required|string|in:pdf,xlsx',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->error(__('mobile.errors.validation_failed'), 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $data = $validator->validated();
        $report = $data['report'];
        $format = $data['format'];
        $branchId = $data['branch_id'] ?? $this->branchId();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null;

        $dataset = match ($report) {
            'stock' => $this->buildStockDataset(
                $business->id,
                $branchId,
                $business->name,
                $business->currency ?? 'USD'
            ),
            'customers' => $this->buildCustomersDataset(
                $business->id,
                $branchId,
                $business->name,
                $business->currency ?? 'USD',
                $from,
                $to
            ),
            'vendors' => $this->buildVendorsDataset(
                $business->id,
                $branchId,
                $business->name,
                $business->currency ?? 'USD',
                $from,
                $to
            ),
            'profit_loss' => $this->buildProfitLossDataset($business->id, $branchId, $from, $to),
            'activity' => $this->buildActivityDataset($business->id, $branchId, $from, $to),
            default => null,
        };

        if (!$dataset) {
            return $this->error('Unsupported report type', 'UNSUPPORTED_REPORT', 422);
        }

        $timestamp = now()->format('Ymd_His');
        $extension = $format === 'xlsx' ? 'xlsx' : 'pdf';
        $filename = "{$report}_{$timestamp}.{$extension}";
        $path = "exports/{$business->id}/{$filename}";

        if ($format === 'xlsx') {
            Excel::store(
                new ArrayReportExport($dataset['columns'], $dataset['rows']),
                $path,
                'public'
            );
        } else {
            $pdf = Pdf::loadView('exports.report', [
                'title' => $dataset['title'],
                'columns' => $dataset['columns'],
                'rows' => $dataset['rows'],
                'summary' => $dataset['summary'],
                'meta' => $dataset['meta'],
            ])->setPaper('a4', 'portrait');

            Storage::disk('public')->put($path, $pdf->output());
        }

        return $this->success([
            'report' => $report,
            'format' => $format,
            'file_name' => $filename,
            'file_path' => $path,
            'download_url' => asset('storage/' . $path),
            'summary' => $dataset['summary'],
            'generated_at' => now()->toIso8601String(),
        ], __('mobile.messages.export_ready'));
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildStockDataset(
        int $businessId,
        ?int $branchId,
        string $businessName,
        string $currency
    ): array
    {
        $items = StockItem::forBusiness($businessId, $branchId)
            ->orderBy('name')
            ->get();

        $rows = $items->map(function (StockItem $item) {
            $costValue = $item->quantity * ($item->cost_price ?? 0);
            $displayQuantity = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');

            return [
                'name' => $item->name,
                'cost_price' => (float) ($item->cost_price ?? 0),
                'selling_price' => (float) ($item->selling_price ?? 0),
                'stock_quantity' => trim($displayQuantity . ' ' . ($item->unit ?? '')),
                'stock_value' => (float) $costValue,
            ];
        })->values()->all();

        $totalCostValue = $items->sum(fn (StockItem $item) => $item->quantity * ($item->cost_price ?? 0));

        return [
            'title' => __('mobile.reports.stock_report_title'),
            'columns' => [
                'name' => __('mobile.reports.stock_columns.name'),
                'cost_price' => __('mobile.reports.stock_columns.cost_price') . " ({$currency})",
                'selling_price' => __('mobile.reports.stock_columns.selling_price') . " ({$currency})",
                'stock_quantity' => __('mobile.reports.stock_columns.stock_quantity'),
                'stock_value' => __('mobile.reports.stock_columns.stock_value') . " ({$currency})",
            ],
            'rows' => $rows,
            'summary' => [
                'total_items' => $items->count(),
                'total_cost_value' => (float) $totalCostValue,
            ],
            'meta' => [
                'report_key' => 'stock',
                'branch_id' => $branchId,
                'business_name' => $businessName,
                'currency' => $currency,
                'total_products_label' => __('mobile.reports.total_products'),
                'money_section_label' => __('mobile.reports.money_section'),
                'total_stock_value_label' => __('mobile.reports.total_stock_value'),
                'generated_label' => __('mobile.reports.generated_at'),
                'generated_at_display' => now()->format('d/m/Y, H:i:s'),
                'app_name' => __('mobile.app.name'),
            ],
        ];
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildVendorsDataset(
        int $businessId,
        ?int $branchId,
        string $businessName,
        string $currency,
        ?Carbon $from,
        ?Carbon $to
    ): array
    {
        $vendorsQuery = Vendor::forBusiness($businessId, $branchId)
            ->orderBy('name')
            ->withSum([
                'transactions as period_credit_total' => function ($query) use ($from, $to) {
                    $query->where('type', 'credit');
                    if ($from) {
                        $query->whereDate('transaction_date', '>=', $from->toDateString());
                    }
                    if ($to) {
                        $query->whereDate('transaction_date', '<=', $to->toDateString());
                    }
                },
            ], 'amount')
            ->withSum([
                'transactions as period_debit_total' => function ($query) use ($from, $to) {
                    $query->where('type', 'debit');
                    if ($from) {
                        $query->whereDate('transaction_date', '>=', $from->toDateString());
                    }
                    if ($to) {
                        $query->whereDate('transaction_date', '<=', $to->toDateString());
                    }
                },
            ], 'amount')
            ->withMax([
                'transactions as last_transaction_date' => function ($query) use ($from, $to) {
                    if ($from) {
                        $query->whereDate('transaction_date', '>=', $from->toDateString());
                    }
                    if ($to) {
                        $query->whereDate('transaction_date', '<=', $to->toDateString());
                    }
                },
            ], 'transaction_date');

        $vendors = $vendorsQuery->get();

        $rows = $vendors->map(function (Vendor $vendor) {
            $balance = (float) $vendor->balance;
            $payable = $balance > 0 ? $balance : 0.0;
            $overpaid = $balance < 0 ? abs($balance) : 0.0;

            return [
                'name' => $vendor->name,
                'phone' => $vendor->phone ?? '-',
                'status' => match ($vendor->status) {
                    'we_owe' => __('mobile.reports.vendor_status.we_owe'),
                    'they_owe' => __('mobile.reports.vendor_status.they_owe'),
                    default => __('mobile.reports.vendor_status.settled'),
                },
                'balance' => round($balance, 2),
                'payable' => round($payable, 2),
                'overpaid' => round($overpaid, 2),
                'period_credit' => round((float) ($vendor->period_credit_total ?? 0), 2),
                'period_debit' => round((float) ($vendor->period_debit_total ?? 0), 2),
                'last_transaction_date' => $vendor->last_transaction_date
                    ? Carbon::parse($vendor->last_transaction_date)->toDateString()
                    : '-',
            ];
        })->values()->all();

        $totalPayable = (float) $vendors->filter(fn (Vendor $v) => (float) $v->balance > 0)->sum('balance');
        $totalOverpaid = (float) $vendors->filter(fn (Vendor $v) => (float) $v->balance < 0)->sum(function (Vendor $v) {
            return abs((float) $v->balance);
        });
        $netBalance = (float) $vendors->sum('balance');

        return [
            'title' => __('mobile.reports.vendor_report_title'),
            'columns' => [
                'name' => __('mobile.reports.vendor_columns.name'),
                'phone' => __('mobile.reports.vendor_columns.phone'),
                'status' => __('mobile.reports.vendor_columns.status'),
                'balance' => __('mobile.reports.vendor_columns.balance') . " ({$currency})",
                'payable' => __('mobile.reports.vendor_columns.payable') . " ({$currency})",
                'overpaid' => __('mobile.reports.vendor_columns.overpaid') . " ({$currency})",
                'period_credit' => __('mobile.reports.vendor_columns.period_credit') . " ({$currency})",
                'period_debit' => __('mobile.reports.vendor_columns.period_debit') . " ({$currency})",
                'last_transaction_date' => __('mobile.reports.vendor_columns.last_transaction_date'),
            ],
            'rows' => $rows,
            'summary' => [
                'total_vendors' => $vendors->count(),
                'active_vendors' => $vendors->where('is_active', true)->count(),
                'vendors_with_payables' => $vendors->filter(fn (Vendor $v) => (float) $v->balance > 0)->count(),
                'total_payable' => round($totalPayable, 2),
                'total_overpaid' => round($totalOverpaid, 2),
                'net_balance' => round($netBalance, 2),
                'period_total_credit' => round((float) $vendors->sum(fn (Vendor $v) => (float) ($v->period_credit_total ?? 0)), 2),
                'period_total_debit' => round((float) $vendors->sum(fn (Vendor $v) => (float) ($v->period_debit_total ?? 0)), 2),
                'period_from' => $from?->toDateString(),
                'period_to' => $to?->toDateString(),
            ],
            'meta' => [
                'report_key' => 'vendors',
                'branch_id' => $branchId,
                'business_name' => $businessName,
                'currency' => $currency,
                'generated_label' => __('mobile.reports.generated_at'),
                'generated_at_display' => now()->format('d/m/Y, H:i:s'),
                'app_name' => __('mobile.app.name'),
                'summary_labels' => [
                    'total_vendors' => __('mobile.reports.vendor_summary.total_vendors'),
                    'active_vendors' => __('mobile.reports.vendor_summary.active_vendors'),
                    'vendors_with_payables' => __('mobile.reports.vendor_summary.vendors_with_payables'),
                    'total_payable' => __('mobile.reports.vendor_summary.total_payable') . " ({$currency})",
                    'total_overpaid' => __('mobile.reports.vendor_summary.total_overpaid') . " ({$currency})",
                    'net_balance' => __('mobile.reports.vendor_summary.net_balance') . " ({$currency})",
                    'period_total_credit' => __('mobile.reports.vendor_summary.period_total_credit') . " ({$currency})",
                    'period_total_debit' => __('mobile.reports.vendor_summary.period_total_debit') . " ({$currency})",
                    'period_from' => __('mobile.reports.from_date'),
                    'period_to' => __('mobile.reports.to_date'),
                ],
            ],
        ];
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildCustomersDataset(
        int $businessId,
        ?int $branchId,
        string $businessName,
        string $currency,
        ?Carbon $from,
        ?Carbon $to
    ): array
    {
        $customersQuery = Customer::forBusiness($businessId, $branchId)
            ->orderBy('name')
            ->withSum([
                'transactions as period_debit_total' => function ($query) use ($from, $to) {
                    $query->where('type', 'debit');
                    if ($from) {
                        $query->whereDate('transaction_date', '>=', $from->toDateString());
                    }
                    if ($to) {
                        $query->whereDate('transaction_date', '<=', $to->toDateString());
                    }
                },
            ], 'amount')
            ->withSum([
                'transactions as period_credit_total' => function ($query) use ($from, $to) {
                    $query->where('type', 'credit');
                    if ($from) {
                        $query->whereDate('transaction_date', '>=', $from->toDateString());
                    }
                    if ($to) {
                        $query->whereDate('transaction_date', '<=', $to->toDateString());
                    }
                },
            ], 'amount')
            ->withMax([
                'transactions as last_transaction_date' => function ($query) use ($from, $to) {
                    if ($from) {
                        $query->whereDate('transaction_date', '>=', $from->toDateString());
                    }
                    if ($to) {
                        $query->whereDate('transaction_date', '<=', $to->toDateString());
                    }
                },
            ], 'transaction_date');

        $customers = $customersQuery->get();

        $rows = $customers->map(function (Customer $customer) {
            $balance = (float) $customer->balance;
            $receivable = $balance > 0 ? $balance : 0.0;
            $advance = $balance < 0 ? abs($balance) : 0.0;

            return [
                'name' => $customer->name,
                'phone' => $customer->phone ?? '-',
                'status' => match ($customer->status) {
                    'owes_us' => __('mobile.reports.customer_status.owes_us'),
                    'we_owe' => __('mobile.reports.customer_status.we_owe'),
                    default => __('mobile.reports.customer_status.settled'),
                },
                'balance' => round($balance, 2),
                'receivable' => round($receivable, 2),
                'advance' => round($advance, 2),
                'period_debit' => round((float) ($customer->period_debit_total ?? 0), 2),
                'period_credit' => round((float) ($customer->period_credit_total ?? 0), 2),
                'last_transaction_date' => $customer->last_transaction_date
                    ? Carbon::parse($customer->last_transaction_date)->toDateString()
                    : '-',
            ];
        })->values()->all();

        $totalReceivable = (float) $customers->filter(fn (Customer $c) => (float) $c->balance > 0)->sum('balance');
        $totalAdvance = (float) $customers->filter(fn (Customer $c) => (float) $c->balance < 0)->sum(function (Customer $c) {
            return abs((float) $c->balance);
        });
        $netBalance = (float) $customers->sum('balance');

        return [
            'title' => __('mobile.reports.customer_report_title'),
            'columns' => [
                'name' => __('mobile.reports.customer_columns.name'),
                'phone' => __('mobile.reports.customer_columns.phone'),
                'status' => __('mobile.reports.customer_columns.status'),
                'balance' => __('mobile.reports.customer_columns.balance') . " ({$currency})",
                'receivable' => __('mobile.reports.customer_columns.receivable') . " ({$currency})",
                'advance' => __('mobile.reports.customer_columns.advance') . " ({$currency})",
                'period_debit' => __('mobile.reports.customer_columns.period_debit') . " ({$currency})",
                'period_credit' => __('mobile.reports.customer_columns.period_credit') . " ({$currency})",
                'last_transaction_date' => __('mobile.reports.customer_columns.last_transaction_date'),
            ],
            'rows' => $rows,
            'summary' => [
                'total_customers' => $customers->count(),
                'active_customers' => $customers->where('is_active', true)->count(),
                'customers_with_receivables' => $customers->filter(fn (Customer $c) => (float) $c->balance > 0)->count(),
                'total_receivable' => round($totalReceivable, 2),
                'total_advance' => round($totalAdvance, 2),
                'net_balance' => round($netBalance, 2),
                'period_total_debit' => round((float) $customers->sum(fn (Customer $c) => (float) ($c->period_debit_total ?? 0)), 2),
                'period_total_credit' => round((float) $customers->sum(fn (Customer $c) => (float) ($c->period_credit_total ?? 0)), 2),
                'period_from' => $from?->toDateString(),
                'period_to' => $to?->toDateString(),
            ],
            'meta' => [
                'report_key' => 'customers',
                'branch_id' => $branchId,
                'business_name' => $businessName,
                'currency' => $currency,
                'generated_label' => __('mobile.reports.generated_at'),
                'generated_at_display' => now()->format('d/m/Y, H:i:s'),
                'app_name' => __('mobile.app.name'),
                'summary_labels' => [
                    'total_customers' => __('mobile.reports.customer_summary.total_customers'),
                    'active_customers' => __('mobile.reports.customer_summary.active_customers'),
                    'customers_with_receivables' => __('mobile.reports.customer_summary.customers_with_receivables'),
                    'total_receivable' => __('mobile.reports.customer_summary.total_receivable') . " ({$currency})",
                    'total_advance' => __('mobile.reports.customer_summary.total_advance') . " ({$currency})",
                    'net_balance' => __('mobile.reports.customer_summary.net_balance') . " ({$currency})",
                    'period_total_debit' => __('mobile.reports.customer_summary.period_total_debit') . " ({$currency})",
                    'period_total_credit' => __('mobile.reports.customer_summary.period_total_credit') . " ({$currency})",
                    'period_from' => __('mobile.reports.from_date'),
                    'period_to' => __('mobile.reports.to_date'),
                ],
            ],
        ];
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildProfitLossDataset(int $businessId, ?int $branchId, ?Carbon $from, ?Carbon $to): array
    {
        $periodFrom = $from ?? Carbon::now()->startOfMonth();
        $periodTo = $to ?? Carbon::now()->endOfMonth();

        $incomeByCategory = IncomeTransaction::forBusiness($businessId, $branchId)
            ->dateRange($periodFrom, $periodTo)
            ->selectRaw('COALESCE(category, "other") as category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        $expenseByCategory = ExpenseTransaction::forBusiness($businessId, $branchId)
            ->dateRange($periodFrom, $periodTo)
            ->selectRaw('COALESCE(category, "other") as category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        $rows = [];

        foreach ($incomeByCategory as $income) {
            $rows[] = [
                'entry_type' => 'income',
                'category' => (string) $income->category,
                'amount' => (float) $income->total,
            ];
        }

        foreach ($expenseByCategory as $expense) {
            $rows[] = [
                'entry_type' => 'expense',
                'category' => (string) $expense->category,
                'amount' => (float) $expense->total,
            ];
        }

        $totalIncome = (float) $incomeByCategory->sum('total');
        $totalExpense = (float) $expenseByCategory->sum('total');
        $profitLoss = $totalIncome - $totalExpense;
        $profitMarginPercent = $totalIncome > 0
            ? round(($profitLoss / $totalIncome) * 100, 2)
            : 0.0;

        return [
            'title' => 'Profit & Loss Report',
            'columns' => [
                'entry_type' => 'Type',
                'category' => 'Category',
                'amount' => 'Amount',
            ],
            'rows' => $rows,
            'summary' => [
                'period_from' => $periodFrom->toDateString(),
                'period_to' => $periodTo->toDateString(),
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'profit_loss' => $profitLoss,
                'profit_margin_percent' => (float) $profitMarginPercent,
            ],
            'meta' => [
                'branch_id' => $branchId,
            ],
        ];
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildActivityDataset(int $businessId, ?int $branchId, ?Carbon $from, ?Carbon $to): array
    {
        $rows = collect();

        $incomeQuery = IncomeTransaction::forBusiness($businessId, $branchId)->with('createdBy:id,name');
        if ($from) {
            $incomeQuery->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $incomeQuery->whereDate('transaction_date', '<=', $to);
        }

        foreach ($incomeQuery->latest('transaction_date')->take(500)->get() as $income) {
            $rows->push([
                'type' => 'income',
                'date' => $income->transaction_date?->toDateString(),
                'description' => $income->description ?? 'Income recorded',
                'amount' => (float) $income->amount,
                'quantity' => null,
                'reference' => $income->reference,
                'created_by' => $income->createdBy?->name,
                'timestamp' => optional($income->created_at)->toIso8601String(),
            ]);
        }

        $expenseQuery = ExpenseTransaction::forBusiness($businessId, $branchId)->with('createdBy:id,name');
        if ($from) {
            $expenseQuery->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $expenseQuery->whereDate('transaction_date', '<=', $to);
        }

        foreach ($expenseQuery->latest('transaction_date')->take(500)->get() as $expense) {
            $rows->push([
                'type' => 'expense',
                'date' => $expense->transaction_date?->toDateString(),
                'description' => $expense->description ?? 'Expense recorded',
                'amount' => (float) $expense->amount,
                'quantity' => null,
                'reference' => $expense->reference,
                'created_by' => $expense->createdBy?->name,
                'timestamp' => optional($expense->created_at)->toIso8601String(),
            ]);
        }

        $vendorQuery = VendorTransaction::query()
            ->whereHas('vendor', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['vendor:id,name', 'createdBy:id,name']);

        if ($from) {
            $vendorQuery->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $vendorQuery->whereDate('transaction_date', '<=', $to);
        }

        foreach ($vendorQuery->latest('transaction_date')->take(500)->get() as $vendorTransaction) {
            $isCredit = $vendorTransaction->type === 'credit';
            $rows->push([
                'type' => $isCredit ? 'vendor_credit' : 'vendor_debit',
                'date' => $vendorTransaction->transaction_date?->toDateString(),
                'description' => $vendorTransaction->description ?: ($isCredit ? 'Vendor credited' : 'Vendor debited'),
                'amount' => (float) $vendorTransaction->amount,
                'quantity' => null,
                'reference' => $vendorTransaction->vendor?->name,
                'created_by' => $vendorTransaction->createdBy?->name,
                'timestamp' => optional($vendorTransaction->created_at)->toIso8601String(),
            ]);
        }

        $stockQuery = StockMovement::query()
            ->whereHas('stockItem', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['stockItem:id,name,sku', 'createdBy:id,name']);

        if ($from) {
            $stockQuery->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $stockQuery->whereDate('created_at', '<=', $to);
        }

        foreach ($stockQuery->latest()->take(500)->get() as $movement) {
            $isIncrease = $movement->type === 'increase';
            $rows->push([
                'type' => $isIncrease ? 'stock_increase' : 'stock_decrease',
                'date' => optional($movement->created_at)->toDateString(),
                'description' => $movement->reason ?: ($isIncrease ? 'Stock increased' : 'Stock decreased'),
                'amount' => null,
                'quantity' => (float) $movement->quantity,
                'reference' => $movement->stockItem?->name,
                'created_by' => $movement->createdBy?->name,
                'timestamp' => optional($movement->created_at)->toIso8601String(),
            ]);
        }

        $sorted = $rows->sortByDesc('timestamp')->values();

        return [
            'title' => 'Activity Report',
            'columns' => [
                'type' => 'Type',
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
                'quantity' => 'Quantity',
                'reference' => 'Reference',
                'created_by' => 'Created By',
            ],
            'rows' => $sorted->map(function (array $row) {
                unset($row['timestamp']);
                return $row;
            })->all(),
            'summary' => [
                'total_records' => $sorted->count(),
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'meta' => [
                'branch_id' => $branchId,
            ],
        ];
    }
}
