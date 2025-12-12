<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlocSupplyToReservationDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('reservation_documents', function (Blueprint $table) {
            $table->string('sloc_supply', 20)->nullable()->after('plant');
        });
    }

    public function down()
    {
        Schema::table('reservation_documents', function (Blueprint $table) {
            $table->dropColumn('sloc_supply');
        });
    }
}
