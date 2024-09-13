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

class SharekitEmail extends Email
{
    private $template;

    public function send() {
        $this->setHTMLTemplate("Email\\Base");
        $this->addAttachment(Director::publicFolder() . "/_resources/themes/surfsharekit/images/email-logo.png", 'logo.png', 'logo');
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

        return parent::send();
    }

    public function addAttachment($path, $alias = null, $cid = null, $mime = null)
    {
        $attachment = \Swift_Attachment::fromPath($path);
        if ($alias) {
            $attachment->setFilename($alias);
        }
        if ($mime) {
            $attachment->setContentType($mime);
        }
        if ($cid) {
            $attachment->setId("logo@image");
        }
        $this->getSwiftMessage()->attach($attachment);

        return $this;
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
        $this->addData('PreferencesLink', Environment::getEnv("FRONTEND_BASE_URL") . '/profile#notifications');

        return $this->getData()->renderWith($this->getTemplate());
    }

    public function getData() {
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