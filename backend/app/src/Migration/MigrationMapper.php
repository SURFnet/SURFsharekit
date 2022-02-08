<?php

namespace SurfSharekit\Migration;

class MigrationMapper
{
    static public $Metafields =
        [
            'PublicationRecord'=>
            [
                '4d88565e-665c-4db9-b5a6-65aa9c272e05' => 'dcterms:title',
                '8a042165-fda5-44f0-8863-27784f4d37e7' => 'dbpo:subtitle',
                'd418da1d-ca28-40b2-888f-b21dccbd9f5d' => 'dcterms:rights',
                'eade93d7-e432-4240-b408-b704677903c7' => 'dcterms:keyword',
                'da2e5ff2-cb76-470e-bb98-e6b8a9653223' => 'dcterms:abstract',
                '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e' => 'dcterms:language',
                '58cc2eed-f5ff-408c-8872-762ddbb12724' => 'dcterms:type',
                'a446aed3-8bf1-449b-a825-723d89b3759e' => 'dcterms:date',
                '80d99f27-2c06-4958-ad51-2264a98fd463' => 'event:place',
                '01f7969c-71db-474d-a52d-f73a212a409d' => 'sharekit:nbc:domain',
                'b9205847-d011-4bac-ba58-5d439a7e1222' => 'dbpo:numberOfPages',
                '782f655c-1d4a-4123-9641-e784ca566f9d' => 'dcterms:educationLevel',
                '9099fa3e-79ad-40d3-857f-617c14fda80b' => 'sharekit:thesis:organization',
                '818e53b7-1880-4003-86da-73df227f195e' => 'sharekit:date:approved',
                '55f3b786-968b-4831-b6c2-a0f0b728b276' => 'sharekit:grade',
                'a55c8683-80a8-4161-b93a-e01dabc5001b' => 'sharekit:award',
                '55f4fd0c-2172-4bac-9ce8-df2fd7e4ca49' => 'skos:note',
                'b493f283-bc76-4c88-8357-31ebe4314386' => 'sharekit:permission',
                '180ffa04-e0cc-42b4-b591-40e3d11c2963' => 'status',
                '8149522b-6527-4989-a99b-be2c35aa36ae' => 'status',
                'f09f256d-192d-47ac-9337-0f2ffcc2670b' => 'status',
                'd5c465a2-ee40-43a0-96cf-106bd8cba19f' => 'status'
            ],
            'LearningObject' =>
            [
                '4d88565e-665c-4db9-b5a6-65aa9c272e05' => 'dcterms:title',
                '8a042165-fda5-44f0-8863-27784f4d37e7' => 'dbpo:subtitle',
                'd418da1d-ca28-40b2-888f-b21dccbd9f5d' => 'dcterms:rights',
                'eade93d7-e432-4240-b408-b704677903c7' => 'dcterms:keyword',
                'da2e5ff2-cb76-470e-bb98-e6b8a9653223' => 'dcterms:abstract',
                '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e' => 'dcterms:language',
                'a446aed3-8bf1-449b-a825-723d89b3759e' => 'dcterms:date',
                '80d99f27-2c06-4958-ad51-2264a98fd463' => 'event:place',
                '19d3e05f-049a-49c8-b476-ccfea0ecf54c' => 'sharekit:nbc:domain',
                '782f655c-1d4a-4123-9641-e784ca566f9d' => 'dcterms:educationLevel',
                'a32f4380-30fd-43ae-b5bb-81f3e731a155' => 'bibo:doi',
                'b075f688-ba32-4eb7-b16a-92ed0cb1d9cf' => 'lom:aggregationlevel',
                '0754752f-fc85-4009-a874-9c541b216eb5' => 'lom:learningresourcetype',
                '52f032bf-71f5-4bd3-a96d-60f0e0a37ba0' => 'lom:technicalformat',
                '7122da5e-3e79-4996-a554-b8f8f1478fe2' => 'lom:educational:intendedEndUserRole',
                'b493f283-bc76-4c88-8357-31ebe4314386' => 'sharekit:permission',
                '990172a8-101c-4e21-8243-196b46cc6ddd' => 'status',
                '8149522b-6527-4989-a99b-be2c35aa36ae' => 'status',
                'f09f256d-192d-47ac-9337-0f2ffcc2670b' => 'status',
                'd5c465a2-ee40-43a0-96cf-106bd8cba19f' => 'status',
                '50748429-b561-472c-87ca-027493f022ea' => 'dcterms:publisher',
                '35f8ab00-d5b1-48bb-923c-40626ec9005c' => 'lom:classification:purpose_discipline'
//'lom:classification:purpose_discipline'
            ],
            'ResearchObject' =>
                [
                    '4d88565e-665c-4db9-b5a6-65aa9c272e05' => 'dcterms:title',
                    '8a042165-fda5-44f0-8863-27784f4d37e7' => 'dbpo:subtitle',
                    'd418da1d-ca28-40b2-888f-b21dccbd9f5d' => 'dcterms:rights',
                    'eade93d7-e432-4240-b408-b704677903c7' => 'dcterms:keyword',
                    'da2e5ff2-cb76-470e-bb98-e6b8a9653223' => 'dcterms:abstract',
                    '512d61ba-9ac6-4f77-92e9-3da03ea63007' => 'researchType',
                    '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e' => 'dcterms:language',
                    'a446aed3-8bf1-449b-a825-723d89b3759e' => 'dcterms:date',
                    '80d99f27-2c06-4958-ad51-2264a98fd463' => 'event:place',
                    'a32f4380-30fd-43ae-b5bb-81f3e731a155' => 'bibo:doi',
                    '01f7969c-71db-474d-a52d-f73a212a409d' => 'sharekit:nbc:domain',
                    'b9205847-d011-4bac-ba58-5d439a7e1222' => 'dbpo:numberOfPages',
                    '3311da0f-e897-4ff0-a077-1eda43255333' => 'bibo:handle',
                    '8371a0eb-71d1-47be-90b8-e9779ca05a5c' => 'mods:relatedItem:host:title',
                    'f6b3e02f-8377-445b-8192-ac7d386c8c8a' => 'mods:relatedItem:host:publisher',
                    '0f9b0ef4-fb56-4f9a-93f4-a6f890d41020' => 'mods:relatedItem:host:place',
                    '105eab80-a00b-4f31-a833-987b818c5a16' => 'bibo:edition',
                    '51401f62-ca9d-4499-a89d-f5e1a2f0d7bd' => 'mods:relatedItem:host:page:start',
                    'a6b9274c-89f6-4ad0-a398-a00fd80e22f1' => 'mods:relatedItem:host:page:end',
                    '7c5a27e3-bca5-4665-8d80-e447eaf5b848' => 'mods:relatedItem:host:volume',
                    '5fc4575e-0f14-4acb-bc6c-7adf61a5f8af' => 'mods:relatedItem:host:issue',
                    '55d56e39-7df0-4154-b507-ca7412c6147d' => 'mods:relatedItem:host:ISSN',
                    'd657756a-6a31-41b8-ad3f-a5067820a8aa' => 'mods:relatedItem:host:ISBN',
                    '29519806-5370-423a-9321-9185ba4a74c1' => 'mods:relatedItem:host:conference',
                    '180ffa04-e0cc-42b4-b591-40e3d11c2963' => 'status',
                    '8149522b-6527-4989-a99b-be2c35aa36ae' => 'status',
                    'f09f256d-192d-47ac-9337-0f2ffcc2670b' => 'status',
                    'd5c465a2-ee40-43a0-96cf-106bd8cba19f' => 'status'
//'lom:classification:purpose_discipline'
                ]
        ];

