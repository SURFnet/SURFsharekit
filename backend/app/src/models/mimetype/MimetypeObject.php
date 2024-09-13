<?php

namespace SurfSharekit\Models;

use Mimey\MimeTypes;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use SurfSharekit\Extensions\DatabaseAdminExtension;
use SurfSharekit\Models\Helper\Constants;

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

        $mimeTypes = new MimeTypes();
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
            "imscp", "md", "sav", "scorm", "graphql"
        ];

        foreach ($fileExtensions as $fileExtension) {
            $mimeType = $mimeTypes->getMimeType($fileExtension);
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
