<?php

namespace SurfSharekit\Models;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;

class CreateDefaultLanguageMetaFieldOptions extends BuildTask {
    protected $title = "Default Language MetaField Options";
    protected $description = "This task creates default metafield options records for the metafield Language and orders them by sortOrder with most used languages first.";

    public function run($request) {
        $sortOrder = 0;
        foreach ($this->sortedLanguages as $code => $langObject) {
            $option = MetaFieldOption::get()->filter([
                "Value" => $code,
                "MetaFieldUuid" => $this->languageMetafieldUuid
                ])->first();

            if ($option !== null) {
                $this->print("Option for language code '$code' already exists: {$langObject['nl']}, skipping...");
                continue;
            }

            // Uuid of language metafield
            $metaFieldLanguage = MetaField::get()->filter('Uuid', $this->languageMetafieldUuid)->first();

            $this->print("Creating option for language code '$code': {$langObject['nl']}");
            /** @var MetaFieldOption $newOption */
            $newOption = MetaFieldOption::create([
                "Value" => $code,
                "Label_NL" => $langObject['nl'],
                "Label_EN" => $langObject['en'],
                "SortOrder" => $sortOrder,
            ]);

            $this->print("Adding option to metafield Language: {$langObject['nl']}");
            $newOption->MetaFieldID = $metaFieldLanguage->ID;
            $newOption->write();

            $sortOrder++;
        }

        $this->print("Done!");
    }


    private function print($message) {
        if (Director::is_cli()) {
            echo $message;
        } else {
            echo "<span>$message</span><br>";
        }
    }

    private $languageMetafieldUuid = "32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e";

