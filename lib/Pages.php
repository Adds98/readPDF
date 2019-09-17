<?php
include_once('PDFObject.php');

class Pages extends PDFObject
{
    /**
     * @param bool $deep
     *
     * @return array
     */
    public function getPages($deep = false)
    {
        if ($this->has('Kids')) {

            if (!$deep) {
                return $this->get('Kids')->getContent();
            } else {
                $kids  = $this->get('Kids')->getContent();
                $pages = array();

                foreach ($kids as $kid) {

                    if ($kid instanceof Pages) {
                        $pages = array_merge($pages, $kid->getPages(true));
                    } else {
                        $pages[] = $kid;
                    }
                }

                return $pages;
            }
        }

        return array();
    }
}
