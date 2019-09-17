<?php

include_once('Element.php');
include_once('Document.php');

class ElementBoolean extends Element
{
    /**
     * @param string   $value
     * @param Document $document
     */
    public function __construct($value, Document $document = null)
    {
        parent::__construct((strtolower($value) == 'true' || $value === true), null);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value ? 'true' : 'false';
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function equals($value)
    {
        return ($this->getContent() === $value);
    }

    /**
     * @param string   $content
     * @param Document $document
     * @param int      $offset
     *
     * @return bool|ElementBoolean
     */
    public static function parse($content, Document $document = null, &$offset = 0)
    {
        if (preg_match('/^\s*(?P<value>true|false)/is', $content, $match)) {
            $value  = $match['value'];
            $offset += strpos($content, $value) + strlen($value);

            return new self($value, $document);
        }

        return false;
    }
}
