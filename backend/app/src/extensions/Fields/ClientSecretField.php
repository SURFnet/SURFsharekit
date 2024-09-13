<?php

namespace SurfSharekit\Extensions\Fields;

use Ramsey\Uuid\Uuid;
use SilverStripe\Forms\PasswordField;
use SilverStripe\View\Requirements;

class ClientSecretField extends PasswordField
{
    private string $fieldId;
    public function __construct($name, $title = null, $value = '')
    {
        $this->fieldId =  hash('crc32', Uuid::uuid4()->toString());
        parent::__construct($name, $title, $value);

        Requirements::css("public/_resources/themes/surfsharekit/css/Fields/ClientSecretField.css");
        Requirements::javascript("public/_resources/themes/surfsharekit/javascript/Fields/ClientSecretField.js");

        $this->setAttribute('id', $this->fieldId);
        $this->setTemplate('Fields/ClientSecretField');
    }

    public function getTemplates()
    {
        return [$this->getTemplate()];
    }

    public function FieldID() {
        return $this->fieldId;
    }
}
