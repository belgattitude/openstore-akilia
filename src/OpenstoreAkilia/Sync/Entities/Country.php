<?php

namespace OpenstoreAkilia\Sync\Entities;

class Country extends AbstractEntity
{

    public function synchronize()
    {
        $akilia2db = $this->akilia2Db;
        $db = $this->openstoreDb;

        $replace = " insert
                     into $db.country
                    (
                    country_id,
                    reference,
                    name,
                    legacy_synchro_at
                )

                select id,
                       iso_3166_1,
                       name,
                        '{$this->legacy_synchro_at}' as legacy_synchro_at
                    
                from $akilia2db.base_country co
                on duplicate key update
                        reference = co.iso_3166_1,
                        name = co.name,
                        legacy_synchro_at = '{$this->legacy_synchro_at}'
                     ";

        $this->dbExecuter->executeSQL("Replace countries", $replace, true, __CLASS__);

        // 2. Deleting - old links in case it changes
        $delete = "
            delete from $db.country 
            where legacy_synchro_at <> '{$this->legacy_synchro_at}' and legacy_synchro_at is not null";

        $this->dbExecuter->executeSQL("Delete eventual removed countries", $delete, true, __CLASS__);
    }
}
