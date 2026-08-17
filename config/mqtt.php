<?php

return [
    'incoming_queue' => env('MQTT_INCOMING_QUEUE', 'mqtt_incoming_jobs'),
    'outgoing_queue' => env('MQTT_OUTGOING_QUEUE', 'mqtt_outgoing_commands'),
];
