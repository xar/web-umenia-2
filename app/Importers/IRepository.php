<?php

namespace App\Importers;


interface IRepository {

    /**
     * @param string $file
     * @param array $options
     * @return \Iterator
     */
    public function getAll($file, array $options = []);

    /**
     * @param string $file
     * @param callable[] $filters
     * @param array $options
     * @return \Iterator
     */
    public function getFiltered($file, array $filters, array $options = []);
}