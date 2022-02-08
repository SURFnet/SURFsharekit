<?php

namespace SurfSharekit\Tasks;

use ReflectionClass;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SurfSharekit\Models\MetaField;
use SurfSharekit\Models\MetaFieldOption;
use SurfSharekit\Models\MetaFieldType;
use SurfSharekit\Models\TemplateMetaField;
use SurfSharekit\Models\TemplateSection;

class CreateDefaultMetaFields extends BuildTask {

    protected $title = 'Create Default MetaFields';
    protected $description = 'Run this task after clean install of Sharekit';

    protected $enabled = true;

    private static $defaultMetaFieldTypes = [
        ['Uuid' => '9d945e1b-0a79-4693-b158-495fa225883a', 'Key' => 'Email', 'Title' => 'Email'],
        ['Uuid' => '0edc9345-3f38-45fe-afdc-702531ab7d0c', 'Key' => 'TextArea', 'Title' => 'TextArea'],
        ['Uuid' => '542a5e11-2716-4182-ac65-d540561a7b36', 'Key' => 'Text', 'Title' => 'URL'],
        ['Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63', 'Key' => 'Text', 'Title' => 'Text'],
        ['Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2', 'Key' => 'Dropdown', 'Title' => 'Dropdown'],
        ['Uuid' => 'b1778664-e727-407b-b528-55159fd25ee1', 'Key' => 'Discipline', 'Title' => 'Dropdown (voor opleiding/discipline)'],
        ['Uuid' => '5502e5a8-9321-4a22-9357-1002f7ba9fd3', 'Key' => 'MultiSelectSuborganisation', 'Title' => 'Dropdown (voor opleidingen, lectoraten en afdelingen)'],
        ['Uuid' => '1fcd5f62-114e-4d03-bc0a-86e420b3320a', 'Key' => 'File', 'Title' => 'File', 'JSONEncodedStorage' => 1],
        ['Uuid' => 'c8787ff1-2a11-45fe-b5de-76c2704b5b10', 'Key' => 'Attachment', 'Title' => 'Attachment'],
        ['Uuid' => '7751f1b7-9169-43ab-8016-e4f22d4e24cd', 'Key' => 'Radio', 'Title' => 'Radio'],
        ['Uuid' => '6922da29-6ed1-44c4-8489-911f223bdf7b', 'Key' => 'Checkbox', 'Title' => 'Checkbox'],
        ['Uuid' => '66386abc-6ef8-4d4d-b579-2fdfe7981d30', 'Key' => 'Text', 'Title' => 'Text'],
        ['Uuid' => 'cf76f409-a1af-4ab7-9bc8-7fffbacc74af', 'Key' => 'Date', 'Title' => 'Date', 'ValidationRegex' => '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$'],
        ['Uuid' => '577ac092-dbf4-45f5-b1bf-d4da3de0033f', 'Key' => 'Text', 'Title' => 'Text-Number', 'ValidationRegex' => '^[0-9]+$'],
        ['Uuid' => 'a8e4e376-517e-434f-a654-001cf05709c3', 'Key' => 'Person', 'Title' => 'A SurfSharekit member'],
        ['Uuid' => '776820af-fd32-4e4b-8fe2-3c591c3676b9', 'Key' => 'Number', 'Title' => 'Number from 0 to 10'],
        ['Uuid' => '074091aa-66dc-4129-afe3-b81cdcb0f506', 'Key' => 'Number', 'Title' => 'Number'],
        ['Uuid' => '791974b1-c8e6-49df-ab26-89ac3d5d4cc1', 'Key' => 'PersonInvolved', 'Title' => 'PersonInvolved'],
        ['Uuid' => '79213e32-d1e3-45e4-8f21-96931d063d2b', 'Key' => 'RepoItemLink', 'Title' => 'RepoItemLink'],
        ['Uuid' => '590da2d9-10a6-468b-942b-c181342c6555', 'Key' => 'Tag', 'Title' => 'Tag'],
        ['Uuid' => '500c5bd6-1c10-4683-ab74-335330d5a3b8', 'Key' => 'Switch-row', 'Title' => 'Switch row'],
        ['Uuid' => '372cf6a1-a407-480b-885f-7e7689485929', 'Key' => 'RepoItem', 'Title' => 'RepoItem'],
        ['Uuid' => '62b4b14c-d3c7-49f9-94c5-593d1c13b367', 'Key' => 'RepoItemLearningObject', 'Title' => 'RepoItemLearningObject'],
        ['Uuid' => '44c8c054-0866-11eb-adc1-0242ac120002', 'Key' => 'MultiSelectDropdown', 'Title' => 'MultiSelectDropdown'],
    ];

