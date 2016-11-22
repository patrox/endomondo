<?php

namespace Fabulator\Endomondo;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use GuzzleHttp\Client;

class EndomondoOld
{

    // Authentication url. Special case.
    const URL_AUTH = '/mobile/auth';

    const URL_WORKOUTS = '/mobile/api/workout/list';
    const URL_WORKOUT = '/mobile/api/workout/get';
    const URL_WORKOUT_POST  = '/mobile/api/workout/post';
    const URL_WORKOUT_CREATE  = '/mobile/track';

    const URL_PROFILE_GET = '/mobile/api/profile/account/get';
    const URL_PROFILE_POST = '/mobile/api/profile/account/post';

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

    public function __construct()
    {
        $this->deviceId = (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, gethostname());
        $this->userAgent = sprintf(
            "Dalvik/1.4.0 (Linux; U; %s %s; %s Build/GINGERBREAD)",
            $this->os,
            $this->osVersion,
            $this->model
        );
        $this->httpclient = new Client(
            ['base_url' => 'https://api.mobile.endomondo.com']
        );
    }

    /**
     * Generate really long number
     * @param  int $randNumberLength
     * @return string
     */
    private function bigRandomNumber($randNumberLength)
    {
        $randNumber = null;

        for ($i = 0; $i < $randNumberLength; $i++) {
            $randNumber .= rand(0, 9);
        }

        return $randNumber;
    }

    /**
     * @param  string $endpoint
     * @param  array $fields
     * @return string
     */
    public function requestApi($endpoint, $fields = null)
    {
        $url = $endpoint . '?' . http_build_query($fields);
        $response = $this->httpclient->post(
            $url,
            [
                "headers" => [
                    "User-Agent" => $this->userAgent
                    ]
            ]
        );
        return (string) $response->getBody();
    }

    /**
     * Request auth token from Endomondo.
     * @param  string $email
     * @param  string $password
     * @return string
     */
    public function requestAuthToken($email, $password)
    {
        $params = [
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
        ];

        $data = $this->requestApi(self::URL_AUTH, $params);

        if (substr($data, 0, 2) == "OK") {
            $lines = explode("\n", $data);
            $authLine = explode('=', $lines[2]);
            $this->setAuthToken($authLine[1]);
            return $authLine[1];
        } else {
            throw new \Exception("Endomondo denied connection: " . $data);
        }
    }

    /**
     * Set auth token for further use
     * @param string $token
     */
    public function setAuthToken($token)
    {
        $this->authToken = $token;
    }

    /**
     * Get saved auth token
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Get user ID of authenticated profile
     * @return string
     */
    public function getUserID()
    {
        if ($this->profile) {
            return $this->profile->id;
        } else {
            $profile = $this->getProfile();
            return $profile->id;
        }
    }

    /**
     * Set user profile
     * @param stdClass Object
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get user profile, if it not exists, request it from Endomondo
     * @return stdClass Object
     */
    public function getProfile()
    {
        if ($this->profile) {
            return $this->profile;
        } else {
            $params = [
                'authToken' => $this->getAuthToken()
            ];
            $profile = json_decode($this->requestApi(self::URL_PROFILE_GET, $params));
            $this->setProfile($profile->data);
            return $profile->data;
        }
    }

    /**
     * Log weight
     * @param  float $weight Weight is in kilograms
     * @param  \DateTime $date
     * @return stdClass Object
     */
    public function logWeight($weight, \DateTime $date)
    {
        $response = $this->postAccountInfo(
            [
                "weight_kg" => $weight,
                "weight_time" => $date->format("Y-m-d H:i:s \U\T\C")
            ]
        );
        $reponseObject = json_decode($response);
        if ($reponseObject->data == 'OK') {
            return $reponseObject;
        } else {
            throw new Exception("Weight update failed: " . $response);
        }
    }

