<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_leads', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_leads', 'city')) {
                $table->string('city', 120)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('marketplace_leads', 'state')) {
                $table->string('state', 2)->nullable()->after('city');
            }
            if (! Schema::hasColumn('marketplace_leads', 'sellers_count')) {
                $table->unsignedSmallInteger('sellers_count')->nullable()->after('employees');
            }
            if (! Schema::hasColumn('marketplace_leads', 'customers_count')) {
                $table->unsignedInteger('customers_count')->nullable()->after('sellers_count');
            }
            if (! Schema::hasColumn('marketplace_leads', 'phone_normalized')) {
                $table->string('phone_normalized', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('marketplace_leads', 'landing_page')) {
                $table->string('landing_page', 500)->nullable()->after('utm_content');
            }
            if (! Schema::hasColumn('marketplace_leads', 'referrer')) {
                $table->string('referrer', 500)->nullable()->after('landing_page');
            }
            if (! Schema::hasColumn('marketplace_leads', 'phone_normalized')) {
                $table->index('phone_normalized');
            }
        });

        Schema::table('marketplace_sales_pipeline', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_sales_pipeline', 'demo_scheduled_at')) {
                $table->timestamp('demo_scheduled_at')->nullable()->after('last_contact_at');
            }
            if (! Schema::hasColumn('marketplace_sales_pipeline', 'next_action_at')) {
                $table->timestamp('next_action_at')->nullable()->after('demo_scheduled_at');
            }
            if (! Schema::hasColumn('marketplace_sales_pipeline', 'next_action_label')) {
                $table->string('next_action_label', 180)->nullable()->after('next_action_at');
            }
        });

        Schema::table('marketplace_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_settings', 'commercial_alert_enabled')) {
                $table->boolean('commercial_alert_enabled')->default(false)->after('whatsapp_message');
            }
            if (! Schema::hasColumn('marketplace_settings', 'commercial_alert_whatsapp')) {
                $table->string('commercial_alert_whatsapp', 40)->nullable()->after('commercial_alert_enabled');
            }
            if (! Schema::hasColumn('marketplace_settings', 'commercial_owner_name')) {
                $table->string('commercial_owner_name', 80)->nullable()->after('commercial_alert_whatsapp');
            }
            if (! Schema::hasColumn('marketplace_settings', 'commercial_alert_template')) {
                $table->text('commercial_alert_template')->nullable()->after('commercial_owner_name');
            }
            if (! Schema::hasColumn('marketplace_settings', 'commercial_outreach_template')) {
                $table->text('commercial_outreach_template')->nullable()->after('commercial_alert_template');
            }
            if (! Schema::hasColumn('marketplace_settings', 'commercial_schedule_template')) {
                $table->text('commercial_schedule_template')->nullable()->after('commercial_outreach_template');
            }
        });

        if (! Schema::hasTable('marketplace_lead_activities')) {
            Schema::create('marketplace_lead_activities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')->constrained('marketplace_leads')->cascadeOnDelete();
                $table->string('type', 60);
                $table->string('label', 180);
                $table->text('detail')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['lead_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('marketplace_lead_notifications')) {
            Schema::create('marketplace_lead_notifications', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')->nullable()->constrained('marketplace_leads')->cascadeOnDelete();
                $table->string('type', 60);
                $table->string('channel', 40)->default('whatsapp');
                $table->string('status', 20)->default('pending');
                $table->string('to_phone', 20)->nullable();
                $table->text('body')->nullable();
                $table->text('error')->nullable();
                $table->string('provider_message_id', 120)->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->unique(['lead_id', 'type', 'channel'], 'mkp_lead_notif_unique');
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_lead_notifications');
        Schema::dropIfExists('marketplace_lead_activities');
    }
};
