<?php
/**
 * @package     Jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   Copyright (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace J4xdemos\Component\Jdocmanual\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\ParameterType;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/**
 * Jdocmanual Component Controller
 *
 * @since  4.0.0
 */
class IndexController extends BaseController
{
	protected $app;

	/**
	 * Method to fetch, parse and store the Manual contents from docs.joomla.org
	 */
	public function fetch()
	{
		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));

		// need parameters for fetch
		$params = ComponentHelper::getParams('com_jdocmanual');

		$active_manual = $app->getUserState('com_jdocmanual.active_manual');
		if (empty($active_manual))
		{
			$active_manual = $params->get('default_manual');
		}
		$url = $params->get('manual' . $active_manual . '_url');
		// For Joomla 3
		//$url = 'https://help.joomla.org/J3.x:Doc_Pages';
		$active_language = $this->app->getUserState('com_jdocmanual.active_language', 'en');

		// if the language is not English add the language code
		$lang = ($active_language == 'en' ? '' : '/' . $active_language);

		// if the page does not exist the first header will be 404
		$content = @file_get_contents($url . $lang);
		if (empty($content))
		{
			$content = @\file_get_contents($url);
			if (empty($content))
			{
				$app->enqueueMessage(Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_INDEX_FAIL'), 'warning');
				return false;
			}
			if ($lang != 'en')
			{
				$app->enqueueMessage(Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_INDEX_ENGLISH'), 'warning');
				// check whether English content exists
				$query = $db->getQuery(true);
				$query->select('id')
				->from('`#__jdocmanual_menu`')
				->where('`menu_key` = :menu_key')
				->where('`language_code` = ' . $db->quote('en'))
				->bind(':menu_key' , $url, ParameterType::STRING);
				$db->setQuery($query);
				$fetched = $db->loadResult();
				if ($fetched)
				{
					return false;
				}
			}
		}

		$dom = new \DOMDocument;

		libxml_use_internal_errors(true);
		$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$newdom = new \DOMDocument;
		$newdom->formatOutput = true;
		$node = $dom->getElementById('jdocmanual-index');
		$node = $newdom->importNode($node, true);
		// And then append it to the "<root>" node
		$newdom->appendChild($node);

		// convert back to html
		$content = $newdom->saveHTML();

		// remove all instances of 'title=...'
		$pattern = '/ title=".*?"/sm';
		$content = \preg_replace($pattern, '', $content);

		// change the links
		// <a href="https://help.joomla.org/proxy?keyref=
		// <a class="content-link" href="#" data-content-id=
		$pattern = '/="\//sm';
		$replacement = '="#" class="content-link" data-content-id="';
		$content = \preg_replace($pattern, $replacement, $content);

		// now go through line by line
		$lines = preg_split("/((\r?\n)|(\r\n?))/", $content);

		$line1 = true;
		$buffer = '';
		$collapse = 1001;
		$collapse_level = 1;
		$item_level = 1;

		foreach($lines as $line)
		{
			// the first three lines:
			// <ul>
			// <li>Administrator
			// <ul>
			if ($line == '<ul>')
			{
				if ($line1)
				{
					$buffer = '<ul id="menu1001" class="nav flex-column main-nav metismenu">' . "\n";
					$line1 = false;
					continue;
				}
				else
				{
					$buffer .= '<ul id="collapse'.$collapse.'" class="collapse-level-'.$collapse_level.' mm-collapse">' . "\n";
					$collapse += 1;
					$item_level += 1;
					continue;
				}
			}
			elseif ($line == '</ul>')
			{
				$buffer .= $line . "\n";
				$item_level -= 1;
				continue;
			}
			elseif ($line =='</li>')
			{
				$buffer .= $line . "\n";
				continue;
			}
			$li_item_class = '<li class="item item-level-'.$item_level.'">';
			// title lines have no ending </li> and no link
			if (strpos($line, '<li>') === 0)
			{
				if (mb_strstr($line, '<a'))
				{
					// line with a link - add a file icon
					$line = \preg_replace('/(.*<a.*?>)/', '$1<span class="icon-file-alt icon-fw" aria-hidden="true"></span>', $line);
					$buffer .= \str_replace('<li>', $li_item_class, $line) . "\n";
					continue;
				}
				else
				{
					// line without a link so a heading
					// <li class="item parent item-level-1"><a class="has-arrow" href="#" aria-label="Administrator" aria-expanded="true"><span class="icon-file-alt icon-fw" aria-hidden="true"></span><span class="sidebar-item-title">Administrator</span></a>
					$title = str_replace('<li>', '', $line);
					$buffer .= '<li class="item parent item-level-' . $item_level . '"><a class="has-arrow" href="#" aria-label="' . $title . '" aria-expanded="true"><span class="icon-folder icon-fw" aria-hidden="true"></span><span class="sidebar-item-title">' . $title . '</span></a>' . "\n";
					continue;
				}
			}
		}

		// save the menu in the database
		$query = $db->getQuery(true);
		$query->insert('#__jdocmanual_menu')
		->set('language_code = :active_language')
		->set('menu_key = :menu_key')
		->set('menu = :menu')
		->bind(':active_language', $active_language, ParameterType::STRING)
		->bind(':menu_key', $url, ParameterType::STRING)
		->bind(':menu', $buffer, ParameterType::STRING);
		$db->setQuery($query);
		$db->execute();
	}
}