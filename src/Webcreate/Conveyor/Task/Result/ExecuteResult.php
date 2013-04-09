<?php

/*
 * @author Jeroen Fiege <jeroen@webcreate.nl>
 * @copyright Webcreate (http://webcreate.nl)
 */

namespace Webcreate\Conveyor\Task\Result;

class ExecuteResult
{
    protected $derived;
    protected $removed;

    /**
     * Constructor.
     *
     * @param array $derived
     * @param array $removed
     */
    public function __construct(array $derived = array(), array $removed = array())
    {
        $this->setDerived($derived);
        $this->setRemoved($removed);
    }

    /**
     * Sets derived files
     *
     * @param array $derived
     * @return \Webcreate\Conveyor\Task\Result\ExecuteResult
     */
    public function setDerived(array $derived)
    {
        $this->derived = $derived;

        return $this;
    }

    /**
     * Gets derived files
     *
     * @return array
     */
    public function getDerived()
    {
        return $this->derived;
    }

    /**
     * Sets removed files
     *
     * @param array $removed
     * @return Webcreate\Conveyor\Task\Result\ExecuteResult
     */
    public function setRemoved(array $removed)
    {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Gets removed files
     *
     * @return array
     */
    public function getRemoved()
    {
        return $this->removed;
    }

}