<?php

namespace ryancco\forker;

use ryancco\forker\Exceptions\SignalHandlerException;

class SignalHandler
{
    /** @var array $signals */
    private $signals = array("SIGHUP" => SIGHUP, "SIGINT" => SIGINT, "SIGTERM" => SIGTERM, "SIGCHLD" => SIGCHLD);
    /** @var array $signalQueue */
    public $signalQueue = array();
    /** @var \WorkerDaemon $workerDaemon */
    private $workerDaemon;

    /**
     * SignalHandler constructor.
     *
     * @param WorkerDaemon $workerDaemon
     */
    public function __construct(WorkerDaemon $workerDaemon)
    {
        $this->workerDaemon = $workerDaemon;

        $this->registerSignals();
    }

    /**
     * @param int      $signal
     * @param null|int $pid
     * @param string   $status
     *
     * @throws SignalHandlerException
     */
    public function handle($signal, $pid = null, $status = null)
    {
        try {
            $callbackName = $this->normalizeSignal($signal) . "Callback";
            if (method_exists($this, $callbackName)) {
                $this->$callbackName($pid, $status);
            } else {
                $this->DefaultCallback();
            }
        } catch (\Exception $e) {
            throw new SignalHandlerException($e->getMessage(), $e->getCode());
        }
    }

    private function DefaultCallback()
    {
        $this->workerDaemon->jobInProgress = false;
        $this->workerDaemon->terminateJob();
    }

    private function SigchldCallback($pid = null, $status = null)
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
     * @param int|string $signal
     *
     * @return string
     * @throws SignalHandlerException
     */
    private function normalizeSignal($signal)
    {
        if (($key = array_search($signal, $this->signals)) !== false) {
            return ucfirst(strtolower($key));
        } elseif (array_key_exists($signal, $this->signals)) {
            return ucfirst(strtolower($signal));
        }

        throw new SignalHandlerException("Unable to normalize signal - not recognized: {$signal}");
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
}
