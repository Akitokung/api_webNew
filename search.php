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

        $json = array(
          'products' => array(),
        );

        $search = $_GET['search'];
        $sql = "
          SELECT 
            *
          FROM 
            `product` AS `a`
            LEFT JOIN `product_drugmode` AS `b` ON `a`.`pro_mode`=`b`.`pd_code`
            LEFT JOIN `product_pharma` AS `c` ON `a`.`pro_code`=`c`.`pp_procode`
          WHERE 
            `a`.`pro_priceC`!='0' AND                             
            SUBSTR(`a`.`pro_name`,1,1)!='-' AND 
            SUBSTR(`a`.`pro_name`,1,3)!='ฟรี' AND 
            `a`.`pro_img`!='' AND 
            `a`.`pro_show`='0' AND 
            (
              `a`.`pro_code` LIKE '%".$search."%' OR
              `a`.`pro_barcode1` LIKE '%".$search."' OR
              `a`.`pro_barcode2` LIKE '%".$search."' OR
              `a`.`pro_barcode3` LIKE '%".$search."' OR
              `a`.`pro_name` LIKE '%".$search."%' OR
              `a`.`pro_nameTH` LIKE '%".$search."%' OR
              `a`.`pro_nameEng` LIKE '%".$search."%' OR
              `a`.`pro_nameMain` LIKE '%".$search."%' OR
              `a`.`pro_genericname` LIKE '%".$search."%' OR
              `a`.`pro_unit1` LIKE '%".$search."%' OR
              `a`.`pro_unit2` LIKE '%".$search."%' OR
              `a`.`pro_unit3` LIKE '%".$search."%' OR
              `a`.`pro_priceTag` LIKE '%".$search."%' OR
              `a`.`pro_details` LIKE '%".$search."%' OR
              `a`.`pro_keysearch` LIKE '%".$search."%' OR
              `c`.`pp_properties` LIKE '%".$search."%' OR
              `c`.`pp_how_to_use` LIKE '%".$search."%' OR
              `c`.`pp_caution` LIKE '%".$search."%' OR
              `c`.`pp_use_in_pregnant_women` LIKE '%".$search."%' OR
              `c`.`pp_side_effects` LIKE '%".$search."%' OR
              `c`.`pp_suggestion` LIKE '%".$search."%'
            )
        ";

        // echo $sql;
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}        
         // Akitokung
        $site = 'https://www.wangpharma.com/';
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          $pro = mysqli_fetch_array(mysqli_query($Con_wang,"
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
              `b`.`pro_code`='".$result['pro_code']."'
          "));

          if ($mem['mem_price']=='A') {$price = number_format($pro['p_a'],2,'.','');}
          else if ($mem['mem_price']=='B') {$price = number_format($pro['p_b'],2,'.','');}
          else if ($mem['mem_price']=='C') {$price = number_format($pro['p_c'],2,'.','');}
          $originalPrice = ($pro['p_tag']!=0)? number_format($pro['p_tag'],2,'.',''):number_format($pro['p_c'],2,'.','');
          $discount = $originalPrice-$price;

          $status = ($pro['pro_instock']>=$pro['pro_limitA'])? 'show':'hide';
          $status = ($pro['pro_show']==0)? 'show':'hide';

          $description_th = str_replace($bad,'',strip_tags($pro['pro_details']));
          $title_th = ($pro['pro_nameMain']!='')? $pro['pro_nameMain']:$pro['pro_nameTH'];
          $title_th = ($title_th!='')? $title_th:$pro['pro_name'];
          $slug = ($pro['pro_gs3']!=0)? 'โปรโมชั่น ฯ':'';

          $quantity = (int)$pro['pro_instock'];
          $quantity = ($quantity>1)? 999:0;

          $pro_img = str_replace('../',$site,$pro['pro_img']);
          $pro_imgU1 = str_replace('../',$site,$pro['pro_imgU1']);
          $pro_imgU2 = str_replace('../',$site,$pro['pro_imgU2']);
          $pro_imgU3 = str_replace('../',$site,$pro['pro_imgU3']);

          $prices = array(
            'price' => 450,
            'originalPrice' => 450,
            'discount' => 0,
            'categories' => array(),
            'image' => array(),
            'tag' => array(),
            'variants' => array(),

            "status" => $status , 
            "_id" => $pro['pro_id'],
            "productId" => $pro['pro_id'],
            "productCode" => $pro['pro_code'],
            "sku" => "",
            "barcode" => "",
            "title" => array(
              "th" => $title_th,
              "en" => $pro['pro_nameEng']
            ),
            "description" => array(
              "th" => $description_th,
              "en" => ''
            ),
            "slug" => $slug, 
            "category" => array(
              "_id" => $pro['pro_mode'], 
              "name" => array(
                "th" => $pro['pd_name_TH'], 
                "en" => $pro['pd_name_Eng'], 
              ),
            ),
            "stock" => $quantity,
            "isCombination" => true,
            "createdAt" => $pro['pro_dateadd'], 
            "updatedAt" => date('Y-m-d'), 
            "sales" => 0,
            "__v" => 0
          );

          array_push($prices['categories'],'1');
          array_push($prices['categories'],'2');
          array_push($prices['categories'],'3');

          array_push($prices['image'],'image_1');
          array_push($prices['image'],'image_2');
          array_push($prices['image'],'image_3');

          $tag_x = array();
          if ($pro['pro_gs6']!=0) {$tag_x[] = 'แนะนำขาย';}
          if ($pro['pro_gs7']!=0) {$tag_x[] = 'สินค้าขายดี';}
          if ($pro['pro_gs8']!=0) {$tag_x[] = 'สินค้าใหม่';}
          if ($pro['pro_gs3']!=0) {$tag_x[] = 'โปรโมชั่น ฯ';}

          $tag_ms = '[';
          foreach ($tag_x as $key => $value) {
            $tag_ms .= '"'.$value;
            if ($key!=COUNT($tag_x)-1) {$tag_ms .= '",';}
            if ($key==COUNT($tag_x)-1) {$tag_ms .= '"';}
          }
          $tag_ms .= ']';

          $prices['tag'][] = (COUNT($tag_x)==0)? '':$tag_ms;


            if ($pro['pro_unit1']!='') {
              $payload_2 = array(
                'register' => $pro['pro_drugregister'],
                'view' => (int)$pro['pro_view'],
                'rating' => (float)number_format($pro['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio1*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio1*$discount,
                'productId' => $pro['pro_id'].'-0',
                'barcode' => $pro['pro_barcode1'],
                'sku' => null,
                'unit' => $pro['pro_unit1'],
                'image' => ($pro_imgU1!='')? $pro_imgU1:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($pro['pro_unit2']!='') {

              $payload_2 = array(
                'register' => $pro['pro_drugregister'],
                'view' => (int)$pro['pro_view'],
                'rating' => (float)number_format($pro['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio2*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio2*$discount,
                'productId' => $pro['pro_id'].'-1',
                'barcode' => $pro['pro_barcode2'],
                'sku' => null,
                'unit' => $pro['pro_unit2'],
                'image' => ($pro_imgU2!='')? $pro_imgU2:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($pro['pro_unit3']!='') {              
              $payload_2 = array(
                'register' => $pro['pro_drugregister'],
                'view' => (int)$pro['pro_view'],
                'rating' => (float)number_format($pro['pro_rating'],2,'.',''),

                'originalPrice' => (float)number_format($pro_before,2,'.',''),
                'price' => (float)number_format($radio3*$price,2,'.',''),
                'quantity' => (float)$quantity/$radio1,

                'discount' => (float)$radio3*$discount,
                'productId' => $pro['pro_id'].'-2',
                'barcode' => $pro['pro_barcode3'],
                'sku' => null,
                'unit' => $pro['pro_unit3'],
                'image' => ($pro_imgU3!='')? $pro_imgU3:$pro_img
              );
              array_push($payload['variants'],$payload_2);
            }

          array_push($json['products'],$prices);
        }

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
