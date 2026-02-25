<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\StockItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StockController extends BaseController
{
    /**
     * List stock items
     * GET /api/v1/business/stock
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();
        $perPage = min($request->get('per_page', 20), 50);

        $query = StockItem::forBusiness($business->id, $request->get('branch_id'))
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->when($request->boolean('low_stock_only'), fn ($q) => $q->lowStock())
            ->when($request->has('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });

        $paginator = $query->orderBy('name')->paginate($perPage);

        $data = $paginator->getCollection()->map(fn ($item) => $this->formatStockItem($item));

        // Summary
        $allItems = StockItem::forBusiness($business->id, $request->get('branch_id'))->active()->get();
        $totalCostValue = $allItems->sum(fn ($item) => $item->quantity * ($item->cost_price ?? 0));
        $totalSellingValue = $allItems->sum(fn ($item) => $item->quantity * ($item->selling_price ?? 0));
        $lowStockCount = $allItems->filter(fn ($item) => $item->is_low_stock)->count();

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'total_items' => $allItems->count(),
                'total_quantity' => (float) $allItems->sum('quantity'),
                'total_value' => (float) $totalCostValue,
                'total_cost_value' => (float) $totalCostValue,
                'total_selling_value' => (float) $totalSellingValue,
                'low_stock_count' => $lowStockCount,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create stock item
     * POST /api/v1/business/stock
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'nullable|numeric|min:0',
            'alert_threshold' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $business = $this->business();
        $data = $validator->validated();
        $resolvedBranchId = $data['branch_id'] ?? $this->branchId();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('stock-images', 'public');
        }

        $stockItem = StockItem::create([
            'business_id' => $business->id,
            'branch_id' => $resolvedBranchId,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'alert_threshold' => $data['alert_threshold'] ?? null,
            'unit' => $data['unit'] ?? 'pcs',
            'cost_price' => $data['cost_price'] ?? null,
            'selling_price' => $data['selling_price'] ?? null,
            'image' => $data['image'] ?? null,
            'is_active' => true,
        ]);

        ActivityLogger::stock('created', $stockItem, [
            'business_id' => $business->id,
            'branch_id' => $stockItem->branch_id,
            'stock_item_id' => $stockItem->id,
            'name' => $stockItem->name,
            'sku' => $stockItem->sku,
            'quantity' => (float) $stockItem->quantity,
        ]);

        return $this->success($this->formatStockItem($stockItem), 'Stock item created successfully', 201);
    }

    /**
     * Show stock item
     * GET /api/v1/business/stock/{stock}
     */
    public function show(StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        return $this->success($this->formatStockItem($stock));
    }

    /**
     * Update stock item
     * PUT /api/v1/business/stock/{stock}
     */
    public function update(Request $request, StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|nullable|string|max:50',
            'description' => 'sometimes|nullable|string|max:1000',
            'alert_threshold' => 'sometimes|nullable|numeric|min:0',
            'unit' => 'sometimes|nullable|string|max:20',
            'cost_price' => 'sometimes|nullable|numeric|min:0',
            'selling_price' => 'sometimes|nullable|numeric|min:0',
            'image' => 'sometimes|nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('stock-images', 'public');
        }

        $stock->update($data);

        ActivityLogger::stock('updated', $stock, [
            'business_id' => $stock->business_id,
            'branch_id' => $stock->branch_id,
            'stock_item_id' => $stock->id,
            'name' => $stock->name,
            'sku' => $stock->sku,
            'quantity' => (float) $stock->quantity,
        ]);

        return $this->success($this->formatStockItem($stock), 'Stock item updated successfully');
    }

    /**
     * Delete stock item
     * DELETE /api/v1/business/stock/{stock}
     */
    public function destroy(StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        if ($stock->movements()->exists()) {
            return $this->error('Cannot delete stock with movements. Deactivate instead.', 'HAS_MOVEMENTS', 400);
        }

        $deletedPayload = [
            'business_id' => $stock->business_id,
            'branch_id' => $stock->branch_id,
            'stock_item_id' => $stock->id,
            'name' => $stock->name,
            'sku' => $stock->sku,
            'quantity' => (float) $stock->quantity,
        ];

        $stock->delete();

        ActivityLogger::stock('deleted', null, $deletedPayload);

        return $this->success(null, 'Stock item deleted successfully');
    }

    /**
     * Increase stock quantity
     * POST /api/v1/business/stock/{stock}/increase
     * NOTE: Does NOT create expense
     */
    public function increase(Request $request, StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();
        $previousQuantity = $stock->quantity;

        $movement = $stock->increase(
            $data['quantity'],
            $data['reason'] ?? 'Stock increase',
            $this->user()->id,
            $data['reference'] ?? null
        );

        return $this->success([
            'item' => [
                'id' => $stock->id,
                'name' => $stock->name,
                'previous_quantity' => (float) $previousQuantity,
                'new_quantity' => (float) $stock->quantity,
                'is_low_stock' => $stock->is_low_stock,
            ],
            'movement' => [
                'id' => $movement->id,
                'type' => 'increase',
                'quantity' => (float) $data['quantity'],
                'reason' => $data['reason'] ?? 'Stock increase',
            ],
        ], 'Stock increased successfully', 201);
    }

    /**
     * Decrease stock quantity
     * POST /api/v1/business/stock/{stock}/decrease
     * NOTE: Does NOT create income
     */
    public function decrease(Request $request, StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();

        if ($data['quantity'] > $stock->quantity) {
            return $this->error('Insufficient stock quantity', 'INSUFFICIENT_STOCK', 400);
        }

        $previousQuantity = $stock->quantity;

        $movement = $stock->decrease(
            $data['quantity'],
            $data['reason'] ?? 'Stock decrease',
            $this->user()->id,
            $data['reference'] ?? null
        );

        return $this->success([
            'item' => [
                'id' => $stock->id,
                'name' => $stock->name,
                'previous_quantity' => (float) $previousQuantity,
                'new_quantity' => (float) $stock->quantity,
                'is_low_stock' => $stock->is_low_stock,
            ],
            'movement' => [
                'id' => $movement->id,
                'type' => 'decrease',
                'quantity' => (float) $data['quantity'],
                'reason' => $data['reason'] ?? 'Stock decrease',
            ],
        ], 'Stock decreased successfully', 201);
    }

    /**
     * Deactivate stock item (soft delete)
     * POST /api/v1/business/stock/{stock}/deactivate
     */
    public function deactivate(StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        $stock->update(['is_active' => false]);

        ActivityLogger::stock('updated', $stock, [
            'business_id' => $stock->business_id,
            'branch_id' => $stock->branch_id,
            'stock_item_id' => $stock->id,
            'name' => $stock->name,
            'sku' => $stock->sku,
            'quantity' => (float) $stock->quantity,
        ]);

        return $this->success($this->formatStockItem($stock), 'Stock item deactivated successfully');
    }

    /**
     * Activate stock item
     * POST /api/v1/business/stock/{stock}/activate
     */
    public function activate(StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        $stock->update(['is_active' => true]);

        ActivityLogger::stock('updated', $stock, [
            'business_id' => $stock->business_id,
            'branch_id' => $stock->branch_id,
            'stock_item_id' => $stock->id,
            'name' => $stock->name,
            'sku' => $stock->sku,
            'quantity' => (float) $stock->quantity,
        ]);

        return $this->success($this->formatStockItem($stock), 'Stock item activated successfully');
    }

    /**
     * Get stock movement history
     * GET /api/v1/business/stock/{stock}/movements
     */
    public function movements(Request $request, StockItem $stock): JsonResponse
    {
        $this->authorizeStock($stock);

        $perPage = min((int) $request->get('per_page', 20), 50);
        $from = $request->get('from');
        $to = $request->get('to');

        $query = $stock->movements()
            ->with('createdBy:id,name')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        $movements = $paginator->getCollection()->map(fn ($m) => [
            'id' => $m->id,
            'type' => $m->type,
            'quantity' => (float) $m->quantity,
            'quantity_before' => (float) $m->quantity_before,
            'quantity_after' => (float) $m->quantity_after,
            'reason' => $m->reason,
            'reference' => $m->reference,
            'created_by' => $m->createdBy?->name,
            'created_at' => $m->created_at->toIso8601String(),
        ]);

        return $this->success([
            'item' => $this->formatStockItem($stock),
            'movements' => $movements,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'default_per_page' => 20,
                'max_per_page' => 50,
            ],
        ]);
    }

    private function authorizeStock(StockItem $stock): void
    {
        if ($stock->business_id !== $this->business()?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatStockItem(StockItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'alert_threshold' => $item->alert_threshold ? (float) $item->alert_threshold : null,
            'is_low_stock' => $item->is_low_stock,
            'unit' => $item->unit,
            'cost_price' => $item->cost_price ? (float) $item->cost_price : null,
            'selling_price' => $item->selling_price ? (float) $item->selling_price : null,
            'image' => $item->image ? asset('storage/' . $item->image) : null,
            'branch_id' => $item->branch_id,
            'branch_name' => $item->branch?->name,
            'is_active' => $item->is_active,
            'created_at' => $item->created_at->toIso8601String(),
        ];
    }
}
