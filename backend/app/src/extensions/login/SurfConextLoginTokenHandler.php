<?php

namespace SurfSharekit\Extensions;

use Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler;
use Bigfork\SilverStripeOAuth\Client\Model\Passport;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;

class SurfConextLoginTokenHandler extends LoginTokenHandler
{
    protected function findOrCreateMember(AccessToken $token, AbstractProvider $provider)
    {
        $user = $provider->getResourceOwner($token);

        /** @var Passport $passport */
        $passport = Passport::get()->filter([
            'Identifier' => $user->getId()
        ])->first();

        if (!$passport) {
            /** @var Member $member */
            if (null === $member = $this->findMember($token, $provider)) {
                throw new ValidationException("Member has no permission to access the CMS");
            }

            if (!$member->isWorksAdmin() && !$member->isDefaultAdmin()) {
                throw new ValidationException("Member has no permission to access the CMS");
            }

            // Create a passport for the new member
            $passport = Passport::create()->update([
                'Identifier' => $user->getId(),
                'MemberID' => $member->ID
            ]);
            $passport->write();
        }
        
        if (!$passport->Member()->isWorksAdmin() && !$passport->Member()->isDefaultAdmin()) {
            $passport->delete();
            throw new ValidationException("Member has no permission to access the CMS");
        }

        return $passport->Member();
    }

    private function findMember($token, $provider) {
        $user = $provider->getResourceOwner($token);

        return Member::get()->find('Email', $user->getEmail());
    }

}