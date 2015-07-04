<?php

/**
 * @copyright Copyright (c) 2012 The FMFI Anketa authors (see AUTHORS).
 * Use of this source code is governed by a license that can be
 * found in the LICENSE file in the project root directory.
 *
 * @package    Anketa
 * @author     Martin Sucha <anty.sk+svt@gmail.com>
 */

namespace AnketaBundle\Lib;

use Exception;

/**
 * Parses CSV lines using native PHP CSV parser.
 */
class NativeCSVTableReader implements TableReaderInterface
{

    /** @var string the field delimiter character */
    private $delimiter;

    /** @var string string begin/end character */
    private $enclosure;

    /** @var string escape character */
    private $escape;

    /** @var array */
    private $header;

    /**
     * Create a new NativeCSVTableReader instance.
     *
     * @param type $fp file handle to read from
     * @param type $delimiter
     * @param type $enclosure
     * @param type $escape
     */
    public function __construct($fp, $delimiter=';',$enclosure='"',$escape='\\') {
        $this->fp = $fp;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->header = $this->readRow();

        // Ak CSV vzniklo konverziou z AISoveho XLS, na zaciatku su hlavickove
        // riadky, ktore maju text iba v prvom stlpci. Tak ich preskocime.
        while (empty($this->header[count($this->header) - 1])) {
            $this->header = $this->readRow();
        }
    }

    public function readRow() {
        return fgetcsv($this->fp, 0, $this->delimiter, $this->enclosure, $this->escape);
    }

    public function readAssocRow() {
        $row = $this->readRow();
        if (!$row) return $row;
        return array_combine($this->header, $row);
    }

    public function getHeader() {
        return $this->header;
    }

}
