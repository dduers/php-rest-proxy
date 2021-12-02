<?php
declare(strict_types=1);
namespace Dduers\PhpRestProxy;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Dduers\PhpRestProxy\RestProxyException;

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
        $this->_client = new Client();
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
            throw new RestProxyException('Undefined mount: '.$_mount_name);

        $_target_url = $this->_mounts[$_mount_name];

        if (substr($_target_url, -1) === '/') 
            $_target_url = substr($_target_url, 0, -1); 

        $_target_url .= $_request_route;

        $this->_request = new Request($_SERVER['REQUEST_METHOD'], $_target_url);
        $this->_response = $this->_client->send($this->_request);
        $this->_headers = $this->_response->getHeaders();
        $this->_body = $this->_response->getBody()->getContents();
    }
  
    /**
     * get response headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->_headers;
    }

    /**
     * get response body
     * @return string
     */
    public function getBody(): string
    {
        return $this->_body;
    }

    /**
     * dump results from remote api with headers
     * @return void
     */
    public function dump()
    {
        foreach ($this->getHeaders() as $name_ => $value_) 
            header($name_.': '.$value_[0]);

        // output response body
        echo $this->getBody();
    }
}
