<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->unique();
            $t->string('region')->index();
            $t->string('address')->nullable();
            $t->string('city')->nullable();
            $t->string('manager_name')->nullable();
            $t->string('status')->default('active'); // active, onboarding, suspended, closed
            $t->date('opened_on')->nullable();
            $t->decimal('royalty_rate', 5, 2)->default(6.00);       // % of gross sales
            $t->decimal('marketing_fee_rate', 5, 2)->default(2.00); // % of gross sales
            $t->decimal('tech_fee', 10, 2)->default(150.00);        // flat monthly
            $t->timestamps();
        });

        Schema::create('sales_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->constrained()->cascadeOnDelete();
            $t->date('period'); // first day of month
            $t->decimal('gross_sales', 12, 2)->default(0);
            $t->decimal('cost_of_goods', 12, 2)->default(0);
            $t->decimal('labor_cost', 12, 2)->default(0);
            $t->decimal('other_expenses', 12, 2)->default(0);
            $t->unsignedInteger('transactions')->default(0);
            $t->decimal('customer_rating', 3, 2)->nullable();
            $t->timestamps();
            $t->unique(['location_id', 'period']);
        });

        Schema::create('royalty_statements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->constrained()->cascadeOnDelete();
            $t->date('period');
            $t->decimal('gross_sales', 12, 2);
            $t->decimal('royalty_amount', 12, 2);
            $t->decimal('marketing_fee', 12, 2);
            $t->decimal('tech_fee', 12, 2);
            $t->decimal('total_due', 12, 2);
            $t->string('status')->default('pending'); // pending, paid, overdue
            $t->date('due_date');
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->unique(['location_id', 'period']);
        });

        Schema::create('compliance_items', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('category'); // safety, brand, financial, operations
            $t->string('frequency')->default('monthly'); // weekly, monthly, quarterly, annual
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('compliance_checks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->constrained()->cascadeOnDelete();
            $t->foreignId('compliance_item_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('pending'); // pending, passed, failed
            $t->date('due_date');
            $t->date('checked_on')->nullable();
            $t->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('location_id')->nullable()->constrained()->cascadeOnDelete(); // null = broadcast
            $t->foreignId('parent_id')->nullable()->constrained('messages')->cascadeOnDelete();
            $t->string('subject')->nullable();
            $t->text('body');
            $t->boolean('is_announcement')->default(false);
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->string('sku')->unique();
            $t->string('name');
            $t->string('category')->nullable();
            $t->string('unit')->default('each');
            $t->decimal('unit_price', 10, 2);
            $t->integer('stock')->default(0);
            $t->integer('reorder_level')->default(10);
            $t->timestamps();
        });

        Schema::create('supply_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('submitted'); // submitted, approved, shipped, delivered, cancelled
            $t->decimal('total', 12, 2)->default(0);
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('supply_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('supply_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 10, 2);
            $t->timestamps();
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('location_id')->nullable()->constrained()->cascadeOnDelete(); // null = network-wide
            $t->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->string('type')->default('contract'); // contract, policy, license, insurance, other
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->date('expires_on')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['documents', 'supply_order_items', 'supply_orders', 'inventory_items', 'messages',
                  'compliance_checks', 'compliance_items', 'royalty_statements', 'sales_records', 'locations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
