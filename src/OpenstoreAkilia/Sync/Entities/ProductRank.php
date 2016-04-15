<?php

namespace OpenstoreAkilia\Sync\Entities;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;


class ProductRank extends AbstractEntity
{


    public function synchronize()
    {
        $akilia2db = $this->akilia2Db;
        $db = $this->openstoreDb;

        $this->includeOpenbridgeFiles();

        $serviceLocator = $this->setup->getServiceLocator();
        $adapter = $this->setup->getDatabaseAdapter();
        $sql = new Sql($adapter);
        $productRank = new \MkProductRank\Marketing\ProductRank($serviceLocator);

        $pricelists = ['BE', 'FR', 'NL', 'DE', '100B', 'US'];
        $brands     = ['STAG', 'REMO', 'ANGE', 'JAME', 'LARO'];
        
//        $pricelists = ['BE']; $brands = ['REMO'];
        
        $types      = [
                        'popular' => [
                            'rank_column' => 'popular_rank_position'
                        ],
                        'fresh' => [
                            'rank_column' => 'fresh_rank_position'
                        ],
                        'deals' => [
                            'rank_column' => 'deal_rank_position'
                        ],
            // trending and mostrated not working for now
                        /*
                        'trending' => [
                            'rank_column' => 'trending_rank_position'
                        ],*/
                        'bestseller' => [
                            'rank_column' => 'bestseller_rank_position'
                        ]
        ];

        $initialRankColumns = [];
        foreach ($types as $type => $type_config) {
            $initialRankColumns[$type_config['rank_column']] = null;
        }

        // Get brands mapping
        $brands_sql = "select brand_id, reference from product_brand";
        $brands_map = array_column($adapter->query($brands_sql, Adapter::QUERY_MODE_EXECUTE)->toArray(), 'brand_id', 'reference');

        // Get pricelist mapping
        $pricelists_sql = "select pricelist_id, reference from pricelist";
        $pricelists_map = array_column($adapter->query($pricelists_sql, Adapter::QUERY_MODE_EXECUTE)->toArray(), 'pricelist_id', 'reference');

        // Get category mapping
        $categories_sql = "select category_id, reference from product_category";
        $categories_map = array_column($adapter->query($categories_sql, Adapter::QUERY_MODE_EXECUTE)->toArray(), 'category_id', 'reference');


        $rankings = [];


        foreach ($brands as $brand) {
            if (!array_key_exists($brand, $brands_map)) {
                throw new \Exception("Error synchronizing ProductRank, brand '$brand' does not exists in db");
            } else {
                $brand_id = $brands_map[$brand];
            }

            foreach ($pricelists as $pricelist) {
                if (!array_key_exists($pricelist, $pricelists_map)) {
                    throw new \Exception("Error synchronizing ProductRank, pricelist '$pricelist' does not exists in db");
                } else {
                    $pricelist_id = $pricelists_map[$pricelist];
                }

                foreach ($types as $type => $type_config) {
                    $params = $this->getTypeParams($type);
                    $params['brands'] = [$brand];
                    $params['pricelists'] = [$pricelist];
                    $store = $productRank->getStore($params);
                    $data = $store->getData();
                    $rank_column = $type_config['rank_column'];

                    $matches = [];
                    foreach ($data as $row) {
                        $category_reference = $row['category_reference'];
                        $product_id = $row['product_id'];
                        // initialize
                        if (!isset($rankings[$pricelist][$brand][$category_reference][$product_id])) {
                            $rankings[$pricelist][$brand][$category_reference][$product_id] =
                                 array_merge([
                                    'product_id'   => $product_id,
                                    'category_id'  => $categories_map[$category_reference],
                                    'pricelist_id' => $pricelist_id,
                                    'brand_id'     => $brand_id,
                                    
                                    
                                 ], $initialRankColumns);
                        }
                        $rankings[$pricelist][$brand][$category_reference][$product_id][$rank_column]   = $row['rank'];
                    }
                }
            }
        }

        
        // SAVE RESULTS IN DATABASE
        $legacy_synchro_at = $this->legacy_synchro_at;
        $replace = "INSERT INTO product_rank"
                . "(product_id, "
                . " rankable_category_id, "
                . " pricelist_id, "
                . " brand_id,"
                . " deal_rank_position, "
                . " fresh_rank_position, "
                . " bestseller_rank_position, "
                . " popular_rank_position, "
                . " trending_rank_position, "
                . " mostrated_rank_position, "
                . " created_at, "
                . " updated_at"
                . ") "
                . "VALUES ("
                . " :product_id, "
                . " :category_id, "
                . " :pricelist_id, "
                . " :brand_id,"
                . " :deal_rank_position,"
                . " :fresh_rank_position,"
                . " :bestseller_rank_position,"
                . " :popular_rank_position,"
                . " :trending_rank_position,"
                . " :mostrated_rank_position,"
                . " '$legacy_synchro_at',"
                . " '$legacy_synchro_at'"
                . ") "
                . "ON DUPLICATE KEY UPDATE "
                . " deal_rank_position = VALUES(deal_rank_position),"
                . " fresh_rank_position = VALUES(fresh_rank_position),"
                . " bestseller_rank_position = VALUES(bestseller_rank_position),"
                . " popular_rank_position = VALUES(popular_rank_position),"
                . " trending_rank_position = VALUES(trending_rank_position),"
                . " mostrated_rank_position = VALUES(deal_rank_position),"
                . " updated_at = '$legacy_synchro_at'";
        
        $replace_stmt = preg_replace('/(:[a-z\_]+)/', '?', $replace);
        $stmt = $adapter->createStatement($replace_stmt);
        $stmt->prepare();
        $insert_data = [];
        $cpt =0;
        
        foreach ($rankings as $pricelist => $ranking_brands) {
            foreach ($ranking_brands as $brand => $ranking_categories) {
                foreach ($ranking_categories as $category => $ranking_products) {
                    foreach ($ranking_products as $product_id => $to_update) {
                        //$insert_data[] = $to_update;
                        $parameters = [
                            $to_update['product_id'],
                            $to_update['category_id'],
                            $to_update['pricelist_id'],
                            $to_update['brand_id'],
                            isset($to_update['deal_rank_position']) ? $to_update['deal_rank_position'] : null,
                            isset($to_update['fresh_rank_position']) ? $to_update['fresh_rank_position'] : null,
                            isset($to_update['bestseller_rank_position']) ? $to_update['bestseller_rank_position'] : null,
                            isset($to_update['popular_rank_position']) ? $to_update['popular_rank_position'] : null,
                            isset($to_update['trending_rank_position']) ? $to_update['trending_rank_position'] : null,
                            isset($to_update['mostrated_rank_position']) ? $to_update['mostrated_rank_position'] : null,
                        ];
                        $stmt->execute($parameters);
                        $cpt++;
                    }
                }
            }
        }

        echo "Affected rows $cpt";
    }


