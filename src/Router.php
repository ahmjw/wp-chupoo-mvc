<?php
/**
 * Japan, Gunma-ken, Maebashi-shi, January 28th 2018
 *
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */

namespace Introvesia\WpChupooMvc;

/**
 * @package    Introvesia
 * @subpackage WpChupooMvc
 * @copyright  Copyright (c) 2016-2018 Introvesia (http://chupoo.introvesia.com)
 * @version    v1.0.0
 */
class Router
{
    /**
     * URI (Uniform Resource Locator)
     *
     * @var string
     */
    private $uri;

    /**
     * Matched route's arguments with key-value paired format
     *
     * @var array
     */
    private $arg_keyvalue = array();

    /**
     * Matched route's arguments with indexed format
     *
     * @var array
     */
    private $arg_arr = array();

    /**
     * Matched route
     *
     * @var string
     */
    private $route;

    /**
     * Class constructor
     *
     * @param string $uri URI
     * @return null
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get route with matched pattern with URI
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Get arguments from matched URI with key-value paired array
     *
     * @return array
     */
    public function getArgKeyValue()
    {
        return $this->arg_keyvalue;
    }

    /**
     * Get arguments from matched URI with indexed array
     *
     * @return array
     */
    public function getArgArr()
    {
        return $this->arg_arr;
    }

    /**
     * Parse route list to find matching pattern
     *
     * @param array $routes Route list
     * @return null
     */
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