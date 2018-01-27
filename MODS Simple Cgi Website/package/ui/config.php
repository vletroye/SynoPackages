<?php

/**
 * Used to store website configuration information.
 *
 * @var string
 */
function config($key = '')
{
    $config = [
        'name' => 'Simple PHP Website',
        'nav_menu' => [
            'home' => 'Home',
            'about-us' => 'About Us',
            'products' => 'Products',
            'contact' => 'Contact',
			'test' => 'Test',
        ],
        'template_path' => 'template',
        'content_path' => 'content',
        'pretty_uri' => false,
        'version' => 'v2.0',
    ];

    return isset($config[$key]) ? $config[$key] : null;
}
