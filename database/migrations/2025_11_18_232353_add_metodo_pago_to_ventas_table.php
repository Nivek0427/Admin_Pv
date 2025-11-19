<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('ventas', function (Blueprint $table) {
        $table->string('metodo_pago')->nullable();
    });
}

public function down()
{
    Schema::table('ventas', function (Blueprint $table) {
        $table->dropColumn('metodo_pago');
    });
}

};
