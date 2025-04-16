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

use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\Http\Response;
use Joomla\Registry\Registry;

class ApiShipHelper
{
	/**
	 * All Providers keys.
	 *
	 * @var string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static array $providers = [
		'1-mig',
		'a2',
		'accordpost',
		'axilog',
		'b2cpl',
		'boxberry',
		'bxb',
		'cainiao',
		'cdek',
		'cityex',
		'courierist',
		'cse',
		'dalli',
		'dellin',
		'dostavista',
		'dpd',
		'e-kit',
		'e-logs',
		'ebulky',
		'ecomlog',
		'exmail',
		'halva',
		'integral',
		'kazpost',
		'kgt',
		'logsis',
		'lpost',
		'magnit',
		'major',
		'pecom',
		'podorojnik',
		'pony',
		'rayber',
		'redexpress',
		'rudostavka',
		'runcrm',
		'rupost',
		'strizh',
		'td',
		'vozovoz',
		'x5',
		'yataxi'
	];

	/**
	 * Method to get list from api.
	 *
	 * @param   string  $token    Api token.
	 * @param   string  $list     List selector.
	 * @param   array   $filter   Filter params.
	 * @param   bool    $sandbox  Is sandbox mode.
	 *
	 * @throws \Exception
	 * @return array Pickup points data array on success, throws on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getList(string $token, string $list, array $filter = [], bool $sandbox = false): array
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}
		if (empty($list))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_LIST_NOT_FOUND'));
		}

		$limit  = 500;
		$offset = 0;
		$result = [];
		$filter = self::convertFilterConditionsToString($filter);
		while (true)
		{
			$url = ($sandbox) ? 'http://api.dev.apiship.ru/v1' : 'https://api.apiship.ru/v1';
			$url .= '/lists/' . $list . '?limit=' . $limit . '&offset=' . $offset;
			if (!empty($filter))
			{
				$url .= '&filter=' . $filter;
			}
			$request = self::sendGetRequest($token, $url);
			$rows    = ($request->exists('rows')) ? $request->get('rows', []) : $request->toArray();
			$count   = count($rows);
			if ($count === 0)
			{
				break;
			}

			foreach ($rows as $row)
			{
				$result[] = (array) $row;
			}

			$offset += $limit;
			if ($count < $limit)
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * Method to get pickup points data.
	 *
	 * @param   string  $token      Api token.
	 * @param   array   $providers  Delivery providers.
	 * @param   array   $operation  Point operation.
	 * @param   bool    $sandbox    Is sandbox mode.
	 *
	 * @throws \Exception
	 *
	 * @return array Pickup points data array on success, throws on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getPoints(string $token, array $providers = [], array $operation = [], bool $sandbox = false): array
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$limit  = 500;
		$offset = 0;
		$filter = self::convertFilterConditionsToString([
			[
				'key'      => 'providerKey',
				'operator' => '=',
				'value'    => $providers
			],
			[
				'key'      => 'availableOperation',
				'operator' => '=',
				'value'    => $operation
			],
		]);

		$result = [];
		while (true)
		{
			$url     = ($sandbox) ? 'http://api.dev.apiship.ru/v1' : 'https://api.apiship.ru/v1';
			$url     .= '/lists/points?limit=' . $limit . '&offset=' . $offset . '&filter=' . $filter;
			$request = self::sendGetRequest($token, $url);
			$rows    = $request->get('rows', []);
			$count   = count($rows);
			if ($count === 0)
			{
				break;
			}

			foreach ($rows as $row)
			{
				$result[] = (array) $row;
			}

			$offset += $limit;
			if ($count < $limit)
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * Method to calculate delivery costs.
	 *
	 * @param   string  $token    Api token.
	 * @param   array   $data     Request data.
	 * @param   bool    $sandbox  Is sandbox mode.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Calculate result Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function calculator(string $token, array $data, bool $sandbox = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = ($sandbox) ? 'http://api.dev.apiship.ru/v1' : 'https://api.apiship.ru/v1';
		$url .= '/calculator';

		return self::sendPostRequest($token, $url, $data);
	}

	/**
	 * Method to send POST api request.
	 *
	 * @param   string  $token  Request Token.
	 * @param   string  $url    Request url.
	 * @param   array   $data   Request Data.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function sendPostRequest(string $token, string $url, array $data = []): Registry
	{
		$http = new Http();
		$http->setOption('transport.curl', [
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		]);
		$headers = [
			'Content-Type'  => 'application/json',
			'authorization' => $token
		];
		$data  = (new Registry($data))->toString('json', ['bitmask' => JSON_UNESCAPED_UNICODE]);
		$debug = "curl --location --request POST '" . $url . "' \ "
			. PHP_EOL . "--header 'authorization: " . $token . "' \ "
			. PHP_EOL . "--header 'Content-Type: application/json' \ "
			. PHP_EOL . "--data-raw '" . $data . "'";

		return self::parseResponse($http->post($url, $data, $headers));
	}

	/**
	 * Method to send GET api request.
	 *
	 * @param   string  $token  Request Token.
	 * @param   string  $url    Request url.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function sendGetRequest(string $token, string $url): Registry
	{
		$http = new Http();
		$http->setOption('transport.curl', [
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		]);
		$headers = [
			'Content-Type'  => 'application/json',
			'authorization' => $token
		];

		return self::parseResponse($http->get($url, $headers));
	}

	/**
	 * Method to parse api response request.
	 *
	 * @param   Response  $response  Source Response object.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request response as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function parseResponse(Response $response): Registry
	{
		$body = $response->body;

		$contents = (!empty($body)) ? new Registry($response->body) : false;
		if ($response->code !== 200)
		{
			$message = ($contents) ? $contents->get('message')
				: preg_replace('#^[0-9]*\s#', '', $response->headers['Status']);

			if ($contents && !empty($contents->get('errors')) && JDEBUG)
			{
				foreach ($contents->get('errors') as $error)
				{
					$message .= PHP_EOL . json_encode($error, JSON_UNESCAPED_UNICODE);
				}
			}

			$code = $response->code;

			throw new \Exception($message, $code);
		}

		if (empty($contents))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_REQUEST'));
		}

		return $contents;
	}

	/**
	 * Method to convert filter conditions to string.
	 *
	 * @param   array  $conditions  Filter conditions array.
	 *
	 * @return string Filter conditions string.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function convertFilterConditionsToString(array $conditions): string
	{
		$result = [];
		foreach ($conditions as $condition)
		{
			if (empty($condition['value']))
			{
				continue;
			}

			$filter   = $condition['key'] . $condition['operator'];
			$filter   .= (is_array($condition['value'])) ? '[' . implode(',', $condition['value']) . ']'
				: $condition['value'];
			$result[] = $filter;
		}

		return implode(';', $result);
	}
}