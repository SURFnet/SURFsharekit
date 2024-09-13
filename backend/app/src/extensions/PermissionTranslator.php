<?php

/**
 * Class PermissionTranslator
 * A class that tries to automatically translate descriptions of permission generated with the RelationaryPermissionProviderTrait
 */
class PermissionTranslator {
    static $objectTranlationMap = [
        'a Attachment RepoItem' => 'een bijlage',
        'a LearningObject RepoItem' => 'een lespakket',
        'a ResearchObject RepoItem' => 'een onderzoek',
        'a Link RepoItem' => 'een link',
        'a PersonInvolved RepoItem' => 'een betrokkene',
        'a PublicationRecord RepoItem' => 'een scriptie',
        'a Dataset RepoItem' => 'een dataset',
        'a Project RepoItem' => 'een project',
        'members' => 'leden',
        'member' => 'lid',
        'groups' => 'groepen',
        'group' => 'groep',
        'templates' => 'sjablonen',
        'template' => 'sjabloon',
        'leermateriaal' => 'leermateriaal',
        'publicationRecord' => 'scriptie',
        'dataset' => 'dataset',
        'project' => 'project',
        'personInvolved' => 'betrokkene',
        'repoItems' => 'ingevulde sjablonen',
        'RepoItems' => 'ingevulde sjablonen',
        'repoItem' => 'ingevuld sjabloon',
        'RepoItem' => 'ingevuld sjabloon',
        'institutes' => 'organisaties',
        'institute' => 'organisatie',
    ];

    static $actionTranlationMap = [
        'View' => 'Inzien van',
        'Edit' => 'Wijzigen van',
        'Delete' => 'Verwijderen van',
        'Create' => 'Aanmaken van'
    ];

    static $relationTranlationMap = [
        'they are co-author of' => 'waar hen coauteur van is',
        'of their own institute' => 'in hun eigen organisatie',
        'of institutes below their own level' => 'in suborganisaties',
        'below their own level' => 'op een lager organisatieniveau',
        'their own institute' => 'hun eigen organisatie',
        'their own' => 'hun eigen',
        'themselves' => 'zichzelf'
    ];

    /**
     * Method that translates an english description generated with RelationaryPermissionProviderTrait to a Dutch variant
     * @param $permissionDescription
     * @return mixed Dutch variant of description
     */
    public static function translate($permissionDescription) {
        $translation = $permissionDescription;

        foreach (static::$actionTranlationMap as $en => $nl) {
            if (strpos($translation, $en) !== false) {
                $translation = str_replace($en, $nl, $translation);
                break;
            }
        }

        foreach (static::$relationTranlationMap as $en => $nl) {
            if (strpos($translation, $en) !== false) {
                $translation = str_replace($en, $nl, $translation);
                break;
            }
        }

        foreach (static::$objectTranlationMap as $en => $nl) {
            if (strpos($translation, $en) !== false) {
                $translation = str_replace($en, $nl, $translation);
                break;
            }
        }

        return $translation;
    }
}