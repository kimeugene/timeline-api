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
        $dataPoints = $this->breakDown($params['data_points']);
        $this->log->addDebug(print_r($dataPoints, true));


        if (is_array($dataPoints) && $dataPoints)
        {
            foreach ($dataPoints as $point)
            {
                $data = array(
                    'TableName'     => 'history_new',
                    'Item'          => array(
                        'email'     => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['email']),
                        'timestamp' => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $point['ts']),
                        'long'      => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $point['long']),
                        'lat'       => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $point['lat']),
                        'code'      => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $point['code'])
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


    }

    protected function breakDown($data)
    {
        // Assumes that the data is in the 'timestamp1|long1|lat1,timestamp2|long2|lat2,...' format.
        $points = explode(',', $data);
        $dataPoints = array();
        foreach ($points as $pin) {
            list($ts,$long,$lat,$code) = explode('|', $pin);
            $dataPoints[] = array('ts'=>$ts, 'long'=>$long, 'lat'=>$lat, 'code'=>$code);
        }
        return $dataPoints;
    }

}

