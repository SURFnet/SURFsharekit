<?php

use PHPUnit\Framework\Assert;
use SilverStripe\api\Upload\Processors\CheckboxMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\DateMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\DisciplineMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\DoiMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\DropdownFieldMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\DropdownTagFieldMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\LectorateMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MultiSelectDropdownFieldMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MultiSelectInstituteSwitchMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MultiSelectPublisherSwitchMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\MultiSelectSuborganisationSwitchMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\NumberMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\RepoItemLearningObjectMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\RepoItemLinkMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\RepoItemMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\PersonInvolvedMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\RepoItemResearchObjectMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\RepoItemsMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\RightOfUseDropdownMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\SwitchRowFieldMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\TextAreaMetaFieldProcessor;
use SilverStripe\api\Upload\Processors\TextMetaFieldProcessor;
use SilverStripe\Core\ClassInfo;

class RepoItemTest extends UploadApiTest
{
    protected function setUp() {
        parent::setUp();
        $this->authenticate();
    }

//    public function testMetafieldValidations() {
//        $processors = ClassInfo::subclassesFor(MetaFieldProcessor::class, false);
//        $metaFieldTypes = [];
//
//        foreach ($processors as $processor) {
//            $metaFieldTypes[$processor] = \SilverStripe\Core\Config\Config::forClass($processor)->get('type');
//        }
//
//        $metaFieldTypes = array_unique($metaFieldTypes);
//
//        foreach ($metaFieldTypes as $processorClass => $metaFieldType) {
//            $metaField = \SurfSharekit\Models\MetaField::get()->filter([
//                'MetaFieldType.Key' => $metaFieldType
//            ])->first();
//
//            $this->assertNotNull($metaField, "Missing test field with type: $metaFieldType");
//            /** @var MetaFieldTestValue[] $testValues */
//            $testValues = $this->getProcessorTestValues($processorClass);
//
//            $this->assertNotEmpty($testValues, "No test values found for $processorClass");
//
//            foreach ($testValues as $testValue) {
//                /** @var MetaFieldProcessor $processor */
//                $processor = $processorClass::create($metaField, $testValue->getValue(), \SurfSharekit\Models\Institute::get()->first()->Uuid);
//                $this->assertThat($processor->validate()->hasErrors(), $testValue->getAssertAs(), "$processorClass value test failed... with value: " . json_encode($testValue->getValue()));
//            }
//        }
//    }

