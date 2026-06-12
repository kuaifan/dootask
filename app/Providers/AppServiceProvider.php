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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Database\Query\Builder::macro('rawSql', function(){
            return array_reduce($this->getBindings(), function($sql, $binding){
                return preg_replace('/\?/', is_numeric($binding) ? $binding : "'".$binding."'" , $sql, 1);
            }, $this->toSql());
        });

        \Illuminate\Database\Eloquent\Builder::macro('rawSql', function(){
            return ($this->getQuery()->rawSql());
        });

        $this->configureRateLimiting();
        $this->registerEvents();
        $this->registerObservers();
    }

    /**
     * api 组限流（原 RouteServiceProvider::configureRateLimiting）
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }

    /**
     * 事件监听（原 EventServiceProvider::$listen）
     */
    protected function registerEvents()
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }

    /**
     * 模型观察者（原 EventServiceProvider::boot）
     */
    protected function registerObservers()
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