    static public $MetaFieldExplode = [
        'eade93d7-e432-4240-b408-b704677903c7' => ',',
        '782f655c-1d4a-4123-9641-e784ca566f9d' => ',',
        '35f8ab00-d5b1-48bb-923c-40626ec9005c' => ','
    ];

    static private $MetaFieldMaps =
        [
                '990172a8-101c-4e21-8243-196b46cc6ddd' => 'LearningStatus',
                '180ffa04-e0cc-42b4-b591-40e3d11c2963' => 'Status',
                '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e' => 'Language',
                '58cc2eed-f5ff-408c-8872-762ddbb12724' => 'Type',
                '512d61ba-9ac6-4f77-92e9-3da03ea63007' => 'ResearchObjectType',
                'd418da1d-ca28-40b2-888f-b21dccbd9f5d' => 'Rights',
                '782f655c-1d4a-4123-9641-e784ca566f9d' => 'EducationLevel',
                '01f7969c-71db-474d-a52d-f73a212a409d' => 'Theme',
                '19d3e05f-049a-49c8-b476-ccfea0ecf54c' => 'Vakgebied', // Vakgebied
                'b075f688-ba32-4eb7-b16a-92ed0cb1d9cf' => 'Aggregatieniveau',
                '7122da5e-3e79-4996-a554-b8f8f1478fe2' => 'EndUserRole',
                '8149522b-6527-4989-a99b-be2c35aa36ae' => 'Archief',
                'f09f256d-192d-47ac-9337-0f2ffcc2670b' => 'InternStatus',
                '720ede7f-52c3-4672-9327-b7e1358d5463' => 'Role',
                '52f032bf-71f5-4bd3-a96d-60f0e0a37ba0' => 'Technischformaat',
                '0754752f-fc85-4009-a874-9c541b216eb5' => 'LearningResource',
                'd5c465a2-ee40-43a0-96cf-106bd8cba19f' => 'NarcisStatus'
        ];

