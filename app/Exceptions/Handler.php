<?php

namespace App\Exceptions;

use App\Module\Base;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 将异常转换为 HTTP 响应。
     * @param $request
     * @param Throwable $e
     * @return array|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ApiException) {
            return response()->json(Base::retError($e->getMessage(), $e->getData(), $e->getCode()));
        } elseif ($e instanceof ModelNotFoundException) {
            return response()->json(Base::retError('Interface error'));
        }
        return parent::render($request, $e);
    }

    /**
     * 重写report优雅记录
     * @param Throwable $e
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        if ($e instanceof ApiException) {
            Log::error($e->getMessage(), ['exception' => ' at ' . $e->getFile() .':' . $e->getLine()]);
        } else {
            parent::report($e);
        }
    }
}
