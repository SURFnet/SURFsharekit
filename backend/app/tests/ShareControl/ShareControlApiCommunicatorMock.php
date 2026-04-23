<?php

namespace ShareControl;

use SilverStripe\Core\Injector\Injectable;
use SurfSharekit\ShareControl\Model\Item;
use SurfSharekit\ShareControl\Model\Author;
use SurfSharekit\ShareControl\Model\File;
use SurfSharekit\ShareControl\Model\Availability;
use SurfSharekit\ShareControl\ShareControlApiCommunicator;
use DateTime;

class ShareControlApiCommunicatorMock implements ShareControlApiCommunicator {
    use Injectable;

    public static $lastSerachTerm = null;
    public static $lastPage = null;
    public static $lastPageSize = null;

    public static function reset() {
        static::$lastSerachTerm = null;
        static::$lastPage = null;
        static::$lastPageSize = null;
    }

    public static function searchItems(string $iBron, string $searchTerm, int $page = 0, int $pageSize = 0): array {
        static::$lastSerachTerm = $searchTerm;
        static::$lastPage = $page;
        static::$lastPageSize = $pageSize;

        $obj1 = new Item();
        $obj1->uuid = "20fcb4b2-8ff7-48a8-80d4-cab1fd986f22";
        $obj1->ibronId = "ibron-demo";
        $obj1->sourceId = "hbo-kennisbank";
        $obj1->type = "STUDENT_WORK";
        $obj1->originalIdentifier = "oai:hbokennisbank.nl:hanzepure:oai:research.hanze.nl:publications/905cc6eb-b66c-4868-b213-9f56caceabb9";
        $obj1->firstHarvested = new DateTime("2025-05-20T08:01:31.693951Z");
        $obj1->lastModified = new DateTime("2025-05-20T08:02:01.697473Z");
        $obj1->sskRepoItemUuid = null;
        $obj1->lastModifiedInSource = new DateTime("2024-06-26T08:38:01Z");
        $obj1->deletedBySource = false;
        $obj1->title = "Big data in 60 minuten";
        $obj1->subTitle = "";
        $obj1->abstract_ = "Technisch gezien hebben mensen heden ten dage meer tijd; we slapen minder dan 20 jaar geleden en efficiency viert hoogtij. Toch is tijdgebrek een veelgehoorde klacht. Vandaar dat de reeks 'digitale trends en tools in 60 minuten' hierop inspringt. Hiermee kan de lezer in korte tijd inzicht krijgen in hedendaagse technologische vraagstukken. 'Meer weten van big data' geschreven door Dik Bijl is de laatste loot aan deze reeks van uitgeverij Haystack. Uiteraard zijn er meer boeken geschreven over big data, maar zoals Bijl stelt: \"Dit is het eerste boekje dat je in een mum van tijd laat kennis maken met big data\". In slechts zes hoofdstukken wordt de lezer ingewijd in algoritmes, machine learning en digitale transformaties. Uiteraard wordt stilgestaan hoe je zelf aan de slag kan met big data om uiteindelijk de eigen organisatie te professionaliseren. Eerlijk is eerlijk, de kracht van het format is om moeilijke klinkende digitale begrippen binnen 60 minuten uit te leggen. Toch is de vluchtigheid van deze 60 minuten-reeks een valkuil. Niet gek want het menselijke werkgeheugen is kortstondig en kwetsbaar. Het duurt enige uren tot enkele dagen voordat nieuwe informatie betrouwbaar wordt opgenomen in ons lange termijn geheugen. Al met al is meer weten van big data in 60 minuten aardig om snel kennis te verkrijgen maar om echt te beklijven, heb ik het toch tweemaal moeten lezen. En zo was mijn tijdwinst weg!";
        $obj1->language = "nl";
        $obj1->keywords = ["facility management", "informatiemaatschappij", "information society"];
        $obj1->authors = [
            (function() {
                $author = new Author();
                $author->uuid = "089901b3-e936-4cbb-9286-7b7cfdf46fe8";
                $author->fullName = "";
                $author->middleName = null;
                $author->firstName = "Rachel";
                $author->lastName = "Kuijlenburg";
                $author->email = null;
                $author->roleTerm = "aut";
                $author->originalIdentifier = null;
                return $author;
            })()
        ];
        $obj1->files = [
            (function() {
                $file = new File();
                $file->uuid = "11803dad-9df6-4518-bbe4-fd53a1586db4";
                $file->fileName = null;
                $file->accessRight = null;
                $file->originalUrl = "https://research.hanze.nl/ws/files/27664775/Big_data_in_60_min_facto.pdf";
                $file->downloadUrl = null;
                $file->resourceMimeType = "application/pdf";
                $file->originalIdentifier = null;
                $file->lastVerified = null;
                $file->fileNotFoundFirstVerifiedAt = null;
                return $file;
            })()
        ];
        $obj1->availabilities = [
            (function() {
                $availability = new Availability();
                $availability->nlbronId = "nlbron-surf-dev";
                $availability->startDate = null;
                $availability->endDate = null;
                $availability->months = null;
                return $availability;
            })()
        ];
        $obj1->courseId = null;
        $obj1->purgeAfter = null;
        $obj1->purged = false;
        $obj1->expiryDateForNlBron = null;
        $obj1->sourceObjects = [];

        $obj2 = new Item();
        $obj2->uuid = "22712826-eaae-49b8-85c7-41ed2e5f5a70";
        $obj2->ibronId = "ibron-demo";
        $obj2->sourceId = "hbo-kennisbank";
        $obj2->type = "STUDENT_WORK";
        $obj2->originalIdentifier = "oai:hbokennisbank.nl:sharekit_hh:oai:surfsharekit.nl:c1515bdb-50c5-48d8-98fd-98c4da92aa6e";
        $obj2->firstHarvested = new DateTime("2025-05-20T08:00:09.726628Z");
        $obj2->lastModified = new DateTime("2025-05-20T08:00:39.729810Z");
        $obj2->sskRepoItemUuid = null;
        $obj2->lastModifiedInSource = new DateTime("2025-02-26T09:19:53Z");
        $obj2->deletedBySource = false;
        $obj2->title = "Vernieuwing in de bewegingsregistratie van mensen";
        $obj2->subTitle = "advies op het gebruik van Machine Learning in combinatie met het Awinda-meetsysteem";
        $obj2->abstract_ = "Door verbeteringen in Inertia Mesurement Unit (IMU) technologie, is hoogwaardig observationeel onderzoek mogelijk. Echter, bij observationeel onderzoek moet veel meer databewerking gedaan worden. Bedrijven zoals Movella zijn geïnteresseerd in de implementatie van Machine Learning (ML), waarbij verschillende bewegingen automatisch gedetecteerd worden aan de hand van de data uit op het lichaam geplaatste IMU's, om werk van de gebruiker te verlichten. Een ML algoritme zou hierbij breed inzetbaar en niet complex van aard moeten zijn, zodat de gebruiker zelf kan selecteren wat automatisch gedetecteerd moet worden. In dit project wordt, aan de hand van een casusopdracht waarbij blessuregevoelige bewegingen in het voetbal worden geclassificeerd, gekeken of een algemeen inzetbaar ML model gemaakt kan worden van minder complex ML algoritme.\nDrie voetballers zijn met behulp van het Awinda-meetsysteem geobserveerd tijdens het trainen. Data van deze metingen is handmatig gecategoriseerd in vier verschillende bewegingen: drie blessuregevoelige bewegingen in de vorm van schoppen, veranderen van richting op snelheid en rennen en een vierde beweging: lopen. Parameters zijn softwarematig geclusterd tot features. Hierna zijn datapunten na het filteren toegekend aan één van de vier bewegingen en gebalanceerd en geschaald. Voor het model is gekozen voor een Support Vector Classifier, in combinatie met Recursive Feature Elimination en Kruisvalidatie.\nTer evaluatie is gekeken naar de nauwkeurigheid van het model en of de features een significant effect hadden voor het classificeren van de datapunten. De nauwkeurigheid van het model was 52,8% (SD: 0,043). De P-waarde voor de significantie van de features was 0,528 (SD: 0,010). Het model was niet voldoende om bewegingen te classificeren. Dit komt hoogstwaarschijnlijk omdat elk datapunt apart werd geëvalueerd in plaats van een serie van opvolgende datapunten, oftewel timeseries-data. Verder onderzoek moet kijken naar algoritmes waarbij timeseries-data geanalyseerd kan worden, waarbij meer data beschikbaar is voor deze analyse. ";
        $obj2->language = "nl";
        $obj2->keywords = ["data-analyse", "machinaal leren", "meetinstrumenten", "voetbalblessures", "voetbaltraining"];
        $obj2->authors = [
            (function() {
                $author = new Author();
                $author->uuid = "f19f2a9b-063a-4291-9ab5-dd2dbf6975c7";
                $author->fullName = "Shane Lagerweij";
                $author->middleName = null;
                $author->firstName = "Shane";
                $author->lastName = "Lagerweij";
                $author->email = null;
                $author->roleTerm = "aut";
                $author->originalIdentifier = null;
                return $author;
            })(),
            (function() {
                $author = new Author();
                $author->uuid = "75be2e45-f81d-4475-b619-9f80c0185f24";
                $author->fullName = "Erik Wilmes";
                $author->middleName = null;
                $author->firstName = "Erik";
                $author->lastName = "Wilmes";
                $author->email = null;
                $author->roleTerm = "ths";
                $author->originalIdentifier = null;
                return $author;
            })()
        ];
        $obj2->files = [
            (function() {
                $file = new File();
                $file->uuid = "5c9803a0-6102-4558-81b2-35e44d65c158";
                $file->fileName = null;
                $file->accessRight = null;
                $file->originalUrl = "https://surfsharekit.nl/objectstore/564bc7fa-4695-44fd-b5e8-719eeef9b229";
                $file->downloadUrl = null;
                $file->resourceMimeType = "application/pdf";
                $file->originalIdentifier = null;
                $file->lastVerified = null;
                $file->fileNotFoundFirstVerifiedAt = null;
                return $file;
            })()
        ];
        $obj2->availabilities = [
            (function() {
                $availability = new Availability();
                $availability->nlbronId = "nlbron-surf-dev";
                $availability->startDate = null;
                $availability->endDate = null;
                $availability->months = null;
                return $availability;
            })()
        ];
        $obj2->courseId = null;
        $obj2->purgeAfter = null;
        $obj2->purged = false;
        $obj2->expiryDateForNlBron = null;
        $obj2->sourceObjects = [];

        return [$obj1, $obj2];
    }

    public static function flagItem(string $iBron, string $uuid, string $ownerUuid): bool {
        // TODO: Implement flagItem() method.
        return false;
    }
}