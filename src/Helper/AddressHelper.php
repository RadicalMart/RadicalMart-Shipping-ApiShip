<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
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
	 * Addresses fields keys.
	 *
	 * @var array|null
	 *
	 * @since 1.0.0
	 */
	public static ?array $addressKeys = [
		'zip',
		'country',
		'region',
		'city',
		'street',
		'house',
		'building',
		'entrance',
		'floor',
		'apartment'
	];

	/**
	 * Customers addresses data cache.
	 *
	 * @var array|null
	 *
	 * @since 1.0.0
	 */
	protected static ?array $_customerAddresses = null;

	/**
	 * Method to convert address data to string.
	 *
	 * @param   array  $data  Address data
	 *
	 * @return string
	 *
	 * @since 1.0.0
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
		if (!empty($data['street']))
		{
			$address[] = $data['street'];
		}
		$address = array_unique($address);

		$mb = function_exists('mb_strtolower');
		foreach (['house', 'building', 'entrance', 'floor', 'apartment'] as $key)
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
	 * Method to convert address data to string.
	 *
	 * @param   array  $data  Address data
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function toDisplay(array $data = []): string
	{
		$string   = self::toString($data);
		$provider = (!empty($data['provider']))
			? Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $data['provider']) : '';

		$result = [];
		if (!empty($provider))
		{
			$result[] = $provider;
		}
		if (!empty($string))
		{
			$result[] = $string;
		}

		return implode(' - ', $result);
	}

	/**
	 * Method to get saved customer addresses.
	 *
	 * @param   int  $user_id    Customer id.
	 * @param   int  $method_id  Method id.
	 *
	 * @return array Customers addresses data.
	 *
	 * @since 1.0.0
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
		$query    = $db->createQuery()
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
	 * Method to generate hash from address data.
	 *
	 * @param   array  $data  Address data array.
	 *
	 * @return string Address hash.
	 *
	 * @since 1.0.0
	 */
	public static function getAddressHash(array $data = []): string
	{
		$hash = [];
		foreach (self::$addressKeys as $key)
		{
			if (!empty($data[$key]))
			{
				$hash[$key] = $data[$key];
			}
		}

		return md5(serialize($hash));
	}

	/**
	 * Method to reset ram cache.
	 *
	 * @since 1.0.0
	 */
	public static function reset(): void
	{
		self::$_customerAddresses = null;
	}
}