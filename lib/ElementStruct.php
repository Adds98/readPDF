<?php

include_once('Element.php');
include_once('Document.php');
include_once('Header.php');

/**
 * Class ElementStruct
 *
 * @package Smalot\PdfParser\Element
 */
class ElementStruct extends Element
{
    /**
     * @param string   $content
     * @param Document $document
     * @param int      $offset
     *
     * @return bool|ElementStruct
     */
    public static function parse($content, Document $document = null, &$offset = 0)
    {
        if (preg_match('/^\s*<<(?P<struct>.*)/is', $content)) {
            preg_match_all('/(.*?)(<<|>>)/s', trim($content), $matches);

            $level = 0;
            $sub   = '';
            foreach ($matches[0] as $part) {
                $sub .= $part;
                $level += (strpos($part, '<<') !== false ? 1 : -1);
                if ($level <= 0) {
                    break;
                }
            }

            $offset += strpos($content, '<<') + strlen(rtrim($sub));

            // Removes '<<' and '>>'.
            $sub = trim(preg_replace('/^\s*<<(.*)>>\s*$/s', '\\1', $sub));

            $position = 0;
            $elements = Element::parse($sub, $document, $position);
            $header   = new Header($elements, $document);

            return $header;
        }

        return false;
    }
}
