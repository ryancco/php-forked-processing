## php-forked-processing (forker)
Asynchronous processing in PHP via process forking

## Example
The below is a farcical example of creating and asynchronously processing a job between 5 forked "child" processes for a total run time of 300 seconds as specified. Should a "child" process die, fail, or be completed before the specified time, a new "child" process will be immediately spun up in it's place. Feel free to run this example and watch the terminal output while killing off and letting "child" processes finish their job.

```php
<?php

require_once 'vendor/autoload.php';

use ryancco\forker\JobInterface;
use ryancco\forker\WorkerDaemon;

declare(ticks=1); // PHP <= 5.6.*

class TestJob implements JobInterface
{
    // Entry point for each child worker
    public function __invoke()
    {
        echo "New child spawned! [PID " . getmypid() . "]\n";
        $this->countTo(5);
    }
    
    public function countTo($max = 5)
    {
        for ($i = 1; $i <= $max; $i++) {
            echo $this->formatOutput($i);
            sleep(10);
        }
    }

    private function formatOutput($loops)
    {
        return "Pass #" . $loops . " [PID " . getmypid() . "]\n";
    }

    // Will be executed on every graceful exit
    public function __destruct()
    {
        echo "__destruct() [PID " . getmypid() . "]\n";
    }
}

$wd = new WorkerDaemon(5, 300);
$wd->executeJob(new TestJob());
```

## License
This library open-sourced software licensed under the [MIT License](http://opensource.org/licenses/MIT)
