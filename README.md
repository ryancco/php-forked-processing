## php-forked-processing (forker)

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/36e4b65f1052459dbd85ea062187bcca)](https://www.codacy.com/app/ryancco/php-forked-processing?utm_source=github.com&utm_medium=referral&utm_content=ryancco/php-forked-processing&utm_campaign=badger)

Asynchronous processing in PHP via process forking

## Example implementation
The below is a farcical example of creating and asynchronously processing a job between 5 forked "child" processes for a total run time of 600 seconds as specified. Should a "child" process die, fail, or be completed before the 600 seconds, a new "child" process will be immediately spun up in it's place. Feel free to run this example and watch the terminal output while killing off and letting "child" processes finish their job.

```php
<?php

require_once 'vendor/autoload.php';

use \ryancco\forker\WorkerDaemon;
use \ryancco\forker\JobInterface;

class TestJob implements JobInterface
{
    public function __invoke()
    {
        for ($i = 1; $i <= 10; $i++) {
            echo $this->formatOutput($i);
            sleep(10);
        }
    }

    private function formatOutput($loops)
    {
        return "Pass #" . $loops . " my PID is " . getmypid() . "\n";
    }
}

$wd = new WorkerDaemon(5, 600);
$wd->executeJob(new TestJob());

```

## License
This library open-sourced software licensed under the [MIT License](http://opensource.org/licenses/MIT)