    static private $Role = [
        'Associate lector' => 'Associate lector'
        ,'associate_lector' => 'Associate lector'
        ,'Co-promotor' => 'Onderzoeker'
        ,'Copromotor' => 'Onderzoeker'
        ,'Dcocent' => 'Docent'
        ,'docent' => 'Docent'
        ,'Docent-onderzoeker' => 'Onderzoeker'
        ,'Hoofdonderzoeker en opdrachtgever' => 'Onderzoeker'
        ,'lector' => 'Lector'
        ,'Lector Robotica' => 'Lector'
        ,'Lid Kenniscentrum' => 'Stafmedewerker'
        ,'lid_kenniskring' => 'Stafmedewerker'
        ,'lid_lectoraat' => 'Lid  lectoraat'
        ,'Medewerker Expertisecentrum Beroepsonderwijs' => 'Stafmedewerker'
        ,'onderzoeker' => 'Onderzoeker'
        ,'Onderzoeker Universiteit Utrecht' => 'Onderzoeker'
        ,'overig' => 'Overig'
        ,'overige' => 'Overig'
        ,'phd' => 'PHD'
        ,'Projectleider' => 'Stafmedewerker'
        ,'Promotor' => 'Onderzoeker'
        ,'senioronderzoeker bij het Mulier Insituut' => 'Onderzoeker'
        ,'staf_medewerker' => 'Stafmedewerker'
        ,'Stagebegeleider' => 'Begeleider (stage/ afstuderen)'
        ,'student' => 'Student'
        ,'student_onderzoeker' => 'Onderzoeker'
        ,'Supervisor' => 'Stafmedewerker'
        ,'tweede beoordelaar' => 'Beoordelaar'
        ,'__default__' => null
    ];

    static private $InternStatus = [
        'archief' => 0,
        'kennisbank' => 1,
        'portaal' => 1,
        'concept' => 0,
        'embargo' => 0,
        '__default__' => 1
    ];

    static private $Archief = [
        'archief' => 1,
        '__default__' => 0
    ];

    static private $Status = [
        'kennisbank' => 1,
        '__default__' => 0
    ];

    static private $LearningStatus = [
        'portaal' => 1,
        '__default__' => 0
    ];

    static private $NarcisStatus =[
        'narcis' => 1,
        '__default__' => 0
    ];

    static private $EndUserRole = [
        'learner'=>'learner',
        'teacher'=>'teacher',
        'author'=>'author',
        '__default__' => 'teacher'
    ];

