<?php

namespace Timeline;

class GetData extends Common
{
    public function __construct($log)
    {
        parent::__construct($log);
    }

    public function process()
    {
        $data = array(
            'TableName'     => 'history',
            'HashKeyValue'  => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $_GET['user_id']),
            "RangeKeyCondition" => array(
                "ComparisonOperator" => "BETWEEN",
                "AttributeValueList" => array(
                    array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => strtotime($_GET['date'] . " 00:00:00")),
                    array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => strtotime($_GET['date'] . " 23:59:59")),
                )
            )
        );

        $this->log->addInfo('Data array:' . print_r($data, true));

        try
        {
            $response = $this->instance->query($data);

            print_r($response['Items']);
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
