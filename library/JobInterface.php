<?php

namespace ryancco\forker;

interface JobInterface
{
    public function __invoke();
}