    private static $defaultMetaFields = [
        ['Uuid' => 'acac043d-6ed1-4d96-bc90-993038ef2574',
            'Title' => 'Gekoppelde lesmaterialen',
            'MetaFieldType_Uuid' => '62b4b14c-d3c7-49f9-94c5-593d1c13b367',
            'Label_NL' => 'Gekoppelde lesmaterialen'],
        ['Uuid' => '2a70a6fe-fd3a-48e2-a957-4c8fa737fd78',
            'Title' => 'Relatie',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Relatie',
            'AttributeKey' => 'Subtitle'],
        ['Uuid' => 'c210986f-0c47-45a1-b5f8-40d065b8a47f',
            'Title' => 'RepoItem',
            'MetaFieldType_Uuid' => '372cf6a1-a407-480b-885f-7e7689485929',
            'Label_NL' => 'RepoItem',
            'AttributeKey' => 'Title'],
        ['Uuid' => '5cede4de-2e88-4cf5-a1d7-a4f22c3241a6',
            'Title' => 'Auteurs en betrokkenen',
            'MetaFieldType_Uuid' => '791974b1-c8e6-49df-ab26-89ac3d5d4cc1',
            'Label_NL' => 'Auteurs en betrokkenen'],
        ['Uuid' => 'a361a5a9-a80b-4e2c-8145-a60e2fce9acf',
            'Title' => 'Persoon',
            'AttributeKey' => 'Title',
            'MetaFieldType_Uuid' => 'a8e4e376-517e-434f-a654-001cf05709c3',
            'Label_NL' => 'Persoon'],
        ['Uuid' => '4d88565e-665c-4db9-b5a6-65aa9c272e05',
            'Title' => 'Titel',
            'AttributeKey' => 'Title',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Titel'],
        ['Uuid' => '8a042165-fda5-44f0-8863-27784f4d37e7',
            'Title' => 'Ondertitel',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Ondertitel'],
        ['Uuid' => '270c698d-81ff-4a5d-aa67-7cf76f63bf78',
            'Title' => 'Suborganisaties',
            'MetaFieldType_Uuid' => '5502e5a8-9321-4a22-9357-1002f7ba9fd3',
            'DefaultKey' => 'AuthorDiscipline',
            'Label_NL' => 'Afdelingen, lectoraten en opleidingen',
            'Label_EN' => 'Organisations, professorships and educations'],
        ['Uuid' => 'a446aed3-8bf1-449b-a825-723d89b3759e',
            'Title' => 'Publicatiedatum',
            'MetaFieldType_Uuid' => 'cf76f409-a1af-4ab7-9bc8-7fffbacc74af',
            'DefaultKey' => 'CurrentDate',
            'AttributeKey' => 'EmbargoDate',
            'Label_NL' => 'Publicatiedatum'],
        ['Uuid' => '720ede7f-52c3-4672-9327-b7e1358d5463',
            'Title' => 'Role',
            'AttributeKey' => 'Subtitle',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Rol'],
        ['Uuid' => '58cc2eed-f5ff-408c-8872-762ddbb12724',
            'Title' => 'Type',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Type'],
        ['Uuid' => '782f655c-1d4a-4123-9641-e784ca566f9d',
            'Title' => 'Niveau',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Niveau'],
        ['Uuid' => 'b9205847-d011-4bac-ba58-5d439a7e1222',
            'Title' => 'Aantal pagina\'s',
            'MetaFieldType_Uuid' => '074091aa-66dc-4129-afe3-b81cdcb0f506',
            'Label_NL' => 'Aantal pagina\'s'],
        ['Uuid' => 'da2e5ff2-cb76-470e-bb98-e6b8a9653223',
            'Title' => 'Samenvatting',
            'MetaFieldType_Uuid' => '0edc9345-3f38-45fe-afdc-702531ab7d0c',
            'Label_NL' => 'Samenvatting'],
        ['Uuid' => '7bc1c220-d83e-4cac-a5a0-ba2263b53b77',
            'Title' => 'Organisatie',
            'MetaFieldType_Uuid' => 'b1778664-e727-407b-b528-55159fd25ee1',
            'DefaultKey' => 'TemplateRootInstitute',
            'Label_NL' => 'Organisatie'],
        ['Uuid' => '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e',
            'Title' => 'Taal',
            'AttributeKey' => 'Language',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Taal'],
        ['Uuid' => '7b813510-9f54-4620-8ead-470ced2f2e74',
            'Title' => 'Auteurs',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Auteurs'],
        ['Uuid' => '55f4fd0c-2172-4bac-9ce8-df2fd7e4ca49',
            'Title' => 'Opmerkingen',
            'MetaFieldType_Uuid' => '0edc9345-3f38-45fe-afdc-702531ab7d0c',
            'Label_NL' => 'Opmerkingen'],
        ['Uuid' => 'a55c8683-80a8-4161-b93a-e01dabc5001b',
            'Title' => 'Award',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Award'],
        ['Uuid' => '818e53b7-1880-4003-86da-73df227f195e',
            'Title' => 'Datum van goedkeuring',
            'MetaFieldType_Uuid' => 'cf76f409-a1af-4ab7-9bc8-7fffbacc74af',
            'Label_NL' => 'Datum van goedkeuring'],
        ['Uuid' => '80d99f27-2c06-4958-ad51-2264a98fd463',
            'Title' => 'Plaats',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Plaats'],
        ['Uuid' => '036ba1b7-67aa-4551-ab95-d539b472ed2c',
            'Title' => 'Website urls en profielpaginas',
            'MetaFieldType_Uuid' => '79213e32-d1e3-45e4-8f21-96931d063d2b',
            'Label_NL' => 'Website urls en profielpaginas'],
        ['Uuid' => '55f3b786-968b-4831-b6c2-a0f0b728b276',
            'Title' => 'Cijfer',
            'MetaFieldType_Uuid' => '776820af-fd32-4e4b-8fe2-3c591c3676b9',
            'Label_NL' => 'Cijfer'],
        ['Uuid' => '64f52f27-1b7b-4af3-bed9-25dfdeb1e82d',
            'Title' => 'Afstudeerbegeleider',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Afstudeerbegeleider'],
        ['Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002',
            'Title' => 'Vakvocabulaire',
            'MetaFieldType_Uuid' => '44c8c054-0866-11eb-adc1-0242ac120002',
            'Label_NL' => 'Vakvocabulaire'],
        ['Uuid' => 'eade93d7-e432-4240-b408-b704677903c7',
            'Title' => 'Trefwoorden',
            'MetaFieldType_Uuid' => '590da2d9-10a6-468b-942b-c181342c6555',
            'Label_NL' => 'Trefwoorden'],
        ['Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d',
            'Title' => 'Gebruiksrecht',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Gebruiksrecht'],
        ['Uuid' => '27c41422-eca0-4350-b32b-6ee20135705e',
            'Title' => 'Uploads',
            'MetaFieldType_Uuid' => 'c8787ff1-2a11-45fe-b5de-76c2704b5b10',
            'Label_NL' => 'Uploads'],
        ['Uuid' => '32efdf11-d6ce-482d-a0c7-67b509214f3a',
            'Title' => 'Bestandsnaam',
            'AttributeKey' => 'Title',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Bestandsnaam'],
        ['Uuid' => '80e3e484-1a87-4448-afb3-26a3e3698b7a',
            'Title' => 'URL',
            'AttributeKey' => 'Title',
            'MetaFieldType_Uuid' => '542a5e11-2716-4182-ac65-d540561a7b36',
            'Label_NL' => 'URL'],
        ['Uuid' => '2af0793b-fc97-455c-ae71-494425905868',
            'Title' => 'Bestand uploaden',
            'MetaFieldType_Uuid' => '1fcd5f62-114e-4d03-bc0a-86e420b3320a',
            'Label_NL' => 'Bestand uploaden'],
        ['Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d',
            'Title' => 'Vakgebied',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Vakgebied'],
        ['Uuid' => '61a51ab3-3557-4bd0-a053-22ffb34014cc',
            'Title' => 'Identifier',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Identifier'],
        ['Uuid' => 'a62da0aa-2202-4fff-9d84-299c367187be',
            'Title' => 'Persoonlijke identifier',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Persoonlijke identifier'],
        ['Uuid' => '9d2700a0-b58f-4264-b4ca-d9c49d895a5e',
            'Title' => 'Keywords',
            'MetaFieldType_Uuid' => '0edc9345-3f38-45fe-afdc-702531ab7d0c',
            'Label_NL' => 'Keywords'],
//        ['Uuid' => '53dcf6a2-3799-4289-96e5-f8fb13337ea5',
//            'Title' => 'Title',
//            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
//            'Label_NL' => 'Title'],
        ['Uuid' => '191cd159-3e71-44bb-ae87-436bb1c90204',
            'Title' => 'Subtitle',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Subtitle'],
        ['Uuid' => '32ba1cc9-25f5-458b-ad5a-0f7630ac76bd',
            'Title' => 'Publisher',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Publisher'],
        ['Uuid' => '8758a790-8e09-4d3a-8685-beafe7b6ea87',
            'Title' => 'Date',
            'MetaFieldType_Uuid' => 'cf76f409-a1af-4ab7-9bc8-7fffbacc74af',
            'Label_NL' => 'Date'],
        ['Uuid' => 'a07923fb-1df7-4858-80e7-1db87eb82774',
            'Title' => 'Resource type',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Resource type'],
        ['Uuid' => 'df8951b6-a573-4f65-89bf-6452690d3e71',
            'Title' => 'Rights',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Rights'],
        ['Uuid' => 'dfc1aceb-5d61-418b-9cda-378f48d4de65',
            'Title' => 'Onderwerp',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Onderwerp'],
        ['Uuid' => '24ad0697-c56d-4bb1-8449-0386bf5304c2',
            'Title' => 'Beschrijving',
            'MetaFieldType_Uuid' => '0edc9345-3f38-45fe-afdc-702531ab7d0c',
            'Label_NL' => 'Beschrijving'],
        ['Uuid' => '219ab2ff-5ec3-405b-9b95-fba04d01fecd',
            'Title' => 'URL',
            'MetaFieldType_Uuid' => '66386abc-6ef8-4d4d-b579-2fdfe7981d30',
            'Label_NL' => 'URL'],
        ['Uuid' => 'b493f283-bc76-4c88-8357-31ebe4314386',
            'Title' => 'Toestemming',
            'Description' => 'Hierbij verklaar ik dat zowel de auteurs als de opdrachtgever van dit afstudeerwerk/stageverslag akkoord zijn met de plaatsing van de publicatie in SURFsharekit, waardoor de publicatie zichtbaar wordt in de HBO Kennisbank en via andere platforms ontslote...',
            'MetaFieldType_Uuid' => '6922da29-6ed1-44c4-8489-911f223bdf7b',
            'Label_NL' => 'Toestemming'],
        ['Uuid' => 'e2743c34-aa20-4cdd-bb15-7686ccec98cc',
            'Title' => 'DOI',
            'MetaFieldType_Uuid' => '66386abc-6ef8-4d4d-b579-2fdfe7981d30',
            'Label_NL' => 'DOI'],
        ['Uuid' => '9099fa3e-79ad-40d3-857f-617c14fda80b',
            'Title' => 'Afstudeerorganisatie',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'Afstudeerorganisatie'],
        ['Uuid' => 'a32f4380-30fd-43ae-b5bb-81f3e731a155',
            'Title' => 'DOI',
            'MetaFieldType_Uuid' => '26f89d07-7e0c-4784-b97e-96407c2c9c63',
            'Label_NL' => 'DOI'],
        ['Uuid' => 'b075f688-ba32-4eb7-b16a-92ed0cb1d9cf',
            'Title' => 'Bruikbaar als',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Bruikbaar als'],
        ['Uuid' => '0754752f-fc85-4009-a874-9c541b216eb5',
            'Title' => 'Soort leermateriaal',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Soort leermateriaal'],
        ['Uuid' => '7122da5e-3e79-4996-a554-b8f8f1478fe2',
            'Title' => 'Beoogde eindgebruiker',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Beoogde eindgebruiker'],
        ['Uuid' => '0b2bc453-d08c-4630-863a-910a01620202',
            'Title' => 'Beoogde leeftijdsgroep',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Beoogde leeftijdsgroep'],
        ['Uuid' => 'd5fa4920-43a4-11eb-b378-0242ac130002',
            'Title' => 'Kosteloos beschikbaar',
            'MetaFieldType_Uuid' => '7ff97962-68ab-4c1d-a218-64dd29efa3a2',
            'Label_NL' => 'Kosteloos beschikbaar'],
        ['Uuid' => 'ee426828-527d-4271-8473-d7e25105705e',
            'Title' => 'Sharekit Publicatie',
            'MetaFieldType_Uuid' => '500c5bd6-1c10-4683-ab74-335330d5a3b8',
            'Label_NL' => 'Sharekit Publicatie',
            'Description_NL' => 'Publiceer jouw scriptie op het sharekit kanaal'],
        ['Uuid' => '25d32760-4c12-4a3d-9268-a6b9993bc126',
            'Title' => 'HBO Kennisbank',
            'MetaFieldType_Uuid' => '500c5bd6-1c10-4683-ab74-335330d5a3b8',
            'Label_NL' => 'HBO Kennisbank',
            'Description_NL' => 'Publiceer jouw scriptie op HBO Kennisbank',
            'SystemKey' => 'PublicChannel'],
        ['Uuid' => '28503146-439e-11eb-b378-0242ac130002',
            'Title' => 'Archief',
            'MetaFieldType_Uuid' => '500c5bd6-1c10-4683-ab74-335330d5a3b8',
            'Label_NL' => 'Archief',
            'Description_NL' => 'Publiceer jouw scriptie in het Archief',
            'SystemKey' => 'Archive'],
        ['Uuid' => '1da68174-43a2-11eb-b378-0242ac130002',
            'Title' => 'Parent learning object',
            'MetaFieldType_Uuid' => '372cf6a1-a407-480b-885f-7e7689485929',
            'Label_NL' => 'Maakt deel uit van',
            'Label_EN' => 'Part of',
            'SystemKey' => 'ContainsParents'],
        ['Uuid' => '2de42df2-43a2-11eb-b378-0242ac130002',
            'Title' => 'Child learning object',
            'MetaFieldType_Uuid' => '372cf6a1-a407-480b-885f-7e7689485929',
            'Label_NL' => 'Maakt deel uit van',
            'Label_EN' => 'Part of',
            'SystemKey' => 'ContainsChildren'],
        ['Uuid' => '2de42df2-43a2-11eb-b378-0242ac130002',
            'Title' => 'Child learning object',
            'MetaFieldType_Uuid' => '372cf6a1-a407-480b-885f-7e7689485929',
            'Label_NL' => 'Maakt deel uit van',
            'Label_EN' => 'Part of',
            'SystemKey' => 'ContainsChildren']
    ];

    private static $defaultMetaFieldOptions = [
        ['Uuid' => '95e9a838-ebae-465b-8f62-7c992d2d47fe',
            'Value' => 'Afstudeerwerk',
            'Label_NL' => 'Afstudeerwerk',
            'MetaField_Uuid' => '58cc2eed-f5ff-408c-8872-762ddbb12724'], // Type
        ['Uuid' => '1f467480-2303-4c3b-9051-6e0b1c9db960',
            'Value' => 'Bachelor',
            'Label_NL' => 'Bachelor',
            'MetaField_Uuid' => '782f655c-1d4a-4123-9641-e784ca566f9d'], // Niveau
        ['Uuid' => 'a24191b1-aa26-46cd-ab54-8216d2a8f986',
            'Value' => 'Master',
            'Label_NL' => 'Master',
            'MetaField_Uuid' => '782f655c-1d4a-4123-9641-e784ca566f9d'], // Niveau
        ['Uuid' => '7166454b-a2ca-4bfb-8cb2-2853ac82e446',
            'Value' => 'nl',
            'Label_NL' => 'Nederlands',
            'MetaField_Uuid' => '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e'], // Taal
        ['Uuid' => '6547880f-b0a5-4fa3-9105-051c87d42bc3',
            'Value' => 'en',
            'Label_NL' => 'Engels',
            'MetaField_Uuid' => '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e'], // Taal
        ['Uuid' => '57bf5553-edfb-489b-b63f-20f326bf7963',
            'Value' => 'Algemene werken',
            'Label_NL' => 'Algemene werken',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => '57bf5553-edfb-489b-b63f-20f326bf7963',
            'Value' => 'Algemene werken',
            'Label_NL' => 'Algemene werken',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => 'f9041795-9f31-4758-ab84-21c426b3fa25',
            'Value' => 'Wetenschap en cultuur in het algemeen',
            'Label_NL' => 'Wetenschap en cultuur in het algemeen',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => 'dadb860f-a4cd-4c40-b51f-60c40227d8e7',
            'Value' => 'Communicatiewetenschap',
            'Label_NL' => 'Communicatiewetenschap',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => '062a007e-6283-4365-a0c0-0999573273ec',
            'Value' => 'Documentaire informatie',
            'Label_NL' => 'Documentaire informatie',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => '93ffccf0-f3fe-448f-b48c-3cf745818983',
            'Value' => 'Filosofie',
            'Label_NL' => 'Filosofie',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => 'ea53e6fe-5933-4a27-b97a-75f0fa649622',
            'Value' => 'Geesteswetenschappen in het algemeen',
            'Label_NL' => 'Geesteswetenschappen in het algemeen',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => 'df27d7e8-e1a9-43b1-b645-f7a1d281e779',
            'Value' => 'Theologie, godsdienstwetenschappen',
            'Label_NL' => 'Theologie, godsdienstwetenschappen',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => 'd32b78b0-77d6-4196-81a6-15ee7b9c4b50',
            'Value' => 'Geschiedenis',
            'Label_NL' => 'Geschiedenis',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => '5c78592d-6f40-411b-a9f0-60bfcd6fc97f',
            'Value' => 'Algemene taal- en literatuurwetenschap',
            'Label_NL' => 'Algemene taal- en literatuurwetenschap',
            'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d'], // Vakgebied
        ['Uuid' => '2d185268-02f5-4a5f-9b94-4cd8ce74f5ff',
            'Value' => 'Naamsvermelding',
            'Label_NL' => 'Naamsvermelding',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => '0a25f79d-0176-4cbe-be2c-f70f75a31371',
            'Value' => 'Naamsvermelding-GelijkDelen',
            'Label_NL' => 'Naamsvermelding-GelijkDelen',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => '56f91c28-c930-4a37-b2a7-cb5bc5523fee',
            'Value' => 'Naamsvermelding-NietCommercieel',
            'Label_NL' => 'Naamsvermelding-NietCommercieel',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => '3c3f634c-c2bb-42b8-b0aa-a34c5845c557',
            'Value' => 'Naamsvermelding-NietCommercieel-GelijkDelen',
            'Label_NL' => 'Naamsvermelding-NietCommercieel-GelijkDelen',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => 'b475d7a3-ea4e-4d29-b3cb-9ec7680e24db',
            'Value' => 'Naamsvermelding-GeenAfgeleideWerken',
            'Label_NL' => 'Naamsvermelding-GeenAfgeleideWerken',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => '510da90e-e0ac-43ce-b329-1cac4e74123a',
            'Value' => 'Naamsvermelding-NietCommercieel-GeenAfgeleideWerken',
            'Label_NL' => 'Naamsvermelding-NietCommercieel-GeenAfgeleideWerken',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => '073d7583-65b3-45f4-a412-a39821430d9e',
            'Value' => 'Public Domain Mark',
            'Label_NL' => 'Public Domain Mark',
            'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d'], // Gebruikersrecht
        ['Uuid' => 'f924c0bc-43a2-11eb-b378-0242ac130002',
            'Value' => 'Lessen reeks',
            'Label_NL' => 'Lessen reeks',
            'MetaField_Uuid' => 'b075f688-ba32-4eb7-b16a-92ed0cb1d9cf'], // Bruikbaar als
        ['Uuid' => '4417d217-b943-4a05-bbbf-ae139750a7e0',
            'Value' => 'Les',
            'Label_NL' => 'Les',
            'MetaField_Uuid' => 'b075f688-ba32-4eb7-b16a-92ed0cb1d9cf'], // Bruikbaar als
        ['Uuid' => 'fc3b738c-3867-46c9-9c05-f071a18f53cd',
            'Value' => 'Powerpoint',
            'Label_NL' => 'Powerpoint',
            'MetaField_Uuid' => '0754752f-fc85-4009-a874-9c541b216eb5'], // Soort leermateriaal
        ['Uuid' => 'e055689c-43a3-11eb-b378-0242ac130002',
            'Value' => 'Video',
            'Label_NL' => 'Video',
            'MetaField_Uuid' => '0754752f-fc85-4009-a874-9c541b216eb5'], // Soort leermateriaal
        ['Uuid' => '584a6f43-9fa3-4840-a62f-e8fa594ea1dc',
            'Value' => 'Student',
            'Label_NL' => 'Student',
            'MetaField_Uuid' => '7122da5e-3e79-4996-a554-b8f8f1478fe2'], // Beoogde eindgebruiker
        ['Uuid' => '7940a7f2-86d0-4edf-8551-83fb8cd15eee',
            'Value' => 'Leraar',
            'Label_NL' => 'Leraar',
            'MetaField_Uuid' => '7122da5e-3e79-4996-a554-b8f8f1478fe2'], // Beoogde eindgebruiker
        ['Uuid' => '63439094-43a4-11eb-b378-0242ac130002',
            'Value' => '18-25',
            'Label_NL' => '18-25',
            'MetaField_Uuid' => '0b2bc453-d08c-4630-863a-910a01620202'], // Beoogde leeftijdsgroep
        ['Uuid' => '6d284f64-43a4-11eb-b378-0242ac130002',
            'Value' => '26-40',
            'Label_NL' => '26-40',
            'MetaField_Uuid' => '0b2bc453-d08c-4630-863a-910a01620202'], // Beoogde leeftijdsgroep
        ['Uuid' => 'a9fefe3e-d32d-11ea-87d0-0242ac130003',
            'Value' => 'Ja',
            'Label_NL' => 'Ja',
            'Label_EN' => 'Yes',
            'MetaField_Uuid' => 'b493f283-bc76-4c88-8357-31ebe4314386'], // Toestemming
        ['Uuid' => '018e6aa0-d8d9-44fe-ade6-a54fffff1fa5',
            'Value' => 'Author',
            'Label_NL' => '1e Auteur',
            'Label_EN' => 'Author',
            'MetaField_Uuid' => '720ede7f-52c3-4672-9327-b7e1358d5463'], // Author
        ['Uuid' => 'c81239ea-8404-429d-8a76-dedccdf873bd',
            'Value' => 'Co-author',
            'Label_NL' => 'Coauteur',
            'Label_EN' => 'Co-author',
            'MetaField_Uuid' => '720ede7f-52c3-4672-9327-b7e1358d5463'], // Co-author
        ['Uuid' => '21486aaa-249f-4938-b87c-aef778123258',
            'Value' => 'Lecturer',
            'Label_NL' => 'Lector',
            'Label_EN' => 'Lecturer',
            'MetaField_Uuid' => '720ede7f-52c3-4672-9327-b7e1358d5463'], // Lecturer
        ['Uuid' => '7cb25025-7901-4902-8636-9dbc354cfb4b',
            'Value' => 'PartOf',
            'Label_NL' => 'Onderdeel van',
            'Label_EN' => 'Part of',
            'MetaField_Uuid' => '2a70a6fe-fd3a-48e2-a957-4c8fa737fd78'], // RepoItem 'PartOf' LearningObject
        ['Uuid' => '0aeac2fa-0867-11eb-adc1-0242ac120002',
            'Value' => 'Wiskunde',
            'Label_NL' => 'Wiskunde',
            'Label_EN' => 'Math',
            'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002'], //Vakvocabulaire - a615fa02-0866-11eb-adc1-0242ac120002
        ['Uuid' => '6a6dc7a4-0867-11eb-adc1-0242ac120002',
            'Value' => 'Professioneel gedradg',
            'Label_NL' => 'Professioneel gedrag',
            'Label_EN' => 'Professional behaviour',
            'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002'], //Vakvocabulaire - a615fa02-0866-11eb-adc1-0242ac120002
        ['Uuid' => '2277ba4a-0867-11eb-adc1-0242ac120002',
            'Value' => 'Principium',
            'Label_NL' => 'Principium',
            'Label_EN' => 'Principium',
            'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002'], //Vakvocabulaire - a615fa02-0866-11eb-adc1-0242ac120002
        ['Uuid' => '73a9289a-0867-11eb-adc1-0242ac120002',
            'Value' => 'Logistiek',
            'Label_NL' => 'Logistiek',
            'Label_EN' => 'Logistics',
            'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002'], //Vakvocabulaire - a615fa02-0866-11eb-adc1-0242ac120002
        ['Uuid' => '9e77482c-0867-11eb-adc1-0242ac120002',
            'Value' => 'Informatica',
            'Label_NL' => 'Informatica',
            'Label_EN' => 'Computer Science',
            'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002'], //Vakvocabulaire - a615fa02-0866-11eb-adc1-0242ac120002
        ['Uuid' => '0bdc5fe2-43a5-11eb-b378-0242ac130002',
            'Value' => 'Ja',
            'Label_NL' => 'Ja',
            'Label_EN' => 'Yes',
            'MetaField_Uuid' => 'd5fa4920-43a4-11eb-b378-0242ac130002'], // Kosteloos beschikbaar
        ['Uuid' => '101b84a2-43a5-11eb-b378-0242ac130002',
            'Value' => 'Nee',
            'Label_NL' => 'Nee',
            'Label_EN' => 'No',
            'MetaField_Uuid' => 'd5fa4920-43a4-11eb-b378-0242ac130002'], // Kosteloos beschikbaar
    ];

    private static $defaultTemplateSections = [
        ['Uuid' => '492ce7b2-2041-4019-9b6c-076318246399', 'Title' => 'Algemene gegevens', 'Title_NL' => 'Algemene gegevens', 'SortOrder' => 1],
        ['Uuid' => '474976ca-de6d-4020-93e4-46f151d2ce00', 'Title' => '-- sectie zonder titel --', 'Title_NL' => '', 'SortOrder' => 2],
        ['Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3', 'Title' => 'Aanvullende gegevens', 'Title_NL' => 'Aanvullende gegevens', 'SortOrder' => 3],
        ['Uuid' => '5c277a59-55f8-45ce-b568-6a64e76ede38', 'Title' => 'Gegevens', 'Title_NL' => 'Gegevens', 'SortOrder' => 4],
        ['Uuid' => 'b4b64370-5538-4221-847a-46285108cf12', 'Title' => 'Bestanden/links', 'Title_NL' => 'Bestanden/links', 'SortOrder' => 5],
        ['Uuid' => 'cace60e8-e6bc-4e9c-9694-9439d2edeec4', 'Title' => 'Type publicatie', 'Title_NL' => 'Type publicatie', 'SortOrder' => 6],
        ['Uuid' => '27825248-92f1-45cf-a8ae-f1661ff2ea32', 'Title' => 'Kanalen', 'Title_NL' => 'Kanalen', 'SortOrder' => 7, 'IsUsedForSelection' => 1],
        ['Uuid' => '89892428-8614-4d25-b41a-b951ebee3670', 'Title' => 'Publiceren', 'Title_NL' => 'Publiceren', 'SortOrder' => 8],
    ];

    /**
     * @var 38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848
     *
     *
     *
     *
     * Vocabulaire
     * Auteurs
     *
     * Bestand / URL
     *
     *

     */
    private static $defaultTemplateMetaFieldsLearningObject = [
        // Algemene gegevens
        ['Uuid' => '8084cae3-3f2c-4987-b0e5-e34cae33bd74', 'MetaField_Uuid' => '4d88565e-665c-4db9-b5a6-65aa9c272e05', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Title
        ['Uuid' => '946ccab9-8076-4416-a5b0-d4d97f159f26', 'MetaField_Uuid' => '8a042165-fda5-44f0-8863-27784f4d37e7', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Subtitle
        ['Uuid' => '306f5f91-7fae-4d71-be7d-a52b34ae35dc', 'MetaField_Uuid' => 'da2e5ff2-cb76-470e-bb98-e6b8a9653223', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Samenvatting
        ['Uuid' => 'ec5b519b-55c4-4dea-a880-1299d0de4c49', 'MetaField_Uuid' => '7bc1c220-d83e-4cac-a5a0-ba2263b53b77', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Organisatie
        ['Uuid' => 'c6e1d6f6-53db-411a-a1da-3d2b4861a44b', 'MetaField_Uuid' => 'a446aed3-8bf1-449b-a825-723d89b3759e', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Publicatiedatum
        ['Uuid' => 'b03df795-b373-464a-8d7e-0b9de05d3635', 'MetaField_Uuid' => '270c698d-81ff-4a5d-aa67-7cf76f63bf78', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Opleiding
        ['Uuid' => '4308f623-3a52-47c3-b35c-ba191f5518e7', 'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Vakgebied
        ['Uuid' => '07147458-b37b-488e-8e0b-1696d04ea227', 'MetaField_Uuid' => '782f655c-1d4a-4123-9641-e784ca566f9d', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Niveau
        ['Uuid' => 'b9a37232-a658-4057-82b1-e552dd02bd5b', 'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Gebruikersrecht
        ['Uuid' => '1f5b2419-7f61-448a-8748-dd54c9dfa21d', 'MetaField_Uuid' => 'b9205847-d011-4bac-ba58-5d439a7e1222', 'IsRequired' => 0, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Aantal pagina's
        ['Uuid' => '38ab29d3-2fae-41f4-a32f-8ab22ef19f76', 'MetaField_Uuid' => '80d99f27-2c06-4958-ad51-2264a98fd463', 'IsRequired' => 0, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Plaats
        ['Uuid' => '5ae232fe-af8e-4841-9be2-8f17acd0e924', 'MetaField_Uuid' => 'eade93d7-e432-4240-b408-b704677903c7', 'IsRequired' => 0, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Trefwoorden
        ['Uuid' => 'e55d11d6-0867-11eb-adc1-0242ac120002', 'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002', 'IsRequired' => 0, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Vakvocabulaire
        ['Uuid' => 'fd57411e-0a1d-4c75-aa3e-7177f6533b14', 'MetaField_Uuid' => '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Taal
        ['Uuid' => '25560894-4d63-4fa5-939e-d5962132204c', 'MetaField_Uuid' => 'a32f4380-30fd-43ae-b5bb-81f3e731a155', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // DOI

        // Aanvullende gegevens
        ['Uuid' => '987e37c9-7e99-4e16-9e3d-5b3ca41309e8', 'MetaField_Uuid' => 'b075f688-ba32-4eb7-b16a-92ed0cb1d9cf', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Bruikbaar als
        ['Uuid' => '28fb006b-fe67-4600-952c-cbaf2e2577c5', 'MetaField_Uuid' => '0754752f-fc85-4009-a874-9c541b216eb5', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Soort leermateriaal
        ['Uuid' => 'ddc61385-9896-42e7-88d5-1d88183ee4b5', 'MetaField_Uuid' => '7122da5e-3e79-4996-a554-b8f8f1478fe2', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Beoogde eindgebruiker
        ['Uuid' => 'c4b508d8-2901-4f73-8c80-7392a8042f77', 'MetaField_Uuid' => '0b2bc453-d08c-4630-863a-910a01620202', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Beoogde leeftijdsgroep
        ['Uuid' => 'a054466a-7f8a-4b4b-b786-d4ae8555c559', 'MetaField_Uuid' => 'd5fa4920-43a4-11eb-b378-0242ac130002', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Kosteloos beschikbaar
        ['Uuid' => '2307c887-6dda-489f-9b3e-e7f26796e7f3', 'MetaField_Uuid' => 'acac043d-6ed1-4d96-bc90-993038ef2574', 'IsRequired' => 1, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Gekoppelde lesmaterialen

        // Bestanden
        ['Uuid' => '8526f6f3-2181-4de0-9a70-c678f1ab0273', 'MetaField_Uuid' => '27c41422-eca0-4350-b32b-6ee20135705e', 'IsRequired' => 0, 'Template_Uuid' => '38fe1064-0ead-4d9f-a5cf-b7f1dcbd6848', 'TemplateSection_Uuid' => 'b4b64370-5538-4221-847a-46285108cf12'], // Uploads

    ];
    private static $defaultTeamplateMetaFieldsRepoItemLearningObject = [
        ['Uuid' => '9b5671b1-923a-475c-8ea0-7f87d2575e7d', 'MetaField_Uuid' => 'c210986f-0c47-45a1-b5f8-40d065b8a47f', 'IsRequired' => 1, 'Template_Uuid' => 'b3521815-26d2-4cf5-a7d4-6f9aa8b74187', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Learning object
        ['Uuid' => 'bb56bd82-5660-47d7-b4de-1a29cce9d025', 'MetaField_Uuid' => '2a70a6fe-fd3a-48e2-a957-4c8fa737fd78', 'IsRequired' => 1, 'Template_Uuid' => 'b3521815-26d2-4cf5-a7d4-6f9aa8b74187', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Relation
    ];
    private static $defaultTemplateMetaFieldsPersonInvolved = [
        // Algemene gegevens
        ['Uuid' => '9d75052b-6cad-4af1-a44e-045f734345f8', 'MetaField_Uuid' => 'a361a5a9-a80b-4e2c-8145-a60e2fce9acf', 'IsRequired' => 1, 'Template_Uuid' => 'b08fa914-8ac8-4bc5-8708-a96b26858819', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Person
        ['Uuid' => 'a028098c-1ae4-41dd-9e34-125939005bcc', 'MetaField_Uuid' => '720ede7f-52c3-4672-9327-b7e1358d5463', 'IsRequired' => 1, 'Template_Uuid' => 'b08fa914-8ac8-4bc5-8708-a96b26858819', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Role
    ];

    private static $defaultTemplateMetaFieldsScriptie = [
        // Algemene gegevens
        ['Uuid' => '1eaf241d-3edc-4642-a550-c9f4e06fbe5e', 'MetaField_Uuid' => '4d88565e-665c-4db9-b5a6-65aa9c272e05', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Title
        ['Uuid' => '5f7a4f6c-dea0-4b8e-8929-e948d477f0f8', 'MetaField_Uuid' => '8a042165-fda5-44f0-8863-27784f4d37e7', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Subtitle
        ['Uuid' => '24b84417-2061-400f-9104-2f5a8b807b6c', 'MetaField_Uuid' => 'da2e5ff2-cb76-470e-bb98-e6b8a9653223', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Samenvatting
        ['Uuid' => '6cedc94a-6514-4c79-a7e4-2a798607379d', 'MetaField_Uuid' => '58cc2eed-f5ff-408c-8872-762ddbb12724', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Type
        ['Uuid' => 'cea93079-0546-4467-9e2c-143aac242104', 'MetaField_Uuid' => '7bc1c220-d83e-4cac-a5a0-ba2263b53b77', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399', 'IsReadOnly' => 1], // Organisatie
        ['Uuid' => '963b4762-3f28-4fd1-962b-80ca9dabafc5', 'MetaField_Uuid' => '32c3d3bc-d74d-4cf1-ac55-35044cfbcd7e', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Taal
        ['Uuid' => 'b027a8c9-9390-4e09-b0bf-b893982c6368', 'MetaField_Uuid' => '270c698d-81ff-4a5d-aa67-7cf76f63bf78', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Opleiding
        ['Uuid' => '39ee6666-6ca3-43a0-b24f-c59cd02f60a7', 'MetaField_Uuid' => '782f655c-1d4a-4123-9641-e784ca566f9d', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Niveau
        ['Uuid' => '8cac7577-7d0c-44d5-83c8-a4863bfbe1f5', 'MetaField_Uuid' => '01f7969c-71db-474d-a52d-f73a212a409d', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Vakgebied
        ['Uuid' => '229700de-fbfe-4f9b-a8e9-d9839ce4079a', 'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Gebruikersrecht
        ['Uuid' => '5605dbcb-5087-4ebb-a383-ddc5f83e3a7e', 'MetaField_Uuid' => 'b9205847-d011-4bac-ba58-5d439a7e1222', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Aantal pagina's
        ['Uuid' => 'a09314e8-ca70-431d-8f9f-901dd61799ad', 'MetaField_Uuid' => 'a446aed3-8bf1-449b-a825-723d89b3759e', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Publicatiedatum
        ['Uuid' => 'd5750436-73d8-45c4-a6ea-b27c16ed1335', 'MetaField_Uuid' => '5cede4de-2e88-4cf5-a1d7-a4f22c3241a6', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Betrokkenen (incl. begeleider)
        ['Uuid' => 'e331f5f7-eeba-472d-a054-36cdf31a6fab', 'MetaField_Uuid' => 'eade93d7-e432-4240-b408-b704677903c7', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Trefwoorden
        ['Uuid' => '0f557fa0-0868-11eb-adc1-0242ac120002', 'MetaField_Uuid' => 'a615fa02-0866-11eb-adc1-0242ac120002', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'], // Vakvocabulaire

        // Aanvullende gegevens
        ['Uuid' => 'c34f7c0c-b6db-4a5d-9ac9-7e2ccbb1f291', 'MetaField_Uuid' => '55f4fd0c-2172-4bac-9ce8-df2fd7e4ca49', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Opmerkingen
        ['Uuid' => '1dbac3e1-ab15-4821-848a-cf54404eda0b', 'MetaField_Uuid' => '818e53b7-1880-4003-86da-73df227f195e', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '4dc80203-0e32-431f-be22-2441e8d591f3'], // Datum van goedkeuring

        // Bestanden en links
        ['Uuid' => '6aa36f5a-f0e6-4129-a6cf-0bd39ba1aa4c', 'MetaField_Uuid' => '27c41422-eca0-4350-b32b-6ee20135705e', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => 'b4b64370-5538-4221-847a-46285108cf12'], // Uploads
        ['Uuid' => '05992caf-27b2-4476-a3bd-d710c818bca4', 'MetaField_Uuid' => '036ba1b7-67aa-4551-ab95-d539b472ed2c', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => 'b4b64370-5538-4221-847a-46285108cf12'], // Website URL / Profiel pagina

        // Link repoitem
        ['Uuid' => 'caff5959-ef52-47b4-ba6d-701606f9d941', 'MetaField_Uuid' => '80e3e484-1a87-4448-afb3-26a3e3698b7a', 'IsRequired' => 1, 'Template_Uuid' => '94661347-0626-43ed-afe1-c3d9aaee0e0f', 'TemplateSection_Uuid' => '5c277a59-55f8-45ce-b568-6a64e76ede38'], // URL
        ['Uuid' => '5ccc3af0-8ab5-483b-94ac-76d893019509', 'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d', 'IsRequired' => 1, 'Template_Uuid' => '94661347-0626-43ed-afe1-c3d9aaee0e0f', 'TemplateSection_Uuid' => '5c277a59-55f8-45ce-b568-6a64e76ede38'], // Gebruikersrecht

        // Attachment repoitem
        ['Uuid' => 'deee1d2c-772e-4f13-96e2-81469612fe5d', 'MetaField_Uuid' => '2af0793b-fc97-455c-ae71-494425905868', 'IsRequired' => 1, 'Template_Uuid' => 'c5ea3076-d11b-4611-a7af-c424cc0dcf15', 'TemplateSection_Uuid' => '5c277a59-55f8-45ce-b568-6a64e76ede38'], // Bestand uploaden
        ['Uuid' => '37dab9ce-56c3-444b-86b0-0b579e792403', 'MetaField_Uuid' => '32efdf11-d6ce-482d-a0c7-67b509214f3a', 'IsRequired' => 1, 'Template_Uuid' => 'c5ea3076-d11b-4611-a7af-c424cc0dcf15', 'TemplateSection_Uuid' => '5c277a59-55f8-45ce-b568-6a64e76ede38'], // Bestandsnaam
        ['Uuid' => 'ae428109-f504-44c7-96f5-f8bd2537a1fc', 'MetaField_Uuid' => 'd418da1d-ca28-40b2-888f-b21dccbd9f5d', 'IsRequired' => 1, 'Template_Uuid' => 'c5ea3076-d11b-4611-a7af-c424cc0dcf15', 'TemplateSection_Uuid' => '5c277a59-55f8-45ce-b568-6a64e76ede38'], // Gebruikersrecht

        // Publiceren
        ['Uuid' => '43d680d3-df1b-4d3f-8ca4-c265c1ccd4af', 'MetaField_Uuid' => 'b493f283-bc76-4c88-8357-31ebe4314386', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '89892428-8614-4d25-b41a-b951ebee3670'], // Toestemming

        //Kanalen
        ['Uuid' => 'b06c51fc-f689-11ea-adc1-0242ac120002', 'MetaField_Uuid' => 'ee426828-527d-4271-8473-d7e25105705e', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '27825248-92f1-45cf-a8ae-f1661ff2ea32'], // Sharekit Publicatie kanaal ja/nee
        ['Uuid' => 'a7c9f08d-3a44-4fac-8f94-97e197e93989', 'MetaField_Uuid' => '25d32760-4c12-4a3d-9268-a6b9993bc126', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '27825248-92f1-45cf-a8ae-f1661ff2ea32'], // HBO Kennisbank kanaal ja/nee
        ['Uuid' => '440c3078-43a9-11eb-b378-0242ac130002', 'MetaField_Uuid' => '28503146-439e-11eb-b378-0242ac130002', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '27825248-92f1-45cf-a8ae-f1661ff2ea32'], // Archief ja/nee

        // NO CATEGORY
        /**
         * ['Uuid' => '30f167c3-b472-4384-8e46-c2f11bc3bc4b', 'MetaField_Uuid' => '270c698d-81ff-4a5d-aa67-7cf76f63bf78', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'],
         *
         * ['Uuid' => 'abf2de4f-bbff-4525-abb5-44b9ea5b7bab', 'MetaField_Uuid' => 'a55c8683-80a8-4161-b93a-e01dabc5001b', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'],
         * ['Uuid' => '6e23755b-a6d6-4a49-a14a-559924406f6a', 'MetaField_Uuid' => '7b813510-9f54-4620-8ead-470ced2f2e74', 'IsRequired' => 1, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'],
         * ['Uuid' => '6ff054cc-5485-43f9-abcc-b7ed00d7e94d', 'MetaField_Uuid' => '64f52f27-1b7b-4af3-bed9-25dfdeb1e82d', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399'],
         * ['Uuid' => '068b09b5-4052-4b54-b36a-a5fc2110155e', 'MetaField_Uuid' => '6c3de831-36dc-49d0-9c66-5deccb2f15b6', 'IsRequired' => 0, 'Template_Uuid' => 'd38365bd-1fb4-4928-b3d6-501a5d7af282', 'TemplateSection_Uuid' => '492ce7b2-2041-4019-9b6c-076318246399']
         */
    ];

    function run($request) {
        $this->loadDefaults(self::$defaultMetaFieldTypes, MetaFieldType::class);
        $this->loadDefaults(self::$defaultMetaFields, MetaField::class);
        $this->loadDefaults(self::$defaultMetaFieldOptions, MetaFieldOption::class);
        $this->loadDefaults(self::$defaultTemplateSections, TemplateSection::class);
        $this->loadDefaults(self::$defaultTemplateMetaFieldsScriptie, TemplateMetaField::class, true);
        $this->loadDefaults(self::$defaultTemplateMetaFieldsPersonInvolved, TemplateMetaField::class, true);
        $this->loadDefaults(self::$defaultTemplateMetaFieldsLearningObject, TemplateMetaField::class, true);
        $this->loadDefaults(self::$defaultTeamplateMetaFieldsRepoItemLearningObject, TemplateMetaField::class, true);
    }

    function loadDefaults(array $defaults, $objClassName, $autoSort = false) {
        $objClass = new ReflectionClass($objClassName);
        if (!$objClass->isSubclassOf(DataObject::class)) {
            echo 'Object is not a subclass of Dataobject!';
            return;
        }
        $sortOrder = 1;
        foreach ($defaults as $default) {
            $defaultExists = $objClassName::get()->filter(['Uuid' => $default['Uuid']])->first();
            if (is_null($defaultExists)) {
                $defaultItem = $objClassName::create();
                foreach ($default as $fieldName => $value) {
                    if (substr($fieldName, -5) === '_Uuid') {
                        // lookup field
                        $subObjClassName = substr($fieldName, 0, -5);
                        $subObjClassFullName = 'SurfSharekit\Models\\' . $subObjClassName;
                        print_r($subObjClassName);
                        echo("<br>");
                        try {
                            $subObjClass = new ReflectionClass($subObjClassFullName);
                            print_r($subObjClass);
                            echo("<br>");
                            if ($subObjClass->isSubclassOf(DataObject::class)) {
                                print_r($value);
                                echo("<br>");
                                $subObj = $subObjClassFullName::get()->filter('Uuid', $value)->first();
                                print_r($subObj);
                                echo("<br>");
                                if (!is_null($subObj)) {
                                    $value = $subObj->ID;
                                    $fieldName = $subObjClassName . 'ID';
                                    print_r($fieldName);
                                    echo("<br>");
                                    $defaultItem->setField($fieldName, $value);
                                }
                            }
                        } catch (\ReflectionException $e) {
                            print_r($e->getMessage());
                            // skip
                        }
                    } else {
                        $defaultItem->setField($fieldName, $value);
                    }
                }
                if ($autoSort) {
                    $defaultItem->setField('SortOrder', $sortOrder);
                    $sortOrder++;
                }
                try {
                    $defaultItem->write();
                } catch (ValidationException $e) {
                    // ignore
                }
            }
        }
    }
}