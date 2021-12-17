<?php

declare(strict_types=1);

namespace Dduers\PhpRestProxy;

use GuzzleHttp\Client;
//use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Response;
use Dduers\PhpRestProxy\RestProxyException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class RestProxy
{
    /**
     * proxy mouts
     * [[name => url], ...]
     */
    private array $_mounts = [];

    /**
     * http client
     */
    private Client $_client;

    /**
     * cookies
     */
    //private CookieJar $_cookies_jar;
    private array $_cookies = [];

    /**
     * target api response
     */
    private Response $_response;
    private array $_response_headers = [];
    private string $_response_body = '';

    /**
     * original request header of the origin
     */
    private array $_origin_request_headers = [];

    /**
     * constructor
     * - parse origin request headers
     * - initialize http client
     */
    function __construct(array $client_options_ = [])
    {
        $this->_client = new Client($client_options_);

        foreach (getallheaders() as $header_ => $value_)
            $this->_origin_request_headers[$header_] = $value_;

        foreach ($_COOKIE as $key_ => $value_)
            $this->_cookies[$key_] = $value_;
    }

    /**
     * mount an api to a name
     * @param $name_
     * @param $url_
     * @return void
     */
    public function mount(string $route_, string $url_): void
    {
        $this->_mounts[$route_] = $url_;
    }

    /**
     * run the proxy
     * @return void
     */
    public function exec(): void
    {
        // remove script directory
        $_script_path = dirname($_SERVER['PHP_SELF']);
        if (strlen($_script_path) > 1)
            $_request_route = substr($_SERVER['REQUEST_URI'], strlen($_script_path));
        else $_request_route = $_SERVER['REQUEST_URI'];

        // shift away root, then store proxy mount name
        $_request_route_array = explode('/', $_request_route);
        array_shift($_request_route_array);
        $_mount_name = array_shift($_request_route_array);

        // build actual target api route
        $_request_route = '/' . implode('/', $_request_route_array);

        if (!isset($this->_mounts[$_mount_name]))
            throw new RestProxyException('undefined mount: ' . $_mount_name);

        $_target_url = $this->_mounts[$_mount_name];

        if (substr($_target_url, -1) === '/')
            $_target_url = substr($_target_url, 0, -1);

        $_target_url .= $_request_route;

        /*
        $this->_cookies_jar = CookieJar::fromArray(
            $this->_cookies,
            $this->getDomainFromUrl($_target_url)
        );
        */

        $_forward_headers = array_filter([
            'User-Agent' => $this->_origin_request_headers['User-Agent'] ?? NULL,
            'Referer' => $this->_origin_request_headers['Referer'] ?? NULL,
            'Accept' => $this->_origin_request_headers['Accept'] ?? NULL,
            'Accept-Charset' => $this->_origin_request_headers['Accept-Charset'] ?? NULL,
            'Accept-Encoding' => $this->_origin_request_headers['Accept-Encoding'] ?? NULL,
            'Accept-Language' => $this->_origin_request_headers['Accept-Language'] ?? NULL,
            'Authorization' => ($_t = ($this->_cookies['_identifier'] ?? '')) ? 'Bearer '.$_t : NULL,
            //'Connection' => $this->_origin_request_headers['Connection'] ?? NULL,
            //'Host' => $this->_origin_request_headers['Host'] ?? NULL,
        ]);

        $_options = [
            'headers' => $_forward_headers,
            //'cookies' => $this->_cookies_jar,
            'http_errors' => true
        ];

        $_method = $_SERVER['REQUEST_METHOD'];

        switch ($_method) {

            case 'GET':
            case 'HEAD':
            case 'OPTIONS':
                break;

            case 'POST':

                if (isset($this->_origin_request_headers['Content-Type'])) {
                    switch ($this->_origin_request_headers['Content-Type']) {

                        case 'application/json':
                            $_params = json_decode(file_get_contents('php://input'), true);
                            $_options = array_merge($_options, [
                                'json' => $_params,
                            ]);
                            break;

                        case 'application/x-www-form-urlencoded':
                            $_params = $_POST;
                            $_options = array_merge($_options, [
                                'form_params' => $_params,
                            ]);
                            break;

                        case 'multipart/form-data':
                            $_params = $_POST;
                            $_options = array_merge($_options, [
                                'form_params' => $_params,
                            ]);
                            break;

                        case 'text/plain':
                            throw new RestProxyException('unsupported encoding type: ' . $_method . '/' . $this->_origin_request_headers['Content-Type']);
                            break;
                    }
                }
                break;

            case 'PUT':
            case 'PATCH':

                if (isset($this->_origin_request_headers['Content-Type'])) {
                    switch ($this->_origin_request_headers['Content-Type']) {

                        case 'application/json':
                            $_params = json_decode(file_get_contents('php://input'), true);
                            $_options = array_merge($_options, [
                                'json' => $_params,
                            ]);
                            break;

                        case 'application/x-www-form-urlencoded':
                            $_body = file_get_contents('php://input');
                            parse_str($_body, $_params);
                            $_options = array_merge($_options, [
                                'form_params' => $_params,
                            ]);
                            break;

                        case 'multipart/form-data':
                            $_body = file_get_contents('php://input');
                            parse_str($_body, $_params);
                            $_options = array_merge($_options, [
                                'form_params' => $_params,
                            ]);
                            break;

                        case 'text/plain':
                            throw new RestProxyException('unsupported encoding type: ' . $_method . '/' . $this->_origin_request_headers['Content-Type']);
                            break;
                    }
                }
                break;

            case 'DELETE':
                break;
        }

        try {
            $this->_response = $this->_client->{strtolower($_method)}($_target_url, $_options);
            $this->_response_headers = $this->_response->getHeaders();
            $this->_response_body = $this->_response->getBody()->getContents();

            $_decoded_body = json_decode($this->_response_body, true);

            if (isset($_decoded_body['data']['_identifier'])) {
                $this->setCookie('_identifier', $_decoded_body['data']['_identifier']);
            }

        } catch (ClientException $_e) {
            //echo Message::toString($_e->getRequest());
            //echo Message::toString($_e->getResponse());
            http_response_code($_e->getResponse()->getStatusCode());
        } catch (ServerException $_e) {
            http_response_code(500);
        }
    }

    /**
     * get request headers of the origin request
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->_origin_request_headers;
    }

    /**
     * get response headers
     * @return array
     */
    public function getReponseHeaders(): array
    {
        return $this->_response_headers;
    }

    /**
     * get response body
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->_response_body;
    }

    /**
     * dump results from remote api with headers
     * @return void
     */
    public function dump(): void
    {
        $_headers = $this->getReponseHeaders();

        array_walk($_headers, function ($value_, $header_) {
            //if (count($value_) > 1)
            foreach ($value_ as $_v)
                header($header_ . ': ' . $_v, false);
            //else header($header_.': '.$value_[0]); 
        });

        echo $this->getResponseBody();
        exit();
    }

    /**
     * parse domain name out of an url
     * @param string $url_
     * @return string
     */
    private function getDomainFromUrl(string $url_): string
    {
        return parse_url($url_)['host'] ?? '';
    }

    /**
     * issue a cookie
     * @param string $name_
     * @param string $content_
     * @return array the settings which where used to create the cookie
     */
    private function setCookie(string $name_, string $content_, bool $stayloggedin_ = false): array
    {
        $_options = array_filter([
            'expires' => $stayloggedin_ === true
                ? (string)(time() + 86400)
                : 31500000,
            //'domain' => (string)$this->_f3->get('CONF.cookie.options.domain') ?: NULL,
            'httponly' => true, //(string)$this->_f3->get('CONF.cookie.options.httponly') ?: NULL,
            'secure' => false, //(string)$this->_f3->get('CONF.cookie.options.secure') ?: NULL,
            'path' => '/', //(string)$this->_f3->get('CONF.cookie.options.path') ?: NULL,
            'samesite' => 'Strict' //(string)$this->_f3->get('CONF.cookie.options.samesite') ?: NULL,
        ]);

        setcookie($name_, $content_, $_options);

        return $_options;
    }
}
