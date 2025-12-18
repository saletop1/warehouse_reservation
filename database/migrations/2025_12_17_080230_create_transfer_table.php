<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('transfers', function (Blueprint $table) {
        //     $table->id();
        //     // $table->foreignId('document_id')->constrained()->onDelete('cascade');
        //     $table->string('document_no', 50);
        //     $table->string('transfer_no', 50)->nullable()->index();
        //     $table->string('plant_supply', 10);
        //     $table->string('plant_destination', 10);
        //     $table->string('move_type', 10)->default('311');
        //     $table->integer('total_items')->default(0);
        //     $table->decimal('total_quantity', 15, 2)->default(0);
        //     $table->string('status', 50)->default('SUBMITTED')->index();
        //     $table->text('sap_message')->nullable();
        //     $table->text('remarks')->nullable();
        //     $table->foreignId('created_by')->nullable()->constrained('users');
        //     $table->string('created_by_name', 100)->nullable();
        //     $table->timestamp('completed_at')->nullable();
        //     $table->json('sap_response')->nullable();
        //     $table->timestamps();

        //     $table->index(['status', 'created_at']);
        //     $table->index(['transfer_no', 'document_no']);
        // });

        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->onDelete('cascade');
            $table->string('material_code', 50);
            $table->string('material_description', 255)->nullable();
            $table->string('batch', 50)->nullable();
            $table->string('batch_sloc', 10)->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->string('unit', 10)->default('PC');
            $table->string('plant_supply', 10);
            $table->string('plant_destination', 10);
            $table->string('sloc_destination', 10)->nullable();
            $table->decimal('requested_qty', 15, 3)->default(0);
            $table->decimal('available_stock', 15, 3)->default(0);
            $table->string('sap_status', 50)->default('SUBMITTED');
            $table->text('sap_message')->nullable();
            $table->integer('item_number')->default(0);
            $table->timestamps();

            $table->index(['transfer_id', 'material_code']);
            $table->index(['sap_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
    }
};
