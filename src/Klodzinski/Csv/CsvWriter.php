<?php


namespace Klodzinski\Csv;


class CsvWriter
{
    private $handle;

    /**
     * CsvWriter constructor.
     *
     * @param $filename
     *
     * @throws CouldNotOpenFileException
     */
    public function __construct($filename)
    {
        $this->handle = fopen($filename, 'w');

        if ($this->handle === FALSE) {
            throw new CouldNotOpenFileException();
        }

        fprintf($this->handle, chr(0xEF).chr(0xBB).chr(0xBF));
    }

    public function write(array $row)
    {
        fputcsv($this->handle, $row);
    }

    public function __destruct()
    {
        fclose($this->handle);
    }
}