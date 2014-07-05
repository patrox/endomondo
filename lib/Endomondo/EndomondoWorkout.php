<?php

namespace Endomondo;

Class EndomondoWorkout {

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

	public function __construct($source){
		$this->source = $source;
		$this->id = $source['id'];
		$this->points = isset($source['points']) ? $source['points'] : array();
	}

	public function getId(){
		return $this->id;
	}

	public function printSource(){
		print_r($this->source);
	}

	public function saveGPX($file){
		$gpx = $this->gpx ? $this->gpx : $this->generateGPX();
		$fp = fopen($file, 'w+');
		fwrite($fp, $gpx);
		fclose($fp);
	}

	public function generateGPX(){
		$xml = new \SimpleXMLElement('<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1" />');
		$trk = $xml->addChild('trk');
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
