<?php

namespace ryancco\forker;

interface JobInterface
{
    public function __invoke($instanceNumber = 1);

    public function __destruct();
}
