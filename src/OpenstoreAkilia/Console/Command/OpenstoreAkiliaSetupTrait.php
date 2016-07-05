<?php

namespace OpenstoreAkilia\Console\Command;


trait OpenstoreAkiliaSetupTrait
{

    /**
     * Gets a helper instance by name.
     *
     * @param string $name The helper name
     *
     * @return mixed The helper value
     *
     * @throws LogicException           if no HelperSet is defined
     * @throws InvalidArgumentException if the helper is not defined
     */
    abstract public function getHelper($name);

    /**
     * Return underlying database connection
     *
     * @return \OpenstoreAkilia\Config\OpenstoreAkiliaSetup
     */
    protected function getOpenstoreAkiliaSetup()
    {
        return $this->getHelper('openstore-akilia-setup')->getSetup();
    }
}
