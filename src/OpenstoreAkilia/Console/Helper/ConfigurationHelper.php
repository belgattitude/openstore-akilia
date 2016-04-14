<?php

namespace OpenstoreAkilia\Console\Helper;

use OpenstoreAkilia\Config\OpenstoreAkiliaSetup;
use Symfony\Component\Console\Helper\Helper;

class ConfigurationHelper extends Helper
{
    /**
     *
     * @var OpenstoreAkiliaSetup
     */
    protected $setup;

    /**
     * 
     * @param OpenstoreAkiliaSetup $openstoreAkiliaSetup
     */
    public function __construct(OpenstoreAkiliaSetup $openstoreAkiliaSetup)
    {
        $this->setup = $openstoreAkiliaSetup;
    }

    /**
     *
     * @return OpenstoreAkiliaSetup
     */
    public function getSetup()
    {
        return $this->setup;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'openstore_akilia_setup';
    }
}
