<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: * ");
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

        $start = ($_GET['start']!='')? $_GET['start']:'0';    
        $end = ($_GET['end']!='')? $_GET['end']:'10';
        
        $sql = "
          SELECT 
            `a`.`bsl_procode` AS `List`,
            `a`.`bsl_price` AS `Price`,
            `b`.*
          FROM 
            `shopping_BSL` AS `a` 
            LEFT JOIN `product` AS `b` ON `a`.`bsl_procode`=`b`.`pro_code`
          WHERE 
            `b`.`pro_priceC`!='0' AND  
            `a`.`bsl_month`='".date('m')."' AND 
            `b`.`pro_img`!=''
          GROUP BY 
            `a`.`bsl_procode`
          ORDER BY 
            `Price`
          DESC
            LIMIT
          ".$start.",".$end."
        ";

        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();

        // Akitokung
        $site = 'https://www.wangpharma.com/';
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {

          $pro_nameMain = ($result['pro_nameMain']!='')? $result['pro_nameMain']:$result['pro_nameTH'];
          $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$result['pro_name'];
          $pro_instock = ($result['pro_instock']>=$result['pro_limitA'])? 'มี':'หมด';

          $pro_nameEng = ($result['pro_nameEng']!='')? $result['pro_nameEng']:null;

          $pro_img = str_replace('../',$site,$result['pro_img']);

          if ($result['pro_barcode1']!='') {$pro_barcode = $result['pro_barcode1'];}
          else if ($result['pro_barcode2']!='') {$pro_barcode = $result['pro_barcode2'];}
          else if ($result['pro_barcode3']!='') {$pro_barcode = $result['pro_barcode3'];}
          $Price_Tag = number_format($result['pro_priceTag'],2,'.','');

          $price_difference = number_format($result['pro_priceC']-$result['pro_priceA'],2,'.',',');
          $per_difference = number_format((($result['pro_priceC']-$result['pro_priceA'])/$result['pro_priceC'])*100,2,'.',',');

          $payload = array(
            'pro_code' => $result['pro_code'],
            'pro_nameMain' => $pro_nameMain,
            'pro_nameEng' => $pro_nameEng,
            'pro_barcode' => $pro_barcode,
            'pro_unit1' => $result['pro_unit1'],
            'Price_Tag' => $Price_Tag,
            'pro_before' => number_format($result['pro_priceC'],2,'.',','),
            'pro_after' => number_format($result['pro_priceA'],2,'.',','),
            'price_difference' => $price_difference,
            'per_difference' => $per_difference,
            'pro_instock' => $pro_instock,
            'pro_img' => $pro_img,
          );
          array_push($json,$payload);
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