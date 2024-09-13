<?php

use SilverStripe\Security\PermissionProvider;

class FrontEndPermissionProvider implements PermissionProvider {
    public function providePermissions() {
        return [
            'FRONTEND_VIEW_PUBLICATIONS' => [
                'name' => 'Shows publications page in frontend menu',
                'category' => 'Menu items'
            ],
            'FRONTEND_VIEW_INSTITUTES' => [
                'name' => 'Shows institute page in frontend menu',
                'category' => 'Menu items'
            ],
            'FRONTEND_VIEW_PROFILES' => [
                'name' => 'Shows profiles page in frontend menu',
                'category' => 'Menu items'
            ],
            'FRONTEND_VIEW_PROJECTS' => [
                'name' => 'Shows projects page in frontend menu',
                'category' => 'Menu items'
            ],
            'FRONTEND_VIEW_TEMPLATES' => [
                'name' => 'Shows templates page in frontend menu',
                'category' => 'Menu items'
            ],
            'FRONTEND_VIEW_REPORTS' => [
                'name' => 'Shows reports page in frontend menu',
                'category' => 'Menu items'
            ],
            'FRONTEND_VIEW_BIN' => [
                'name' => 'Shows removed items (bin) page in frontend menu',
                'category' => 'Menu items'
            ],
        ];
    }
}