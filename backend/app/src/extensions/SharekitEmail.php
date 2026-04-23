<?php

namespace SurfSharekit\Models;

use Exception;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mime\Test\Constraint\EmailAttachmentCount;

class SharekitEmail extends Email
{
    private $template;

    public function send(): void {
        $this->setHTMLTemplate("Email\\Base");
        $this->addAttachment(Director::publicFolder() . "/_resources/themes/surfsharekit/images/email-logo.png", 'logo.png', cid: 'logo');
        $this->setData(ArrayData::create([
            'Content' => $this->getContent(),
        ]));

        // if env is not life we redirect mail to set config email and add some data
        if (Environment::getenv('APPLICATION_ENVIRONMENT') != 'live') {
            $environment = Environment::getenv('APPLICATION_ENVIRONMENT');
            $recipient = SiteConfig::current_site_config()->getField('Email');

            if (empty($recipient)) {
                throw new \Exception("Config email empty");
            }

            $this->setSubject("[$environment] " . $this->getSubject());

            // set data to show banner
            $this->addData('OriginalRecipient', implode(', ', array_keys($this->getTo())));

            // reset to
            $this->setTo($recipient);
        }

        parent::send();
    }

    public function addAttachment($path, $alias = null, $mime = null, $cid = null): static {
        $attachment = new DataPart(new File($path), $alias, $mime);
        if ($cid) {
            $attachment->setContentId("logo@image");
        }
        return $this->addPart($attachment);
    }

    /**
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * @param string $template
     * @return SharekitEmail
     */
    public function setTemplate(string $template): self {
        $this->template = $template;

        return $this;
    }

    private function getContent(): String {
        if ($this->getTemplate() === null) {
            throw new \Exception("Template not set for SharekitEmail");
        }

        $this->addData('DashboardLink', Environment::getEnv("FRONTEND_BASE_URL") . '/dashboard');
        $this->addData('ProfileLink', Environment::getEnv("FRONTEND_BASE_URL") . '/profile');
        $this->addData('PreferencesLink', Environment::getEnv("FRONTEND_BASE_URL") . '/profile#notifications');

        return $this->getData()->renderWith($this->getTemplate());
    }

    public function getData(): ViewableData {
        $data = parent::getData();
        if ($data instanceof ViewableData) {
            return $data;
        }

        if (is_array($data)) {
            return ArrayData::create($data);
        }

        throw new Exception("Data in SharekitEmail should be either a ViewableData or an array");
    }
}