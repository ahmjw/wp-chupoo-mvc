<?php

namespace Introvesia\WpChupooMvc;

use Introvesia\PhpDomView\Layout;
use Introvesia\PhpDomView\View;
use Introvesia\PhpDomView\Config as ViewConfig;

class Base
{
	public function run()
	{
		ViewConfig::setData(array(
			'base_url' => get_home_url(),
			'layout_dir' => get_template_directory() . '/modules/layouts',
			'layout_url' => get_template_directory_uri(),
		));
		add_action( 'template_redirect', array($this, 'loadPublic') );
		add_action( 'admin_menu', array($this, 'adminMenu') );
	}

	public function adminMenu()
	{
	  $menus = require(get_template_directory() . '/modules/controllers/admin/menu.php');
	  foreach ($menus as $route => $menu) {
	    add_menu_page($menu[0], $menu[1], 'read', 'c/' . $route, array($this, 'loadAdmin'));
	  }
	}

	public function loadAdmin()
	{
		global $plugin_page;

		$routing = require(get_template_directory() . '/modules/admin_routes.php');
		$args = array();

		$view_dir = '/admin';
		$page = substr($plugin_page, 2);
		$view_name = $routing[$page];

		ViewConfig::setData('view_dir', get_template_directory() . '/modules/views' . $view_dir);
		ViewConfig::setData('layout_dir', get_template_directory() . '/modules/layouts/admin');

		$controller_path = get_template_directory() . '/modules/controllers' . $view_dir . '/' . $view_name . '.php';
		$data = require($controller_path);

		$view = new View($view_name, $data);
		$view->parse($view);
		print $view->getOutput();
	}

	public function loadPublic()
	{
	  $routing = require(get_template_directory() . '/modules/routes.php');

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