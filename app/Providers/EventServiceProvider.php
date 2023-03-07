<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use App\Observers\ProjectObserver;
use App\Observers\ProjectTaskObserver;
use App\Observers\ProjectTaskUserObserver;
use App\Observers\ProjectUserObserver;
use App\Observers\WebSocketDialogObserver;
use App\Observers\WebSocketDialogUserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Project::observe(ProjectObserver::class);
        ProjectTask::observe(ProjectTaskObserver::class);
        ProjectTaskUser::observe(ProjectTaskUserObserver::class);
        ProjectUser::observe(ProjectUserObserver::class);
        WebSocketDialog::observe(WebSocketDialogObserver::class);
        WebSocketDialogUser::observe(WebSocketDialogUserObserver::class);
    }
}
