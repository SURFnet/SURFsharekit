<?php

namespace SilverStripe\EnvironmentExport;

trait Exportable {

    public function excludedFieldsForImport(): array {
        return [
            "ID"
        ];
    }
}