<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\RadicalMart\Administrator\Helper\CommandsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\NumberHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PermissionsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\UserHelper;
use Joomla\Component\RadicalMart\Administrator\Model\CommandsModel;
use Joomla\Component\RadicalMart\Administrator\Model\OrderModel;
use Joomla\Component\RadicalMart\Site\Model\CheckoutModel;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Input\Input;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Console\UpdateStatusesCommand;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Console\UpdateStatusesLegacyCommand;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\AddressHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\ApiShipHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\CacheHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\DaDataHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\LogHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Traits\RetailCRMTrait;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class ApiShip extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use MVCFactoryAwareTrait;
	use RetailCRMTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Enable on RadicalMart
	 *
	 * @var  bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public bool $radicalmart = true;

	/**
	 * Default shipping fields params.
	 *
	 * @var array|string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static array $defaultAddressFieldsParams = [
		'uid'       => 'hidden',
		'provider'  => 'required',
		'country'   => 'required',
		'region'    => 'not_required',
		'city'      => 'required',
		'zip'       => 'required',
		'street'    => 'required',
		'house'     => 'required',
		'building'  => 'not_required',
		'entrance'  => 'not_required',
		'floor'     => 'not_required',
		'apartment' => 'not_required',
		'string'    => 'hidden',
		'display'   => 'hidden',
		'comment'   => 'not_required'
	];

	/**
	 * Shipping method params cache.
	 *
	 * @var Registry[]|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static ?array $_shippingParams = null;

	/**
	 * Map markers array cache.
	 *
	 * @var string[]|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static ?array $_mapMarkers = null;

	/**
	 * Is RadicalMart 3 installed.
	 *
	 * @var bool|null
	 *
	 * @since      __DEPLOY_VERSION__
	 */
	protected null|bool $_isRadicalMart3 = null;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartPrepareMethodForm' => 'onRadicalMartPrepareMethodForm',

			'onRadicalMartGetOrderShipping'        => 'onRadicalMartGetOrderShipping',
			'onRadicalMartGetOrderShippingMethods' => 'onRadicalMartGetOrderShippingMethods',
			'onRadicalMartGetOrderTotal'           => 'onRadicalMartGetOrderTotal',
			'onRadicalMartGetOrderLogs'            => 'onRadicalMartGetOrderLogs',
			'onRadicalMartGetOrderForm'            => 'onRadicalMartGetOrderForm',

			'onRadicalMartLoadOrderMethodFormData'      => 'onRadicalMartLoadOrderMethodFormData',
			'onRadicalMartPrepareOrderMethodSaveData'   => 'onRadicalMartPrepareOrderMethodSaveData',
			'onRadicalMartAfterOrderSave'               => 'onRadicalMartAfterOrderSave',
			'onRadicalMartPrepareAdministratorListItem' => 'onRadicalMartPrepareAdministratorListItem',

			'onRadicalMartGetPersonalShippingMethods'    => 'onRadicalMartGetPersonalShippingMethods',
			'onRadicalMartPrepareCustomerMethodSaveData' => 'onRadicalMartPrepareCustomerMethodSaveData',
			'onRadicalMartGetCustomerMethodForm'         => 'onRadicalMartGetCustomerMethodForm',
			'onRadicalMartGetPersonalMethodForm'         => 'onRadicalMartGetCustomerMethodForm',
			'onRadicalMartGetCheckoutCustomerData'       => 'onRadicalMartGetCheckoutCustomerData',

			'onRadicalMartAfterChangeOrderStatus'   => 'onRadicalMartAfterChangeOrderStatus',
			'onRadicalMartGetAdministratorCommands' => 'onRadicalMartGetAdministratorCommands',
			'onRadicalMartRegisterCLICommands'      => 'onRadicalMartRegisterCLICommands',
			'onAjaxApiship'                         => 'onAjax',
			'callback'                              => 'apiCallback',

			'onRadicalMartRetailCRMExportShippingMethodData' => 'onRadicalMartRetailCRMExportShippingMethodData',
			'onRadicalMartRetailCRMExportOrderData'          => 'onRadicalMartRetailCRMExportOrderData'
		];
	}

	/**
	 * Loads the plugin language file.
	 *
	 * @param   string  $extension  The extension for which a language file should be loaded.
	 * @param   string  $basePath   The basepath to use.
	 *
	 * @throws \Exception
	 * @return  boolean  True, if the file has successfully loaded.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function loadLanguage($extension = '', $basePath = JPATH_ADMINISTRATOR): bool
	{
		$result = parent::loadLanguage($extension, $basePath);
		if (!$result)
		{
			return false;
		}

		parent::loadLanguage('com_radicalmart',
			(Factory::getApplication()->isClient('site')) ? JPATH_SITE : JPATH_ADMINISTRATOR);

		return true;
	}

	/**
	 * Prepare method params form.
	 *
	 * @param   Form   $form     Shipping method form object.
	 * @param   mixed  $data     The data expected for the form.
	 * @param   mixed  $tmpData  The  temporary form data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareMethodForm(Form $form, mixed $data, mixed $tmpData): void
	{
		$registry      = new Registry($tmpData);
		$params        = new Registry($registry->get('params'));
		$id            = (int) $registry->get('id', 0);
		$delivery_type = (int) $params->get('delivery_type', 2);

		$pointFields   = [
			'points_map_key',
			'points_files',
		];
		$addressFields = [
			'field_country',
			'field_region',
			'field_city',
			'field_zip',
			'field_street',
			'field_house',
			'field_building',
			'field_entrance',
			'field_floor',
			'field_apartment',
			'fields_default',
			'dadata_token',
			'dadata_secret',
		];

		if ($delivery_type === 1)
		{
			foreach ($pointFields as $field)
			{
				$form->removeField($field, 'params');
			}
		}
		elseif ($delivery_type === 2)
		{
			foreach ($addressFields as $field)
			{
				$form->removeField($field, 'params');
			}

			if ($id > 0)
			{
				$form->setFieldAttribute('points_files', 'shipping', $id, 'params');
			}
			else
			{
				$form->removeField('points_files', 'params');
			}
		}
		else
		{
			foreach ($pointFields as $field)
			{
				$form->removeField($field, 'params');
			}
			foreach ($addressFields as $field)
			{
				$form->removeField($field, 'params');
			}
		}

		$form->setFieldAttribute('get_lists', 'shipping', $id, 'params');
		$form->setFieldAttribute('get_webhooks', 'shipping', $id, 'params');
		$form->setFieldAttribute('webhook', 'shipping', $id, 'params');

		if (!$this->isRetailCRMEnabled())
		{
			$form->removeField('api_orders_retailcrm_mapping', 'params');
		}
	}

	/**
	 * Prepare RadicalMart shipping data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $method    Method data.
	 * @param   array   $data      Order form data.
	 * @param   array   $products  Order products data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderShipping(string $context, object $method, array $data,
	                                              array  $products, array $currency): void
	{
		$shipping        = (!empty($data['shipping'])) ? $data['shipping'] : [];
		$message         = [];
		$error           = [];
		$calculate_price = false;

		if ($context === 'com_radicalmart.checkout')
		{
			$calculate_price = true;
		}
		elseif ($context === 'com_radicalmart.order')
		{
			if (isset($shipping['recalculate_price']))
			{
				$calculate_price = ((int) $shipping['recalculate_price'] === 1);
			}
		}

		// Calculate price
		$price = [];
		if ($calculate_price)
		{
			$language = $this->getApplication()->getLanguage();
			$price    = $this->calculatePrice($data, $products);
			if (!empty($price['error']))
			{
				$constant = 'PLG_RADICALMART_SHIPPING_APISHIP_ERROR_' . $price['error'];
				$text     = $language->hasKey($constant) ? Text::_($constant) : $price['error'];

				$price['base']        = 0;
				$price['base_string'] = ' ';
				$price['base_seo']    = ' ';
				$price['base_number'] = 0;

				if (in_array($price['error'], ['select_point', 'select_address', 'select_tariff']))
				{
					$message[] = $text;

				}
				else
				{
					$error[] = $text;
				}
			}

			unset($price['error']);
		}
		elseif (!empty($shipping['price']) && !empty($shipping['price']['base']))
		{
			$price = $shipping['price'];
		}

		// Set price values
		if (isset($price['base']))
		{
			if (empty($price['base']))
			{
				$price['base_string'] = '';
				$price['base_seo']    = '';
				$price['base_number'] = '';
			}
			else
			{
				$price['base'] = PriceHelper::clean($price['base']);
				if (!isset($price['base_string']))
				{
					$price['base_string'] = PriceHelper::toString($price['base'], $currency['code']);
				}
				if (!isset($price['base_seo']))
				{
					$price['base_seo'] = PriceHelper::toString($price['base'], $currency['code'], 'seo');
				}
				if (!isset($price['base_number']))
				{
					$price['base_number'] = PriceHelper::toString($price['base'], $currency['code'], false);
				}
			}

			$price['final']        = $price['base'];
			$price['final_string'] = $price['base_string'];
			$price['final_seo']    = $price['base_seo'];
			$price['final_number'] = $price['base_number'];
		}

		// Set order data
		$method->order              = new \stdClass();
		$method->order->title       = $method->title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;

		// Set messages
		$method->message = implode(PHP_EOL, $message);
		$method->error   = implode(PHP_EOL, $error);

		// Set checkout data
		if ($context === 'com_radicalmart.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.apiship.radicalmart.checkout';
		}

		// Set notification
		$method->notification = $this->prepareMethodNotification($data, $method, $currency);
	}

	/**
	 * Method to prepare shipping notification information.
	 *
	 * @param   array   $data      Form data array.
	 * @param   object  $method    Shipping method object.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @return array Order shipping display data.
	 *
	 * @since __DEPLOY_VERSION__
	 *
	 */
	protected function prepareMethodNotification(array $data, object $method, array $currency): array
	{
		if (empty($data))
		{
			return [];
		}

		$result      = [];
		$displayData = $this->getShippingDisplayData($data, $method, $currency);
		foreach ($displayData as $key => $value)
		{
			if (empty($value))
			{
				continue;
			}

			$constant          = 'PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_' . str_replace('_seo', '', $key);
			$result[$constant] = $value;
		}

		return $result;
	}

	/**
	 * Prepare RadicalMart order shipping methods data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $method    Method data.
	 * @param   array   $formData  Order form data.
	 * @param   array   $products  Order products data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderShippingMethods(string $context, object $method, array $formData,
	                                                     array  $products, array $currency): void
	{
		// Set disabled
		if ((int) $method->params->get('delivery_type', 2) === 2 && empty($method->params->get('points_map_key')))
		{
			$method->disabled = true;

			return;
		}

		foreach ($products as $product)
		{
			if ((int) $product->shipping->get('enable', 1) === 0
				|| empty($product->shipping->get('weight', ''))
				|| empty($product->shipping->get('length', ''))
				|| empty($product->shipping->get('width', ''))
				|| empty($product->shipping->get('height', ''))
			)
			{
				$method->disabled = true;

				return;
			}
		}

		// Clean secret param
		$method->params->set('token', '');
		$method->params->set('dadata_token', '');
		$method->params->set('dadata_secret', '');
		$method->params->set('webhook', '');
		$method->disabled = false;
	}

	/**
	 * Prepare RadicalMart order totals.
	 *
	 * @param   string             $context   Context selector string.
	 * @param   array              $total     Order total data.
	 * @param   array              $formData  Form data array.
	 * @param   array              $products  Shipping method data.
	 * @param   object             $shipping  Shipping method data.
	 * @param   object|null|false  $payment   Payment method data.
	 * @param   array              $currency  Order currency data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderTotal(string $context, array &$total, array $formData, array $products,
	                                           object $shipping, mixed $payment, array $currency): void
	{
		if (!empty($shipping->order->price['base']) && $shipping->order->price['base'] > 0)
		{
			$total['base'] += $shipping->order->price['base'];
		}

		if (!empty($shipping->order->price['final']) && $shipping->order->price['final'] > 0)
		{
			$total['final'] += $shipping->order->price['final'];
		}
	}

	/**
	 * Method to display order logs.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   array   $log      Log data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderLogs(string $context, array &$log): void
	{
		if ($log['action'] === 'apiship_order_create')
		{
			$log['action_text'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_LOGS_ORDER_CREATE');
			$log['message']     = Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_LOGS_ORDER_CREATE_MESSAGE',
				$log['result']['orderId']);
		}
		elseif ($log['action'] === 'apiship_order_status')
		{
			$log['action_text'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_LOGS_ORDER_STATUS');
			$log['message']     = Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_LOGS_ORDER_STATUS_MESSAGE',
				$log['result']['orderInfo']['orderId'], $log['result']['status']['name']);
		}
		elseif ($log['action'] === 'apiship_order_cancel')
		{
			$log['action_text'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_LOGS_ORDER_CANCEL');
			$log['message']     = Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_LOGS_ORDER_CANCEL_MESSAGE',
				$log['result']['orderId']);
		}
	}

	/**
	 * Prepare RadicalMart order forms.
	 *
	 * @param   string        $context   Context selector string.
	 * @param   Form          $form      Order form object.
	 * @param   array         $formData  Form data array.
	 * @param   array         $products  Shipping method data.
	 * @param   object        $shipping  Shipping method data.
	 * @param   object|false  $payment   Payment method data.
	 * @param   array         $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderForm(string $context, Form $form, array $formData,
	                                          array  $products, object $shipping, object|bool $payment,
	                                          array  $currency): void
	{
		$formName = $form->getName();
		if (!in_array($formName, ['com_radicalmart.checkout', 'com_radicalmart.order', 'com_radicalmart.order_site']))
		{
			return;
		}

		$this->prepareCheckoutForm($context, $formName, $form, $shipping);
		$this->prepareSiteOrderForm($context, $formName, $form, $formData, $shipping, $currency);
		$this->prepareOrderForm($context, $formName, $form, $formData, $shipping, $currency);
	}

	/**
	 * Prepare RadicalMart checkout form.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   string  $formName  Form name
	 * @param   Form    $form      Order form object.
	 * @param   object  $shipping  Shipping method data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function prepareCheckoutForm(string $context, string $formName, Form $form, object $shipping): void
	{
		if ($formName !== 'com_radicalmart.checkout')
		{
			return;
		}

		$params        = self::getShippingMethodParams($shipping->id);
		$user_id       = $this->getApplication()->getIdentity()->id;
		$delivery_type = (int) $params->get('delivery_type', 2);

		if ($delivery_type === 1)
		{
			$form->removeField('point', 'shipping');
			$form->setFieldAttribute('address', 'shipping', $shipping->id, 'shipping');
			$form->setFieldAttribute('address', 'customer', $user_id, 'shipping');
			$form->setFieldAttribute('address', 'context', $context, 'shipping');
		}
		else
		{
			$form->removeField('address', 'shipping');

			$form->setFieldAttribute('point', 'shipping', $shipping->id, 'shipping');
			$form->setFieldAttribute('point', 'context', $context, 'shipping');
		}

		$form->setFieldAttribute('tariff', 'context', $context, 'shipping');
	}

	/**
	 * Prepare RadicalMart site order form.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   string  $formName  Form name
	 * @param   Form    $form      Order form object.
	 * @param   array   $formData  Form data array.
	 * @param   object  $method    Shipping method data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function prepareSiteOrderForm(string $context, string $formName, Form $form, array $formData,
	                                        object $method, array $currency): void
	{
		if ($formName !== 'com_radicalmart.order_site')
		{
			return;
		}

		$displayData = $this->getShippingDisplayData($formData, $method, $currency);
		foreach ($displayData as $key => $value)
		{
			if (!empty($value))
			{
				$form->setFieldAttribute($key, 'default', $value, 'shipping . display');
				$form->setFieldAttribute($key, 'value', $value, 'shipping . display');
				$form->setValue($key, 'shipping.display', $value);
			}
			else
			{
				$form->removeField($key, 'shipping.display');
			}
		}
	}

	/**
	 * Prepare RadicalMart order form.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   string  $formName  Form name
	 * @param   Form    $form      Order form object.
	 * @param   array   $formData  Form data array.
	 * @param   object  $method    Shipping method data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function prepareOrderForm(string $context, string $formName, Form $form, array $formData,
	                                    object $method, array $currency): void
	{
		if ($formName !== 'com_radicalmart.order')
		{
			return;
		}

		/** @var Form $editForm */
		$editForm = Factory::getContainer()->get(FormFactoryInterface::class)
			->createForm('com_radicalmart.order.shipping.edit');
		$editForm->loadFile(JPATH_PLUGINS . '/radicalmart_shipping/apiship/forms/radicalmart/order_edit.xml');

		$params        = self::getShippingMethodParams($method->id);
		$delivery_type = (int) $params->get('delivery_type', 2);
		if ($delivery_type === 1)
		{
			$editForm->setFieldAttribute('address', 'shipping', $method->id);

			$form->removeGroup('shipping.point');
			$editForm->removeField('point');
		}
		else
		{
			$editForm->setFieldAttribute('point', 'shipping', $method->id);
			$editForm->setFieldAttribute('point', 'context', $context);

			$form->removeGroup('shipping.address');
			$editForm->removeField('address');
		}
		$editForm->setFieldAttribute('tariff', 'context', $context);
		$editForm->setFieldAttribute('base', 'currency', $currency['code'], 'price');

		$form->setFieldAttribute('edit', 'formsource', $editForm->getXml()->asXML(), 'shipping');

		if ((int) $params->get('api_orders_enabled', 0) === 1 && !empty($formData['id']))
		{
			$form->setFieldAttribute('actions', 'order_id', $formData['id'], 'shipping.api_order');
			$form->setFieldAttribute('actions', 'context', 'com_radicalmart.order', 'shipping.api_order');
		}
		else
		{
			$form->removeGroup('shipping.api_order');
		}
	}

	/**
	 * Method to get order shipping display data.
	 *
	 * @param   array   $data      Form data array.
	 * @param   object  $method    Shipping method object.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @return array Order shipping display data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getShippingDisplayData(array $data, object $method, array $currency): array
	{
		$shipping      = (!empty($data['shipping'])) ? $data['shipping'] : [];
		$params        = self::getShippingMethodParams($method->id);
		$delivery_type = (int) $params->get('delivery_type', 2);
		$result        = [
			'provider'         => '',
			'delivery_type'    => Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE_' . $delivery_type),
			'api_order_status' => (!empty($shipping['api_order']['status_key'])) ?
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_STATUS_' . $shipping['api_order']['status_key']) : '',
			'address'          => '',
			'comment'          => (!empty($shipping['comment'])) ? $shipping['comment'] : '',
			'tariff'           => (!empty($shipping['tariff']['id'])) ? $shipping['tariff']['name'] : '',
			'cost'             => (!empty($shipping['price']['final']))
				? PriceHelper::toString($shipping['price']['final'], $currency['code']) : '',
			'cost_seo'         => (!empty($shipping['price']['final']))
				? PriceHelper::toString($shipping['price']['final'], $currency['code'], 'seo') : '',
			'tracking_number'  => (!empty($shipping['tracking_number'])) ? $shipping['tracking_number'] : '',
			'tracking_url'     => (!empty($shipping['tracking_url'])) ? '<a href="' . $shipping['tracking_url']
				. '" target="_blank">' . $shipping['tracking_url'] . '</a>' : '',
			'sending_date'     => (!empty($shipping['sending_date']))
				? HTMLHelper::date($shipping['sending_date'], Text::_('DATE_FORMAT_LC4')) : '',
			'date'             => (!empty($shipping['date']))
				? HTMLHelper::date($shipping['date'], Text::_('DATE_FORMAT_LC4')) : '',
			'note'             => (!empty($shipping['note'])) ? $shipping['note'] : '',
		];

		if ($delivery_type === 1 && !empty($shipping['address']['string']))
		{
			$result['provider'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $shipping['address']['provider']);
			$result['address']  = $shipping['address']['string'];

		}
		elseif ($delivery_type === 2 && !empty($shipping['point']['id']))
		{
			$result['provider'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $shipping['point']['providerKey']);
			$result['address']  = $shipping['point']['address'];
		}

		return $result;
	}

	/**
	 * Prepare loaded RadicalMart form data.
	 *
	 * @param   string   $context   Context selector string.
	 * @param   array   &$data      Method saved  data.
	 * @param   object   $method    Order shipping method object.
	 * @param   array    $formData  Order form data.
	 * @param   array    $products  Order products data.
	 * @param   array    $currency  Order currency data.
	 * @param   bool     $isNew     Is new order.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartLoadOrderMethodFormData(string $context, array &$data, object $method, array $formData,
	                                                     array  $products, array $currency, bool $isNew): void
	{
		if ($context !== 'com_radicalmart.order')
		{
			return;
		}

		// Cleanup actions
		$data['recalculate_price'] = 0;

		$data['display'] = $this->getShippingDisplayData($formData, $method, $currency);
		$data['edit']    = [
			'point'   => (!empty($data['point'])) ? $data['point'] : [],
			'address' => (!empty($data['address'])) ? $data['address'] : [],
			'tariff'  => (!empty($data['tariff'])) ? $data['tariff'] : [],
			'price'   => (!empty($data['price'])) ? $data['price'] : [],
		];
	}

	/**
	 * Prepare and clean RadicalMart order save data.
	 *
	 * @param   string   $context   Context selector string.
	 * @param   array   &$data      Method saved  data.
	 * @param   object   $method    Order shipping method object.
	 * @param   array    $formData  Order form data.
	 * @param   array    $products  Order products data.
	 * @param   array    $currency  Order currency data.
	 * @param   bool     $isNew     Is new order.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareOrderMethodSaveData(string $context, array &$data, object $method, array $formData,
	                                                        array  $products, array $currency, bool $isNew): void
	{
		// Cleanup data
		unset($data['edit']);
		unset($data['display']);
		unset($data['recalculate_price']);
		unset($data['data']['edit']);
		unset($data['data']['display']);
		unset($data['data']['recalculate_price']);
	}

	/**
	 * Listener for `onRadicalMartAfterOrderSave` event.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   array   $formData  Form data array
	 * @param   array   $data      Save data array
	 * @param   object  $order     Order data.
	 * @param   bool    $isNew     Is new order.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartAfterOrderSave(string $context, array $formData, array $data, object $order, bool $isNew): void
	{
		$this->saveCustomerData($context, $formData, $data);
		$this->removeCalculatorCache($formData);
		$this->removeCleanCache($formData);
	}

	/**
	 * Method to update custom shipping data after save checkout order.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   array   $formData  Form data array
	 * @param   array   $data      Save data array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function saveCustomerData(string $context, array $formData, array $data): void
	{
		if ($context !== 'com_radicalmart.checkout' || empty($formData['shipping']))
		{
			return;
		}

		$shipping = $formData['shipping'];
		if (empty($shipping['id']))
		{
			return;

		}
		$method_id     = $shipping['id'];
		$params        = self::getShippingMethodParams($shipping['id']);
		$delivery_type = (int) $params->get('delivery_type', 2);
		$user_id       = (!empty($data['created_by'])) ? (int) $data['created_by']
			: $this->getApplication()->getIdentity()->id;

		$db                 = $this->getDatabase();
		$query              = $db->createQuery()
			->select(['id', 'shipping'])
			->from($db->quoteName('#__radicalmart_customers'))
			->where($db->quoteName('id') . ' = :user_id')
			->bind(':user_id', $user_id);
		$customer           = $db->setQuery($query, 0, 1)->loadObject();
		$customer->shipping = (new Registry($customer->shipping))->toArray();

		$method_key = 'shipping_method_' . $method_id;
		if (!isset($customer->shipping[$method_key]))
		{
			$customer->shipping[$method_key] = [];
		}

		$needUpdate = false;
		if ($delivery_type === 1 && !empty($formData['shipping']['address']['uid']))
		{
			$uid       = $formData['shipping']['address']['uid'];
			$addresses = (!empty($customer->shipping[$method_key]['addresses']))
				? $customer->shipping[$method_key]['addresses'] : [];

			if ($uid === 'new' || !isset($addresses[$uid]))
			{
				$uids = array_keys($addresses);
				if ($uid === 'new' || in_array($uid, $uids))
				{
					$uid = $this->generateAddressUID();
					while (in_array($uid, $uids))
					{
						$uid = $this->generateAddressUID();
					}
				}
				$formData['shipping']['address']['uid'] = $uid;
			}
			$addresses[$uid] = $formData['shipping']['address'];

			$customer->shipping[$method_key]['addresses'] = $addresses;
			$needUpdate                                   = true;
		}
		elseif ($delivery_type === 2 && !empty($formData['shipping']['point']['id']))
		{
			$customer->shipping[$method_key]['point'] = $formData['shipping']['point'];
			$needUpdate                               = true;
		}

		if (!$needUpdate)
		{
			return;
		}

		$customer->shipping = (new Registry($customer->shipping))->toString();
		$db->updateObject('#__radicalmart_customers', $customer, 'id');
	}

	/**
	 * Method to delete Calculator request cache.
	 *
	 * @param   array  $formData  Form data array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function removeCalculatorCache(array $formData): void
	{
		if (empty($formData['shipping']['id']))
		{
			return;
		}
		$method_id = (int) $formData['shipping']['id'];
		if (!empty($formData['shipping']['tariff']['hash']))
		{

			$hash = $formData['shipping']['tariff']['hash'];
			CacheHelper::deleteCache($method_id, 'calculator', $hash);
		}
		CacheHelper::deleteOldCache($method_id, 'calculator');
	}

	/**
	 * Method to delete Clean request cache.
	 *
	 * @param   array  $formData  Form data array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function removeCleanCache(array $formData): void
	{
		if (empty($formData['shipping']['id']))
		{
			return;
		}
		$method_id = (int) $formData['shipping']['id'];
		if (!empty($formData['shipping']['address']['hash']))
		{
			$hash = $formData['shipping']['address']['hash'];
			CacheHelper::deleteCache($method_id, 'clean', $hash);
		}

		CacheHelper::deleteOldCache($method_id, 'clean');
	}

	/**
	 * Change admin list item display.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $item     Item data.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareAdministratorListItem(string $context, object $item): void
	{
		if ($context === 'com_radicalmart.orders')
		{
			$item->display_layout['shipping'] = 'plugins.radicalmart_shipping.apiship.radicalmart.orders';
		}
	}

	/**
	 * Prepare RadicalMart order shipping method data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $method   Method data.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetPersonalShippingMethods(string $context, object $method): void
	{
		// Set layout
		if ($context === 'com_radicalmart.personal')
		{
			$method->layout = 'plugins.radicalmart_shipping.apiship.radicalmart.personal';
		}
	}

	/**
	 * Prepare RadicalMart method prices data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   array   $data     Form method data array.
	 * @param   object  $method   Method data.
	 * @param   bool    $isNew    Is new customer.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareCustomerMethodSaveData(string $context, array &$data, object $method, bool $isNew): void
	{
		if (empty($data['addresses']))
		{
			return;
		}

		$uids           = array_unique(ArrayHelper::getColumn($data['addresses'], 'uid'));
		$params         = self::getShippingMethodParams($method->id);
		$fields_default = $params->get('fields_default', []);

		$addresses = [];
		foreach ($data['addresses'] as $address)
		{
			if (empty($address['uid']))
			{
				$uid = $this->generateAddressUID();
				while (in_array($uid, $uids))
				{
					$uid = $this->generateAddressUID();
				}
				$address['uid'] = $uid;
				$uids[]         = $uid;
			}

			foreach ($fields_default as $fdk => $fdv)
			{
				if (empty($address[$fdk]))
				{
					$address[$fdk] = $fdv;
				}
			}

			$address['string']  = AddressHelper::toString($address);
			$address['display'] = AddressHelper::toDisplay($address);

			$addresses[$address['uid']] = $address;
		}

		$data['addresses'] = $addresses;
	}

	/**
	 * Prepare RadicalMart customer and personal forms.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   Form    $form      Custer shipping method form object.
	 * @param   mixed   $data      The data expected for the form.
	 * @param   object  $shipping  Shipping method data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetCustomerMethodForm(string $context, Form $form, mixed $data, object $shipping): void
	{
		$params        = self::getShippingMethodParams($shipping->id);
		$delivery_type = (int) $params->get('delivery_type', 2);
		if ($delivery_type === 1)
		{
			$form->removeField('point');
			/** @var Form $addressesForm */
			$addressesField = $form->getFieldXml('addresses');
			$addressesForm  = Factory::getContainer()->get(FormFactoryInterface::class)
				->createForm('com_radicalmart.customer.shipping.addresses');
			$addressesForm->load($addressesField->form->asXML());

			foreach (array_keys(self::$defaultAddressFieldsParams) as $key)
			{

				$fieldDisplay = $shipping->params->get('field_' . $key);
				if ($fieldDisplay === 'hidden')
				{
					$addressesForm->removeField($key);
				}
			}
			foreach ($params->get('fields_default', []) as $dk => $dv)
			{
				$addressesForm->setFieldAttribute($dk, 'default', $dv);
			}

			$addressesForm->setFieldAttribute('provider', 'available',
				implode(',', $params->get('providers', [])));

			$form->setFieldAttribute('addresses', 'formsource', $addressesForm->getXml()->asXML());
		}
		else
		{
			$form->removeField('addresses');

			$form->setFieldAttribute('point', 'shipping', $shipping->id);
			$form->setFieldAttribute('point', 'context', $context);
		}
	}

	/**
	 * Method to generate shipping address uid.
	 *
	 * @return string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function generateAddressUID(): string
	{
		$result     = '';
		$characters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's',
			't', 'u', 'v', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
			'P', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
		$length     = 10;
		for ($i = 0; $i < $length; $i++)
		{
			$key    = rand(0, count($characters) - 1);
			$result .= $characters[$key];
		}

		return $result;
	}

	/**
	 * Get RadicalMart checkout customer data.
	 *
	 * @param   string  $context       Context selector string.
	 * @param   object  $shipping      Shipping method object.
	 * @param   array   $customerData  Customer data method data.
	 *
	 * @return array|false Customer shipping data for merge.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetCheckoutCustomerData(string $context, object $shipping, array $customerData): bool|array
	{
		if (empty($customerData))
		{
			return false;
		}

		$delivery_type = (int) $shipping->params->get('delivery_type', 2);
		if ($delivery_type !== 2 || empty($customerData['point']))
		{
			return false;
		}

		return ['point' => $customerData['point']];
	}

	/**
	 * Method to ajax functions.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAjax(Event $event): void
	{
		try
		{
			$task   = $this->getApplication()->getInput()->get('task');
			$method = 'ajax' . $task;
			if (empty($task) || !method_exists($this, $method))
			{
				throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_AJAX_METHOD_NOT_FOUND'), 500);
			}

			$result = $this->$method();
			$event->setArgument('result', $result);
			$event->setArgument('results', $result);
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Method to get points array.
	 *
	 * @throws  \Exception
	 *
	 * @return array Points collection data array.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function ajaxGetPoints(): array
	{
		$input    = $this->getApplication()->getInput();
		$shipping = $input->getInt('shipping', 0);
		$file     = $input->getCmd('file');
		if (empty($shipping))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'));
		}

		if (empty($file))
		{
			return CacheHelper::getPointsCacheFiles($shipping);
		}

		return CacheHelper::getPointsCacheData($shipping, $file);
	}

	/**
	 * Method to tariffs array.
	 *
	 * @throws  \Exception
	 *
	 * @return array Points collection data array.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function ajaxLoadTariffs(): array
	{
		try
		{
			$input    = $this->getApplication()->getInput();
			$formData = $input->get('jform', [], 'array');
			$context  = $input->getString('field_context', 'com_radicalmart.order');
			if (empty($formData))
			{
				throw new \Exception('empty_form_data');
			}

			if ($context === 'com_radicalmart.checkout')
			{
				/** @var CheckoutModel $model */
				$this->getApplication()->setUserState('com_radicalmart.checkout.data', $formData);
				$model = $this->getMVCFactory()->createModel('Checkout', 'Site', ['ignore_request' => true]);
			}
			else
			{
				/** @var OrderModel $model */
				$order_id = (!empty($formData['id'])) ? $formData['id'] : 0;
				$model    = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
				$model->setState('order.id', $order_id);

				$edit_data = (!empty($formData['shipping']['edit'])) ? $formData['shipping']['edit'] : [];
				foreach (['point', 'address', 'tariff'] as $fdk)
				{
					$fdv = (isset($edit_data[$fdk])) ? $edit_data[$fdk] : [];

					$formData['shipping'][$fdk] = $fdv;
				}
			}
			$order = $model->getItem();

			if (empty($order))
			{
				throw new \Exception('products_not_found', 404);
			}
			$products = $order->products;
			if (empty($products))
			{
				throw new \Exception('products_not_found', 404);
			}

			$request = $this->getOrderTariffs($formData, $products);

			if (empty($request['tariffs']))
			{
				throw new \Exception('tariffs_not_found', 404);
			}

			$value = (!empty($formData['shipping']['tariff']['id'])) ?
				(int) $formData['shipping']['tariff']['id'] : 0;
			if (count($request['tariffs']) === 1 && !empty($request['tariffs'][0]->tariffId))
			{
				$value = (int) $request['tariffs'][0]->tariffId;
			}

			return [
				'value'   => $value,
				'hash'    => $request['hash'],
				'tariffs' => $request['tariffs'],
				'html'    => LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.tariffs.list', [
					'value'      => $value,
					'tariffs'    => $request['tariffs'],
					'provider'   => $request['provider'],
					'field_name' => $input->get('field_name', 'rmsa_tariff', 'string'),
					'field_id'   => $input->get('field_id', 'rmsa_tariff', 'string'),
					'currency'   => PriceHelper::getCurrentCurrency()['code'],
				])
			];
		}
		catch (\Throwable $e)
		{
			$language = $this->getApplication()->getLanguage();
			$constant = 'PLG_RADICALMART_SHIPPING_APISHIP_ERROR_' . $e->getMessage();
			$text     = $language->hasKey($constant) ? Text::_($constant) : $e->getMessage();

			throw new \Exception($text, $e->getCode(), $e);
		}
	}

	/**
	 * Method to ajax validate address.
	 *
	 * @throws \Exception
	 *
	 * @return array Validate result.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxValidateAddress(): array
	{
		try
		{
			$input    = $this->getApplication()->getInput();
			$shipping = $input->getInt('shipping', 0);
			$address  = $input->get('validate', [], 'array');

			return $this->validateAddress($shipping, $address);
		}
		catch (\Throwable $e)
		{

			$language = $this->getApplication()->getLanguage();
			$constant = 'PLG_RADICALMART_SHIPPING_APISHIP_ERROR_' . $e->getMessage();
			$text     = $language->hasKey($constant) ? Text::_($constant) : $e->getMessage();

			throw new \Exception($text, $e->getCode(), $e);
		}
	}

	/**
	 * Method to get administrator shipping order price.
	 *
	 * @throws \Throwable
	 *
	 * @return array Order shipping price data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxGetAdministratorShippingPrice(): array
	{
		$app     = $this->getApplication();
		$input   = $app->getInput();
		$data    = $input->get('jform', [], 'array');
		$oldData = $app->getUserState('com_radicalmart.edit.order.data', []);
		$app->setUserState('com_radicalmart.edit.order.data', $data);
		try
		{
			/** @var OrderModel $model */
			$model    = $this->getMVCFactory()->createModel('Order', 'Administrator');
			$order    = $model->getItem();
			$shipping = $order->shipping;

			$app->setUserState('com_radicalmart.edit.order.data', $oldData);

			return [
				'message' => $shipping->message,
				'error'   => $shipping->error,
				'price'   => $shipping->order->price,
			];
		}
		catch (\Throwable $e)
		{
			$app->setUserState('com_radicalmart.edit.order.data', $oldData);
			throw $e;
		}
	}

	/**
	 * Method to ajax delete old points cache data.
	 *
	 * @throws \Exception
	 *
	 * @return bool True on success.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxPointsFilesRemoveData(): bool
	{
		$method_id = $this->getApplication()->getInput()->getInt('shipping', 0);
		if (empty($method_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$folder = CacheHelper::getPointsCacheFolder($method_id);
		if (is_dir($folder))
		{
			Folder::delete($folder);
		}

		return true;
	}

	/**
	 * Method to ajax delete old points cache data.
	 *
	 * @throws \Exception
	 *
	 * @return int|array|string Total data array, Rows count int, Result message string.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxPointsFilesCreateCache(): array|int|string
	{
		$input     = $this->getApplication()->getInput();
		$method_id = $input->getInt('shipping', 0);
		if (empty($method_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}
		$limit = $input->getInt('limit', 0);
		if ($limit === 0)
		{
			throw new \Exception('Incorrect limit loop', 500);
		}

		$params    = self::getShippingMethodParams($method_id);
		$token     = $params->get('token');
		$providers = $params->get('providers', []);
		$operation = [2, 3];

		$offset = $input->getInt('offset', 0);
		$action = $input->getCmd('action', 'total');
		if ($action === 'start')
		{
			$folder = CacheHelper::getPointsCacheFolder($method_id);
			if (!is_dir($folder))
			{
				Folder::create($folder);
			}

			$total   = ApiShipHelper::getPointsTotal($token, $providers, $operation);
			$offsets = [];
			for ($offset = 0; $offset < $total; $offset += $limit)
			{
				$offsets[] = $offset;
			}

			return [
				'total_points' => $total,
				'total_files'  => count($offsets),
				'offsets'      => $offsets,
			];
		}
		if ($action === 'advise')
		{
			$requestRows = ApiShipHelper::getPoints($token, $providers, $operation, $offset, $limit);
			$count       = count($requestRows);
			if ($count === 0)
			{
				return 0;
			}

			$rows = [];
			$keys = ['id', 'providerKey', 'name', 'countryCode', 'address', 'lat', 'lng'];
			foreach ($requestRows as $row)
			{
				$row = (array) $row;
				foreach (array_keys($row) as $key)
				{
					if (!in_array($key, $keys))
					{
						unset($row[$key]);
					}
				}

				$rows[] = $row;
			}

			CacheHelper::savePointsCache($method_id, $offset, $rows);

			return $count;
		}

		$files = CacheHelper::getPointsCacheFiles($method_id);
		$count = count($files);
		$last  = end($files);
		$date  = HTMLHelper::date($last['time'], Text::_('DATE_FORMAT_LC5'));

		return Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_FILES_RESET_RESULT',
			$count, $last['end'], $date);
	}

	/**
	 * Method to display api data.
	 *
	 * @throws \Exception
	 *
	 * @return string[] Api lists list or api request result.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxGetLists(): array
	{
		$this->checkAdministratorClient();

		$input      = $this->getApplication()->getInput();
		$sipping_id = $input->getInt('id', 0);
		if (empty($sipping_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$params = self::getShippingMethodParams($sipping_id);
		$token  = $params->get('token');
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'), 403);
		}

		$lists = ['statuses', 'providers', 'tariffs', 'pickupTypes', 'deliveryTypes', 'paymentMethods', 'operationTypes',
			'pointTypes'];

		$list = $input->getString('list');
		if (!empty($list) && in_array($list, $lists))
		{
			$filter    = [];
			$providers = $params->get('providers', []);

			if ($list === 'tariffs')
			{
				$filter[] = [
					'key'      => 'providerKey',
					'operator' => '=',
					'value'    => $providers
				];
			}

			dd(ApiShipHelper::getList($token, $list, $filter));
		}

		$link   = 'index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&task=getLists&format=raw&id='
			. $sipping_id . '&list=';
		$result = [
			'<ul>'
		];
		foreach ($lists as $list)
		{
			$result[] = '<li>'
				. '<a href="' . Route::link('administrator', $link . $list) . '" target="_blank">'
				. $list
				. '</a></li>';
		}
		$result[] = '</ul>';

		return $result;
	}

	/**
	 * Method to display api data.
	 *
	 * @throws \Exception
	 *
	 * @return string[] Api lists list or api request result.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxGetWebhooks(): array
	{
		$this->checkAdministratorClient();

		$input      = $this->getApplication()->getInput();
		$sipping_id = $input->getInt('id', 0);
		if (empty($sipping_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$params = self::getShippingMethodParams($sipping_id);
		$token  = $params->get('token');
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'), 403);
		}

		$create = Route::link('administrator',
			'index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&shipping='
			. $sipping_id . '&task=createWebhook&format=raw&list_return=1&uuid=', false);

		$result = [
			'<table class="table table-striped table-bordered">',
			'<thead><tr>',
			'<th>UUID</th>',
			'<th>URL</th>',
			'<th>TYPE</th>',
			'<th><a href="' . $create . '" class="btn btn-sm btn-success text-light">'
			. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_WEBHOOK_CREATE') . '</a></th>',
			'</tr></thead>',
			'<tbody>'
		];

		$delete = Route::link('administrator',
			'index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&id='
			. $sipping_id . '&task=deleteWebhook&format=raw&list_return=1&uuid=', false);
		foreach (ApiShipHelper::getWebhooks($token) as $webhook)
		{
			$result[] = '<tr>';
			$result[] = '<td>' . $webhook['uuid'] . '</td>';
			$result[] = '<td>' . $webhook['url'] . '</td>';
			$result[] = '<td>' . $webhook['type'] . '</td>';
			$result[] = '<td><a href="' . $delete . $webhook['uuid'] . '" class="btn btn-sm btn-danger">'
				. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_WEBHOOK_DELETE') . '</a></td>';
			$result[] = '</tr>';
		}

		$result[] = '</tbody></table>';

		return $result;
	}

	/**
	 * Method to delete webhook.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxDeleteWebhook(): void
	{
		$this->checkAdministratorClient();

		/** @var AdministratorApplication $app */
		$app        = $this->getApplication();
		$input      = $app->getInput();
		$sipping_id = $input->getInt('id', 0);
		if (empty($sipping_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$params = self::getShippingMethodParams($sipping_id);
		$token  = $params->get('token');
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'), 403);
		}

		$uuid = $input->getString('uuid', '');
		if (empty($uuid))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_WEBHOOK_NOT_FOUND'), 404);
		}

		$log = false;
		if ((int) $params->get('logs', 0) === 1)
		{
			$log = $sipping_id . 'apiship.webhook.create';
		}

		$resuest = ApiShipHelper::deleteWebhook($token, $uuid, $log);
		if ($input->getInt('list_return', 0) === 1)
		{
			$app->enqueueMessage(Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_WEBHOOK_DELETED', $uuid), 'success');
			$app->redirect(Route::link('administrator',
				'index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&id='
				. $sipping_id . '&task=getWebhooks&format=html',
				false), 301);

			return;
		}

		dd($resuest);
	}

	/**
	 * Method to display api data.
	 *
	 * @throws \Exception
	 *
	 * @return string[] Api lists list or api request result.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxCreateWebhook(): array
	{
		$this->checkAdministratorClient();

		/** @var AdministratorApplication $app */
		$app       = $this->getApplication();
		$input     = $this->getApplication()->getInput();
		$method_id = $input->getInt('shipping', 0);
		if (empty($method_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$params = self::getShippingMethodParams($method_id);
		$token  = $params->get('token');
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'), 403);
		}

		$url      = self::getWebhookUrl();
		$uuid     = false;
		$webhooks = ApiShipHelper::getWebhooks($token);
		if (!empty($webhooks))
		{
			foreach ($webhooks as $webhook)
			{
				if ($webhook['url'] === $url)
				{
					$uuid = $webhook['uuid'];
					break;
				}
			}
		}

		if (!$uuid)
		{
			$data = [
				'url'  => $url,
				'type' => 'ORDER_STATUS',
			];

			$log = false;
			if ((int) $params->get('logs', 0) === 1)
			{
				$log = $method_id . 'apiship.webhook.create';
			}

			$request = ApiShipHelper::createWebhook($token, $data, $log);
			$uuid    = $request->get('uuid');
		}

		$db             = $this->getDatabase();
		$query          = $db->createQuery()
			->select(['id', 'params'])
			->from($db->quoteName('#__radicalmart_shipping_methods'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $method_id, ParameterType::INTEGER);
		$update         = $db->setQuery($query, 0, 1)->loadObject();
		$update->params = new Registry($update->params);
		$update->params->set('webhook', $url);
		$update->params = $update->params->toString();

		$db->updateObject('#__radicalmart_shipping_methods', $update, 'id');

		if ($input->getInt('list_return', 0) === 1)
		{
			$app->enqueueMessage(Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_WEBHOOK_CREATED', $uuid), 'success');
			$app->redirect(Route::link('administrator',
				'index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&id='
				. $method_id . '&task=getWebhooks&format=html',
				false), 301);
		}

		return [
			'url'  => $url,
			'uuid' => $uuid,
			'safe' => Uri::getInstance($url)->toString(['scheme', 'host', 'port', 'path'])
		];
	}

	/**
	 * Method to run Api order actions.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function ajaxExecuteOrderAction(): array
	{
		$this->checkAdministratorClient();

		$input    = $this->getApplication()->getInput();
		$order_id = $input->getInt('order_id', 0);
		if (empty($order_id))
		{
			throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
		}

		/** @var OrderModel $model */
		$model = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
		$model->setState('order.id', $order_id);

		$order = $model->getItem($order_id);
		if (empty($order->id))
		{
			throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
		}

		if (empty($order->shipping) || empty($order->shipping->id) || $order->shipping->plugin !== 'apiship')
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$sipping_id = $order->shipping->id;
		$params     = ParamsHelper::getShippingMethodsParams($sipping_id);
		if ((int) $params->get('api_orders_enabled', 0) === 0)
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDERS_DISABLED'), 500);
		}

		$action = $input->getString('action');
		if (empty($action) || !in_array($action, ['create', 'update_status', 'cancel']))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDERS_ACTION_NOT_ALLOWED'), 403);
		}

		$updateData = [
			'api_order' => []
		];
		$messages   = [];
		if ($action === 'create')
		{
			$data                               = $this->createApiOrder($order);
			$updateData['api_order']['created'] = (new Date($data->get('created', '')))->toSql();
			$messages[]                         = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_ACTIONS_CREATE_SUCCESS');
			$action                             = 'update_status';
		}

		if ($action === 'cancel')
		{
			$this->cancelApiOrder($order);
			$messages[] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_ACTIONS_CANCEL_SUCCESS');
			$action     = 'update_status';
		}

		if ($action === 'update_status')
		{
			$data                                  = $this->getApiOrderStatus($order, false);
			$updateData['api_order']['id']         = $data->get('orderInfo.orderId');
			$updateData['api_order']['status_key'] = $data->get('status.key');
			$updateData['api_order']['status']     = $data->get('status.name');
			if ($tracking_url = $data->get('orderInfo.trackingUrl'))
			{
				$updateData['tracking_url'] = $tracking_url;
			}

			$status_key = $data->get('status.key');

			$this->updateOrderShippingData($order_id, $updateData);
			$this->changeOrderStatus($order, $status_key);

			if ($this->isRetailCRMEnabled())
			{
				$model->reset($order_id);
				$order = $model->getItem($order_id);

				try
				{
					$this->updateRetailCRMOrderShippingData($order);
					$this->changeRetailCRMOrderStatus($order, $status_key);
				}
				catch (\Throwable)
				{

				}
			}

			$messages[] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_ACTIONS_UPDATE_STATUS_SUCCESS');
		}

		if (count($messages) === 0)
		{
			$messages[] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_ACTIONS_NO_RESULT');
		}

		return [
			'messages' => $messages,
			'set_data' => $updateData
		];
	}

	/**
	 * Method to create order if not exist.
	 *
	 * @param   object  $order  Order object
	 *
	 * @throws \Exception
	 *
	 * @return Registry New order data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function createApiOrder(object $order): Registry
	{
		if (empty($order->shipping) || empty($order->shipping->id) || $order->shipping->plugin !== 'apiship')
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$shipping      = $order->formData['shipping'];
		$method_id     = $order->shipping->id;
		$params        = self::getShippingMethodParams($method_id);
		$token         = $params->get('token');
		$delivery_type = (int) $params->get('delivery_type', 2);
		$senders       = $params->get('sender', []);

		if ((int) $params->get('api_orders_enabled', 0) === 0)
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDERS_DISABLED'), 500);
		}

		if (empty($shipping['tariff']) || empty($shipping['tariff']['id']))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TARIFF_NOT_FOUND'), 400);
		}

		if ($delivery_type === 2)
		{
			if (empty($shipping['point']['id']))
			{
				throw  new \Exception('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SELECT_POINT', 500);
			}

			$provider = $shipping['point']['providerKey'];
		}
		else
		{
			if (empty($shipping['address']['string']))
			{
				throw  new \Exception('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SELECT_ADDRESS', 500);
			}

			$provider = $shipping['address']['provider'];
		}

		$senderParams = (isset($senders[$provider])) ? $senders[$provider] : false;
		if (empty($senderParams))
		{
			throw  new \Exception('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SENDER_NOT_FOUND', 404);
		}
		$pickup_type = (isset($senderParams['pickup_type'])) ? (int) $senderParams['pickup_type'] : 1;

		$contacts = ($order->contacts instanceof Registry) ? $order->contacts : new Registry($order->contacts);

		$requestData = [
			'order'     => [
				'clientNumber' => $order->number,
				'weight'       => 0,
				'providerKey'  => $provider,
				'pickupType'   => $pickup_type,
				'deliveryType' => $delivery_type,
				'tariffId'     => $shipping['tariff']['id'],
				'pointInId'    => null,
				'pointOutId'   => null,
			],
			'cost'      => [
				'assessedCost' => 0,
				'codCost'      => 0,
			],
			'sender'    => [
				'companyName' => $params->get('api_orders_sender_company_name'),
				'companyInn'  => $params->get('api_orders_sender_company_inn'),
				'contactName' => $params->get('api_orders_sender_contact_name'),
				'phone'       => $params->get('api_orders_sender_phone'),
				'email'       => $params->get('api_orders_sender_email'),
			],
			'recipient' => [
				'contactName' => UserHelper::nameToString($contacts->toArray()),
				'phone'       => $contacts->get('phone'),
				'email'       => $contacts->get('email'),
			],
			'places'    => [0 => [
				'weight' => 0,
				'items'  => []
			]],
		];

		if ($pickup_type === 2)
		{
			$requestData['order']['pointInId'] = (int) $senderParams['point'];
		}
		else
		{
			$requestData['sender']['addressString'] = $senderParams['address'];
			$requestData['sender']['countryCode']   = $senderParams['country'];
		}

		if ($delivery_type === 2)
		{
			$requestData['order']['pointOutId'] = $shipping['point']['id'];
		}
		else
		{
			foreach (AddressHelper::$addressKeys as $addressKey)
			{
				if (!empty($shipping['address'][$addressKey]))
				{
					$requestData['recipient'][$addressKey] = $shipping['address'][$addressKey];
				}
			}
			$requestData['recipient']['addressString'] = AddressHelper::toString($shipping['address']);
		}

		$cod_payment_method = (int) $params->get('cod_payment_method', 0);
		$cod_payment        = (!empty($order->payment) && !empty($order->payment->id)
			&& (int) $order->payment->id === $cod_payment_method);
		if ($cod_payment)
		{
			$requestData['cost']['codCost'] = $order->total['final'];
			$requestData['cost']['deliveryCost'] = $order->shipping->order->price['final'];
		}

		foreach ($order->products as $product)
		{
			$item = [
				'description'  => $product->title,
				'quantity'     => $product->order['quantity'],
				'assessedCost' => $product->order['sum_final']
			];
			if (!empty($product->code))
			{
				$item['articul'] = $product->code;
			}

			if ($cod_payment)
			{
				$item['cost'] = $product->order['sum_final'];
			}

			$this->setPlaceItemDimensions($item, $product);

			$requestData['places'][0]['items'][] = $item;

			$requestData['places'][0]['weight']  += $item['weight'] * $product->order['quantity'];
			$requestData['order']['weight']      += $item['weight'] * $product->order['quantity'];
			$requestData['cost']['assessedCost'] += $item['assessedCost'];
		}

		$log = false;
		if ((int) $params->get('logs', 0) === 1)
		{
			$log = $method_id . '.apiship.order.create';
		}

		$result = ApiShipHelper::createOrder($token, $requestData, $log);

		$this->addOrderLog($order->id, 'apiship_order_create', [
			'result' => $result->toArray()
		]);

		return $result;
	}

	/**
	 * Method to get order status data and update if you need.
	 *
	 * @param   object  $order   Order object.
	 * @param   bool    $update  Update order data.
	 *
	 * @throws \Exception
	 * @return Registry Order status data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getApiOrderStatus(object $order, bool $update = true): Registry
	{
		if (empty($order->shipping) || empty($order->shipping->id) || $order->shipping->plugin !== 'apiship')
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$method_id = $order->shipping->id;
		$params    = self::getShippingMethodParams($method_id);
		$token     = $params->get('token');

		if ((int) $params->get('api_orders_enabled', 0) === 0)
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDERS_DISABLED'), 500);
		}

		$log = false;
		if ((int) $params->get('logs', 0) === 1)
		{
			$log = $method_id . '.apiship.order.status';
		}

		$result = ApiShipHelper::getOrderStatus($token, ['client_number' => $order->number], $log);

		if ($update)
		{
			$updateData = [
				'api_order' => [
					'id'         => $result->get('orderInfo.orderId'),
					'status_key' => $result->get('status.key'),
					'status'     => $result->get('status.name'),
				]
			];
			if ($tracking_url = $result->get('orderInfo.trackingUrl'))
			{
				$updateData['tracking_url'] = $tracking_url;
			}

			$this->updateOrderShippingData($order->id, $updateData);
			$this->addOrderLog($order->id, 'apiship_order_status', [
				'result' => $result->toArray()
			]);
		}

		return $result;
	}

	/**
	 * Method to cancel api order.
	 *
	 * @param   object  $order  Order object
	 *
	 * @throws \Exception
	 *
	 * @return Registry Action result data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function cancelApiOrder(object $order): Registry
	{
		if (empty($order->shipping) || empty($order->shipping->id) || $order->shipping->plugin !== 'apiship')
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$shipping  = $order->formData['shipping'];
		$method_id = $order->shipping->id;
		$params    = self::getShippingMethodParams($method_id);
		$token     = $params->get('token');

		if ((int) $params->get('api_orders_enabled', 0) === 0)
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDERS_DISABLED'), 500);
		}

		$log        = false;
		$log_status = false;
		if ((int) $params->get('logs', 0) === 1)
		{
			$log        = $method_id . '.apiship.order.cancel';
			$log_status = $method_id . '.apiship.order.status';
		}

		$api_order_id = (!empty($shipping['api_order']['id'])) ? $shipping['api_order']['id'] : false;
		if (!$api_order_id)
		{
			$data = ApiShipHelper::getOrderStatus($token, ['client_number' => $order->number], $log_status);

			$api_order_id = $data->get('orderInfo.orderId', '00000');
		}

		$result = ApiShipHelper::cancelOrder($token, $api_order_id, $log);

		$this->addOrderLog($order->id, 'apiship_order_cancel', [
			'result' => $result->toArray()
		]);

		return $result;
	}

	/**
	 * Method to update database order data.
	 *
	 * @param   int    $order_id    Order id.
	 * @param   array  $updateData  Shipping update data.
	 *
	 * @throws \Exception
	 *
	 * @return bool True in data updated, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function updateOrderShippingData(int $order_id, array $updateData): bool
	{
		if (empty($updateData))
		{
			return false;
		}

		if (empty($order_id))
		{
			throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
		}

		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select(['id', 'shipping'])
			->from($db->quoteName('#__radicalmart_orders'))
			->where($db->quoteName('id') . ' = :order_id')
			->bind(':order_id', $order_id, ParameterType::INTEGER);
		$order = $db->setQuery($query, 0, 1)->loadObject();
		if (empty($order))
		{
			throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
		}

		$shipping = (new Registry($order->shipping))->toArray();
		if (empty($shipping['data']))
		{
			$shipping['data'] = [];
		}

		$update = false;

		foreach ($updateData as $field => $value)
		{
			if (is_array($value))
			{
				if (!isset($shipping['data'][$field]))
				{
					$shipping['data'][$field] = [];
				}
				foreach ($value as $sub_field => $sub_value)
				{
					$update                               = true;
					$shipping['data'][$field][$sub_field] = $sub_value;
				}
			}
			else
			{
				$update                   = true;
				$shipping['data'][$field] = $value;
			}
		}
		if (!$update)
		{
			return false;
		}

		$order->shipping = (new Registry($shipping))->toString();

		return $db->updateObject('#__radicalmart_orders', $order, 'id');
	}

	/**
	 * Method to update database order data.
	 *
	 * @param   object  $order       The Order object.
	 * @param   string  $status_key  ApiShip status key.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function changeOrderStatus(object $order, string $status_key): void
	{
		if (empty($order->shipping) || empty($order->shipping->id) || $order->shipping->plugin !== 'apiship')
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$method_id = $order->shipping->id;
		$params    = self::getShippingMethodParams($method_id);

		if ((int) $params->get('api_orders_enabled', 0) === 0)
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDERS_DISABLED'), 500);
		}

		if (empty($params->get('api_orders_rm_mapping')))
		{
			return;
		}

		$newStatus = false;
		$mapping   = ArrayHelper::fromObject($params->get('api_orders_rm_mapping'));
		foreach ($mapping as $value)
		{
			if ($value['api_orders_mapping_api_status'] === $status_key)
			{
				$newStatus = $value['api_orders_mapping_rm_status'];
				break;
			}
		}
		if (!$newStatus || (int) $newStatus === (int) $order->status->id)
		{
			return;
		}

		/** @var OrderModel $model */
		$model = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
		$model->setState('order.id', $order->id);

		$user_id = ($this->getApplication()->isClient('cli')) ? -1 : null;
		$model->updateStatus($order->id, $newStatus, false, $user_id, 'ApiShip');
	}

	/**
	 * Method to add RadicalMart order logs.
	 *
	 * @param   int|null     $pk      RadicalMart order id.
	 * @param   string|null  $action  Action name.
	 * @param   array        $data    Action data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function addOrderLog(?int $pk = null, ?string $action = null, array $data = []): void
	{
		/** @var OrderModel $model */
		$model = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
		$model->setState('order.id', $pk);

		if (!isset($data['user_id']))
		{
			$data['user_id'] = ($this->getApplication()->isClient('cli')) ? -1 : null;
		}
		$data['plugin'] = 'apiship';
		$data['group']  = 'radicalmart_shipping';

		$model->addLog($pk, $action, $data);
	}

	/**
	 * Method to send form token.
	 *
	 * @throws  \Exception
	 *
	 * @return bool True on success, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function ajaxGetCSRF(): bool
	{
		$app = $this->getApplication();
		$app->getLanguage()->load('com_radicalmart');
		$check = (!empty($app->getInput()->post->getInt('check_post')));

		$message  = ($check) ? '' : Text::_('COM_RADICALMART_ERROR_ACCESS_DENIED');
		$code     = ($check) ? 200 : 401;
		$response = ($check) ? Session::getFormToken() : 1;

		header('Content-Type: application/json');
		echo new JsonResponse($response, $message, ($code !== 200));
		Factory::getApplication()->close($code);

		return ($code === 200);
	}

	/**
	 * Auto shipping actions on order statuses.
	 *
	 * @param   string|null  $context    Context selector string.
	 * @param   object|null  $order      Order object.
	 * @param   int          $oldStatus  Old status id.
	 * @param   int          $newStatus  New status id.
	 * @param   bool         $isNew      Is new order.
	 *
	 * @throws \Exception
	 *
	 * @return true|void True if you need update order object, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartAfterChangeOrderStatus(?string $context = null, ?object $order = null,
	                                                    int     $oldStatus = 0, int $newStatus = 0, bool $isNew = false)
	{
		if (empty($order->shipping) || $order->shipping->plugin !== 'apiship')
		{
			return;
		}

		/** @var Registry $params */
		$params = $order->shipping->params;
		if ((int) $params->get('api_orders_enabled', 0) !== 1)
		{
			return;
		}

		$create = ArrayHelper::toInteger($params->get('api_orders_statuses_create', []));
		$cancel = ArrayHelper::toInteger($params->get('api_orders_statuses_cancel', []));
		if (!in_array($newStatus, $create) && !in_array($newStatus, $cancel))
		{
			return;
		}

		try
		{
			if (in_array($newStatus, $create))
			{
				$this->createApiOrder($order);
			}
			if (in_array($newStatus, $cancel))
			{
				$this->cancelApiOrder($order);
			}
		}
		catch (\Throwable)
		{

		}

		try
		{
			$this->getApiOrderStatus($order);

			return true;
		}
		catch (\Throwable)
		{

		}
	}

	/**
	 * Register cli commands.
	 *
	 * @param   array     $commands  Updated commands array.
	 * @param   Registry  $params    RadicalMart params.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartRegisterCLICommands(array &$commands, Registry $params): void
	{
		$commands[] = ($this->isRadicalMart3()) ? UpdateStatusesCommand::class : UpdateStatusesLegacyCommand::class;
	}

	/**
	 * Method to add administrator commands config.
	 *
	 * @param   string|null  $context   Context selector string.
	 * @param   array        $commands  Administrator commands array.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetAdministratorCommands(?string $context = null, array &$commands = []): void
	{
		if ($this->isRadicalMart3())
		{
			$this->loadRadicalMartCommands($context, $commands);
		}
		else
		{
			$this->loadRadicalMartLegacyCommands($context, $commands);
		}
	}

	/**
	 * Method to add administrator commands config to RadicalMart 3.
	 *
	 * @param   string|null  $context   Context selector string.
	 * @param   array        $commands  Administrator commands array.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function loadRadicalMartCommands(?string $context = null, array &$commands = []): void
	{
		if (in_array($context, ['com_radicalmart.commands', 'com_radicalmart.orders'])
			&& PermissionsHelper::canDo('core.edit', 'com_radicalmart', 'orders'))
		{
			$commands['radicalmart:shipping:apiship:update_statuses'] = [
				'command' => 'radicalmart:shipping:apiship:update_statuses',
				'text'    => 'PLG_RADICALMART_SHIPPING_APISHIP_COMMANDS_UPDATE_STATUSES',
				'method'  => function (string $task, Input $input, array $data = []): array|bool {
					return $this->commandUpdateStatuses($task, $input, $data);
				}
			];
		}
	}

	/**
	 * `radicalmart:shipping:apiship:update_statuses` command.
	 *
	 * @param   string  $task   Task name.
	 * @param   Input   $input  Input data.
	 * @param   array   $data   Last response data.
	 *
	 * @throws \Exception|\Throwable
	 *
	 * @return array|bool Response data on Success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function commandUpdateStatuses(string $task, Input $input, array $data = []): array|bool
	{
		if ($task === 'load')
		{
			$data['groups'] = [
				'orders' => [
					'title' => 'ApiShip: Update orders statuses',
					'tasks' => [
						'total'  => [
							'title'  => 'Get total orders',
							'render' => LayoutHelper::render(
								'components.radicalmart.administrator.commands.progress',
								['current' => 0, 'total' => 1]
							),
						],
						'update' => [
							'title'  => 'Update statuses',
							'render' => '',
						],
					]
				]
			];

			return $data;
		}

		/** @var CommandsModel $commandsModel */
		$commandsModel = $this->getMVCFactory()->createModel('Commands', 'Administrator', ['ignore_request' => true]);
		$orders        = $input->get('cid', [], 'array');
		if ($task === 'total')
		{
			$total = $commandsModel->getTotal('#__radicalmart_orders', $orders);

			$data['groups']['orders']['tasks']['total']['render'] = LayoutHelper::render(
				'components.radicalmart.administrator.commands.progress',
				['current' => 1, 'total' => 1]
			);

			$data['groups']['orders']['tasks']['update']['render'] = LayoutHelper::render(
				'components.radicalmart.administrator.commands.progress',
				['current' => 0, 'total' => $total]
			);


			$data['orders_total']    = $total;
			$data['orders_last']     = 0;
			$data['orders_progress'] = 0;

			$data['action'] = ($total > 0) ? 'next' : 'break';

			return $data;
		}

		if ($task === 'update')
		{
			$data['orders_progress']++;

			$data['groups']['orders']['tasks']['update']['render'] = LayoutHelper::render(
				'components.radicalmart.administrator.commands.progress',
				['current' => $data['orders_progress'], 'total' => $data['orders_total']]
			);

			if ($data['orders_total'] === 0)
			{
				$data['action'] = 'next';

				return $data;
			}

			$pk = $commandsModel->getNextPrimaryKey('#__radicalmart_orders', $data['orders_last'], $orders);
			if ($pk === 0 || $data['orders_last'] === $pk)
			{
				$data['action'] = 'next';

				return $data;
			}
			$data['orders_last'] = $pk;

			/** @var OrderModel $model */
			$model = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
			$model->setState('order.id', $pk);
			$order = $model->getItem($pk);

			try
			{
				$shippingData = $this->getApiOrderStatus($order);
				$status_key   = $shippingData->get('status.key');
				$this->changeOrderStatus($order, $status_key);

				if ($this->isRetailCRMEnabled())
				{
					$model->reset($pk);
					$order = $model->getItem($pk);

					try
					{
						$this->updateRetailCRMOrderShippingData($order);
						$this->changeRetailCRMOrderStatus($order, $status_key);
					}
					catch (\Throwable)
					{

					}
				}
			}
			catch (\Throwable $e)
			{
				if ($e->getCode() === 500)
				{
					throw $e;
				}
			}

			$data['action'] = ($data['orders_progress'] === $data['orders_total']) ? 'next' : 'repeat';

			return $data;
		}

		if ($task === 'finish')
		{
			$context = $input->get('context', 'null');

			$data['task'] = ($context === 'com_radicalmart.orders') ? 'window.reload' : 'close';

			return $data;
		}

		return false;
	}

	/**
	 * Method to add administrator commands config to RadicalMart 2.
	 *
	 * @param   string|null  $context   Context selector string.
	 * @param   array        $commands  Administrator commands array.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function loadRadicalMartLegacyCommands(?string $context = null, array &$commands = []): void
	{
		$app = $this->getApplication();
		if (in_array($context, ['com_radicalmart.commands', 'com_radicalmart.orders']))
		{
			$commands['radicalmart:shipping:apiship:update_statuses'] = [
				'command' => 'radicalmart:shipping:apiship:update_statuses',
				'text'    => 'PLG_RADICALMART_SHIPPING_APISHIP_COMMANDS_UPDATE_STATUSES',
				'method'  => function (string $task) use ($app) {
					$table = '#__radicalmart_orders';
					if ($task === 'total')
					{
						return $this->getLegacyCommandsTotal($table);
					}

					$pk = $app->getInput()->getInt('pk', 0);
					if (empty($pk))
					{
						throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
					}

					/** @var OrderModel $model */
					$model = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
					$model->setState('order.id', $pk);
					$order = $model->getItem($pk);

					try
					{
						$data = $this->getApiOrderStatus($order);

						$status_key = $data->get('status.key');
						$this->changeOrderStatus($order, $data->get('status.key'));

						if ($this->isRetailCRMEnabled())
						{
							$model->reset($pk);
							$order = $model->getItem($pk);

							try
							{
								$this->updateRetailCRMOrderShippingData($order);
								$this->changeRetailCRMOrderStatus($order, $status_key);
							}
							catch (\Throwable)
							{

							}
						}
					}
					catch (\Throwable $e)
					{
						if ($e->getCode() === 500)
						{
							throw $e;
						}
					}

					return $this->getLegacyCommandsNextPrimaryKey($table);
				},
			];
		}
	}

	/**
	 * Method to get total items, and first item primary key.
	 *
	 * @param   string  $table   Table name.
	 * @param   string  $column  Primary key column.
	 *
	 * @return array Result data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getLegacyCommandsTotal(string $table = '', string $column = 'id'): array
	{
		$app   = $this->getApplication();
		$total = 0;
		$next  = 0;

		$all     = ($app->getInput()->getInt('all', 0) === 1);
		$item_pk = $app->getInput()->getInt('item_pk', 0);
		if (!$all && $item_pk === 0)
		{
			if (!empty($app->getInput()->get('cid', [], 'array')))
			{
				$pks   = ArrayHelper::toInteger($app->getInput()->get('cid', [], 'array'));
				$total = count($pks);
				$next  = (int) array_shift($pks);
			}
			elseif ($app->getInput()->get('jform', [], 'array'))
			{
				$data = $app->getInput()->get('jform', [], 'array');
				if (!empty($data['id']))
				{
					$total = 1;
					$next  = (int) $data['id'];
				}
			}
		}
		elseif (!$all && $item_pk > 0)
		{
			$total = 1;
			$next  = $item_pk;
		}

		if (empty($total))
		{
			$total = CommandsHelper::getTotalItems($table, $column);
		}

		if (empty($next))
		{
			$next = CommandsHelper::getNextPrimaryKey($table, 0, $column);
		}

		return [
			'total' => $total,
			'next'  => $next,
		];
	}

	/**
	 * Method to get current and next item primary keys.
	 *
	 * @param   string  $table   Table name.
	 * @param   string  $column  Primary key column.
	 *
	 * @return array Result data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getLegacyCommandsNextPrimaryKey(string $table = '', string $column = 'id'): array
	{
		$app     = $this->getApplication();
		$current = $app->getInput()->getInt('pk', 0);
		$result  = [
			'current' => $current,
			'next'    => 0,
		];

		$all     = ($app->getInput()->getInt('all', 0) === 1);
		$item_pk = $app->getInput()->getInt('item_pk', 0);
		if (!$all && $item_pk === 0)
		{
			if ($app->getInput()->get('jform', [], 'array'))
			{
				$data = $app->getInput()->get('jform', [], 'array');
				if (!empty($data['id']) && $current === (int) $data['id'])
				{
					return $result;
				}
			}

			if (!empty($app->getInput()->get('cid', [], 'array')))
			{
				$pks     = ArrayHelper::toInteger($app->getInput()->get('cid', [], 'array'));
				$next    = 0;
				$getNext = false;
				foreach ($pks as $pk)
				{
					if ($getNext)
					{
						$next = (int) $pk;
						break;
					}

					if ((int) $pk === $current)
					{
						$getNext = true;
					}
				}

				$result['next'] = $next;

				return $result;

			}
		}
		elseif (!$all && $item_pk > 0)
		{
			$result['next'] = ($current === $item_pk) ? 0 : $item_pk;

			return $result;
		}

		$result['next'] = CommandsHelper::getNextPrimaryKey($table, $current, $column);

		return $result;
	}

	/**
	 * Method to validate address data.
	 *
	 * @param   int    $method_id  Shipping method id.
	 * @param   array  $address    Address value.
	 *
	 * @throws \Exception
	 *
	 * @return array Check result data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function validateAddress(int $method_id = 0, array $address = []): array
	{
		if (empty($method_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$params              = self::getShippingMethodParams($method_id);
		$valid               = true;
		$empty_fields_keys   = [];
		$empty_fields_labels = [];

		$language = $this->getApplication()->getLanguage();
		foreach (array_keys(self::$defaultAddressFieldsParams) as $field_name)
		{
			$path      = 'field_' . $field_name;
			$fieldType = $params->get($path, 'hidden');
			if ($fieldType === 'required' && empty($address[$field_name]))
			{
				$valid                 = false;
				$constant              = 'PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $field_name;
				$empty_fields_keys[]   = $field_name;
				$empty_fields_labels[] = ($language->hasKey($constant)) ? Text::_($constant) : $field_name;
			}
		}

		if (!$valid)
		{
			$address['string'] = '';

			return [
				'valid'               => false,
				'message'             => Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_EMPTY_ADDRESS_FIELDS',
					implode(', ', $empty_fields_labels)),
				'empty_fields_keys'   => $empty_fields_keys,
				'empty_fields_labels' => $empty_fields_labels,
				'values'              => $address,
			];
		}

		$dadata_token  = $params->get('dadata_token');
		$dadata_secret = $params->get('dadata_secret');
		if (!empty($dadata_token) && !empty($dadata_secret))
		{
			$hash    = AddressHelper::getAddressHash($address);
			$oldHash = (!empty($address['hash'])) ? $address['hash'] : '';
			$timeout = ((int) $params->get('cache', 1) === 1) ? '1 day' : '0';
			if (!empty($oldHash) && $oldHash !== $hash)
			{
				CacheHelper::deleteCache($method_id, 'clean', $oldHash);
			}
			$cacheAddress = CacheHelper::getCache($method_id, 'clean', $hash, $timeout);
			if ($cacheAddress)
			{
				foreach ($cacheAddress as $address_field => $address_value)
				{
					$address[$address_field] = $address_value;
				}
			}
			else
			{
				try
				{
					$log = false;
					if ((int) $params->get('logs', 0) === 1)
					{
						$log = $method_id . '.dadata.clean';
					}
					$clenAddress = DaDataHelper::cleanAddress($dadata_token, $dadata_secret, $address, $log);
					$addressMap  = [
						'country'   => 'country',
						'region'    => 'region',
						'city'      => 'city',
						'zip'       => 'postal_code',
						'street'    => 'street',
						'house'     => 'house',
						'building'  => 'block',
						'entrance'  => 'entrance',
						'floor'     => 'floor',
						'apartment' => 'flat',
					];
					foreach ($addressMap as $address_field => $dadata_field)
					{
						if (!empty($clenAddress[$dadata_field]))
						{
							$address[$address_field] = $clenAddress[$dadata_field];
						}
					}
					$cacheData = [];
					foreach (AddressHelper::$addressKeys as $addressKey)
					{
						if (!empty($address[$addressKey]))
						{
							$cacheData[$addressKey] = $address[$addressKey];
						}
					}
					$hash = AddressHelper::getAddressHash($address);
					CacheHelper::saveCache($method_id, 'clean', $hash, $cacheData);
					$address['hash'] = $hash;
				}
				catch (\Throwable)
				{

				}
			}
		}

		$address['string']  = AddressHelper::toString($address);
		$address['display'] = AddressHelper::toDisplay($address);

		return [
			'valid'  => true,
			'values' => $address,
		];
	}

	/**
	 * Method to get calculated tariffs data array from order data.
	 *
	 * @param   array  $data      Order shipping data.
	 * @param   array  $products  Order products data.
	 * @param   bool   $force     Force api request.
	 *
	 * @throws \Exception
	 *
	 * @return array Order price data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getOrderTariffs(array $data, array $products, bool $force = false): array
	{
		$shipping = (!empty($data['shipping'])) ? $data['shipping'] : [];
		if (empty($shipping['id']))
		{
			throw new \Exception('shipping_method_not_found');
		}

		$method_id     = $shipping['id'];
		$params        = self::getShippingMethodParams($method_id);
		$token         = $params->get('token');
		$delivery_type = (int) $params->get('delivery_type', 2);
		$senders       = $params->get('sender', []);

		$requestData = [
			'places'  => [],
			'codCost' => 0,
		];
		foreach ($products as $product)
		{
			$item = [];
			$this->setPlaceItemDimensions($item, $product);

			for ($i = 1; $i <= $product->order['quantity']; $i++)
			{
				$requestData['places'][] = $item;
			}

			$requestData['codCost'] += $product->order['sum_final'];
		}

		if (count($requestData['places']) === 0)
		{
			throw new \Exception('places_not_found');
		}

		if ($delivery_type === 2)
		{
			if (empty($shipping['point']['id']))
			{
				throw  new \Exception('select_point');
			}

			$requestData['pointOutId'] = $shipping['point']['id'];
			$provider                  = $shipping['point']['providerKey'];
		}
		else
		{
			if (empty($shipping['address']['string']))
			{
				throw  new \Exception('select_address');
			}

			$validation = $this->validateAddress($method_id, $shipping['address']);
			$address    = $validation['values'];
			if (empty($address['string']))
			{
				throw  new \Exception('select_address');
			}
			$requestData['to'] = [
				'addressString' => $address['string'],
			];

			foreach (['region' => 'region', 'city' => 'city', 'zip' => 'inode'] as $from_address => $to_request)
			{
				if (!empty($address[$from_address]))
				{
					$requestData['to'][$from_address] = $address[$from_address];
				}
			}

			$provider = $shipping['address']['provider'];
		}
		$requestData['deliveryTypes'] = [$delivery_type];

		$senderParams = (isset($senders[$provider])) ? $senders[$provider] : false;
		if (empty($senderParams))
		{
			throw  new \Exception('sender_not_found');
		}

		$pickup_type = (isset($senderParams['pickup_type'])) ? (int) $senderParams['pickup_type'] : 1;

		if ($pickup_type === 2)
		{
			$requestData['pointInId'] = (int) $senderParams['point'];
		}
		else
		{
			$requestData['from'] = [
				'addressString' => $senderParams['address'],
				'countryCode'   => $senderParams['country'],
			];
		}
		$requestData['pickupTypes'] = [$pickup_type];

		$cod_payment_method = (int) $params->get('cod_payment_method', 0);
		if (empty($data['payment']) || empty($data['payment']['id'])
			|| (int) $data['payment']['id'] !== $cod_payment_method)
		{
			unset($requestData['codCost']);
		}

		$hash    = md5(serialize($requestData));
		$oldHash = (!empty($shipping['tariff']['hash'])) ? $shipping['tariff']['hash'] : '';
		$timeout = ((int) $params->get('cache', 1) === 1) ? '1 day' : '0';
		if (!empty($oldHash) && $oldHash !== $hash)
		{
			CacheHelper::deleteCache($method_id, 'calculator', $oldHash);
		}
		$request = (!$force) ? CacheHelper::getCache($method_id, 'calculator', $hash, $timeout) : false;
		if ($request === false)
		{
			$log = false;
			if ((int) $params->get('logs', 0) === 1)
			{
				$log = $method_id . '.apiship.calculator';
			}
			$request = ApiShipHelper::calculator($token, $requestData, $log);

			CacheHelper::saveCache($method_id, 'calculator', $hash, $request);
		}

		$delivery_typeString = ($delivery_type === 2) ? 'deliveryToPoint' : 'deliveryToDoor';
		$tariffs             = $request->get($delivery_typeString, []);
		$tariffs             = (!empty($tariffs[0]) && !empty($tariffs[0]->tariffs)) ? $tariffs[0]->tariffs : [];

		if (!empty($tariffs) && !empty($senderParams['tariffs_regexp']))
		{
			$rebuild = false;
			foreach ($tariffs as $t => $tariff)
			{
				if (!preg_match($senderParams['tariffs_regexp'], $tariff->tariffName))
				{
					unset($tariffs[$t]);
					$rebuild = true;
				}
			}
			if ($rebuild)
			{
				$tariffs = array_values($tariffs);
			}
		}

		return ['hash' => $hash, 'tariffs' => $tariffs, 'provider' => $provider];
	}

	/**
	 * Method to calculate order shipping price.
	 *
	 * @param   array  $data      Order shipping data.
	 * @param   array  $products  Order products data.
	 *
	 * @return array Order price data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function calculatePrice(array $data, array $products): array
	{
		try
		{
			$shipping  = (!empty($data['shipping'])) ? $data['shipping'] : [];
			$method_id = (!empty($shipping['id'])) ? (int) $shipping['id'] : 0;
			if (empty($method_id))
			{
				throw new \Exception('shipping_method_not_found');
			}
			$params = self::getShippingMethodParams($method_id);

			$delivery_type = (int) $params->get('delivery_type', 2);
			if ($delivery_type === 2 && empty($shipping['point']['id']))
			{
				throw  new \Exception('select_point');
			}
			if ($delivery_type === 1 && empty($shipping['address']['string']))
			{
				throw  new \Exception('select_address');
			}

			$tariff_id = (!empty($shipping['tariff']['id'])) ? (int) $shipping['tariff']['id'] : 0;
			if (empty($tariff_id))
			{
				throw new \Exception('select_tariff');
			}

			$request = $this->getOrderTariffs($data, $products);
			if (empty($request['tariffs']))
			{
				throw new \Exception('tariff_not_found', 404);
			}

			$find = false;
			foreach ($request['tariffs'] as $tariff)
			{
				if ((int) $tariff->tariffId === $tariff_id)
				{
					$find = $tariff;
					break;
				}
			}
			if (empty($find))
			{
				throw new \Exception('tariff_not_found', 404);
			}

			$result['base'] = $find->deliveryCost;
		}
		catch (\Throwable $e)
		{
			$result['error'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Method to get parsed shipping method params.
	 *
	 * @param   int  $method_id
	 *
	 * @return Registry Shipping method params.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getShippingMethodParams(int $method_id = 0): Registry
	{
		if (empty($method_id))
		{
			return new Registry([]);
		}

		if (isset(self::$_shippingParams[$method_id]))
		{
			return self::$_shippingParams[$method_id];
		}

		$params = ParamsHelper::getShippingMethodsParams($method_id);

		$sender = [];
		foreach (ArrayHelper::fromObject($params->get('sender', new \stdClass())) as $datum)
		{
			if (!isset($sender[$datum['provider']]))
			{
				$sender[$datum['provider']] = $datum;
			}
		}
		$params->set('sender', $sender);

		$providers = array_keys($sender);
		$params->set('providers', $providers);

		foreach (self::$defaultAddressFieldsParams as $field => $value)
		{
			$path = 'field_' . $field;
			if (empty($params->get($path)))
			{
				$params->set($path, $value);
			}
		}

		$fields_default = [];
		if (count($providers) > 0)
		{
			$fields_default['provider'] = $providers[0];
		}
		foreach (ArrayHelper::fromObject($params->get('fields_default', new \stdClass())) as $datum)
		{
			if (!isset($fields_default[$datum['field']]))
			{
				$fields_default[$datum['field']] = $datum['value'];
			}
		}
		$params->set('fields_default', $fields_default);

		self::$_shippingParams[$method_id] = $params;

		return $params;
	}

	/**
	 * Webhooks api callback.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function apiCallback(): void
	{
		$log   = 'apiship.callback';
		$app   = $this->getApplication();
		$input = $app->getInput();
		$entry = [
			'input' => $input->getArray(),
			'error' => false,
		];
		try
		{
			$info = $input->get('orderInfo', [], 'array');
			if (empty($info['orderId']))
			{
				throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_API_ORDER_NOT_FOUND'), 404);
			}
			if (empty($info['clientNumber']))
			{
				throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
			}
			$order_number = $info['clientNumber'];

			$secret = $input->getString('s', '');
			if (empty($secret) || $secret !== self::getWebhookSecret())
			{
				throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CALLBACK_ACCESS'), 403);
			}

			$db       = $this->getDatabase();
			$query    = $db->createQuery()
				->select('id')
				->from($db->quoteName('#__radicalmart_orders'))
				->where($db->quoteName('number') . '= :order_number')
				->bind(':order_number', $order_number);
			$order_id = (int) $db->setQuery($query, 0, 1)->loadResult();

			if (empty($order_id))
			{
				throw new \Exception(Text::_('COM_RADICALMART_ERROR_ORDER_NOT_FOUND'), 404);
			}

			/** @var OrderModel $model */
			$model = $this->getMVCFactory()->createModel('Order', 'Administrator', ['ignore_request' => true]);
			$model->setState('order.id', $order_id);
			$order = $model->getItem($order_id);

			$data       = $this->getApiOrderStatus($order);
			$status_key = $data->get('status.key');
			$this->changeOrderStatus($order, $status_key);

			if ($this->isRetailCRMEnabled())
			{
				$model->reset($order_id);
				$order = $model->getItem($order_id);

				try
				{
					$this->updateRetailCRMOrderShippingData($order);
					$this->changeRetailCRMOrderStatus($order, $status_key);
				}
				catch (\Throwable)
				{

				}
			}
		}
		catch (\Exception $e)
		{
			$entry['error'] = $e->getMessage();
			if ($e->getCode() === 403)
			{
				throw $e;
			}
			echo $e->getMessage();
		}

		LogHelper::addLog($log, $entry, (!empty($entry['error'])));

		$app->close(200);
	}

	/**
	 * Method to get Apiship webhook.
	 *
	 * @return string Webhook url.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getWebhookUrl(): string
	{
		$root     = Uri::getInstance()->toString(['scheme', 'host', 'port']);
		$endpoint = ParamsHelper::getComponentParams()->get('plugins_entry', 'radicalmart_plugins');

		return $root . '/' . $endpoint . '/shipping/apiship/callback?s=' . self::getWebhookSecret();
	}

	/**
	 * Method to get Apiship webhook secret.
	 *
	 * @return string Webhook secret.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getWebhookSecret(): string
	{
		return ApplicationHelper::getHash('apiship');
	}

	/**
	 * Method to get providers markers.
	 *
	 * @return string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getMapMarkers(): array
	{
		if (self::$_mapMarkers !== null)
		{
			return self::$_mapMarkers;
		}

		self::$_mapMarkers = [];

		$files = Folder::files(JPATH_SITE . '/media/plg_radicalmart_shipping_apiship/images', 'marker-');
		foreach ($files as $file)
		{
			$key = str_replace('marker-', '', File::stripExt($file));

			self::$_mapMarkers[$key] = HTMLHelper::image('plg_radicalmart_shipping_apiship/' . $file,
				'', null, true, true);
		}

		return self::$_mapMarkers;
	}

	/**
	 * Method to set place item dimensions.
	 *
	 * @param   array   $item     Modified shipping place item.
	 * @param   object  $product  Order product data.
	 *
	 * @throws \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setPlaceItemDimensions(array &$item, object $product): void
	{
		if ((int) $product->shipping->get('enable', 1) === 0)
		{
			throw new \Exception('product_shipping_not_available', 500);
		}

		$weight_unit      = $product->shipping->get('weight_unit', 'g');
		$dimensions_units = $product->shipping->get('dimensions_units', 'cm');

		foreach (['weight', 'width', 'height', 'length'] as $key)
		{
			$value = (int) round(NumberHelper::floatClean($product->shipping->get($key, 0)));
			if (empty($value))
			{
				throw new \Exception('product_shipping_not_available', 500);
			}

			if ($key === 'weight' && $weight_unit === 'kg')
			{
				$value = $value * 1000;
			}
			elseif ($key !== 'weight' && $dimensions_units !== 'cm')
			{
				$value = ($dimensions_units === 'mm') ? $value / 10 : $value * 100;
			}

			$item[$key] = $value;
		}
	}

	/**
	 * Method to check administrator client and throw if needed.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function checkAdministratorClient(): void
	{
		if (!$this->getApplication()->isClient('administrator'))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_ADMINISTRATOR_ONLY'), 403);
		}
	}

	/**
	 * Method to check is Radicalmart 3 installed.
	 *
	 * @return bool True if is RadicalMart 3, False if not.
	 *
	 * @since      __DEPLOY_VERSION__
	 */
	protected function isRadicalMart3(): bool
	{
		if ($this->_isRadicalMart3 !== null)
		{
			return $this->_isRadicalMart3;
		}

		// Get current version
		$db    = $this->getDatabase();
		$query = $db->createQuery()
			->select('manifest_cache')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('element') . ' = ' . $db->quote('com_radicalmart'));

		$version               = (new Registry($db->setQuery($query)->loadResult()))->get('version');
		$this->_isRadicalMart3 = (!(empty($version)) && version_compare($version, '2.9.5') >= 0);

		return $this->_isRadicalMart3;
	}
}