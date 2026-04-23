<?php

namespace SilverStripe\ui;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Assets\File;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\RepoItem;
use SurfSharekit\Models\RepoItemFile;

class RepoItemFileModelAdmin extends ModelAdmin {
    private static $url_segment = "repoitemfile";
    private static $menu_title = 'Repository Item Files';
    private static $menu_priority = 300;
    private static $page_length = 200;

    private static $managed_models = [
        RepoItemFile::class
    ];

    public function getList()
    {
        $modelClass = $this->modelClass;

        if ($modelClass === RepoItemFile::class) {
            // Get IDs from the many_many join table
            $repoFileIds = DB::query("SELECT * FROM SurfSharekit_RepoItemFile")->column('ID');

            // Get Files with matching IDs
            if (count($repoFileIds)) {
                return RepoItemFile::get()->filter('ID', $repoFileIds);
            }
        }

        return parent::getList();
    }
}