<?php

namespace App\Providers;

use App\Models\File;
use App\Models\FileUser;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskContent;
use App\Models\ProjectTaskUser;
use App\Models\ProjectTaskVisibilityUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\UserTag;
use App\Models\UserTagRecognition;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Observers\FileObserver;
use App\Observers\FileUserObserver;
use App\Observers\ProjectObserver;
use App\Observers\ProjectTaskContentObserver;
use App\Observers\ProjectTaskObserver;
use App\Observers\ProjectTaskUserObserver;
use App\Observers\ProjectTaskVisibilityUserObserver;
use App\Observers\ProjectUserObserver;
use App\Observers\UserObserver;
use App\Observers\UserTagObserver;
use App\Observers\UserTagRecognitionObserver;
use App\Observers\WebSocketDialogMsgObserver;
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
        File::observe(FileObserver::class);
        FileUser::observe(FileUserObserver::class);
        Project::observe(ProjectObserver::class);
        ProjectTask::observe(ProjectTaskObserver::class);
        ProjectTaskContent::observe(ProjectTaskContentObserver::class);
        ProjectTaskUser::observe(ProjectTaskUserObserver::class);
        ProjectTaskVisibilityUser::observe(ProjectTaskVisibilityUserObserver::class);
        ProjectUser::observe(ProjectUserObserver::class);
        User::observe(UserObserver::class);
        UserTag::observe(UserTagObserver::class);
        UserTagRecognition::observe(UserTagRecognitionObserver::class);
        WebSocketDialog::observe(WebSocketDialogObserver::class);
        WebSocketDialogMsg::observe(WebSocketDialogMsgObserver::class);
        WebSocketDialogUser::observe(WebSocketDialogUserObserver::class);
    }
}
