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
class ContentController extends BaseController
{
	protected $app;
	protected $update = false;

	protected function setjdocmanualcookie($name, $value, $days)
	{
		if (!empty($days))
		{
			$offset = time() + $days*24*60*60;
		}
		else
		{
			$offset = 0;
		}
		$cookie_domain = $this->app->get('cookie_domain', '');
		$cookie_path   = $this->app->get('cookie_path', '/');
		$cookie = session_get_cookie_params();
		setcookie($name, $value, $offset, $cookie_path, $cookie_domain, $cookie['secure'], true);
	}

	public function update()
	{
		$this->update = true;
		$this->fill();
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
	}

	public function fillpanel()
	{
		if (!Session::checkToken('post'))
		{
			// if the session has expired a login form will appear
			// but the token will be invalid so redirect to jdocmanual page
			$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
			return;
		}
		//$this->setjdocmanualcookie('jdocmanualReset', '', 0);
		$this->fill();
	}

	protected function fill()
	{
		$db = Factory::getDbo();

		$manual_id = $this->app->input->get('manual_id', 0, 'int');
		$item_id = $this->app->input->get('item_id', '', 'string');
		$page_language_code = $this->app->input->get('page_language_code', '', 'string');

		$page_language_code = empty($page_language_code) ? 'en' : $page_language_code;

		if ($this->update !== true)
		{
			// is the required page already downloaded?
			$query = $db->getQuery(true);

			$query->select('jp.jdoc_key, jp.content')
			->from('#__jdocmanual_pages AS jp')
			->where('jp.jdoc_key = :itemId')
			->where('jp.language_code = :page_language_code')
			->bind(':itemId', $item_id, ParameterType::STRING)
			->bind(':page_language_code', $page_language_code, ParameterType::STRING);
			$db->setQuery($query);
			$row = $db->loadObject();

			// if content is not empty echo and exit
			if (!empty($row->content))
			{
				$this->send_template($row->content);
			}
		}

		// fetch the page from source

		$query = $db->getQuery(true);
		$query->select('index_url')
		->from('#__jdocmanual_sources')
		->where('id = :id')
		->bind(':id', $manual_id);
		$db->setQuery($query);
		$url = $db->loadResult();

		$image_url = 'https://' . parse_url($url, PHP_URL_HOST);

		// keep everything up to the last /
		$url = substr($url, 0, strrpos($url,'/')+1);

		// if the url contains proxy
		if (strpos($url, 'proxy') !== false)
		{
			$url .= '?page=';
		}

		// if the language is not English add the language code
		$lang = ($page_language_code == 'en' ? '' : '/' . $page_language_code);
		$lang_unavailable = false;

		// if the page does not exist the first header will be 404

		$content = @file_get_contents($url . $item_id . $lang);
		if (empty($content))
		{
			$content = file_get_contents($url . $item_id);
			if (empty($content))
			{
				echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_PAGE_FAIL');
				jexit();
			}
			$lang_unavailable = true;
		}
		$dom = new \DOMDocument;

		libxml_use_internal_errors(true);
		$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$newdom = new \DOMDocument;
		$newdom->formatOutput = true;
		$node = $dom->getElementById('mw-content-text');

		// node could be empty so try to get the outermost div
		if (empty($node))
		{
			$node = $dom->getElementsByTagName('div')[0]; // 'mw-parser-output')[0];
		}

		$node = $newdom->importNode($node, true);

		// And then append it to the "<root>" node
		$newdom->appendChild($node);

		// remove nodes not relevant in the manual
		$xpath = new \DOMXPath($newdom);

		// not all of these nodes are in all documents
		foreach($xpath->query('//div[contains(attribute::class, "mw-pt-translate-header")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		foreach($xpath->query('//hr') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		foreach($xpath->query('//div[contains(attribute::class, "hf-nsheader")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		foreach($xpath->query('//div[contains(attribute::class, "hf-header")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		foreach($xpath->query('//div[contains(attribute::class, "mw-pt-languages")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		foreach($xpath->query('//div[contains(attribute::style, "clear:both")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		foreach($xpath->query('//div[contains(attribute::class, "toc")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}
		// the [Edit] after a title
		foreach($xpath->query('//span[contains(attribute::class, "mw-editsection")]') as $e )
		{
			$e->parentNode->removeChild($e);
		}

		$content = $newdom->saveHTML();

		// remove empty paragraphs
		$pattern = '/<p>\s*(?:<br>)\s*/sm';
		$content = \preg_replace($pattern, '', $content);

		// imges need a full url src="/... to src="http.../
		$pattern = '/src="/';
		$replace =  'src="' . $image_url;
		$content = \preg_replace($pattern, $replace, $content);

		// tricky - do first item in srcset
		$pattern = '/srcset="/';
		$replace =  'srcset="' . $image_url;
		$content = \preg_replace($pattern, $replace, $content);

		// so any images with preceding space must be in a srcset
		$pattern = '/,\s\/images\//';
		$replace =  ', ' . $image_url . '/images/';
		$content = \preg_replace($pattern, $replace, $content);

		// but my mediawiki site is planting inline style
		$pattern = '/style="width:\d{1,}px;"/';
		$replace =  '';
		$content = \preg_replace($pattern, $replace, $content);

		// remove links
		$pattern = '/<a .*?>(.*?)<\/a>/';
		$content = preg_replace($pattern, '$1', $content);

		// change class="alert-box" to class="alert alert-info" role="alert"
		$pattern = '/class="alert-box"/';
		$replace = '/class="alert alert-info" role="alert"/';
		$content = preg_replace($pattern, $replace, $content);

		if ($lang_unavailable)
		{
			$mod = '<div class="alert alert-info">';
			$mod .= Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_PAGE_FAIL_LANGUAGE');
			$mod .= "</div>\n";
			$content =  $mod . $content;
		}

		// and store it locally
		$query = $db->getQuery(true);
		if ($this->update)
		{
			$query->update('#__jdocmanual_pages')
			->where('jdoc_key = :id')
			->bind(':id', $item_id);
		} else {
			$query->insert('#__jdocmanual_pages')
			->set('jdoc_key = :id')
			->bind(':id', $item_id);
		}
		$query->set('language_code = ' . $db->quote($page_language_code))
		->set('content = ' . $db->quote($content));
		$db->setQuery($query);
		$db->execute();
		$this->send_template($content);
	}

	protected function send_template($content)
	{
		echo '<div id="scroll-panel">';
		echo $content;
		echo '</div>';
		if ($this->update)
		{
			$this->app->enqueueMessage(Text::_('COM_JDOCMANUAL_JDOCMANUAL_UPDATE_PAGE_SUCCESS'), 'success');
			return;
		}
		exit;
	}
}