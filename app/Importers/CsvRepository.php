<?php

namespace App\Importers;


class CsvRepository implements IRepository {

    public function getAll($file, array $options = []) {
        $reader = $this->createReader($file, $options);

        $headers = $reader->fetchOne();
        $records = $reader->setOffset(1)->fetchAssoc($headers);

        return $records;
    }

    public function getFiltered($file, array $filters, array $options = []) {
        $all = $this->getAll($file, $options);
        return $this->filter($all, $filters);
    }

    /**
     * @param string $file
     * @return Reader
     */
    protected function createReader($file, array $options = []) {
        $reader = Reader::createFromPath($file);

        if (isset($options['delimiter'])) {
            $reader->setDelimiter($options['delimiter']);
        }

        if (isset($options['enclosure'])) {
            $reader->setEnclosure($options['enclosure']);
        }

        if (isset($options['escape'])) {
            $reader->setEscape($options['escape']);
        }

        if (isset($options['newline'])) {
            $reader->setNewline($options['newline']);
        }

        if (isset($options['input_encoding'])) {
            if (!$reader->isActiveStreamFilter()) {
                throw new \LogicException('Stream filter is not active');
            }

            $conversionFilter = $this->getConversionFilter($options['input_encoding']);
            $reader->appendStreamFilter($conversionFilter);
        }

        return $reader;
    }

    /**
     * @param string $input_encoding
     * @return string
     */
    protected function getConversionFilter($input_encoding) {
        return sprintf('convert.iconv.%s/UTF-8', $input_encoding);
    }

    /**
     * @param \Iterator $records
     * @return \Iterator
     */
    protected function filter(\Iterator $records, array $filters) {
        return new \CallbackFilterIterator($records, function ($current, $key) use ($filters) {
            foreach ($filters as $filter) {
                if (!$filter($current, $key)) {
                    return false;
                }
            }

            return true;
        });
    }
}