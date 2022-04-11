<?php
/**
 * @package     jdocmanual.Administrator
 * @subpackage  com_jdocmanual
 *
 * @copyright   Copyright (C) 2021 Clifford E Ford
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

?>

	<?php if (empty($this->menu)) : ?>
		<?php
		echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_FIRST_FETCH_INDEX');
		?>
	<?php else : ?>
	<h2><?php echo $this->source->title; ?></h2>

	<div class="row">
		<div class="col-12 col-md-3 g-0">
			<nav id="jdocmanual-wrapper" aria-label="JDOC Manual Menu" class="sidebar-wrapper sidebar-menu">
				<?php echo $this->menu->menu; ?>
			</nav>
		</div>
		<div class="col-12 col-md-6">
			<div class="document-title">
			<h2 id="document-title">
			<?php echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_DOCUMENT_TITLE'); ?>
			</h2>
			</div>

			<div id="document-panel" tabindex="0">
				<?php echo Text::_('COM_JDOCMANUAL_JDOCMANUAL_FIRST_SELECT'); ?>
			</div>
		</div>
		<div class="col-12 col-md-3 d-none d-md-block" id="toc-panel">
		</div>
	</div>
	<?php endif; ?>
