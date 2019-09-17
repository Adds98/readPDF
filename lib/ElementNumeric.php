<?php

include_once('Element.php');
include_once('Document.php');

/**
 * Class ElementNumeric
 *
 * @package Smalot\PdfParser\Element
 */
class ElementNumeric extends Element
{
    /**
     * @param string   $value
     * @param Document $document
     */
    public function __construct($value, Document $document = null)
    {
        parent::__construct(floatval($value), null);
    }

    /**
     * @param string   $content
     * @param Document $document
     * @param int      $offset
     *
     * @return bool|ElementNumeric
     */
    public static function parse($content, Document $document = null, &$offset = 0)
    {
        if (preg_match('/^\s*(?P<value>\-?[0-9\.]+)/s', $content, $match)) {
            $value  = $match['value'];
            $offset += strpos($content, $value) + strlen($value);

            return new self($value, $document);
        }

        return false;
    }
}
