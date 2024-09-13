<?php

namespace SurfSharekit\Tasks;

use SilverStripe\Dev\BuildTask;
use SurfSharekit\Models\SharekitEmail;

class MailTestTask extends BuildTask
{

    public function run($request) {
        $email = SharekitEmail::create();
        $email->setTo('menno@zooma.nl');
        $email->setData([
            'GroupName' => "TestGroup",
            'PersonName' => "TestPerson"
        ]);
        $email->setTemplate('Email\\PersonClaimApproved');
        $email->send();
    }
}