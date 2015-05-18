<?php
/**
 * RSS Feed Datasource
 *
 * Helps reading RSS feeds in CakePHP as if it were a model.
 *
 * PHP versions 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('Xml',  'Utility');

class RssSource extends DataSource {

/**
 * Default configuration options
 *
 * @var array
 */
	protected $_baseConfig = array(
		'feedUrl' => false,
		'encoding' => 'UTF-8',
		'cacheTime' => '+1 day',
		'version' => '2.0',
	);

	public $cacheSources = false;

/**
 * Should modify this method to ping or check url to see if it returns a valid
 * response.
 *
 * @return bool
 */
	public function isConnected() {
		return true;
	}

/**
 * read function.
 *
 * @param object &$model
 * @param array $queryData
 * @return array
 */
	public function read(Model $model, $queryData = array(), $recursive = NULL) {
		if (isset($model->feedUrl) && !empty($model->feedUrl)) {
			$this->config['feedUrl'] = $model->feedUrl;
		}
		$data = $this->_readData();

		$channel = Set::extract($data, 'rss.channel');
		if ( isset($channel['item']) ) {
			unset($channel['item']);
		}

		$items = Set::extract($data, 'rss.channel.item');

		if ($items) {
			foreach ($items as $key => $value) {
				if ($this->_checkConditions($value, $queryData['conditions']) === false) {
					unset($items[$key]);
				}
			}

			if (!empty($items)) {
				$items = $this->_sortItems($model, $items, $queryData['order']);
			}

			//used for pagination
			$items = $this->_getPage($items, $queryData);

			// return item count
			if (Set::extract($queryData, 'fields') == '__count') {
				return array(array($model->alias => array('count' => count($items))));
			}
		} else {
			if (Set::extract($queryData, 'fields') == '__count') {
				return array(array($model->alias => array('count' => count($items))));
			}
		}

		$result = array();
		if (is_array($items)) {
			foreach ($items as $item) {
				$item['channel'] = $channel;
				$result[] = array($model->alias => $item);
			}
		}
		return $result;
	}

/**
 * name function.
 *
 * @access public
 * @param mixed $name
 * @return void
 */
	public function name($name) {
		return $name;
	}

/**
 * _readData function.
 *
 * @return void
 */
	protected function _readData() {
		$config = $this->config;
		$feedUrl = $config['feedUrl'];
		$cacheTime = $config['cacheTime'];

		$cachePath = 'rss_' . md5($feedUrl);
		Cache::set(array('duration' => $cacheTime));
		$data = Cache::read($cachePath);

		if ($data === false) {
			$data = Set::reverse(
				Xml::build(
					$this->config['feedUrl'],
					array(
						'version' => $this->config['version'],
						'encoding' => $this->config['encoding']
					)
				)
			);

			Cache::set(array('duration' => $cacheTime));
			Cache::write($cachePath, serialize($data));
		} else {
			$data = unserialize($data);
		}

		return $data;
	}

	protected function _checkConditions($record, $conditions) {
		$result = true;
		if (empty($conditions)) {
			return $result;
		}
		foreach ($conditions as $name => $value) {
			if (strpos($name, '.') !== false) {
				list($alias, $name) = explode('.', $name);
			}

			if (strtolower($name) === 'or') {
				$cond = $value;
				$result = false;
				foreach ($cond as $name => $value) {
					if (strpos($name, '.') !== false) {
						list($condAlias, $name) = pluginSplit($name);
					}
					if (is_array($value)) {
						foreach ($value as $val) {
							if (Set::matches($this->_createRule($name, $val), $record)) {
								$result = true;
							}
						}
					} else {
						if (Set::matches($this->_createRule($name, $value), $record)) {
							$result = true;
						}
					}
				}
			} elseif (strtolower($name) === 'and') {
				$result = $this->_checkConditions($record, $value);
			} elseif (strtolower($name) === 'not') {
				$result = !$this->_checkConditions($record, $value);
			} else {
				if (Set::matches($this->_createRule($name, $value), $record) === false) {
					$result = false;
				}
			}
		}
		return $result;
	}

	protected function _createRule($name, $value) {
		if (is_numeric($name)) {
			return array($value);
		} elseif (strpos($name, ' ') !== false) {
			return array(str_replace(' ', '', $name) . $value);
		}
		return array("{$name}={$value}");
	}

/**
 * _getPage function.
 *
 * @param mixed $items
 * @param array $queryData
 * @return void
 */
	protected function _getPage($items = null, $queryData = array()) {
		if ( empty($queryData['limit']) ) {
			return $items;
		}

		$limit = $queryData['limit'];
		$page = $queryData['page'];

		$offset = $limit * ($page - 1);

		return array_slice($items, $offset, $limit);
	}

/**
 * _sortItems function.
 *
 * @param mixed &$model
 * @param mixed $items
 * @param mixed $order
 * @return void
 */
	protected function _sortItems(&$model, $items, $order) {
		if (empty($order) || empty($order[0])) {
			return $items;
		}

		$sorting = array();
		foreach ( $order as $orderItem ) {
			if ( is_string($orderItem) ) {
				$field = $orderItem;
				$direction = 'asc';
			} else {
				foreach ($orderItem as $field => $direction) {
					continue;
				}
			}

			$field = str_replace($model->alias . '.', '', $field);

			$values = Set::extract($items, '{n}.' . $field);
			if ( in_array($field, array('lastBuildDate', 'pubDate')) ) {
				foreach ($values as $i => $value) {
					$values[$i] = strtotime($value);
				}
			}
			$sorting[] =& $values;

			switch (strtolower($direction)) {
				case 'asc':
					$direction = SORT_ASC;
					break;
				case 'desc':
					$direction = SORT_DESC;
					break;
				default:
					trigger_error('Invalid sorting direction ' . strtolower($direction));
			}
			$sorting[] =& $direction;
		}

		$sorting[] =& $items;
		$sorting[] =& $direction;
		call_user_func_array('array_multisort', $sorting);

		return $items;
	}

/**
 * calculate function.
 *
 * @param mixed &$model
 * @param mixed $func
 * @param array $params
 * @return void
 */
	public function calculate(&$model, $func, $params = array()) {
		return '__' . $func;
	}

/**
 * This datasource does not support creating rss feeds
 *
 * @return void
 */
	public function create(Model $model, $fields = null, $values = null) {
		return false;
	}

/**
 * This datasource does not support updating rss feeds
 *
 * @return void
 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		return false;
	}

/**
 * This datasource does not support deleting rss feeds
 *
 * @return void
 */
	public function delete(Model $model, $conditions = null) {
		return false;
	}

}