    static private $LearningResource = [
        'http://purl.edustandaard.nl/concept/e17ace33-b704-4535-8709-d9e1bcd6e4bc' => 'kennisclip',
        'http://purl.edustandaard.nl/concept/d1b5a0a0-c86b-4eee-a7b8-9858dfe1c1eb' => 'weblecture',
        'http://purl.edustandaard.nl/concept/88643f16-8d97-4f77-92af-c89f6ab3ec4e' => 'vlog',
        'http://purl.edustandaard.nl/concept/77bd3b97-9a42-4f41-a97a-0c1e264c125a' => 'blogpost',
        'http://purl.edustandaard.nl/concept/c38fe670-779d-4fd4-9f6b-83c0ce26cf3b' => 'animatie',
        'http://purl.edustandaard.nl/concept/3144b5ec-ad4f-4228-8534-f062f2711b41' => 'experiment',
        'http://purl.edustandaard.nl/concept/3ba8dfd9-1095-4604-8e50-6c724f77518d' => 'infographic',
        'http://purl.edustandaard.nl/concept/92007d62-e449-49d4-9f76-4f183700fdb7' => 'opdracht_gesloten',
        'http://purl.edustandaard.nl/concept/2dd3c40e-e79e-4c90-b217-b00cc443d6ec' => 'opdracht_open',
        'http://purl.edustandaard.nl/concept/23b22ea4-cd65-4bdf-87dd-6c1234d060f4' => 'professionaliserings-materiaal',
        'http://purl.edustandaard.nl/concept/6dafa207-a760-4c21-95ab-d1df83860a6a' => 'quiz-test',
        'http://purl.edustandaard.nl/concept/cd0ea747-4c30-4632-b0c6-7b2c3fe7fa83' => 'referentiemateriaal',
        'http://purl.edustandaard.nl/concept/b707f4a3-7da1-4655-a770-b90992cba305' => 'simulatie',
        'http://purl.edustandaard.nl/concept/d2dc186b-6d4f-4e7c-8011-6bdbb1f84339' => 'scoringsrubriek',
        'http://purl.edustandaard.nl/concept/6b1e5851-f031-4838-ba2b-0703493a2f89' => 'toetsmatrijs',
        'http://purl.edustandaard.nl/concept/7c7dfb5b-2d2d-463b-ad35-b4fc534ddf22' => 'tutorial',
        'exercise' => 'opdracht_gesloten',
        'diagram' => 'infographic',
        'narrative%20text' => 'document',
        'lecture' => 'weblecture',
        'exam' => 'quiz-test',
        'index' => 'document',
        'table' => 'toetsmatrijs',
        'problem%20statement' => 'opdracht_open',
        'questionnaire' => 'quiz-test',
        'graph' => 'infographic',
        'simulation' => 'simulatie',
        'experiment' => 'experiment',
        '__default__' => null
    ];

    static private $Technischformaat = [
        'http://purl.edustandaard.nl/concept/119b549b-6e78-4d61-84de-10469d4e2065' => 'degrees-video'
        ,'http://purl.edustandaard.nl/concept/a216b03c-3a5f-497d-8d5c-c961a807171c' => 'printable-object'
        ,'http://purl.edustandaard.nl/concept/aa663343-a483-4910-b217-a597dbefcb3d' => 'image'
        ,'http://purl.edustandaard.nl/concept/413789d5-b826-4500-ba0d-71f78942d489' => 'app'
        ,'http://purl.edustandaard.nl/concept/3dcf8bab-e4e8-424e-b622-3e3e5fe3e149' => 'audio'
        ,'http://purl.edustandaard.nl/concept/3ea8d183-2ed0-46ed-9e70-cc4214d36469' => 'augmented-reality'
        ,'http://purl.edustandaard.nl/concept/9c79693f-4d1d-4d7f-aab3-bfd3642630b4' => 'document'
        ,'http://purl.edustandaard.nl/concept/e827b9c8-ae37-400b-86e9-49211d89c32d' => 'openaccess-textbook'
        ,'http://purl.edustandaard.nl/concept/72ec6f5b-6071-468c-82b2-65370614a857' => 'presentation'
        ,'http://purl.edustandaard.nl/concept/887886e6-7d75-4c13-b2fa-2da911e1e775' => 'spreadsheet'
        ,'http://purl.edustandaard.nl/concept/61de1e1c-4a84-481b-8d2c-9c94cb5af1bd' => 'video'
        ,'http://purl.edustandaard.nl/concept/e7178740-b24b-4a0a-8ae6-86d0b0c5a385' => 'virtual-reality'
        ,'http://purl.edustandaard.nl/concept/8588410b-58e5-4f16-8e79-7d54b19d84bf' => 'website',
        '__default__' => null
    ];

