<?php

namespace SurfSharekit\constants;

class RepoItemTypeConstant {

    // Primary
    const PUBLICATION_RECORD = "PublicationRecord";
    const LEARNING_OBJECT = "LearningObject";
    const RESEARCH_OBJECT = "ResearchObject";
    const DATASET = "Dataset";
    const PROJECT = "Project";
    const PRIMARY_TYPES = [self::PUBLICATION_RECORD, self::LEARNING_OBJECT, self::RESEARCH_OBJECT, self::DATASET, self::PROJECT];


    // Secondary
    const REPOITEM_REPOITEM_FILE = "RepoItemRepoItemFile";
    const REPOITEM_LEARNING_OBJECT= "RepoItemLearningObject";
    const REPOITEM_LINK = "RepoItemLink";
    const REPOITEM_PERSON = "RepoItemPerson";
    const REPOITEM_RESEARCH_OBJECT = "RepoItemResearchObject";
    const SECONDARY_TYPES = [self::REPOITEM_REPOITEM_FILE, self::REPOITEM_LEARNING_OBJECT, self::REPOITEM_LINK, self::REPOITEM_PERSON, self::REPOITEM_RESEARCH_OBJECT];

    /**
     * @return array
     */
    public static function getAll(): array {
        return array_merge(
            self::PRIMARY_TYPES,
            self::SECONDARY_TYPES
        );
    }
}