<?php
declare(strict_types=1);


namespace RlKafka\Logger;

use Illuminate\Support\Facades\Log;
use RdKafka\Message;

class Logger
{
    /**.
     * @param  \RdKafka\Message  $message
     *
     * @return void
     */
    public function error(Message $message): void
    {
        Log::error(
            sprintf('Kafka consumer error: [%d] %s' . PHP_EOL, $message->err,  $message->errstr())
        );
    }
}
