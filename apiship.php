<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Registry\Registry;

class plgRadicalMart_ShippingApiShip extends CMSPlugin
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app = null;

	/**
	 * Affects constructor behavior.
	 *
	 * @var  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Add apiship config form to RadicalMart.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartPrepareConfigForm($form, $data)
	{
		Form::addFormPath(__DIR__ . '/forms');
		$form->loadFile('config');
	}

	/**
	 * Add onRadicalMartPrepareConfigForm & onRadicalMartPrepareProductForm triggers and modules positions.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onContentPrepareForm($form, $data)
	{
		if ($form->getName() === 'com_radicalmart.shippingmethod')
		{
			$form->setFieldAttribute('from', 'key',
				ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key'),
				'params');
		}
	}

	/**
	 * Add onRadicalMartPrepareConfigForm & onRadicalMartPrepareProductForm triggers and modules positions.
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @throws  Exception
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onRadicalMartGetOrderForm($context, $form, $data, $shipping, $payment)
	{
		if ($form->getName() === 'com_radicalmart.checkout')
		{
			$places = (new Registry($shipping->params->get('from')))->toString('json');
			$form->setFieldAttribute('from', 'places',
				$places, 'shipping');
			$form->setFieldAttribute('to', 'key',
				ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key'),
				'shipping');
		}
	}
}