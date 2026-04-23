<?php

namespace SilverStripe\EnvironmentExport\Models;

use SilverStripe\api\Upload\Data\Traits\SerializableTrait;
use SilverStripe\Security\Member;
use SurfSharekit\Models\Helper\Logger;
use Throwable;

class Export {
    use SerializableTrait;

    private ExportMetadata $metadata;
    private array $data;

    public function __construct(ExportMetadata $metadata, array $data) {
        $this->metadata = $metadata;
        $this->data = $data;
        $this->sortData();
    }

    public static function fromJson($json): ?Export {
        try {
            $decodedJson = json_decode($json, true);

            $metadata = $decodedJson["meta"] ?? [];
            $exportMetadata = ExportMetadata::fromJson($metadata);

            $exportData = $decodedJson["data"];
            $exportDataArray = [];
            foreach ($exportData as $exportDataItem) {
                $exportDataArray[] = ExportedDataObject::fromJson($exportDataItem);
            }

            return new Export(
                $exportMetadata,
                $exportDataArray
            );
        } catch(Throwable $e) {
            Logger::debugLog("Failed deserializing Export: {$e->getMessage()}");
            return null;
        }
    }

    public function getMetadata(): ExportMetadata {
        return $this->metadata;
    }

    public function getData(): array {
        return $this->data;
    }

    private function sortData() {
        /** Members should *always* be imported after the groups and roles have been generated, so move to end */
        usort($this->data, function($a, $b) {
            /** @var ExportedDataObject $a */
            /** @var ExportedDataObject $b */
            if ($a->getMetadata()->getClass() == Member::class) {
                return 1;
            } else if ($b->getMetadata()->getClass() == Member::class) {
                return -1;
            } else {
                return 0;
            }
        });
    }

}