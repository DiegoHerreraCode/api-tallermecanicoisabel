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
        Schema::table('ingresos', function (Blueprint $table) {
            if (Schema::hasColumn('ingresos', 'pdf_checklist')) {
                $table->dropColumn('pdf_checklist');
            }
            if (!Schema::hasColumn('ingresos', 'image')) {
                $table->string('image')->nullable()->after('observaciones_cliente');
            }
            if (!Schema::hasColumn('ingresos', 'imagePath')) {
                $table->string('imagePath')->nullable()->after('image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            if (Schema::hasColumn('ingresos', 'image')) {
                $table->dropColumn('image');
            }
            if (Schema::hasColumn('ingresos', 'imagePath')) {
                $table->dropColumn('imagePath');
            }
            if (!Schema::hasColumn('ingresos', 'pdf_checklist')) {
                $table->string('pdf_checklist')->nullable()->after('observaciones_cliente');
            }
        });
    }
};