    /**
     * Post account info (weight eg.)
     * @param  array $input
     * @return string
     */
    public function postAccountInfo($input)
    {
        $params = array(
            'authToken' => $this->getAuthToken(),
            'userId' => $this->getUserID(),
            'input' => json_encode($input),
            'gzip' => false
        );
        $response = $this->requestApi(self::URL_PROFILE_POST, $params);
        return $response;
    }

    public function getMyWorkoutList($maxResults = 40)
    {
        return $this->getWorkoutList($this->getUserID(), $maxResults);
    }

    /**
     * Get workout list
     * @param  int || null $userId
     * @param  int $maxResult
     * @return array
     */
    public function getWorkoutList($userId, $maxResults = 40)
    {
        $params = array(
            'authToken' => $this->getAuthToken(),
            'language' => $this->language,
            'fields' => 'basic,pictures,tagged_users,points,playlist,interval',
            'maxResults' => $maxResults
        );

        $params['userId'] = $userId;

        $workoutsSource = json_decode($this->requestApi(self::URL_WORKOUTS, $params));

        $workouts = [];

        foreach ($workoutsSource->data as $workout) {
            $workouts[] = new Workout($workout);
        }

        return $workouts;
    }

    /**
     * Get single workout
     * @param  string $workoutId
     * @return object Workout
     */
    public function getWorkout($workoutId)
    {
        $params = [
                'authToken' => $this->getAuthToken(),
                'fields' => 'basic,points,pictures,tagged_users,points,playlist,interval',
                'workoutId' => $workoutId
        ];
        $workout = json_decode($this->requestApi(self::URL_WORKOUT, $params));
        return Workout($workout);
    }

    /**
     * Create Endomondo workout
     * @param  int $sport
     * @param  \DateTime $start
     * @param  int $duration in seconds
     * @param  float $distance in kilometres
     * @return object Workout
     */
    public function createWorkout($sport, \DateTime $start, $duration, $distance = 0.0, $calories = false)
    {
        $params = [
            'authToken' => $this->getAuthToken(),
            'userId' => $this->getUserID(),
            'workoutId' => '-' . $this->bigRandomNumber(16) . '',
            'duration' => $duration,
            'sport' => $sport,
            'distance' => $distance,
            'trackPoints' => false,
            'extendedResponse' => true,
            'gzip' => 'false'
        ];

        $response = $this->requestApi(self::URL_WORKOUT_CREATE, $params);

        $split = explode("\n", $response);

        // endomondo does not allow to set start date in new workout
        // it does not return workout id, so it must be get the last one and edit it
        if ($split[0] == 'OK') {
            $workouts = $this->getWorkoutList(null, 1);
            $id = $workouts[0]->getId();
            try {
                $set = [
                    'start_time' => gmdate("Y-m-d H:i:s \U\T\C", $start->format("U")),
                    'sport' => $sport,
                    'duration' => $duration,
                    'distance' => $distance
                    ];

                if ($calories) {
                    $set['calories'] = $calories;
                };

                $this->editWorkout($id, $set);
            } catch (Exception $e) {
                throw new \Exception("Creating of workout was unsuccesfull: " . $e->getMessage());
            }

            return $id;
        } else {
            throw new \Exception("Creating of workout was unsuccesfull: " . $response);
        }
    }

    /**
     * Edit Endomondo workout
     * @param  string $id
     * @param  array $params
     * @return stdClass Object
     */
    public function editWorkout($id, $properties)
    {
        $params = array(
                'authToken' => $this->getAuthToken(),
                'userId' => $this->getUserID(),
                'gzip' => 'false',
                'workoutId' => $id
        );
        $params['input'] = json_encode($properties);
        $response = $this->requestApi(self::URL_WORKOUT_POST, $params);
        $responseObject = json_decode($response);
        if ($responseObject->data === 'OK') {
            return $responseObject;
        } else {
            throw new Exception("Edit of workout failed: ", $response);
        }
    }
}