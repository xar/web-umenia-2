<?php

namespace Tests;

use App\Importers\MgImporter;

class MgImporterTest extends \TestCase
{
    protected $importer;

    protected function setUp() {
        parent::setUp();
        $this->importer = new MgImporter();
    }

    public function testImport() {
        $file = __DIR__ . '/resources/test.csv';
        $items = $this->importer->import($file);

        var_dump($items[0]);
    }
}