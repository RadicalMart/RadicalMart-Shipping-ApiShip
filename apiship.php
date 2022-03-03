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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
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
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

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
			$form->setFieldAttribute('from', 'key',
				ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key'),
				'params');
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
		if ($form->getName() === 'com_radicalmart.checkout')
		{
			$places = (new Registry($shipping->params->get('from')))->toString('json');
			$form->setFieldAttribute('from', 'places',
				$places, 'shipping');
			$form->setFieldAttribute('to', 'key',
				ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key'),
				'shipping');
		}
	}


	/**
	 * Method to ajax functions.
	 *
	 * @throws  Exception
	 *
	 * @return mixed Function result.
	 *
	 * @since  1.3.0
	 */
	public function onAjaxApiship()
	{
		$action = $this->app->input->get('action');
		if (empty($action) || !method_exists($this, $action))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_AJAX_METHOD_NOT_FOUND'), 500);
		}

		return $this->$action();
	}

	protected function calculateCheckout($force = false)
	{
		$data = $this->app->input->get('jform', array(), 'array');

		/** @var RadicalMartModelCheckout $checkoutModel */
		JLoader::register('RadicalMartHelperIntegration',
			JPATH_ADMINISTRATOR . '/components/com_radicalmart/helpers/integration.php');
		RadicalMartHelperIntegration::initializeSite();
		BaseDatabaseModel::addIncludePath(RadicalMartHelperIntegration::$sitePath . '/models');
		Table::addIncludePath(RadicalMartHelperIntegration::$adminPath . '/tables');
		Form::addFormPath(RadicalMartHelperIntegration::$sitePath . '/forms');
		Form::addFieldPath(RadicalMartHelperIntegration::$sitePath . '/field');
		$checkoutModel = BaseDatabaseModel::getInstance('Checkout', 'RadicalMartModel');
		$order         = $checkoutModel->getItem();
		if (!$order)
		{
			throw new Exception(Text::_('COM_RADICALMART_ORDER_NOT_FOUND'));
		}

		$products = $order->products;
		if (empty($products))
		{
			throw new Exception(Text::_('COM_RADICALMART_ERROR_PRODUCTS_NOT_FOUND'));
		}

		$shipping = (!empty($data['shipping'])) ? $data['shipping'] : array();

		$provider = 'boxberry'; // TODO Provider
		$token    = '9c3a7cfe13f402fc78b0dd6edad36993'; // TODO TOKEN
		$hash     = array(
			$provider,
			$shipping['from']['addressString'],
			$shipping['to']['addressString'],
		);

		$request = array(
			'providerKeys' => array($provider),
			'weight'       => 0,
			'width'        => 0,
			'height'       => 0,
			'length'       => 0,
			'assessedCost' => $order->total['final'],
			'from'         => (!empty($shipping['from'])) ? $shipping['from'] : array(),
			'to'           => (!empty($shipping['to'])) ? $shipping['to'] : array(),
			'places'       => array(),
		);
		foreach ($products as $product)
		{
			if ((int) $product->shipping->get('enable', 1) === 0
				|| empty($product->shipping->get('weight', ''))) continue;

			$item = new stdClass();

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
			$item->weight = (float) $product->shipping->get('weight');
			if ($product->shipping->get('weight_unit') === 'kg')
			{
				$item->weight = $item->weight * 1000;
			}

			$hash[] = $product->id . ':' . $product->order['quantity'];
			for ($i = 1; $i <= $product->order['quantity']; $i++)
			{
				$request['places'][] = $item;
				$request['weight']   += $item->weight;
				$request['width']    += $item->width;
				$request['height']   += $item->height;
				$request['length']   += $item->length;
			}
		}

		if (empty($request['places'])) throw new Exception(Text::_('COM_RADICALMART_PRODUCTS_NOT_FOUND'));

		// Check hash
		$oldHash = (!empty($data['shipping']['calculate']['hash'])) ? $data['shipping']['calculate']['hash'] : '';
		$hash    = md5(serialize($hash));
		if ($hash !== $oldHash || $force)
		{
			$http = new Http();
			$http->setOption('transport.curl', array(CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
			$request = (new Registry($request))->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));

			$response = $http->post('http://api.dev.apiship.ru/v1/calculator', $request,
				array('Content-Type' => 'application/json', 'authorization' => $token));

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

			$result = array();
			foreach ($context->get('deliveryToDoor') as $deliveryToDoor)
			{
				if ($deliveryToDoor->providerKey === $provider)
				{
					if (!empty($deliveryToDoor->tariffs[0]))
					{
						$result['cost']    = $deliveryToDoor->tariffs[0]->deliveryCost;
						$result['daysMin'] = $deliveryToDoor->tariffs[0]->daysMin;
						$result['daysMax'] = $deliveryToDoor->tariffs[0]->daysMax;
					}
				}
			}
		}
		else
		{
			$result = $data['shipping']['calculate'];
		}

		if (empty($result))
		{
			throw new Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_CALCULATE_COST'), 500);
		}
		else
		{
			$result['hash'] = $hash;

			$code                  = $order->currency['code'];
			$result['cost_string'] = RadicalMartHelperPrice::toString($result['cost'], $code);
			$result['cost_seo']    = RadicalMartHelperPrice::toString($result['cost'], $code, 'seo');
			$result['cost_number'] = RadicalMartHelperPrice::toString($result['cost'], $code, false);
		}

		return $result;
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
	 * @since  1.0.0
	 */
	public function onRadicalMartGetShippingMethods($context, $method, $formData, $products, $currency)
	{
		// Set disabled
		$method->disabled = false;

		// Set price

		if (!empty($formData['shipping']['calculate'])) $price = array('base' => $formData['shipping']['calculate']['cost']);
		else $price = array('base' => 0);

		$price = $this->preparePrice($price, $currency['code']);

		// Set order
		$method->order              = new stdClass();
		$method->order->id          = $method->id;
		$method->order->title       = $method->title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;
		if (!empty($formData['shipping']['calculate']))
		{
			foreach ($formData['shipping']['calculate'] as $key => $value) $method->order->$key = $value;
		}

		//if ($context === 'com_radicalmart.checkout') $method->layout = 'plugins.radicalmart_checkout.standard.checkout';
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
	 * @since 1.0.0
	 */
	public function onRadicalMartGetOrderTotal($context, &$total, $formData, $shipping, $payment, $currency)
	{
		if (!empty($shipping->order->price['base'])) $total['base'] += $shipping->order->price['base'];
		if (!empty($shipping->order->price['final'])) $total['final'] += $shipping->order->price['final'];
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
	 * @since  1.0.0
	 */
	protected function preparePrice($price = array(), $code = null)
	{
		// Set base price
		$price['base']        = RadicalMartHelperPrice::clean($price['base'], $code);
		$price['base_string'] = (empty($price['base'])) ?
			Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE')
			: RadicalMartHelperPrice::toString($price['base'], $code);
		$price['base_seo']    = (empty($price['base'])) ? Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE')
			: RadicalMartHelperPrice::toString($price['base'], $code, 'seo');
		$price['base_number'] = RadicalMartHelperPrice::toString($price['base'], $code, false);

		// Set final price
		$price['final']        = $price['base'];
		$price['final_string'] = (empty($price['final'])) ? Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE')
			: RadicalMartHelperPrice::toString($price['final'], $code);
		$price['final_seo']    = (empty($price['final'])) ? Text::_('PLG_RADICALMART_SHIPPING_APISHIP_NULL_PRICE')
			: RadicalMartHelperPrice::toString($price['final'], $code, 'seo');
		$price['final_number'] = RadicalMartHelperPrice::toString($price['final'], $code, false);

		return $price;
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
	 * @since  1.0.0
	 */
	public function onContentNormaliseRequestData($context, &$objData, $form)
	{
		if ($context === 'com_radicalmart.checkout' && $form->getName() === 'com_radicalmart.checkout')
		{
			$objData['shipping']['calculate'] = $this->calculateCheckout(true);
		}
	}
}