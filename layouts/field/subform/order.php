<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $tmpl            The Empty form for template
 * @var   array  $forms           Array of JForm instances for render the rows
 * @var   bool   $multiple        The multiple state for the form field
 * @var   int    $min             Count of minimum repeating in multiple mode
 * @var   int    $max             Count of maximum repeating in multiple mode
 * @var   string $name            Name of the input field.
 * @var   string $fieldname       The field name
 * @var   string $fieldId         The field ID
 * @var   string $control         The forms control
 * @var   string $label           The field label
 * @var   string $description     The field description
 * @var   string $class           Classes for the container
 * @var   array  $buttons         Array of the buttons that will be rendered
 * @var   bool   $groupByFieldset Whether group the subform fields by it`s fieldset
 */


$modalId = 'Shipping_EditModal_' . $fieldId;

// Load assets
/** @var \Joomla\CMS\Document\Document $document */
$document = Factory::getApplication()->getDocument();
$assets = $document->getWebAssetManager();
$assets->getRegistry()
	->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');

$assets->useScript('plg_radicalmart_shipping_apiship.administrator.order');
?>
<div id="<?php echo $fieldId; ?>" radicalmart-shipping-apiship-order="container"
	 data-modal_id="<?php echo $modalId; ?>">
	<button class="btn btn-primary" id="<?php echo $fieldId . '_open'; ?>"
			data-bs-toggle="modal"
			type="button"
			data-bs-target="#<?php echo $modalId; ?> ">
		<span class="icon-pen-square" aria-hidden="true"></span>
		<?php echo Text::_('JACTION_EDIT'); ?>
	</button>
	<?php echo HTMLHelper::_('bootstrap.renderModal', $modalId, [
		'title'  => Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ORDER_EDIT'),
		'footer' => $this->sublayout('footer', $displayData)
	], $this->sublayout('body', $displayData)); ?>
</div>