    private $sortedLanguages = [
        'nl' => ['nl' => 'Nederlands', 'en' => 'Dutch'],
        'en' => ['nl' => 'Engels', 'en' => 'English'],
        'de' => ['nl' => 'Duits', 'en' => 'German'],
        'fr' => ['nl' => 'Frans', 'en' => 'French'],
        'es' => ['nl' => 'Spaans', 'en' => 'Spanish'],
        'aa' => ['nl' => 'Afar', 'en' => 'Afar'],
        'ab' => ['nl' => 'Abchazisch', 'en' => 'Abkhaz'],
        'ae' => ['nl' => 'Avestisch', 'en' => 'Avestan'],
        'af' => ['nl' => 'Afrikaans', 'en' => 'Afrikaans'],
        'ak' => ['nl' => 'Akan', 'en' => 'Akan'],
        'am' => ['nl' => 'Amhaars', 'en' => 'Amharic'],
        'an' => ['nl' => 'Aragonees', 'en' => 'Aragonese'],
        'ar' => ['nl' => 'Arabisch', 'en' => 'Arabic'],
        'as' => ['nl' => 'Assamees', 'en' => 'Assamese'],
        'av' => ['nl' => 'Avaars', 'en' => 'Avaric'],
        'ay' => ['nl' => 'Aymara', 'en' => 'Aymara'],
        'az' => ['nl' => 'Azerbeidzjaans', 'en' => 'Azerbaijani'],
        'ba' => ['nl' => 'Basjkiers', 'en' => 'Bashkir'],
        'be' => ['nl' => 'Wit-Russisch', 'en' => 'Belarusian'],
        'bg' => ['nl' => 'Bulgaars', 'en' => 'Bulgarian'],
        'bh' => ['nl' => 'Bihari', 'en' => 'Bihari'],
        'bi' => ['nl' => 'Bislama', 'en' => 'Bislama'],
        'bm' => ['nl' => 'Bambara', 'en' => 'Bambara'],
        'bn' => ['nl' => 'Bengaals', 'en' => 'Bengali'],
        'bo' => ['nl' => 'Tibetaans', 'en' => 'Tibetan'],
        'br' => ['nl' => 'Bretons', 'en' => 'Breton'],
        'bs' => ['nl' => 'Bosnisch', 'en' => 'Bosnian'],
        'ca' => ['nl' => 'Catalaans', 'en' => 'Catalan'],
        'ce' => ['nl' => 'Tsjetsjeens', 'en' => 'Chechen'],
        'ch' => ['nl' => 'Chamorro', 'en' => 'Chamorro'],
        'co' => ['nl' => 'Corsicaans', 'en' => 'Corsican'],
        'cr' => ['nl' => 'Cree', 'en' => 'Cree'],
        'cs' => ['nl' => 'Tsjechisch', 'en' => 'Czech'],
        'cu' => ['nl' => 'Kerkslavisch', 'en' => 'Church Slavonic'],
        'cv' => ['nl' => 'Tsjoevasjisch', 'en' => 'Chuvash'],
        'cy' => ['nl' => 'Welsh', 'en' => 'Welsh'],
        'da' => ['nl' => 'Deens', 'en' => 'Danish'],
        'dv' => ['nl' => 'Divehi', 'en' => 'Divehi'],
        'dz' => ['nl' => 'Dzongkha', 'en' => 'Dzongkha'],
        'ee' => ['nl' => 'Ewe', 'en' => 'Ewe'],
        'el' => ['nl' => 'Grieks', 'en' => 'Greek'],
        'eo' => ['nl' => 'Esperanto', 'en' => 'Esperanto'],
        'et' => ['nl' => 'Estisch', 'en' => 'Estonian'],
        'eu' => ['nl' => 'Baskisch', 'en' => 'Basque'],
        'fa' => ['nl' => 'Perzisch', 'en' => 'Persian'],
        'ff' => ['nl' => 'Fula', 'en' => 'Fulah'],
        'fi' => ['nl' => 'Fins', 'en' => 'Finnish'],
        'fj' => ['nl' => 'Fijisch', 'en' => 'Fijian'],
        'fo' => ['nl' => 'Faeröers', 'en' => 'Faroese'],
        'fy' => ['nl' => 'Fries', 'en' => 'Frisian'],
        'ga' => ['nl' => 'Iers', 'en' => 'Irish'],
        'gd' => ['nl' => 'Schots-Gaelisch', 'en' => 'Scottish Gaelic'],
        'gl' => ['nl' => 'Galicisch', 'en' => 'Galician'],
        'gn' => ['nl' => 'Guaraní', 'en' => 'Guarani'],
        'gu' => ['nl' => 'Gujarati', 'en' => 'Gujarati'],
        'gv' => ['nl' => 'Manx', 'en' => 'Manx'],
        'ha' => ['nl' => 'Hausa', 'en' => 'Hausa'],
        'he' => ['nl' => 'Hebreeuws', 'en' => 'Hebrew'],
        'hi' => ['nl' => 'Hindi', 'en' => 'Hindi'],
        'hr' => ['nl' => 'Kroatisch', 'en' => 'Croatian'],
        'hu' => ['nl' => 'Hongaars', 'en' => 'Hungarian'],
        'hy' => ['nl' => 'Armeens', 'en' => 'Armenian'],
        'id' => ['nl' => 'Indonesisch', 'en' => 'Indonesian'],
        'is' => ['nl' => 'IJslands', 'en' => 'Icelandic'],
        'it' => ['nl' => 'Italiaans', 'en' => 'Italian'],
        'ja' => ['nl' => 'Japans', 'en' => 'Japanese'],
        'jv' => ['nl' => 'Javaans', 'en' => 'Javanese'],
        'ka' => ['nl' => 'Georgisch', 'en' => 'Georgian'],
        'kk' => ['nl' => 'Kazachs', 'en' => 'Kazakh'],
        'km' => ['nl' => 'Khmer', 'en' => 'Khmer'],
        'kn' => ['nl' => 'Kannada', 'en' => 'Kannada'],
        'ko' => ['nl' => 'Koreaans', 'en' => 'Korean'],
        'ku' => ['nl' => 'Koerdisch', 'en' => 'Kurdish'],
        'ky' => ['nl' => 'Kirgizisch', 'en' => 'Kyrgyz'],
        'la' => ['nl' => 'Latijn', 'en' => 'Latin'],
        'lb' => ['nl' => 'Luxemburgs', 'en' => 'Luxembourgish'],
        'li' => ['nl' => 'Limburgs', 'en' => 'Limburgish'],
        'ln' => ['nl' => 'Lingala', 'en' => 'Lingala'],
        'lo' => ['nl' => 'Laotiaans', 'en' => 'Lao'],
        'lt' => ['nl' => 'Litouws', 'en' => 'Lithuanian'],
        'lv' => ['nl' => 'Lets', 'en' => 'Latvian'],
        'mg' => ['nl' => 'Malagasi', 'en' => 'Malagasy'],
        'mi' => ['nl' => 'Maori', 'en' => 'Maori'],
        'mk' => ['nl' => 'Macedonisch', 'en' => 'Macedonian'],
        'ml' => ['nl' => 'Malayalam', 'en' => 'Malayalam'],
        'mn' => ['nl' => 'Mongools', 'en' => 'Mongolian'],
        'mr' => ['nl' => 'Marathi', 'en' => 'Marathi'],
        'ms' => ['nl' => 'Maleis', 'en' => 'Malay'],
        'mt' => ['nl' => 'Maltees', 'en' => 'Maltese'],
        'my' => ['nl' => 'Birmees', 'en' => 'Burmese'],
        'ne' => ['nl' => 'Nepalees', 'en' => 'Nepali'],
        'no' => ['nl' => 'Noors', 'en' => 'Norwegian'],
        'pl' => ['nl' => 'Pools', 'en' => 'Polish'],
        'pt' => ['nl' => 'Portugees', 'en' => 'Portuguese'],
        'ro' => ['nl' => 'Roemeens', 'en' => 'Romanian'],
        'ru' => ['nl' => 'Russisch', 'en' => 'Russian'],
        'sk' => ['nl' => 'Slowaaks', 'en' => 'Slovak'],
        'sl' => ['nl' => 'Sloveens', 'en' => 'Slovenian'],
        'sr' => ['nl' => 'Servisch', 'en' => 'Serbian'],
        'sv' => ['nl' => 'Zweeds', 'en' => 'Swedish'],
        'th' => ['nl' => 'Thai', 'en' => 'Thai'],
        'tr' => ['nl' => 'Turks', 'en' => 'Turkish'],
        'uk' => ['nl' => 'Oekraïens', 'en' => 'Ukrainian'],
        'ur' => ['nl' => 'Urdu', 'en' => 'Urdu'],
        'vi' => ['nl' => 'Vietnamees', 'en' => 'Vietnamese'],
        'zh' => ['nl' => 'Chinees', 'en' => 'Chinese'],
        'zu' => ['nl' => 'Zoeloe', 'en' => 'Zulu']
    ];
}