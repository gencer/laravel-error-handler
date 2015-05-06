<?php namespace Winternight\LaravelErrorHandler\Classes;

use Exception;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Translation\Translator as Lang;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Contracts\Routing\ResponseFactory as Response;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Winternight\LaravelErrorHandler\Contracts\DisplayContract;


/**
 * Class PlainDisplay.
 *
 * @package Winternight\LaravelErrorHandler\Classes
 */
class PlainDisplay implements DisplayContract {

	/**
	 * The config repository instance.
	 *
	 * @var \Illuminate\Contracts\Config\Repository
	 */
	protected $config;

	/**
	 * The HTTP request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The Translator instance.
	 *
	 * @var \Illuminate\Translation\Translator
	 */
	protected $lang;


	/**
	 * Construct.
	 *
	 * @param \Illuminate\Contracts\Config\Repository      $config
	 * @param \Illuminate\Contracts\View\Factory           $view
	 */
	public function __construct( Config $config, View $view, Lang $lang ) {
		$this->config = $config;
		$this->view   = $view;
		$this->lang   = $lang;
	}

	/**
	 * Set the HTTP Request instance.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return $this
	 */
	public function setRequest( Request $request ) {
		$this->request = $request;
		return $this;
	}

	/**
	 * Get the HTML content associated with the given exception.
	 *
	 * @param \Exception $exception
	 * @param int        $code
	 *
	 * @return string
	 */
	public function display( Exception $exception, $code ) {
		// Collect some info about the exception.
		$info = $this->info( $code, $exception );

		// Is the current request an AJAX request?
		if( (boolean)( $this->request instanceof Request && $this->request->ajax() ) == true ) {
			return JsonResponse::create( [ 'error' => $info ], $code );
		}

		// If it's a HTTP Exception and there is a custom view, use that.
		if( $exception instanceof HttpException && $this->view->exists( "errors.{$code}" ) ) {
			return $this->view->make( "errors.{$exception->getStatusCode()}", $info )->render();
		}

		// If the configured default error view (or errors.error) exists, render that.
		if( ( $errorview = $this->config->get( 'view.error', 'errors.error' ) ) && $this->view->exists( $errorview ) ) {
			return $this->view->make( $errorview, $info )->render();
		}

		// Last resort: simply show the error code and message.
		return sprintf( '%d %s: %s', $code, $info['name'], $exception->getMessage() );
	}

	/**
	 * Get the exception information.
	 *
	 * @param int        $code
	 * @param \Exception $exception
	 *
	 * @return array
	 */
	protected function info( $code, Exception $exception ) {
		$name        = $this->lang->get( "winternight/laravel-error-handler::messages.error.{$code}.name" );
		$message     = $this->lang->get( "winternight/laravel-error-handler::messages.error.{$code}.message" );
		$description = $exception->getMessage();

		return compact( 'code', 'name', 'message', 'description' );
	}
}