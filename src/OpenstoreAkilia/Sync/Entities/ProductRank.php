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
                                    'pricelist_id' => $pricelist_id,
                                    'brand_id'     => $brand_id,
                                    'category_id'  => $categories_map[$category_reference],
                                    'product_id'   => $product_id,
                                 ], $initialRankColumns);
                        }
                        $rankings[$pricelist][$brand][$category_reference][$product_id][$rank_column]   = $row['rank'];
                    }
                }
            }
        }

        $insert_data = [];
        foreach ($rankings as $pricelist => $ranking_brands) {
            foreach ($ranking_brands as $brand => $ranking_categories) {
                foreach ($ranking_categories as $category => $ranking_products) {
                    foreach ($ranking_products as $product_id => $to_update) {
                        var_dump($to_update);
                        die();
                        $insert_data[] = array_values($to_update);
                    }
                }
            }
        }
        var_dump($insert_data);
        die();
        die();


        die();
        var_dump($productRank);
        die();
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
