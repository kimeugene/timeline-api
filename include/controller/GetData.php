<?php
namespace Timeline;

class GetData extends Common
{
    // Units of distance
    const MILES = 'M';
    const KILOMETERS = 'K';
    const NAUTICAL_MILES = 'N';
    
    /**
     * Point of origin
     * @var array
     */
    private $pointOfOrigin = array();
    
    public function __construct($log)
    {
        parent::__construct($log);
    }

    public function process($params)
    {
        $this->zoomLevel = $params['zoom'];
        $data = array(
            'TableName'         => 'history_new',
            'HashKeyValue'      => array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => $params['user_id']),
            'Limit'             => empty($params['limit']) ? 25 : (int)$params['limit'],
            'ScanIndexForward'  => false,
            'RangeKeyCondition' => array(
                'ComparisonOperator' => 'BETWEEN',
                'AttributeValueList' => array(
                    array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => strtotime($params['date'] . ' 00:00:00')),
                    array(\Aws\DynamoDb\Enum\ScalarAttributeType::S => strtotime($params['date'] . ' 23:59:59')),
                )
            )
        );

        $this->log->addInfo('Params:' . print_r($params, true));
        $this->log->addInfo('Data array:' . print_r($data, true));

        try
        {
            $start = microtime(true);
            $response = $this->instance->query($data);
            $this->log->addDebug('OK. Consumed units: ' . $response['ConsumedCapacityUnits']);
            $this->log->addDebug('Took: ' . (microtime(true) - $start));

            $formattedResponse = array();
            for($i=0; $i<count($response['Items']); $i++) {
                $continue = $this->filterPoints($response['Items'][$i]['lat']['S'], $response['Items'][$i]['long']['S']);
                if ($continue) {
                    $formattedResponse[] = array($response['Items'][$i]['lat']['S'], $response['Items'][$i]['long']['S'], date('D M d H:i:s', $response['Items'][$i]['timestamp']['S']));
                    $this->log->addInfo('Timestamp: ' . date('Y-m-d H:i:s', $response['Items'][$i]['timestamp']['S']));
                }
            }

            $this->log->addInfo(sprintf("Number of datapoints: %d", count($formattedResponse)));
            //print json_encode($response['Items']);
            print json_encode($formattedResponse);
        }
        catch (\Exception $e)
        {
            $this->log->addError($e->getMessage());
        }

        if (isset($response['ConsumedCapacityUnits']) && $response['ConsumedCapacityUnits'] > 0)
        {
            $this->log->addDebug('OK. Consumed units: ' . $response['ConsumedCapacityUnits']);
        }
        else
        {
            $this->log->addError('Could not find Consumed Capacity Units or the value was 0.');
        }

    }
    
    /**
     * Determines whether a point has enough distance from the point of origin to be considered original.
     * @param type $latitude
     * @param type $longitude
     * @return boolean true if the point is original, false if the distance is too short from the point of origin.
     */
    private function filterPoints($latitude, $longitude)
    {
        if (empty($this->pointOfOrigin)) {
            $this->log->addDebug(sprintf('Setting point of origin at latitude: %s and longitude: %s.', $latitude, $longitude));
            $this->pointOfOrigin = array(
                'latitude'  => $latitude,
                'longitude' => $longitude
            );
            return true;
        }
        
        $currentPoint = array(
            'latitude'  => $latitude,
            'longitude' => $longitude
        );
        
        $distance = self::vincentyGreatCircleDistance($this->pointOfOrigin, $currentPoint);
        $this->log->addDebug('Distance calculated: ' . $distance);
        
        if ($distance <= $this->calculateDistance($this->zoomLevel)) {
            $this->log->addDebug(sprintf('Point discarded; distance was too low: %d.', $distance));
            return false;
        }
        $this->pointOfOrigin = $currentPoint;
        return true;
    }
    
    public static function vincentyGreatCircleDistance(array $firstPoint, array $secondPoint, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($firstPoint['latitude']);
        $lonFrom = deg2rad($firstPoint['longitude']);
        $latTo = deg2rad($secondPoint['latitude']);
        $lonTo = deg2rad($secondPoint['longitude']);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }
    
    /**
     * Distance needed to be considered a new point
     * @todo We need to find a better algorithm for this. The number right now is arbitrary and, frankly, not immensely convincing.
     * @param type $zoom
     * @return type
     */
    private function calculateDistance($zoom)
    {
        $scale = array(
            0 => 591657550.500000,
            1 => 591657550.500000,
            2 => 295828775.300000,
            3 => 147914387.600000,
            4 => 73957193.820000,
            5 => 36978596.910000,
            6 => 18489298.450000,
            7 => 9244649.227000,
            8 => 4622324.614000,
            9 => 2311162.307000,
            10 => 1155581.153000,
            11 => 577790.576700,
            12 => 288895.288400,
            13 => 144447.644200,
            14 => 72223.822090,
            15 => 36111.911040,
            16 => 18055.955520,
            17 => 9027.977761,
            18 => 4513.988880,
            19 => 2256.994440,
            20 => 1128.497220,
            21 => 564.24861
        );
        
        $distance = $scale[$zoom] / pow(1.58, $zoom);
        $distance = ($distance < 300) ? 300 : $distance;
        $this->log->addDebug(sprintf('Zoom: %d, Distance calculated: %d.', $zoom, $distance));
        
        return $distance;
    }
    
}

