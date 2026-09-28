<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_owner')->default(false);
            $t->string('email_mode')->default('off');
        });
        Schema::create('sources', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('retailer');
            $t->string('driver'); $t->text('url'); $t->string('allowed_host');
            $t->jsonb('mapping')->nullable(); $t->boolean('enabled')->default(false);
            $t->unsignedInteger('interval_minutes')->default(360);
            $t->timestampTz('next_run_at')->nullable(); $t->timestampTz('last_run_at')->nullable();
            $t->text('last_error')->nullable(); $t->timestampsTz();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('brand')->nullable();
            $t->string('model')->nullable(); $t->string('category')->nullable();
            $t->string('search_text'); $t->timestampsTz();
        });
        Schema::create('offers', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('external_key'); $t->string('retailer');
            $t->string('title'); $t->text('description')->nullable();
            $t->unsignedBigInteger('price_minor'); $t->unsignedBigInteger('original_price_minor')->nullable();
            $t->string('currency', 3)->default('MAD'); $t->string('channel', 10)->default('online');
            $t->string('city')->nullable(); $t->text('url'); $t->text('image_url')->nullable();
            $t->string('revision', 64); $t->boolean('active')->default(true);
            $t->timestampTz('valid_from')->nullable(); $t->timestampTz('valid_until')->nullable();
            $t->timestampTz('seen_at'); $t->timestampsTz();
            $t->unique(['source_id', 'external_key']);
            $t->index(['active', 'channel', 'price_minor']);
            $t->index(['retailer', 'city']);
        });
        Schema::create('price_snapshots', function (Blueprint $t) {
            $t->id(); $t->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('price_minor'); $t->unsignedBigInteger('original_price_minor')->nullable();
            $t->timestampTz('observed_at'); $t->index(['offer_id', 'observed_at']);
        });
        Schema::create('watches', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name'); $t->jsonb('include_terms'); $t->jsonb('exclude_terms')->nullable();
            $t->jsonb('retailers')->nullable(); $t->jsonb('cities')->nullable();
            $t->jsonb('channels')->nullable(); $t->string('brand')->nullable();
            $t->string('category')->nullable(); $t->unsignedBigInteger('max_price_minor')->nullable();
            $t->unsignedTinyInteger('min_discount')->nullable();
            $t->boolean('muted')->default(false); $t->timestampsTz();
        });
        Schema::create('offer_alerts', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('watch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $t->string('revision', 64); $t->timestampTz('read_at')->nullable();
            $t->timestampTz('emailed_at')->nullable(); $t->timestampsTz();
            $t->unique(['watch_id', 'offer_id', 'revision']);
            $t->index(['user_id', 'read_at']);
        });
        Schema::create('source_runs', function (Blueprint $t) {
            $t->id(); $t->foreignId('source_id')->constrained()->cascadeOnDelete();
            $t->string('status'); $t->unsignedInteger('items')->default(0);
            $t->unsignedInteger('changed')->default(0); $t->text('error')->nullable();
            $t->timestampTz('started_at'); $t->timestampTz('finished_at')->nullable();
        });
    }
    public function down(): void {
        foreach (['source_runs','offer_alerts','watches','price_snapshots','offers','products','sources'] as $table) Schema::dropIfExists($table);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_owner','email_mode']));
    }
};
