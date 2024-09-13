<?php

namespace Zooma\SilverStripe\ModelAdmin;

use SilverStripe\Core\Environment;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Reports\Report;

class LogReport extends Report {
    public function title() {
        return "Logreport";
    }

    public function description() {
        return "Shows the last 50 rows of the silverstripe.log file";
    }

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $test = $fields->fieldByName('ReportDescription');
        $test->setValue("<span style='display: block; font-size: 16px; margin-bottom: 50px;'>
            <span style='display: block;'>Here you can see the last 50 currently in the log file.</span>
        </span>");
        return $fields;
    }

    public function sourceRecords($params, $sort, $limit) {
        $result = new ArrayList();

        $f = fopen(Environment::getEnv("LOCAL_LOG_PATH"), 'r');
        $cursor = -1;
        fseek($f, $cursor, SEEK_END);
        $char = fgetc($f);
        /**
         * Trim trailing newline chars of the file
         */
        while ($char === "\n" || $char === "\r") {
            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);
        }
        /**
         * Read until the start of file or first newline char
         */
        $linesRead = [];
        $line = '';

        /**
         * aaa/n/r
         * 123
         */
        while ($char !== false) {
            if (count($linesRead) >= 50) {
                break;
            }

            fseek($f, $cursor--, SEEK_END);
            $char = fgetc($f);

            if ($char === "\n" || $char === "\r") {
                if (strlen($line)) {
                    $linesRead[] = $line;
                }
                $line = '';
            } else {
                $line = $char . $line;
            }
        }

        fclose($f);

        foreach ($linesRead as $line) {
            $result[] = [
                'Text' => $line
            ];
        }

        return $result;
    }

    public function columns() {
        $fields['Text'] = ['title' => 'Log text'];
        return $fields;
    }
}