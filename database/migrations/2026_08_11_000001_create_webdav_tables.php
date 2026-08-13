<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webdav_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('public_id', 40)->unique();
            $table->bigInteger('userid')->index();
            $table->string('name', 100);
            $table->string('password_hash', 255);
            $table->string('password_suffix', 4);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->string('last_user_agent', 255)->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('webdav_locks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 100)->unique();
            $table->bigInteger('userid')->index();
            $table->bigInteger('credential_id')->index();
            $table->bigInteger('file_id')->nullable()->index();
            $table->string('uri', 1000);
            $table->char('uri_hash', 64)->index();
            $table->string('owner', 255)->nullable();
            $table->string('scope', 20)->default('exclusive');
            $table->string('depth', 20)->default('infinity');
            $table->timestamp('timeout_at')->index();
            $table->timestamps();
        });

        Schema::create('webdav_properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->index();
            $table->string('path', 1000);
            $table->char('path_hash', 64)->index();
            $table->string('name', 255);
            $table->char('property_hash', 64);
            $table->unsignedTinyInteger('value_type')->default(1);
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(
                ['userid', 'path_hash', 'property_hash'],
                'webdav_properties_user_path_property_unique'
            );
        });

        Schema::create('webdav_operation_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('request_id', 100)->nullable()->index();
            $table->bigInteger('userid')->nullable()->index();
            $table->bigInteger('credential_id')->nullable()->index();
            $table->string('method', 20);
            $table->string('uri', 1000)->nullable();
            $table->bigInteger('file_id')->nullable()->index();
            $table->smallInteger('status')->default(0)->index();
            $table->string('result', 255)->nullable();
            $table->bigInteger('bytes')->default(0);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webdav_operation_logs');
        Schema::dropIfExists('webdav_properties');
        Schema::dropIfExists('webdav_locks');
        Schema::dropIfExists('webdav_credentials');
    }
};
