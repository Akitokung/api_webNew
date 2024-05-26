<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');
  
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // token สำหรับ decode jwt
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $spc_memcode = $data['mem_code'];

        $sql = "
          SELECT 
            * 
          FROM 
            `shopping_orderHead` AS `a` 
            LEFT JOIN `shopping_order` AS `b` ON `a`.`soh_runing`=`b`.`spo_runing`
            LEFT JOIN `product` AS `c` ON `b`.`spo_procode`=`c`.`pro_code`
          WHERE 
            `a`.`soh_id`='".$_GET['id']."'
        ";
        
        $query = mysqli_query($Con_wang,$sql);      $num_rows = mysqli_num_rows($query);
        if (!$query) {http_response_code(404);}
        $site = 'https://www.wangpharma.com/';

        if ($num_rows<=0) {
          http_response_code(401);
          echo 'ไม่ม่รายการสินค้า ในรถเข็น';
        }
        else {
          $result = mysqli_fetch_array($query);
          $mem = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              * 
            FROM 
              `member` AS `a` 
              LEFT JOIN `member_phone` AS `b` ON `a`.`mem_code`=`b`.`mn_memcode`
            WHERE 
              `a`.`mem_code`='".$result['soh_memcode']."'
            LIMIT 
               1
          "));
          $lg = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT * FROM `logintsc_shipping_type` WHERE `lst_id`='".$result['soh_shiptype']."'
          "));

          $mem_address = ($mem['mem_address']!='')? 'เลขที่ '.trim($mem['mem_address']).' ':null;
          $mem_village = ($mem['mem_village']!='')? 'หมู่ที่ '.trim($mem['mem_village']).' ':null;
          $mem_alley = ($mem['mem_alley']!='')? 'ซอย'.trim($mem['mem_alley']).' ':null;
          $mem_road = ($mem['mem_road']!='')? 'ถนน'.trim($mem['mem_road']).' ':null;
          $mem_tumbon = ($mem['mem_tumbon']!='')? 'ตำบล'.trim($mem['mem_tumbon']).' ':null;
          $mem_amphur = ($mem['mem_amphur']!='')? 'อำเภอ'.trim($mem['mem_amphur']).' ':null;
          $mem_province = ($mem['mem_province']!='')? 'จังหวัด'.trim($mem['mem_province']).' ':null;
          $mem_post = ($mem['mem_post']!='')? 'รหัสไปรษณีย์ '.trim($mem['mem_post']).' ':null;
          $country = 'ประเทศไทย';

          $address = $mem_address.$mem_village.$mem_alley.$mem_road.$mem_tumbon.$mem_amphur.$mem_province.$mem_post.$country;

          $json = array(
            'user_info' => array(),
            'cart' => array(),

            'discount' => 0,
            '_id' => (int)$result['soh_id'],
            'shippingOption' => $lg['lst_type'],
            'paymentMethod' => 'Cash', 
            'status' => 'Delivered',
            'subTotal' => (int)number_format($result['soh_listsale']+$result['soh_listfree'],2,'.',''),
            'shippingCost' => (float)number_format($lg['lst_price'],2,'.',''),
            'total' => (float)number_format($result['soh_sumprice'],2,'.',''),
            'user' => $mem['mem_code'],
            'createdAt' => $result['soh_datetime'],
            'updatedAt' => $result['soh_printtime'],
            'invoice' => $result['soh_runing'],
            '__v' => 0,
          );

          $user_info = array(
            'name' => $mem['mem_name'],
            'contact' => $mem['mn_phoneshop'],
            'email' => $mem['mn_emailshop'],
            'address' => $address,
            'country' => $country,
            'city' => ($mem['mem_province']!='')? trim($mem['mem_province']):null,
            'zipCode' => ($mem['mem_post']!='')? trim($mem['mem_post']):null, 
          );

          $json['user_info'] = $user_info;

          $ic = "
            SELECT 
              *,
              `b`.`pro_priceTag` AS `p_tag` ,
              `b`.`pro_priceA` AS `p_a` , 
              `b`.`pro_priceB` AS `p_b` , 
              `b`.`pro_priceC` AS `p_c` 
            FROM 
              `shopping_order` AS `a` 
              LEFT JOIN `product` AS `b` ON `a`.`spo_procode`=`b`.`pro_code`
              LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
            WHERE 
              `a`.`spo_runing`='".$result['soh_runing']."'
          ";

          $qic = mysqli_query($Con_wang,$ic);  $num_rows = mysqli_num_rows($qic);
          while ($ric = mysqli_fetch_array($qic)) {
            if ($mem['mem_price']=='A') {$price = number_format($ric['p_a'],2,'.','');}
            else if ($mem['mem_price']=='B') {$price = number_format($ric['p_b'],2,'.','');}
            else if ($mem['mem_price']=='C') {$price = number_format($ric['p_c'],2,'.','');}
            $originalPrice = ($ric['p_tag']!=0)? number_format($ric['p_tag'],2,'.',''):number_format($ric['p_c'],2,'.','');
            $discount = $originalPrice-$price;

            $pro_imgm = str_replace('../',$site,$ric['pro_img']);

            $tag_x = array();
            if ($ric['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
            if ($ric['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
            if ($ric['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
            if ($ric['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

            $tag_ms = '[';
            foreach ($tag_x as $key => $value) {
              $tag_ms .= '"'.$value;
              if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
              if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
            }
            $tag_ms .= ']';

            $status = ($ric['pro_instock']>=$ric['pro_limitA'])? 'show':'hide';
            $status = ($ric['pro_show']==0)? 'show':'hide';

            $sku = null;      $unit = null;      $barcode = null;
            if ($ric['spo_unit']==1) {
              $sku = $ric['pro_unit1'];
              $unit = $ric['pro_unit1'];
              $barcode = $ric['pro_barcode1'];

              $pro_img = str_replace('../',$site,$ric['pro_imgU1']);
            }
            else if ($ric['spo_unit']==2) {
              $sku = $ric['pro_unit2'];
              $unit = $ric['pro_unit1'];
              $barcode = $ric['pro_barcode2'];

              $pro_img = str_replace('../',$site,$ric['pro_imgU2']);
            }
            else if ($ric['spo_unit']==3) {
              $sku = $ric['pro_unit3'];
              $unit = $ric['pro_unit1'];
              $barcode = $ric['pro_barcode3'];

              $pro_img = str_replace('../',$site,$ric['pro_imgU3']);
            }

            $pro_img = ($pro_img=='')? $pro_imgm:$pro_img;

            $description_th = str_replace($bad,'',strip_tags($ric['pro_details']));
            $title_th = ($ric['pro_nameMain']!='')? $ric['pro_nameMain']:$ric['pro_nameTH'];
            $title_th = ($title_th!='')? $title_th:$ric['pro_name'];
            $slug = ($ric['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

            $stock = (int)$ric['pro_instock'];
            $stock = ($stock>1)? 999:0;

            $cart = array(
              'prices' => array(
                'price' => (float)$price,
                'originalPrice' => (float)$originalPrice,
                'discount' => (float)$discount,
              ),
              'image' => $pro_img,
              'tag' => array(),
              'status' => $status , 
              '_id' => $ric['pro_id'],
              'productId' => $ric['pro_id'],
              'productCode' => $ric['pro_code'],
              'sku' => $sku,
              'barcode' => $barcode,
              'title' => $title_th,
              'slug' => $slug,
              'category' => array(
                '_id' => $ric['pro_mode'], 
                'name' => array(
                  'th' => $ric['pd_name_TH'], 
                  'en' => $ric['pd_name_Eng'], 
                ),
              ),
              'stock' => $stock,
              'isCombination' => true,
              'createdAt' => $ric['spo_Cdatetime'],
              'updatedAt' => $result['soh_printtime'],
              'sales' => 0 , 
              '__v' => 0 ,
              'id' => $ric['spo_id'],
              'variant' => array(
                'register' => $ric['pro_drugregister'],
                'view' => (int)$ric['pro_view'],
                'rating' => (float)number_format($ric['pro_rating'],2,'.',''),

                'originalPrice' => (float)$originalPrice,
                'price' => (float)$price,
                'quantity' => $stock,
                'discount' => (float)$discount,
                'productId' => $ric['pro_id'],
                'barcode' => $barcode,
                'sku' => $sku,
                'unit' => $unit,
                'image' => $pro_img,
              ),
              'price' => (int)number_format($ric['spo_ppu'],0,'.',''),
              'originalPrice' => (float)$originalPrice,
              'quantity' => (int)number_format($ric['spo_amount'],0,'.',''),
              'itemTotal' => (float)number_format($ric['spo_total'],0,'.',''),
            );
            $cart['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;
            array_push($json['cart'],$cart);
          }

          echo json_encode($json);
        }
      }
      else {
        http_response_code(404);
        echo 'error';
      }
    }
    else {
      // 404 = Not Found
      http_response_code(404);
    }
  }
?>