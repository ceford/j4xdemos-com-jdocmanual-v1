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
		if (empty($active_manual))
		{
			// need parameters for fetch
			$params = ComponentHelper::getParams('com_jdocmanual');
			$active_manual = $params->get('default_manual');
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('menu')
		->from('#__jdocmanual_menu')
		->where('state = 1')
		->where('source_id = ' . $active_manual)
		->order('id desc');
		$db->setQuery($query);
		return $db->loadObject();
	}

}
