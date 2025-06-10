<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Extension;

\defined('_JEXEC') or die;

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
use Joomla\Component\RadicalMart\Administrator\Helper\NumberHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Component\RadicalMart\Administrator\Model\OrderModel;
use Joomla\Component\RadicalMart\Site\Model\CheckoutModel;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\AddressHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\ApiShipHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\CacheHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\DaDataHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class ApiShip extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use MVCFactoryAwareTrait;

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

			'onAjaxApiship' => 'onAjax',
		];
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
				$form->setFieldAttribute($key, 'default', $value, 'shipping.display');
				$form->setFieldAttribute($key, 'value', $value, 'shipping.display');
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

		$result = [
			'provider'        => '',
			'delivery_type'   => Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE_' . $delivery_type),
			'address'         => '',
			'comment'         => (!empty($shipping['comment'])) ? $shipping['comment'] : '',
			'tariff'          => (!empty($shipping['tariff']['id'])) ? $shipping['tariff']['name'] : '',
			'cost'            => (!empty($shipping['price']['final']))
				? PriceHelper::toString($shipping['price']['final'], $currency['code']) : '',
			'cost_seo'        => (!empty($shipping['price']['final']))
				? PriceHelper::toString($shipping['price']['final'], $currency['code'], 'seo') : '',
			'tracking_number' => (!empty($shipping['tracking_number'])) ? $shipping['tracking_number'] : '',
			'date'            => (!empty($shipping['date']))
				? HTMLHelper::date($shipping['date'], Text::_('DATE_FORMAT_LC4')) : '',
			'note'            => (!empty($shipping['note'])) ? $shipping['note'] : '',
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
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartAfterOrderSave(string $context, array $formData, array $data, object $order, bool $isNew): void
	{
		$this->saveCustomerData($context, $formData, $data);
		$this->removeCalculatorCache($formData);
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
		$query              = $db->getQuery(true)
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
	 * Method to delete Calculator request cahce.
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
	public function onRadicalMartGetPersonalShippingMethods(string $context, object &$method)
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
	public function onRadicalMartPrepareCustomerMethodSaveData(string $context, array &$data, object $method, bool $isNew)
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
			$app    = $this->getApplication();
			$task   = $app->input->get('task');
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
		$app      = $this->getApplication();
		$shipping = $app->input->getInt('shipping', 0);
		$file     = $app->input->getCmd('file');
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
			$app      = $this->getApplication();
			$formData = $app->input->get('jform', [], 'array');
			$context  = $app->input->getString('field_context', 'com_radicalmart.order');
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
					'field_name' => $app->input->get('field_name', 'rmsa_tariff', 'string'),
					'field_id'   => $app->input->get('field_id', 'rmsa_tariff', 'string'),
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
			$app      = $this->getApplication();
			$shipping = $app->input->getInt('shipping', 0);
			$address  = $app->input->get('validate', [], 'array');

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
		$data    = $app->input->get('jform', [], 'array');
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
		$method_id = $this->getApplication()->input->getInt('shipping', 0);
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
		$app       = $this->getApplication();
		$method_id = $app->input->getInt('shipping', 0);
		if (empty($method_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}
		$limit = $app->input->getInt('limit', 0);
		if ($limit === 0)
		{
			throw new \Exception('Incorrect limit loop', 500);
		}

		$params    = self::getShippingMethodParams($method_id);
		$token     = $params->get('token');
		$providers = $params->get('providers', []);
		$operation = [2, 3];
		$sandbox   = ((int) $params->get('sandbox', 0) === 1);

		$offset = $app->input->getInt('offset', 0);
		$action = $app->input->getCmd('action', 'total');
		if ($action === 'start')
		{
			$folder = CacheHelper::getPointsCacheFolder($method_id);
			if (!is_dir($folder))
			{
				Folder::create($folder);
			}

			$total   = ApiShipHelper::getPointsTotal($token, $providers, $operation, $sandbox);
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
			$requestRows = ApiShipHelper::getPoints($token, $providers, $operation, $sandbox, $offset, $limit);
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
		if (!$this->getApplication()->isClient('administrator'))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		$sipping_id = $this->getApplication()->input->getInt('id', 0);
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

		$list = $this->getApplication()->input->getString('list');
		if (!empty($list) && in_array($list, $lists))
		{
			$filter    = [];
			$sandbox   = ((int) $params->get('sandbox', 0) === 1);
			$providers = $params->get('providers', []);

			if ($list === 'tariffs')
			{
				$filter[] = [
					'key'      => 'providerKey',
					'operator' => '=',
					'value'    => $providers
				];
			}

			dd(ApiShipHelper::getList($token, $list, $filter, $sandbox));
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
		$check = (!empty($app->input->post->getInt('check_post')));

		$message  = ($check) ? '' : Text::_('COM_RADICALMART_ERROR_ACCESS_DENIED');
		$code     = ($check) ? 200 : 401;
		$response = ($check) ? Session::getFormToken() : 1;

		header('Content-Type: application/json');
		echo new JsonResponse($response, $message, ($code !== 200));
		Factory::getApplication()->close($code);

		return ($code === 200);
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
			try
			{
				$clenAddress = DadataHelper::cleanAddress($dadata_token, $dadata_secret, $address);
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
			}
			catch (\Throwable $e)
			{

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
		$sandbox       = ((int) $params->get('sandbox', 0) === 1);
		$delivery_type = (int) $params->get('delivery_type', 2);
		$senders       = $params->get('sender', []);

		$requestData = ['places' => []];

		foreach ($products as $product)
		{
			if ((int) $product->shipping->get('enable', 1) === 0)
			{
				throw new \Exception('product_shipping_not_available');
			}

			$item = [];

			$weight_unit      = $product->shipping->get('weight_unit', 'g');
			$dimensions_units = $product->shipping->get('dimensions_units', 'cm');

			foreach (['weight', 'width', 'height', 'length'] as $key)
			{
				$value = NumberHelper::floatClean($product->shipping->get($key, 0));
				if (empty($value))
				{
					throw new \Exception('product_shipping_not_available');
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
			for ($i = 1; $i <= $product->order['quantity']; $i++)
			{
				$requestData['places'][] = $item;
			}
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

		$hash    = md5(serialize($requestData));
		$oldHash = (!empty($shipping['tariff']['hash'])) ? $shipping['tariff']['hash'] : '';
		if (!empty($oldHash) && $oldHash !== $hash)
		{
			CacheHelper::deleteCache($method_id, 'calculator', $oldHash);
		}
		$request = (!$force) ? CacheHelper::getCache($method_id, 'calculator', $hash) : false;
		if ($request === false)
		{
			$request = ApiShipHelper::calculator($token, $requestData, $sandbox);
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
}