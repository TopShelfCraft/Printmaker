<?php
namespace Craft;

/**
 * @author    Top Shelf Craft <michael@michaelrog.com>
 * @copyright Copyright (c) 2016, Michael Rog
 * @package   craft.plugins.printmaker
 * @since     1.3.0
 */
class PrintmakerTwigExtension extends \Twig_Extension
{


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

        // TODO: Implement Twig_Extension_GlobalsInterface instead.

        return array(
            'printmaker' => new PrintmakerVariable(),
        );

    }


}
