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

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Component\RadicalMart\Administrator\Traits\StaticDatabaseTrait;
use Joomla\Registry\Registry;

class AddressHelper
{
	use StaticDatabaseTrait;

	/**
	 * Customers addresses data cache.
	 *
	 * @var array|null
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static ?array $_customerAddresses = null;

	/**
	 * Method to convert address data to string.
	 *
	 * @param   array  $data  Address data
	 *
	 * @return string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function toString(array $data = []): string
	{
		$address = [];
		if (!empty($data['zip']))
		{
			$address[] = $data['zip'];
		}
		if (!empty($data['country']))
		{
			$address[] = $data['country'];
		}
		if (!empty($data['region']))
		{
			$address[] = $data['region'];
		}
		if (!empty($data['city']))
		{
			$address[] = $data['city'];
		}

		$mb = function_exists('mb_strtolower');
		foreach (['street', 'house', 'building', 'entrance', 'floor', 'apartment'] as $key)
		{
			if (!empty($data[$key]))
			{
				$title     = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $key);
				$title     = ($mb) ? mb_strtolower($title) : strtolower($title);
				$address[] = $title . ' ' . $data[$key];
			}
		}

		return (!empty($address)) ? implode(', ', $address) : '';
	}

	/**
	 * Method to get saved customer addresses.
	 *
	 * @param   int  $user_id    Customer id.
	 * @param   int  $method_id  Method id.
	 *
	 * @return array Customers addresses data.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getCustomerAddresses(int $user_id = 0, int $method_id = 0): array
	{
		if (empty($user_id) || empty($method_id))
		{
			return [];
		}

		if (self::$_customerAddresses == null)
		{
			self::$_customerAddresses = [];
		}

		$key = $user_id . '_' . $method_id;
		if (isset(self::$_customerAddresses[$key]))
		{
			return self::$_customerAddresses[$key];
		}

		$db       = self::getDatabase();
		$query    = $db->getQuery(true)
			->select(['id', 'shipping'])
			->from($db->quoteName('#__radicalmart_customers'))
			->where($db->quoteName('id') . ' = :user_id')
			->bind(':user_id', $user_id);
		$customer = $db->setQuery($query, 0, 1)->loadObject();
		if (empty($customer) || empty($customer->id) || empty($customer->shipping))
		{
			self::$_customerAddresses[$key] = [];

			return [];
		}

		$method_key         = 'shipping_method_' . $method_id;
		$customer->shipping = (new Registry($customer->shipping))->toArray();
		if (!isset($customer->shipping[$method_key]))
		{
			self::$_customerAddresses[$key] = [];

			return [];
		}

		if (empty($customer->shipping[$method_key]['addresses']))
		{
			self::$_customerAddresses[$key] = [];

			return [];
		}

		self::$_customerAddresses[$key] = $customer->shipping[$method_key]['addresses'];

		return self::$_customerAddresses[$key];
	}

	/**
	 * Method to reset ram cache.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function reset(): void
	{
		self::$_customerAddresses = null;
	}
}