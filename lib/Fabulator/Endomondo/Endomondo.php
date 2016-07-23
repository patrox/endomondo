<?php

namespace Fabulator\Endomondo;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;

class Endomondo
{
    const SPORT_RUNNING = 0;
    const SPORT_CYCLING_TRANSPORT = 1;
    const SPORT_CYCLING_SPORT = 2;
    const SPORT_MOUNTAIN_BIKINGS = 3;
    const SPORT_SKATING = 4;
    const SPORT_ROLLER_SKIING = 5;
    const SPORT_SKIING_CROSS_COUNTRY = 6;
    const SPORT_SKIING_DOWNHILL = 7;
    const SPORT_SNOWBOARDING = 8;
    const SPORT_KAYAKING = 9;
    const SPORT_KITE_SURFING = 10;
    const SPORT_ROWING = 11;
    const SPORT_SAILING = 12;
    const SPORT_WINDSURFING = 13;
    const SPORT_FINTESS_WALKING = 14;
    const SPORT_GOLFING = 15;
    const SPORT_HIKING = 16;
    const SPORT_ORIENTEERING = 17;
    const SPORT_WALKING = 18;
    const SPORT_RIDING = 19;
    const SPORT_SWIMMING = 20;
    const SPORT_SPINNING = 21;
    const SPORT_OTHER = 22;
    const SPORT_AEROBICS = 23;
    const SPORT_BADMINTON = 24;
    const SPORT_BASEBALL = 25;
    const SPORT_BASKETBALL = 26;
    const SPORT_BOXING = 27;
    const SPORT_CLIMBING_STAIRS = 28;
    const SPORT_CRICKET = 29;
    const SPORT_CROSS_TRAINING = 30;
    const SPORT_DANCING = 31;
    const SPORT_FENCING = 32;
    const SPORT_FOOTBALL_AMERICAN = 33;
    const SPORT_FOOTBALL_RUGBY = 34;
    const SPORT_FOOTBALL_SOCCER = 35;
    const SPORT_HANDBALL = 36;
    const SPORT_HOCKEY = 37;
    const SPORT_PILATES = 38;
    const SPORT_POLO = 39;
    const SPORT_SCUBA_DIVING = 40;
    const SPORT_SQUASH = 41;
    const SPORT_TABLE_TENIS = 42;
    const SPORT_TENNIS = 43;
    const SPORT_VOLEYBALL_BEACH = 44;
    const SPORT_VOLEYBALL_INDOOR = 45;
    const SPORT_WEIGHT_TRAINING = 46;
    const SPORT_YOGA = 47;
    const SPORT_MARTINAL_ARTS = 48;
    const SPORT_GYMNASTICS = 49;
    const SPORT_STEP_COUNTER = 50;
    const SPORT_CIRKUIT_TRAINING = 87;

    /**
     * Namespace for workouts REST API.
     *
     * @var Workouts
     */
    public $workouts;

    /**
     * Id of logged user.
     *
     * @var int
     */
    public $userId;

    /**
     * Guzzle Http Client.
     *
     * @var Client
     */
    private $httpclient;

    /**
     * CSFR token.
     *
     * @var string
     */
    private $csrf = '-first-';

    public function __construct()
    {
        $this->httpclient = new Client([
            'base_url' => 'https://www.endomondo.com/rest/'
            ]);
        $this->workouts = new Workouts($this);
    }

    /**
     * Call get request on Endomondo API.
     *
     * @param  string $endpoint name of endpoint
     * @param  array    $data       data to sent
     * @return object           response
     */
    public function get($endpoint, $data = [])
    {
        return $this->request('GET', 'v1/users/' . $this->userId . '/' . $endpoint . '?' . http_build_query($data));
    }

    /**
     * Call put request on Endomondo API.
     *
     * @param  string   $endpoint   name of endpoint
     * @param  array    $data       data to put
     * @return object               response
     */
    public function put($endpoint, $data)
    {
        // regenerate CSFR token, need when you updating data
        $this->generateCSRFToken();

        return $this->request('PUT', 'v1/users/' . $this->userId . '/' . $endpoint, $data);
    }

    /**
     * Regular request on Endomondo API.
     *
     * @param  string $method   http method
     * @param  string $endpoint name of endpoint
     * @param  array  $data     data to send
     * @return object           response
     */
    public function request($method, $endpoint, $data = [])
    {
        $method = strtolower($method);

        // set auth data and post data
        $options = [
            'body' => $method === 'post' || $method === 'put' ? json_encode($data) : null,
            'cookies' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Cookie' => 'CSRF_TOKEN=' . $this->csrf . '',
                'X-CSRF-TOKEN' => $this->csrf
            ]
        ];

        $response = $this->httpclient->$method($endpoint, $options);

        // decode answer
        return json_decode((string) $response->getBody());
    }

    /**
     * Regenerate CSFR Token
     */
    public function generateCSRFToken()
    {
        $response = $this->httpclient->get(
            'https://www.endomondo.com/users/' . $this->userId,
            ['cookies' => true]
        );

        foreach ($response->getHeaders()['Set-Cookie'] as $item) {
            $cookie = SetCookie::fromString($item);
            if ($cookie->getName() === 'CSRF_TOKEN') {
                $this->csrf = $cookie->getValue();
            }
        }
    }

    /**
     * Login user to Endomondo.
     *
     * @param  string $email    login/email
     * @param  string $password password
     * @return object           user info
     */
    public function login($email, $password)
    {
        $response = $this->request('POST', 'session', [
            'email' => $email,
            'password' => $password,
            'remember' => true
        ]);
        $this->userId = $response->id;
        return $response;
    }

    /**
     * Get info about current session.
     *
     * @return object response from Endomondo
     */
    public function getUserInfo()
    {
        return $this->request('GET', 'session');
    }
}
