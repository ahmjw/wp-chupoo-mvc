<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 28th 2018
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */
namespace Introvesia\WpChupooMvc;

class Router
{
    private $uri;
    private $arg_keyvalue = array();
    private $arg_arr = array();
    private $route;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getArgKeyValue()
    {
        return $this->arg_keyvalue;
    }

    public function getArgArr()
    {
        return $this->arg_arr;
    }

    public function parse(array $routes)
    {
        foreach ($routes as $pattern => $route) {
            $arg_names = array();
            $pattern = preg_replace_callback('/\(([a-zA-Z0-9_]+):(.*?)\)/i', function($matches) use(&$arg_names) {
                $arg_names[] = $matches[1];
                return '('.$matches[2].')';
            }, $pattern);

            $pattern = '/'.str_replace('/', '\/', $pattern).'/i';
            if (preg_match($pattern, $this->uri, $match)) {
                $values = array_slice($match, 1);
                foreach ($values as $i => $value) {
                    $this->arg_arr[] = $value;
                    $this->arg_keyvalue[$arg_names[$i]] = $value;
                }
                $this->route = $route;
            }
        }
    }
}