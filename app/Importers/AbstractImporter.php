<?php

namespace App\Importers;


use App\Item;
use League\Csv\Reader;

abstract class AbstractImporter
{
    protected $sanitizers = [];

    protected $filters = [];

    protected $mapping = [];

    protected $defaults = [];

    protected $options = [];

    public function __construct() {
        $this->registerSanitizers();
        $this->registerFilters();
    }

    /**
     * @param \Iterator $record
     * @return mixed
     */
    abstract protected function getItemId(array $record);

    /**
     * @param string $file
     * @return Item[]
     */
    public function import($file) {
        $reader = $this->createReader($file);

        $headers = $reader->fetchOne();
        $records = $reader->setOffset(1)->fetchAssoc($headers);
        $records = $this->filter($records);

        foreach ($records as $record) {
            $id = $this->getItemId($record);

            $item = Item::firstOrNew(['id' => $id]);

            $record = array_map(function ($value) {
                return $this->sanitize($value);
            }, $record);

            $this->hydrate($item, $record);

            $item->save();
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param string $file
     * @return Reader
     */
    protected function createReader($file) {
        $reader = Reader::createFromPath($file);

        if (isset($this->options['delimiter'])) {
            $reader->setDelimiter($this->options['delimiter']);
        }

        if (isset($this->options['enclosure'])) {
            $reader->setEnclosure($this->options['enclosure']);
        }

        if (isset($this->options['escape'])) {
            $reader->setEscape($this->options['escape']);
        }

        if (isset($this->options['newline'])) {
            $reader->setNewline($this->options['newline']);
        }

        if (isset($this->options['input_encoding'])) {
            if (!$reader->isActiveStreamFilter()) {
                throw new \LogicException('Stream filter is not active');
            }

            $conversionFilter = $this->getConversionFilter($this->options['input_encoding']);
            $reader->appendStreamFilter($conversionFilter);
        }

        return $reader;
    }

    /**
     * @param \Iterator $records
     * @return \Iterator
     */
    protected function filter(\Iterator $records) {
        return new \CallbackFilterIterator($records, function ($current, $key) {
            foreach ($this->filters as $filter) {
                if (!$filter($current, $key)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function sanitize($value) {
        foreach ($this->sanitizers as $sanitizer) {
            $value = $sanitizer($value);
        }

        return $value;
    }

    /**
     * @param Item $item
     * @param array $record
     */
    protected function hydrate(Item $item, array $record) {
        foreach ($record as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedKey = $this->mapping[$key];
                $item->$mappedKey = $value;
            }
        }

        foreach ($item as $key => $value) {
            $method_name = sprintf('hydrate%s', camel_case($key));
            if (method_exists($this, $method_name)) {
                $item->$key = $this->$method_name($record);
            }
        }

        foreach ($this->defaults as $key => $default) {
            if (!isset($item->$key)) {
                $item->$key = $default;
            }
        }
    }

    /**
     * @param string $input_encoding
     * @return string
     */
    protected function getConversionFilter($input_encoding) {
        return sprintf('convert.iconv.%s/UTF-8', $input_encoding);
    }

    protected function registerSanitizers() {}

    protected function registerFilters() {}
}