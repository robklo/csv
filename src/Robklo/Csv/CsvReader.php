<?php


namespace Robklo\Csv;


class CsvReader
{
    private $headers = [];
    private $handle;
    /** @var bool|array */
    private $currentData = false;

    private $fieldSeparator = ',';

    /**
     * CsvReader constructor.
     *
     * @param $csvFile
     *
     * @param bool $hasHeader
     * @param string $fieldSeparator
     * @param int $ignoreFirstNRows
     * @throws CouldNotOpenFileException
     * @throws NoDataException
     */
    public function __construct($csvFile, $hasHeader = false, $fieldSeparator = ',', $ignoreFirstNRows = 0)
    {
        $this->fieldSeparator = $fieldSeparator;
        $this->handle = fopen($csvFile, "r");
        if ($this->handle === false) {
            throw new CouldNotOpenFileException();
        }

        for($n = 0; $n < $ignoreFirstNRows; ++$n) {
            $this->loadNextLine();
        }

        if ($hasHeader && $this->loadNextLine()) {
            $headersRow    = $this->getRow();
            $this->headers = array_values($headersRow);
        }
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function loadNextLine()
    {
        $this->currentData = fgetcsv($this->handle, 0, $this->fieldSeparator);

        if ($this->currentData === false) {
            return false;
        }

        return true;
    }

    /**
     * @return array|bool|false
     * @throws NoDataException
     */
    public function getRow()
    {
        if ($this->currentData === false) {
            throw new NoDataException();
        }

        if (empty($this->headers)) {
            return $this->currentData;
        }

        return array_combine(array_values($this->headers), $this->maybeAssignHeaderNames($this->currentData));
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    private function maybeAssignHeaderNames($row)
    {
        if ( ! is_array($row) || empty($this->headers)) {
            return $row;
        }

        $newRow = [];
        foreach ($this->headers as $idx => $name) {
            $newRow[$name] = $row[$idx];
        }

        return $newRow;
    }
}