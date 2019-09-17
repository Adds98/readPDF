<?php


include_once('Header.php');
include_once('PDFObject.php');
include_once('Page.php');

class Form extends Page
{
    /**
     * @param Page $page
     *
     * @return string
     */
    public function getText(Page $page = null)
    {
        $header   = new Header(array(), $this->document);
        $contents = new PDFObject($this->document, $header, $this->content);

        return $contents->getText($this);
    }
}
