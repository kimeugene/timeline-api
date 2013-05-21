<?php

namespace Timeline;


class PostData extends Common
{

    public function __construct($log)
    {
        parent::__construct($log);
    }

    public function process($params)
    {
        $data = array(
            'TableName'     => 'history_new',
            'Item'          => array(
                'email' => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['email']),
                'timestamp' => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['timestamp']),
                'long'  => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['long']),
                'lat'  => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['lat']),
            )
        );

        $this->log->addDebug("EMail: " . $params['email'] );

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
