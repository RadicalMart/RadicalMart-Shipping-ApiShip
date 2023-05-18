<?php
/*
 * @package     RadicalMart Shipping Standard Plugin
 * @subpackage  plg_radicalmart_shipping_standard
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class ApiShip extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Loads the application object.
	 *
	 * @var  \Joomla\CMS\Application\CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Enable on RadicalMart
	 *
	 * @var  bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public bool $radicalmart = true;

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
			'onRadicalMartPrepareMethodForm'       => 'onRadicalMartPrepareMethodForm',
			'onRadicalMartGetOrderShippingMethods' => 'onRadicalMartGetOrderShippingMethods',
			'onRadicalMartGetOrderTotal'           => 'onRadicalMartGetOrderTotal',
			'onRadicalMartGetOrderForm'            => 'onRadicalMartGetOrderForm',
			'onAjaxApiship'                        => 'onAjax',
		];
	}

	/**
	 * Set map key in method form.
	 *
	 * @param   Form   $form     Shipping method form object.
	 * @param   mixed  $data     The data expected for the form.
	 * @param   mixed  $tmpData  The  temporary form data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareMethodForm(Form $form, $data, $tmpData)
	{
		$registry = new Registry($data);
		$params   = new Registry($registry->get('params'));
		$form->setFieldAttribute('sender', 'map_key', $params->get('map_key'), 'params');
	}

	/**
	 * Prepare RadicalMart order shipping method data.
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
	                                                     array  $products, array $currency)
	{
		// Set disabled
		$method->disabled = false;
		if (empty($method->params->get('map_key')))
		{
			$method->disabled = true;
		}
		if (!$method->disabled)
		{
			foreach ($products as $product)
			{
				if ((int) $product->shipping->get('enable', 1) === 0
					|| empty($product->shipping->get('weight', '')))
				{
					$method->disabled = true;
					break;
				}
			}
		}

		// Clean secret param
		$method->params->set('token', '');

		// Prepare price
		$price = (!empty($formData['shipping']['price'])) ? $formData['shipping']['price'] : ['base' => 0];
		$code  = $currency['code'];
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
			$price['base']        = PriceHelper::clean($price['base'], $code);
			$price['base_string'] = PriceHelper::toString($price['base'], $code,);
			$price['base_seo']    = PriceHelper::toString($price['base'], $code, 'seo');
			$price['base_number'] = PriceHelper::toString($price['base'], $code, false);;
		}
		$price['final']        = $price['base'];
		$price['final_string'] = $price['base_string'];
		$price['final_seo']    = $price['base_seo'];
		$price['final_number'] = $price['base_number'];

		// Set order
		$method->order              = new \stdClass();
		$method->order->id          = $method->id;
		$method->order->title       = $method->title;
		$method->order->code        = $method->code;
		$method->order->description = $method->description;
		$method->order->price       = $price;

		// Set layout
		if ($context === 'com_radicalmart.checkout')
		{
			$method->layout = 'plugins.radicalmart_shipping.apiship.radicalmart.checkout';
		}
	}

	/**
	 * Prepare RadicalMart & RadicalMart Express order totals.
	 *
	 * @param   string             $context   Context selector string.
	 * @param   array              $total     Order total data.
	 * @param   array              $formData  Form data array.
	 * @param   array|null|false   $products  Shipping method data.
	 * @param   object|null|false  $shipping  Shipping method data.
	 * @param   object|null|false  $payment   Payment method data.
	 * @param   array              $currency  Order currency data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderTotal(string $context, array &$total, array $formData, $products, $shipping, $payment,
	                                           array  $currency)
	{
		if (!empty($shipping->order->price['base']))
		{
			$total['base'] += $shipping->order->price['base'];
		}

		if (!empty($shipping->order->price['final']))
		{
			$total['final'] += $shipping->order->price['final'];
		}
	}

	/**
	 * Prepare RadicalMart order form.
	 *
	 * @param   string             $context   Context selector string.
	 * @param   Form               $form      Order form object.
	 * @param   array              $formData  Form data array.
	 * @param   array|null|false   $products  Shipping method data.
	 * @param   object|null|false  $shipping  Shipping method data.
	 * @param   object|null|false  $payment   Payment method data.
	 * @param   array              $currency  Order currency data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderForm(string $context, Form $form, array $formData, $products, $shipping, $payment, array $currency)
	{
		$formName = $form->getName();
		if ($formName !== 'com_radicalmart.checkout' && $formName !== 'com_radicalmart.order')
		{
			return;
		}

		// Prepare fields
		$form->setFieldAttribute('sender', 'places',
			(new Registry($shipping->params->get('sender')))->toString(), 'shipping');

		$bindData = (!empty($formData)) ? $formData : $this->app->input->get('jform', [], 'array');
		$map_key  = $shipping->params->get('map_key');

		if (!empty($bindData['shipping']) && !empty($bindData['shipping']['delivery_type'])
			&& (int) $bindData['shipping']['delivery_type'] === 2)
		{
			$form->setFieldAttribute('point', 'shipping', $shipping->id, 'shipping');
			$form->setFieldAttribute('point', 'map_key', $map_key, 'shipping');
			$form->setFieldAttribute('point', 'context', $context, 'shipping');
			$form->removeField('recipient', 'shipping');
		}
		else
		{
			$form->setFieldAttribute('recipient', 'map_key', $map_key, 'shipping');
			$form->removeField('point', 'shipping');
		}

		// Load scripts
		$document       = $this->app->getDocument();
		$assets         = $document->getWebAssetManager();
		$assetsRegistry = $assets->getRegistry();
		$assetsRegistry->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
		$assets->useScript('plg_radicalmart_shipping_apiship.radicalmart');
		$document->addScriptOptions('radicalmart_shipping_apiship_radicalmart', [
			'shipping_id' => $shipping->id,
			'controller'  => Route::_('index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&format=json', false),
			'formType'    => ($formName === 'com_radicalmart.checkout') ? 'checkout' : 'order',
		]);
	}

	/**
	 * Method to ajax functions.
	 *
	 * @throws  \Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAjax(Event $event)
	{
		try
		{
			$action = $this->app->input->get('action');
			$method = $action;
			if (empty($action) || !method_exists($this, $method))
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
	protected function getPoints()
	{
		$context  = $this->app->input->get('context');
		$shipping = $this->app->input->getInt('shipping', 0);
		if (empty($shipping))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}

		$params = $this->getMethodParams($context, $shipping);
		if (!$params)
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_METHOD_NOT_FOUND'));
		}

		$provider = $params->get('provider');
		$limit    = 1000000;
		$url      = ($params->get('sandbox')) ? 'http://api.dev.apiship.ru/v1' : 'https://api.apiship.ru/v1';
		$url      .= '/lists/points?limit=' . $limit . '&filter=' . 'providerKey=' . $provider . ';availableOperation=[2,3]';

		$http = new Http();
		$http->setOption('transport.curl', array(CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
		$headers  = [
			'Content-Type'  => 'application/json',
			'authorization' => $params->get('token')
		];
		$response = $http->get($url, $headers);

		$body     = $response->body;
		$contents = (!empty($body)) ? new Registry($response->body) : false;

		if ($response->code !== 200)
		{
			$message = ($contents) ? $contents->get('message')
				: preg_replace('#^[0-9]*\s#', '', $response->headers['Status']);
			$code    = $response->code;

			throw new \Exception($message, $code);
		}

		if (empty($contents))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_GET_POINTS'));
		}
		$rows = $contents->get('rows', []);
		if (empty($rows))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_CANT_GET_POINTS'));
		}
		$result  = [
			'type'     => 'FeatureCollection',
			'features' => [],
		];
		$options = (new Registry($this->app->input->getString('marker', '')))->toArray();
		foreach ($contents->get('rows', []) as $row)
		{
			$result['features'][] = [
				'type'      => 'Feature',
				'id'        => $row->id,
				'title'     => $row->name,
				'latitude'  => $row->lat,
				'longitude' => $row->lng,
				'address'   => $row->address,
				'geometry'  => [
					'type'        => 'Point',
					'coordinates' => [$row->lat, $row->lng]
				],
				'options'   => $options,
			];
		}

		return $result;
	}

	/**
	 * Method to get payment method params.
	 *
	 * @param   string  $context  Context selector string.
	 * @param   int     $pk       Payment method id.
	 *
	 * @return false|Registry Method prams registry object on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getMethodParams(string $context, int $pk)
	{
		$params = false;
		if (strpos($context, 'com_radicalmart.') !== false)
		{
			$params = ParamsHelper::getShippingMethodsParams($pk);
		}

		if ($params && (int) $params->get('sandbox', 0) === 1)
		{
			$params->set('token', '9c3a7cfe13f402fc78b0dd6edad36993');
		}

		return $params;
	}
}