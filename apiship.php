<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

class plgRadicalMart_ShippingApiShip extends CMSPlugin
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  JDatabaseDriver
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db = null;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Shipping method params.
	 *
	 * @var  Registry
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected $_shippingMethodParams = null;

	/**
	 * Add apiship config form to RadicalMart.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareConfigForm($form, $data)
	{
		Form::addFormPath(__DIR__ . '/forms');
		$form->loadFile('config');
	}

	/**
	 * Add onRadicalMartPrepareConfigForm & onRadicalMartPrepareProductForm triggers and modules positions.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		if ($form->getName() === 'com_radicalmart.shippingmethod')
		{

		}
	}

	/**
	 * Add onRadicalMartPrepareConfigForm & onRadicalMartPrepareProductForm triggers and modules positions.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderForm($context, $form, $data, $shipping, $payment)
	{
		$formName = $form->getName();
		if ($formName === 'com_radicalmart.checkout' || $formName === 'com_radicalmart.order')
		{
			$places = (new Registry($shipping->params->get('sender')))->toString('json');

			$bindData = $data;
			if (empty($bindData)) $bindData = $this->app->input->get('jform', array(), 'array');
			if (!empty($bindData['shipping']) && !empty($bindData['shipping']['delivery_type'])
				&& (int) $bindData['shipping']['delivery_type'] === 2)
			{
				$form->removeField('recipient', 'shipping');
			}
			else $form->removeField('pvz', 'shipping');
			$form->setFieldAttribute('sender', 'places', $places, 'shipping');

			HTMLHelper::script('plg_radicalmart_shipping_apiship/order.min.js', array('version' => 'auto', 'relative' => true));

			Factory::getDocument()->addScriptOptions('radicalmart_shipping_apiship_order', array(
				'shipping_id' => $shipping->id,
				'controller'  => Route::_('index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&format=json', false),
				'formType'    => ($formName === 'com_radicalmart.checkout') ? 'checkout' : 'order',
			));
		}
		elseif ($formName === 'com_radicalmart.order_site')
		{
			if (!empty($data['shipping']) && !empty($data['shipping']['delivery_type'])
				&& (int) $data['shipping']['delivery_type'] === 2)
			{
				$form->removeField('address', 'shipping.recipient');
			}
			else $form->removeField('address', 'shipping.pvz');
		}
	}

	/**
	 * Prepare order shipping method data.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   object  $method    Method data.
	 * @param   array   $formData  Order form data.
	 * @param   array   $products  Order products data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetShippingMethods($context, $method, $formData, $products, $currency)
	{
		// Set disabled
		$method->disabled = false;
		foreach ($products as $product)
		{
			if ((int) $product->shipping->get('enable', 1) === 0
				|| empty($product->shipping->get('weight', '')))
			{
				$method->disabled = true;
				break;
			}
		}

		// Set price
		if (!empty($formData['shipping']['price'])) $price = $formData['shipping']['price'];
		else $price = array('base' => 0);
		$price = $this->preparePrice($price, $currency['code']);

		// Unset token
		$method->params->remove('token');

		// Set order
		$method->order              = new stdClass();
		$method->order->id          = $method->id;
		$method->order->title       = $method->title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;

		if ($context === 'com_radicalmart.checkout') $method->layout = 'plugins.radicalmart_shipping.apiship.form.checkout';
	}

	/**
	 * Prepare order totals.
	 *
	 * @param   string  $context   Context selector string.
	 * @param   array   $total     Order total data.
	 * @param   array   $formData  Form data array.
	 * @param   object  $shipping  Shipping method data.
	 * @param   object  $payment   Payment method data.
	 * @param   array   $currency  Order currency data.
	 *
	 * @throws Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderTotal($context, &$total, $formData, $shipping, $payment, $currency)
	{
		if (!empty($shipping->order->price['base'])) $total['base'] += $shipping->order->price['base'];
		if (!empty($shipping->order->price['final'])) $total['final'] += $shipping->order->price['final'];
	}

	/**
	 * Method to ajax functions.
	 *
	 * @throws  Exception
	 *
	 * @return mixed Function result.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAjaxApiship()
	{
		$action = $this->app->input->get('action');
		if (empty($action) || !method_exists($this, $action))
		{
			throw new Exception('ApiShip: ' .
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_AJAX_METHOD_NOT_FOUND'), 500);
		}

		return $this->$action();
	}

	/**
	 * Method to get PVZ points array.
	 *
	 * @throws  Exception
	 *
	 * @return mixed Function result.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getPVZ()
	{
		$data = $this->app->input->get('jform', array(), 'array');
		if (empty($data['shipping']) || empty($data['shipping']['id']))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}
		$params = $this->getShippingMethodParams($data['shipping']['id']);
		if (!$params)
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}

		$provider = $params->get('provider');

		$limit = 1000000;
		$url   = ($params->get('sandbox')) ? 'http://api.dev.apiship.ru/v1' : 'https://api.apiship.ru/v1';
		$url   .= '/lists/points?limit=' . $limit . '&filter='
			. 'providerKey=' . $provider . ';availableOperation=[2,3]';

		$sandbox = ((int) $params->get('sandbox') === 1);
		$token   = ($sandbox) ? '9c3a7cfe13f402fc78b0dd6edad36993' : $params->get('token');

		$http = new Http();
		$http->setOption('transport.curl', array(CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
		$headers  = array(
			'Content-Type'  => 'application/json',
			'authorization' => $token
		);
		$response = $http->get($url, $headers);

		if (((new Version())->isCompatible('4.0')))
		{
			$body    = $response->body;
			$context = (!empty($body)) ? new Registry($response->body) : false;
		}
		else $context = (!empty($response->body)) ? new Registry($response->body) : false;

		if ($response->code !== 200)
		{
			$message = ($context) ? $context->get('message')
				: preg_replace('#^[0-9]*\s#', '', $response->headers['Status']);
			$code    = $response->code;

			throw new Exception($message, $code);
		}

		$result = array(
			'type'     => 'FeatureCollection',
			'features' => array(),
		);
		foreach ($context->get('rows', array()) as $row)
		{
			$result['features'][] = array(
				'type'       => 'Feature',
				'id'         => $row->id,
				'title'      => $row->name,
				'latitude'   => $row->lat,
				'longitude'  => $row->lng,
				'address'    => $row->address,
				'geometry'   => array(
					'type'        => 'Point',
					'coordinates' => array($row->lat, $row->lng)
				),
				'properties' => array(
					'balloonContent' => '',
				),
				'options'    => array(
					'openBalloonOnClick' => false,
				)
			);
		}

		return $result;
	}

	/**
	 * Prepare prices data.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   object  $objData  Input data.
	 * @param   Form    $form     Joomla Form object.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentNormaliseRequestData($context, &$objData, $form)
	{
		if ($context === 'com_radicalmart.checkout' && $form->getName() === 'com_radicalmart.checkout')
		{
			$objData['shipping']['price'] = $this->calculateCheckout(true);
		}
	}

	/**
	 * Method to calculate shipping price in checkout.
	 *
	 * @param   bool  $force  Force calculate prices.
	 *
	 * @throws Exception
	 *
	 * @return array Calculate shipping price result.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function calculateCheckout($force = false)
	{
		$data = $this->app->input->get('jform', array(), 'array');

		$this->app->setUserState('com_radicalmart.checkout.data', $data);

		// Get order data
		/** @var RadicalMartModelCheckout $checkoutModel */
		JLoader::register('RadicalMartHelperIntegration',
			JPATH_ADMINISTRATOR . '/components/com_radicalmart/helpers/integration.php');
		RadicalMartHelperIntegration::initializeSite();
		BaseDatabaseModel::addIncludePath(RadicalMartHelperIntegration::$sitePath . '/models');
		Table::addIncludePath(RadicalMartHelperIntegration::$adminPath . '/tables');
		Form::addFormPath(RadicalMartHelperIntegration::$sitePath . '/forms');
		Form::addFieldPath(RadicalMartHelperIntegration::$sitePath . '/field');
		$checkoutModel = BaseDatabaseModel::getInstance('Checkout', 'RadicalMartModel');

		return $this->calculate($checkoutModel->getItem(), $force);
	}

	/**
	 * Method to calculate shipping price.
	 *
	 * @param   object  $order  Order object.
	 * @param   bool    $force  Force calculate prices.
	 *
	 * @throws Exception
	 * @return array Calculate shipping price result.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function calculate($order = null, $force = false)
	{
		if (empty($order)) throw new Exception(Text::_('COM_RADICALMART_ORDER_NOT_FOUND'));
		$products = $order->products;
		if (empty($products)) throw new Exception(Text::_('COM_RADICALMART_ERROR_PRODUCTS_NOT_FOUND'));

		if (!$order->shipping || empty($order->shipping->id))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}

		$params = $this->getShippingMethodParams($order->shipping->id);
		if (empty($params))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}

		$shipping = (!empty($order->formData['shipping'])) ? $order->formData['shipping'] : array();
		if (empty($shipping['sender']['address']))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_EMPTY_SENDER_ADDRESS'));
		}

		$recipientData = ((int) $shipping['delivery_type'] !== 2) ? $shipping['recipient'] : $shipping['pvz'];
		if (empty($recipientData['address']))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_EMPTY_RECIPIENT_ADDRESS'));
		}

		// Prepare hash
		$provider = $params->get('provider');
		$hash     = array(
			$provider,
			$shipping['sender']['address'],
			$recipientData['address'],
			$shipping['delivery_type'],
		);

		// Prepare request
		$sender = array();
		if (!empty($shipping['sender']['address'])) $sender['addressString'] = $shipping['sender']['address'];
		if (!empty($shipping['sender']['latitude'])) $sender['lat'] = $shipping['sender']['latitude'];
		if (!empty($shipping['sender']['longitude'])) $sender['lng'] = $shipping['sender']['longitude'];

		$recipient = array();
		if (!empty($recipientData['address'])) $recipient['addressString'] = $recipientData['address'];
		if (!empty($recipientData['latitude'])) $recipient['lat'] = $recipientData['latitude'];
		if (!empty($recipientData['longitude'])) $recipient['lng'] = $recipientData['longitude'];

		$deliveryType = (int) $shipping['delivery_type'];

		$data = array(
			'providerKeys'  => array($provider),
			'deliveryTypes' => array($deliveryType),
			'weight'        => 0,
			'width'         => 0,
			'height'        => 0,
			'length'        => 0,
			'assessedCost'  => $order->total['final'],
			'from'          => $sender,
			'to'            => $recipient,
			'places'        => array(),
		);

		foreach ($products as $product)
		{
			if ((int) $product->shipping->get('enable', 1) === 0
				|| empty($product->shipping->get('weight', '')))
			{
				throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_CALCULATE_COST'));
			}

			$item         = new stdClass();
			$item->weight = (float) $product->shipping->get('weight');
			if ($product->shipping->get('weight_unit') === 'kg')
			{
				$item->weight = $item->weight * 1000;
			}

			if (!empty($product->shipping->get('length', '')))
			{
				$item->length = (float) $product->shipping->get('length');
			}

			if (!empty($product->shipping->get('length', '')))
			{
				$item->length = (float) $product->shipping->get('length');
			}
			if (!empty($product->shipping->get('width', '')))
			{
				$item->width = (float) $product->shipping->get('width');
			}
			if (!empty($product->shipping->get('height', '')))
			{
				$item->height = (float) $product->shipping->get('height');
			}

			if ($product->shipping->get('dimensions_units') === 'mm')
			{
				if (!empty($item->length)) $item->length = $item->length / 10;
				if (!empty($item->width)) $item->width = $item->width / 10;
				if (!empty($item->height)) $item->height = $item->height / 10;
			}
			elseif ($product->shipping->get('dimensions_units') === 'm')
			{
				if (!empty($item->length)) $item->length = $item->length * 100;
				if (!empty($item->width)) $item->width = $item->width * 100;
				if (!empty($item->height)) $item->height = $item->height * 100;
			}

			$hash[] = $product->id . ':' . $product->order['quantity'];
			for ($i = 1; $i <= $product->order['quantity']; $i++)
			{
				$data['places'][] = $item;
				$data['weight']   += $item->weight;

				if (!empty($item->width)) $data['width'] += $item->width;
				if (!empty($item->height)) $data['height'] += $item->height;
				if (!empty($item->length)) $data['length'] += $item->length;
			}
		}
		if (empty($data['places'])) throw new Exception(Text::_('COM_RADICALMART_ERROR_PRODUCTS_NOT_FOUND'));

		// Calculate
		$oldHash = (!empty($shipping['price']['hash'])) ? $shipping['price']['hash'] : '';
		$newHash = md5(serialize($hash));
		if ($newHash !== $oldHash)
		{
			$request = $this->sendRequest('calculator', $data, $shipping['id']);
			$result  = array();
			if ($deliveryType === 1)
			{

				foreach ($request->get('deliveryToDoor') as $deliveryToDoor)
				{
					if ($deliveryToDoor->providerKey === $provider)
					{
						if (!empty($deliveryToDoor->tariffs[0]))
						{
							$result['base']  = $deliveryToDoor->tariffs[0]->deliveryCost;
							$result['final'] = $deliveryToDoor->tariffs[0]->deliveryCost;
						}
					}
				}
			}
			else
			{
				foreach ($request->get('deliveryToPoint') as $deliveryToPoint)
				{

					if ($deliveryToPoint->providerKey === $provider)
					{
						if (!empty($deliveryToPoint->tariffs[0]))
						{
							if (in_array($recipientData['id'], $deliveryToPoint->tariffs[0]->pointIds))
							{
								$result['base']  = $deliveryToPoint->tariffs[0]->deliveryCost;
								$result['final'] = $deliveryToPoint->tariffs[0]->deliveryCost;
							}
						}
					}
				}
			}
		}
		else $result = $shipping['price'];
		if (empty($result)) throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_CALCULATE_COST'));

		return $result;
	}

	/**
	 * Method to send api request.
	 *
	 * @param   string  $method          The api method name.
	 * @param   array   $data            Request data.
	 * @param   int     $shippingMethod  Shipping method id.
	 *
	 * @throws Exception
	 *
	 * @return  Registry  Response data on success.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function sendRequest($method = null, $data = array(), $shippingMethod = null)
	{
		$params = $this->getShippingMethodParams($shippingMethod);
		if (empty($params))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}

		$sandbox = ((int) $params->get('sandbox') === 1);
		$url     = ($params->get('sandbox')) ? 'http://api.dev.apiship.ru/v1' : 'https://api.apiship.ru/v1';
		if (!empty($method)) $url .= '/' . $method;
		$token = ($sandbox) ? '9c3a7cfe13f402fc78b0dd6edad36993' : $params->get('token');

		$data = (new Registry($data))->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));

		$http = new Http();
		$http->setOption('transport.curl', array(CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
		$headers  = array(
			'Content-Type'  => 'application/json',
			'authorization' => $token
		);
		$response = $http->post($url, $data, $headers);

		if (((new Version())->isCompatible('4.0')))
		{
			$body    = $response->body;
			$context = (!empty($body)) ? new Registry($response->body) : false;
		}
		else $context = (!empty($response->body)) ? new Registry($response->body) : false;

		if ($response->code !== 200)
		{
			$message = ($context) ? $context->get('message')
				: preg_replace('#^[0-9]*\s#', '', $response->headers['Status']);
			$code    = $response->code;

			throw new Exception($message, $code);
		}

		return $context;
	}

	/**
	 * Method to get shipping method params.
	 *
	 * @param   int  $pk  Payment method id.
	 *
	 * @return Registry Shipping method params
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getShippingMethodParams($pk = null)
	{
		$pk = (int) $pk;
		if (empty($pk)) return new Registry();

		if ($this->_shippingMethodParams === null) $this->_shippingMethodParams = array();
		if (!isset($this->_shippingMethodParams[$pk]))
		{
			$db                               = $this->db;
			$query                            = $db->getQuery(true)
				->select('params')
				->from($db->quoteName('#__radicalmart_shipping_methods'))
				->where('id = ' . $pk);
			$this->_shippingMethodParams[$pk] = ($result = $db->setQuery($query, 0, 1)->loadResult())
				? new Registry($result) : new Registry();
		}

		return $this->_shippingMethodParams[$pk];
	}

	/**
	 * Prepare price values.
	 *
	 * @param   array   $price  Item price array.
	 * @param   string  $code   Currency code.
	 *
	 * @throws Exception
	 *
	 * @return array Formatting price array, False on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function preparePrice($price = array(), $code = null)
	{
		// Set base price

		if (empty($price['base']))
		{
			$price['base']        = 0;
			$price['base_string'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE');
			$price['base_seo']    = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE');
			$price['base_number'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE');
		}
		elseif ((float) $price['base'] == -1)
		{
			$price['base']        = 0;
			$price['base_string'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_CALCULATE_COST');
			$price['base_seo']    = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_CALCULATE_COST');
			$price['base_number'] = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_CALCULATE_COST');
		}
		else
		{
			$price['base']        = RadicalMartHelperPrice::clean($price['base'], $code);
			$price['base_string'] = RadicalMartHelperPrice::toString($price['base'], $code,);
			$price['base_seo']    = RadicalMartHelperPrice::toString($price['base'], $code, 'seo');
			$price['base_number'] = RadicalMartHelperPrice::toString($price['base'], $code, false);;
		}

		$price['final']        = $price['base'];
		$price['final_string'] = $price['base_string'];
		$price['final_seo']    = $price['base_seo'];
		$price['final_number'] = $price['base_number'];

		return $price;
	}
}