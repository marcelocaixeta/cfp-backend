<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('color', 16)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('last_four_digits', 4)->nullable();
            $table->string('brand')->nullable();
            $table->decimal('limit_amount', 14, 2)->nullable();
            $table->unsignedTinyInteger('closing_day')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('credit_card_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->string('description');
            $table->decimal('total_amount', 14, 2);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->unsignedSmallInteger('current_installment')->default(1);
            $table->decimal('installment_amount', 14, 2);
            $table->date('first_due_date');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'first_due_date']);
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->string('lender_name');
            $table->string('description')->nullable();
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->string('interest_rate_period')->nullable();
            $table->unsignedSmallInteger('installment_count');
            $table->decimal('installment_amount', 14, 2);
            $table->date('first_due_date');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'first_due_date']);
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['loan_id', 'installment_number']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
        });

        Schema::create('btc_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('amount_btc', 20, 8);
            $table->decimal('average_buy_price', 18, 2)->nullable();
            $table->char('currency', 3)->default('BRL');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('btc_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->char('currency', 3)->default('BRL');
            $table->decimal('price', 18, 2);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['currency', 'captured_at']);
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('default_currency', 3)->default('BRL');
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->jsonb('dashboard_preferences')->nullable();
            $table->jsonb('notification_preferences')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('category')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('btc_price_snapshots');
        Schema::dropIfExists('btc_assets');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('credit_card_debts');
        Schema::dropIfExists('credit_cards');
        Schema::dropIfExists('finance_categories');
    }
};
