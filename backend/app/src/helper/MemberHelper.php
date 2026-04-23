<?php

namespace SurfSharekit\Models\Helper;

use SilverStripe\Security\Member;
use SurfSharekit\Models\Person;

class MemberHelper {

    static function getMemberFullName(Member $member) {

        $nameValues = [];
        if(!empty($firstName = trim($member->FirstName ?? ""))){
            $nameValues[] = $firstName;
        }
        if(!empty($surname = trim($member->Surname ?? ""))){
            $nameValues[] = $surname;
        }

        $fullName = implode(' ', $nameValues);
        return $fullName;
    }

    static function getPersonFullName(Person $member) {

        $nameValues = [];
        if(!empty($firstName = trim($member->FirstName ?? ""))){
            $nameValues[] = $firstName;
        }
        elseif(!empty($initials = trim($member->Initials ?? ""))){
            $nameValues[] = $initials;
        }
        if(!empty($surnamePrefix = trim($member->SurnamePrefix ?? ""))){
            $nameValues[] = $surnamePrefix;
        }
        if(!empty($surname = trim($member->Surname ?? ""))){
            $nameValues[] = $surname;
        }

        $fullName = implode(' ', $nameValues);
        return $fullName;
    }
}