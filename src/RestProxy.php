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

    private string $_body;
    private array $_headers;

    private $_map;

    function __construct()
    {
        $this->_client = new CLient();

        $this->_request = new Request($_SERVER['REQUEST_METHOD'], 'http://domain19.local/v1/home');

        $this->_response = $this->_client->send($this->_request);

        $this->_headers = $this->_response->getHeaders();

        $this->_body = $this->_response->getBody()->getContents();
    }

    /**
     * register an api
     */
    /*
    public function register(string $name_, string $url_)
    {
        $this->_map[$name_] = $url_;
    }


    public function run()
    {
        foreach ($this->_map as $name_ => $mapurl_)
            return $this->dispatch($name_, $mapurl_);
    }



    private function dispatch(string $name_, string $mapurl_)
    {



        $_url = $this->_request->getpathinfo();

        if (strpos($_url, $name_) == 1) {

            $_url = $mapurl_ . str_replace("/{$name_}", '', $_url);

            $_query = $this->_request->getquerystring();

            switch ($this->_request->getmethod()) {

                case 'get':
                    $this->content = $this->_curl->doget($_url, $_query);
                    break;

                case 'post':
                    $this->content = $this->_curl->dopost($_url, $_query);
                    break;

                case 'delete':
                    $this->content = $this->_curl->dodelete($_url, $_query);
                    break;

                case 'put':
                    $this->content = $this->_curl->doput($_url, $_query);
                    break;
            }

            $this->_headers = $this->_curl->getheaders();
        }
    }
    */


    public function getHeaders()
    {
        return $this->_headers;
    }

    public function getBody()
    {
        return $this->_body;
    }
    
}