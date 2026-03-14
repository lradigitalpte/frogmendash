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
        if (Schema::hasTable('payments_payment_transactions')) {
            return;
        }

        Schema::create('payments_payment_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sort')->nullable()->comment('Sort Order');
            if (Schema::hasTable('accounts_account_moves')) {
                $table->foreignId('move_id')->comment('Journal Entry')->constrained('accounts_account_moves')->restrictOnDelete();
            } else {
                $table->unsignedBigInteger('move_id')->comment('Journal Entry');
            }

            if (Schema::hasTable('accounts_journals')) {
                $table->foreignId('journal_id')->nullable()->comment('Journal')->constrained('accounts_journals')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('journal_id')->nullable()->comment('Journal');
            }
            $table->foreignId('company_id')->nullable()->comment('Company')->constrained('companies')->nullOnDelete();

            if (Schema::hasTable('accounts_bank_statements')) {
                $table->foreignId('statement_id')->nullable()->comment('Bank Statement')->constrained('accounts_bank_statements')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('statement_id')->nullable()->comment('Bank Statement');
            }

            $table->foreignId('partner_id')->nullable()->comment('Partner')->constrained('partners_partners')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->comment('Currency')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('foreign_currency_id')->nullable()->comment('Foreign Currency')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('created_id')->nullable()->comment('Created By')->constrained('users')->nullOnDelete();
            $table->string('account_number')->nullable()->comment('Account Number');
            $table->string('partner_name')->nullable()->comment('Partner Name');
            $table->string('transaction_type')->nullable()->comment('Transaction Type');
            $table->string('payment_reference')->nullable()->comment('Payment Reference');
            $table->string('internal_index')->nullable()->comment('Internal Index');
            $table->json('transaction_details')->nullable()->comment('Transaction Details');
            $table->decimal('amount', 15, 4)->comment('Amount')->default(0);
            $table->decimal('amount_currency', 15, 4)->nullable()->comment('Amount Currency')->default(0);
            $table->decimal('amount_residual', 15, 4)->nullable()->comment('Amount Residual')->default(0);
            $table->boolean('is_reconciled')->default(false)->comment('Is Reconciled');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments_payment_transactions');
    }
};
