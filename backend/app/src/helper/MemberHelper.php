<?php

namespace SurfSharekit\Models\Helper;

use SilverStripe\Security\Member;
use SurfSharekit\Models\Person;

class MemberHelper {

    static function getMemberFullName(Member $member) {

        $nameValues = [];
        if(!empty($firstName = trim($member->getField('FirstName')))){
            $nameValues[] = $firstName;
        }
        if(!empty($surname = trim($member->getField('Surname')))){
            $nameValues[] = $surname;
        }

        $fullName = implode(' ', $nameValues);
        return $fullName;
    }

    static function getPersonFullName(Person $member) {

        $nameValues = [];
        if(!empty($initials = trim($member->getField('Initials')))){
            $nameValues[] = $initials;
        }
        elseif(!empty($firstName = trim($member->getField('FirstName')))){
            $nameValues[] = $firstName;
        }
        if(!empty($surnamePrefix = trim($member->getField('SurnamePrefix')))){
            $nameValues[] = $surnamePrefix;
        }
        if(!empty($surname = trim($member->getField('Surname')))){
            $nameValues[] = $surname;
        }

        $fullName = implode(' ', $nameValues);
        return $fullName;
    }
}