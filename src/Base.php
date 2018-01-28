<?php

namespace Introvesia\WpChupooMvc;

use Introvesia\PhpDomView\Layout;
use Introvesia\PhpDomView\View;
use Introvesia\PhpDomView\Config as ViewConfig;
use Introvesia\Chupoo\Models\Db;

class Base
{
	public function run()
	{
		global $wpdb;

		ViewConfig::setData(array(
			'base_url' => get_home_url(),
			'layout_dir' => get_template_directory() . '/modules/layouts',
			'layout_url' => get_template_directory_uri(),
		));

		Db::setConnection($wpdb->dbh);

		spl_autoload_register(array($this, 'loadClass'));
		set_exception_handler(array($this, 'handleException'));
		add_action( 'template_redirect', array($this, 'loadPublic') );
		add_action( 'admin_menu', array($this, 'adminMenu') );
		add_action( 'wp_loaded', array($this, 'pageLoaded') );
	}

	public function loadClass($className)
	{
		$path = get_template_directory() . '/modules/models/' . substr($className, 7) . '.php';
		include($path);
	}

	public function handleException($exc)
	{
		print_r($exc);
	}

	public function pageLoaded()
	{
		current_user_can('read');
	}

	public function adminMenu()
	{
	  current_user_can('read');
	  $menus = require(get_template_directory() . '/modules/config/admin_menu.php');
	  foreach ($menus as $route => $menu) {
	    add_menu_page(
	    	$menu[0], 
	    	$menu[1], 
	    	'read', 
	    	'c/' . $route,
	    	array($this, 'loadAdmin'));
	    if (isset($menu[2])) {
	    	foreach ($menu[2] as $sub_route => $sub_menu) {
	    		add_submenu_page(
			    	'c/' . $route,
			        $sub_menu[0],
			        $sub_menu[1],
			        'read',
			        'c/' . $sub_route,
			        array($this, 'loadAdmin'));
	    	}
	    }
	    if (isset($menu[3])) {
	    	foreach ($menu[3] as $sub_route => $sub_menu) {
	    		add_submenu_page(
			    	null,
			        $sub_menu[0],
			        $sub_menu[1],
			        'read',
			        'c/' . $sub_route,
			        array($this, 'loadAdmin'));
	    	}
	    }
	  }
	}

	public function loadAdmin()
	{
		global $plugin_page;

		$routing = require(get_template_directory() . '/modules/config/admin_routes.php');
		$args = array();

		$view_dir = '/admin';
		$page = substr($plugin_page, 2);
		$view_name = $routing[$page];

		ViewConfig::setData('view_dir', get_template_directory() . '/modules/views' . $view_dir);
		ViewConfig::setData('layout_dir', get_template_directory() . '/modules/layouts/admin');

		$controller_path = get_template_directory() . '/modules/controllers' . $view_dir . '/' . $view_name . '.php';
		$data = require($controller_path);

		$view = new View($view_name, $data);
		$view->setSeparateAssets(false);
		$view->parse($view);
		print $view->getOutput();
	}

	public function loadPublic()
	{
	  $routing = require(get_template_directory() . '/modules/config/public_routes.php');

	  $uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	  $uri_segments = explode('/', $uri_path);
	  $status_code = 200;
	  $args = array();

	  
	  if (!empty($uri_segments[2])) {
	      $uri = implode('/', array_slice($uri_segments, 2));
	      if (isset($routing[$uri])) {
	        $view_dir = '';
	        $view_name = $routing[$uri];
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
			ViewConfig::setData('view_dir', get_template_directory() . '/modules/views' . $view_dir);
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