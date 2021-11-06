<?php
/**
 * @package     jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

class com_jdocmanualInstallerScript
{

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{

		$params = ComponentHelper::getParams('com_jdocmanual');
		if ($params == '{}')
		{
			$this->setParams();
		}

		return true;
	}
	/**
	 * Sets parameter values in the extensions row of the extension table.
	 */
	protected function setParams()
	{
		$params = '{"manual1_name":"Joomla 3","manual1_url":"https:\/\/docs.joomla.org\/J3.x:Doc_Pages","manual2_name":"Joomla 4","manual2_url":"https:\/\/docs.joomla.org\/J4.x:Doc_Pages","manual3_name":"","manual3_url":"","default_manual":"1","languages":"en"}';

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
		->update($db->quoteName('#__extensions'))
		->set('params = ' . $db->quote($params))
		->where("element = 'com_jdocmanual'");
		$db->setQuery($query)->execute();
	}
}