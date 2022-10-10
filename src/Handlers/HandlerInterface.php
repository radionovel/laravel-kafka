<?php
declare(strict_types=1);

namespace RlKafka\Handlers;

interface HandlerInterface
{
    public function handle($payload);
}
