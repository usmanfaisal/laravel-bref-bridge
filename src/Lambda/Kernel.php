<?php declare(strict_types=1);

/**
 * Package: laravel-bref-bridge
 * Create Date: 2019-02-27
 * Created Time: 14:30
 */

namespace STS\Bref\Bridge\Lambda;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use STS\Bref\Bridge\Lambda\Application as Lambda;
use STS\Bref\Bridge\Lambda\Contracts\Kernel as KernelContract;
use STS\Bref\Bridge\Lambda\Contracts\Registrar;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     * @var Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $laravelEventDispatcher;

    /**
     * The Lambda Application this kernel is managing.
     *
     * @var Lambda
     */
    protected $lambda;

    /**
     * Stores the output from the Lambda Application.
     *
     * @var array
     */
    protected $output;

    /**
     * The bootstrap classes for the Lambda application.
     *
     * @var array
     */
    protected $bootstrappers = [
        LoadEnvironmentVariables::class,
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterFacades::class,
        SetRequestForConsole::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    /**
     * Create a new Lambda kernel instance.
     */
    public function __construct(Application $app, Dispatcher $laravelEventDispatcher)
    {
        $this->app = $app;
        $this->laravelEventDispatcher = $laravelEventDispatcher;
    }

    /**
     * Pass the Lambda event/context on the application,
     * store the results, then return said results.
     */
    public function handle(string $event, string $context): array
    {
        try {
            $this->bootstrap();
            $this->output = $this->getLambda()->run($event, $context);
        } catch (\Throwable $e) {
            $e = new FatalThrowableError($e);
            $this->reportException($e);
            return $this->renderException($e);
        }
        return $this->output;
    }

    /**
     * Bootstrap the lambda application.
     */
    public function bootstrap(): void
    {
        if (! $this->app->hasBeenBootstrapped()) {
            $this->app->bootstrapWith($this->bootstrappers());
        }

        $this->app->loadDeferredProviders();
    }

    /**
     * Get the bootstrap classes for the Lambda application.
     */
    protected function bootstrappers(): array
    {
        return $this->bootstrappers;
    }

    /**
     * Gets the Lambda Application for us.
     */
    protected function getLambda(): Lambda
    {
        if ($this->lambda === null) {
            return $this->lambda = new Lambda($this->laravelEventDispatcher, $this->app[Registrar::class]);
        }
        return $this->lambda;
    }

    /**
     * Report the exception to the exception handler.
     */
    protected function reportException(\Throwable $e): void
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the given exception for Lambda
     */
    protected function renderException(\Throwable $e): array
    {
        return [
            'exception' => exceptionToArray($e),
            'errorMessage' => $e->getMessage(),
            'errorType' => get_class($e),
        ];
    }

    /**
     * Returns the Application results.
     */
    public function output(): array
    {
        return $this->output;
    }

    /**
     * Terminate the application
     */
    public function terminate(int $status): void
    {
        $this->app->terminate();
    }
}
