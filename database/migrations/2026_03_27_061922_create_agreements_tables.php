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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // document name
            $table->string('pdf_path'); // path to pdf file in storage
            $table->timestamps();
        });

        Schema::create('agreement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Client or Tutor

            // ── Status: "Awaiting signature" or "Signed"
            $table->string('status')->default('Awaiting signature');

            // ── Signature fields: data entered by the user when signing
            $table->string('signed_full_name')->nullable();
            $table->date('signed_date_manual')->nullable(); // Entered "by hands"
            $table->timestamp('signed_at')->nullable(); // Accurate system timestamp

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
        Schema::dropIfExists('agreement_requests');
    }
};
