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

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Console;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Component\RadicalMart\Administrator\Console\AbstractCommand;
use Joomla\Component\RadicalMart\Administrator\Helper\CommandsHelper;
use Joomla\Component\RadicalMart\Administrator\Model\OrderModel;
use Joomla\Database\ParameterType;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Extension\ApiShip;
use Joomla\Registry\Registry;

class UpdateStatusesCommand extends AbstractCommand
{
	use MVCFactoryAwareTrait;

	/**
	 * The default command name
	 *
	 * @var    string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $defaultName = 'radicalmart:shipping:apiship:update_statuses';

	/**
	 * Command text title for configure.
	 *
	 * @var   string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected string $commandText = 'Radicalmart Shipping - ApiShip: Update statuses';

	/**
	 * Command description for configure help block.
	 *
	 * @var   string
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected string $commandDescription = 'run script for check and update all orders statuses';

	/**
	 * Command methods for step by step run.
	 *
	 * @var  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected array $methods = [
		'updateOrdersStatuses',
	];

	/**
	 * Method to update orders statuses.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function updateOrdersStatuses(): void
	{
		$this->ioStyle->title('ApiShip: Update orders statuses');

		$this->ioStyle->text('Get total orders');
		$this->startProgressBar(1, true);
		$total = CommandsHelper::getTotalItems('#__radicalmart_orders');
		$this->finishProgressBar();

		$this->ioStyle->text('Update statuses');
		$this->startProgressBar($total, true);
		$last    = 0;
		$limit   = 50;
		$db      = $this->getDatabase();
		$factory = $this->getMVCFactory();
		$plugin  = new ApiShip();
		$plugin->setApplication(Factory::getApplication());
		$plugin->setDatabase($db);
		$plugin->setMVCFactory($factory);
		$errors = [];
		while (true)
		{
			$query  = $db->createQuery()
				->select(['id', 'shipping'])
				->from($db->quoteName('#__radicalmart_orders'))
				->where($db->quoteName('id') . ' > :last')
				->bind(':last', $last, ParameterType::INTEGER);
			$orders = $db->setQuery($query, 0, $limit)->loadObjectList();
			$count  = count($orders);
			if ($count === 0)
			{
				break;
			}

			foreach ($orders as $order)
			{
				$last            = (int) $order->id;
				$order->shipping = new Registry($order->shipping);
				if ($order->shipping->get('plugin') !== 'apiship')
				{
					$this->advanceProgressBar();
					continue;
				}

				$params = ApiShip::getShippingMethodParams($order->shipping->get('id'));
				if ((int) $params->get('api_orders_enabled', 0) === 0)
				{
					$this->advanceProgressBar();
					continue;
				}

				try
				{
					/** @var OrderModel $model */
					$model = $factory->createModel('Order', 'Administrator', ['ignore_request' => true]);
					$model->setState('order.id', $order->id);
					$order = $model->getItem($order->id);

					$data       = $plugin->getApiOrderStatus($order);
					$status_key = $data->get('status.key');
					$plugin->changeOrderStatus($order, $status_key);

					if ($plugin->isRetailCRMEnabled())
					{
						$model->reset($order->id);
						$order = $model->getItem($order->id);

						try
						{
							$plugin->updateRetailCRMOrderShippingData($order);
							$plugin->changeRetailCRMOrderStatus($order, $status_key);
						}
						catch (\Throwable)
						{

						}
					}
				}
				catch (\Throwable $e)
				{
					if ($e->getCode() === 500)
					{
						$errors[] = $order->id . ': `' . $e->getMessage() . '`';
					}
				}

				$this->advanceProgressBar();
			}

			$db->disconnect();
			if ($count < $limit)
			{
				break;
			}
		}
		$this->finishProgressBar();

		if (count($errors) > 0)
		{
			$this->ioStyle->error(implode(PHP_EOL, $errors));
		}
	}
}