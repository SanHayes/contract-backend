<?php

use app\command\BatchSync;
use app\command\Collect;
use app\command\Stake;
use app\command\Sync;
use app\command\Tx;

return [
    Collect::class,
    Sync::class,
    Stake::class,
    Tx::class,
    BatchSync::class,
    \app\command\Test::class,
    \app\command\flex\Stake::class,
    \app\command\UserLevel::class,
    \app\command\flex\Pledge::class,
];