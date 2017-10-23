<?php

namespace App\Importers;


interface IRepository {

    /**
     * @param string $file
     * @param array $options
     * @return \Iterator
     */
    public function getAll($file, array $options = []);
}