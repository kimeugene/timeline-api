<?php

namespace Timeline;


class PostData extends Common
{

    public function __construct($log)
    {
        parent::__construct($log);
    }

    public function process()
    {
        $data = array(
            'TableName'     => 'history',
            'Item'          => array(
                'email' => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $_POST['email']),
                'timestamp' => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $_POST['timestamp']),
                'long'  => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $_POST['long']),
                'lat'  => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $_POST['lat']),
            )
        );

        try
        {
            $response = $this->instance->putItem($data);
        }
        catch (\Exception $e)
        {
            $this->log->addError("ERROR:" . $e->getMessage());
        }

        if (isset($response['ConsumedCapacityUnits']) && $response['ConsumedCapacityUnits'] > 0)
        {
            $this->log->addDebug("OK. Consumed units: " . $response['ConsumedCapacityUnits']);
        }
        else
        {
            $this->log->addError("ERROR");
        }

    }
}
