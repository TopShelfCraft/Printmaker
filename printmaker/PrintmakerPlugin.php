<?php
namespace Craft;

/**
 * Printmaker Plugin class
 */
class PrintmakerPlugin extends BasePlugin
{

	public function getName()
	{
		return 'Printmaker';
	}

	public function getVersion()
	{
		return '0.9.1';
	}

	public function getDeveloper()
	{
		return 'Michael Rog';
	}

	public function getDeveloperUrl()
	{
		return 'http://michaelrog.com/craft/printmaker';
	}

	public function hasCpSection()
	{
		return false;
	}

	public function registerCpRoutes()
	{
		return array();
	}

	public function onAfterInstall()
	{
		craft()->printmaker_beta->phoneHome();
	}

}