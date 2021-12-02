<?php
declare(strict_types=1);
namespace Dduers\PhpRestProxy;

use Symfony\Component\HttpFoundation\request;


class RestProxy 
{
    private $_request;
    private $_curl;
    private $_map;

    private $_content;
    private $_headers;

    function __construct(request $request_, curlwrapper $curl_)
    {
        $this->_request = $request_;
        $this->_curl = $curl_;
    }

    /**
     * register an api
     */
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


    public function getHeaders()
    {
        return $this->_headers;
    }

    public function getContent()
    {
        return $this->_content;
    }
}