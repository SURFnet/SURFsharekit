<?php

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;
use SilverStripe\Reports\Report;
use SilverStripe\View\ArrayData;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;
use SurfSharekit\Models\RepoItem;

class CustomSideReport_RepoItems extends Report
{
// the name of the report
    public function title() {
        return 'Aantal opgevoerde materialen';
    }

    public function sourceRecords($params, $sort, $limit)
    {
        $monthParam = isset($params['Month'])?intval($params['Month']):0;
        $yearParam = isset($params['Year'])?intval($params['Year']):date('Y');

        if($monthParam == 0){
            $dateFrom  = $yearParam . '-01-01';
            $dateUntil  = $yearParam+1 . '-01-01';
        }
        else{
            if(strlen($monthParam) == 1){
                $monthParam = '0' . $monthParam;
            }
            $dateFrom = $yearParam . '-' . $monthParam . '-01';
            $dateFromObj = new DateTime($dateFrom);
            $dateUntil = $dateFromObj->add(new DateInterval('P1M'))->format('Y-m-d');
        }

        $returnSet = ArrayList::create();
        $repoTypes = Constants::MAIN_REPOTYPES;
        $bindClause = implode(',', array_fill(0, count($repoTypes), '?'));
        $bindValues = $repoTypes;
        $bindValues[] = $dateFrom;
        $bindValues[] = $dateUntil;
        $records = DB::prepared_query("SELECT RepoType, DATE_FORMAT(Created, '%Y-%m') as YearMonth, COUNT('ID') as `Count` FROM " . (new DataObjectSchema)->tableName(RepoItem::class) . " WHERE RepoType in ($bindClause) and Created >= ? and Created < ? GROUP BY RepoType, DATE_FORMAT(Created, '%Y-%m')", $bindValues);
        Logger::debugLog(DB::$lastQuery);

        $resultArray = [];
        foreach($records as $record){
            if(!array_key_exists($record['YearMonth'], $resultArray)){
                $resultArray[$record['YearMonth']] = [];
            }
            $resultArray[$record['YearMonth']][$record['RepoType']] = $record['Count'];
        }

        foreach($resultArray as $yearMonth => $result){
            $resultObj = ['Month' => $yearMonth];
            foreach($repoTypes as $repoType){
                $resultObj[$repoType] = array_key_exists($repoType, $result)?$result[$repoType]:0;
            }
            Logger::debugLog($resultObj);
            $arrayDataRecord = ArrayData::create($resultObj);
            $returnSet->push($arrayDataRecord);
        }
        return $returnSet;
    }
    public function columns()
    {
        $fields['Month'] = ['title' => 'Maand'];
        foreach(Constants::MAIN_REPOTYPES as $repoType){
            $fields[$repoType] = ['title' => $repoType];
        }

        return $fields;
    }
    public function parameterFields()
    {
        $years = [];
        for($year = Date('Y'); $year >= 2010 ; $year--){
            $years[(string)$year] = $year;
        }
        return FieldList::create(
            DropdownField::create('Month', 'Month', ['0' => 'Alle maanden','1' => 'Januari', '2' => 'Februari', '3' => 'Maart', '4' => 'April','5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Augustus', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'December']),
            DropdownField::create(
                'Year',
                'Jaar',
                $years,
                Date('Y')
            )
        );
    }
}
