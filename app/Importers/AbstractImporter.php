<?php

namespace App\Importers;

use App\Import;
use App\ImportRecord;
use App\Item;
use Intervention\Image\Image;


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

    const IMAGE_MAX_SIZE = 800;

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
     * @param array $record
     * @return string
     */
    abstract protected function getItemImageFilename(array $record);

    /**
     * @param string $filename
     * @param string $image_file
     * @return string
     */
    abstract protected function getItemIipImage($filename, $image_file);

    /**
     * @param Import $import
     * @param mixed $file
     */
    public function import(Import $import, $file) {
        // todo restrict to only one type
        $filename = (is_array($file)) ? $file['basename'] : $file->getClientOriginalName();

        $import_record = $this->createImportRecord(
            $import->id,
            Import::STATUS_IN_PROGRESS,
            date('Y-m-d H:i:s'),
            $filename
        );

        $records = $this->repository->getFiltered($filename, $this->filters, $this->options);

        $items = [];
        foreach ($records as $record) {
            try {
                $item = $this->createItem($record);
            } catch (\Exception $e) {
                $import_record->wrong_items++;
                throw $e;
            }

            // todo refactor
            $image_file = $this->getItemImage($import, $filename, $record);

            if ($image_file) {
                $this->uploadImage($item, $image_file);
                $item->has_image = true;
                $import_record->imported_images++;

                $iip_img = $this->getItemIipImage($filename, $image_file);

                $iip_url = 'http://www.webumenia.sk/fcgi-bin/iipsrv.fcgi?DeepZoom=' . $iip_img;
                if (isValidURL($iip_url)) {
                    $item->iipimg_url = $iip_img;
                    $import_record->imported_iip++;
                }
            }

            $item->save();
            $import_record->imported_items++;

            $items[] = $item;
        }

        $import_record->save();

        return $items;
    }

    protected function createImportRecord($import_id, $status, $started_at, $filename) {
        $import_record = new ImportRecord();
        $import_record->import_id = $import_id;
        $import_record->status = $status;
        $import_record->started_at = $started_at;
        $import_record->filename = $filename;

        return $import_record;
    }

    /**
     * @param array $record
     * @return Item
     */
    protected function createItem(array $record) {
        $id = $this->getItemId($record);

        $item = Item::firstOrNew(['id' => $id]);

        $record = array_map(function ($value) {
            return $this->sanitize($value);
        }, $record);

        $this->mapFields($item, $record);
        $this->applyCustomHydrators($item, $record);
        $this->setDefaultValues($item);

        return $item;
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
    protected function mapFields(Item $item, array $record) {
        foreach ($record as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedKey = $this->mapping[$key];
                $item->$mappedKey = $value;
            }
        }
    }

    /**
     * @param Item $item
     * @param array $record
     */
    protected function applyCustomHydrators(Item $item, array $record) {
        foreach ($item->toArray() as $key => $value) {
            $method_name = sprintf('hydrate%s', camel_case($key));
            if (method_exists($this, $method_name)) {
                $item->$key = $this->$method_name($record);
            }
        }
    }

    /**
     * @param Item $item
     * @param array $record
     */
    protected function setDefaultValues(Item $item) {
        foreach ($this->defaults as $key => $default) {
            if (!isset($item->$key)) {
                $item->$key = $default;
            }
        }
    }

    /**
     * @param Import $import
     * @param string $filename
     * @param array $record
     * @return string
     */
    protected function getItemImage(Import $import, $filename, array $record) {
        $path = sprintf(
            '%s/import/%s/%s/%s*.{jpg,jpeg,JPG,JPEG}',
            storage_path(),
            $import->dir_path,
            $filename,
            $this->getItemImageFilename($record)
        );

        $images =glob($path, GLOB_BRACE);
        return reset($images);
    }

    /**
     * @param Item $item
     * @param array $file
     * @return Image
     */
    protected function uploadImage($item, $file) {
        $uploaded_image = \Image::make(storage_path('app/' . $file['path']));

        if ($uploaded_image->width() > $uploaded_image->height()) {
            $uploaded_image->widen(self::IMAGE_MAX_SIZE);
        } else {
            $uploaded_image->heighten(self::IMAGE_MAX_SIZE);
        }

        $item->removeImage();

        $save_as = $item->getImagePath($full = true);
        return $uploaded_image->save($save_as);
    }
}