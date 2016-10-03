<?php
namespace Craft;

/**
 * PrintmakerTwigExtension
 *
 * @author    Michael Rog <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @see       http://topshelfcraft.com
 * @package   craft.plugins.printmaker
 * @since     1.0
 */
class PrintmakerTwigExtension extends \Twig_Extension
{


	// Public Methods
	// =========================================================================


	/**
	 * Returns the name of the Twig extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'printmaker';
	}


	/**
	 * {@inheritdoc}
	 *
	 * @deprecated since 1.23 (to be removed in 2.0), implement Twig_Extension_GlobalsInterface instead
	 */
	public function getGlobals()
	{
		return array(
			'printmaker' => new PrintmakerVariable(),
		);
	}


}
