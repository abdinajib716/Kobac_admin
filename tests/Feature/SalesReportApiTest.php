<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockItem;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_items_report_groups_rows_by_product_with_date_filters(): void
    {
        [$user, $branch, $business] = $this->createBusinessContext();

        $stockA = StockItem::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'name' => 'Rice',
            'quantity' => 100,
            'cost_price' => 4,
            'selling_price' => 6,
            'is_active' => true,
        ]);

        $stockB = StockItem::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'name' => 'Sugar',
            'quantity' => 100,
            'cost_price' => 3,
            'selling_price' => 5,
            'is_active' => true,
        ]);

        $sale = Sale::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'status' => Sale::STATUS_COMPLETED,
            'sale_type' => 'cash',
            'payment_status' => 'paid',
            'subtotal' => 22,
            'total' => 22,
            'amount_paid' => 22,
            'cost_total' => 14,
            'profit_total' => 8,
            'sold_at' => '2026-04-15 10:00:00',
            'created_by' => $user->id,
            'completed_at' => '2026-04-15 10:00:00',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'stock_item_id' => $stockA->id,
            'product_name_snapshot' => 'Rice',
            'unit_snapshot' => 'pcs',
            'quantity' => 2,
            'cost_price_snapshot' => 4,
            'unit_price' => 6,
            'line_total' => 12,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'stock_item_id' => $stockB->id,
            'product_name_snapshot' => 'Sugar',
            'unit_snapshot' => 'pcs',
            'quantity' => 2,
            'cost_price_snapshot' => 3,
            'unit_price' => 5,
            'line_total' => 10,
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeaders([
            'X-Branch-ID' => $branch->id,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/business/sales/reports/items?from=2026-04-01&to=2026-04-30');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'filters' => [
                        'from' => '2026-04-01',
                        'to' => '2026-04-30',
                    ],
                    'summary' => [
                        'total_products' => 2,
                        'total_qty' => 4,
                        'total_price' => 22,
                        'total_cost' => 14,
                        'total_profit' => 8,
                    ],
                ],
            ]);

        $rows = collect($response->json('data.rows'));

        $this->assertSame(2, $rows->count());
        $this->assertSame(4.0, (float) $response->json('data.summary.total_qty'));
        $this->assertSame(8.0, (float) $rows->firstWhere('product', 'Rice')['profit']);
    }

    public function test_sales_items_report_export_returns_shareable_file_urls(): void
    {
        [$user, $branch] = $this->createBusinessContext();

        Sanctum::actingAs($user);

        $response = $this->withHeaders([
            'X-Branch-ID' => $branch->id,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/business/sales/reports/items/export?from=2026-04-01&to=2026-04-30&format=pdf');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'report' => 'sales_items',
                    'format' => 'pdf',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'download_url',
                    'share_url',
                    'print_url',
                    'file_path',
                    'mime_type',
                    'summary',
                ],
            ]);
    }

    /**
     * @return array{0: User, 1: Branch, 2: Business}
     */
    private function createBusinessContext(): array
    {
        $user = User::factory()->create([
            'user_type' => User::TYPE_BUSINESS,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 9.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'trial_enabled' => true,
            'trial_days' => 14,
            'features' => [
                'sales' => true,
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Demo Store',
            'currency' => 'USD',
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'HQ',
            'is_main' => true,
            'is_active' => true,
        ]);

        return [$user, $branch, $business];
    }
}
