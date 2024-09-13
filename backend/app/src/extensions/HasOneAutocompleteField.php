<?php

namespace SurfSharekit\Extensions;

use NathanCox\HasOneAutocompleteField\Forms\HasOneAutocompleteField as Base;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

class HasOneAutocompleteField extends Base
{
    protected $placeholderText = "Search & select an Option";

    public function Field($properties = []) {
        Requirements::javascript('_resources/themes/surfsharekit/javascript/hasoneautocompletefield.js');
        Requirements::css('nathancox/hasoneautocompletefield: client/dist/css/hasoneautocompletefield.css');

        $fields = FieldGroup::create($this->name);
        $fields->setName($this->name);

        $fields->push($searchField = TextField::create($this->name.'Search', ''));
        $searchField->setAttribute('style', 'display: block; width: 100%;');
        $searchField->setAttribute('data-search-url', $this->Link('search'));
        $searchField->setAttribute('size', 40);
        $searchField->setAttribute('placeholder', $this->placeholderText);
        $searchField->addExtraClass('no-change-track hasoneautocomplete-search');

        $fields->push($idField = HiddenField::create($this->name, ''));
        $idField->addExtraClass('hasoneautocomplete-id');

        if ($this->value) {
            $idField->setValue($this->value);
        }

        if ($this->clearButtonEnabled === true) {
            $fields->push($clearField = FormAction::create($this->name.'Clear', ''));
            $clearField->setUseButtonTag(true);
            $clearField->setButtonContent('Clear');
            $clearField->addExtraClass('clear hasoneautocomplete-clearbutton btn-outline-danger btn-hide-outline action--delete btn-sm');

            if (intval($this->value) === 0) {
                $clearField->setAttribute('style', 'display:none;');
            }
        }

        return $fields;
    }
}