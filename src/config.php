<?php

return [
	'db_start' => env('HPR_DB_START', 201711),

    'hashid' => [
		'length' => 8,
		'salt' => env('HPR_HASH_SALT', 3.14159265359),
		'dictionary' => env(
			'HP_HASH_DICTIONARY',
			'qPkOasSbAufMB6r94CjH0hKLt5dUweIz3lDgNyxvn8TcYoGiV71XRQmW2EZJFp'
		),
		'strHashKey' => env('HPR_HASH_STR_KEY', 'b7cda4e9930c0622110a89f0c55a3140'),
	],
];