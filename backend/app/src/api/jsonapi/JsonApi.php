<?php

namespace SurfSharekit\Api;

class JsonApi {
    public const CONTENT_TYPE = 'application/vnd.api+json';

    public const TAG_META = 'meta';
    public const TAG_LINKS = 'links';
    public const TAG_LINKS_SELF = 'self';
    public const TAG_LINKS_FIRST = 'first';
    public const TAG_LINKS_LAST = 'last';
    public const TAG_LINKS_PREVIOUS = 'prev';
    public const TAG_LINKS_NEXT = 'next';
    public const TAG_LINKS_RELATED = 'related';
    public const TAG_TOTAL_COUNT = 'totalCount';

    public const TAG_ERRORS = 'errors';
    public const TAG_ERROR_TITLE = 'title';
    public const TAG_ERROR_DETAIL = 'detail';
    public const TAG_ERROR_CODE = 'code';

    public const TAG_DATA = 'data';
    public const TAG_ID = 'id';
    public const TAG_TYPE = 'type';
    public const TAG_ATTRIBUTES = 'attributes';

    public const TAG_RELATIONSHIPS = 'relationships';

    public const TAG_INCLUDED = 'included';

    public const TAG_FILTERS = 'filters';
}

class JsonApiOperations {
    public const CONTENT_TYPE = 'application/vnd.api+json;ext="https://jsonapi.org/ext/atomic"';

    public const TAG_ATOMIC_OPERATION = 'atomic:operations';
    public const TAG_ATOMIC_RESULTS = 'atomic:RESULTS';
    
    public const TAG_OPERATION = 'op';
    public const TAG_OPERATION_ADD = 'add';
    public const TAG_OPERATION_UPDATE = 'update';
    public const TAG_OPERATION_REMOVE = 'remove';
    public const TAG_REFERENCE = 'ref';
    public const TAG_HYPERLINK_REFERENCE = 'href';
}
