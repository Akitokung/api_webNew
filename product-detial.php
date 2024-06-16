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
        //echo $data['mem_code'];

        $mem = mysqli_fetch_array(mysqli_query($Con_wang,"SELECT * FROM `member` WHERE `mem_code`='".$data['mem_code']."'"));

        $sql = "
          SELECT 
            *,
            `a`.`pro_priceTag` AS `p_tag` ,
            `a`.`pro_priceA` AS `p_a` , 
            `a`.`pro_priceB` AS `p_b` , 
            `a`.`pro_priceC` AS `p_c` 
          FROM 
            `product` AS `a` 
            LEFT JOIN `product_pharma` AS `b` ON `a`.`pro_code`=`b`.`pp_procode`
            LEFT JOIN `supplier` AS `c` ON `a`.`pro_supplier`=`c`.`sp_code`
            LEFT JOIN `product_drugmode` AS `d` ON `a`.`pro_mode`=`d`.`pd_code`
          WHERE 
            `a`.`pro_show`!='1' AND 
            `a`.`pro_code`='".$_GET['pcode']."'
        ";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        // Akitokung
        $site = 'https://www.wangpharma.com/';
        $result = mysqli_fetch_array($query,MYSQLI_ASSOC);

        if ($mem['mem_price']=='A') {$price = number_format($result['p_a'],2,'.','');}
        else if ($mem['mem_price']=='B') {$price = number_format($result['p_b'],2,'.','');}
        else if ($mem['mem_price']=='C') {$price = number_format($result['p_c'],2,'.','');}
        $originalPrice = ($result['p_tag']!=0)? number_format($result['p_tag'],2,'.',''):number_format($result['p_c'],2,'.','');
        $discount = $originalPrice-$price;


          $status = ($result['pro_instock']>=$result['pro_limitA'])? 'show':'hide';
          $status = ($result['pro_show']==0)? 'show':'hide';

          $description_th = str_replace($bad,'',strip_tags($result['pro_details']));
          $title_th = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $title_th = ($title_th!='')? $title_th:$result['pro_name'];
          $slug = ($result['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

          $quantity = (int)$result['pro_instock'];
          $quantity = ($quantity>1)? 999:0;

          $json = array(
            'product' => array(
              'prices' => array(
                'price' => (float)$price,
                'originalPrice' => (float)$originalPrice,
                'discount' => (float)$discount,
              ),
              'categories' => array(),
              'image' => array(),
              'tag' => array(),
              'variants' => array(),
              'status' => $status,
              '_id' => $result['pro_id'],
              'productId' => $result['pro_id'],
              'productCode' => $result['pro_code'],
              'sku' => null,
              'barcode' => "",
              'title' => array(
                'th' => $title_th,
                'en' => $result['pro_nameEng']
              ),
              'description' => array(
                'th' => $description_th,
                'en' => ''
              ),
              'slug' => $slug, 
              'category' => array(
                '_id' => $result['pro_mode'], 
                'name' => array(
                  'th' => $result['pd_name_TH'], 
                  'en' => $result['pd_name_Eng'], 
                ),
              ),
              'stock' => $quantity,
              'isCombination' => true,
              'createdAt' => $result['pro_dateadd'], 
              'updatedAt' => date('Y-m-d'), 
              'sales' => 0,
              '__v' => 0

            ),
            'relatedProducts' => (object)array(),
          );

        if ($result['pro_glwa1']!=0) {$json['product']['categories'][] = 'ยาสามัญประจำบ้าน';}
        if ($result['pro_glwa2']!=0) {$json['product']['categories'][] = 'ยาแผนโบราณ';}
        if ($result['pro_glwa3']!=0) {$json['product']['categories'][] = 'อาหารเสริม';}
        if ($result['pro_glwa4']!=0) {$json['product']['categories'][] = 'ยาอันตราย';}
        if ($result['pro_glwa6']!=0) {$json['product']['categories'][] = 'ยาควบคุมพิเศษ';}
        if ($result['pro_glwa7']!=0) {$json['product']['categories'][] = 'ยาตามใบสั่งแพทย์';}
        if ($result['pro_glwa8']!=0) {$json['product']['categories'][] = 'ยาบรรจุเสร็จ';}
        if ($result['pro_glwa9']!=0) {$json['product']['categories'][] = 'เครื่องมือแพทย์';}
        if ($result['pro_glwa10']!=0) {$json['product']['categories'][] = 'เวชสำอาง';}

          $pro_img = str_replace('../',$site,$result['pro_img']);
          if ($result['pro_img']!='') {$json['product']['image'][] = $pro_img;}
          else {$pro_img = '';}

          $pro_imgU1 = str_replace('../',$site,$result['pro_imgU1']);
          if ($result['pro_imgU1']!='') {$json['product']['image'][] = $pro_imgU1;}

          $pro_imgU2 = str_replace('../',$site,$result['pro_imgU2']);
          if ($result['pro_imgU2']!='') {$json['product']['image'][] = $pro_imgU2;}

          $pro_imgU3 = str_replace('../',$site,$result['pro_imgU3']);
          if ($result['pro_imgU3']!='') {$json['product']['image'][] = $pro_imgU3;}

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

          $json['product']['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;

          $radio1 = $result['pro_ratio1']/$result['pro_ratio1'];
          $radio2 = $result['pro_ratio1']/$result['pro_ratio2'];
          $radio3 = $result['pro_ratio1']/$result['pro_ratio3'];

            if ($result['pro_unit1']!='') {
              $payload_2 = array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio1*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio1*$discount,
                'productId' => $result['pro_id'].'-0',
                'barcode' => $result['pro_barcode1'],
                'sku' => null,
                'unit' => $result['pro_unit1'],
                'image' => ($pro_imgU1!='')? $pro_imgU1:$pro_img
              );
              array_push($json['product']['variants'],$payload_2);
            }

            if ($result['pro_unit2']!='') {

              $payload_2 = array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio2*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio2*$discount,
                'productId' => $result['pro_id'].'-1',
                'barcode' => $result['pro_barcode2'],
                'sku' => null,
                'unit' => $result['pro_unit2'],
                'image' => ($pro_imgU2!='')? $pro_imgU2:$pro_img
              );
              array_push($json['product']['variants'],$payload_2);
            }

            if ($result['pro_unit3']!='') {              
              $payload_2 = array(
                'register' => $result['pro_drugregister'],
                'view' => (int)$result['pro_view'],
                'rating' => (float)number_format($result['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio3*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio3*$discount,
                'productId' => $result['pro_id'].'-2',
                'barcode' => $result['pro_barcode3'],
                'sku' => null,
                'unit' => $result['pro_unit3'],
                'image' => ($pro_imgU3!='')? $pro_imgU3:$pro_img
              );
              array_push($json['product']['variants'],$payload_2);
            }

        // echo SUBSTR(trim($result['pro_name']),0,12);

        $sql2 = "
            SELECT 
              *,
              `b`.`pro_priceTag` AS `p_tag` ,
              `b`.`pro_priceA` AS `p_a` , 
              `b`.`pro_priceB` AS `p_b` , 
              `b`.`pro_priceC` AS `p_c` 
            FROM 
              `product` AS `b`
              LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
            WHERE 
              b.pro_code !='".$result['pro_code']."' AND 
              `b`.`pro_name` LIKE '".SUBSTR(trim($result['pro_name']),0,12)."%'
            LIMIT
              12
        ";
        $query2 = mysqli_query($Con_wang,$sql2);
        while($result2 = mysqli_fetch_array($query2,MYSQLI_ASSOC)) {

        if ($mem['mem_price']=='A') {$price = number_format($result2['p_a'],2,'.','');}
        else if ($mem['mem_price']=='B') {$price = number_format($result2['p_b'],2,'.','');}
        else if ($mem['mem_price']=='C') {$price = number_format($result2['p_c'],2,'.','');}
        $originalPrice = ($result2['p_tag']!=0)? number_format($result2['p_tag'],2,'.',''):number_format($result2['p_c'],2,'.','');
        $discount = $originalPrice-$price;


          $status = ($result2['pro_instock']>=$result2['pro_limitA'])? 'show':'hide';
          $status = ($result2['pro_show']==0)? 'show':'hide';

          $description_th = str_replace($bad,'',strip_tags($result2['pro_details']));
          $title_th = ($result2['pro_nameMain']!='')? $result2['pro_nameMain']:$result2['pro_nameTH'];
          $title_th = ($title_th!='')? $title_th:$result2['pro_name'];
          $slug = ($result2['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

          $quantity = (int)$result2['pro_instock'];
          $quantity = ($quantity>1)? 999:0;


          $payload = array(
              'prices' => array(
                'price' => (float)$price,
                'originalPrice' => (float)$originalPrice,
                'discount' => (float)$discount,
              ),
              'categories' => array(),
              'image' => array(),
              'tag' => array(),
              'variants' => array(),
              'status' => $status,
              '_id' => $result2['pro_id'],
              'productId' => $result2['pro_id'],
              'productCode' => $result2['pro_code'],
              'sku' => null,
              'barcode' => "",
              'title' => array(
                'th' => $title_th,
                'en' => $result2['pro_nameEng']
              ),
              'description' => array(
                'th' => $description_th,
                'en' => ''
              ),
              'slug' => $slug, 
              'category' => array(
                '_id' => $result2['pro_mode'], 
                'name' => array(
                  'th' => $result2['pd_name_TH'], 
                  'en' => $result2['pd_name_Eng'], 
                ),
              ),
              'stock' => $quantity,
              'isCombination' => true,
              'createdAt' => $result2['pro_dateadd'], 
              'updatedAt' => date('Y-m-d'), 
              'sales' => 0,
              '__v' => 0
          );
          if ($result2['pro_glwa1']!=0) {$payload['categories'][] = 'ยาสามัญประจำบ้าน';}
          if ($result2['pro_glwa2']!=0) {$payload['categories'][] = 'ยาแผนโบราณ';}
          if ($result2['pro_glwa3']!=0) {$payload['categories'][] = 'อาหารเสริม';}
          if ($result2['pro_glwa4']!=0) {$payload['categories'][] = 'ยาอันตราย';}
          if ($result2['pro_glwa6']!=0) {$payload['categories'][] = 'ยาควบคุมพิเศษ';}
          if ($result2['pro_glwa7']!=0) {$payload['categories'][] = 'ยาตามใบสั่งแพทย์';}
          if ($result2['pro_glwa8']!=0) {$payload['categories'][] = 'ยาบรรจุเสร็จ';}
          if ($result2['pro_glwa9']!=0) {$payload['categories'][] = 'เครื่องมือแพทย์';}
          if ($result2['pro_glwa10']!=0) {$payload['categories'][] = 'เวชสำอาง';}

          $pro_img = str_replace('../',$site,$result2['pro_img']);
          if ($result2['pro_img']!='') {$payload['image'][] = $pro_img;}
          else {$pro_img = '';}

          $pro_imgU1 = str_replace('../',$site,$result2['pro_imgU1']);
          if ($result2['pro_imgU1']!='') {$payload['image'][] = $pro_imgU1;}

          $pro_imgU2 = str_replace('../',$site,$result2['pro_imgU2']);
          if ($result2['pro_imgU2']!='') {$payload['image'][] = $pro_imgU2;}

          $pro_imgU3 = str_replace('../',$site,$result2['pro_imgU3']);
          if ($result2['pro_imgU3']!='') {$payload['image'][] = $pro_imgU3;}

          $tag_x = array();
          if ($result2['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
          if ($result2['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
          if ($result2['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
          if ($result2['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

          $tag_ms = '[';
          foreach ($tag_x as $key => $value) {
            $tag_ms .= '"'.$value;
            if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
            if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
          }
          $tag_ms .= ']';

          $payload['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;

          $radio1 = $result2['pro_ratio1']/$result2['pro_ratio1'];
          $radio2 = $result2['pro_ratio1']/$result2['pro_ratio2'];
          $radio3 = $result2['pro_ratio1']/$result2['pro_ratio3'];

            if ($result2['pro_unit1']!='') {
              $payload_2 = array(
                'register' => $result2['pro_drugregister'],
                'view' => (int)$result2['pro_view'],
                'rating' => (float)number_format($result2['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio1*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio1*$discount,
                'productId' => $result2['pro_id'].'-0',
                'barcode' => $result2['pro_barcode1'],
                'sku' => null,
                'unit' => $result2['pro_unit1'],
                'image' => ($pro_imgU1!='')? $pro_imgU1:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($result2['pro_unit2']!='') {

              $payload_2 = array(
                'register' => $result2['pro_drugregister'],
                'view' => (int)$result2['pro_view'],
                'rating' => (float)number_format($result2['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio2*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio2*$discount,
                'productId' => $result2['pro_id'].'-1',
                'barcode' => $result2['pro_barcode2'],
                'sku' => null,
                'unit' => $result2['pro_unit2'],
                'image' => ($pro_imgU2!='')? $pro_imgU2:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($result2['pro_unit3']!='') {              
              $payload_2 = array(
                'register' => $result2['pro_drugregister'],
                'view' => (int)$result2['pro_view'],
                'rating' => (float)number_format($result2['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio3*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio3*$discount,
                'productId' => $result2['pro_id'].'-2',
                'barcode' => $result2['pro_barcode3'],
                'sku' => null,
                'unit' => $result2['pro_unit3'],
                'image' => ($pro_imgU3!='')? $pro_imgU3:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            array_push($json['relatedProducts'],$payload);

        }
        // echo $sql;

        mysqli_close($Con_wang);
        echo json_encode($json);
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