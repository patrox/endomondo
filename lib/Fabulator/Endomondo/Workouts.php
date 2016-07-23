<?php

namespace Fabulator\Endomondo;

class Workouts
{
    /**
     * Core API.
     *
     * @var Endomondo
     */
    private $api;

    public function __construct(Endomondo $api)
    {
        $this->api = $api;
    }

    /**
     * Request single workout.
     *
     * @param  int $id      id of workout
     * @return Workout      Workout object
     */
    public function get($id)
    {
        return new Workout($this->api->get('workouts/' . $id));
    }

    /**
     * Edit workout
     * @param  int $id          workout id
     * @param  array $data      data to update
     * @return response         response from Endomondo
     */
    public function edit($id, $data)
    {
        return $this->api->put('workouts/' . $id, $data);
    }

    /**
     * Request list of last workouts.
     *
     * @param  int $limit       how many workouts request
     * @return array            array of Workouts objects
     */
    public function getList($limit = 15)
    {
        $workouts = $this->api->get('workouts/history', [
            'limit' => $limit,
            'expand' => 'workout'
        ]);

        $list = [];
        foreach ($workouts->data as $workout) {
            $list[] = new Workout($workout);
        }
        return $list;
    }
}
