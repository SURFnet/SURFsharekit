<?php

namespace SurfSharekit\Models;

use SilverStripe\ORM\DataObject;
use Symfony\Component\Mime\MimeTypes;

class MimetypeObject extends DataObject {

    private static $table_name = 'SurfSharekit_MimetypeObject';

    private static $db = [
        'Extension' => 'Varchar(255)',
        'MimeType' => 'Varchar(255)',
        'Whitelist' => 'Boolean'
    ];

    private static $searchable_fields = [
        'Extension',
        'MimeType'
    ];

    public function requireDefaultRecords() {
        parent::requireDefaultRecords();

        $mimeTypesObj = new MimeTypes();
        $fileExtensions = [
            "css", "ace", "arc", "arj",
            "asf", "au", "avi", "bmp", "bz2",
            "cab", "cda", "csv", "dmg", "doc",
            "docx", "dotx", "flv", "gif", "gpx",
            "gz", "hqx", "ico", "jpeg", "jpg",
            "kml", "m4a", "m4v", "mid", "midi",
            "mkv", "mov", "mp3", "mp4", "mpa",
            "mpeg", "mpg", "ogg", "ogv", "pages",
            "pcx", "pdf", "png", "pps", "ppt",
            "pptx", "potx", "ra", "ram", "rm",
            "rtf", "sit", "sitx", "tar", "tgz",
            "tif", "tiff", "txt", "wav", "webm",
            "wma", "wmv", "xls", "xlsx", "xltx",
            "zip", "zipx", "ppsx", "mhtml", "mht",
            "odt", "sib", "zip", "epub", "imscc",
            "imscp", "md", "sav", "scorm", "graphql",
            "h5p", "apkg", "svg", "psd"
        ];

        foreach ($fileExtensions as $fileExtension) {
            $mimeTypes = $mimeTypesObj->getMimeTypes($fileExtension);
            $mimeType = $mimeTypes[0] ?? null;
            $existingMimetype = MimetypeObject::get()->filter('Extension', $fileExtension)->first();

            if (!$existingMimetype || $existingMimetype === null) {
                $mimetypeObject = MimetypeObject::create([
                    'Extension' => $fileExtension,
                    'MimeType' => $mimeType,
                    'Whitelist' => false
                ]);
                $mimetypeObject->write();
            }
        }
    }


    public function canCreate($member = null, $context = null) {
        return true;
    }

    public function canEdit($member = null) {
        return true;
    }

    public function canView($member = null) {
        return true;
    }
}
