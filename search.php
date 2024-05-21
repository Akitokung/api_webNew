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

        for ($i = 0 ; $i < 1 ; $i++) { 
          $prices = array(
            'price' => 450,
            'originalPrice' => 450,
            'discount' => 0,
            'categories' => array(),
            'image' => array(),
            'tag' => array(),
            'variants' => array()
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