    public function testPostRepoItem() {
        $body = $this->getRepoItemRequestBody("e6c6c49e-186b-4b05-9ae6-2e777737bbd8", "9bc007df-82c3-4bcb-9b94-1dfd5d77f9ca");
        var_dump($body);

        $response = $this->post(
            '/api/upload/v1/repoitems',
            [],
            null,
            null,
            $body
        );

        print_r($response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function getRepoItemRequestBody(
        string $owner,
        string $insitute,
        string $repoItemType = "PublicationRecord"
    ) {
        return json_encode([
            "owner" => $owner,
            "institute" => $insitute,
            "repoItemType" => $repoItemType,
            "metadata" => [
                "title" => $this->getTextValue("This is a title"), // text
                "subtitle" => $this->getTextValue("This is a subtitle"), // text
                "summary" => $this->getTextValue("This is a summary"), // textArea
                "keywords" => $this->getDropdownTagValue(["A new tag"], \SurfSharekit\Models\MetaField::get()->find('JsonKey', 'keywords')->MetaFieldOptions()), // dropdownTag
                "dateIssued" => $this->getDateValue(), // Date
                "location" => $this->getTextValue("Leiden"), // text
                "pages" => $this->getNumberValue(), // number
                "type" => $this->getDropdownValue(\SurfSharekit\Models\MetaField::get()->find('JsonKey', 'type')->MetaFieldOptions(), 0), // dropdown
                "level" => $this->getMultiSelectDropdownValue(\SurfSharekit\Models\MetaField::get()->find('JsonKey', 'level')->MetaFieldOptions()), // multiSelectDropdown
                "language" => $this->getDropdownValue(\SurfSharekit\Models\MetaField::get()->find('JsonKey', 'language')->MetaFieldOptions(), 1), // dropdown
                "termsOfUse" => $this->getDropdownValue(\SurfSharekit\Models\MetaField::get()->find('JsonKey', 'termsOfUse')->MetaFieldOptions(), 1), // rightOfUse
                "theme" => $this->getMultiSelectDropdownValue(\SurfSharekit\Models\MetaField::get()->find('JsonKey', 'theme')->MetaFieldOptions(), 2), // multiSelectDropdown
                "permission" => true, // Checkbox
                "doi" => "10.1080/10509585.2015.1092083", // DOI
                "auteursEnBetrokkenen" => $this->getRepoItemPersonValues(), // PersonInvolved, Person
                "link" => $this->getRepoItemLinkValues(), // repoItemLink, RepoItem
                "departmentLectorateDiscipline" => $this->getMultiSelectSuborganisationSwitchValue($insitute), // MultiSelectSuborganisationSwitch, Scoped
                "education" => $this->getDropdownValue(\SurfSharekit\Models\Institute::get()->filter(['Level' => 'discipline', 'Institute.Uuid' => $insitute]), 1), // Discipline
                "publisher" => $this->getMultiSelectPublisherSwitchValue(), // MultiSelectPublisherSwitch, MultiSelectInstituteSwitch
                "lectorate" => $this->getDropdownValue(\SurfSharekit\Models\Institute::get()->filter(['Level' => 'lectorate', 'Institute.Uuid' => $insitute]), 1), // Lectorate
                // attachment
                // File
                // RepoItemLearningObject
                // RepoItemResearchObject
            ]
        ]);
    }

    private function getTextValue($text) {
        return $text;
    }

    private function getDropdownTagValue(array $newTags, \SilverStripe\ORM\DataList $tags) {
        $tags = [...$newTags, ...array_values($tags->limit(2)->column('Uuid'))];

        return $tags;
    }

    private function getRepoItemLinkValues() {
        $accessRightOptions = \SurfSharekit\Models\MetaFieldOption::get()->filter([
            "MetaFieldID" => \SurfSharekit\Models\MetaField::get()->filter('JsonKey', 'accessRights')->first()->ID
        ])->column('Uuid');

        $optionSelector = rand(0, 2);

        return [
            [
                "url" => "https://nu.nl",
                "urlName" => "NU.nl",
                "accessRights" => array_values($accessRightOptions)[$optionSelector],
                "important" => true
            ]
        ];
    }

    private function getRepoItemPersonValues() {
        return [
            [
                "persoon" => \SurfSharekit\Models\Person::get()->first()->Uuid,
                "alias" => "Alias",
                "role" => \SurfSharekit\Models\MetaField::get()->find('JsonKey', 'Role')->MetaFieldOptions()->first()->Uuid,
                "extern" => true
            ]
        ];
    }

    private function getMultiSelectPublisherSwitchValue() {
        $instituteUuids = \SurfSharekit\Models\Institute::get()->filter('InstituteID', 0)->limit(2)->column('Uuid');

        return [
            ...array_values($instituteUuids)
        ];
    }

    private function getDateValue($date = "now") {
        return (new DateTime($date))->format("d-m-Y");
    }

    private function getNumberValue($number = null) {
        return $number ?? rand(0, 10);
    }

    private function getMultiSelectSuborganisationSwitchValue($rootInstituteUuid) {
        $institutes = (new \SurfSharekit\Services\Discover\DiscoverUploadService())->getLevelBasedInstitutesUuids($rootInstituteUuid, ['lectorate','discipline','department']);

        return [
            ...array_values(array_slice($institutes, 0, 2))
        ];
    }

    private function getDropdownValue(\SilverStripe\ORM\DataList $values, $index = null) {
        if ($index == null) {
            return $values->first()->Uuid;
        }

        $vals = $values->column('Uuid');

        return $vals[$index];
    }

    private function getMultiSelectDropdownValue(\SilverStripe\ORM\DataList $values, $amount = 2) {
        $vals = $values->column('Uuid');

        return array_slice($vals, 0, $amount);
    }

    private function getProcessorTestValues($processClass) {
        $values = [
            CheckboxMetaFieldProcessor::class => [
                new MetaFieldTestValue(true, Assert::isTrue()),
                new MetaFieldTestValue(false, Assert::isTrue()),
                new MetaFieldTestValue('', Assert::isFalse()),
                new MetaFieldTestValue(0, Assert::isFalse()),
                new MetaFieldTestValue(1, Assert::isFalse()),
                new MetaFieldTestValue('true', Assert::isFalse()),
                new MetaFieldTestValue('false', Assert::isFalse()),
            ],

            DateMetaFieldProcessor::class => [
                new MetaFieldTestValue('2024-12-21', Assert::isTrue()),
                new MetaFieldTestValue('2024-02-04', Assert::isTrue()),
                new MetaFieldTestValue('2024-02', Assert::isFalse()),
                new MetaFieldTestValue('2024', Assert::isFalse()),
                new MetaFieldTestValue(true, Assert::isFalse())
            ],

            DisciplineMetaFieldProcessor::class => [

            ],

            DoiMetaFieldProcessor::class => [
                new MetaFieldTestValue('10.1080/10509585.2015.1092083', Assert::isTrue()),
                new MetaFieldTestValue('10509585.2015.1092083', Assert::isFalse()),
                new MetaFieldTestValue('https://doi.org/10.1080/10509585.2015.1092083', Assert::isFalse()),
                new MetaFieldTestValue(true, Assert::isFalse()),
            ],

            DropdownFieldMetaFieldProcessor::class => [

            ],

            DropdownTagFieldMetaFieldProcessor::class => [

            ],

            LectorateMetaFieldProcessor::class => [

            ],

            MultiSelectDropdownFieldMetaFieldProcessor::class => [

            ],

            MultiSelectInstituteSwitchMetaFieldProcessor::class => [

            ],

            MultiSelectPublisherSwitchMetaFieldProcessor::class => [

            ],

            MultiSelectSuborganisationSwitchMetaFieldProcessor::class => [

            ],

            NumberMetaFieldProcessor::class => [
                new MetaFieldTestValue(0, Assert::isTrue()),
                new MetaFieldTestValue(5, Assert::isTrue()),
                new MetaFieldTestValue(9, Assert::isTrue()),
                new MetaFieldTestValue(10, Assert::isTrue()),
                new MetaFieldTestValue(192, Assert::isTrue()),
                new MetaFieldTestValue('Lorem ipsum', Assert::isFalse()),
                new MetaFieldTestValue(false, Assert::isFalse()),
                new MetaFieldTestValue(true, Assert::isFalse())
            ],

            RepoItemLearningObjectMetaFieldProcessor::class => [

            ],

            RepoItemLinkMetaFieldProcessor::class => [
            ],

            RepoItemMetaFieldProcessor::class => [

            ],

            PersonInvolvedMetaFieldProcessor::class => [

            ],

            RepoItemResearchObjectMetaFieldProcessor::class => [

            ],

            RightOfUseDropdownMetaFieldProcessor::class => [

            ],

            SwitchRowFieldMetaFieldProcessor::class => [

            ],

            TextAreaMetaFieldProcessor::class => [
                new MetaFieldTestValue('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', Assert::isTrue()),
                new MetaFieldTestValue(999999999999, Assert::isTrue()),
                new MetaFieldTestValue(false, Assert::isFalse()),
            ],

            TextMetaFieldProcessor::class => [
                new MetaFieldTestValue('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', Assert::isTrue()),
                new MetaFieldTestValue(999999999999, Assert::isTrue()),
                new MetaFieldTestValue(false, Assert::isFalse()),
            ],
        ];

        return $values[$processClass] ?? null;
    }

}