    static private $Aggregatieniveau = [
        '1' => 'element',
        '2' => 'lesson-component',
        '3' => 'lesson',
        '4' => 'series-of-lessons',
        '__default__' => 'lesson'
    ];

    static private $Theme = [
        '1' => 'algemenewerken',
        '2' => 'taal_cultuur_kunsten',
        '5' => 'ict_media',
        '6' => 'ict_media',
        '8' => 'filosofie_religie',
        '10'  => 'filosofie_religie',
        '11' => 'filosofie_religie',
        '15' => 'filosofie_religie',
        '17' => 'taal_cultuur_kunsten',
        '18' => 'taal_cultuur_kunsten',
        '20' => 'taal_cultuur_kunsten',
        '21' => 'taal_cultuur_kunsten',
        '24' => 'taal_cultuur_kunsten',
        '30' => 'techniek',
        '31' => 'techniek',
        '33' => 'techniek',
        '35' => 'techniek',
        '38' => 'natuur_landbouw',
        '39' => 'natuur_landbouw',
        '42' => 'natuur_landbouw',
        '43' => 'natuur_landbouw',
        '44' => 'gezondheid',
        '46' => 'gezondheid',
        '48' => 'natuur_landbouw',
        '49' => 'mens_maatschappij',
        '50' => 'techniek',
        '51' => 'ict_media',
        '52' => 'techniek',
        '53' => 'techniek',
        '54' => 'ict_media',
        '55' => 'bouw_logistiek',
        '56' => 'bouw_logistiek',
        '57' => 'natuur_landbouw',
        '58' => 'techniek',
        '70' => 'mens_maatschappij',
        '71' => 'mens_maatschappij',
        '73' => 'mens_maatschappij',
        '74' => 'ruimtelijkeordening_planning',
        '76' => 'recreatie_beweging_sport',
        '77' => 'mens_maatschappij',
        '79' => 'mens_maatschappij',
        '80' => 'opvoeding_onderwijs',
        '81' => 'opvoeding_onderwijs',
        '83' => 'economie_management',
        '85' => 'economie_management',
        '86' => 'recht',
        '88' => 'mens_maatschappij',
        '89' => 'mens_maatschappij',
        '__default__' => 'algemenewerken'
    ];

    static private $Vakgebied = [
        '38' => 'aarde_milieu',
        '39' => 'aarde_milieu',
        '42' => 'aarde_milieu',
        '43' => 'aarde_milieu',
        '48' => 'aarde_milieu',
        '74' => 'aarde_milieu',
        '83' => 'economie_bedrijf',
        '85' => 'economie_bedrijf',
        '76' => 'economie_bedrijf',
        '30' => 'exact_informatica',
        '31' => 'exact_informatica',
        '33' => 'exact_informatica',
        '35' => 'exact_informatica',
        '54' => 'exact_informatica',
        '10' => 'gedrag_maatschappij',
        '70' => 'gedrag_maatschappij',
        '71' => 'gedrag_maatschappij',
        '73' => 'gedrag_maatschappij',
        '77' => 'gedrag_maatschappij',
        '79' => 'gedrag_maatschappij',
        '44' => 'gezondheid',
        '46' => 'gezondheid',
        '1' => 'interdisciplinair',
        '8' => 'kunst_cultuur',
        '11' => 'kunst_cultuur',
        '15' => 'kunst_cultuur',
        '2' => 'kunst_cultuur',
        '20' => 'kunst_cultuur',
        '21' => 'kunst_cultuur',
        '24' => 'kunst_cultuur',
        '49' => 'onderwijs_opvoeding',
        '80' => 'onderwijs_opvoeding',
        '81' => 'onderwijs_opvoeding',
        '86' => 'recht_bestuur',
        '88' => 'recht_bestuur',
        '89' => 'recht_bestuur',
        '5' => 'taal_communicatie',
        '6' => 'taal_communicatie',
        '17' => 'taal_communicatie',
        '18' => 'taal_communicatie',
        '50' => 'techniek',
        '51' => 'techniek',
        '52' => 'techniek',
        '53' => 'techniek',
        '55' => 'techniek',
        '56' => 'techniek',
        '57' => 'techniek',
        '58' => 'techniek'
    ];

