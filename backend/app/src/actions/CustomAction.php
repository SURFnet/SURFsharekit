<?php

namespace SurfSharekit\Action;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;

class CustomAction extends LiteralField
{
    use DefaultLink;

    /**
     * @var array
     */
    protected $params = [];


    /**
     * @var string
     */
    protected $buttonIcon = null;

    /**
     * Create a new action button.
     * @param action The method to call when the button is clicked
     * @param title The label on the button
     * @param extraClass A CSS class to apply to the button in addition to 'action'
     */
    public function __construct($action, $title = "", $extraClass = 'btn-primary')
    {
        parent::__construct($action, $title);
        $this->addExtraClass($extraClass);
    }

    public function performReadonlyTransformation()
    {
        return $this->castedCopy(self::class);
    }

    public function getLink()
    {
        if (!$this->link) {
            $this->link = $this->getControllerLink($this->name, $this->params);
        }
        return $this->link;
    }

    /**
     * Get an icon for this button
     *
     * @return string
     */
    public function getButtonIcon()
    {
        return $this->buttonIcon;
    }

    /**
     * Set an icon for this button
     *
     * Feel free to use SilverStripeIcons constants
     *
     * @param string $buttonIcon An icon for this button
     * @return $this
     */
    public function setButtonIcon(string $buttonIcon)
    {
        $this->buttonIcon = $buttonIcon;
        return $this;
    }

    public function Type()
    {
        return 'inline-action';
    }

    public function FieldHolder($properties = array())
    {
        $classes = $this->extraClass();
        if ($this->buttonIcon) {
            $classes .= " font-icon";
            $classes .= ' font-icon-' . $this->buttonIcon;
        }
        $link = $this->getLink();
        $attrs = '';
        if ($this->newWindow) {
            $attrs .= ' target="_blank"';
        }
        if ($this->readonly) {
            $attrs .= ' style="display:none"';
        }

        $backUrl = $_SERVER['REQUEST_URI'];
        $backUrl = str_replace('/ItemEditForm/', '', $backUrl);
        if (strpos($link, '?') !== false) {
            $link .= '&BackURL='. $backUrl;
        } else {
            $link .= '?BackURL='. $backUrl;
        }

        $content = '<a href="' . $link .'" class="btn ' . $classes . ' action no-ajax"' . $attrs . '>';
        $title = $this->content;
        $content .= $title;
        $content .= '</a>';
        $this->content = $content;

        return parent::FieldHolder($properties);
    }

    /**
     * Get the value of params
     *
     * @return  array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the value of params
     *
     * @param  array  $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        return $this;
    }

    public function setDisabled($disabled) {
        if ($disabled) {
            $this->addExtraClass('disabled');
        }

        return $this;
    }
}