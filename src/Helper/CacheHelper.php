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

	/**
	 * Method to get points cache data folder.
	 *
	 * @param   int  $method_id  Shipping method id.
	 *
	 * @return string Cache folder full path.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getPointsCacheFolder(int $method_id): string
	{
		return Path::clean(CacheHelper::$cacheFolder . '_points_' . $method_id);
	}

	/**
	 * Method to save points cache data.
	 *
	 * @param   int    $method_id  Shipping method id.
	 * @param   int    $offset     Points offset
	 * @param   array  $rows       Points rows data.
	 *
	 * @throws \Exception
	 *
	 * @return bool True on success.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function savePointsCache(int $method_id, int $offset = 0, array $rows = []): bool
	{
		$folder = self::getPointsCacheFolder($method_id);
		if (!is_dir($folder))
		{
			Folder::create($folder);
		}

		$end      = $offset + count($rows);
		$filename = $offset . '-' . $end . '_' . time();

		$path = $folder . '/' . $filename . '.json';
		if (is_file($path))
		{
			File::delete($path);
		}

		$result = file_put_contents($path, (new Registry($rows))->toString());
		if ($result === false)
		{
			throw new \Exception('Could not save points file `' . $filename . '`', 500);
		}

		return true;
	}

	/**
	 * Method to get points cache files list.
	 *
	 * @param   int  $method_id  Shipping method id.
	 *
	 * @return array True on success.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getPointsCacheFiles(int $method_id): array
	{
		if ($method_id === 0)
		{
			return [];
		}

		$folder = self::getPointsCacheFolder($method_id);
		if (!is_dir($folder))
		{
			return [];
		}

		$files = Folder::files($folder);
		if (count($files) === 0)
		{
			return [];
		}

		$result = [];
		foreach ($files as $file)
		{
			if (!preg_match('/^(\d+)-(\d+)_([\d]+)\.json$/', $file, $matches))
			{
				continue;
			}

			$data = [
				'start' => (int) $matches[1] + 1,
				'end'   => (int) $matches[2],
				'time'  => (int) $matches[3],
				'file'  => $file,
			];

			$result[$data['start']] = $data;
		}

		if (count($result) === 0)
		{
			return [];
		}

		ksort($result);

		return $result;
	}

	/**
	 * Method to get points cache data from file.
	 *
	 * @param   int     $method_id  Shipping method id.
	 * @param   string  $file       cache file name
	 *
	 * @return array True on success.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getPointsCacheData(int $method_id, string $file): array
	{
		$folder = self::getPointsCacheFolder($method_id);
		if (!is_dir($folder))
		{
			return [];
		}

		$path = Path::clean($folder . '/' . $file);
		if (!is_file($path))
		{
			return [];
		}

		return (new Registry(file_get_contents($path)))->toArray();
	}
}