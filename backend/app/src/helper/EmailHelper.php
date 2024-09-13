<?php

namespace SurfSharekit\Models\Helper;

use SurfSharekit\Models\Person;
use SurfSharekit\Models\SharekitEmail;

class EmailHelper {
    public static function sendEmail(array $emailAddressList, string $template, string $emailSubject, array $data = []) {
        if (count($emailAddressList)) {
            $email = SharekitEmail::create()
                ->setData($data)
                ->setTemplate($template);

            foreach ($emailAddressList as $emailAddress) {
                $email->addData('Receiver', Person::get()->filter('Email', $emailAddress)->first());

                $email
                    ->setSubject($emailSubject)
                    ->setTo($emailAddress);

                // redirection of mail is now implemented in SharekitEmail
                $email->send();
            }

            Logger::debugLog("Sending email to " . json_encode($emailAddressList));
        }
    }
}
