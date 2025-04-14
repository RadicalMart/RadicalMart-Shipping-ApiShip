<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2025 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Field\ApiShip;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class AjaxButtonField extends FormField
{
	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'plugins.radicalmart_shipping.apiship.field.ajax-button';

	/**
	 * Ajax task.
	 *
	 * @var  string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?string $task = null;

	/**
	 * Shipping method id.
	 *
	 * @var  int|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?int $shipping = null;

	/**
	 * Button icon.
	 *
	 * @var  string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?string $icon = '';

	/**
	 * Button text.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $text = '';

	/**
	 * Link client.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $client = 'site';

	/**
	 * Ajax response format client.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected string $format = 'raw';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value.
	 *
	 * @throws \Exception
	 *
	 * @return  bool  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null): bool
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->task     = (!empty($this->element['task'])) ? (string) $this->element['task'] : $this->task;
			$this->shipping = (!empty($this->element['shipping'])) ? (int) $this->element['shipping'] : $this->shipping;
			$this->client   = (!empty($this->element['client'])) ? (string) $this->element['client'] : $this->client;
			$this->format   = (!empty($this->element['format'])) ? (string) $this->element['format'] : $this->format;
			$this->icon     = (!empty($this->element['icon'])) ? (string) $this->element['icon'] : $this->icon;
			$this->text     = (!empty($this->element['text'])) ? Text::_((string) $this->element['text']) : $this->text;
		}

		return $return;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @throws  \Exception
	 *
	 * @return  array Layout data array.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getLayoutData(): array
	{
		$data             = parent::getLayoutData();
		$data['task']     = $this->task;
		$data['shipping'] = $this->shipping;
		$data['client']   = $this->client;
		$data['format']   = $this->format;
		$data['link']     = Route::link($this->client,
			'index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&id='
			. $this->shipping . '&task=' . $this->task . '&format=' . $this->format, false);
		$data['icon']     = $this->icon;
		$data['text']     = $this->text;

		return $data;
	}
}