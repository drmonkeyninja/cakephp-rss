# CakePHP Rss

RSS datasource plugin for CakePHP 2.

## Installation
[![License](https://poser.pugx.org/drmonkeyninja/cakephp-rss/license.png)](https://packagist.org/packages/drmonkeyninja/cakephp-rss) [![Build Status](https://travis-ci.org/drmonkeyninja/cakephp-rss.svg)](https://travis-ci.org/drmonkeyninja/cakephp-rss)

This plugin can be installed using Composer:-

	composer require drmonkeyninja/cakephp-rss

Alternatively copy the plugin to your app/Plugin directory and rename the plugin's directory 'Rss'.

Then add the following line to your bootstrap.php to load the plugin.

	CakePlugin::load('Rss');

## Usage

The values shown below under the Optional comment will be set to the values you see there if they are left out. The required options must exist. If you wish, you can make different database config property for each different feed you would like to work with. The feedUrl is an optional parameter that will be used by default if it is not set in the model.

	<?php
	// app/config/database.php
	class DATABASE_CONFIG {
		public $feedSource = array(

			/** Required **/
			'datasource' => 'Rss.RssSource',
			'database' => false,

			/** Optional **/
			'feedUrl' => 'http://feedurl',
			'encoding' => 'UTF-8',
			'cacheTime' => '+1 day',
		);
	}

Inside of each model that will consume an RSS feed, change the $useDbConfig property to the appropriate feed property from the database.php file. If you are instead using a single database config property, you would set the feed url in the model. The public $feedUrl is read before a read, and takes priority over the feedUrl set in the database config property.

	<?php
	// app/Model/Feed.php
	class Feed extends AppModel {
		public $useDbConfig = 'feedSource';
		/** Optional **/
		public $feedUrl = 'http://feedUrl';
	}

Then in your controller that uses the model, simply use the $this->Model->find('all');

	<?php
	// app/Controller/FeedsController.php
	class FeedsController extends AppController {
		public function index() {
			$this->set('feeds', $this->Feed->find('all'));
			return;
		}
	}