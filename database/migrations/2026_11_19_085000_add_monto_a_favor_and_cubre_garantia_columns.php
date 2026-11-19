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
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'monto_pendiente') && !Schema::hasColumn('ordenes', 'monto_por_cobrar')) {
                $table->renameColumn('monto_pendiente', 'monto_por_cobrar');
            } elseif (Schema::hasColumn('ordenes', 'monto_a_cobrar') && !Schema::hasColumn('ordenes', 'monto_por_cobrar')) {
                $table->renameColumn('monto_a_cobrar', 'monto_por_cobrar');
            }
            if (!Schema::hasColumn('ordenes', 'monto_a_favor')) {
                $table->decimal('monto_a_favor', 12, 2)->default(0.00)->after('monto_por_cobrar');
            }
        });

        Schema::table('ordenes_repuestos', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_repuestos', 'cubre_garantia')) {
                $table->dropColumn('cubre_garantia');
            }
            if (!Schema::hasColumn('ordenes_repuestos', 'cantidad_cubierta_garantia')) {
                $table->integer('cantidad_cubierta_garantia')->default(0)->after('cantidad');
            }
        });

        Schema::table('ordenes_servicios_taller', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_servicios_taller', 'cubre_garantia')) {
                $table->dropColumn('cubre_garantia');
            }
            if (!Schema::hasColumn('ordenes_servicios_taller', 'cantidad_cubierta_garantia')) {
                $table->integer('cantidad_cubierta_garantia')->default(0)->after('cantidad');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes', 'monto_por_cobrar')) {
                $table->renameColumn('monto_por_cobrar', 'monto_pendiente');
            }
            if (Schema::hasColumn('ordenes', 'monto_a_favor')) {
                $table->dropColumn('monto_a_favor');
            }
        });

        Schema::table('ordenes_repuestos', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_repuestos', 'cantidad_cubierta_garantia')) {
                $table->dropColumn('cantidad_cubierta_garantia');
            }
        });

        Schema::table('ordenes_servicios_taller', function (Blueprint $table) {
            if (Schema::hasColumn('ordenes_servicios_taller', 'cantidad_cubierta_garantia')) {
                $table->dropColumn('cantidad_cubierta_garantia');
            }
        });
    }
};
