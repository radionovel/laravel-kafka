<?php
declare(strict_types=1);

namespace RlKafka\Console\Commands;

use Illuminate\Console\Command;
use RlKafka\Consumers\RlKafkaConsumer;

class ConsumeCommand extends Command
{
    protected $signature = 'rlkafka:consume';
    protected $description = 'Kafka consumer';

    public function handle(RlKafkaConsumer $consumer)
    {
        $consumer->consume();
    }
}
