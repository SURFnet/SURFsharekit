<?php

namespace SurfSharekit\constants;

class RoleConstant {
    // Main roles
    const MEMBER = 'Default Member';
    const STUDENT = 'Student';
    const WORKSADMIN = 'Worksadmin';
    const APIUSER = 'API';
    const STAFF = 'Staff';
    const SUPPORTER = 'Supporter';
    const SITEADMIN = 'Siteadmin';
    const UPLOAD_API_USER = 'UploadApiUser';

    // Sub roles
    const RESEARCH_UPLOADER = "Research Uploader";
    const LEARNING_MATERIAL_UPLOADER = "Learning Material Uploader";
    const THESIS_UPLOADER = "Thesis Uploader";
    const DATASET_UPLOADER = "Dataset Uploader";
    const PROJECT_UPLOADER = "Project Uploader";
    const PROFILE_EDITOR = "Profile Editor";
    const ORGANISATION_EDITOR = "Organisation Editor";

    // Main roles are the default roles. Default roles CANNOT be removed from their respective groups.
    const MAIN_ROLES = [
        self::WORKSADMIN,
        self::APIUSER,
        self::UPLOAD_API_USER,
        self::SITEADMIN,
        self::SUPPORTER,
        self::STAFF,
        self::STUDENT,
        self::MEMBER
    ];

    // Sub roles are additional roles beside the main roles. These sub roles MAY be removed from a particular group
    const SUB_ROLES = [
        self::RESEARCH_UPLOADER,
        self::LEARNING_MATERIAL_UPLOADER,
        self::THESIS_UPLOADER,
        self::DATASET_UPLOADER,
        self::PROFILE_EDITOR,
        self::ORGANISATION_EDITOR,
        self::PROJECT_UPLOADER
    ];

    // A specific group is created For each of the following roles upon creating a new Institute
    const DEFAULT_INSTITUTE_ROLES = [
        self::SITEADMIN,
        self::SUPPORTER,
        self::STAFF,
        self::STUDENT,
        self::MEMBER,
        self::UPLOAD_API_USER
    ];

    const ROLE_SORT = [
        self::WORKSADMIN,
        self::SITEADMIN,
        self::SUPPORTER,
        self::STAFF,
        self::STUDENT,
        self::MEMBER
    ];

    // This map links a set of sub roles to a main role. The default role of a group determines which sub roles are added upon creating a new institute.
    const MAIN_TO_SUB_ROLE_MAP = [
        self::MEMBER => [],
        self::STUDENT => [self::THESIS_UPLOADER],
        self::STAFF => [self::THESIS_UPLOADER, self::RESEARCH_UPLOADER, self::LEARNING_MATERIAL_UPLOADER],
        self::SUPPORTER => [],
        self::SITEADMIN => [],
    ];
}