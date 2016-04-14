<?php

namespace OpenstoreAkilia\Sync\Entities;

class Customer extends AbstractEntity
{

    public function synchronize()
    {
        
        $akilia2db = $this->akilia2Db;
        $db = $this->openstoreDb;

        $replace = " insert
                     into $db.customer
                    (
                    customer_id,
                    reference,
                    name,
                    first_name,
                    flag_active,
                    street,
                    street_2,
                    street_number,
                    zipcode,
                    city,
                    country_id,
                    legacy_mapping,
                    legacy_synchro_at
                )

                select bc.id,
                       bc.reference,
                       bc.name,
                       bc.first_name,
                       if (bc.flag_archived = 1, 0, 1) as flag_active,
                       bc.street,
                       bc.street_2,
                       bc.street_number,
                       bc.zipcode,
                       bc.city,
                       bc.country_id,
                       bc.id as legacy_mapping,
                       '{$this->legacy_synchro_at}' as legacy_synchro_at
                    
                from $akilia2db.base_customer bc
                on duplicate key update
                       reference = bc.reference,
                       name = bc.name,
                       first_name = bc.first_name,
                       flag_active = if (bc.flag_archived = 1, 0, 1),
                       street = bc.street,
                       street_2 = bc.street_2,
                       street_number = bc.street_number,
                       zipcode = bc.zipcode,
                       city = bc.city,
                       country_id = bc.country_id,                
                       legacy_synchro_at = '{$this->legacy_synchro_at}'
                     ";

        $this->dbExecuter->executeSQL("Replace customers", $replace, true, __CLASS__);

        // 2. Deleting - old links in case it changes
        $delete = "
            delete from $db.customer
            where legacy_synchro_at <> '{$this->legacy_synchro_at}' and legacy_synchro_at is not null";

        $this->dbExecuter->executeSQL("Delete eventual removed customers", $delete, true, __CLASS__);
        
    }
}
