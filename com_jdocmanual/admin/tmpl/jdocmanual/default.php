<?php
/**
 * @package     jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   Copyright (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$params = ComponentHelper::getParams('com_jdocmanual');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_jdocmanual.jdocmanual')
->useScript('com_jdocmanual.jdocmanual');

Text::script('COM_JDOCMANUAL_JDOCMANUAL_TOC_IN_THIS_PAGE', true);

?>

<form action="<?php echo Route::_('index.php?option=com_jdocmanual'); ?>"
	method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="task" id="task" value="">
	<input type="hidden" name="jform[language]" id="jform_language" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

	<?php if (empty($this->menu->menu)) : ?>
		<?php
		echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_FIRST_FETCH_INDEX');
		?>
	<?php else : ?>
	<div class="row">
		<div class="col-12 col-md-3 p-2" id="toggle-joomla-menu">
			<a id="jd-collapse" href="#" aria-label="Toggle Joomla Menu">
				<span id="jdocmanual-collapse-icon" class="icon-fw icon-toggle-off" aria-hidden="true"></span>
				<span class="sidebar-item-title"><?php echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_TOGGLE_JOOMLA_MENU'); ?></span>
			</a>
		</div>

		<div class="col-12 col-md-9 document-title">
			<h1 id="document-title">
			<?php echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_DOCUMENT_TITLE'); ?>
			</h1>
		</div>
	</div>

	<div class="row">
		<div class="col-12 col-md-3 g-0">
			<nav id="jdocmanual-wrapper" aria-label="JDOC Manual Menu" class="sidebar-wrapper sidebar-menu">
				<?php echo $this->menu->menu; ?>
			</nav>
		</div>
		<div class="col-12 col-md-9 document-title">
			<div id="jdocmanual-main" class="row g-0">
			<div class="col col-md-8" id="document-panel" tabindex="0">
				<div>
					<?php echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_FIRST_SELECT'); ?>
				</div>
			</div>
			<div class="col-12 col-md-4 d-none d-md-block ps-3" id="toc-panel">
			</div>
		</div>
	</div>
	<?php endif; ?>

