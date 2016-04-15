<?php

$sync_entity_ns = 'OpenstoreAkilia\\Sync\\Entities';


return [
    /**
     * Standard default entities
     */
    'openstore-akilia' => [
        'synchronizer' => [
            'entities_map' => [
                'country' => ['class' => $sync_entity_ns . '\\Country'],
                'country' => ['class' => $sync_entity_ns . '\\Country'],
                'api' => ['class' => $sync_entity_ns . '\\Api'],
                'product_rank' => ['class' => $sync_entity_ns . '\\ProductRank'],
            ]
        ],
    ]
];
