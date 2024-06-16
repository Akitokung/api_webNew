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

        $mem = mysqli_fetch_array(mysqli_query($Con_wang,"
          SELECT 
            * 
          FROM 
            `member` AS `a` 
            LEFT JOIN `member_phone` AS `b` ON `a`.`mem_code`=`b`.`mn_memcode`
          WHERE 
            `a`.`mem_code`='".$spc_memcode."'
          LIMIT 
            1
        "));

        $json = array(
          'items' => array(),
        );

        $sql = "
          SELECT 
            *,
            `b`.`pro_priceTag` AS `p_tag` ,
            `b`.`pro_priceA` AS `p_a` , 
            `b`.`pro_priceB` AS `p_b` , 
            `b`.`pro_priceC` AS `p_c` 
          FROM 
            `shopping_cart` AS `a` 
            LEFT JOIN `product` AS `b` ON `a`.`spc_procode`=`b`.`pro_code`
            LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
          WHERE 
            `a`.`spc_memcode`='".$spc_memcode."' AND 
            `a`.`spc_amount`!=0
        ";
        $query = mysqli_query($Con_wang,$sql);      $num_rows = mysqli_num_rows($query);

        if (!$query) {http_response_code(404);}
        $site = 'https://www.wangpharma.com/';

        if ($num_rows<=0) {http_response_code(401);echo 'ไม่ม่รายการสินค้า ในรถเข็น';}
        else {
          $cartTotal = 0;
          while ($result = mysqli_fetch_array($query)) {
            if ($mem['mem_price']=='A') {$price = number_format($result['p_a'],2,'.','');}
            else if ($mem['mem_price']=='B') {$price = number_format($result['p_b'],2,'.','');}
            else if ($mem['mem_price']=='C') {$price = number_format($result['p_c'],2,'.','');}
            $originalPrice = ($result['p_tag']!=0)? number_format($result['p_tag'],2,'.',''):number_format($result['p_c'],2,'.','');
            $discount = $originalPrice-$price;

            $pro_imgm = str_replace('../',$site,$result['pro_img']);
            $sku = null;      $unit = null;      $barcode = null;
            if ($result['spc_unit']==1) {
              $no = 0;
              $sku = null;
              $unit = $result['pro_unit1'];
              $barcode = $result['pro_barcode1'];
              $endt = $result['pro_unit1'];
              $pro_img = str_replace('../',$site,$result['pro_imgU1']);
            }
            else if ($result['spc_unit']==2) {
              $no = 1;
              $sku = null;
              $unit = $result['pro_unit2'];
              $barcode = $result['pro_barcode2'];
              $endt = $result['pro_unit2'];
              $pro_img = str_replace('../',$site,$result['pro_imgU2']);
            }
            else if ($result['spc_unit']==3) {
              $no = 2;
              $sku = null;
              $unit = $result['pro_unit3'];
              $barcode = $result['pro_barcode3'];
              $endt = $result['pro_unit3'];
              $pro_img = str_replace('../',$site,$result['pro_imgU3']);
            }

            $pro_img = ($pro_img=='')? $pro_imgm:$pro_img;

            $tag_x = array();
            if ($result['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
            if ($result['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
            if ($result['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
            if ($result['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

            $tag_ms = '[';
            foreach ($tag_x as $key => $value) {
              $tag_ms .= '"'.$value;
              if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
              if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
            }
            $tag_ms .= ']';

            $status = ($result['pro_instock']>=$result['pro_limitA'])? 'show':'hide';
            $status = ($result['pro_show']==0)? 'show':'hide';


            $description_th = str_replace($bad,'',strip_tags($result['pro_details']));
            $title_th = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
            $title_th = ($title_th!='')? $title_th:$result['pro_name'];
            $slug = ($result['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

            $stock = (int)$result['pro_instock'];
            $stock = ($stock>1)? 999:0;

            $items = array(
              'prices' => array(
                'price' => (float)$price,
                'originalPrice' => (float)$originalPrice,
                'discount' => (float)$discount,
              ),
              'image' => $pro_imgm,
              'tag' => array(),
              'status' => $status , 
              '_id' => $result['pro_id'],
              'productId' => $result['pro_id'],
              'productCode' => $result['pro_code'],
              'sku' => $sku,
              'barcode' => $barcode,
              'title' => $title_th.'-'.$endt,
              'slug' => $slug,
              'category' => array(
                '_id' => $result['pro_mode'], 
                'name' => array(
                  'th' => $result['pd_name_TH'], 
                  'en' => $result['pd_name_Eng'], 
                ),
              ),
              'stock' => $stock,
              'isCombination' => true,
              'createdAt' => $result['spc_datetime'],
              'updatedAt' => $result['spc_datetime'],
              'sales' => 0 , 
              '__v' => 0 ,
              'id' => $result['spc_id'],
              'variant' => array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)$originalPrice,
                'price' => (float)$price,
                'quantity' => $stock,
                'discount' => (float)$discount,
                'productId' => $result['pro_id'].'-'.$no,
                'barcode' => $barcode,
                'sku' => $sku,
                'unit' => $unit,
                'image' => $pro_img,
              ),
              'price' => (int)number_format($result['spc_ppu'],0,'.',''),
              'originalPrice' => (float)$originalPrice,
              'quantity' => (int)number_format($result['spc_amount'],0,'.',''),
              'itemTotal' => (float)number_format($result['spc_total'],0,'.',''),
            );

            $cartTotal += $result['spc_total'];
            array_push($json['items'],$items);
          }

          $json['isEmpty'] = false;
          $json['totalItems'] = $num_rows;
          $json['totalUniqueItems'] = $num_rows;
          $json['cartTotal'] = $cartTotal;
          $json['metadata'] = (object)array();
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