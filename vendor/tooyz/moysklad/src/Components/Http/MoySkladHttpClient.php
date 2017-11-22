<?php

namespace MoySklad\Components\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use MoySklad\Exceptions\ApiResponseException;
use MoySklad\Exceptions\PosTokenException;
use MoySklad\Exceptions\RequestFailedException;
use MoySklad\Exceptions\ResponseParseException;

class MoySkladHttpClient{
    const
        METHOD_GET = "GET",
        METHOD_POST = "POST",
        METHOD_PUT = "PUT",
        METHOD_DELETE = "DELETE",
        HTTP_CODE_SUCCESS = [200, 201];

	const  curlOptions = [
	CURLOPT_SSLVERSION => 3,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_SSL_VERIFYHOST => false
	];

    private $preRequestSleepTime = 200;

    private
        $endpoint = "https://online.moysklad.ru/api/remap/1.1/",
        $posEndpoint = "https://online.moysklad.ru/api/posap/1.0/",
        $login,
        $password,
        $posToken;

    public function __construct($login, $password, $posToken)
    {
        $this->login = $login;
        $this->password = $password;
        $this->posToken = $posToken;
    }

    public function setPosToken($posToken){
        $this->posToken = $posToken;
    }

    public function get($method, $payload = [], $options = null){
        return $this->makeRequest(
            self::METHOD_GET,
            $method,
            $payload,
            $options
        );
    }

    public function post($method, $payload = [], $options = null){
        return $this->makeRequest(
            self::METHOD_POST,
            $method,
            $payload,
            $options
        );
    }

    public function put($method, $payload = [], $options = null){
        return $this->makeRequest(
            self::METHOD_PUT,
            $method,
            $payload,
            $options
        );
    }

    public function delete($method, $payload = [], $options = null){
        return $this->makeRequest(
            self::METHOD_DELETE,
            $method,
            $payload,
            $options
        );
    }

    public function getLastRequest(){
        return RequestLog::getLast();
    }

    public function getRequestList(){
        return RequestLog::getList();
    }

    public function setPreRequestTimeout($ms){
        $this->preRequestSleepTime = $ms;
    }

    /**
     * @param $requestHttpMethod
     * @param $apiMethod
     * @param array $data
     * @param array $options
     * @return \stdClass
     * @throws ApiResponseException
     * @throws PosTokenException
     * @throws RequestFailedException
     */
    private function makeRequest(
        $requestHttpMethod,
        $apiMethod,
        $data = [],
        $options = null
    ){
        if ( !$options ) $options = new RequestConfig();

        $password = $this->password;
        if ( $options->get('usePosApi') ){
            if ( $options->get('usePosToken') ){
                if ( empty($this->posToken) ){
                    throw new PosTokenException();
                }
                $password = $this->posToken;
            }
            $endpoint = $this->posEndpoint;
        } else {
            $endpoint = $this->endpoint;
        }

        $headers = [
            "Authorization" => "Basic " . base64_encode($this->login . ':' . $password)
        ];
        $config = [
            "base_uri" => $endpoint,
            "headers" => $headers,
            'stream_context' => [
		            'ssl' => [
			            'allow_self_signed' => true
		            ],
	            ],
	            'verify'         => false,
            ];

        $jsonRequestsTypes = [
            self::METHOD_POST,
            self::METHOD_PUT,
            self::METHOD_DELETE
        ];
        $requestBody = [];
        if ( $options->get('ignoreRequestBody') === false ){
            if ( $requestHttpMethod === self::METHOD_GET ){
                $requestBody['query'] = $data;
            } else if ( in_array($requestHttpMethod, $jsonRequestsTypes) ){
                $requestBody['json'] = $data;
            }
        }

        $serializedRequest = (isset($requestBody['json'])?\json_decode(\json_encode($requestBody['json'])):$requestBody['query']);
        $reqLog = [
            "req" => [
                "type" => $requestHttpMethod,
                "method" => $endpoint . $apiMethod,
                "body" => $serializedRequest,
                "headers" => $headers
            ]
        ];
        RequestLog::add($reqLog);
        $client = new Client($config);
        try{
            usleep($this->preRequestSleepTime);
            $res = $client->request(
                $requestHttpMethod,
                $apiMethod,
                $requestBody
            );
            if ( in_array($res->getStatusCode(), self::HTTP_CODE_SUCCESS) ){
                if ( $requestHttpMethod !== self::METHOD_DELETE ){
                    $result = \json_decode($res->getBody());
                    if ( is_null($result) === false ){
                        $reqLog['res'] = $result;
                        RequestLog::replaceLast($reqLog);
                        return $result;
                    } else {
                        throw new ResponseParseException($res);
                    }
                }
                RequestLog::replaceLast($reqLog);
            } else {
                throw new RequestFailedException($reqLog['req'], $res);
            }
        } catch (\Exception $e){
            if ( $e instanceof ClientException){
                $req = $reqLog['req'];
                $res = $e->getResponse()->getBody()->getContents();
                $except = new RequestFailedException($req, $res);
                if ( $res = \json_decode($res) ){
                    if ( isset($res->errors) || (is_array($res) && isset($res[0]->errors))){
                        $except = new ApiResponseException($req, $res);
                    }
                }
            } else $except = $e;
            throw $except;
        }
        return null;
    }
}
