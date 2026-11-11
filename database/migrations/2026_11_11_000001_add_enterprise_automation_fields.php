<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->unsignedInteger('kilometrage_actuel')->default(0)->after('photo');
            $table->date('dernier_releve_kilometrage_at')->nullable();
        });

        Schema::table('alertes', function (Blueprint $table) {
            $table->string('severity', 20)->default('medium')->after('message');
            $table->string('status', 20)->default('open')->after('severity');
            $table->string('dedupe_key', 191)->nullable()->unique()->after('status');
            $table->json('metadata')->nullable()->after('dedupe_key');
        });

        Schema::table('absences', function (Blueprint $table) {
            $table->string('statut_traitement', 30)->default('a_traiter')->after('motif');
            $table->foreignId('traitee_par')->nullable()->after('statut_traitement')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('traitee_le')->nullable()->after('traitee_par');
        });

        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moto_id')->constrained('motos')->cascadeOnDelete();
            $table->string('component', 80);
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedInteger('interval_days')->nullable();
            $table->unsignedInteger('last_done_km')->nullable();
            $table->date('last_done_at')->nullable();
            $table->unsignedInteger('next_due_km')->nullable();
            $table->date('next_due_at')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['moto_id', 'active']);
            $table->index(['next_due_at', 'active']);
            $table->index(['next_due_km', 'active']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerte_id')->nullable()->constrained('alertes')->nullOnDelete();
            $table->string('channel', 20); // dashboard, email, whatsapp
            $table->string('recipient')->nullable();
            $table->string('template', 100)->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('idempotency_key', 191)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['channel', 'status']);
        });

        Schema::create('automation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('workflow', 120);
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->string('external_execution_id', 120)->nullable()->index();
            $table->string('status', 20)->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('summary')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['workflow', 'status', 'created_at']);
        });

        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // prediction, anomaly, maintenance
            $table->string('severity', 20)->default('info');
            $table->string('title', 180);
            $table->text('description');
            $table->json('data')->nullable();
            $table->string('status', 20)->default('new'); // new, reviewed, dismissed
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status', 'generated_at']);
        });

        Schema::create('ai_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 30)->default('assistant');
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->json('context_summary')->nullable();
            $table->string('provider', 40)->default('ollama');
            $table->string('model', 100)->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interactions');
        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('automation_runs');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('maintenance_plans');

        Schema::table('absences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('traitee_par');
            $table->dropColumn(['statut_traitement', 'traitee_le']);
        });
        Schema::table('alertes', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn(['severity', 'status', 'dedupe_key', 'metadata']);
        });
        Schema::table('motos', function (Blueprint $table) {
            $table->dropColumn(['kilometrage_actuel', 'dernier_releve_kilometrage_at']);
        });
    }
};
