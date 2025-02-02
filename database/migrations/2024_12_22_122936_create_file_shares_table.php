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
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->text('link')->nullable();
            $table->smallInteger('link_type')->comment('1 = public, 2 = private');
            $table->smallInteger('access_type')->comment('1 = editor, 2 = viewer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_shares');
    }
};
