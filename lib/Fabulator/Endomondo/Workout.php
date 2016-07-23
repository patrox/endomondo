<?php

namespace Fabulator\Endomondo;

class Workout
{

    private $endomondo;
    private $sport;
    private $privacy_workout;
    private $id;
    private $distance;
    private $duration;
    private $burgers_burned;
    private $name;
    private $owner_id;
    private $calories;
    private $start_time;
    private $speed_avg;
    private $points;
    private $altitude_min;
    private $descent;
    private $ascent;
    private $altitude_max;
    private $hydration;
    private $speed_max;
    private $heart_rate_max;
    private $heart_rate_avg;
    private $live;
    private $souce;
    private $playlist;
    private $gpx = false;
    private $timeFormat = 'Y-m-d\TH:i:s\Z';
    private $sportNames = [
        Endomondo::SPORT_RUNNING  => 'Running',
        Endomondo::SPORT_CYCLING_TRANSPORT  => 'Cycling, transport',
        Endomondo::SPORT_CYCLING_SPORT  => 'Cycling, sport',
        Endomondo::SPORT_MOUNTAIN_BIKINGS  => 'Mountain biking',
        Endomondo::SPORT_SKATING  => 'Skating',
        Endomondo::SPORT_ROLLER_SKIING  => 'Roller skiing',
        Endomondo::SPORT_SKIING_CROSS_COUNTRY  => 'Skiing, cross country',
        Endomondo::SPORT_SKIING_DOWNHILL  => 'Skiing, downhill',
        Endomondo::SPORT_SNOWBOARDING  => 'Snowboarding',
        Endomondo::SPORT_KAYAKING  => 'Kayaking',
        Endomondo::SPORT_KITE_SURFING => 'Kite surfing',
        Endomondo::SPORT_ROWING => 'Rowing',
        Endomondo::SPORT_SAILING => 'Sailing',
        Endomondo::SPORT_WINDSURFING => 'Windsurfing',
        Endomondo::SPORT_FINTESS_WALKING => 'Fitness walking',
        Endomondo::SPORT_GOLFING => 'Golfing',
        Endomondo::SPORT_HIKING => 'Hiking',
        Endomondo::SPORT_ORIENTEERING => 'Orienteering',
        Endomondo::SPORT_WALKING => 'Walking',
        Endomondo::SPORT_RIDING => 'Riding',
        Endomondo::SPORT_SWIMMING => 'Swimming',
        Endomondo::SPORT_SPINNING => 'Spinning',
        Endomondo::SPORT_OTHER => 'Other',
        Endomondo::SPORT_AEROBICS => 'Aerobics',
        Endomondo::SPORT_BADMINTON => 'Badminton',
        Endomondo::SPORT_BASEBALL => 'Baseball',
        Endomondo::SPORT_BASKETBALL => 'Basketball',
        Endomondo::SPORT_BOXING => 'Boxing',
        Endomondo::SPORT_CLIMBING_STAIRS => 'Climbing stairs',
        Endomondo::SPORT_CRICKET => 'Cricket',
        Endomondo::SPORT_CROSS_TRAINING => 'Cross training',
        Endomondo::SPORT_DANCING => 'Dancing',
        Endomondo::SPORT_FENCING => 'Fencing',
        Endomondo::SPORT_FOOTBALL_AMERICAN => 'Football, American',
        Endomondo::SPORT_FOOTBALL_RUGBY => 'Football, rugby',
        Endomondo::SPORT_FOOTBALL_SOCCER => 'Football, soccer',
        Endomondo::SPORT_HANDBALL => 'Handball',
        Endomondo::SPORT_HOCKEY => 'Hockey',
        Endomondo::SPORT_PILATES => 'Pilates',
        Endomondo::SPORT_POLO => 'Polo',
        Endomondo::SPORT_SCUBA_DIVING => 'Scuba diving',
        Endomondo::SPORT_SQUASH => 'Squash',
        Endomondo::SPORT_TABLE_TENIS => 'Table tennis',
        Endomondo::SPORT_TENNIS => 'Tennis',
        Endomondo::SPORT_VOLEYBALL_BEACH => 'Volleyball, beach',
        Endomondo::SPORT_VOLEYBALL_INDOOR => 'Volleyball, indoor',
        Endomondo::SPORT_WEIGHT_TRAINING => 'Weight training',
        Endomondo::SPORT_YOGA => 'Yoga',
        Endomondo::SPORT_MARTINAL_ARTS => 'Martial arts',
        Endomondo::SPORT_GYMNASTICS => 'Gymnastics',
        Endomondo::SPORT_STEP_COUNTER => 'Step counter',
        Endomondo::SPORT_CIRKUIT_TRAINING => 'Circuit Training'
    ];

