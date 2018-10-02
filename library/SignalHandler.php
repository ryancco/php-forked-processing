<?php

namespace ryancco\forker;

use ryancco\forker\Exceptions\SignalHandlerException;

class SignalHandler
{
    /** @var array $signals */
    private $signals = array("SIGCHLD" => SIGCHLD, "SIGINT" => SIGINT, "SIGTERM" => SIGTERM);
    /** @var array $signalQueue */
    public $signalQueue = array();
    /** @var WorkerDaemon $workerDaemon */
    private $workerDaemon;

    /**
     * SignalHandler constructor.
     *
     * @param WorkerDaemon $workerDaemon
     * @throws SignalHandlerException
     */
    public function __construct(WorkerDaemon $workerDaemon)
    {
        $this->workerDaemon = $workerDaemon;

        $this->registerSignals();

        $this->registerAsyncSignalHandling();
    }

    /**
     * @param int         $signal
     * @param null|int    $pid
     * @param null|string $status
     *
     * @throws SignalHandlerException
     */
    public function handle($signal, $pid = null, $status = null)
    {
        switch ($signal) {
            case SIGCHLD:
                $this->sigchldCallback($pid, $status);
                break;
            default:
                $this->workerDaemon->terminateJob();
                break;
        }
    }

    /**
     * @param int|null $pid
     * @param int|null $status
     */
    private function sigchldCallback($pid = null, $status = null)
    {
        if (is_null($pid)) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        while ($pid > 0) {
            if (($key = array_search($pid, $this->workerDaemon->currentWorkers)) !== false) {
                unset($this->workerDaemon->currentWorkers[$key]);
            } else {
                $this->signalQueue[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }

    /**
     * @throws SignalHandlerException
     */
    private function registerSignals()
    {
        foreach ($this->signals as $str => $int) {
            if (!pcntl_signal($int, array($this, "handle"))) {
                throw new SignalHandlerException("Unable to register signal: {$str} ({$int})");
            }
        }
    }

    private function registerAsyncSignalHandling()
    {
        if (PHP_VERSION_ID >= 70100) {
            pcntl_async_signals(true);
        }
    }
}
