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
        
        if ($distance <= $this->getZoomRatio()) {
            $this->log->addDebug(sprintf('Point discarded; distance was too low: %d.', $distance));
            return false;
        }
        $this->pointOfOrigin = $currentPoint;
        return true;
    }
    
    /**
     * Measures the shortest distance between any two points on the surface of Earth "As the crow flies"
     * @see http://en.wikipedia.org/wiki/Great-circle_distance
     * 
     * @param array $firstPoint
     * @param array $secondPoint
     * @param type $earthRadius
     * @return type
     */
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
     * Distance needed to be considered a new point.
     * @return int
     */
    private function getZoomRatio($zoom=null)
    {
        $ratio = array( // Google distance commented below
            0  => 200000, // 1183315101
            1  => 160000, // 591657550.5
            2  => 120000, // 295828775.3
            3  => 90000,  // 147914387.6
            4  => 60000,  // 73957193.82
            5  => 40000,  // 36978596.91
            6  => 25000,  // 18489298.45
            7  => 15000,  // 9244649.227
            8  => 10000,  // 4622324.614
            9  => 5000,   // 2311162.307
            10 => 1800,   // 1155581.153
            11 => 1000,   // 577790.5767
            12 => 600,    // 288895.2884
            13 => 450,    // 144447.6442
            14 => 400,    // 72223.82209
            15 => 350,    // 36111.91104
            16 => 300,    // 18055.95552
            17 => 250,    // 9027.977761
            18 => 200,    // 4513.98888
            19 => 150,    // 2256.99444
            20 => 100,    // 1128.49722
            21 => 50      // 564.24861
        );
        
        $zoom = (!is_null($zoom)) ? $zoom : $this->zoomLevel;
        $distance = $ratio[$zoom];
        $this->log->addDebug(sprintf('Zoom: %d, Distance calculated: %d.', $zoom, $distance));
        
        return $distance;
    }
    
}

