<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\Button\CustomButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\Component\RadicalMart\Administrator\Helper\NumberHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\QuantityHelper;
use Joomla\Component\RadicalMart\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\ApiShipHelper;
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
			'onRadicalMartGetOrderForm'            => 'onRadicalMartGetOrderForm',

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
		$registry      = new Registry($data);
		$params        = new Registry($registry->get('params'));
		$id            = (int) $registry->get('id', 0);
		$delivery_type = (int) $params->get('delivery_type', 2);

		$pointFields   = [
			'points_map_key',
			'points_points_reset',
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
				$form->setFieldAttribute('points_reset', 'shipping', $id, 'params');
			}
			else
			{
				$form->removeField('points_reset', 'params');
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
		$message         = [];
		$error           = [];
		$calculate_price = false;
		if ($context === 'com_radicalmart.checkout')
		{
			$calculate_price = true;
		}
		elseif ($context === 'com_radicalmart.order')
		{
			if (isset($data['recalculate_price']))
			{
				$calculate_price = ((int) $data['recalculate_price'] === 1);
			}
		}

		// Calculate price
		$price = [];
		if ($calculate_price)
		{
			$language = $this->getApplication()->getLanguage();
			$price    = $this->calculatePrice($context, $method->id, $data, $products, $currency);
			if (!empty($price['error']))
			{
				$constant = 'PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CALCULATE_' . $price['error'];
				$text     = $language->hasKey($constant) ? Text::_($constant) : $price['error'];

				$price['base']        = 0;
				$price['base_string'] = ' ';
				$price['base_seo']    = ' ';
				$price['base_number'] = 0;

				if (in_array($price['error'], ['select_point', 'enter_address', 'select_tariff']))
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
		elseif (!empty($data['price']) && !empty($data['price']['base']))
		{
			$price = $data['price'];
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
		$method->disabled = false;
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
	}

	/**
	 * Prepare RadicalMart order form.
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

		// Prepare sender field
		$form->setFieldAttribute('sender', 'places',
			(new Registry($shipping->params->get('sender')))->toString(), 'shipping');

		$delivery_type = (int) $shipping->params->get('delivery_type', 2);

		if ($delivery_type === 1)
		{
			$form->removeField('point', 'shipping');
		}
		elseif ($delivery_type === 2)
		{
			$form->removeGroup('shipping.address');

			$form->setFieldAttribute('point', 'shipping', $shipping->id, 'shipping');
			$form->setFieldAttribute('point', 'context', $context, 'shipping');
		}
		else
		{
			$form->removeGroup('shipping.address');
			$form->removeField('point', 'shipping');
		}
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
			throw new \Exception('ApiShip: ' . $e->getMessage(), $e->getCode(), $e);
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
		if (empty($shipping))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'));
		}

		return $this->getPointsRows($shipping);
	}

	protected function onAjaxLoadTariffsField()
	{
		exit('ss');
	}

	/**
	 * Method to hard reset shipping method points cache.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function ajaxResetPointsCache(): void
	{
		$this->getPointsRows($this->getApplication()->input->getInt('id', 0), true);

		echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_CACHE_RESET_SUCCESS');
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
	 * Method to get Pickup points from cache if can.
	 *
	 * @param   int   $method_id  Shipping method id.
	 * @param   bool  $force      Force regenerate cache
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getPointsRows(int $method_id, bool $force = false): array

	{
		if (empty($method_id))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'));
		}

		$cacheFolder = JPATH_CACHE . '/' . 'plg_radicalmart_shipping_apiship';
		if (!is_dir($cacheFolder))
		{
			Folder::create($cacheFolder);
		}

		$cacheFile  = $cacheFolder . '/points_' . $method_id . '.json';
		$cacheExist = false;
		if (is_file($cacheFile))
		{
			$cacheExist = true;
			if ($force || (time() - filemtime($cacheFile)) >= 86400)
			{
				File::delete($cacheFile);
				$cacheExist = false;
			}
		}
		if (!$cacheExist)
		{
			$params = self::getShippingMethodParams($method_id);

			$rows = ApiShipHelper::getPoints(
				$params->get('token'),
				array_keys($params->get('sender')),
				[2, 3],
				((int) $params->get('sandbox', 0) === 1)
			);
			$keys = ['id', 'providerKey', 'name',
				'countryCode', 'address', 'phone', 'timetable', 'lat', 'lng'];
			foreach ($rows as &$row)
			{
				foreach (array_keys($row) as $key)
				{
					if (!in_array($key, $keys))
					{
						unset($row[$key]);
					}
				}
			}

			if (count($rows) === 0)
			{
				throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_GET_POINTS'));
			}

			file_put_contents($cacheFile, (new Registry($rows))->toString());
		}
		else
		{
			$rows = (new Registry(file_get_contents($cacheFile)))->toArray();
		}

		return $rows;
	}

	/**
	 * Method to calculate order shipping price.
	 *
	 * @param   string  $context    Context selector string.
	 * @param   int     $method_id  Shipping Method ID.
	 * @param   array   $data       Order shipping data.
	 * @param   array   $products   Order products data.
	 * @param   array   $currency   Order currency data.
	 *
	 * @return array Order price data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function calculatePrice(string $context, int $method_id, array $data, array $products, array $currency): array
	{
		try
		{
			$params   = self::getShippingMethodParams($method->id);
			$shipping = (!empty($data['shipping'])) ? $data['shipping'] : [];

			$token         = $params->get('token');
			$delivery_type = (int) $params->get('delivery_type', 2);
			$sandbox       = ((int) $params->get('sandbox', 0) === 1);

			$requestData = [
				'places' => [],
				'weight' => 0,
				'width'  => 0,
				'height' => 0,
				'length' => 0,
			];
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
					$value = NumberHelper::floatClean($product->shipping->get('weight', 0));
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
					$requestData['weight']   += $item['weight'];
					$requestData['width']    += $item['width'];
					$requestData['height']   += $item['height'];
					$requestData['length']   += $item['length'];
				}
			}

			if (count($requestData['places']) === 0)
			{
				throw new \Exception('places_not_found');
			}

			$provider = '';
			if ($delivery_type === 2)
			{
				if ((empty($shipping['point']['id'])))
				{
					throw  new \Exception('select_point');
				}

				$requestData['pointOutId'] = $shipping['point']['id'];
				$provider                  = $shipping['point']['providerKey'];
			}
			else
			{
				// TODO recipient
			}

			if (empty($shipping['tariff']))
			{
				throw new \Exception('select_tariff');
			}

			// Sender data
			$requestData['from'] = [];
			foreach ([
				         'address'     => 'addressString',
				         'countryCode' => 'countryCode',
				         'latitude'    => 'lat',
				         'longitude'   => 'lng'
			         ] as $sf => $st)
			{
				if (!empty($shipping['sender'][$sf]))
				{
					$requestData['from'][$st] = $shipping['sender'][$sf];
				}
			}

			$tariff = ApiShipHelper::getTariff($token, $requestData, $provider, $delivery_type, $sandbox);

			$result['base'] = $tariff->deliveryCost;
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