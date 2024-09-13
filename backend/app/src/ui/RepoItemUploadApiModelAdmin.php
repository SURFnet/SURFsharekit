<?php

use SurfSharekit\Models\RepoItemUploadConfig;

class RepoItemUploadApiModelAdmin extends SingletonModelAdmin {
    private static $url_segment = "repoitemuploadapi";
    private static $menu_title = 'RepoItem Upload API';
    private static $menu_priority = 100;
    private static $page_length = 200;

    private static $managed_models = [
        RepoItemUploadConfig::class
    ];


    protected function getFormActions() {
        return null;
    }
}