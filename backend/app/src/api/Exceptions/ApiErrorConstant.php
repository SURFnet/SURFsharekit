<?php

namespace SurfSharekit\Api\Exceptions;

class ApiErrorConstant {
    // Generic API errors
    const GA_BR_001 = ["code" => "GA_BR_001", "description" => "Incorrect post body", "message" => "Incorrect post body"];
    const GA_BR_002 = ["code" => "GA_BR_002", "description" => "Missing parameters", "message" => "There are one or more requests parameters missing"];
    const GA_BR_003 = [ "code" => "GA_BR_003", "description" => "Unsupported filter", "message" => "Filter not supported for this object type"];
    const GA_BR_004 = ["code" => "GA_BR_004", "description" => "Missing and/or invalid fields", "message" => "There are one or more fields missing or invalid"];
    const GA_BR_005 = ["code" => "GA_BR_005", "description" => "Resource already exists", "message" => "The provided resource already exists"];
    const GA_BR_006 = ["code" => "GA_BR_006", "description" => "Invalid filter", "message" => "One or more of the provided filters is unsupported or invalid"];
    const GA_NF_001 = ["code" => "GA_NF_001", "description" => "Unknown request path", "message" => "Request path could not be found"];
    const GA_NF_002 = ["code" => "GA_NF_002", "description" => "Resource not found", "message" => "Requested resource could not be found"];
    const GA_FB_001 = ["code" => "GA_FB_001", "description" => "Insufficient permissions", "message" => "You do not have the required permissions to perform this action"];
    const GA_UA_001 = ["code" => "GA_UA_001", "description" => "Missing authorization header", "message" => "Missing authorization header"];
    const GA_UA_002 = ["code" => "GA_UA_002", "description" => "Invalid bearer token", "message" => "The provided bearer token was found invalid"];
    const GA_UA_003 = ["code" => "GA_UA_003", "description" => "Provided credentials invalid", "message" => "Invalid client_id and client_secret combination"];
    const GA_PTL_001 = ["code" => "GA_PTL_001", "description" => "Payload too large", "message" => "The provided payload was too large"];
    const GA_UMT_001 = ["code" => "GA_UMT_001", "description" => "Unsupported file extension", "message" => "The extension of the provided file is currently not supported"];
    const GA_NI_001 = ["code" => "GA_NI_001", "description" => "Not implemented", "message" => "The current action is not yet implemented"];
    const GA_ISE_001 = ["code" => "GA_ISE_001", "description" => "Unexpected error", "message" => "An unexpected error has occurred"];
    const GA_ISE_002 = ["code" => "GA_ISE_002", "description" => "Unexpected error during authentication", "message" => "Due to an unexpected error the provided bearer token could not be verified"];



    // Upload API errors
    const UA_BR_001 = ["code" => "UA_BR_001", "description" => "client_id parameter", "message" => "client_id parameter is missing or empty"];
    const UA_BR_002 = ["code" => "UA_BR_002", "description" => "client_secret parameter", "message" => "client_secret parameter is missing or empty"];
    const UA_BR_003 = ["code" => "UA_BR_003", "description" => "grant_type parameter", "message" => "grant_type parameter is missing or empty"];
    const UA_BR_004 = ["code" => "UA_BR_004", "description" => "institute parameter", "message" => "institute parameter is missing or empty"];
    const UA_BR_005 = ["code" => "UA_BR_005", "description" => "Missing file", "message" => "Exactly one file is expected, 0 found"];
    const UA_BR_006 = ["code" => "UA_BR_006", "description" => "Unable to replace file", "message" => "This file is linked to a RepoItem that does not have the 'Draft' status, please update the status to 'Draft' first"];
    const UA_BR_007 = ["code" => "UA_BR_007", "description" => "Position does not exist", "message" => "The provided position does not exist"];
    const UA_BR_008 = ["code" => "UA_BR_008", "description" => "Incorrect RepoItem status", "message" => "Could not perform the required action on this RepoItem because of its status"];
    const UA_BR_009 = ["code" => "UA_BR_009", "description" => "Incorrect DAI", "message" => "The provided DAI is incorrect. Please ensure it conforms to the required format and try again."];
    const UA_BR_010 = ["code" => "UA_BR_0010", "description" => "Incorrect ISNI", "message" => "The provided ISNI is incorrect. Please ensure it conforms to the required format and try again."];
    const UA_BR_011 = ["code" => "UA_BR_0011", "description" => "Incorrect ORCID", "message" => "The provided ORCID is incorrect. Please ensure it conforms to the required format and try again."];
    const UA_FB_001 = ["code" => "UA_FB_001", "description" => "No permissions for institute", "message" => "Missing permissions for the provided institute"];
    const UA_FB_002 = ["code" => "UA_FB_002", "description" => "Authenticated for wrong institute", "message" => "You're trying to perform an action outside the scope of the institute you're authenticated for"];
    const UA_NF_001 = ["code" => "UA_NF_001", "description" => "File does not exist", "message" => "The file to replace does not exist"];
    const UA_NF_002 = ["code" => "UA_NF_002", "description" => "RepoItem does not exist", "message" => "RepoItem with this UUID does not exist"];
    const UA_ISE_001 = ["code" => "UA_USE_001", "description" => "Unexpected error during file processing", "message" => "An unexpected error occurred while processing the uploaded file(s)"];

}