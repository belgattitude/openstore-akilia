<?php

namespace OpenstoreAkilia\Sync\Entities;

class Api extends AbstractEntity
{

    public function synchronize()
    {
        
        $akilia2db = $this->akilia2Db;
        $db = $this->openstoreDb;

        // Step 1: let's synchronize the api services

        $replace = " insert
                     into $db.api_service
                    (
                    service_id,    reference,    description,
                    legacy_synchro_at
                )
                select id, reference, description,
                       '{$this->legacy_synchro_at}' as legacy_synchro_at
                from $akilia2db.api_service apis
                on duplicate key update
                        reference = apis.reference,
                        description = apis.description,
                        legacy_synchro_at = '{$this->legacy_synchro_at}'
                     ";
        $this->dbExecuter->executeSQL("Replace api_service", $replace, true, __CLASS__);
        // 2. Deleting - old links in case it changes
        $delete = "
            delete from $db.api_service 
            where legacy_synchro_at <> '{$this->legacy_synchro_at}' and legacy_synchro_at is not null";
        $this->dbExecuter->executeSQL("Delete eventual removed api_service", $delete, true, __CLASS__);

        // Step 2: let' synchronize the api keys

        $replace = " insert
                     into $db.api_key
                    (
                    api_id,    api_key, flag_active,
                    legacy_synchro_at
                )
                select id, api_key, is_active,
                       '{$this->legacy_synchro_at}' as legacy_synchro_at
                from $akilia2db.auth_api aa
                on duplicate key update
                        api_key = aa.api_key,
                        flag_active = aa.is_active,
                        legacy_synchro_at = '{$this->legacy_synchro_at}'
                     ";
        $this->dbExecuter->executeSQL("Replace api_key", $replace, true, __CLASS__);
        // 2. Deleting - old links in case it changes
        $delete = "
            delete from $db.api_key 
            where legacy_synchro_at <> '{$this->legacy_synchro_at}' and legacy_synchro_at is not null";
        $this->dbExecuter->executeSQL("Delete eventual removed api_key", $delete, true, __CLASS__);

        // Step 3: api_key_services

        $replace = " insert
                     into $db.api_key_service
                    (
                    id, api_id,    service_id,
                    legacy_synchro_at
                )
                select id, api_id, service_id,
                       '{$this->legacy_synchro_at}' as legacy_synchro_at
                from $akilia2db.auth_api_service aas
                on duplicate key update
                        legacy_synchro_at = '{$this->legacy_synchro_at}'
                     ";
        $this->dbExecuter->executeSQL("Replace api_key_service", $replace, true, __CLASS__);
        // 2. Deleting - old links in case it changes
        $delete = "
            delete from $db.api_key_service 
            where legacy_synchro_at <> '{$this->legacy_synchro_at}' and legacy_synchro_at is not null";
        $this->dbExecuter->executeSQL("Delete eventual removed api_key_service", $delete, true, __CLASS__);

        // Step 4: api_key_customers
        $replace = " insert
                     into $db.api_key_customer
                    (
                    id, api_id,    customer_id,
                    legacy_synchro_at
                )
                select distinct id, api_id, customer_id,
                       '{$this->legacy_synchro_at}' as legacy_synchro_at
                from $akilia2db.auth_api_customer aac
                on duplicate key update
                        legacy_synchro_at = '{$this->legacy_synchro_at}'
                     ";
        $this->dbExecuter->executeSQL("Replace api_key_customer", $replace, true, __CLASS__);
        // 2. Deleting - old links in case it changes
        $delete = "
            delete from $db.api_key_customer 
            where legacy_synchro_at <> '{$this->legacy_synchro_at}' and legacy_synchro_at is not null";
        $this->dbExecuter->executeSQL("Delete eventual removed api_key_customer", $delete, true, __CLASS__);

        // Resync customer pricelists access
        $this->synchronizeCustomerPricelist();
        
    }
}
