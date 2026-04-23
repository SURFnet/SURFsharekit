<?php

namespace SurfSharekit\Action;

use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;

class BlueprintCopyButton
{
    /**
     * Generates the Copy JSON Button and the Textarea Field.
     *
     * @param string $json The JSON data to be copied.
     * @param string $buttonText The text to display on the button (e.g., 'Kopieer JSON').
     * @return array
     */
    public static function create(string $json, string $buttonText = 'Kopieer JSON'): array
    {
        $copyButton = LiteralField::create(
            'CopyButton',
            sprintf(
                '<button type="button"
            style="background-color: #0d5aa7; color: white; border: none; padding: 5px 10px; margin-bottom: 30px;" 
            onclick="var origText = this.innerHTML; this.style.backgroundColor=\'white\'; this.style.color=\'blue\'; this.innerHTML=\'Gekopieërd\'; navigator.clipboard.writeText(`%s`); setTimeout(() => { this.style.backgroundColor=\'#0d5aa7\'; this.style.color=\'white\'; this.innerHTML = origText; }, 500);">
            %s
        </button>',
                htmlspecialchars($json, ENT_QUOTES),
                $buttonText
            )
        );

        $textareaField = TextareaField::create('Blueprint', 'Blueprint JSON')
            ->setValue($json)
            ->setDescription('This is the full JSON representation of the object. Copy this object into a new Blueprint object, convert it, and it creates a new instance of this object.')
            ->setRows(substr_count($json, "\n") + 2)
            ->setReadonly(true);

        return [$copyButton, $textareaField];
    }
}