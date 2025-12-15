<?php
// database/migrations/[timestamp]_create_reservation_stocks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationStocksTable extends Migration
{
    public function up()
    {
        Schema::create('reservation_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('document_no')->index();
            $table->string('matnr', 50); // Material Number
            $table->string('mtbez', 100)->nullable(); // Material Type Description
            $table->string('maktx', 255)->nullable(); // Material Description
            $table->string('werk', 10); // Plant
            $table->string('lgort', 10)->nullable(); // Storage Location
            $table->string('charg', 50)->nullable(); // Batch
            $table->decimal('clabs', 15, 3)->default(0); // Unrestricted Stock
            $table->string('meins', 10)->nullable(); // Base Unit of Measure
            $table->string('vbeln', 50)->nullable(); // Sales Document
            $table->string('posnr', 10)->nullable(); // Sales Document Item
            $table->timestamp('stock_date')->nullable();
            $table->integer('sync_by')->nullable();
            $table->timestamp('sync_at')->nullable();
            $table->timestamps();

            $table->index(['document_no', 'matnr']);
            $table->index(['werk', 'matnr', 'lgort']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservation_stocks');
    }
}
