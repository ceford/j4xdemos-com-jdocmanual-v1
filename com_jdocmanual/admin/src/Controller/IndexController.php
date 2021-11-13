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
		$index_language = $this->app->getUserState('com_jdocmanual.index_language', 'en');

		// if the language is not English add the language code
		$lang = ($index_language == 'en' ? '' : '/' . $index_language);

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
				->from('#__jdocmanual_menu')
				->where('menu_key = :menu_key')
				->where('language_code = ' . $db->quote('en'))
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
		//<ul>
		//<li>Getting Started
		//<ul>
		//<li><a href="/Special:MyLanguage/J4.x:Getting_Started_with_Joomla!" title="Special:MyLanguage/J4.x:Getting Started with Joomla!">Getting Started with Joomla!</a></li>

		// remove all instances of 'title=...'
		$pattern = '/ title=".*?"/sm';
		$content = \preg_replace($pattern, '', $content);

		// change the links
		$pattern = '/="\/(?:Special:MyLanguage\/)/sm';
		$replacement = '="#" class="content-link" data-content-id="';
		$content = \preg_replace($pattern, $replacement, $content);
		//<ul>
		//<li>Getting Started
		//<ul>
		//<li><a href="#" class="content-link" data-content-id="J4.x:Getting_Started_with_Joomla!">Getting Started with Joomla!</a></li>echo $content;die;

		// now go through line by line
		$lines = preg_split("/((\r?\n)|(\r\n?))/", $content);

		$buffer = '<div class="accordion" id="accordionJdoc">' . "\n";
		$number = 1;
		$nlines = count($lines);

		// a line like the following is the start of a menu block
		// <li>Getting Started
		// a line with a terminating </li> is within a menu block
		foreach($lines as $i => $line)
		{
			// the last line of input is </div>
			if ($i == ($nlines - 4) || strpos($line, '</div>') !== false)
			{
				$buffer .= '</div>' . "\n";
				break;
			}
			if(strpos($line, '<li>') !== false)
			{
				if(strpos($line, '</li>') === false)
				{
					// this is menu level 1 - becomes accordion item
					$buffer .= '<div class="accordion-item">' . "\n";
					$buffer .= '<a href="#" class="accordion-header accordion-button jdocmenu-item" ';
					$buffer .= 'id="item_'.$number.'" ';
					$buffer .= 'data-bs-toggle="collapse" ';
					$buffer .= 'data-bs-target="#collapse_'.$number.'" ';
					$buffer .= 'aria-expanded="false" aria-controls="collapse_'.$number.'">' . "\n";
					$buffer .= substr($line, 4) . "\n";
					$buffer .= '</a>' . "\n";
					$buffer .= '<div id="collapse_'.$number.'" class="accordion-collapse collapse" aria-labelledby="item_'.$number.'" data-bs-parent="#accordionJdoc">' . "\n";
					$buffer .= '<div class="jdocmanual-accordion-body">' . "\n";
					$buffer .= '<ul>' . "\n";
					$number += 1;
				}
				else
				{
					// this is a regular link to go in the body
					// but only if it contains an anchor
					if (strpos($line, '<a ') !== false)
					{
						$line = str_replace('<a ', '<span class="icon-file-alt icon-fw icon-jdocmanual" aria-hidden="true"></span><a ', $line);
						$buffer .= $line  . "\n";
					}
				}
			}
			else if (strpos($line, '</ul>') === 0)
			{
				$buffer .= $line . "\n";
				$buffer .= '</div>' . "\n";
				$buffer .= '</div>' . "\n";
				$buffer .= '</div>' . "\n";
			}
		}

		// save the menu in the database
		$query = $db->getQuery(true);
		$query->insert('#__jdocmanual_menu')
		->set('language_code = :index_language')
		->set('menu_key = :menu_key')
		->set('menu = :menu')
		->bind(':index_language', $index_language, ParameterType::STRING)
		->bind(':menu_key', $url, ParameterType::STRING)
		->bind(':menu', $buffer, ParameterType::STRING);
		$db->setQuery($query);
		$db->execute();
	}
}