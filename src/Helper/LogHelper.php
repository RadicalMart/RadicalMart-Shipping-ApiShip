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

use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

class LogHelper
{
	/**
	 * Logger category prefix.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	protected static string $loggerCategory = 'plg_radicalmart_shipping_apiship';

	/**
	 * Logger instance.
	 *
	 * @var array|null
	 *
	 * @since 1.0.0
	 */
	protected static ?array $_logger = null;

	/**
	 * Method to add a log entry.
	 *
	 * @param   string  $log    Log key name.
	 * @param   mixed   $entry  Log entry data.
	 * @param   bool    $error  Is error log.
	 *
	 * @since 1.0.0
	 */
	public static function addLog(string $log, array|string $entry, bool $error = false): void
	{
		$category = self::$loggerCategory . '.' . $log;
		if (self::$_logger === null)
		{
			self::$_logger = [];
		}
		if (!isset(self::$_logger[$category]))
		{
			Log::addLogger([
				'text_file'         => $category . '.php',
				'text_entry_format' => "{DATETIME}\t{CLIENTIP}\t{MESSAGE}\t{PRIORITY}"],
				Log::ALL,
				[$category]
			);
		}

		if (!is_string($entry))
		{
			$entry = (new Registry($entry))->toString();
		}

		Log::add($entry, ($error) ? Log::ERROR : Log::INFO, $category);
	}
}