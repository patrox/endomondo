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
     * @return object           response from Endomondo
     */
    public function edit($id, $data)
    {
        return $this->api->put('workouts/' . $id, $data);
    }

    /**
     * Delete workout.
     *
     * @param  int $id      workout id
     * @return object       response from Endomondo
     */
    public function delete($id)
    {
        return $this->api->delete('workouts/' . $id);
    }

    /**
     * Create new Endomondo workout
     * 
     * @param  int       id of sport
     * @param  \DateTime start of workout
     * @param  int       duration in decs
     * @param  float     distance in km
     * @param  int       number of calories   
     * @return int       id of endomondo workout
     */
    public function create($sport, \DateTime $start, $duration, $distance = 0.0, $calories = false)
    {
        return $this->api
            ->getOldAPI()
            ->createWorkout($sport, $start, $duration, $distance, $calories);
    }

    /**
     * Request list of last workouts.
     *
     * @param  int $limit       how many workouts request
     * @return array            array of Workouts objects
     */
    public function getLast($limit = 15)
    {
        return $this->filter([
            'limit' => 15,
        ]);
    }

    /**
     * Get list of workouts by start and end end.
     *
     * @param  \Datetime $start start of interval
     * @param  \Datetime $end   end of interval
     * @return array            array of Workouts objects
     */
    public function getByDates(\Datetime $start, \Datetime $end, $limit = 15)
    {
        return $this->filter([
            'before' => $end->format('c'),
            'after' => $start->format('c'),
            'limit' => $limit
        ]);
    }

    /**
     * Filter workout list.
     *
     * @param  array $filters array of filters
     * @return array          array of Workouts objects
     */
    public function filter($filters)
    {
        $base = [
            'expand' => 'workout',
        ];
        $workouts = $this->api->get('workouts/history', array_merge($base, $filters));

        $list = [];
        foreach ($workouts->data as $workout) {
            $list[] = new Workout($workout);
        }
        return $list;
    }
}
