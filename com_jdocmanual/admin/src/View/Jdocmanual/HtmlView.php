<?php
/**
 * @package     jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   Copyright (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace J4xdemos\Component\Jdocmanual\Administrator\View\Jdocmanual;

\defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

/**
 * View class for jdocmanual.
 *
 * @since  4.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The search tools form
	 *
	 * @var    Form
	 * @since  1.6
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var    array
	 * @since  1.6
	 */
	public $activeFilters = [];

	/**
	 * Category data
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $categories = [];

	/**
	 * An array of items
	 *
	 * @var    array
	 * @since  1.6
	 */
	protected $items = [];

	/**
	 * The pagination object
	 *
	 * @var    Pagination
	 * @since  1.6
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var    Registry
	 * @since  1.6
	 */
	protected $state;


	protected $active_manual;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  void
	 *
	 * @since   1.6
	 * @throws  Exception
	 */
	public function display($tpl = null): void
	{
		/** @var JdocmanualModel $model */
		$model               = $this->getModel();
		$this->page          = $model->getPage();
		$this->menu          = $model->getMenu();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar(): void
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_jdocmanual');
		$active_manual = $app->getUserState('com_jdocmanual.active_manual');
		$active_language = $app->getUserState('com_jdocmanual.active_language', 'en');
		$index_language = $app->getUserState('com_jdocmanual.index_language', 'en');

		if (empty($active_manual))
		{
			$active_manual = $params->get('default_manual');
		}
		$manual = $params->get('manual' . $active_manual . '_name');
		$manual_url = $params->get('manual' . $active_manual . '_url');
		$this->jdocmanual_active_url = substr($manual_url, 0, strrpos($manual_url, '/') + 1);

		ToolbarHelper::title($manual . ' (' . $active_language . ')', 'book');

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		$dropdown = $toolbar->dropdownButton('select-manual')
		->text('COM_JDOCMANUAL_JDOCMANUAL_MANUAL_SELECT')
		->toggleSplit(false)
		->icon('icon-code-branch')
		->buttonClass('btn btn-action');

		$childBar = $dropdown->getChildToolbar();

		// ToDo: change to cycle through manuals from params
		$icon = $active_manual == 1 ? 'icon-check' : '';
		$childBar->standardButton('manual1')
		->text($params->get('manual1_name'))
		->icon($icon)
		->task('content.selectmanual1');

		$icon = $active_manual == 2 ? 'icon-check' : '';
		if (!empty($params->get('manual2_name')))
		{
			$childBar->standardButton('manual2')
			->text($params->get('manual2_name'))
			->icon($icon)
			->task('content.selectmanual2');
		}

		$icon = $active_manual == 3 ? 'icon-check' : '';
		if (!empty($params->get('manual3_name')))
		{
			$childBar->standardButton('manual3')
			->text($params->get('manual3_name'))
			->icon($icon)
			->task('content.selectmanual3');
		}

		$dropdown = $toolbar->dropdownButton('select-language')
		->text('COM_JDOCMANUAL_JDOCMANUAL_LAGUAGE_SELECT')
		->toggleSplit(false)
		->icon('icon-language')
		->buttonClass('btn btn-action');

		$childBar = $dropdown->getChildToolbar();

		$languages = $params->get('languages');
		$languages_list = (array) explode(',', $languages);

		$childBar->separatorButton('page')
		->text('Content Language');

		foreach ($languages_list as $language)
		{
			$icon = '';
			if ($active_language == $language)
			{
				$icon = 'icon-check';
			}
			$childBar->standardButton($language)
			->text($language)
			->buttonClass('set-language')
			->task('content.selectlanguage')
			->icon($icon);
		}

		$childBar->separatorButton('index')
		->text('Index Language');

		foreach ($languages_list as $language)
		{
			$icon = '';
			if ($index_language == $language)
			{
				$icon = 'icon-check';
			}
			$childBar->standardButton($language)
			->text($language)
			->buttonClass('set-language index')
			->task('content.selectindexlanguage')
			->icon($icon);
		}

		$toolbar->standardButton('update-page')
		->text(Text::_('COM_JDOCMANUAL_JDOCMANUAL_UPDATE_PAGE'))
		->task('content.update')
		->icon('icon-upload');

		$user  = Factory::getUser();

		if ($user->authorise('core.admin', 'com_jdocmanual') || $user->authorise('core.options', 'com_jdocmanual'))
		{
			$toolbar->standardButton('index')
			->text(Text::_('COM_JDOCMANUAL_JDOCMANUAL_FETCH_INDEX'))
			->task('index.fetch')
			->icon('icon-download');

			$toolbar->preferences('com_jdocmanual');
		}

		$tmpl = $app->input->getCmd('tmpl');
		if ($tmpl !== 'component')
		{
			ToolbarHelper::help('jdocmanual', true);
		}
	}
}

