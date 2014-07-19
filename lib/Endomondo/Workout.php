<?php

namespace Endomondo;

Class Workout {

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
	private $sportNames = array(
		0  => 'Running',
    1  => 'Cycling, transport',
    2  => 'Cycling, sport',
    3  => 'Mountain biking',
    4  => 'Skating',
    5  => 'Roller skiing',
    6  => 'Skiing, cross country',
    7  => 'Skiing, downhill',
    8  => 'Snowboarding',
    9  => 'Kayaking',
    10 => 'Kite surfing',
    11 => 'Rowing',
    12 => 'Sailing',
    13 => 'Windsurfing',
    14 => 'Fitness walking',
    15 => 'Golfing',
    16 => 'Hiking',
    17 => 'Orienteering',
    18 => 'Walking',
    19 => 'Riding',
    20 => 'Swimming',
    21 => 'Spinning',
    22 => 'Other',
    23 => 'Aerobics',
    24 => 'Badminton',
    25 => 'Baseball',
    26 => 'Basketball',
    27 => 'Boxing',
    28 => 'Climbing stairs',
    29 => 'Cricket',
    30 => 'Cross training',
    31 => 'Dancing',
    32 => 'Fencing',
    33 => 'Football, American',
    34 => 'Football, rugby',
    35 => 'Football, soccer',
    36 => 'Handball',
    37 => 'Hockey',
    38 => 'Pilates',
    39 => 'Polo',
    40 => 'Scuba diving',
    41 => 'Squash',
    42 => 'Table tennis',
    43 => 'Tennis',
    44 => 'Volleyball, beach',
    45 => 'Volleyball, indoor',
    46 => 'Weight training',
    47 => 'Yoga',
    48 => 'Martial arts',
    49 => 'Gymnastics',
    50 => 'Step counter');

	public function __construct($endomondo, $source){
		$this->source = $source;
		$this->endomondo = $endomondo;
		$this->id = $source['id'];
		$date = new \DateTime();
		$this->start_time = $date->setTimestamp(strtotime($source['start_time']));
		$this->sport = $source['sport'];
		$this->calories = isset($source['calories']) ? $source['calories'] : false;
		$this->distance = isset($source['distance']) ? $source['distance'] : 0;
		$this->duration = isset($source['duration']) ? $source['duration'] : 0;
		$this->points = isset($source['points']) ? $source['points'] : array();
	}

	private function buildEndomondoData(){

		$datas = array(
			'sport' => $this->sport,
			'duration' => $this->duration,
			'distance' => $this->distance,
			'start_time' => $this->start_time->format("Y-m-d H:i:s \U\T\C")
			);
		if($this->calories){
			$datas['calories'] = $this->calories;
		}

		return $datas;

	}

	public function push(){
		$this->endomondo->edit_workout($this->id, $this->buildEndomondoData());
	}

	public function setSport($sport){
		$this->sport = $sport;
	}

	private function getSportName(){
		return $this->sportNames[$this->sport];
	}

	private function getGPXSportName(){
		return str_replace(", ", "_", strtoupper($this->getSportName()));
	}

	public function getId(){
		return $this->id;
	}

	public function getName(){
		return $this->getSportName();
	}

	public function getStart(){
		return $this->start_time;
	}

	public function getDuration(){
		return $this->duration;
	}

	public function getCalories(){
		return $this->calories;
	}

	public function getDistance(){
		return $this->distance;
	}

	/*public function addActivityToFitbit(){
		$this->fitbit->logActivity(
				$this->getStart(),
				'',
				$this->getDuration() * 1000,
				round($this->getCalories()),
				round($this->getDistance(), 2),
				'Kilometer',
				$workout->getName());
	}*/

	public function printSource(){
		print_r($this->source);
	}

	public function saveGPX($file){
		$fp = fopen($file, 'w+');
		fwrite($fp, $this->getGPX());
		fclose($fp);
	}

	public function getGPX(){
		return $this->gpx ? $this->gpx : $this->generateGPX();
	}

	private function generateGPX(){
		$xml = new \SimpleXMLElement('<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1" />');
		$trk = $xml->addChild('trk');
		$trk->addChild('type', $this->getGPXSportName());
		$trkseg = $trk->addChild('trkseg');

		foreach($this->points as $point){
			$trkpt = $trkseg->addChild('trkpt');
			$trkpt->addChild('time', gmdate($this->timeFormat, strtotime($point['time'])));
			$trkpt->addAttribute("lat", $point['lat']);
			$trkpt->addAttribute("lon", $point['lng']);
			if(isset($point['alt'])){
				$trkpt->addChild("ele", $point['alt']);
			}
			if(isset($point['hr'])){
				$ext = $trkpt->addChild("extensions");
				$trackPoint = $ext->addChild("gpxtpx:TrackPointExtension", '', 'gpxtpx');
				$trackPoint->addChild("gpxtpx:hr", $point['hr'], 'gpxtpx');
			}
		}

		$this->gpx = $xml->asXML();
		return $this->gpx;
	}


}
