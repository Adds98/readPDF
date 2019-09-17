<?php

include_once('ElementArray.php');
include_once('ElementBoolean.php');
include_once('ElementDate.php');
include_once('ElementHexa.php');
include_once('ElementName.php');
include_once('ElementNull.php');
include_once('ElementNumeric.php');
include_once('ElementString.php');
include_once('ElementXRef.php');
include_once('tcpdf_parser.php');

class Parser
{
    /**
     * @var PDFObject[]
     */
    protected $objects = array();

    /**
     *
     */
    public function __construct()
    {

    }

    /**
     * @param $filename
     * @return Document
     * @throws \Exception
     */
    public function parseFile($filename)
    {
        $content = file_get_contents($filename);
        return $this->parseContent($content);
    }

    /**
     * @param $content
     * @return Document
     * @throws \Exception
     */
    public function parseContent($content)
    {
        // Create structure using TCPDF Parser.
        ob_start();
        @$parser = new TCPDF_PARSER(ltrim($content));
        list($xref, $data) = $parser->getParsedData();
        unset($parser);
        ob_end_clean();

        if (isset($xref['trailer']['encrypt'])) {
            throw new \Exception('Secured pdf file are currently not supported.');
        }

        if (empty($data)) {
            throw new \Exception('Object list not found. Possible secured file.');
        }

        // Create destination object.
        $document      = new Document();
        $this->objects = array();

        foreach ($data as $id => $structure) {
            $this->parseObject($id, $structure, $document);
            unset($data[$id]);
        }

        $document->setTrailer($this->parseTrailer($xref['trailer'], $document));
        $document->setObjects($this->objects);

        return $document;
    }

    protected function parseTrailer($structure, $document)
    {
        $trailer = array();

        foreach ($structure as $name => $values) {
            $name = ucfirst($name);

            if (is_numeric($values)) {
                $trailer[$name] = new ElementNumeric($values, $document);
            } elseif (is_array($values)) {
                $value          = $this->parseTrailer($values, null);
                $trailer[$name] = new ElementArray($value, null);
            } elseif (strpos($values, '_') !== false) {
                $trailer[$name] = new ElementXRef($values, $document);
            } else {
                $trailer[$name] = $this->parseHeaderElement('(', $values, $document);
            }
        }

        return new Header($trailer, $document);
    }

    /**
     * @param string   $id
     * @param array    $structure
     * @param Document $document
     */
    protected function parseObject($id, $structure, $document)
    {
        $header  = new Header(array(), $document);
        $content = '';

        foreach ($structure as $position => $part) {
            switch ($part[0]) {
                case '[':
                    $elements = array();

                    foreach ($part[1] as $sub_element) {
                        $sub_type   = $sub_element[0];
                        $sub_value  = $sub_element[1];
                        $elements[] = $this->parseHeaderElement($sub_type, $sub_value, $document);
                    }

                    $header = new Header($elements, $document);
                    break;

                case '<<':
                    $header = $this->parseHeader($part[1], $document);
                    break;

                case 'stream':
                    $content = isset($part[3][0]) ? $part[3][0] : $part[1];

                    if ($header->get('Type')->equals('ObjStm')) {
                        $match = array();

                        // Split xrefs and contents.
                        preg_match('/^((\d+\s+\d+\s*)*)(.*)$/s', $content, $match);
                        $content = $match[3];

                        // Extract xrefs.
                        $xrefs = preg_split(
                            '/(\d+\s+\d+\s*)/s',
                            $match[1],
                            -1,
                          PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
                        );
                        $table = array();

                        foreach ($xrefs as $xref) {
                            list($id, $position) = explode(' ', trim($xref));
                            $table[$position] = $id;
                        }

                        ksort($table);

                        $ids       = array_values($table);
                        $positions = array_keys($table);

                        foreach ($positions as $index => $position) {
                            $id            = $ids[$index] . '_0';
                            $next_position = isset($positions[$index + 1]) ? $positions[$index + 1] : strlen($content);
                            $sub_content   = substr($content, $position, $next_position - $position);

                            $sub_header         = Header::parse($sub_content, $document);
                            $object             = PDFObject::factory($document, $sub_header, '');
                            $this->objects[$id] = $object;
                        }

                        // It is not necessary to store this content.
                        $content = '';

                        return;
                    }
                    break;

                default:
                    if ($part != 'null') {
                        $element = $this->parseHeaderElement($part[0], $part[1], $document);

                        if ($element) {
                            $header = new Header(array($element), $document);
                        }
                    }
                    break;

            }
        }

        if (!isset($this->objects[$id])) {
            $this->objects[$id] = PDFObject::factory($document, $header, $content);
        }
    }

    /**
     * @param array    $structure
     * @param Document $document
     *
     * @return Header
     * @throws \Exception
     */
    protected function parseHeader($structure, $document)
    {
        $elements = array();
        $count    = count($structure);

        for ($position = 0; $position < $count; $position += 2) {
            $name  = $structure[$position][1];
            $type  = $structure[$position + 1][0];
            $value = $structure[$position + 1][1];

            $elements[$name] = $this->parseHeaderElement($type, $value, $document);
        }

        return new Header($elements, $document);
    }

    /**
     * @param $type
     * @param $value
     * @param $document
     *
     * @return Element|Header
     * @throws \Exception
     */
    protected function parseHeaderElement($type, $value, $document)
    {
        switch ($type) {
            case '<<':
                return $this->parseHeader($value, $document);

            case 'numeric':
                return new ElementNumeric($value, $document);

            case 'boolean':
                return new ElementBoolean($value, $document);

            case 'null':
                return new ElementNull($value, $document);

            case '(':
                if ($date = ElementDate::parse('(' . $value . ')', $document)) {
                    return $date;
                } else {
                    return ElementString::parse('(' . $value . ')', $document);
                }

            case '<':
                return $this->parseHeaderElement('(', ElementHexa::decode($value, $document), $document);

            case '/':
                return ElementName::parse('/' . $value, $document);

            case 'ojbref': // old mistake in tcpdf parser
            case 'objref':
                return new ElementXRef($value, $document);

            case '[':
                $values = array();

                foreach ($value as $sub_element) {
                    $sub_type  = $sub_element[0];
                    $sub_value = $sub_element[1];
                    $values[]  = $this->parseHeaderElement($sub_type, $sub_value, $document);
                }

                return new ElementArray($values, $document);

            case 'endstream':
            case 'obj': //I don't know what it means but got my project fixed.
            case '':
                // Nothing to do with.
                break;

            default:
                throw new \Exception('Invalid type: "' . $type . '".');
        }
    }
}