    /**
     * 
     * @param string $type
     * @return array
     * @throws \Exception
     */
    protected function getTypeParams($type)
    {
        $params = [];
        $params['min_customers_last_12_months'] = 1;
        $params['min_product_total_last_12_months'] = 500;
        $params['product_top'] = 10;
        $params['product_count_pct'] = 0.18;
        $params['minimum_categ_total'] = 10000;

        switch ($type) {
           case 'fresh':
                $min_date = new \DateTime();
                $min_date->sub(new \DateInterval('P12M'));
                $params['minimum_first_sale_recorded_at'] = $min_date;
                $params['product_top'] = 10;
                $params['product_count_pct'] = 0.8;
                $params['minimum_categ_total'] = 500;
                break;
            case 'popular':
                $params['is_popular'] = true;
                $params['min_customers_last_12_months'] = 10;
                $params['product_count_pct'] = 0.4;
                $params['minimum_categ_total'] = 10000;
                break;
            case 'trending':
                $params['is_trending'] = true;
                $params['min_customers_last_12_months'] = 10;
                $params['product_count_pct'] = 0.2;
                $params['minimum_categ_total'] = 10000;
                break;

            case 'deals':
                $params['is_discounted'] = true;
                $params['product_top'] = 10;
                $params['product_count_pct'] = 0.4;
                $params['minimum_categ_total'] = 5000;
                break;
            case 'bestseller':
                // Nothing to do - use defaults
                break;

            default:
                throw new \Exception("Report type '$type' not supported");

        }

        return $params;
    }


    protected function includeOpenbridgeFiles()
    {
        $openbridge_path = $this->setup->getOpenbridgePath();
        $module_path = $this->setup->getOpenbridgeModulePath('ng_mk_product_rank');


        require_once $openbridge_path . '/core/src/Openbridge/ServiceManager/AbstractServiceLocatorAware.php';
        require_once $module_path . '/src/MkProductRank/Marketing/Params/RankableTrait.php';
        require_once $module_path . '/src/MkProductRank/Marketing/CategoryRank.php';
        require_once $module_path . '/src/MkProductRank/Marketing/ProductRank.php';
    }
}
