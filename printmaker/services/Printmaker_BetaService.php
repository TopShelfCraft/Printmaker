<?php
namespace Craft;

/**
 * Printmaker BetaService
 */
class Printmaker_BetaService extends BaseApplicationComponent
{

	const SecondStarToTheRight = 'http://michaelrog.com/craft/et/printmaker.php';

	public function phoneHome() {

		$et = new Et(static::SecondStarToTheRight);
		$et->phoneHome();

	}

}