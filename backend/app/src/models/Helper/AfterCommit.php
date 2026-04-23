<?php

namespace SurfSharekit\Models\Helper;

use SilverStripe\ORM\DB;

/**
 * Simple "run after commit" queue for the current request.
 *
 * Usage:
 * - call AfterCommit::add($cb) inside code that runs within a DB transaction
 * - call AfterCommit::flushIfOutermost() right after transactionEnd()
 * - call AfterCommit::clear() on transactionRollback()
 */
final class AfterCommit
{
    /** @var array<int, callable> */
    private static array $callbacks = [];

    public static function add(callable $cb): void
    {
        self::$callbacks[] = $cb;
    }

    /**
     * Call this after you end a transaction.
     * It will only run callbacks once we are fully out of transactions (depth === 0).
     */
    public static function flushIfOutermost(): void
    {
        if (DB::get_conn()->transactionDepth() !== 0) {
            return;
        }

        $cbs = self::$callbacks;
        self::$callbacks = [];

        foreach ($cbs as $cb) {
            $cb();
        }
    }

    /**
     * Call this on rollback if you want to discard side effects.
     */
    public static function clear(): void
    {
        self::$callbacks = [];
    }
}


