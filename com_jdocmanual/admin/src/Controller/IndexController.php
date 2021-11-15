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

		$node = $dom->getElementById('jdocmanual-index');

		$newdom = new \DOMDocument;
		$newdom->loadXML("<root><someelement>text in some element</someelement></root>");

		$node = $newdom->importNode($node, true);
		$newdom->documentElement->appendChild($node);

		$id = 1;
		$html = '<div class="accordion" id="accordionJdoc">';

		foreach ($newdom->getElementsByTagName('ul')->item(0)->childNodes as $node)
		{
			if ($node->nodeType === XML_ELEMENT_NODE)
			{
				if ($node->nodeName == 'li')
				{
					$value = $node->nodeValue;
					$lines = preg_split("/((\r?\n)|(\r\n?))/", $value);
					$html .= $this->accordion_start ($id, $lines[0]);
					$id += 1;
					foreach ($node->getElementsByTagName('a') as $child)
					{
						$html .= $this->accordion_item($child->getAttribute('href'), $child->nodeValue);
					}
					$html .= $this->accordion_end ();
				}
			}
		}
		$html .= "\n</div>\n";

		// save the menu in the database
		$query = $db->getQuery(true);
		$query->insert('#__jdocmanual_menu')
		->set('language_code = :index_language')
		->set('menu_key = :menu_key')
		->set('menu = :menu')
		->bind(':index_language', $index_language, ParameterType::STRING)
		->bind(':menu_key', $url, ParameterType::STRING)
		->bind(':menu', $html, ParameterType::STRING);
		$db->setQuery($query);
		$db->execute();
	}

	protected function accordion_start ($id, $label)
	{
		$html =<<<EOF
<div class="accordion-item">
<a href="#" class="accordion-header accordion-button jdocmenu-item" id="item_{$id}" data-bs-toggle="collapse" data-bs-target="#collapse_{$id}" aria-expanded="false" aria-controls="collapse_{$id}">
{$label}
</a>
<div id="collapse_{$id}" class="accordion-collapse collapse" aria-labelledby="item_{$id}" data-bs-parent="#accordionJdoc">
<div class="jdocmanual-accordion-body">
<ul>
EOF;
		return $html;
	}

	protected function accordion_end ()
	{
		return "\n</ul>\n</div>\n</div>\n</div>\n";
	}

	protected function accordion_item($link, $value)
	{
		$link = preg_replace('/\/Special:MyLanguage\//', '', $link);
		$html ='<li><span class="icon-file-alt icon-fw icon-jdocmanual" aria-hidden="true"></span>';
		$html .= '<a href="#" class="content-link" data-content-id="' . $link . '">';
		$html .= $value . '</a></li>' . "\n";
		return $html;
	}

}