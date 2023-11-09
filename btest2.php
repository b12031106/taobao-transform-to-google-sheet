<?php
require 'vendor/autoload.php';
require "./IopSdk.php";
require "./Config.php";

ini_set('memory_limit', '512M');

$token = json_decode($token_body, true);
$access_token = $token['access_token'];

function getAuthTokenCreate($code)
{
    global $app_key;
    global $app_secret;

    $c = new IopClient('https://api.taobao.global/rest', $app_key, $app_secret);
    $c->logLevel = Constants::$log_level_debug;
    $request = new IopRequest('/auth/token/create', 'GET');
    $request->addApiParam('code', $code);
    return $c->execute($request);
}

function get($path, $params = []): string
{
    global $access_token;
    global $app_key;
    global $app_secret;

    $c = new IopClient('https://api.taobao.global/rest', $app_key, $app_secret);
    $c->logLevel = Constants::$log_level_debug;
    $request = new IopRequest($path, 'GET');

    foreach ($params as $key => $val) {
        $request->addApiParam($key, $val);
    }

    return $c->execute($request, $access_token);
}

function post($path, $params = []) : string
{
    global $access_token;

    $c = new IopClient('https://api.taobao.global/rest', $app_key, $app_secret);
    $request = new IopRequest($path, 'POST');

    foreach ($params as $key => $val) {
        $request->addApiParam($key, $val);
    }

    return $c->execute($request, $access_token);
}

