<?php
namespace Europeana\Api\Response;
use Europeana\Api\Helpers\Response as Response_Helper;
use Php\Exception;


abstract class ResponseAbstract {

	/**
	 * @var {array}
	 * creating a local version so it can be altered if necessary, e.g. obfuscated
	 */
	public $http_info;

	/**
	 * @var {string}
	 * creating a local version so it can be altered if necessary, e.g. obfuscated
	 */
	public $message_body;

	/**
	 * @var {\W3CHttp\Response}
	 */
	public $Response;

	/**
	 * @var {string}
	 */
	public $wskey;


	/**
	 * @var {array}
	 */
	protected $http_status_code_to_error;


	protected function init() {
		$this->Response = null;
		$this->http_info = array();
		$this->http_status_code_to_error = array(
			200 => 'The request was executed successfully',
			400 => 'The request sent by the client was syntactically incorrect',
			401 => 'Service was called with invalid argument(s); check the call URL',
			404 => 'The requested resource is not available',
			406 => 'Not acceptable',
			429 => 'The request could be served because the application has reached its usage limit',
			500 => 'Internal Server Error. Something has gone wrong, please report to us'
		);
		$this->message_body = '';
		$this->wskey = '';
	}

	protected function obfuscateApiKey() {
		if ( !empty( $this->wskey ) ) {
			if ( isset( $this->http_info['url'] ) ) {
				$this->http_info['url'] = Response_Helper::obfuscateApiKey( $this->http_info['url'], $this->wskey );
			}

			if ( isset( $this->http_info['request_header'] ) ) {
				$this->http_info['request_header'] = Response_Helper::obfuscateApiKey( $this->http_info['request_header'], $this->wskey );
			}

			$this->message_body = Response_Helper::obfuscateApiKey( $this->message_body, $this->wskey );
		}
	}

	/**
	 * @param {\W3C\Http\Response} $Response
	 * @param {string} $wskey
	 */
	protected function populate( $Response, $wskey = '' ) {
		if ( $Response instanceof \W3C\Http\Response ) {
			$this->Response = $Response;
			$this->http_info = $Response->getHttpInfo();
			$this->message_body = $Response->getMessageBody();
		}

		if ( !empty( $wskey ) && is_string( $wskey ) ) {
			$this->wskey = filter_var( $wskey, FILTER_SANITIZE_STRING );
		}

		$this->validate();
	}

	/**
	 * @throws Exception
	 */
	protected function throwRequestError() {
		$msg = 'API call error : ';

		if ( array_key_exists( $this->http_info['http_code'], $this->http_status_code_to_error ) ) {
			$msg .= $this->http_status_code_to_error[ $this->http_info['http_code'] ];
		} else {
			$msg .= 'the API returned an http status code that’s not officially handled by the API - ' . $this->http_info['http_code'];
		}

		$msg .= PHP_EOL . 'API call info  : ' . PHP_EOL;
		$msg .= print_r( $this->http_info, true );
		throw new Exception( $msg );
	}

	public function validate() {
		if ( !( $this->Response instanceof \W3C\Http\Response ) ) {
			throw new Exception( 'no valid \W3C\Http\Response provided' );
		}

		if ( $this->http_info['http_code'] !== 200 ) {
			$this->throwRequestError();
		}
	}
}