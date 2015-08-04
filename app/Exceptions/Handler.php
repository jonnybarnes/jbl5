<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exc
     * @return void
     */
    public function report(Exception $exc)
    {
        return parent::report($exc);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exc
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exc)
    {
        if (config('app.debug')) {
            return $this->renderExceptionWithWhoops($exc);
        }

        return parent::render($request, $exc);
    }

    /**
     * Render an exception using Whoops.
     *
     * @param  \Exception $exc
     * @return \Illuminate\Http\Response
     */
    protected function renderExceptionWithWhoops(Exception $exc)
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());

        return new \Illuminate\Http\Response(
            $whoops->handleException($exc),
            $exc->getStatusCode(),
            $exc->getHeaders()
        );
    }
}
