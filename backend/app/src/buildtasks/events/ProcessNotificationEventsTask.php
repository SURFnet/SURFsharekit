<?php

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SurfSharekit\Models\Event;
use SurfSharekit\Models\Helper\Constants;
use SurfSharekit\Models\Helper\Logger;

/**
 * This Task processes all Event objects from the SurfSharekit_Event Table.
 * These NotificationEventCreator class is responsible for creating all Event objects.
 * When processing events, the type of each event is checked and then the correct handler for that type of event is called
 **/

class ProcessNotificationEventsTask extends BuildTask {

    protected $title = 'Process all notification events from the SurfSharekit_Event Table';
    protected $description = 'This task processes all events from the SurfSharekit_Event table. The executed logic depends on the type of events that are being processed';

    private $successCount = 0;
    private $failedCount = 0;
    private $totalValidEventCount = 0;
    private $totalInvalidEventCount = 0;

    public function run($request) {
        set_time_limit(0);

        $events = Event::get();
        $validEvents = $events->filter(['Invalidated' => false]);
        $this->totalValidEventCount = count($validEvents);
        echo("Total amount of events to process: " . $this->totalValidEventCount);
        echo("<br><hr><br>");
        foreach ($validEvents as $event) {
            switch ($event->Type) {
                case Constants::CLAIM_STATUS_CHANGED_EVENT:
                    try {
                        Logger::debugLog("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'");
                        echo("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'"); echo("<br>");
                        ClaimStatusChangedEventHandler::getInstance()->process($event);
                        $this->successCount++;
                        Logger::debugLog("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '$event->Type'");
                        echo("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '$event->Type'"); echo("<br><br>");
                    } catch (Exception $e) {
                        Logger::debugLog("[PROCESSING_EVENT_FAILED] - Could not process Event with ID $event->ID, unknown type '$event->Type'");
                        Logger::debugLog($e->getMessage());
                        echo("<span style='color: red'>[PROCESSING_EVENT_FAILED] - Failed processing Event (ID $event->ID) of type '$event->Type'</span>"); echo("<br>");
                        echo("<span style='color: red'>" . $e->getMessage() . "</span>"); echo("<br><br>");
                        $this->failedCount++;
                    }
                    break;
                case Constants::REPO_ITEM_STATUS_CHANGED_EVENT:
                    try {
                        Logger::debugLog("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'");
                        echo("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'"); echo("<br>");
                        RepoItemStatusChangedEventHandler::getInstance()->process($event);
                        $this->successCount++;
                        Logger::debugLog("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '$event->Type'");
                        echo("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '$event->Type'"); echo("<br><br>");
                    } catch (Exception $e) {
                        Logger::debugLog("[PROCESSING_EVENT_FAILED] - Could not process Event with ID $event->ID, unknown type '$event->Type'");
                        Logger::debugLog($e->getMessage());
                        echo("<span style='color: red'>[PROCESSING_EVENT_FAILED] - Failed processing Event (ID $event->ID) of type '$event->Type'</span>"); echo("<br>");
                        echo("<span style='color: red'>" . $e->getMessage() . "</span>"); echo("<br><br>");
                        $this->failedCount++;
                    }
                    break;
                case Constants::SANITIZATION_PROCESS_END_EVENT:
                    try {
                        Logger::debugLog("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'");
                        echo("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'"); echo("<br>");
                        SanitizationProcessEndEventHandler::getInstance()->process($event);
                        $this->successCount++;
                        Logger::debugLog("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '$event->Type'");
                        echo("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '" . $event->Type . "'"); echo("<br><br>");
                    } catch (Exception $e) {
                        Logger::debugLog("[PROCESSING_EVENT_FAILED] - Could not process Event with ID $event->ID, unknown type '$event->Type'");
                        Logger::debugLog($e->getMessage());
                        echo("<span style='color: red'>[PROCESSING_EVENT_FAILED] - Failed processing Event (ID $event->ID) of type '" . $event->Type . "'</span>"); echo("<br>");
                        echo("<span style='color: red'>" . $e->getMessage() . "</span>"); echo("<br><br>");
                        $this->failedCount++;
                    }
                    break;
                case Constants::FILL_REQUEST_CREATED_EVENT:
                    try {
                        $this->logEventStart($event);
                        FillRequestCreatedEventHandler::getInstance()->process($event);
                        $this->successCount++;
                        $this->logEventEnd($event);
                    } catch (Exception $e) {
                        $this->logException($event, $e);
                        $this->failedCount++;
                    }
                    break;
                case Constants::RECOVER_REQUEST_CREATED_EVENT:
                     try {
                         $this->logEventStart($event);
                         RecoverRequestCreatedEventHandler::getInstance()->process($event);
                         $this->successCount++;
                         $this->logEventEnd($event);
                     } catch (Exception $e) {
                         $this->logException($event, $e);
                         $this->failedCount++;
                     }
                    break;
                case Constants::RECOVER_REQUEST_APPROVED_EVENT:
                    try {
                        $this->logEventStart($event);
                        RecoverRequestResponseEventHandler::getInstance()
                            ->setIsApproved(true)
                            ->process($event);
                        $this->successCount++;
                        $this->logEventEnd($event);
                    } catch (Exception $e) {
                        $this->logException($event, $e);
                        $this->failedCount++;
                    }
                    break;
                case Constants::RECOVER_REQUEST_DECLINED_EVENT:
                    try {
                        $this->logEventStart($event);
                        RecoverRequestResponseEventHandler::getInstance()
                            ->setIsApproved(false)
                            ->process($event);
                        $this->successCount++;
                        $this->logEventEnd($event);
                    } catch (Exception $e) {
                        $this->logException($event, $e);
                        $this->failedCount++;
                    }
                    break;
                default:
                    $this->failedCount++;
                    Logger::debugLog("[PROCESSING_EVENT_FAILED] - Could not process Event with ID $event->ID, unknown type '$event->Type'");
                    echo("<span style='color: red'>[PROCESSING_EVENT_FAILED] - Could not process Event with ID $event->ID, unknown type '$event->Type'</span>"); echo("<br><br>");
            }
        }
        $this->deleteAllEvents($events);
        $this->echoTaskReport();
    }

