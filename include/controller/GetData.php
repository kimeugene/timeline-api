<?php
namespace Timeline;

class GetData extends Common
{
    // Minimum distance required to be considered a new point.
    const MINIMUM_DISTANCE = .1;
    
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
        $this->log->addDebug(sprintf('Point of origin: %s', print_r($this->pointOfOrigin, true)));
        
        $currentPoint = array(
            'latitude'  => $latitude,
            'longitude' => $longitude
        );
        $this->log->addDebug(sprintf('Current point: %s', print_r($currentPoint, true)));
        
        $distance = self::vincentyGreatCircleDistance($this->pointOfOrigin, $currentPoint);
        $this->log->addDebug('Distance calculated: ' . $distance);
        
        if ($distance <= self::MINIMUM_DISTANCE) {
            $this->log->addDebug(sprintf('Point discarded; distance was too low: %d.', $distance));
            return false;
        }
        $this->pointOfOrigin = $currentPoint;
        return true;
    }
    
    /**
     * Logic "borrowed" from http://www.geodatasource.com/developers/php
     * 
     * @param array $firstPoint Latitude and Longitude of point 1 (in decimal degrees)
     * @param array $secondPoint Latitude and Longitude of point 2 (in decimal degrees)
     * @param string $returnAsUnitOfDistance [optional] Instead of raw distance, a unit of distance will be returned: kilometers, miles, nautical miles.
     * @return int distance, in requested or raw format.
     */
//    public static function distance(array $firstPoint, array $secondPoint, $returnAsUnitOfDistance=null) 
//    {
//        $theta = $firstPoint['longitude'] - $secondPoint['longitude'];
//        $this->log->addDebug('Theta: ' . $theta);
//        $radiants = sin(deg2rad($firstPoint['latitude'])) * sin(deg2rad($secondPoint['latitude']))
//            + cos(deg2rad($firstPoint['latitude'])) * cos(deg2rad($secondPoint['latitude'])) * cos(deg2rad($theta));
//        $distance = rad2deg(acos($radiants));
//        $this->log->addDebug('Distance: ' . $distance);
//
//        if ($returnAsUnitOfDistance !== null) {
//            $miles = $distance * 60 * 1.1515;
//            switch ($returnAsUnitOfDistance) {
//            case self::KILOMETERS:
//                return ($miles * 1.609344);
//                break;
//            case self::NAUTICAL_MILES:
//                return ($miles * 0.8684);
//                break;
//            case self::MILES:
//            default:
//                return $miles;
//            }
//        }
//        return $distance;
//    }
    
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
    
}

