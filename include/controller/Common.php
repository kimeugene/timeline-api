<?php

namespace Timeline;

use Aws\Common\Aws;
use Aws\DynamoDb;
use Aws\DynamoDb\Enum;
use Aws\Common\Region;
use Aws\DynamoDb\Exception;
use Aws\DynamoDb\Enum\ComparisonOperator;

class Common
{

    protected $log;
    protected $instance;

    public function __construct($log)
    {
        $this->log = $log;
        $this->log->addInfo('Common controller');
        $aws = Aws::factory(ROOT_DIR . '/config/awsconfig.php');

        $this->instance = $aws->get('dynamodb_tl');
    }

}
