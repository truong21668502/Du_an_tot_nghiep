<?php

namespace App\Observers;

use App\Models\TestRealTime;

namespace App\Observers;

use App\Models\TestRealTime;
use App\Events\TestEvent;

class TestRealTimeObserver
{
    public function created(TestRealTime $testRealTime): void
    {
        event(new TestEvent($testRealTime));
    }
}

