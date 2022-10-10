<?php
return [
    'kafka'  => [
        'bootstrap.servers' => '127.0.0.1:9092',
        'group.id' => 'rlkafka'
    ],

    'handlers' => [
//        'my-topic:updated' => \App\Handlers\DefaultHandler::class,
    ],
];
