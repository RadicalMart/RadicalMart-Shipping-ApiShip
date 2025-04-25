<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Helper;

use Joomla\CMS\Date\Date;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

class CacheHelper
{
	/**
	 * Plugin cache folder.
	 *
	 * @var string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static string $cacheFolder = JPATH_CACHE . '/plg_radicalmart_shipping_apiship';

	/**
	 * Method to get cache filename.
	 *
	 * @param   int          $method_id  Shipping method id.
	 * @param   string       $selector   Cache selector.
	 * @param   string|null  $hash       Cache hash.
	 *
	 * @return string JSON Cache filename.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getCacheFilename(int $method_id, string $selector, ?string $hash = null): string
	{
		$filename = $method_id . '_' . $selector;
		if (!empty($hash))
		{
			$filename .= '_' . $hash;
		}

		return $filename . '.json';
	}

	/**
	 * Method to get cache data.
	 *
	 * @param   int          $method_id  Shipping method id.
	 * @param   string       $selector   Cache selector.
	 * @param   string|null  $hash       Cache hash.
	 * @param   string       $timeout    Cache timeout string.
	 * @param   bool         $delete     Need delete old cache.
	 *
	 * @return Registry|bool Cache file Registry data on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getCache(int    $method_id, string $selector, ?string $hash = null,
	                                string $timeout = '1 day', bool $delete = true): Registry|bool
	{
		$filename = self::getCacheFilename($method_id, $selector, $hash);
		$path     = Path::clean(self::$cacheFolder . '/' . $filename);
		if (!is_file($path))
		{
			return false;
		}

		$file_time    = filemtime($path);
		$timeout_time = (new Date('-' . $timeout))->toUnix();
		if ($file_time <= $timeout_time)
		{
			if ($delete)
			{
				self::deleteCache($method_id, $selector, $hash);
			}

			return false;
		}


		$contents = file_get_contents($path);
		if (empty($contents))
		{
			return false;
		}

		return new Registry($contents);
	}

	/**
	 * Method to delete cache file.
	 *
	 * @param   int          $method_id  Shipping method id.
	 * @param   string       $selector   Cache selector.
	 * @param   string|null  $hash       Cache hash.
	 *
	 * @return bool
	 *
	 * @since __DEPLOY_VERSION__ True if file not exist or file deleter, False on failure.
	 */
	public static function deleteCache(int $method_id, string $selector, ?string $hash = null): bool
	{
		$filename = self::getCacheFilename($method_id, $selector, $hash);
		$path     = Path::clean(self::$cacheFolder . '/' . $filename);
		if (is_file($path))
		{
			return File::delete($path);
		}

		return true;
	}

	/**
	 * Method to delete old cache files.
	 *
	 * @param   int     $method_id  Shipping method id.
	 * @param   string  $selector   Cache selector.
	 * @param   string  $timeout    Cache timeout string.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function deleteOldCache(int $method_id, string $selector, string $timeout = '1 day'): void
	{
		$folder = Path::clean(self::$cacheFolder);
		if (!is_dir($folder))
		{
			return;
		}

		$files = Folder::files($folder, $method_id . '_' . $selector, false, true);
		if (count($files) === 0)
		{
			return;
		}

		$timeout_time = (new Date('-' . $timeout))->toUnix();
		foreach ($files as $path)
		{
			$file_time = filemtime($path);
			if ($file_time <= $timeout_time)
			{
				File::delete($path);
			}
		}
	}

	/**
	 * Method to save cache data.
	 *
	 * @param   int          $method_id  Shipping method id.
	 * @param   string       $selector   Cache selector.
	 * @param   string|null  $hash       Cache hash.
	 * @param   mixed        $data       Save cache data.
	 *
	 * @return bool True on success, False on failure.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function saveCache(int $method_id, string $selector, ?string $hash = null, mixed $data = []): bool
	{
		if (!$data instanceof Registry)
		{
			$data = new Registry($data);
		}

		$filename = self::getCacheFilename($method_id, $selector, $hash);
		$folder   = Path::clean(self::$cacheFolder);
		if (!is_dir($folder))
		{
			Folder::create($folder);
		}

		$path = Path::clean(self::$cacheFolder . '/' . $filename);
		if (is_file($path))
		{
			File::delete($path);
		}

		$result = file_put_contents($path, $data->toString());

		return ($result !== false);
	}
}