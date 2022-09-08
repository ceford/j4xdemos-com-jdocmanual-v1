<?php
/**
 * @package     jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   Copyright (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

//use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

//$active_language = Factory::getApplication()->getUserState('com_jdocmanual.active_language', 'en');
//$scripts = "let jdocmanual_active_url = '{$this->jdocmanual_active_url}';\n";
//$scripts .= "let jdocmanual_active_language = '{$active_language}';\n";

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_jdocmanual.jdocmanual')
->useScript('com_jdocmanual.jdocmanual');
//->addInlineScript($scripts);

//$wa->registerAndUseStyle('jdocmanual-site', 'com_jdocmanual/jdocmanual-site.css', [], [], []);

Text::script('COM_JDOCMANUAL_JDOCMANUAL_TOC_IN_THIS_PAGE', true);

$proxy = false;

//HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.dropdown', 'select', []);
HTMLHelper::_('bootstrap.collapse');

?>

<?php $this->addToolbar(); ?>

<form action="<?php echo Route::_('index.php?option=com_jdocmanual'); ?>"
	method="post" name="adminForm" id="adminForm">

	<input type="hidden" name="task" id="task" value="">
	<input type="hidden" name="jform[manual_id]" id="jform_manual_id" value="<?php echo $this->manual_id; ?>">
	<input type="hidden" name="jform[index_language_code]" id="jform_index_language_code" value="<?php echo $this->index_language_code; ?>">
	<input type="hidden" name="jform[page_language_code]" id="jform_page_language_code" value="<?php echo $this->page_language_code; ?>">
	<input type="hidden" name="jform[page_path]" id="jform_page_path" value="<?php echo $this->page_path; ?>">
	<input type="hidden" name="jform[index_url]" id="jform_index_url" value="<?php echo $this->source->index_url; ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php if (empty($this->menu)) : ?>
	<?php
		echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_FIRST_FETCH_INDEX');
	?>
<?php else : ?>
	<h2><?php echo $this->source->title; ?></h2>

	<?php include 'site-layout.php'; ?>
<?php endif; ?>

