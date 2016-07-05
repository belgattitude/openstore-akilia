<?php

namespace OpenstoreAkilia\Utils;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class ProductDescExtractor
{

    /**
     * @var Adapter $adapter
     */
    protected $adapter;

    /**
     * ProductDescExtractor constructor.
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }


    /**
     * Extract attributes from product_description
     * @return array
     */
    public function extract()
    {
        $params = [
            'lang' => 'en',
        ];

        $select = $this->getProductDescSelect($params);
        $sql = new Sql($this->adapter);
        $string = $sql->buildSqlString($select);
        $rows = $this->adapter->query($string, Adapter::QUERY_MODE_EXECUTE);

        $extracted = [];
        $extracted_stats = [
            'diameter' => 0,
            'weight' => 0,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'color' => 0,
            'total_extracted' => 0,
            'weight_warnings' => 0,
            'diameter_warnings' => 0,
        ];

        foreach ($rows as $row) {
            $extraction_available = false;
            $product_id = $row['product_id'];

            // Step 1: extract diameter from title or invoice title
            // only if there's only one diameter specified (") to prevent matching 6" x 11"
            if (substr_count($row['title'], '"')  == 1) {
                $text = str_replace(',', '.', $row['title']);
                $match = preg_match_all('/(([1-3]?[0-9](\.[1-9])?)\ ?")/', $text, $matches);
                if ($match && $matches[2][0] > 0) {
                    $diameter = $matches[2][0];
                    $extracted_stats['diameter']++;
                    $extraction_available = true;
                    // meters to inches = 1m * 39.3700787402
                    //$products[$product_id]['diameter'] = $diameter * 0.0254;
                }
            } else {
                $diameter = null;
            }
            // Step 2: extract product weight from description
            // i.e. Weight: 50 g (1.75 oz.) with cord

            $text = strtolower(str_replace("\n", ' ', $row['description'] . ' ' . $row['characteristic']));
            $text = str_replace(' :', ':', $text);
            $text = str_replace(',', '.', $text);
            if (substr_count($text, ' weight:') == 1) {
                if (preg_match_all('/(\ weight:\ ?(([0-9]+(\.[0-9]+)?)\ ?(kilogram|gram|kg|g)))/', $text, $matches)) {
                    $unit = $matches[5][0];
                    if (in_array($unit, ['g', 'gram'])) {
                        $multiplier = 1000;
                    } elseif (in_array($unit, ['kg', 'kilogram'])) {
                        $multiplier = 1;
                    } else {
                        $multiplier = 0; // unsupported unit
                    }

                    $net_weight = $matches[3][0] / $multiplier;
                    $extracted_stats['weight']++;
                    $extraction_available = true;
                }
            } else {
                $net_weight = null;
            }

            // Step 3. Extract length if exists
            $text = strtolower(str_replace("\n", ' ', $row['description'] . ' ' . $row['characteristic']));
            $text = str_replace(' :', ':', $text);
            $text = str_replace(',', '.', $text);
            if (substr_count($text, ' length:') == 1) {
                $matched = preg_match_all('/(\ length:\ ?(([0-9]+(\.[0-9]+)?)\ ?(meter|millimeter|centimer|cm|mm|m)))/', $text, $matches);
                if ($matched) {
                    $unit = $matches[5][0];
                    if (in_array($unit, ['centimeter', 'cm'])) {
                        $multiplier = 100;
                    } elseif (in_array($unit, ['mm', 'millimeter'])) {
                        $multiplier = 1000;
                    } elseif (in_array($unit, ['m', 'meter'])) {
                        $multiplier = 1;
                    } else {
                        $multiplier = 0; // unsupported unit
                    }

                    $length = $matches[3][0] / $multiplier;
                    $extracted_stats['length']++;
                    $extraction_available = true;
                }
            } else {
                $length = null;
            }


            // Step 4: extract dimensions only if length
            // is not provided. For example the cables

            if (!$length) {
                $text = strtolower(str_replace("\n", ' ', $row['description'] . ' ' . $row['characteristic']));
                $text = str_replace(' :', ':', $text);
                $text = str_replace('*', 'x', $text);
                $text = str_replace(',', '.', $text);
                $matched = preg_match_all('/([0-9]+(\.[0-9]+)?)x([0-9]+(\.[0-9]+)?)(x([0-9]+(\.[0-9]+)?))?\ ?(meter|millimeter|centimer|cm|mm|m)/', $text, $matches);

                if ($matched == 1) {
                    $unit = $matches[8][0];

                    if (in_array($unit, ['centimeter', 'cm'])) {
                        $multiplier = 100;
                    } elseif (in_array($unit, ['mm', 'millimeter'])) {
                        $multiplier = 1000;
                    } elseif (in_array($unit, ['m', 'meter'])) {
                        $multiplier = 1;
                    } else {
                        $multiplier = 0; // unsupported unit
                    }
                    $length = $matches[1][0] / $multiplier;
                    $width  = $matches[3][0] / $multiplier;
                    $height = $matches[6][0] / $multiplier;

                    $extraction_available = true;
                    $extracted_stats['length']++;
                    $extracted_stats['width']++;
                    // optional
                    if ($height > 0) {
                        $extracted_stats['height']++;
                    } else {
                        $height = null;
                    }
                } else {
                    $width = null;
                    $length = null;
                    $height = null;
                }
            } else {
                $width = null;
                $height = null;
            }

            // Step 5: Extract color

            $text = strtolower(str_replace("\n", '@', $row['title'] . ' ' . str_replace('- ', "\n", $row['description']) . ' ' . str_replace('- ', "\n", $row['characteristic'])));
            $text = str_replace(' :', ':', $text);
            $text = strtolower($text);
            //if (substr_count($text, 'color:') == 1) {
            $matched = preg_match_all('/(colo(u)?r:\ ?([^@\(]+))/', $text, $matches);
            if ($matched) {
                $color = $matches[3][0];
                $color = str_replace('&', ' and ', $color);
                $color = str_replace('/', ' and ', $color);
                $color = str_replace('  ', ' ', $color);
                $color = str_replace('highgloss', '', $color);
                $color = str_replace('high-gloss', '', $color);
                $color = str_replace('semigloss', '', $color);
                $color = str_replace('semi-gloss', '', $color);
                $color = trim($color);

                if (!preg_match('~\b(with|and|loss|mat|matt|tone)\b~i', $color)) {
                    $extraction_available = true;
                    $extracted_stats['color']++;
                } else {
                    $color = null;
                }
            } else {
                $color = null;
            }
            //}
            //
            // END of various extractions
            //
            if ($extraction_available) {
                $extracted_stats['total_extracted']++;
                $warnings = [];
                $diff_bbx_gross_weight = ($row['weight_gross'] != '' && $net_weight !== null)
                    ? ($net_weight - $row['weight_gross']) : null;
                $diff_bbx_net_weight = ($row['weight'] != '' && $net_weight !== null)
                    ? ($net_weight - $row['weight']) : null;
                if ($diff_bbx_gross_weight > 0) {
                    $warnings[] = "net($net_weight)>gross(" . number_format($row['weight_gross'], 2) . ")";
                    $extracted_stats['weight_warnings']++;
                } elseif ($diff_bbx_gross_weight === 0) {
                    $warnings[] = "net=gross";
                    $extracted_stats['weight_warnings']++;
                }


                $extracted[] = [
                    'product_id' => $product_id,
                    'product_reference' => $row['reference'],
                    'diameter' => $diameter,
                    'net_weight' => $net_weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'color' => $color,
                    'warnings' => implode(',', $warnings),
                ];
            }
        }

        return [
            'data' => $extracted,
            'stats' => [
                [
                    'attribute' => 'total_products',
                    'total' => count($rows),
                    'warnings' => ''
                ],[
                    'attribute' => 'total_extracted',
                    'total' => $extracted_stats['total_extracted'],
                    'warnings' => ''
                ],
                [
                    'attribute' => 'net weight',
                    'total' => $extracted_stats['weight'],
                    'warnings' => $extracted_stats['weight_warnings']
                ],[
                    'attribute' => 'diameter',
                    'total' => $extracted_stats['diameter'],
                    'warnings' => $extracted_stats['diameter_warnings']
                ],[
                    'attribute' => 'length',
                    'total' => $extracted_stats['length'],
                    'warnings' => ''
                ],[
                    'attribute' => 'width',
                    'total' => $extracted_stats['width'],
                    'warnings' => ''
                ],[
                    'attribute' => 'height',
                    'total' => $extracted_stats['height'],
                    'warnings' => ''
                ],[
                    'attribute' => 'color',
                    'total' => $extracted_stats['color'],
                    'warnings' => ''
                ]
            ]
        ];
    }

