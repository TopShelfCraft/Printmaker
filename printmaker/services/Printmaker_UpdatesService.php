<?php
namespace Craft;

/**
 * Printmaker_UpdatesService
 *
 * @author    Top Shelf Craft <support@topshelfcraft.com.>
 * @copyright Copyright (c) 2016, Michael Rog
 * @license   http://topshelfcraft.com/license
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class Printmaker_UpdatesService extends BaseApplicationComponent
{

	const SecondStarToTheRight = 'http://michaelrog.com/craft/et/printmaker.php';
	const ReleaseFeedUrl = 'https://topshelfcraft.com/releases/printmaker.json';

	public function phoneHome() {

		$et = new Et(static::SecondStarToTheRight);
		$et->phoneHome();

	}

}