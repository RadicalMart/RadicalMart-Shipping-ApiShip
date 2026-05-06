<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.2
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\Http\Response;
use Joomla\Registry\Registry;

class DaDataHelper
{
	/**
	 * Method to clean address data in DaData api.
	 *
	 * @param   string       $token    DaData api Token.
	 * @param   string       $secret   DaData api Secret.
	 * @param   array        $address  Source data array.
	 * @param   string|bool  $log      Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return array Clean address Values.
	 *
	 * @since 1.0.0
	 */
	public static function cleanAddress(string $token, string $secret, array $address = [], string|bool $log = false): array
	{
		$url = 'https://dadata.ru/api/v2/clean/address';

		$addressString = AddressHelper::toString($address);
		try
		{
			$response = self::sendPostRequest($token, $secret, $url, [$addressString], $log);
			$array    = $response->toArray();
			$result   = (!empty($array[0])) ? $array[0] : [];
		}
		catch (\Throwable)
		{
			$result = [];
		}

		if (!empty($result))
		{
			if (empty($result['city']) && !empty($result['region'])
				&& in_array($result['region'], ['Москва', 'Санкт-Петербург', 'Севастополь', 'Байконур']))
			{
				$result['city'] = $result['region'];
			}
		}

		return $result;
	}

	/**
	 * Method to send POST api request.
	 *
	 * @param   string       $token   Request Token.
	 * @param   string       $secret  Request Secret.
	 * @param   string       $url     Request url.
	 * @param   array        $data    Request Data.
	 * @param   string|bool  $log     Log name if enabled, False if not.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since 1.0.0
	 */
	protected static function sendPostRequest(string      $token, string $secret, string $url, array $data = [],
	                                          string|bool $log = false): Registry
	{
		if (empty($token) || empty($secret))
		{
			throw new \Exception(Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_DADATA_ACCESS'),
				403);
		}

		// Send request
		$http = new Http();
		$http->setOption('transport.curl', [
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		]);
		$headers = [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Token ' . $token,
			'X-Secret'      => $secret,
		];

		$requestData = json_encode($data);
		$entry       = [
			'url'         => $url,
			'data'        => $data,
			'headers'     => $headers,
			'requestData' => $requestData,
			'debug'       => "curl --location --request POST '" . $url . "' \ "
				. PHP_EOL . "--header 'authorization: " . $token . "' \ "
				. PHP_EOL . "--header 'X-Secret: " . $secret . "' \ "
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

			throw new $e;
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
	 * @since 1.0.0
	 */
	protected static function parseResponse(Response $response): Registry
	{
		$body = $response->body;

		if (empty($body))
		{
			$message = preg_replace('#^[0-9]*\s#', '', $response->headers['Status']);
			throw new \Exception($message, 500);
		}

		$contents = new Registry($body);
		if ((int) $contents->get('status', 200) !== 200)
		{
			throw new \Exception($contents->get('detail', $contents->get('error', 'Request Error'))
				, $contents->get('status', 500));
		}

		return $contents;
	}
}