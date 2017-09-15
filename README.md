## php-forked-processing (forker)
Asynchronous processing in PHP via process forking

## Example
The below is a farcical example of creating and asynchronously processing a job between 5 forked "child" processes for a total run time of 600 seconds as specified. Should a "child" process die, fail, or be completed before the 600 seconds, a new "child" process will be immediately spun up in it's place. Feel free to run this example and watch the terminal output while killing off and letting "child" processes finish their job.

```php
<?php
declare(ticks=1);

require_once 'vendor/autoload.php';

use ryancco\forker\JobInterface;
use ryancco\forker\WorkerDaemon;

class TestJob implements JobInterface
{
    public function __invoke()
    {
        echo "New child spawned! [PID " . getmypid() . "]\n";
        for ($i = 1; $i <= 5; $i++) {
            echo $this->formatOutput($i);
            sleep(10);
        }
    }

    private function formatOutput($loops)
    {
        return "Pass #" . $loops . " [PID " . getmypid() . "]\n";
    }

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