    public function __construct($source)
    {
        $this->source = $source;
        $this->id = $source->id;
        $date = new \DateTime();
        $this->start_time = $date->setTimestamp(strtotime($source->start_time));
        $this->sport = $source->sport;
        $this->calories = isset($source->calories) ? $source->calories : false;
        $this->distance = isset($source->distance) ? $source->distance : 0;
        $this->duration = isset($source->duration) ? $source->duration : 0;
        $this->points = isset($source->points) ? $source->points : array();
    }

    /**
     * Parse data for request to Endomondo
     * @return array
     */
    private function toArray()
    {
        $datas = array(
            'sport' => $this->sport,
            'duration' => $this->duration,
            'distance' => $this->distance,
            'start_time' => $this->start_time->format("Y-m-d H:i:s \U\T\C")
            );

        if ($this->calories) {
            $datas['calories'] = $this->calories;
        }

        return $datas;
    }

    /**
     * Set sport
     * @param int $sport
     */
    public function setSport($sport)
    {
        $this->sport = $sport;
    }

    /**
     * Get sport id
     * @return int
     */
    public function getSportId()
    {
        return $this->sport;
    }

    /**
     * Get sport name
     * @return string
     */
    private function getSportName()
    {
        return $this->sportNames[$this->sport];
    }

    /**
     * Get escaped sport name
     * @return string
     */
    private function getGPXSportName()
    {
        return str_replace(", ", "_", strtoupper($this->getSportName()));
    }

    /**
     * Get id of workout
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get sport name
     * @return string
     */
    public function getName()
    {
        return $this->getSportName();
    }

    /**
     * Get start time
     * @return DateTime
     */
    public function getStart()
    {
        return $this->start_time;
    }

    /**
     * Get duration in seconds
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Get calories
     * @return int
     */
    public function getCalories()
    {
        return $this->calories;
    }

    /**
     * Get distance in kilometres
     * @return float
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Debug for source
     */
    public function printSource()
    {
        print_r($this->source);
    }

    public function getHeartRateAvg()
    {
        return isset($this->source->heart_rate_avg) ? $this->source->heart_rate_avg : null;
    }

    /**
     * Get gps points
     * @return array
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * Save GPX of workout
     * @param  string
     */
    public function saveGPX($file)
    {
        $fp = fopen($file, 'w+');
        fwrite($fp, $this->getGPX());
        fclose($fp);
    }

    /**
     * Get GPX document
     * @return string
     */
    public function getGPX()
    {
        return $this->gpx ? $this->gpx : $this->generateGPX();
    }

    /**
     * Generate GPX of workout
     * @return string
     */
    private function generateGPX()
    {
        $xml = new \SimpleXMLElement(
            '<gpx xmlns="http://www.topografix.com/GPX/1/1"'
            . 'xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1"'
            . '/>'
        );
        $trk = $xml->addChild('trk');
        $trk->addChild('type', $this->getGPXSportName());
        $trkseg = $trk->addChild('trkseg');

        foreach ($this->points as $point) {
            $trkpt = $trkseg->addChild('trkpt');
            $trkpt->addChild('time', gmdate($this->timeFormat, strtotime($point->time)));
            $trkpt->addAttribute("lat", $point->lat);
            $trkpt->addAttribute("lon", $point->lng);
            if (isset($point->alt)) {
                $trkpt->addChild("ele", $point->alt);
            }
            if (isset($point->hr)) {
                $ext = $trkpt->addChild("extensions");
                $trackPoint = $ext->addChild("gpxtpx:TrackPointExtension", '', 'gpxtpx');
                $trackPoint->addChild("gpxtpx:hr", $point->hr, 'gpxtpx');
            }
        }

        $this->gpx = $xml->asXML();
        return $this->gpx;
    }
}
