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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->onDelete('cascade');
            $table->string('name_en');
            $table->string('name_sk');
            $table->text('description_en')->nullable();
            $table->text('description_sk')->nullable();
            $table->string('blockchain_candidate_id')->nullable()->comment('Hex ID used on blockchain');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('election_id');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
