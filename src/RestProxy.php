<?php
declare(strict_types=1);
namespace Dduers\PhpRestProxy;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Dduers\PhpRestProxy\RestProxyException;
use GuzzleHttp\Cookie\CookieJar;

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

    private array $_cookies = [];

    private CookieJar $_cookies_jar;

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
     */
    public function mount(string $route_, string $url_)
    {
        $this->_mounts[$route_] = $url_;
    }

    /**
     * run the proxy
     * @return void
     */
    public function exec()
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
        $_request_route = '/'.implode('/', $_request_route_array);

        if (!isset($this->_mounts[$_mount_name]))
            throw new RestProxyException('undefined mount: '.$_mount_name);

        $_target_url = $this->_mounts[$_mount_name];

        if (substr($_target_url, -1) === '/') 
            $_target_url = substr($_target_url, 0, -1); 

        $_target_url .= $_request_route;

        $this->_cookies_jar = CookieJar::fromArray(
            $this->_cookies, 
            $this->getDomainFromUrl($_target_url)
        );

        $_forward_headers = array_filter([
            'User-Agent' => $this->_origin_request_headers['User-Agent'] ?? NULL,
            'Referer' => $this->_origin_request_headers['Referer'] ?? NULL,
            'Accept' => $this->_origin_request_headers['Accept'] ?? NULL,
            'Accept-Charset' => $this->_origin_request_headers['Accept-Charset'] ?? NULL,
            'Accept-Encoding' => $this->_origin_request_headers['Accept-Encoding'] ?? NULL,
            'Accept-Language' => $this->_origin_request_headers['Accept-Language'] ?? NULL,
            //'Connection' => $this->_origin_request_headers['Connection'] ?? NULL,
            //'Host' => $this->_origin_request_headers['Host'] ?? NULL,
        ]);

        switch ($_SERVER['REQUEST_METHOD']) {

            case 'GET':
                $this->_response = $this->_client->get($_target_url, [
                    'headers' => $_forward_headers,
                    'cookies' => $this->_cookies_jar
                ]);
                break;

            case 'HEAD':
                $this->_response = $this->_client->get($_target_url, [
                    'headers' => $_forward_headers,
                    'cookies' => $this->_cookies_jar
                ]);
                break;

            case 'OPTIONS':
                $this->_response = $this->_client->get($_target_url, [
                    'headers' => $_forward_headers,
                    'cookies' => $this->_cookies_jar
                ]);
                break;

            case 'POST':

                if (isset($this->_origin_request_headers['Content-Type'])) {
                    switch ($this->_origin_request_headers['Content-Type']) {

                        case 'application/json':
                            $_params = json_decode(file_get_contents("php://input"), true);
                            $_options = [
                                'json' => $_params,
                                'headers' => array_filter([
                                    'User-Agent' => $this->_origin_request_headers['User-Agent'] ?? NULL,
                                    'Referer' => $this->_origin_request_headers['Referer'] ?? NULL,
                                    'Accept' => $this->_origin_request_headers['Accept'] ?? NULL,
                                    'Accept-Charset' => $this->_origin_request_headers['Accept-Charset'] ?? NULL,
                                    'Accept-Encoding' => $this->_origin_request_headers['Accept-Encoding'] ?? NULL,
                                    'Accept-Language' => $this->_origin_request_headers['Accept-Language'] ?? NULL,
                                    //'Connection' => $this->_origin_request_headers['Connection'] ?? NULL,
                                    //'Host' => $this->_origin_request_headers['Host'] ?? NULL,
                                ]),
                                'cookies' => $this->_cookies_jar
                            ];
                            break;

                        case 'application/x-www-form-urlencoded':
                            $_params = $_POST;
                            $_options = [
                                'form_params' => $_params,
                                'headers' => $_forward_headers,
                                'cookies' => $this->_cookies_jar
                            ];
                            break;

                        case 'multipart/form-data':
                            $_params = $_POST;
                            $_options = [
                                'form_params' => $_params,
                                'headers' => $_forward_headers,
                                'cookies' => $this->_cookies_jar
                            ];
                            break;

                        case 'text/plain':
                            throw new RestProxyException('unsupported encoding type: '.$_SERVER['REQUEST_METHOD'].'/'.$this->_origin_request_headers['Content-Type']);
                            break;
                    }
                }

                if (!isset($_params))
                    throw new RestProxyException('no parameters received: '.$_SERVER['REQUEST_METHOD']);

                $this->_response = $this->_client->post($_target_url, $_options);
                break;

            case 'PUT':
                $_params = [];
                $_body = file_get_contents('php://input');
                parse_str($_body, $_params);

                $this->_response = $this->_client->put($_target_url, [
                    'body' => $_params,
                    'headers' => $_forward_headers,
                    'cookies' => $this->_cookies_jar
                ]);
                break;

            case 'PATCH':
                $_params = [];
                $_body = file_get_contents('php://input');
                parse_str($_body, $_params);

                $this->_response = $this->_client->put($_target_url, [
                    'body' => $_params,
                    'headers' => $_forward_headers,
                    'cookies' => $this->_cookies_jar
                ]);
                break;

            case 'DELETE':
                $this->_response = $this->_client->delete($_target_url, [
                    'headers' => $_forward_headers,
                    'cookies' => $this->_cookies_jar
                ]);
                break;
        }

        
        $this->_response_headers = $this->_response->getHeaders();
        $this->_response_body = $this->_response->getBody()->getContents();
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
    public function dump()
    {
        $_headers = $this->getReponseHeaders();

        array_walk($_headers, function($value_, $header_) {
            //if (count($value_) > 1)
                foreach ($value_ as $_v)
                    header($header_.': '.$_v, false); 
            //else header($header_.': '.$value_[0]); 
        });

        echo $this->getResponseBody();
        exit();
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
     * parse domain name out of an url
     * @param string $url_
     * @return string
     */
    private function getDomainFromUrl(string $url_): string
    {
        return parse_url($url_)['host'] ?? '';
    }
}
