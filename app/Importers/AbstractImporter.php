<?php

namespace App\Importers;

use App\Item;


abstract class AbstractImporter {

    /** @var callable[] */
    protected $sanitizers = [];

    /** @var callable[] */
    protected $filters = [];

    /** @var array */
    protected $mapping = [];

    /** @var array */
    protected $defaults = [];

    /** @var IRepository */
    protected $repository;

    /**
     * @param IRepository $repository
     */
    public function __construct(IRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * @param array $record
     * @return mixed
     */
    abstract protected function getItemId(array $record);

    /**
     * @param string $file
     * @return Item[]
     */
    public function import($file) {
        $records = $this->repository->getAll($file, $this->options);
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

        foreach ($item->toArray() as $key => $value) {
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
}