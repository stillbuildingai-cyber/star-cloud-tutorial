<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('code')->unique()->comment('金流代碼');
            $table->string('name')->comment('中文名稱');
            $table->string('category')->nullable()->comment('大分類');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('flow_id')->nullable()->unique()->comment('Cloud 金流 ID (B600 response)');
            $table->string('order_no')->nullable()->index()->comment('APP 訂單號 (B600 req9)');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignId('member_id')->nullable()->constrained('members')->onDelete('set null');
            $table->smallInteger('payment_type')->comment('金流類型碼');
            $table->decimal('total_amount', 10, 2)->comment('消費金額');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('折扣金額');
            $table->decimal('pay_amount', 10, 2)->comment('實付金額');
            $table->decimal('change_amount', 10, 2)->default(0)->comment('找零');
            $table->integer('points_used')->default(0)->comment('使用點數');
            $table->decimal('original_amount', 10, 2)->nullable()->comment('折扣前金額');
            $table->tinyInteger('payment_status')->comment('0:失敗/1:成功');
            $table->text('payment_request')->nullable()->comment('金流送出 data');
            $table->text('payment_response')->nullable()->comment('金流回傳 data');
            $table->datetime('payment_at')->nullable()->comment('支付時間');
            $table->string('member_barcode')->nullable()->comment('會員條碼');
            $table->string('invoice_info')->nullable()->comment('發票歸戶');
            $table->datetime('machine_time')->nullable()->comment('機台時間');
            $table->string('status')->default('completed')->comment('訂單狀態');
            $table->json('metadata')->nullable()->comment('其他資訊');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'machine_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_name')->nullable()->comment('商品名稱 (備份)');
            $table->string('sku')->nullable()->comment('商品編號 (備份)');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('flow_id')->index()->comment('金流 ID');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('rtn_code')->nullable();
            $table->string('rtn_msg')->nullable();
            $table->string('invoice_no')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('carrier_id')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('random_number')->nullable();
            $table->string('love_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dispense_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('flow_id')->nullable()->index()->comment('金流 ID');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('product_id')->comment('機台端傳入之商品 ID');
            $table->string('slot_no')->comment('貨道編號');
            $table->decimal('amount', 10, 2);
            $table->integer('remaining_stock')->nullable();
            $table->tinyInteger('dispense_status')->comment('0:成功/1:失敗');
            $table->string('member_barcode')->nullable();
            $table->datetime('machine_time')->nullable();
            $table->integer('points_used')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'machine_id', 'flow_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispense_records');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('payment_types');
    }
};