    /**
     *
     * @return Select
     */
    public function getProductDescSelect(array $params)
    {
        $lang = $params['lang'];

        $select = new Select();

        $select->from(['p' => 'product'], [])
            ->join(['p18' => 'product_translation'], new Expression("p18.product_id = p.product_id and p18.lang='$lang'"), [], $select::JOIN_LEFT)
            ->join(['pb' => 'product_brand'], new Expression('pb.brand_id = p.brand_id'), [])
            ->join(['pstub' => 'product_stub'], new Expression('pstub.product_stub_id = p.product_stub_id'), [], $select::JOIN_LEFT)
            ->join(['pstub18' => 'product_stub_translation'], new Expression("pstub.product_stub_id = pstub18.product_stub_id and pstub18.lang='$lang'"), [], $select::JOIN_LEFT)
            ->join(['pc' => 'product_category'], new Expression('p.category_id = pc.category_id'), [], $select::JOIN_LEFT)
            ->join(['pc18' => 'product_category_translation'], new Expression("pc.category_id = pc18.category_id and pc18.lang='$lang'"), [], $select::JOIN_LEFT)
            ->join(['pg' => 'product_group'], new Expression('pg.group_id = p.group_id'), [], $select::JOIN_LEFT)
            ->join(['pg18' => 'product_group_translation'], new Expression("pg18.group_id = pg.group_id and pg18.lang='$lang'"), [], $select::JOIN_LEFT)

            ->join(
                ['ppl' => 'product_pricelist'],
                new Expression("ppl.product_id = p.product_id"),
                [],
                $select::JOIN_INNER
            )
            ->join(
                ['pl' => 'pricelist'],
                new Expression("ppl.pricelist_id = pl.pricelist_id"),
                [],
                $select::JOIN_INNER
            )
            ->join(['pt'  => 'product_type'], new Expression('p.type_id = pt.type_id'), [], $select::JOIN_LEFT)
            ->join(['pst' => 'product_status'], new Expression('pst.status_id = ppl.status_id'), [], $select::JOIN_LEFT)
            ->join(['serie'  => 'product_serie'], new Expression('serie.serie_id = p.serie_id'), [], $select::JOIN_LEFT);

        $columns = [
            'product_id' => new Expression('p.product_id'),
            'reference' => new Expression('p.reference'),
            'brand_id'  => new Expression('p.brand_id'),
            'brand_reference' => new Expression('pb.reference'),
            'category_id' => new Expression('p.category_id'),
            'category_reference' => new Expression('pc.reference'),
            'category_title' => new Expression('pc18.title'),
            'category_breadcrumb' => new Expression('pc18.breadcrumb'),
            'serie_id'  => new Expression('serie.serie_id'),
            'serie_reference' => new Expression('serie.reference'),
            'product_type_id' => new Expression('pt.type_id'),
            'product_type_reference' => new Expression('pt.reference'),
            'product_status_id' => new Expression('pst.status_id'),
            'product_status_reference' => new Expression('pst.reference'),

            'product_stub_id' => new Expression('pstub.product_stub_id'),
            'product_stub_reference' => new Expression('pstub.reference'),

            'invoice_title' => new Expression('COALESCE(p18.invoice_title, p.invoice_title)'),
            'title' => new Expression('COALESCE(p18.title, p.title)'),

            // @killparent, when the id_art_tete will be fully removed, use the second commented column instead of this one
            // This hack allows to not include twice the parent description
            'description' => new Expression('
                    CONCAT_WS("\n",
                        pstub18.description_header,
                        if ((pstub.product_stub_id is not null and p.parent_id is null), 
                                null, 
                                if (p.product_stub_id is null,
                                    COALESCE(p18.description, p.description),
                                    p18.description
                                )
                            ),
                        pstub18.description_footer
                    )    
                    '),
            /*
            'product_description' => new Expression('
                    CONCAT_WS("\n",
                        pstub18.description_header,
                        COALESCE(p18.description, p.description)
                        pstub18.description_footer
                    )
                    '),
            */
            'characteristic' => new Expression('COALESCE(p18.characteristic, p.characteristic)'),
            'created_at' => new Expression('p.created_at'),
            'weight' => new Expression('p.weight'),
            'weight_gross' => new Expression('p.weight_gross'),
            'volume' => new Expression('p.volume'),
            'length' => new Expression('p.length'),
            'width' => new Expression('p.width'),
            'height' => new Expression('p.height'),
            'diameter' => new Expression('p.diameter')
       ];

        $select->columns(array_merge($columns, [
            'product_status_references' => new Expression('GROUP_CONCAT(distinct pst.reference order by pst.reference)'),
            'flag_end_of_lifecycle' => new Expression('MAX(pst.flag_end_of_lifecycle)'),
            'end_of_lifecycle_pricelists' => new Expression('GROUP_CONCAT(distinct if(pst.flag_end_of_lifecycle = 1, pl.reference, null) ORDER BY pl.reference)'),
            'active_pricelists' => new Expression("GROUP_CONCAT(distinct pl.reference order by pl.reference)"),
        ]), true);
        $select->group(array_keys($columns));
        $select->order('pc.category_id, pc.sort_index, p.reference');

        // Standard where clause, excluding products that are not in any pricelists:
        $select->where([
            'p.flag_active' => 1,
            'ppl.flag_active' => 1,
            //'pb.reference' => 'STAG',
            "pb.reference <> '****'"
        ]);

        return $select;
    }
}
