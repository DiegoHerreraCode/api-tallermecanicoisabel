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
        Schema::table('hallazgos_tecnicos', function (Blueprint $table) {
            if (!Schema::hasColumn('hallazgos_tecnicos', 'id_ingreso')) {
                $table->unsignedBigInteger('id_ingreso')->nullable()->after('id_orden');
                $table->foreign('id_ingreso')
                    ->references('id_ingreso')
                    ->on('ingresos')
                    ->onDelete('cascade');
            }

            // Asegurar que id_orden sea nullable para permitir hallazgos asociados solo a un ingreso
            $table->unsignedBigInteger('id_orden')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hallazgos_tecnicos', function (Blueprint $table) {
            if (Schema::hasColumn('hallazgos_tecnicos', 'id_ingreso')) {
                $table->dropForeign(['id_ingreso']);
                $table->dropColumn('id_ingreso');
            }
        });
    }
};
