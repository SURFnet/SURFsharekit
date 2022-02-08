<?php

namespace SurfSharekit\Api;

use Exception;

class IdDoesNotExistException extends Exception {
}

class BadResumptionTokenException extends Exception {
}

class BadArgumentException extends Exception {
}

class CannotDisseminateFormatException extends Exception {
}

class NoMetadataFormatsException extends Exception {
}

class BadChannelException extends Exception {
}

class ChannelNotAllowedException extends Exception {
}

class NoRecordsMatchException extends Exception {
}