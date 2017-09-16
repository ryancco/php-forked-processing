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
    /** @var array $loopsCounted */
    private $loopsCounted = array();
    
    /**
     * Entry point for each child worker
     */
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
        
        $this->loopsCounted[$max] += 1;
    }

    private function formatOutput($loops)
    {
        return "Pass #" . $loops . " [PID " . getmypid() . "]\n";
    }
    
    private function recordLoopsCounted()
    {
        try {
            $queue = new Queue(); // Does not exist
        } catch (Exception $e) {
            exit("Unable to instantiate new Queue: " . $e->getMessage());
        }
        $queue->push($this->loopsCounted);
    }

    /**
     * Exit point for each child worker
     * Make all of your garbage collection and stats recording calls here
     */
    public function __destruct()
    {
        echo "__destruct() [PID " . getmypid() . "]\n";
        $this->recordLoopsCounted();
    }
}

$wd = new WorkerDaemon(5, 300);
$wd->executeJob(new TestJob());
```

## License
This library open-sourced software licensed under the [MIT License](http://opensource.org/licenses/MIT)