    private function logEventStart($event) {
        Logger::debugLog("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'");
        echo("[PROCESSING_EVENT_START] - Started Processing Event (ID $event->ID) of type '$event->Type'"); echo("<br>");
    }

    private function logEventEnd($event) {
        Logger::debugLog("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '$event->Type'");
        echo("[PROCESSING_EVENT_END] - Successfully processed Event (ID $event->ID) of type '" . $event->Type . "'"); echo("<br><br>");
    }

    private function logException($event, $exception) {
        Logger::debugLog("[PROCESSING_EVENT_FAILED] - Could not process Event with ID $event->ID, unknown type '$event->Type'");
        Logger::debugLog($exception->getMessage());
        echo("<span style='color: red'>[PROCESSING_EVENT_FAILED] - Failed processing Event (ID $event->ID) of type '" . $event->Type . "'</span>"); echo("<br>");
        echo("<span style='color: red'>" . $exception->getMessage() . "</span>"); echo("<br><br>");
    }

    private function deleteAllEvents($events) {
        echo("[DELETE] Deleting all processed and invalidated events..."); echo("<br>");
        $queryString = count($events) ? ('' . implode(',', $events->getIDList())) : '-1';
        DB::query("
            DELETE
            FROM SurfSharekit_Event
            WHERE ID IN ($queryString)
        ");
        echo("[DELETE] Successfully deleted all processed and invalidated events!"); echo("<br>");
    }

    private function echoTaskReport() {
        echo("<br>");
        echo("<span style='font-weight: bold'>Finished processing</span>");
        echo("<br><hr><br>");
        echo("Total invalidated events: $this->totalInvalidEventCount"); echo("<br>");
        echo("Total events processed: $this->totalValidEventCount"); echo("<br>");
        echo("Success: $this->successCount"); echo("<br>");
        echo("Failed: $this->failedCount"); echo("<br>");
    }

}