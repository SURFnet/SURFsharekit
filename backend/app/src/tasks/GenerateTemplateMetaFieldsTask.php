<?php
namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\Template;

class GenerateTemplateMetaFieldsTask extends BuildTask{

    protected $title = 'Generate TemplateMetafields Task';
    protected $description = 'This task (re)generates metafields for all templates from top to bottom';

    protected $enabled = true;


    function run($request) {
        set_time_limit(0);

        $rootTemplates = Template::get()->filter(['InstituteId'=> 0]);
        /** @var Template $rootTemplate */
        foreach($rootTemplates as $rootTemplate){
            $rootTemplate->write(false, false, true);
        }
    }

}