<?php

namespace OpenstoreAkilia\Sync\Entities;

class ProductRank extends AbstractEntity
{

    public function synchronize()
    {
        $akilia2db = $this->akilia2Db;
        $db = $this->openstoreDb;

        $module_path = $this->setup->getOpenbridgeModulePath('ng_mk_product_rank');
        
        
        var_dump($module_path);
        die();
    }
    
    
}
