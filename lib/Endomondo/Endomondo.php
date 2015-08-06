<?php

namespace Endomondo;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use GuzzleHttp\Client;

Class Endomondo {

	private $country = "EN";
	private $deviceId = null;
	private $os = "Android";
	private $appVersion = "10.2.6";
	private $appVariant = "M-Pro";
	private $osVersion = "4.1";
	private $model = "GT-B5512";
	private $authToken = null;
	private $userAgent = null;
	private $language = 'EN';
	private $profile = null;
	private $workoutFactory = null;

	# Authentication url. Special case.
	const URL_AUTH = '/mobile/auth';

	# Page for latest workouts.
	const URL_WORKOUTS = '/mobile/api/workout/list';

	# Single workout
	const URL_WORKOUT = '/mobile/api/workout/get';

	const URL_PROFILE_GET = '/mobile/api/profile/account/get';

	const URL_PROFILE_POST = '/mobile/api/profile/account/post';

	const URL_WORKOUT_POST  = '/mobile/api/workout/post';

	const URL_WORKOUT_CREATE  = '/mobile/track';

	public function __construct() {
		$this->workoutFactory = new WorkoutFactory($this);

		$this->deviceId = (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, gethostname());
		$this->userAgent = sprintf("Dalvik/1.4.0 (Linux; U; %s %s; %s Build/GINGERBREAD)", $this->os, $this->osVersion, $this->model);
		$this->httpclient = new Client(['base_url' => 'https://api.mobile.endomondo.com']);
	}

    private function bigRandomNumber($randNumberLength){
        $randNumber = null;

        for ($i = 0; $i < $randNumberLength; $i++) {
            $randNumber .= rand(0, 9);
        }

        return $randNumber;
    }

	public function getSite($site, $fields = NULL) {
		$url = $site . '?' . http_build_query($fields);
		$response = $this->httpclient->post($url,
			array(
				"headers" => array(
					"User-Agent" => $this->userAgent
					)
				)
			);
		return (string) $response->getBody();
	}

	public function requestAuthToken($email, $password) {
		$params = array(
			'email' => $email,
			'password' => $password,
			'country' => $this->country,
			'deviceId' => $this->deviceId,
			'os' => $this->os,
			'appVersion' => $this->appVersion,
			'appVariant' => $this->appVariant,
			'osVersion' => $this->osVersion,
			'model' => $this->model,
			'v' => 2.4,
			'action' => 'PAIR'
		);

		$data = $this->getSite(self::URL_AUTH, $params);
		if (substr($data, 0, 2) == "OK") {
			$lines = explode("\n", $data);
			$authLine = explode('=', $lines[2]);
			$this->setAuthToken($authLine[1]);
			return $authLine[1];
		} else {
			throw new \Exception("Endomondo denied connection: " . $data);
		}
	}

	public function setAuthToken($token){
		$this->authToken = $token;
	}

	final protected function getAuthToken() {
		return $this->authToken;
	}


	public function getUserID(){
		return $this->profile->id;
	}

	public function setProfile($profile){
		$this->profile = $profile;
	}

	public function getProfile() {
		if($this->profile){
			return $this->profile;
		} else {
			$params = array(
					'authToken' => $this->getAuthToken()
			);
			$profile = json_decode($this->getSite(self::URL_PROFILE_GET, $params));
			$this->setProfile($profile->data);
			return $profile->data;
		}
	}

	public function logWeight($weight, \DateTime $date){
		return $this->postAccountInfo(
			array(
				"weight_kg" => $weight,
				"weight_time" => $date->format("Y-m-d H:i:s \U\T\C")
				)
			);
	}

	public function postAccountInfo($input) {
		$params = array(
			'authToken' => $this->getAuthToken(),
			'userId' => $this->getUserID(),
			'input' => json_encode($input),
			'gzip' => false
		);
		$response = $this->getSite(self::URL_PROFILE_POST, $params);
		return $response;
	}


	public function workoutList($userId = null, $maxResults = 40) {
		$params = array(
				'authToken' => $this->getAuthToken(),
				'language' => $this->language,
				'fields' => 'basic,pictures,tagged_users,points,playlist,interval',
				'maxResults' => $maxResults
		);
		if (isset($userId)){
			$params['userId'] = $userId;
		} elseif (isset($this->profile)) {
			$params['userId'] = $this->profile->id;
		} else {
			throw new \Exception("User Id is missing");
		}

		$workoutsSource = json_decode($this->getSite(self::URL_WORKOUTS, $params), 1);

		$workouts = array();

		foreach($workoutsSource['data'] as $workout){
			$workouts[] = $this->workoutFactory->create($workout);
		}

		return $workouts;
	}

	public function workout($workoutId) {
		$params = array(
				'authToken' => $this->getAuthToken(),
				'fields' => 'basic,points,pictures,tagged_users,points,playlist,interval',
				'workoutId' => $workoutId
		);
		$workout = $this->getSite(self::URL_WORKOUT, $params);
		return new $this->workoutFactory->create(json_decode($workout, 1));
	}

	public function createWorkout($sport, $duration, $distance = 0) {
		$params = array(
			'authToken' => $this->getAuthToken(),
			'userId' => $this->getUserID(),
			'workoutId' => '-' . $this->bigRandomNumber(16) . '',
			'duration' => $duration,
			'sport' => $sport,
			'distance' => $distance,
			'trackPoints' => false,
			'extendedResponse' => true,
    		'gzip' => 'false'
		);

		$response = $this->getSite(self::URL_WORKOUT_CREATE, $params);

		$split = explode("\n", $response);
		if($split[0] == 'OK'){
			return $this->workoutFactory->create(
				array(
					"id" => str_replace("workout.id=", "", $split[1]),
					'duration' => $duration,
					'sport' => $sport,
					'start_time' => gmdate("Y-m-d H:i:s \U\T\C", time())
					));
		} else {
			throw new \Exception("Creating of workout was unsuccesfull: " . $response);
		}
	}

	public function editWorkout($id, $edited) {
		if($id == 0){
			if($this->createWorkout($edited['sport'], $edited['duration'])){
				$workouts = $this->workoutList(null, 1);
				$workout = $workouts[0];
				$id = $workout->getId();
			} else {
				return false;
			}
		}
		$params = array(
				'authToken' => $this->getAuthToken(),
				'userId' => $this->getUserID(),
				'gzip' => 'false',
				'workoutId' => $id
		);
		$params['input'] = json_encode($edited);
		return $this->getSite(self::URL_WORKOUT_POST, $params);
	}
}
