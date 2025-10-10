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

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Traits;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;
use Joomla\Plugin\RadicalMart\RetailCRM\Helper\LoggingHelper as RetailCRMHelperLoggingHelper;
use Joomla\Plugin\RadicalMart\RetailCRM\Helper\RetailCRMHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

trait RetailCRMTrait
{
	/**
	 * RetailCRM integration enabled.
	 *
	 * @var bool|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected ?bool $retailCRMEnabled = null;

	/**
	 * RetailCRM Api params cache.
	 *
	 * @var Registry|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected ?Registry $_retailCRMParams = null;

	/**
	 * RetailCRM Orders request data cache.
	 *
	 * @var array|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected ?array $_retailCRMOrders = null;

	/**
	 * Method to check RetailCRM integration is enabled.
	 *
	 * @return bool True if enabled, False if not.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function isRetailCRMEnabled(): bool
	{
		if ($this->retailCRMEnabled !== null)
		{
			return $this->retailCRMEnabled;
		}

		$this->retailCRMEnabled = PluginHelper::isEnabled('radicalmart', 'retailcrm');

		return $this->retailCRMEnabled;
	}

	/**
	 * Method to update retailCRM order data.
	 *
	 * @param   object  $order  RadicalMart order data.
	 *
	 * @throws \Exception
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function updateRetailCRMOrderShippingData(object $order): void
	{
		if (!$this->isRetailCRMEnabled())
		{
			return;
		}

		if (empty($order->shipping) || empty($order->shipping->id) || $order->shipping->plugin !== 'apiship')
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_SHIPPING_METHOD_NOT_FOUND'), 404);
		}

		try
		{
			$data = $this->getRetailCRMOrderData($order);
			if (!$data)
			{
				throw new \Exception(Text::_('COM_RADICALMART_ORDER_NOT_FOUND'), 404);
			}
			$crmParams = $this->getRetailCRMParams();
			$apikey    = $crmParams->get('apikey');
			$domain    = $crmParams->get('domain');
			$prefix    = $crmParams->get('prefix');

			$orderData             = $data['order'];
			$orderData['delivery'] = [
				'code' => RetailCRMHelper::getSymbolicCode($prefix, 'shipping_method', $order->shipping->id),
				'data' => [],
			];
			$this->setRetailCRMDeliveryData($order, $crmParams, $orderData);
			$data['order'] = $orderData;

			$result = RetailCRMHelper::editItem($apikey, $domain, 'orders', $data['order']['externalId'], $data);
			if ($result === false)
			{
				throw new \Exception(Text::_('PLG_RADICALMART_RETAILCRM_ERROR_ORDER_EXPORT'), 500);
			}

			RetailCRMHelperLoggingHelper::logInfo('order', 'Export Success', [
				'order_id'         => $order->id,
				'order_externalId' => $data['order']['externalId'],
			]);
		}
		catch (\Throwable $e)
		{
			RetailCRMHelperLoggingHelper::logError($e->getMessage(), [
				'entry'        => 'ApiShip',
				'method'       => 'updateRetailCRMOrderShippingData',
				'order_id'     => $order->id,
				'order_number' => $order->number,
			]);
		}
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
	public function changeRetailCRMOrderStatus(object $order, string $status_key): void
	{
		if (!$this->isRetailCRMEnabled())
		{
			return;
		}

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

		if (empty($params->get('api_orders_retailcrm_mapping')))
		{
			return;
		}

		$newStatus = false;
		$mapping   = ArrayHelper::fromObject($params->get('api_orders_retailcrm_mapping'));

		foreach ($mapping as $value)
		{
			if ($value['api_orders_mapping_api_status'] === $status_key)
			{
				$newStatus = $value['api_orders_mapping_retailcrm_status'];
				break;
			}
		}

		if (!$newStatus)
		{
			return;
		}

		try
		{
			$data = $this->getRetailCRMOrderData($order);
			if (!$data)
			{
				throw new \Exception(Text::_('COM_RADICALMART_ORDER_NOT_FOUND'), 404);
			}
			$data['order']['status'] = $newStatus;

			$crmParams = $this->getRetailCRMParams();
			$apikey    = $crmParams->get('apikey');
			$domain    = $crmParams->get('domain');

			$result = RetailCRMHelper::editItem($apikey, $domain, 'orders', $data['order']['externalId'], $data);

			if ($result === false)
			{
				throw new \Exception(Text::_('PLG_RADICALMART_RETAILCRM_ERROR_ORDER_CHANGE_STATUS'), 500);
			}

			RetailCRMHelperLoggingHelper::logInfo('order_change_status', 'Status changed', [
				'order_id'         => $order->id,
				'order_externalId' => $data['order']['externalId'],
				'status_code'      => $newStatus,
			]);
		}
		catch (\Throwable $e)
		{
			RetailCRMHelperLoggingHelper::logError($e->getMessage(), [
				'entry'        => 'ApiShip',
				'method'       => 'changeRetailCRMOrderStatus',
				'order_id'     => $order->id,
				'order_number' => $order->number,
			]);
		}
	}

	/**
	 * Prepare RetailCRM Shipping method data.
	 *
	 * @param   object    $method     Shipping method data.
	 * @param   Registry  $crmParams  RetailCRM params.
	 * @param   array     $data       RetailCRM delivery type data.
	 * @param   array     $services   RetailCRM delivery services data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartRetailCRMExportShippingMethodData(object $method, Registry $crmParams, array &$data, array &$services): void
	{
		foreach (self::getShippingMethodParams($method->id)->get('sender') as $sender)
		{
			$services[] = $this->getRetailCRMDeliveryServiceData($method, $crmParams, $sender);
		}
	}

	/**
	 * Prepare RetailCRM order data.
	 *
	 * @param   object    $order      Current order object.
	 * @param   Registry  $crmParams  RetailCRM params.
	 * @param   array     $data       RetailCRM order data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartRetailCRMExportOrderData(object $order, Registry $crmParams, array &$data): void
	{
		$this->setRetailCRMDeliveryData($order, $crmParams, $data);
	}

	/**
	 * Method to get RetailCRM delivery service data.
	 *
	 * @param   object    $method     Shipping method data.
	 * @param   Registry  $crmParams  RetailCRM params.
	 * @param   array     $sender     Sender params.
	 *
	 * @return array RetailCRM delivery service data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getRetailCRMDeliveryServiceData(object $method, Registry $crmParams, array $sender): array
	{
		$method_params = self::getShippingMethodParams($method->id);
		$delivery_type = $method_params->get('delivery_type');

		$prefix     = $crmParams->get('id_prefix');
		$methodCode = RetailCRMHelper::getSymbolicCode($prefix, 'shipping_method', $method->id);

		$suffix = implode('_', [
			$sender['provider'],
			'pt' . $sender['pickup_type'],
			'dt' . $delivery_type,
		]);

		$name = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $sender['provider']) . ': '
			. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PICKUP_TYPE_' . $sender['pickup_type']) . ' > '
			. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE_' . $delivery_type);

		return [
			'code'         => RetailCRMHelper::getSymbolicCode($prefix, 'shipping_method', $method->id, $suffix),
			'name'         => $name,
			'active'       => true,
			'deliveryType' => $methodCode,
		];
	}

	/**
	 * Method to set RetailCRM delivery order data.
	 *
	 * @param   object    $order      Current order object.
	 * @param   Registry  $crmParams  RetailCRM params.
	 * @param   array     $data       RetailCRM order data.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function setRetailCRMDeliveryData(object $order, Registry $crmParams, array &$data): void
	{
		$method_id = $order->shipping->id;
		$shipping  = $order->formData['shipping'];

		$params        = self::getShippingMethodParams($method_id);
		$delivery_type = (int) $params->get('delivery_type', 2);
		$provider      = ($delivery_type === 2) ? $shipping['point']['providerKey'] : $shipping['address']['provider'];

		$senders      = $params->get('sender', []);
		$senderParams = (isset($senders[$provider])) ? $senders[$provider] : false;
		$pickup_type  = (isset($senderParams['pickup_type'])) ? (int) $senderParams['pickup_type'] : 1;

		if (!isset($data['delivery']['address']))
		{
			$data['delivery']['address'] = [];
		}

		if (!isset($data['delivery']['data']))
		{
			$data['delivery']['data'] = [];
		}

		$data['delivery']['service'] = $this->getRetailCRMDeliveryServiceData($order->shipping, $crmParams, $senderParams);

		$data['delivery']['data']['tariff']     = $shipping['tariff']['id'];
		$data['delivery']['data']['tariffName'] = $shipping['tariff']['name'];

		$data['delivery']['address']['notes'] = [];

		$data['delivery']['address']['notes'][] =
			Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER') . ': '
			. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider);

		$data['delivery']['address']['notes'][] =
			Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_TARIFF') . ': '
			. $shipping['tariff']['name'];
		$data['delivery']['address']['notes'][] = '';

		if (!empty($shipping['api_order']))
		{
			if (!empty($shipping['api_order']['id']))
			{
				$data['delivery']['data']['externalId'] = $shipping['api_order']['id'];

				$data['delivery']['address']['notes'][] =
					Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_ID') . ': '
					. $shipping['api_order']['id'];
			}

			if (!empty($shipping['api_order']['status']))
			{
				$data['delivery']['address']['notes'][] =
					Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_STATUS') . ': '
					. $shipping['api_order']['status'];
			}

			$data['delivery']['address']['notes'][] = '';
		}

		$data['delivery']['address']['notes'][] =
			Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PICKUP_TYPE') . ': '
			. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PICKUP_TYPE_' . $senderParams['pickup_type']);

		if ($pickup_type === 1)
		{
			$data['delivery']['address']['notes'][] =
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PICKUP_ADDRESS') . ': '
				. $senderParams['address'];
		}
		else
		{
			$data['delivery']['address']['notes'][] =
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PICKUP_POINT') . ': '
				. $senderParams['point'];

			$data['delivery']['data']['shipmentpointId'] = $senderParams['point'];
		}
		$data['delivery']['address']['notes'][] = '';

		$data['delivery']['address']['notes'][] =
			Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE') . ' : '
			. Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE_' . $delivery_type);

		if ($delivery_type === 2)
		{
			$data['delivery']['address']['name']       = $shipping['point']['display'];
			$data['delivery']['address']['countryIso'] = $shipping['point']['countryCode'];
			$data['delivery']['address']['text']       = $shipping['point']['countryCode'] . ' ' . $shipping['point']['address'];

			$data['delivery']['data']['pickuppointId']                  = $shipping['point']['id'];
			$data['delivery']['data']['pickuppointAddress']             = $shipping['point']['address'];
			$data['delivery']['data']['pickuppointName']                = $shipping['point']['title'];
			$data['delivery']['data']['pickuppointCoordinateLatitude']  = $shipping['point']['latitude'];
			$data['delivery']['data']['pickuppointCoordinateLongitude'] = $shipping['point']['longitude'];

			$data['delivery']['address']['notes'][] =
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINT') . ': ' . $shipping['point']['address'];
		}
		else
		{
			foreach ([
				         'index'    => 'zip',
				         'country'  => 'country',
				         'region'   => 'region',
				         'city'     => 'city',
				         'street'   => 'street',
				         'building' => 'house',
				         'flat'     => 'apartment',
				         'floor'    => 'floor',
				         'block'    => 'entrance',
				         'housing'  => 'building',
				         'name'     => 'display',
				         'text'     => 'string'

			         ] as $dest => $src
			)
			{
				if (empty($shipping['address'][$src]))
				{
					continue;
				}
				$data['delivery']['address'][$dest] = $shipping['address'][$src];
			}
		}
		$data['delivery']['address']['notes'][] = '';

		if (!empty($shipping['tracking_number']))
		{
			$data['delivery']['address']['notes'][] =
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_TRACKING_NUMBER') . ': ' . $shipping['tracking_number'];
		}

		if (!empty($shipping['tracking_url']))
		{
			$data['delivery']['address']['notes'][] =
				Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SHIPPING_TRACKING_URL') . ': ' . $shipping['tracking_url'];
		}

		$data['delivery']['address']['notes'] = implode(PHP_EOL, $data['delivery']['address']['notes']);
	}

	/**
	 * Method to get RetailCRM order request data.
	 *
	 * @param   object  $order  RadicalMart order object.
	 *
	 * @return array|bool Request order data on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function getRetailCRMOrderData(object $order): array|bool
	{
		$order_id = $order->id;

		if ($this->_retailCRMOrders === null)
		{
			$this->_retailCRMOrders = [];
		}

		if (isset($this->_retailCRMOrders[$order_id]))
		{
			return $this->_retailCRMOrders[$order_id];
		}

		$params    = $this->getRetailCRMParams();
		$apikey    = $params->get('apikey');
		$domain    = $params->get('domain');
		$prefix    = $params->get('id_prefix');
		$shop_code = $params->get('shop_code');
		try
		{
			$externalId = RetailCRMHelper::getExternalId($prefix, 'order', $order->id);
			$exist      = RetailCRMHelper::getItem($apikey, $domain, 'orders', $externalId);
			if (empty($exist))
			{
				throw new \Exception('Order not found', 404);
			}

			$data = [
				'site'  => $shop_code,
				'order' => [
					'number'     => $order->number,
					'externalId' => $externalId,
				],
			];
		}
		catch (\Throwable)
		{
			$data = false;
		}

		$this->_retailCRMOrders[$order_id] = $data;

		return $data;
	}

	/**
	 * Method to get Api params.
	 *
	 * @return Registry Api params.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getRetailCRMParams(): Registry
	{
		if ($this->_retailCRMParams !== null)
		{
			return $this->_retailCRMParams;
		}
		$params = ParamsHelper::getComponentParams();

		$this->_retailCRMParams = new Registry([
			'apikey'    => $params->get('retailcrm_apikey', ''),
			'domain'    => trim($params->get('retailcrm_domain', '')),
			'id_prefix' => $params->get('retailcrm_id_prefix', 'rm'),
			'shop_id'   => (int) $params->get('retailcrm_shop_id', 0),
			'shop_code' => trim($params->get('retailcrm_shop_code', '')),
		]);

		return $this->_retailCRMParams;
	}
}