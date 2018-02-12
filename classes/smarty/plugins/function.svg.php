<?php

function smarty_function_svg($params)
{
    if (!isset($params['src'])) {
        throw new \RuntimeException('{svg} smarty tag requires src attribute');
    }

    // Load the SVG document
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load(ltrim($params['src'], '/'));
    $svg = $dom->documentElement;

    // Add attributes
    foreach ($params as $name => $value) {
        if ($name == 'src') {
            continue;
        }

        $svg->setAttribute($name, $value);
    }

    // Check if the viewport is set, if the viewport is not set the SVG wont't scale.
    if (!$svg->hasAttribute('viewBox') && $svg->hasAttribute('width') && $svg->hasAttribute('height')) {
        $svg->setAttribute('viewBox', '0 0 ' . $svg->getAttribute('width') . ' ' . $svg->getAttribute('height'));
    }

    // Return the modified SVG document
    return $dom->saveXML($svg);
}
