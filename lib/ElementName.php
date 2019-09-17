<?php

include_once('Element.php');
include_once('Document.php');
include_once('Font.php');

/**
 * Class ElementName
 *
 * @package Smalot\PdfParser\Element
 */
class ElementName extends Element
{
    /**
     * @param string   $value
     * @param Document $document
     */
    public function __construct($value, Document $document = null)
    {
        parent::__construct($value, null);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function equals($value)
    {
        return $value == $this->value;
    }

    /**
     * @param string   $content
     * @param Document $document
     * @param int      $offset
     *
     * @return bool|ElementName
     */
    public static function parse($content, Document $document = null, &$offset = 0)
    {
        if (preg_match('/^\s*\/(?P<name>[A-Z0-9\-\+,#\.]+)/is', $content, $match)) {
            $name   = $match['name'];
            $offset += strpos($content, $name) + strlen($name);
            $name   = Font::decodeEntities($name);

            return new self($name, $document);
        }

        return false;
    }
}
