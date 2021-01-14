<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Response;
use App\Models\ApiLog;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $exception)
    {


        $rendered   = parent::render($request, $exception);
        $url        = $request->path();
        $hostname   = gethostname();

        if ($exception instanceof ValidationException) {
			$message = json_decode($exception->getResponse()->content());
		}else{
			$message    = $exception->getMessage();
        }
        $level      = $rendered->getStatusCode();
        $channel    = $config['name'] ?? env('APP_ENV');
        $ip         = $request->server('REMOTE_ADDR');
        $user_agent = $request->server('HTTP_USER_AGENT');

        ApiLog::create([
            'instance'      => $hostname,
            'channel'       => $channel,
            'message'       => $level.' / '.$message,
            'level'         => 'ERROR',
            'ip'            => $ip,
            'user_agent'    => $user_agent,
            'url'           => $url,
            'context'       => $exception,
            'extra'         => $request

        ]);

        

        return response()
        ->json([
            'status'=>$level ,
            'datas' => [], 
            'errors' => [
                'ip' => $ip, 
                'user_agent' => $user_agent, 
                'message' => $message, 
            ]
            ])
        ->withHeaders([
            'Content-Type'          => 'application/json',
            ])
        ->setStatusCode($level);

        // return parent::render($request, $exception);
		
    }
 
}
