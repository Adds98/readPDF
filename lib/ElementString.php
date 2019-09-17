<?php

include_once('Element.php');
include_once('Document.php');
include_once('Font.php');

class ElementString extends Element
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
     * @return bool|ElementString
     */
    public static function parse($content, Document $document = null, &$offset = 0)
    {
        if (preg_match('/^\s*\((?P<name>.*)/s', $content, $match)) {
            $name = $match['name'];

            // Find next ')' not escaped.
            $cur_start_text = $start_search_end = 0;
            while (($cur_start_pos = strpos($name, ')', $start_search_end)) !== false) {
                $cur_extract = substr($name, $cur_start_text, $cur_start_pos - $cur_start_text);
                preg_match('/(?P<escape>[\\\]*)$/s', $cur_extract, $match);
                if (!(strlen($match['escape']) % 2)) {
                    break;
                }
                $start_search_end = $cur_start_pos + 1;
            }

            // Extract string.
            $name   = substr($name, 0, $cur_start_pos);
            $offset += strpos($content, '(') + $cur_start_pos + 2; // 2 for '(' and ')'
            $name   = str_replace(
                array('\\\\', '\\ ', '\\/', '\(', '\)', '\n', '\r', '\t'),
                array('\\',   ' ',   '/',   '(',  ')',  "\n", "\r", "\t"),
                $name
            );

            // Decode string.
            $name = Font::decodeOctal($name);
            $name = Font::decodeEntities($name);
            $name = Font::decodeHexadecimal($name, false);
            $name = Font::decodeUnicode($name);

            return new self($name, $document);
        }

        return false;
    }
}
