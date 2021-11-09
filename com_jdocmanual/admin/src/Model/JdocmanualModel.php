<?php
/**
 * @package     jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   Copyright (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace J4xdemos\Component\Jdocmanual\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Methods supporting a list of jdocmanual records.
 *
 * @since  1.6
 */
class JdocmanualModel extends ListModel
{

	public function getPage()
	{
	}

	public function getMenu()
	{
		$app = Factory::getApplication();
		$active_manual = $app->getUserState('com_jdocmanual.active_manual');
		$params = ComponentHelper::getParams('com_jdocmanual');
		if (empty($active_manual))
		{
			// need parameters for fetch
			$active_manual = $params->get('default_manual');
			$app->setUserState('com_jdocmanual.active_manual', $active_manual);
		}
		$url = $params->get('manual' . $active_manual . '_url');

		$active_language = $app->getUserState('com_jdocmanual.active_language');
		if (empty($active_language))
		{
			$active_language = 'en';
			$app->setUserState('com_jdocmanual.active_language', $active_language);
		}
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('menu')
		->from('#__jdocmanual_menu')
		->where('state = 1')
		->where('menu_key = :menu_key')
		->where('language_code = :language_code')
		->bind(':menu_key', $url, ParameterType::STRING)
		->bind(':language_code', $active_language, ParameterType::STRING)
		->order('id desc');
		//var_dump($url, $active_language, $query->__tostring());die;
		$db->setQuery($query);
		$menu = $db->loadObject();
		if (empty($menu && $active_language != 'en'))
		{
			// try again with English
			$query = $db->getQuery(true);
			$query->select('menu')
			->from('#__jdocmanual_menu')
			->where('state = 1')
			->where('menu_key = :menu_key')
			->where('language_code = ' . $db->quote('en'))
			->bind(':menu_key', $url, ParameterType::STRING)
			->order('id desc');
			//var_dump($url, $active_language, $query->__tostring());die;
			$db->setQuery($query);
			$menu = $db->loadObject();
		}
		return $menu;
	}
}
