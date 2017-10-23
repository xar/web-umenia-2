<?php


namespace App\Importers;


use App\Repositories\IFileRepository;

class NgImporter extends AbstractImporter {

    protected $options = [
        'delimiter' => ';',
        'enclosure' => '"',
        'escape' => '\\',
        'newline' => "\r\n",
        'input_encoding' => 'CP1250',
    ];

    protected $mapping = [
        'Název díla' => 'title',
        'z cyklu' => 'related_work',
        'Datace' => 'dating',
        'Technika' => 'technique',
        'Materiál' => 'medium',
        'Inventární čís.' => 'identifier',
        'Popis' => 'description',
    ];

    protected $defaults = [
        'work_type' => '',
        'topic' => '',
        'relationship_type' => '',
        'place' => '',
        'gallery' => '',
        'description' => '',
        'title' => '',
    ];

    protected $name = 'ng';

    public function __construct(IFileRepository $repository) {
        parent::__construct($repository);

        $this->sanitizers[] = function($value) {
            return empty_to_null($value);
        };
    }


    protected function getItemId(array $record) {
        $key = 'Inventární čís.';
        $id = $record[$key];
        $id = strtr($id, ' ', '_');

        return sprintf('CZE:NG.%s', $id);
    }

    protected function getItemImageFilename(array $record) {
        $key = 'Inventární čís.';
        $filename = $record[$key];
        $filename = strtr($filename, ' ', '_');

        return $filename;
    }

    protected function getItemIipImageUrl($csv_filename, $image_filename) {
        return sprintf(
            '/NG/jp2/%s.jp2',
            $image_filename
        );
    }

    protected function hydrateAuthor(array $record) {
        $keys = [
            'Autor (jméno příjmení, příp. Anonym)',
            'Autor 2',
            'Autor 3',
        ];

        $keys = array_filter($keys, function ($key) use ($record) {
            return $record[$key] !== null;
        });

        $authors = array_filter(
            array_map(function($key) use ($record) {
                return self::bracketAuthor($record[$key]);
            }, $keys)
        );

        return implode(', ', $authors);
    }

    protected function hydrateInscription(array $record) {
        $key1 = 'značeno kde (umístění v díle)';
        $key2 = 'Značeno (jak např. letopočet, signatura, monogram)';

        return sprintf('%s: %s', $record[$key1], $record[$key2]);
    }

    protected function hydrateMeasurement(array $record) {
        $key = 'Čistý rozměr (bez rámu, pasparty apod)';
        if ($record[$key] !== null) {
            return $record[$key];
        }

        $measurement1 = self::buildMeasurement($record, 'šířka', 'výška', 'hloubka', 'jednotky');
        $measurement2 = self::buildMeasurement($record, 'šířka_0', 'výška_0', 'hloubka_0', 'jednotky_0');

        $key = 'Rozměr 2';
        if ($record[$key] !== null) {
            $measurement2 = sprintf('%s: %s', $record[$key], $measurement2);
        }

        $measurements = array_filter([$measurement1, $measurement2], function ($measurement) {
            return $measurement !== '';
        });
        $measurement = implode(', ', $measurements);

        $key = 'popis rozměru (např. s rámem, se soklem, celý papír apod.)';
        if ($record[$key] !== null) {
            $measurement = sprintf('%s (%s)', $measurement, $record[$key]);
        }

        return $measurement;
    }

    protected function hydrateDateEarliest(array $record) {
        if (preg_match_all('/(\d{4})/', $record['Datování (určené)'], $matches)) {
            if (isset($matches[1][0])) {
                return $matches[1][0];
            }
        }

        return 0;
    }

    protected function hydrateDateLatest(array $record) {
        if (preg_match_all('/(\d{4})/', $record['Datování (určené)'], $matches)) {
            if (isset($matches[1][count($matches) - 1])) {
                return $matches[1][count($matches) - 1];
            }
        }

        return 0;
    }

    /**
     * @param array $record
     * @param string $width
     * @param string $height
     * @param string $depth
     * @param string $units
     * @return string
     */
    protected static function buildMeasurement(array $record, $width, $height, $depth, $units) {
        $measurement = [];

        $units_suffix = $record[$units] !== null ? sprintf(' %s', $record[$units]) : '';
        if ($record[$width] !== null) {
            $measurement[] = sprintf('šířka %s%s', $record[$width], $units_suffix);
        }
        if ($record[$height] !== null) {
            $measurement[] = sprintf('výška %s%s', $record[$height], $units_suffix);
        }
        if ($record[$depth] !== null) {
            $measurement[] = sprintf('hloubka %s%s', $record[$depth], $units_suffix);
        }

        return implode(', ', $measurement);
    }

    /**
     * @param string $author
     * @return string
     */
    protected static function bracketAuthor($author) {
        return preg_replace('/,\s*(.*)/', ' ($1)', $author);
    }
}