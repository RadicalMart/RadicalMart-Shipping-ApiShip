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

class DaDataHelper
{
	/**
	 * Method to clean address data in DaData api.
	 *
	 * @param   string  $token    DaData api Token.
	 * @param   string  $secret   DaData api Secret.
	 * @param   array   $address  Source data array.
	 *
	 * @throws \Exception
	 *
	 * @return array Clean address Values.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function cleanAddress(string $token, string $secret, array $address = []): array
	{
		$url = 'https://dadata.ru/api/v2/clean/address';


		$addressString = AddressHelper::toString($address);
		try
		{
			$response      = self::sendPostRequest($token, $secret, $url, [$addressString]);
		}
		catch (\Throwable $e) {
			echo '<pre>', print_r($e->getMessage(), true), '</pre>';
			exit('2312');
		}

		$array  = $response->toArray();
		$result = (!empty($array[0])) ? $array[0] : [];
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
	 * @param   string  $token   Request Token.
	 * @param   string  $secret  Request Secret.
	 * @param   string  $url     Request url.
	 * @param   array   $data    Request Data.
	 *
	 * @throws \Exception
	 *
	 * @return Registry Request result as Registry object.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected static function sendPostRequest(string $token, string $secret, string $url, array $data = []): Registry
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

		$data = json_encode($data);

		return self::parseResponse($http->post($url, $data, $headers));
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