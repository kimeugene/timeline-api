<?php


return array(
    'includes' => array('_aws'),
    'services' => array(
        'dynamodb_tl' => array(
            'extends' => 'dynamodb',
            'params'  => array(
                'key'    => 'AKIAJDQNCHJ7LNPPBK4Q',
                'secret' => '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',
                'region' => 'us-east-1'
            )
        )
    )
);

