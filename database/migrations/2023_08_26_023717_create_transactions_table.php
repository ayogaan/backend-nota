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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_good');
            $table->unsignedBigInteger('id_notes');
            $table->unsignedBigInteger('id_project');
            
            $table->integer('quantity');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('total_cuts', 10, 2)->nullable();

            // Add other fields as needed
            $table->timestamps();

            // Add foreign key constraints if required
            $table->foreign('id_good')->references('id')->on('goods')->onDelete('cascade');
            $table->foreign('id_notes')->references('id')->on('notes')->onDelete('cascade');
            $table->foreign('id_project')->references('id')->on('projects')->onDelete('cascade');
            

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
