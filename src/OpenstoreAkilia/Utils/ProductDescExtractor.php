<?php

namespace OpenstoreAkilia\Utils;

use Zend\Db\Adapter\Adapter;

class ProductDescExtractor {

    /**
     * @var Adapter $adapter
     */
    protected $adapter;

    /**
     * ProductDescExtractor constructor.
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }


    /**
     * Extract attributes from product_description
     */
    public function extract()  {

        $lang = 'en';


        
    }

}