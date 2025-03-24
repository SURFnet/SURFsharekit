<?php

namespace SurfSharekit\Models\Helper;

use App\Product;
use SurfSharekit\Models\MimetypeObject;

class MimetypeHelper {

    public static function getWhitelistedExtensions() {
        return array_map('strtolower', MimetypeObject::get()->filter('Whitelist', true)->column('Extension'));
    }

    public static function getMimeType(?string $fileName, ?string $extension){

        if ($fileName) {
            $fileTypeExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $mimetypeObject = MimetypeObject::get()
                ->filter([
                    'Extension' => $fileTypeExtension,
                    'Whitelist' => true
                ])->first();
        } else {
            if ($extension) {
                $mimetypeObject = MimetypeObject::get()
                    ->filter([
                        'Extension' =>  $extension,
                        'Whitelist' => true
                    ])->first();
            }
        }

        if ($mimetypeObject && $mimetypeObject->exists() && $mimetypeObject->MimeType) {
            if($mimetypeObject->MimeType == "application/x-gzip") {
                return "application/zip";
            }

            return $mimetypeObject->MimeType;
        }

        return null;
    }
}
