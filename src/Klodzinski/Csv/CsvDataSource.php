<?php

namespace Klodzinski\Csv;

class CsvDataSource
{
    private $headers = [];
    private $columnTransformers = [];
    private $rows = [];
    private $ignoreColumns = [];
    private $renameColumns = [];
    private $newColumnsAfter = [];

    private $fieldSeparator = ',';
    private $ignoreFirstNRows;
    private $hasHeader = false;
    /**
     * CsvDataSource constructor.
     *
     * @param $csvFile
     *
     * @throws CouldNotOpenFileException
     * @throws NoDataException
     */
    public function __construct($csvFile, $hasHeader, $fieldSeparator = ',', $ignoreFirstNRows = 0)
    {
        $this->hasHeader = $hasHeader;
        $this->ignoreFirstNRows = $ignoreFirstNRows;
        $this->fieldSeparator = $fieldSeparator;
        $this->load($csvFile);
    }

    /**
     * @param $csvFile
     *
     * @throws CouldNotOpenFileException
     * @throws NoDataException
     */
    private function load($csvFile)
    {
        $csvReader = new CsvReader($csvFile, $this->hasHeader, $this->fieldSeparator, $this->ignoreFirstNRows);

        $this->headers = $csvReader->getHeaders();

        while ($csvReader->loadNextLine()) {
            $this->rows[] = $csvReader->getRow();
        }
    }

    public function removeColumn($index)
    {
        $this->removeColumns([$index]);
    }

    public function removeColumns(array $index)
    {
        $this->ignoreColumns += $index;
    }

    public function columnTransformer($columnName, \Closure $transformer)
    {
        $this->columnTransformers[$columnName] = $transformer;
    }

    public function renameColumns(array $array)
    {
        $this->renameColumns += $array;
    }

    public function addColumnAfter($afterColumn, $newColumn, \Closure $newColumnCreation)
    {
        $this->newColumnsAfter[$afterColumn] = [
            'name'     => $newColumn,
            'callback' => $newColumnCreation
        ];
    }

    public function forEachRow(\Closure $closure) {
        foreach ($this->rows as $row) {
            $closure($this->processRow($row));
        }
    }

    /**
     * @param  string  $fileName
     *
     * @throws CouldNotOpenFileException
     */
    public function createNew(string $fileName)
    {
        $headers = $this->getConvertedHeaders();

        $csvWriter = new CsvWriter($fileName);

        $csvWriter->write($headers);

        foreach ($this->rows as $row) {
            $rowToSave = $this->processRow($row);
            $csvWriter->write($rowToSave);
        }
    }

    private function getConvertedHeaders() {
        $renameColumns = $this->renameColumns;
        $ignoreColumns = $this->ignoreColumns;

        $headers = $this->headers;
        foreach ($headers as $header) {
            if (array_key_exists($header, $this->newColumnsAfter)) {
                $afterIndex = array_search($header, $headers);

                $h1 = array_slice($headers, 0, $afterIndex);
                $h2 = array($this->newColumnsAfter[$header]['name']);
                $h3 = array_slice($headers, $afterIndex, count($headers) - $afterIndex);

                $headers = array_merge($h1, $h2, $h3);
            }
        }


        $headers = array_map(function ($header) use ($renameColumns) {
            if (array_key_exists($header, $renameColumns)) {
                return $this->renameColumns[$header];
            }

            return $header;
        }, $headers);

        return array_filter($headers, function ($value) use ($ignoreColumns) {
            if (array_search($value, $ignoreColumns) === false) {
                return true;
            }

            return false;
        });
    }

    private function processRow($row)
    {
        $rowToSave = $row;
        foreach ($row as $columnName => $columnValue) {
            if (array_key_exists($columnName, $this->columnTransformers)) {
                $rowToSave[$columnName] = $this->columnTransformers[$columnName]($columnValue, $row);
            }

            if (array_key_exists($columnName, $this->newColumnsAfter)) {
                $afterIndex = array_search($columnName, array_keys($rowToSave));
                $rowToSave  = array_slice($rowToSave, 0, $afterIndex, true) +
                              array($this->newColumnsAfter[$columnName]['name'] => $this->newColumnsAfter[$columnName]['callback']($row)) +
                              array_slice($rowToSave, $afterIndex, count($rowToSave) - $afterIndex, true);
            }
        }

        return $this->ignore($rowToSave);
    }

    private function ignore($row)
    {
        $newRow = $row;
        foreach ($row as $k => $v) {
            if (array_search($k, $this->ignoreColumns) !== false) {
                unset($newRow[$k]);
            }
        }

        return $newRow;
    }
}