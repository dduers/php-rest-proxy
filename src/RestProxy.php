<?php
declare(strict_types=1);
namespace Dduers\PhpRestProxy;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class RestProxy 
{
    private Client $_client;
    private Request $_request;
    private Response $_response;
    private array $_mounts = [];
    private array $_headers = [];
    private string $_body = '';
    
    function __construct()
    {
        $this->_client = new CLient();
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
     */
    public function exec()
    {
        $_request_url = 
            (isset($_SERVER['HTTPS']) ? 'https' : 'http') 
                . '://'.$_SERVER['HTTP_HOST']
                . $_SERVER['REQUEST_URI'] 
                . (($_SERVER['QUERY_STRING'] ?? '') ? $_SERVER['QUERY_STRING'] : '');

        $_request_route = explode('/', explode('://', $_request_url)[1]);

        array_shift($_request_route);

        $_mount_name = array_shift($_request_route);

        $_request_route = implode('/', $_request_route);

        $_target_url = $this->_mounts[$_mount_name];

        if (substr($_target_url, -1) != '/') 
            $_target_url .= '/';

        $_target_url .= $_request_route;

        $this->_request = new Request($_SERVER['REQUEST_METHOD'], $_target_url);

        $this->_response = $this->_client->send($this->_request);

        $this->_headers = $this->_response->getHeaders();

        $this->_body = $this->_response->getBody()->getContents();
    }
  
    /**
     * get response headers
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * get response body
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * dump results from remote api with headers
     */
    public function dump()
    {
        foreach ($this->getHeaders() as $name_ => $value_) 
            header($name_.': '.$value_[0]);

        // output response body
        echo $this->getBody();
    }
}
