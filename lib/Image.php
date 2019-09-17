<?php

include_once('PDFObject.php');
include_once('Page.php');

/**
 * Class Image
 *
 * @package Smalot\PdfParser\XObject
 */
class Image extends PDFObject
{
    /**
     * @param Page $page
     *
     * @return string
     */
    public function getText(Page $page = null)
    {
        return '';
    }
}
