<?php

namespace SilverStripe\actions;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\registries\BlueprintConverterRegistry;
use SilverStripe\Tasks\ConvertBlueprintToDataObjectTask;

class ConvertBlueprintGridFieldButton implements GridField_HTMLProvider, GridField_ActionProvider
{
    public function getHTMLFragments($gridField)
    {
        $modelClass = $gridField->getModelClass();
        $converter = BlueprintConverterRegistry::getConverter($modelClass);

        if (!$converter) {
            return [];
        }

        $targetClass = $converter->getTargetClass();
        $buttonLabel = "Convert to " . (new $targetClass)->plural_name();

        $button = new GridField_FormAction(
            $gridField,
            'convertblueprints',
            $buttonLabel,
            'convertblueprints',
            null
        );

        $button->addExtraClass('btn btn-secondary no-ajax font-icon-down-circled action_export');
        $button->setAttribute('data-icon', 'arrow-circle-right');

        return [
            'buttons-before-left' => $button->Field()
        ];
    }

    public function getActions($gridField)
    {
        return ['convertblueprints'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName !== 'convertblueprints') return;

        $modelClass = $gridField->getModelClass();
        $request = new HTTPRequest('GET', '', ['blueprintClass' => $modelClass]);

        $task = ConvertBlueprintToDataObjectTask::create();
        $task->run($request);

        $controller = Controller::curr();
        $controller->getRequest()->getSession()->set('ConvertedMessage', 'Conversion completed successfully.');

        return $controller->redirectBack();
    }
}