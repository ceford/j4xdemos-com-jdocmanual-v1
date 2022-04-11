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
		$jform = $this->app->input->get('jform', array(), 'array');
		$manual_id = $jform['manual_id'];
		$index_language_code = $jform['index_language_code'];

		$this->app->enqueueMessage('Index updated for manual #' . $manual_id . ' in ' . $index_language_code, 'warning');

		$db = Factory::getDbo();
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));

		// Get url from the sources table
		$query = $db->getQuery(true);
		$query->select('index_url')
		->from('#__jdocmanual_sources')
		->where('id = :id' )
		->bind(':id', $manual_id);
		$db->setQuery($query);
		$url = $db->loadResult();

		// Check if pages are being delivered by a proxy
		$proxy = true;
		if (strpos($url, '/proxy/') === false)
		{
			$proxy = false;
		}


		// if the language is not English add the language code
		$lang = ($index_language_code == 'en' ? '' : '/' . $index_language_code);

		// if the page does not exist the first header will be 404
		// suppress the error message
		$content = @file_get_contents($url . $lang);

		if (empty($content))
		{
			// suppress the error message
			$content = @\file_get_contents($url);
			if (empty($content))
			{
				$this->app->enqueueMessage(Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_INDEX_FAIL'), 'warning');
				return false;
			}
			if ($lang != 'en')
			{
				$this->app->enqueueMessage(Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_INDEX_ENGLISH'), 'warning');
				// check whether English content exists
				$query = $db->getQuery(true);
				$query->select('id')
				->from('#__jdocmanual_menu')
				->where('source_id = :source_id')
				->where('language_code = ' . $db->quote('en'))
				->bind(':source_id' , $manual_id);
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
						// if no proxy is in use the url is like this
						// /Special:MyLanguage/Welcome OR
						// /mw/index.php?title=Welcome

						// if a proxy is in use the url is like this
						// /proxy?keyref=Help40:Articles&lang=en OR
						// /proxy/?page=Welcome
						// And
						// /mw/index.php?title=Welcome
						// needs to be converted to
						// /proxy/?page=Welcome
						$href = $child->getAttribute('href');
						if ($proxy)
						{
							$href = substr($href, (strpos($href, '=')+1));
						}
						$html .= $this->accordion_item($href, $child->nodeValue);
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
		->set('source_id = :source_id')
		->set('menu = :menu')
		->bind(':index_language', $index_language_code, ParameterType::STRING)
		->bind(':source_id', $manual_id)
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