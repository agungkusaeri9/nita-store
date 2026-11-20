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
        Schema::dropIfExists('product_virtual_try_ons');
        Schema::dropIfExists('virtual_try_on_models');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'is_virtual_try_on')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_virtual_try_on');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'is_virtual_try_on')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_virtual_try_on')->default(false)->after('weight');
            });
        }
    }
};
