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

	public function selectlanguage()
	{
		$jform = $this->app->input->get('jform', array(), 'array');
		$language = trim($jform['language']);
		$this->app->setUserState('com_jdocmanual.active_language', $language);
		setcookie('jdocmanualReset', 'reset', time() + 3600);
		setcookie('jdocmanualTitle', '', time() - 3600);
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
	}

	public function selectindexlanguage()
	{
		$jform = $this->app->input->get('jform', array(), 'array');
		$language = trim($jform['language']);
		$this->app->setUserState('com_jdocmanual.index_language', $language);
		setcookie('jdocmanualReset', 'reset', time() + 3600);
		setcookie('jdocmanualTitle', '', time() - 3600);
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
	}

	public function selectmanual1()
	{
		$this->reset(1);
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
	}

	public function selectmanual2()
	{
		$this->reset(2);
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
	}

	public function selectmanual3()
	{
		$this->reset(3);
		$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
	}

	protected function reset($id)
	{
		// unset the cookies
		setcookie('jdocmanualReset', 'reset', time() + 3600);
		setcookie('jdocmanualItemId', '', time() - 3600);
		setcookie('jdocmanualTitle', '', time() - 3600);
		// set the active manual
		$this->app->setUserState('com_jdocmanual.active_manual', $id);
	}

	public function update()
	{
		$id = $this->app->getUserState('com_jdocmanual.active_page');
		if (empty($id))
		{
			$this->app->enqueueMessage(Text::_('COM_JDOCMANUAL_JDOCMANUAL_UPDATE_PAGE_FAIL_NO_ID'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_jdocmanual&view=jdocmanual', false));
			return;
		}
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
		setcookie('jdocmanualReset', 'reset', time() - 3600);
		$this->fill();
	}

	protected function get_http_response_code($url) {
		$headers = get_headers($url);
		return substr($headers[0], 9, 3);
	}

	protected function fill()
	{
		$db = Factory::getDbo();
		$active_language = $this->app->getUserState('com_jdocmanual.active_language', 'en');
		if ($this->update == true)
		{
			$id = $this->app->getUserState('com_jdocmanual.active_page');
		}
		else
		{
			$id = $this->input->getString('itemId');
			// is the required page already downloaded?
			$query = $db->getQuery(true);
			$query->select('jp.jdoc_key, jp.content')
			->from('#__jdocmanual_pages AS jp')
			->where('jp.jdoc_key = :itemId')
			->where('language_code = ' . $db->quote($active_language))
			->bind(':itemId', $id, ParameterType::STRING);

			$db->setQuery($query);
			$row = $db->loadObject();
			$this->app->setUserState('com_jdocmanual.active_page', $id);

			// if content is not empty echo and exit
			if (!empty($row->content))
			{
				$this->send_template($row->content);
			}
		}

		// fetch the page from source

		// need parameters for fetch
		$params = ComponentHelper::getParams('com_jdocmanual');

		$active_manual = $this->app->getUserState('com_jdocmanual.active_manual');
		if (empty($active_manual))
		{
			$active_manual = $params->get('default_manual');
		}
		$url = $params->get('manual' . $active_manual . '_url');

		// keep everything up to the last /
		$url = substr($url, 0, strrpos($url,'/')+1);

		// if the language is not English add the language coe
		$lang = ($active_language == 'en' ? '' : '/' . $active_language);
		$lang_unavailable = false;
		// if the page does not exist the first header will be 404
		$content = @file_get_contents($url . $id . $lang);
		if (empty($content))
		{
			$content = file_get_contents($url . $id);
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

		$content = $newdom->saveHTML();

		// remove empty paragraphs
		$pattern = '/<p>\s*(?:<br>)\s*/sm';
		$content = \preg_replace($pattern, '', $content);

		// imges need a full url src="/... to src="http.../
		$pattern = '/\/images\//';
		$replace = $url . '\/images\//';
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
			->bind(':id', $id);
		} else {
			$query->insert('#__jdocmanual_pages')
			->set('jdoc_key = :id')
			->bind(':id', $id);
		}
		$query->set('language_code = ' . $db->quote($active_language))
		->set('content = ' . $db->quote($content));
		$db->setQuery($query);
		$db->execute();
		$this->app->setUserState('com_jdocmanual.active_page', $id);
		$this->send_template($content);
	}

	protected function send_template($content)
	{
		echo '<div id="scroll-panel">';
		echo $content;
		echo '</div>';
		if ($this->update)
		{
			return;
		}
		jexit();
	}
}