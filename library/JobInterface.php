<?php

namespace ryancco\forker;

interface JobInterface
{
    public function __invoke($instanceNumber);

    public function __destruct();
}
