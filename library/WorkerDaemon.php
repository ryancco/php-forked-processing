<?php

namespace ryancco\forker;

use ryancco\forker\Exceptions\WorkerDaemonException;

class WorkerDaemon
{
    /** @var int $maxWorkers */
    protected $maxWorkers;
    /** @var int $maxRunTime */
    protected $maxRunTime;
    /** @var bool $jobInProgress */
    protected $jobInProgress;
    /** @var SignalHandler $signalHandler */
    public $signalHandler;
    /** @var array $currentWorkers */
    public $currentWorkers = array();
    /** @var int $daemonPid */
    public $daemonPid;


    /**
     * WorkerDaemon constructor.
     *
     * @param int $maxWorkers
     * @param int $maxRunTime maximum execution time in seconds
     */
    public function __construct($maxWorkers = 1, $maxRunTime = 3600)
    {
        $this->daemonPid = getmypid();
        $this->maxWorkers = $maxWorkers;
        $this->maxRunTime = $maxRunTime;

        $this->signalHandler = new SignalHandler($this);
    }

    /**
     * @param JobInterface $job
     */
    public function executeJob(JobInterface $job)
    {
        $this->jobInProgress = true;
        $startTime = time();

        while ((time() <= ($this->maxRunTime + $startTime)) && $this->jobInProgress) {
            while (count($this->currentWorkers) < $this->maxWorkers) {
                $this->delegateJob($job);
            }
        }
    }

    public function terminateJob()
    {
        if ($this->daemonPid === getmypid()) {
            $this->jobInProgress = false;

            foreach ($this->currentWorkers as $pid) {
                posix_kill($pid, SIGTERM);
                pcntl_waitpid($pid, $status);
                pcntl_wexitstatus($status);
            }
        }
        exit(0);
    }

    /**
     * @param JobInterface $job
     *
     * @throws WorkerDaemonException
     */
    private function delegateJob(JobInterface $job)
    {
        try {
            $pid = pcntl_fork();
        } catch (\Exception $e) {
            throw new WorkerDaemonException($e->getMessage(), $e->getCode());
        }

        switch ($pid) {
            case -1:
                break;
            case 0:
                $job();
                $this->terminateJob();
                break;
            default:
                $this->currentWorkers[] = $pid;
                if (isset($this->signalHandler->signalQueue[$pid])) {
                    $this->signalHandler->handle(SIGCHLD, $pid, $this->signalHandler->signalQueue[$pid]);
                    unset($this->signalHandler->signalQueue[$pid]);
                }
                break;
        }
    }
}
