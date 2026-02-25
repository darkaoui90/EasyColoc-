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
        Schema::create('colocation_user', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('colocation_id')->constrained('colocations')->cascadeOnDelete() ; 
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role')->default('member');  // owner/member

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            //Prevent the same user being attached twice to same colocation
            $table->unique(['colocation_id', 'user_id']);
            





        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colocation_user');
    }
};
