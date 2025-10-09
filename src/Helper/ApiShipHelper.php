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
	 * @var string]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static string $apiUrl = 'https://api.apiship.ru/v1';

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
	 * All Statuses keys.
	 *
	 * @var string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static array $statuses = [
		'uploading',
		'uploaded',
		'onPointIn',
		'onWay',
		'onPointOut',
		'readyForRecipient',
		'delivering',
		'delivered',
		'deliveryCanceled',
		'returning',
		'returned',
		'returnedFromDelivery',
		'partialReturn',
		'returnReady',
		'uploadingError',
		'lost',
		'problem',
		'unknown',
		'notApplicable',
	];

	/**
	 * Method to get list from api.
	 *
	 * @param   string  $token   Api token.
	 * @param   string  $list    List selector.
	 * @param   array   $filter  Filter params.
	 *
	 * @throws \Exception
	 *
	 * @return array Pickup points data array on success, throws on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getList(string $token, string $list, array $filter = []): array
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
			$url = self::$apiUrl . '/lists/' . $list . '?limit=' . $limit . '&offset=' . $offset;
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
	 *
	 * @throws \Exception
	 *
	 * @return array Pickup points data array on success, throws on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getPoints(string $token, array $providers = [], array $operation = [],
	                                 int    $offset = 0, int $limit = 500): array
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

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

		$url     = self::$apiUrl . '/lists/points?limit=' . $limit . '&offset=' . $offset . '&filter=' . $filter;
		$request = self::sendGetRequest($token, $url);

		return $request->get('rows', []);
	}

	/**
	 * Method to get pickup points count.
	 *
	 * @param   string  $token      Api token.
	 * @param   array   $providers  Delivery providers.
	 * @param   array   $operation  Point operation.
	 *
	 * @throws \Exception
	 *
	 * @return int Pickup points count.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getPointsTotal(string $token, array $providers = [], array $operation = []): int
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

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

		$url     = self::$apiUrl . '/lists/points?limit=1&offset=0&filter=' . $filter;
		$request = self::sendGetRequest($token, $url);
		$meta    = $request->get('meta', new \stdClass());

		return (!empty($meta->total)) ? (int) $meta->total : 0;
	}

	/**
	 * Method to calculate delivery costs.
	 *
	 * @param   string       $token  Api token.
	 * @param   array        $data   Request data.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Calculate result Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function calculator(string $token, array $data, string|bool $log = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/calculator';

		return self::sendPostRequest($token, $url, $data, $log);
	}

	/**
	 * Method to create api order.
	 *
	 * @param   string       $token  Api token.
	 * @param   array        $data   Request data.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry New order Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function createOrder(string $token, array $data, string|bool $log = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/orders';

		return self::sendPostRequest($token, $url, $data, $log);
	}

	/**
	 * Method to get api order status.
	 *
	 * @param   string       $token  Api token.
	 * @param   array        $data   Request data.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Status data Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getOrderStatus(string $token, array $data, string|bool $log = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/orders';
		if (!empty($data['order_id']))
		{
			$url .= '/' . $data['order_id'] . '/status';
		}
		elseif (!empty($data['client_number']))
		{
			$url .= '/status?clientNumber=' . $data['client_number'];
		}
		else
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_EMPTY_ORDER_REQUEST'));
		}

		return self::sendGetRequest($token, $url, $log);
	}

	/**
	 * Method to get api order status.
	 *
	 * @param   string       $token     Api token.
	 * @param   string       $order_id  Api order id.
	 * @param   string|bool  $log       Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Status data Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function cancelOrder(string $token, string $order_id = '0000', string|bool $log = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/orders/' . $order_id . '/cancel';

		return self::sendGetRequest($token, $url, $log);
	}

	/**
	 * Method to get webhooks list from api.
	 *
	 * @param   string  $token  Api token.
	 *
	 * @throws \Exception
	 *
	 * @return array Webhooks data array on success, throws on failure.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public static function getWebhooks(string $token): array
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/webhooks';

		$request = self::sendGetRequest($token, $url);

		return $request->toArray();
	}

	/**
	 * Method to create api webhook.
	 *
	 * @param   string       $token  Api token.
	 * @param   array        $data   Request data.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Created webhook data Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function createWebhook(string $token, array $data, string|bool $log = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/webhooks';

		return self::sendPostRequest($token, $url, $data, $log);
	}

	/**
	 * Method to delete api webhook.
	 *
	 * @param   string       $token  Api token.
	 * @param   string       $uuid   Webhook uuid.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Created webhook data Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function deleteWebhook(string $token, string $uuid = '0000', string|bool $log = false): Registry
	{
		if (empty($token))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_TOKEN'));
		}

		$url = self::$apiUrl . '/webhooks/' . $uuid;

		return self::sendDeleteRequest($token, $url, $log);
	}

	/**
	 * Method to send POST api request.
	 *
	 * @param   string       $token  Request Token.
	 * @param   string       $url    Request url.
	 * @param   array        $data   Request Data.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function sendPostRequest(string $token, string $url, array $data = [], string|bool $log = false): Registry
	{
		$http    = new Http(['transport.curl' => [
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		]]);
		$headers = [
			'Content-Type'  => 'application/json',
			'authorization' => $token
		];

		$requestData = (new Registry($data))->toString('json', ['bitmask' => JSON_UNESCAPED_UNICODE]);
		$entry       = [
			'url'         => $url,
			'data'        => $data,
			'headers'     => $headers,
			'requestData' => $requestData,
			'debug'       => "curl --location --request POST '" . $url . "' \ "
				. PHP_EOL . "--header 'authorization: " . $token . "' \ "
				. PHP_EOL . "--header 'Content-Type: application/json' \ "
				. PHP_EOL . "--data-raw '" . $requestData . "'"
		];
		try
		{
			$result            = self::parseResponse($http->post($url, $requestData, $headers));
			$entry['response'] = $result;
			if ($log)
			{
				LogHelper::addLog($log, $entry);
			}

			return $result;
		}
		catch (\Exception $e)
		{
			$entry['error'] = $e->getMessage();
			if (!$log)
			{
				$log = 'error';
			}
			LogHelper::addLog($log, $entry);

			throw $e;
		}
	}

	/**
	 * Method to send GET api request.
	 *
	 * @param   string       $token  Request Token.
	 * @param   string       $url    Request url.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function sendGetRequest(string $token, string $url, string|bool $log = false): Registry
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

		$entry = [
			'url'     => $url,
			'headers' => $headers,
		];

		try
		{
			$result            = self::parseResponse($http->get($url, $headers));
			$entry['response'] = $result;
			if ($log)
			{
				LogHelper::addLog($log, $entry);
			}

			return $result;
		}
		catch (\Exception $e)
		{
			$entry['error'] = $e->getMessage();
			if (!$log)
			{
				$log = 'error';
			}
			LogHelper::addLog($log, $entry);

			throw $e;
		}
	}

	/**
	 * Method to send DELETE api request.
	 *
	 * @param   string       $token  Request Token.
	 * @param   string       $url    Request url.
	 * @param   string|bool  $log    Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function sendDeleteRequest(string $token, string $url, string|bool $log = false): Registry
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

		$entry = [
			'url'     => $url,
			'headers' => $headers,
		];

		try
		{
			$result            = self::parseResponse($http->delete($url, $headers));
			$entry['response'] = $result;
			if ($log)
			{
				LogHelper::addLog($log, $entry);
			}

			return $result;
		}
		catch (\Exception $e)
		{
			$entry['error'] = $e->getMessage();
			if (!$log)
			{
				$log = 'error';
			}
			LogHelper::addLog($log, $entry);

			throw $e;
		}
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

			if ($contents && !empty($contents->get('errors')))
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