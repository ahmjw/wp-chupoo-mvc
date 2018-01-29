<?php

/**
 * Japan, Gunma-ken, Maebashi-shi, January 27th 2018
 *
 * @link http://chupoo.introvesia.com
 * @author Ahmad <rawndummy@gmail.com>
 */

namespace Introvesia\WpChupooMvc;

use Introvesia\PhpDomView\Layout;
use Introvesia\PhpDomView\View;
use Introvesia\PhpDomView\Config;
use Introvesia\Chupoo\Models\Db;

/**
 * @package    Introvesia
 * @subpackage WpChupooMvc
 * @copyright  Copyright (c) 2016-2018 Introvesia (http://chupoo.introvesia.com)
 * @version    v1.0.0
 */
class Base
{
	/**
     * Matched route
     *
     * @var string
     */
	private $route;

	/**
     * Matched route's arguments
     *
     * @var array
     */
	private $args = array();

	/**
     * Run MVC loader
     *
     * @return null
     */
	public function run()
	{
		global $wpdb;

		Config::setData(array(
			'base_url' => get_home_url(),
			'layout_dir' => get_template_directory() . '/modules/layouts',
			'layout_url' => get_template_directory_uri(),
		));

		Db::setConnection($wpdb->dbh);

		spl_autoload_register(array($this, 'loadClass'));
		set_exception_handler(array($this, 'handleException'));
		add_action( 'template_redirect', array($this, 'loadPublic') );
		add_action( 'admin_menu', array($this, 'adminMenu') );
	}

	/**
     * Model class autoloading
     *
     * @param string $className Model class name
     * @return null
     */
	public function loadClass($className)
	{
		if (preg_match('/^\\Models\\\\.*?$/', $className)) {
			$path = get_template_directory() . '/modules/models/' . substr($className, 7) . '.php';
			include($path);
		}
	}

	/**
     * Show caught error
     *
     * @param object $exc Exception information
     * @return null
     */
	public function handleException($exc)
	{
		print_r($exc);
	}

	/**
     * Load admin page menu
     *
     * @return null
     */
	public function adminMenu()
	{
		global $plugin_page;
		$page = substr($plugin_page, 2);
	  	$menus = require(get_template_directory() . '/modules/config/admin_menu.php');
		// Routing
		$route_list = require(get_template_directory() . '/modules/config/admin_routes.php');
		$router = new Router($page);
		$router->parse($route_list);
		$this->route = $router->getRoute();
		$this->args = $router->getArgKeyValue();

		foreach ($menus as $route => $menu) {
			if ($route == $this->route) {
				$slug = 'c/' . $page;
			} else {
				$slug = 'c/' . $route;
			}
			add_menu_page(
				$menu[0], 
				$menu[1], 
				'read', 
				$slug,
				array($this, 'loadAdmin'));
			if (isset($menu[2])) {
				foreach ($menu[2] as $sub_route => $sub_menu) {
					if ($sub_route == $this->route) {
						$slug = 'c/' . $page;
					} else {
						$slug = 'c/' . $sub_route;
					}
					add_submenu_page(
						'c/' . $route,
						$sub_menu[0],
						$sub_menu[1],
						'read',
						$slug,
						array($this, 'loadAdmin'));
				}
			}
			if (isset($menu[3])) {
				foreach ($menu[3] as $sub_route => $sub_menu) {
					if ($sub_route == $this->route) {
						$slug = 'c/' . $page;
					} else {
						$slug = 'c/' . $sub_route;
					}
					add_submenu_page(
						'c/' . $sub_route,
						$sub_menu,
						$sub_menu,
						'read',
						$slug,
						array($this, 'loadAdmin'));
				}
			}
		}
	}

	/**
     * Admin page loader
     *
     * @return null
     */
	public function loadAdmin()
	{
		global $plugin_page;

		$view_dir = '/admin';
		$page = substr($plugin_page, 2);

		$controller_path = get_template_directory() . '/modules/controllers' . $view_dir;
		if (!isset($route_list[$page])) {
			if (file_exists($controller_path . '/' . $page . '.php')) {
				$view_name = $page;
			} else if (!empty($this->route)) {
				$view_name = $this->route;
			}
		} else {
			$view_name = $route_list[$page];
		}

		Config::setData('view_dir', get_template_directory() . '/modules/views' . $view_dir);
		Config::setData('layout_dir', get_template_directory() . '/modules/layouts/admin');

		if (!empty($this->args)) {
			extract($this->args);
		}
		$data = require($controller_path . '/' . $view_name . '.php');

		$view = new View($view_name, $data);
		$view->setSeparateAssets(false);
		$view->parse($view);
		print $view->getOutput();
	}

	/**
     * Public page loader
     *
     * @return null
     */
	public function loadPublic()
	{
	  $route_list = require(get_template_directory() . '/modules/config/public_routes.php');

	  $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	  $uri_segments = explode('/', $uri_path);
	  $status_code = 200;
	  $args = array();

	  
	  if (!empty($uri_segments[2])) {
	      $uri = implode('/', array_slice($uri_segments, 2));
	      if (isset($route_list[$uri])) {
	        $view_dir = '';
	        $view_name = $route_list[$uri];
	        $args = array_slice($uri_segments, 2);
	      } else {
	        $last_index = count($uri_segments) - 1;
	        $view_name = trim($uri_segments[$last_index], '.html');
	        $status_code = 404;
	      }
	  } else {
	      $view_name = 'index';
	      $view_dir = '/';
	  }

		if (!preg_match('/^(.*?)\..*?$/', $view_name) && $status_code == 200) {
			Config::setData('view_dir', get_template_directory() . '/modules/views' . $view_dir);
			$controller_path = get_template_directory() . '/modules/controllers' . $view_dir . '/' . $view_name . '.php';
			$data = require($controller_path);

			$view = new View($view_name, $data);
			$layout = new Layout('page', $data);
			$layout->parse($view);
			print $layout->getOutput();
			die;
		}
	}
}