<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->boolean('is_admin')->default(false);$t->rememberToken();$t->timestamps();});
  Schema::create('packages',function(Blueprint $t){
   $t->id();$t->string('slug')->unique();$t->string('title');$t->string('destination');$t->string('activity');$t->unsignedSmallInteger('days')->default(1);$t->string('difficulty')->default('Moderada');$t->string('summary',500);$t->text('description');$t->string('cover')->nullable();$t->json('gallery')->nullable();$t->json('itinerary')->nullable();$t->text('included')->nullable();$t->text('excluded')->nullable();$t->text('technical_details')->nullable();$t->text('terms')->nullable();$t->unsignedBigInteger('price_clp')->nullable();$t->unsignedBigInteger('price_usd_cents')->nullable();$t->boolean('published')->default(false);$t->boolean('featured')->default(false);$t->timestamps();
  });
  Schema::create('bookings',function(Blueprint $t){
   $t->uuid('id')->primary();$t->foreignId('package_id')->nullable()->constrained()->nullOnDelete();$t->string('package_title');$t->string('name');$t->string('email');$t->string('phone');$t->unsignedSmallInteger('travelers');$t->text('notes')->nullable();$t->string('currency',3);$t->unsignedBigInteger('unit_amount');$t->unsignedBigInteger('total_amount');$t->string('provider');$t->string('provider_order_id')->nullable()->unique();$t->string('provider_token')->nullable()->unique();$t->string('status')->default('pending');$t->timestamp('paid_at')->nullable();$t->string('capture_id')->nullable()->unique();$t->timestamp('receipt_sent_at')->nullable();$t->timestamps();
  });
  Schema::create('inquiries',function(Blueprint $t){$t->id();$t->string('name');$t->string('email');$t->string('destination')->nullable();$t->text('message');$t->timestamps();});
 }
 public function down(): void {foreach(['inquiries','bookings','packages','users'] as $table) Schema::dropIfExists($table);}
};
