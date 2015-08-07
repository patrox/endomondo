<?php

namespace Fabulator;

class WorkoutFactory
{

    private $endomondo;

    public function __construct(Endomondo $endomondo)
    {
        $this->endomondo = $endomondo;
    }

    public function create($source)
    {
        return new Workout($this->endomondo, $source);
    }
}
