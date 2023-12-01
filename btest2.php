<?php
require 'vendor/autoload.php';
require "./IopSdk.php";
require "./Config.php";

date_default_timezone_set('Asia/Taipei');
ini_set('memory_limit', '1024M');

$token = json_decode($token_body, true);
$access_token = $token['access_token'];

function jsonEncode($variable)
{
    return json_encode($variable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function googleDelay()
{
    global $google_api_delay_ms;
    usleep($google_api_delay_ms);
}

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

function getGoogleClient()
{
    global $google_credential_path;

    $client = new Google_Client();
    $client->setApplicationName('淘寶匯入到 google sheet');
    $client->setAuthConfig($google_credential_path);
    $client->setScopes(
        [
            Google_Service_Sheets::SPREADSHEETS,
            Google_Service_Drive::DRIVE,
        ]
    );
    $client->setAccessType('offline');

    return $client;
}

function getSheetService(Google_Client $client)
{
    return new Google_Service_Sheets($client);
}

function getDriveService(Google_Client $client)
{
    return new Google_Service_Drive($client);
}

function fetchAllProductLists($scroll_id = '')
{
    global $product_list_csv_folder_path;
    global $fetch_spus_list_started_at;
    global $fetch_spus_list_ended_at;

    $start_unixtime = strtotime($fetch_spus_list_started_at);
    $end_unixtime = strtotime($fetch_spus_list_ended_at);

    $page_count = 0;
    $api_delay_seconds = 1;

    if (!file_exists($product_list_csv_folder_path)) {
        echo "\nfolder not exists, create: {$product_list_csv_folder_path}";
        mkdir($product_list_csv_folder_path);
    }

    do {
        $page_count += 1;
        $file_path = "{$product_list_csv_folder_path}/{$page_count}.json";

        $start = hrtime(true);
        echo "\nprocess page {$page_count}, scroll_id: {$scroll_id}";

        if (file_exists($file_path)) {
            echo "\ncache exists {$file_path}";
            $json = file_get_contents($file_path);
        } else {
            $json = productSpusGetByScroll($start_unixtime, $end_unixtime, $scroll_id);
        }

        $stop = hrtime(true) - $start;
        echo "\nfetch done, time: " . ($stop / 1000000000);
        echo "\ncurrent memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB';


        if (!file_exists($file_path)) {
            echo "\ncache save {$file_path}";
            file_put_contents($file_path, $json);
        }

        $response = json_decode($json, true);

        sleep($api_delay_seconds);

        $scroll_id = $response['data']['scroll_id'];
        $surplus_total = $response['data']['surplus_total'];
        $results_total = $response['data']['results_total'];

        echo "\nsurplus_total: {$surplus_total}, results_total: {$results_total}";
    } while ($scroll_id);
}

function processAllProductLists($page_count = 1)
{
    global $product_list_csv_folder_path;

    $product_details_query_chunk_size = 100;

    $next_file_path = "{$product_list_csv_folder_path}/{$page_count}.json";

    if (!file_exists($next_file_path)) {
        echo "\nfile not found {$next_file_path}";
        exit;
    }

    do {
        $json = file_get_contents($next_file_path);
        $response = json_decode($json, true);
        $product_list = $response['data']['product_list'];

        $product_chunks = array_chunk($product_list, $product_details_query_chunk_size);

        // next page
        $page_count += 1;
        $next_file_path = "{$product_list_csv_folder_path}/{$page_count}.json";
    } while (file_exists($next_file_path));
}

function logs($msg)
{
    echo "\n[" . date('Y-m-d H:i:s') . "] " . $msg;
}

function getHeaderRow()
{
    return [
        'no',
        'item_id',
        'cn_title',
        'tb_category_id',
        'tb_category_path',
        'skus count',
        'min sku price',
        'max sku price',
        'supplier_nick',
        'first image url',
        'first image',
        'attributes',
        'link'
    ];
}

function extractSheetIdFromWebViewLink($link)
{
    preg_match('/\/d\/([^\/]+)/', $link, $matches);
    return $matches[1];
}

function getSpreadSheetIdFromDriveFile(Google\Service\Drive\DriveFile $file)
{
    return $file->getMimeType() === 'application/vnd.google-apps.shortcut'
        ? $file->getShortcutDetails()->getTargetId()
        : $file->getId();
}

function filePathEscape($str)
{
    return str_replace('/', '|', $str);
}

function getIndexFromRange($range)
{
    // Sheet1!A1:C3
    $range = explode('!', $range); // 'Sheet1' 'A1:C3'
    $range = explode(':', $range[1]); // 'A1', 'C3'
    return array_map(
        function ($str) {
            return preg_replace("/[^\d]/", "", $str);
        },
        $range
    );
}

function writeToGoogleSheets()
{
    $options = getopt(
        '',
        [
            'folder_id::',
            'started_at::',
            'ended_at::',
            'row_height::',
            'scroll_id::',
        ]
    );

    logs("options: " . json_encode($options));

    $folder_id = $options['folder_id'] ?: '';
    $fetch_spus_list_started_at = $options['started_at'] ?: '';
    $fetch_spus_list_ended_at = $options['ended_at'] ?: '';
    $adjust_row_height = isset($options['row_height']);
    $row_height = $options['row_height'] ?: 100;
    $scroll_id = $options['scroll_id'] ?: '';

    $start_unixtime = strtotime($fetch_spus_list_started_at);
    $end_unixtime = strtotime($fetch_spus_list_ended_at);

    logs("duration: ");
    logs(date('Y-m-d H:i:s', $start_unixtime) . " ~ " . date('Y-m-d H:i:s', $end_unixtime));

    $api_delay_seconds = 1;

    $sheet_name = 'Sheet1';

    $spus_count = 0;
    $product_details_query_chunk_size = 100;

    $page_count = 0;

    $google_client = getGoogleClient();
    $drive_service = getDriveService($google_client);
    $sheet_service = getSheetService($google_client);
    $files = getAllFileInDrive($drive_service, $folder_id);

    googleDelay();

    foreach ($files as $file_name => $file_id) {
        logs("found file: {$file_name} {$file_id}");
    }

    do {
        $page_count += 1;

        $start = hrtime(true);
        logs("process page {$page_count}, scroll_id: {$scroll_id}");

        $json = productSpusGetByScroll($start_unixtime, $end_unixtime, $scroll_id);

        $stop = hrtime(true) - $start;
        logs("fetch done, time: " . ($stop / 1000000000));
        logs("current memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB');

        // echo "\nspus response:";
        // echo "\n{$json}";

        $response = json_decode($json, true);

        sleep($api_delay_seconds);

        $scroll_id = $response['data']['scroll_id'];

        $product_list = $response['data']['product_list'];
        $surplus_total = $response['data']['surplus_total'];
        $results_total = $response['data']['results_total'];

        logs("surplus_total: {$surplus_total}, results_total: {$results_total}");

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
            logs("process details chunk idx: {$chunk_idx}: ");
            // echo implode(', ', $item_ids);

            sleep($api_delay_seconds);

            $product_details_json = productDetailsQuery($item_ids);
            $response = json_decode($product_details_json, true);

            // echo "\ndetails response:";
            // echo "\n{$product_details_json}";

            if (!isset($response['data']['goods_info_list'])) {
                logs("cannot get goods_info_list, skip");
                continue;
            }

            $spus = $response['data']['goods_info_list'];

            $categories_rows = [];

            foreach ($spus as $spu) {
                $spus_count += 1;
                $item_id = $spu['item_id'];
                logs("({$spus_count}) process item: {$item_id}");
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
                    ? $spu['images'][0]
                    : '';

                $spu_image_display = $spu_image
                    ? "=IMAGE(\"" . $spu_image . "\")"
                    : '';

                $distributor_link = 'https://distributor.taobao.global/apps/product/detail?mpId=' . (string) $spu['item_id'];

                $categories_rows[$spu['tb_category_path']][] = [
                    '',
                    "'{$spu['item_id']}", // item id
                    // (string) $spu['item_id'], // item_id
                    $spu['cn_title'], // cn_title
                    $spu['tb_category_id'], // category_id
                    $spu['tb_category_path'], // category_path
                    count($spu['skus']), // sku count
                    $min_price,
                    $max_price,
                    $spu['supplier_nick'],
                    $spu_image,
                    $spu_image_display,
                    join(', ', $attributes),
                    // sprintf(
                    //     '=HYPERLINK("%s", "供銷平台網址")',
                    //     $distributor_link
                    // )
                    $distributor_link
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

                // $rows[] = $row;
            }

            foreach ($categories_rows as $category_path => $rows) {
                $include_header = false;

                if (!array_key_exists($category_path, $files)) {
                    logs("{$category_path} not exists in google drive, create");

                    $file_name = $category_path;
                    // $spreadsheet = new Google_Service_Sheets_Spreadsheet(
                    //     [
                    //         'properties' => ['title' => $file_name],
                    //     ]
                    // );

                    $file_metadata = new Google_Service_Drive_DriveFile(
                        [
                            'name' => $file_name,
                            'mimeType' => 'application/vnd.google-apps.spreadsheet',
                            'parents' => [$folder_id], // 指定父資料夾
                        ]
                    );

                    $new_file = $drive_service
                        ->files
                        ->create(
                            $file_metadata,
                            [
                                'fields' => 'id,webViewLink',
                            ]
                        );

                    googleDelay();
                    $webview_link = $new_file->getWebViewLink();

                    logs("new file webview link: {$webview_link}");

                    $spreadsheet_id = extractSheetIdFromWebViewLink(
                        $webview_link
                    );
                    $files[$category_path] = $spreadsheet_id;

                    array_unshift($rows, getHeaderRow());
                    $include_header = true;
                } else {
                    $spreadsheet_id = $files[$category_path];
                }

                logs("append to {$spreadsheet_id}");

                $values = new Google_Service_Sheets_ValueRange(
                    [
                        'values' => $rows,
                    ]
                );

                $response = $sheet_service->spreadsheets_values->append(
                    $spreadsheet_id,
                    $sheet_name,
                    $values,
                    [
                        'valueInputOption' => 'USER_ENTERED',
                    ]
                );
                googleDelay();
                logs("write to google sheets done (" . count($rows) . ")");

                // 取得新增行的結果
                $updates = $response->getUpdates();
                $updated_range = $updates->getUpdatedRange();

                list($start_index, $end_index) = getIndexFromRange($updated_range);

                $start_index -= 1;
                $end_index -= 1;

                if ($include_header) {
                    $start_index += 1;
                }

                logs("update {$start_index} ~ {$end_index} height");

                // $sheet_id = $sheet_service
                //     ->spreadsheets
                //     ->get($spreadsheet_id)
                //     ->sheets[0]
                //     ->properties
                //     ->sheetId;
                $sheet_id = 0;

                // adjust row height
                $requests = [
                    [
                        'updateDimensionProperties' => [
                            'range' => [
                                'sheetId' => $sheet_id,
                                'dimension' => 'ROWS',
                                'startIndex' => $start_index,// + 1 for header
                                'endIndex' => $end_index,
                            ],
                            'properties' => [
                                'pixelSize' => $row_height, // 设置行高度为 100px
                            ],
                            'fields' => 'pixelSize',
                        ],
                    ],
                ];

                $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
                    [
                        'requests' => $requests
                    ]
                );

                if ($adjust_row_height) {
                    $sheet_service->spreadsheets->batchUpdate(
                        $spreadsheet_id,
                        $batchUpdateRequest
                    );

                    googleDelay();
                    logs("update row height success.");
                }
            }

            $stop = hrtime(true) - $start;
            logs("process details chunk {$chunk_idx} done, time: " . ($stop / 1000000000));
            logs("current memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB');
        }

    } while ($scroll_id);
}

function writeToCsv()
{
    $options = getopt(
        '',
        [
            'started_at:',
            'ended_at:',
            'scroll_id:',
            'dest_folder_path:',
            'one_file',
        ]
    );

    $fetch_spus_list_started_at = isset($options['started_at']) ? $options['started_at'] : '';
    $fetch_spus_list_ended_at = isset($options['ended_at']) ? $options['ended_at'] : '';
    $scroll_id = isset($options['scroll_id']) ? $options['scroll_id'] : '';
    $product_list_csv_folder_path = isset($options['dest_folder_path']) ? $options['dest_folder_path'] : '';
    $one_file = isset($options['one_file']);

    logs(
        "options: " . jsonEncode(
            [
                'started_at' => $fetch_spus_list_started_at,
                'ended_at' => $fetch_spus_list_ended_at,
                'scroll_id' => $scroll_id,
                'product_list_csv_folder_path' => $product_list_csv_folder_path,
                'one_file' => $one_file,
            ]
        )
    );

    if (!$fetch_spus_list_started_at || !$fetch_spus_list_ended_at || !$product_list_csv_folder_path) {
        logs('missing required options');
        return false;
    }

    $start_unixtime = strtotime($fetch_spus_list_started_at);
    $end_unixtime = strtotime($fetch_spus_list_ended_at);

    logs("duration: ");
    logs(date('Y-m-d H:i:s', $start_unixtime) . " ~ " . date('Y-m-d H:i:s', $end_unixtime));

    if (!file_exists($product_list_csv_folder_path)) {
        logs("folder not exists, create... [{$product_list_csv_folder_path}]");
        mkdir($product_list_csv_folder_path);
    }

    $api_delay_seconds = 1;

    $spus_count = 0;
    $product_details_query_chunk_size = 100;

    $page_count = 0;

    do {
        $page_count += 1;

        $start = hrtime(true);
        logs("process page {$page_count}, scroll_id: {$scroll_id}");

        $json = productSpusGetByScroll($start_unixtime, $end_unixtime, $scroll_id);

        $stop = hrtime(true) - $start;
        logs("fetch done, time: " . ($stop / 1000000000));
        logs("current memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB');

        // echo "\nspus response:";
        // echo "\n{$json}";

        $response = json_decode($json, true);

        sleep($api_delay_seconds);

        if (!isset($response['data']['product_list'])) {
            logs("product list not found, break");
            break;
        }

        $scroll_id = $response['data']['scroll_id'];

        $product_list = $response['data']['product_list'];
        $surplus_total = $response['data']['surplus_total'];
        $results_total = $response['data']['results_total'];

        logs("surplus_total: {$surplus_total}, results_total: {$results_total}");

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
            logs("process details chunk idx: {$chunk_idx}: ");
            // echo implode(', ', $item_ids);

            sleep($api_delay_seconds);

            $product_details_json = productDetailsQuery($item_ids);
            $response = json_decode($product_details_json, true);

            // echo "\ndetails response:";
            // echo "\n{$product_details_json}";

            if (!isset($response['data']['goods_info_list'])) {
                logs("cannot get goods_info_list, skip");
                continue;
            }

            $spus = $response['data']['goods_info_list'];

            $categories_rows = [];

            foreach ($spus as $spu) {
                $spus_count += 1;
                $item_id = $spu['item_id'];
                logs("({$spus_count}) process item: {$item_id}");
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


                $min_price = min($sku_prices);
                $max_price = max($sku_prices);

                $spu_image = count($spu['images']) > 0
                    ? $spu['images'][0]
                    : '';

                $spu_image_display = $spu_image
                    ? "=IMAGE(\"" . $spu_image . "\")"
                    : '';

                $distributor_link = 'https://distributor.taobao.global/apps/product/detail?mpId=' . (string) $spu['item_id'];

                $categories_rows[$spu['tb_category_path']][] = [
                    '',
                    strval($spu['item_id']), // item id
                    $spu['cn_title'], // cn_title
                    $spu['tb_category_id'], // category_id
                    $spu['tb_category_path'], // category_path
                    count($spu['skus']), // sku count
                    $min_price,
                    $max_price,
                    $spu['supplier_nick'],
                    $spu_image,
                    $spu_image_display,
                    join(', ', $attributes),
                    $distributor_link
                ];
            }

            foreach ($categories_rows as $category_path => $rows) {
                $escape_filename = $one_file ? "all-in-one" : filePathEscape($category_path);
                logs("process {$category_path}, escape: {$escape_filename}");
                $csv_filepath = "{$product_list_csv_folder_path}/" . $escape_filename . ".csv";

                if (!file_exists($csv_filepath)) {
                    logs("first time append, add headers");
                    array_unshift($rows, getHeaderRow());
                }

                $fp = fopen($csv_filepath, 'a+');

                foreach ($rows as $row) {
                    fputcsv($fp, $row);
                }

                fclose($fp);

                logs("append to {$csv_filepath} (" . count($rows) . ")");
            }

            $stop = hrtime(true) - $start;
            logs("process details chunk {$chunk_idx} done, time: " . ($stop / 1000000000));
            logs("current memory useage: " . (memory_get_usage() / 1024 / 1024) . ' MB');
        }

    } while ($scroll_id);
}

function getAllFileInDrive(Google_Service_Drive $drive_service, $folder_id)
{
    logs("fetch files from {$folder_id}");
    googleDelay();
    $files = $drive_service->files->listFiles(
        [
            'q' => "'{$folder_id}' in parents and trashed=false",
            'orderBy' => 'name',
        ]
    );

    return array_reduce(
        $files->getFiles(),
        function ($carry, $file) use ($drive_service) {
            logs("{$file->getName()} {$file->getId()} {$file->getWebViewLink()}");
            googleDelay();
            $full_file = $drive_service->files->get(
                $file->getId(),
                [
                    'fields' => '*'
                ]
            );
            logs("{$full_file->getName()} {$full_file->getId()} {$full_file->getWebViewLink()}");
            var_dump($full_file);
            $carry[$file->name] = $file->getId();
            return $carry;
        },
        [],
    );
}

function getAllFilesFromDriveFolderId(Google_Service_Drive $drive_service, $folder_id)
{
    logs("fetch files from {$folder_id}");
    googleDelay();
    $files = $drive_service->files->listFiles(
        [
            'q' => "'{$folder_id}' in parents and trashed=false",
            'orderBy' => 'name',
        ]
    );

    $tmp = [];
    foreach ($files as $file) {
        $tmp[] = $file;
    }

    return array_map(
        function ($file) use ($drive_service) {
            logs("fetch file detail: {$file->getName()} {$file->getId()}");
            googleDelay();
            return $drive_service->files->get(
                $file->getId(),
                [
                    'fields' => '*'
                ]
            );
        },
        $tmp
    );
}

function uploadCsvFilesToGoogleSheet()
{
    $options = getopt(
        '',
        [
            'source_folder_path:',
            'drive_folder_id:',
        ]
    );

    $source_folder_path = isset($options['source_folder_path']) ? $options['source_folder_path'] : '';
    $drive_folder_id = isset($options['drive_folder_id']) ? $options['drive_folder_id'] : '';

    logs(
        "options: " . jsonEncode(
            [
                'source_folder_path' => $source_folder_path,
                'drive_folder_id' => $drive_folder_id,
            ]
        )
    );

    if (!$source_folder_path || !$drive_folder_id) {
        logs("missing required options, break");
        return false;
    }

    $google_client = getGoogleClient();
    $drive_service = getDriveService($google_client);
    $sheet_service = getSheetService($google_client);

    // get all csv files
    $realpath = realpath($source_folder_path);

    $dir = opendir($realpath);
    $file_count = 0;
    while (($file = readdir($dir)) !== false) {
        if (pathinfo($file, PATHINFO_EXTENSION) !== 'csv') {
            continue;
        }

        $filename = pathinfo($file, PATHINFO_FILENAME);

        $full_path = $realpath . '/' . $file;
        $size = filesize($full_path);

        if ($size === 0) {
            continue;
        }

        $file_count += 1;

        logs("process no.{$file_count} file [{}$filename]..");

        $fp = fopen($full_path, 'r');
        $rows = [];
        while (($row = fgetcsv($fp)) !== false) {
            $rows[] = $row;
        }
        fclose($fp);

        $file_metadata = new Google_Service_Drive_DriveFile(
            [
                'name' => str_replace('|', '/', $filename),
                'mimeType' => 'application/vnd.google-apps.spreadsheet',
                'parents' => [$drive_folder_id],
            ]
        );

        googleDelay();
        $new_file = $drive_service
            ->files
            ->create(
                $file_metadata,
                [
                    'fields' => 'id,webViewLink',
                ]
            );

        $webview_link = $new_file->getWebViewLink();
        logs("new file webview link: {$webview_link}");

        $spreadsheet_id = extractSheetIdFromWebViewLink(
            $webview_link
        );

        $values = new Google_Service_Sheets_ValueRange(
            [
                'values' => $rows,
            ]
        );

        googleDelay();
        $sheet_service->spreadsheets_values->append(
            $spreadsheet_id,
            'Sheet1',
            $values,
            [
                'valueInputOption' => 'USER_ENTERED',
            ]
        );
        logs("write to google sheets done (" . count($rows) . ")");

        $sheet_id = 0;

        $start_index = 1;
        $end_index = count($rows);
        $row_height = 100;

        // adjust row height
        $requests = [
            [
                'updateDimensionProperties' => [
                    'range' => [
                        'sheetId' => $sheet_id,
                        'dimension' => 'ROWS',
                        'startIndex' => $start_index,// + 1 for header
                        'endIndex' => $end_index,
                    ],
                    'properties' => [
                        'pixelSize' => $row_height, // 设置行高度为 100px
                    ],
                    'fields' => 'pixelSize',
                ],
            ],
        ];

        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
            [
                'requests' => $requests
            ]
        );

        googleDelay();
        $sheet_service->spreadsheets->batchUpdate(
            $spreadsheet_id,
            $batchUpdateRequest
        );

        logs("update row height success. {$spreadsheet_id}");
    }

    closedir($dir);
}

function fetchFromGoogleSpreadsheetId($spread_sheet_id)
{
    $google_client = getGoogleClient();
    $sheet_service = getSheetService($google_client);

    $spreadsheet = $sheet_service->spreadsheets->get($spread_sheet_id);
    $sheets = $spreadsheet->getSheets();

    googleDelay();

    $letters = range('A', 'Z');

    $item_ids = [];
    $count = 0;

    foreach ($sheets as $sheet_index => $sheet) {
        $sheet_title = $sheet->properties->title;

        // 設定範圍，僅包含第一行的資料
        $range = "'{$sheet_title}'" . '!1:1';
        $params = [
            'valueRenderOption' => 'UNFORMATTED_VALUE',  // 這裡使用 'UNFORMATTED_VALUE' 作為例子，你可以根據需求選擇其他值
        ];

        logs("process sheet: {$sheet_title} range: [{$range}]");

        googleDelay();
        $response = $sheet_service->spreadsheets_values->get(
            $spread_sheet_id,
            $range,
            $params
        );
        $values = $response->getValues();

        if (is_null($values)) {
            logs("header row is empty");
            continue;
        }
        $header_row = $values[0];

        logs("第一行：" . implode(', ', $header_row));

        $checkbox_column_index = array_search('多匡列POOL', $header_row);
        if ($checkbox_column_index === false) {
            // 試試看 "大POOL"
            $checkbox_column_index = array_search('大POOL', $header_row);
        }

        $item_id_column_index = array_search('item_id', $header_row);

        if ($checkbox_column_index === false) {
            logs("sheet[{$sheet_index}]: {$sheet_title} 找不到 勾選POOL欄位");
            continue;
        }

        if ($item_id_column_index === false) {
            logs("sheet[{$sheet_index}]: {$sheet_title} 找不到 item_id");
            continue;
        }

        $checkbox_column = $letters[$checkbox_column_index];
        $item_id_column = $letters[$item_id_column_index];

        logs("勾選POOL 欄位：{$checkbox_column}, item_id 欄位：{$item_id_column}");

        // 設定範圍，這裡使用整個工作表的 A 到 B 欄
        $range = "'{$sheet_title}'" . "!{$checkbox_column}:{$item_id_column}";
        logs("目標範圍 {$range}");

        googleDelay();
        $response = $sheet_service->spreadsheets_values->get(
            $spread_sheet_id,
            $range
        );
        $values = $response->getValues();

        foreach ($values as $value) {
            if ($value[$checkbox_column_index] !== 'TRUE') {
                continue;
            }

            $before_item_id = trim($value[$item_id_column_index]);

            if (!$before_item_id) {
                continue;
            }

            // number format will break item_id which is long
            // $item_id = number_format($before_item_id, 0, '', '');
            $item_id = $before_item_id;

            $count += 1;
            $item_ids[] = $item_id;
            logs("found ({$count}) [{$item_id}] ({$before_item_id})");
        }
    }

    return $item_ids;
}

function fixGoogleSheetItemId($spread_sheet_id)
{
    $google_client = getGoogleClient();
    $sheet_service = getSheetService($google_client);

    googleDelay();
    $spreadsheet = $sheet_service->spreadsheets->get($spread_sheet_id);
    $sheets = $spreadsheet->getSheets();

    foreach ($sheets as $sheet) {
        $sheet_name = $sheet->properties->title;
        $sheet_id = $sheet->properties->sheetId;

        $range = "'{$sheet_name}'" . '!A1:ZZ1';

        logs("process sheet: {$sheet_name} {$sheet_id} range: [{$range}]");

        googleDelay();
        $response = $sheet_service->spreadsheets_values->get(
            $spread_sheet_id,
            $range
        );
        $values = $response->getValues();

        if (is_null($values)) {
            logs("沒有 header, pass");
            continue;
        }

        $header_row = $values[0];
        $item_id_column_index = array_search('item_id', $header_row);
        $original_item_id_column_index = array_search('original_item_id', $header_row);

        if ($original_item_id_column_index !== false) {
            logs('original_item_id found, pass');
            continue;
        }

        if ($item_id_column_index === false) {
            logs('item_id column not found, pass');
            continue;
        }

        $new_item_id_column_index = $item_id_column_index + 1;

        $insert_row_request = new Google_Service_Sheets_Request(
            [
                'insertDimension' => [
                    'range' => [
                        'sheetId' => $sheet_id,
                        'dimension' => 'COLUMNS',
                        'startIndex' => $item_id_column_index + 1,
                        'endIndex' => $item_id_column_index + 2,
                    ],
                    'inheritFromBefore' => false,
                ]
            ]
        );

        googleDelay();
        $batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(
            [
                'requests' => [$insert_row_request],
            ]
        );

        $sheet_service->spreadsheets->batchUpdate(
            $spread_sheet_id,
            $batch_update_request
        );

        logs('insert new row');

        $char_range = range('A', 'Z');
        $update_range = sprintf(
            '\'%s\'!%s1:%s2',
            $sheet_name,
            $char_range[$item_id_column_index],
            $char_range[$new_item_id_column_index]
        );

        $update_values = [
            [
                'original_item_id',
                'item_id'
            ],
            [
                Google_Model::NULL_VALUE,
                sprintf(
                    '=ARRAYFORMULA(""&%s2:%s)',
                    $char_range[$item_id_column_index],
                    $char_range[$item_id_column_index]
                )
            ]
        ];

        logs("update range: {$update_range}");

        googleDelay();
        $result = $sheet_service
            ->spreadsheets_values
            ->update(
                $spread_sheet_id,
                $update_range,
                new Google_Service_Sheets_ValueRange(
                    [
                        'range' => $update_range,
                        'majorDimension' => 'ROWS',
                        'values' => $update_values,
                    ]
                ),
                [
                    'valueInputOption' => 'USER_ENTERED',
                ]
            );
        logs("insert data, updated cells: " . $result->getUpdatedCells());
    }

}

function fetchItemIdFromDriveFolder()
{
    $options = getopt(
        '',
        [
            'dest_file_path:',
            'drive_folder_id:',
        ]
    );

    $google_client = getGoogleClient();
    $drive_service = getDriveService($google_client);

    $dest_file_path = isset($options['dest_file_path']) ? $options['dest_file_path'] : '';
    $drive_folder_id = isset($options['drive_folder_id']) ? $options['drive_folder_id'] : '';

    logs(
        "options: " . jsonEncode(
            [
                'dest_file_path' => $dest_file_path,
                'drive_folder_id' => $drive_folder_id,
            ]
        )
    );

    if (!$dest_file_path || !$drive_folder_id) {
        logs("missing required options, break");
        return false;
    }

    $fp = fopen($dest_file_path, 'a');

    $files = getAllFilesFromDriveFolderId($drive_service, $drive_folder_id);

    foreach ($files as $file) {
        if ($file->getMimeType() === 'application/vnd.google-apps.folder') {
            logs($file->getName() . " not an spreadsheets, skip.");
            continue;
        }

        if ($file->getMimeType() === 'application/vnd.google-apps.shortcut'
            && $file->getShortcutDetails()->getTargetMimeType() !== 'application/vnd.google-apps.spreadsheet'
        ) {
            logs($file->getName() . " not an spreadsheets, skip.");
            continue;
        }

        $spread_sheet_id = getSpreadSheetIdFromDriveFile($file);
        logs("{$file->getName()} spread sheet id: {$spread_sheet_id}");

        $item_ids = fetchFromGoogleSpreadsheetId($spread_sheet_id);

        foreach ($item_ids as $item_id) {
            fputcsv($fp, [(string) $item_id]);
        }
    }

    fclose($fp);
}

function fixItemIdsSheetsDriveFolder()
{
    $options = getopt(
        '',
        [
            'folder_id:',
        ]
    );

    $folder_id = isset($options['folder_id']) ? $options['folder_id'] : '';

    if (!$folder_id) {
        logs("missing required options, break");
        return false;
    }

    $google_client = getGoogleClient();
    $drive_service = getDriveService($google_client);

    $files = getAllFilesFromDriveFolderId($drive_service, $folder_id);
    foreach ($files as $file) {
        $spread_sheet_id = getSpreadSheetIdFromDriveFile($file);
        logs("{$file->getName()} spread sheet id: {$spread_sheet_id}");

        fixGoogleSheetItemId($spread_sheet_id);
    }
}

function goFixGoogleSheetItemId()
{
    $options = getopt(
        '',
        [
            'spreadsheet_id:',
        ]
    );

    $spreadsheet_id = isset($options['spreadsheet_id']) ? $options['spreadsheet_id'] : '';

    if (!$spreadsheet_id) {
        logs("missing required options, break");
        return false;
    }

    fixGoogleSheetItemId($spreadsheet_id);
}

function updateProductStatus()
{
    $taobao_api_delay_seconds = 5;
    $items_chunk_count = 100;
    $options = getopt(
        '',
        [
            'spreadsheet_id:',
            'start_row_index:',
        ]
    );

    $spreadsheet_id = isset($options['spreadsheet_id']) ? $options['spreadsheet_id'] : '';
    $start_row_index = isset($options['start_row_index']) ? intval($options['start_row_index']) : 2;

    if (!$spreadsheet_id) {
        logs("missing required options, break");
        return false;
    }

    $google_client = getGoogleClient();
    $sheet_service = getSheetService($google_client);

    $spreadsheet = $sheet_service->spreadsheets->get($spreadsheet_id);
    $sheets = $spreadsheet->getSheets();

    foreach ($sheets as $sheet) {
        $sheet_name = $sheet->properties->title;
        $sheet_id = $sheet->properties->sheetId;

        $header_range = "'{$sheet_name}'" . '!A1:ZZ1';
        logs("process sheet: {$sheet_name} {$sheet_id} get header, range: [{$header_range}]");

        googleDelay();
        $response = $sheet_service->spreadsheets_values->get(
            $spreadsheet_id,
            $header_range
        );
        $values = $response->getValues();

        if (is_null($values)) {
            logs("沒有 header, pass");
            continue;
        }

        $header_row = $values[0];
        $item_id_column_index = array_search('item_id', $header_row);
        $taobao_status_column_index = array_search('taobao_status', $header_row);
        $taobao_status_updated_at_column_index = array_search('taobao_status_updated_at', $header_row);

        if ($item_id_column_index === false) {
            logs('item_id not found, pass');
            continue;
        }

        if ($taobao_status_column_index === false) {
            logs('taobao_status column not found, pass');
            continue;
        }

        if ($taobao_status_updated_at_column_index === false) {
            logs('taobao_status column not found, pass');
            continue;
        }

        $letters = range('A', 'Z');
        $item_id_column_letter = $letters[$item_id_column_index];
        $taobao_status_column_letter = $letters[$taobao_status_column_index];
        $taobao_status_updated_at_column_letter = $letters[$taobao_status_updated_at_column_index];

        $item_ids_range = "'{$sheet_name}'" . '!' . $item_id_column_letter . $start_row_index . ':' . $item_id_column_letter;

        $response = $sheet_service->spreadsheets_values->get(
            $spreadsheet_id,
            $item_ids_range
        );
        $values = $response->getValues();

        $rows = array_map(
            function ($row) {
                return isset($row[0])
                    ? $row[0]
                    : null;
            },
            $values
        );

        $row_chunks = array_chunk($rows, $items_chunk_count);

        foreach ($row_chunks as $chunk_idx => $item_ids) {
            $temp = [];
            foreach ($item_ids as $item_id) {
                if (is_null($item_id)) {
                    continue;
                }
                $temp[$item_id] = 'MISSING';
            }

            sleep($taobao_api_delay_seconds);
            $product_details_json = productDetailsQuery($item_ids);
            $response = json_decode($product_details_json, true);

            $updated_at = '="' . date('Y-m-d H:i:s') . '"';

            $good_info_list = isset($response['data']['goods_info_list'])
                ? $response['data']['goods_info_list']
                : [];

            foreach ($good_info_list as $spu) {
                $temp[$spu['item_id']] = $spu['status'];
            }

            $update_rows = [];
            foreach ($item_ids as $item_id) {
                $status = is_null($item_id) ? '' : $temp[$item_id];
                logs($item_id . ' => ' . $status);
                $update_rows[] = [
                    $status, $updated_at,
                ];
            }

            $values = new Google_Service_Sheets_ValueRange(
                [
                    'values' => $update_rows,
                ]
            );

            $chunk_start_row_index = ($chunk_idx * $items_chunk_count) + $start_row_index;
            $chunk_end_row_index = $chunk_start_row_index + ($items_chunk_count - 1);
            $updated_range = "'{$sheet_name}'" . '!'
                . $taobao_status_column_letter . $chunk_start_row_index
                . ':'
                . $taobao_status_updated_at_column_letter . $chunk_end_row_index;

            googleDelay();
            $response = $sheet_service->spreadsheets_values->update(
                $spreadsheet_id,
                $updated_range,
                $values,
                [
                    'valueInputOption' => 'USER_ENTERED',
                ]
            );

            logs("update {$updated_range} done");
        }
    }
}

$auth_code = isset($argv[1]) ? $argv[1] : '';

$scroll_id = isset($argv[1]) ? $argv[1] : '';

$source_folder_path = isset($argv[1]) ? $argv[1] : '';
$drive_folder_id = isset($argv[2]) ? $argv[2] : '';

$spread_sheet_id = isset($argv[1]) ? $argv[1] : '';

$list_folder_id = isset($argv[1]) ? $argv[1] : '';
$csv_filepath = isset($argv[2]) ? $argv[2] : '';

$options = getopt(
    '',
    [
        'name:'
    ]
);

$function_name = $options['name'];
if (!$function_name && function_exists($function_name)) {
    die('need a function name or function not exists.');
}

logs("execute {$function_name}");
call_user_func($function_name);


// generateNewToken($auth_code);
// writeToGoogleSheets($scroll_id);
// writeToCsv($scroll_id);
// fetchAllProductLists($scroll_id);
// uploadCsvFilesToGoogleSheet($source_folder_path, $drive_folder_id);
// fetchFromGoogleSpreadsheetId($spread_sheet_id);
// fetchItemIdFromDriveFolder($list_folder_id, $csv_filepath);
// fixGoogleSheetItemId($spread_sheet_id);
// fixItemIdsSheetsDriveFolder($list_folder_id);


// $strs = [
//     '节庆用品/礼品>装扮用品>挂饰/生肖挂饰',
//     '节庆用品\/礼品>装扮用品>挂饰\/生肖挂饰',
//     '节庆用品/礼品>节日用品>圣诞袜',
//     '节庆用品\/礼品>节日用品>圣诞袜',
//     '收纳整理>家庭收纳用具>收纳盒>内衣收纳盒',
//     '居家布艺>地垫(新)>厨房地垫',
// ];

// foreach ($strs as $idx => $str) {
//     echo "\n=== {$idx} === \n";
//     echo "\n=== {$str} === \n";
//     $product_list_csv_folder_path = '/Users/justinhsu/tmp/product_list';
//     $escape_filename = filePathEscape($str);
//     $str = str_replace('/', '|', $str);
//     $csv_filepath = "{$product_list_csv_folder_path}/" . $str . ".csv";
//     $fp = fopen($csv_filepath, 'a+');
//     fclose($fp);
//     echo "\n\n";
// }