function jsonPretty(string $response) : string
{
    return json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function productSpusGet($page_no = 1)
{
    $start_unixtime = strtotime('2023-10-25 00:00:00');
    $end_unixtime = strtotime('2023-10-28 00:00:00');

    echo "\n";
    echo date('Y-m-d H:i:s', $start_unixtime);
    echo " ~ ";
    echo date('Y-m-d H:i:s', $end_unixtime);

    $start_modified_time = $start_unixtime * 1000;
    $end_modified_time = $end_unixtime * 1000;

    $spus_get_type = 'PAGINATION';
    $spus_get_type = 'SCROLL';

    $scroll_id = 'eJxdUstuxCAM/Bpy6SVAnocctrvtsb+AUOJsaHmkQKpmv74mD6ktQoKxxxMzjtAyRNEvPjjf8YbzsuBVU3HG6zYTofdawPcsojLQ0aptiqrIC8pqmo0Q+0mMCvQQOg9aRuWsUAPhz3eDkh5khAMYN6hxPcAyD3smgNbg9xIz72dUUcN5iseUaFHGJeBF2S+w0fkkNHvVb+phFj3KYbWYZZx+h6w0cAaNVNicuYvFa4TOD6K3UXDj7JY/A/R/oB7kmj6OzwnOZqPzRsbuPd0/F/Br1zs7qjvht0lFwi9lnhN23WkIExGxBy/tBylfg3pg2xeWWBXuTQOLCWt2O5CTjLgQVjOa4yp50dKKIiasfbq83Z4S9/Ak0Y7UrjcqHVGE39BplDqNf0mzozjWpJhvKn8J1224jBUbIQuAzxpEalrEdYYOfwIPIeCIfwAXubhL';

    return get('/product/spus/get', [
        'page_size' => 500,
        'start_modified_time' => $start_modified_time,
        'end_modified_time' => $end_modified_time,
        'status' => 'normal',
        'spus_get_type' => $spus_get_type,
        //'page_no' => $page_no,
        'scroll_id' => $scroll_id,
    ]);
}

function productSpusGetByScroll($start_unixtime, $end_unixtime, $scroll_id, $page_size = 500)
{
    $start_modified_time = $start_unixtime * 1000;
    $end_modified_time = $end_unixtime * 1000;

    $spus_get_type = 'SCROLL';

    $params = [
        'page_size' => $page_size,
        'start_modified_time' => $start_modified_time,
        'end_modified_time' => $end_modified_time,
        'status' => 'normal',
        'spus_get_type' => $spus_get_type,
    ];

    if ($scroll_id) {
        $params['scroll_id'] = $scroll_id;
    }

    return get('/product/spus/get', $params);
}

function itemGet($item_id)
{
    return get('/item/get', ['item_id' => $item_id]);
}

function productDetailsQuery($item_ids)
{
    return get('/product/details/query', [
        'items' => sprintf('[%s]', implode(',', $item_ids)),
        // 'market_code' => 'TW',
    ]);
}

function deliveryAddressGet()
{
    return get('/delivery/address/get', [
        'country' => '228',
    ]);
}

function getOrderItem()
{
    return [
        'item_id' => '2048241117207587',
        'quantity' => 1,
        'sku_id' => '5159117406243',
    ];
}

function getWarehouseAddress()
{
    return json_encode([
        'zip' => 111111,
        'country' => '中国',
        'state' => '浙江',
        'city' => '杭州市',
        'address' => '小王新村',
        'name' => '大明',
        'mobile_phone' => '15432456575',
    ]);
}

function getOrderData()
{
    return [
        'need_supplychain_service' => 'false',
        'receiver_address' => json_encode([
            'zip' => 110,
            'country' => '台灣',
            'address' => '南港區三重路19之3號D棟5F',
        ]),
        'render_item_List' => json_encode([
            getOrderItem(),
        ]),
        'warehouse_address' => getWarehouseAddress(),
    ];
}

function purchaseOrderRender()
{
    // 台北市115南港區三重路19之3號D棟5F
    return get('/purchase/order/render', getOrderData());
}

function purchaseOrderCreate()
{
    return post('/purchase/order/create', [
        'outer_purchase_id' => date('YmdHis') . rand(1000, 9999),
        'purchase_amount' => '443',
        'order_line_list' => json_encode([
            [
                'item_id' => '2048241117207587',
                'orderLineNo' => 1,
                'quantity' => 1,
                'price' => 23,
                'currency' => 'CNY',
                'title' => '測試商品',
                'skuId' => '5159117406243',
            ]
        ]),
        'receiver' => getWarehouseAddress(),
        'warehouse_address_info' => getWarehouseAddress(),
    ]);
}

function productRelationBuild($item_ids)
{
    return get('/product/relation/build', [
        'item_ids' => sprintf('[%s]', implode(',', $item_ids)),
    ]);
}

function purchaseOrdersQuery()
{
    return get('/purchase/orders/query', [
        'modify_time_start' => strtotime('before 30 days') * 1000,
        'modify_time_end' => strtotime('after 30 days') * 1000,
    ]);
}

function mpidGet($item_id)
{
    return get('/mpId/get', [
        'item_id' => $item_id,
    ]);
}

function promotionQuery($mp_id)
{
    return get('/promotion/query', [
        'param' => $mp_id,
    ]);
}

// $item_id = '641592387397';
$item_id = '2048241117207587';

$requests = [
    // productSpusGet(),
    // itemGet($item_id),
    // productDetailsQuery([$item_id]),
    // deliveryAddressGet(),
    // purchaseOrderRender(),
    // purchaseOrderCreate(),
    // productRelationBuild('731350215252'),
    // purchaseOrdersQuery(),
    // mpidGet($item_id),
    // promotionQuery($item_id),
];

foreach ($requests as $request) {
    echo "\n" . jsonPretty($request);
}
// echo "\n" . jsonPretty($product_spus_get);

// $item_get = get('/item/get', ['item_id' => '641592387397']);
// echo "\n" . jsonPretty($item_get);

// $product_details_query = get('/product/details/query', ['items' => "[641592387397]"]);
// echo "\n" . jsonPretty($product_details_query);

function generateNewToken($code)
{
    echo getAuthTokenCreate($code);
}

function getSheetService()
{
    global $google_credential_path;

    $client = new Google_Client();
    $client->setApplicationName('淘寶匯入到 google sheet');
    $client->setAuthConfig($google_credential_path);
    $client->setScopes(['https://www.googleapis.com/auth/spreadsheets']);
    return new Google_Service_Sheets($client);
}

function writeToGoogleSheets($scroll_id = '')
{
    global $spreadsheet_id;
    global $fetch_spus_list_started_at;
    global $fetch_spus_list_ended_at;

    $start_unixtime = strtotime($fetch_spus_list_started_at);
    $end_unixtime = strtotime($fetch_spus_list_ended_at);

    echo "\n";
    echo "duration: ";
    echo date('Y-m-d H:i:s', $start_unixtime);
    echo " ~ ";
    echo date('Y-m-d H:i:s', $end_unixtime);

    $api_delay_seconds = 1;

    $service = getSheetService();
    $sheet_name = 'Sheet1';

    $spus_count = 0;
    $product_details_query_chunk_size = 100;
    $row_height = 100;

    $page_count = 0;

    do {
        $page_count += 1;

        $start = hrtime(true);
        echo "\nprocess page {$page_count}, scroll_id: {$scroll_id}";

        $json = productSpusGetByScroll($start_unixtime, $end_unixtime, $scroll_id);

        $stop = hrtime(true) - $start;
        echo "\nfetch done, time: " . ($stop / 1000000000);
        echo "\ncurrent memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB';

        // echo "\nspus response:";
        // echo "\n{$json}";

        $response = json_decode($json, true);

        sleep($api_delay_seconds);

        $scroll_id = $response['data']['scroll_id'];

        $product_list = $response['data']['product_list'];
        $surplus_total = $response['data']['surplus_total'];
        $results_total = $response['data']['results_total'];

        echo "\nsurplus_total: {$surplus_total}, results_total: {$results_total}";

        $product_chunks = array_chunk($product_list, $product_details_query_chunk_size);

        foreach ($product_chunks as $chunk_idx => $chunk) {
            $rows = [];

            $item_ids = array_map(
                function ($spu) {
                    return $spu['item_id'];
                },
                $chunk
            );

            $start = hrtime(true);
            echo "\nprocess details chunk idx: {$chunk_idx}: ";
            // echo implode(', ', $item_ids);

            sleep($api_delay_seconds);

            $product_details_json = productDetailsQuery($item_ids);
            $response = json_decode($product_details_json, true);

            // echo "\ndetails response:";
            // echo "\n{$product_details_json}";

            if (!isset($response['data']['goods_info_list'])) {
                echo "\n cannot get goods_info_list, skip";
                continue;
            }

            $spus = $response['data']['goods_info_list'];

            foreach ($spus as $spu) {
                $spus_count += 1;
                $item_id = $spu['item_id'];
                echo "\n({$spus_count}) process item: {$item_id}";
                $attributes = array_map(
                    function ($attribute) {
                        $property = $attribute['pTextMulti']['langAndValueMap']['CN_zh']['value'];
                        $value = $attribute['vTextMulti']['langAndValueMap']['CN_zh']['value'];
                        return "{$property}：{$value}";
                    },
                    $spu['attributes']
                );

                $sku_prices = array_map(
                    function ($sku) {
                        return $sku['price'];
                    },
                    $spu['skus']
                );

                // $skus_string = implode(
                //     ', ',
                //     array_map(
                //         function ($sku) {
                //             return implode(
                //                 '+',
                //                 array_map(
                //                     function ($attribute) {
                //                         return sprintf(
                //                             '%s：%s',
                //                             $attribute['pTextMulti']['langAndValueMap']['CN_zh']['value'],
                //                             $attribute['vTextMulti']['langAndValueMap']['CN_zh']['value']
                //                         );
                //                     },
                //                     $sku['attributes']
                //                 )
                //             );
                //         },
                //         $spu['skus']
                //     )
                // );

                $min_price = min($sku_prices);
                $max_price = max($sku_prices);

                $spu_image = count($spu['images']) > 0
                    ? "=IMAGE(\"" . $spu['images'][0] . "\")"
                    : '';

                $row = [
                    (string) $spu['item_id'], // item_id
                    $spu['cn_title'], // cn_title
                    $spu['tb_category_id'], // category_id
                    $spu['tb_category_path'], // category_path
                    count($spu['skus']), // sku count
                    $min_price,
                    $max_price,
                    $spu['supplier_nick'],
                    $spu_image,
                    join(', ', $attributes),
                    sprintf(
                        '=HYPERLINK("%s", "供銷平台網址")',
                        'https://distributor.taobao.global/apps/product/detail?mpId=' . (string) $spu['item_id']
                    )
                ];

                // $sku_attribute_and_image = array_reduce(
                //     array_slice($spu['skus'], 0, 3),
                //     function ($carry, $sku) {
                //         $attribute_string = implode(
                //             '+',
                //             array_map(
                //                 function ($attribute) {
                //                     return sprintf(
                //                         '%s：%s',
                //                         $attribute['pTextMulti']['langAndValueMap']['CN_zh']['value'],
                //                         $attribute['vTextMulti']['langAndValueMap']['CN_zh']['value']
                //                     );
                //                 },
                //                 $sku['attributes']
                //             )
                //         );
                //         $attribute_image = count($sku['images']) > 0
                //             ? "=IMAGE(\"" . $sku['images'][0] . "\")"
                //             : '';

                //         $carry[] = $attribute_string;
                //         $carry[] = $attribute_image;
                //         return $carry;
                //     },
                //     []
                // );

                // $row = array_merge(
                //     $row,
                //     $sku_attribute_and_image
                // );

                // echo "values: " . join(' | ', $row);

                $rows[] = $row;
            }

            $values = new Google_Service_Sheets_ValueRange(
                [
                    'values' => $rows,
                ]
            );

            $service->spreadsheets_values->append(
                $spreadsheet_id,
                $sheet_name,
                $values,
                [
                    'valueInputOption' => 'USER_ENTERED',
                ]
            );
            echo "\nwrite to google sheets done (" . count($rows) . ")";

            // adjust row height
            $requests = [
                [
                    'updateDimensionProperties' => [
                        'range' => [
                            'sheetId' => $service->spreadsheets->get($spreadsheet_id)->sheets[0]->properties->sheetId,
                            'dimension' => 'ROWS',
                            'startIndex' => max((($page_count - 1) * $product_details_query_chunk_size) - 1, 0) + 1, // + 1 for header
                            'endIndex' => ($page_count * $product_details_query_chunk_size) - 1 + 1, // + 1 for header
                        ],
                        'properties' => [
                            'pixelSize' => $row_height, // 设置行高度为 100px
                        ],
                        'fields' => 'pixelSize',
                    ],
                ],
            ];


            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(['requests' => $requests]);
            $service->spreadsheets->batchUpdate($spreadsheet_id, $batchUpdateRequest);

            echo "\nupdate row height success.";

            $stop = hrtime(true) - $start;
            echo "\nprocess details chunk {$chunk_idx} done, time: " . ($stop / 1000000000);
            echo "\ncurrent memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB';
        }

    } while ($scroll_id);
}

$scroll_id = isset($argv[1]) ? $argv[1] : '';
writeToGoogleSheets($scroll_id);

// generateNewToken('2_500916_0nsfw0PQScwVkdZfyiRCuNfR9');



