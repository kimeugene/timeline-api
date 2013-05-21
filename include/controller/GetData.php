<?php

namespace Timeline;

class GetData extends Common
{
    public function __construct($log)
    {
        parent::__construct($log);
    }

    public function process($params)
    {
        $data = array(
            'TableName'     => 'history_new',
            'HashKeyValue'  => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['user_id']),
	    'Limit'         => empty($params['limit']) ? 25 : (int)$params['limit'],
	    'ScanIndexForward' => false,
            "RangeKeyCondition" => array(
                "ComparisonOperator" => "BETWEEN",
                "AttributeValueList" => array(
                    array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => strtotime($params['date'] . " 00:00:00")),
                    array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => strtotime($params['date'] . " 23:59:59")),
                )
            )
        );

        $this->log->addInfo('Data array:' . print_r($data, true));

        try
        {
	    $start = microtime(true);
            $response = $this->instance->query($data);
            $this->log->addDebug("OK. Consumed units: " . $response['ConsumedCapacityUnits']);
            $this->log->addDebug("Took: " . (microtime(true) - $start));

            $formattedResponse = array();
            for($i=0; $i<count($response['Items']); $i++) {
	      $formattedResponse[] = array($response['Items'][$i]['lat']['S'], $response['Items'][$i]['long']['S'], date('D M d H:i:s', $response['Items'][$i]['timestamp']['S']));
	      $this->log->addInfo("Timestammp: " . date('Y-m-d H:i:s', $response['Items'][$i]['timestamp']['S']));
	    }

	    $this->log->addInfo("Number of datapoints:");
	    $this->log->addInfo(count($formattedResponse));
            //print json_encode($response['Items']);
	    print json_encode($formattedResponse);
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
