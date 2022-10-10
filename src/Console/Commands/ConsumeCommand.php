<?php
declare(strict_types=1);

namespace RlKafka\Console\Commands;

use RlKafka\Consumers\RlKafkaConsumer;
use Illuminate\Console\Command;

class ConsumeCommand extends Command
{
    protected $signature = 'rlkafka:consume';
    protected $description = 'Kafka consumer';

    public function handle(RlKafkaConsumer $consumer)
    {
        $consumer->consume();
    }
}
