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


        $types      = [
                        'popular' => [
                            'rank_column' => 'popular_rank'
                        ],
                        'fresh' => [
                            'rank_column' => 'fresh_rank'
                        ],
                        'deals' => [
                            'rank_column' => 'deal_rank'
                        ],
            // trending and mostrated not working for now
                        /*
                        'trending' => [
                            'rank_column' => 'trending_rank'
                        ],*/
                        'bestseller' => [
                            'rank_column' => 'bestseller_rank'
                        ]
        ];

        $initialRankColumns = [];
        foreach ($types as $type => $type_config) {
            $initialRankColumns[$type_config['rank_column']] = null;
        }

        // Get brands mapping
        $brands_sql = "select pb.brand_id, pb.reference, count(*) nb_active_products "
                . "from product_brand pb "
                . "inner join product p on p.brand_id = pb.brand_id "
                . "inner join product_pricelist ppl on ppl.product_id = p.product_id "
                . "where pb.flag_active = 1 "
                . "  and ppl.flag_active = 1 "
                . "group by pb.brand_id, pb.reference "
                . "having nb_active_products > 0";

        $brands_map = array_column($adapter->query($brands_sql, Adapter::QUERY_MODE_EXECUTE)->toArray(), 'brand_id', 'reference');
        
        // The null special brand
        $brands_map['NULLBRAND'] = 'NULLBRAND';

        // Get pricelist mapping
        $pricelists_sql = "select pl.pricelist_id, pl.reference, count(*) "
                . "from pricelist pl inner join product_pricelist ppl on ppl.pricelist_id = pl.pricelist_id "
                . "where pl.flag_active = 1 and ppl.flag_active = 1 "
                . "group by pl.pricelist_id, pl.reference "
                . "having count(*) > 0";
        $pricelists_map = array_column($adapter->query($pricelists_sql, Adapter::QUERY_MODE_EXECUTE)->toArray(), 'pricelist_id', 'reference');

        // Get category mapping
        $categories_sql = "select category_id, reference from product_category where flag_rankable = 1";
        $categories_map = array_column($adapter->query($categories_sql, Adapter::QUERY_MODE_EXECUTE)->toArray(), 'category_id', 'reference');

        $pricelists = array_keys($pricelists_map);
        $brands     = array_keys($brands_map);

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
                    
                    if ($brand == 'NULLBRAND') {
                        $params['brands'] = null;
                    } else {
                        $params['brands'] = [$brand];
                    }
                    $params['pricelists'] = [$pricelist];
                    $store = $productRank->getStore($params);
                    $data = $store->getData()->toArray();

                    if (count($data) > 0) {
                        //var_dump($data); die();
                    }
                    $log_line = [
                        str_pad($brand, 7, " "),
                        str_pad($pricelist, 5, " ", STR_PAD_RIGHT),
                        str_pad($type, 15, " ", STR_PAD_RIGHT),
                    ];

                    $this->log(' - ' . implode(' ', $log_line) . str_pad((string) count($data), 3, ' ', STR_PAD_LEFT) . " products");

                    $rank_column = $type_config['rank_column'];

                    $matches = [];
                    foreach ($data as $row) {
                        $category_reference = $row['category_reference'];
                        $product_id = $row['product_id'];
                        // initialize
                        if (!isset($rankings[$pricelist][$brand][$category_reference][$product_id])) {
                            
                            if ($brand_id == 'NULLBRAND') {
                                $brand_id = null;
                            } 
                           
                            $rankings[$pricelist][$brand][$category_reference][$product_id] =
                                 array_merge([
                                    'product_id'   => $product_id,
                                    'category_id'  => $categories_map[$category_reference],
                                    'pricelist_id' => $pricelist_id,
                                    'brand_id'     => $brand_id,
                                    'total_recorded_quantity' => $row['product_total_qty_last_12_months'],
                                    'total_recorded_turnover' => $row['product_total_last_12_months'],
                                    'nb_customers' => $row['product_nb_customers_last_12_months']

                                 ], $initialRankColumns);
                        }
                        $rankings[$pricelist][$brand][$category_reference][$product_id][$rank_column] = $row['rank'];
 
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
                . " total_recorded_quantity, "
                . " total_recorded_turnover, "
                . " nb_customers, "                
                . " deal_rank, "
                . " fresh_rank, "
                . " bestseller_rank, "
                . " popular_rank, "
                . " trending_rank, "
                . " mostrated_rank, "
                . " created_at, "
                . " updated_at"
                . ") "
                . "VALUES ("
                . " :product_id, "
                . " :category_id, "
                . " :pricelist_id, "
                . " :brand_id,"
                . " :total_recorded_quantity, "
                . " :total_recorded_turnover, "
                . " :nb_customers, "                
                . " :deal_rank,"
                . " :fresh_rank,"
                . " :bestseller_rank,"
                . " :popular_rank,"
                . " :trending_rank,"
                . " :mostrated_rank,"
                . " '$legacy_synchro_at',"
                . " '$legacy_synchro_at'"
                . ") "
                . "ON DUPLICATE KEY UPDATE "
                . " total_recorded_quantity = VALUES(total_recorded_quantity),"
                . " total_recorded_turnover = VALUES(total_recorded_turnover),"
                . " nb_customers = VALUES(nb_customers),"                
                . " deal_rank = VALUES(deal_rank),"
                . " fresh_rank = VALUES(fresh_rank),"
                . " bestseller_rank = VALUES(bestseller_rank),"
                . " popular_rank = VALUES(popular_rank),"
                . " trending_rank = VALUES(trending_rank),"
                . " mostrated_rank = VALUES(mostrated_rank),"
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
                            $to_update['total_recorded_quantity'],
                            $to_update['total_recorded_turnover'],
                            $to_update['nb_customers'],
  
                            isset($to_update['deal_rank']) ? $to_update['deal_rank'] : null,
                            isset($to_update['fresh_rank']) ? $to_update['fresh_rank'] : null,
                            isset($to_update['bestseller_rank']) ? $to_update['bestseller_rank'] : null,
                            isset($to_update['popular_rank']) ? $to_update['popular_rank'] : null,
                            isset($to_update['trending_rank']) ? $to_update['trending_rank'] : null,
                            isset($to_update['mostrated_rank']) ? $to_update['mostrated_rank'] : null,
                        ];
                        //var_dump($paramters); die();
                        $stmt->execute($parameters);
                        $cpt++;
                    }
                }
            }
        }
        
        $delete = "delete from product_rank where updated_at <> '$legacy_synchro_at'";
        
       
        $ret = $adapter->query($delete)->execute();
       
        $this->updateProductPricelistFlags($adapter);

        $this->log("Successfully loaded $cpt new ranking rows");
    }


    public function updateProductPricelistFlags(Adapter $adapter)
    {
        $delete = "update product_pricelist ppl set "
                . " ppl.bestseller_rank = null, "
                . " ppl.deal_rank = null, "
                . " ppl.fresh_rank = null, "
                . " ppl.popular_rank = null, "
                . " ppl.trending_rank = null ";

        $adapter->query($delete);

        $update = "update product_pricelist ppl "
                . "inner join pricelist pl on pl.pricelist_id = ppl.pricelist_id "
                . "inner join product_rank pr "
                . " on pr.product_id = ppl.product_id and ppl.pricelist_id = pr.pricelist_id "
                . "set "
                . " ppl.bestseller_rank = if (pr.bestseller_rank > 0, 1, 0), "
                . " ppl.deal_rank = if (pr.deal_rank > 0, 1, 0), "
                . " ppl.fresh_rank = if (pr.fresh_rank > 0, 1, 0), "
                . " ppl.popular_rank = if (pr.popular_rank > 0, 1, 0), "
                . " ppl.trending_rank = if (pr.trending_rank > 0, 1, 0)"
                . "where pr.brand_id is null";


        $adapter->query($update);
    }

    protected function log($message)
    {
        echo $message . "\n";
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