    static private $EducationLevel = [
        'sharekit:educationLevel:hbo_bachelor'=>'hbo-bachelor',
        'sharekit:educationLevel:hbo_master'=>'hbo-master',
        'sharekit:educationLevel:wo_bachelor'=>'wo-bachelor',
        'sharekit:educationLevel:phd'=>'phd',
        'sharekit:educationLevel:wo_master'=>'wo-master',
        'sharekit:educationLevel:hbo_associate_degree'=>'hbo-associate-degree',
        'sharekit:educationLevel:post_hbo'=>'post-hbo',
        '__default__' => null
    ];

    static private $Rights = [
        'https://creativecommons.org/licenses/by-nc-sa/4.0/' => 'naamsvermelding-nietcommercieel-gelijkdelen',
        'urn:all-rights-reserved' => 'alle-rechten-voorbehouden',
        'https://creativecommons.org/licenses/by-sa/4.0/' => 'naamsvermelding-gelijkdelen',
        'https://creativecommons.org/licenses/by/4.0/' => 'naamsvermelding',
        'https://creativecommons.org/licenses/by-nc/4.0/' => 'naamsvermelding-nietcommercieel',
        'https://creativecommons.org/licenses/by-nc-nd/4.0/' => 'naamsvermelding-nietcommercieel-geenafgeleidewerken',
        'https://creativecommons.org/licenses/by-nd/4.0/' => 'naamsvermelding-geenafgeleidewerken',
        'https://creativecommons.org/publicdomain/zero/1.0/' => 'publicdomain',
        '__default__' => 'publicdomain'
    ];

    static private $InstituteLevel =
        [
            'organization' => 'organisation',
            'department'=> 'department',
            'lectorate' =>  'lectorate',
            'training' => 'discipline',
            'hogeschool' => 'organisation',
            '__default__' => null
        ];

    static private $Language =
        [
            'nl' => 'nl',
            'en' => 'en',
            'de' => 'de',
            'fr' => 'fr',
            'es' => 'es',
            '__default__' => 'en'
        ];

    static private $Type =
        [
            'http://purl.org/eprint/type/Thesis' => 'thesis',
            'info:eu-repo/semantics/traineeReport' => 'internship-report',
            '__default__' => 'thesis'
        ];

    static private $ResearchObjectType =
        [
            'info:eu-repo/semantics/article' => 'Artikel',
            'info:eu-repo/semantics/conferenceObject' => 'Conferentiebijdrage',
            'info:eu-repo/semantics/annotation' => 'Annotatie',
            'info:eu-repo/semantics/contributionToPeriodical' => 'Bijdrage aan periodiek',
            'info:eu-repo/semantics/book' => 'Boek',
            'info:eu-repo/semantics/bookPart' => 'Boekdeel',
            'info:eu-repo/semantics/lecture' => 'Lezing',
            //'Patent'
            'info:eu-repo/semantics/preprint' => 'Preprint',
            'info:eu-repo/semantics/report' => 'Rapport',
            'info:eu-repo/semantics/review' => 'Recensie',
            'info:eu-repo/semantics/workingPaper' => 'Working paper',
            'info:eu-repo/semantics/other' => 'Andersoortig materiaal',
            '__default__' => 'Andersoortig materiaal'
        ];

    static public function mapMetaFields($metaFieldUuid, $value){
        if(array_key_exists($metaFieldUuid, self::$MetaFieldMaps)){
            return self::map(self::$MetaFieldMaps[$metaFieldUuid], $value);
        }
        return $value;
    }

    static public function map($field, $value){
        if(property_exists(MigrationMapper::class, $field)){
            if(array_key_exists($value, self::$$field)){
                return self::$$field[$value];
            }else{
                return self::$$field['__default__'];
            }
        }
    }
}