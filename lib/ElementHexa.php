<?php

include_once('Document.php');

/**
 * Class ElementHexa
 *
 * @package Smalot\PdfParser\Element
 */
class ElementHexa extends ElementString
{
    /**
     * @param string   $content
     * @param Document $document
     * @param int      $offset
     *
     * @return bool|ElementHexa
     */
    public static function parse($content, Document $document = null, &$offset = 0)
    {
        if (preg_match('/^\s*\<(?P<name>[A-F0-9]+)\>/is', $content, $match)) {
            $name    = $match['name'];
            $offset += strpos($content, '<' . $name) + strlen($name) + 2; // 1 for '>'
            // repackage string as standard
            $name    = '(' . self::decode($name, $document) . ')';
            $element = false;

            if (!($element = ElementDate::parse($name, $document))) {
                $element = ElementString::parse($name, $document);
            }

            return $element;
        }

        return false;
    }

    /**
     * @param string   $value
     * @param Document $document
     */
    public static function decode($value, Document $document = null)
    {
        $text   = '';
        $length = strlen($value);

        if (substr($value, 0, 2) == '00') {
            for ($i = 0; $i < $length; $i += 4) {
                $hex = substr($value, $i, 4);
                $text .= '&#' . str_pad(hexdec($hex), 4, '0', STR_PAD_LEFT) . ';';
            }
        } else {
            for ($i = 0; $i < $length; $i += 2) {
                $hex = substr($value, $i, 2);
                $text .= chr(hexdec($hex));
            }
        }

        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');

        return $text;
    }
}
