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

        $s = ($_GET['s']!='')? $_GET['s']:date('Y-m-d');    
        $e = ($_GET['e']!='')? $_GET['e']:date('Y-m-d');

        $start = ($_GET['start']!='')? $_GET['start']:0;
        $end = ($_GET['end']!='')? $_GET['end']:12;

        $sql = "
          SELECT 
            `b`.*,
            SUM(`b`.`WH_receiveBox_TC_qtyBox`) AS `boxs`,
            SUM(`b`.`WH_receiveBox_TC_qtySub`) AS `list`,
            `c`.*,
            `c`.`id` AS `pro_id`
          FROM 
            `WH_receiveBox_TC` AS `b`
            LEFT JOIN `product` AS `c` ON `b`.`WH_receiveBox_TC_idPro`=`c`.`id`
          WHERE 
            `b`.`WH_receiveBox_TC_dateAdd` BETWEEN '".$s." 00:00:00' AND '".$e." 23:59:59'
          GROUP BY 
            `b`.`WH_receiveBox_TC_idPro`
          ORDER BY 
            `b`.`WH_receiveBox_TC_dateAdd`
          DESC
            LIMIT 
          ".$start." , ".$end."
        ";

        $site = 'https://www.wangpharma.com/';

        $query = mysqli_query($Con_pharSYS,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();

        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          $pro = mysqli_fetch_array(mysqli_query($Con_wang,"
            SELECT 
              * 
            FROM 
              `product` AS `b` 
              LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
            WHERE 
              `b`.`pro_code`='".$result['pcode']."'
          "));


          $pro_nameMain = ($pro['pro_nameMain']!='')? $pro['pro_nameMain']:$pro['pro_nameTH'];
          $pro_nameMain = ($pro_nameMain!='')? $pro_nameMain:$pro['pro_name'];
          $pro_instock = ($pro['pro_instock']>=$pro['pro_limitA'])? 'มี':'หมด';
          $pro_instock = (int)999;

          $pro_nameEng = ($pro['pro_nameEng']!='')? $pro['pro_nameEng']:null;

          $pro_img = str_replace('../',$site,$pro['pro_img']);

          if ($pro['pro_barcode1']!='') {$pro_barcode = $pro['pro_barcode1'];}
          else if ($pro['pro_barcode2']!='') {$pro_barcode = $pro['pro_barcode2'];}
          else if ($pro['pro_barcode3']!='') {$pro_barcode = $pro['pro_barcode3'];}
          $Price_Tag = number_format($pro['pro_priceTag'],2,'.','');

          $price_difference = number_format($pro['pro_priceC']-$pro['pro_priceA'],2,'.',',');
          $per_difference = number_format((($pro['pro_priceC']-$pro['pro_priceA'])/$pro['pro_priceC'])*100,2,'.',',');

          $payload = array(
            'pro_code' => $pro['pro_code'],
            'pro_nameMain' => $pro_nameMain,
            'pro_nameEng' => $pro_nameEng,
            'pro_barcode' => $pro_barcode,
            'pro_unit1' => $pro['pro_unit1'],
            'pro_mode' => $pro['pd_name'],
            'Price_Tag' => $Price_Tag,
            'variants' => array(),
            'pro_before' => number_format($pro['pro_priceC'],2,'.',','),
            'pro_after' => number_format($pro['pro_priceA'],2,'.',','),
            'price_difference' => $price_difference,
            'per_difference' => $per_difference,
            'pro_instock' => $pro_instock,
            'pro_img' => $pro_img,
            'pro_details' => $pro['pro_details'],
          );

          $radio1 = $pro['pro_ratio1']/$pro['pro_ratio1'];
          $radio2 = $pro['pro_ratio1']/$pro['pro_ratio2'];
          $radio3 = $pro['pro_ratio1']/$pro['pro_ratio3'];

            if ($pro['pro_unit1']!='') {
              $pro_before = $radio1*$pro['pro_priceC'];
              $pro_after = $radio1*$pro['pro_priceA'];

              $payload_2 = array(
                'pro_unit' => $pro['pro_unit1'],
                'Price_Tag' => $Price_Tag,
                'pro_before' => number_format($pro_before,2,'.',','),
                'pro_after' => number_format($pro_after,2,'.',','),
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($pro['pro_unit2']!='') {
              $pro_before = $radio2*$pro['pro_priceC'];
              $pro_after = $radio2*$pro['pro_priceA'];

              $payload_2 = array(
                'pro_unit' => $pro['pro_unit2'],
                'Price_Tag' => $Price_Tag,
                'pro_before' => number_format($pro_before,2,'.',','),
                'pro_after' => number_format($pro_after,2,'.',','),
              );
              array_push($payload['variants'],$payload_2);
            }

            if ($pro['pro_unit3']!='') {
              $pro_before = $radio3*$pro['pro_priceC'];
              $pro_after = $radio3*$pro['pro_priceA'];
              
              $payload_2 = array(
                'pro_unit' => $pro['pro_unit3'],
                'Price_Tag' => $Price_Tag,
                'pro_before' => number_format($pro_before,2,'.',','),
                'pro_after' => number_format($pro_after,2,'.',','),
              );
              array_push($payload['variants'],$payload_2);
            }


          // $payload_2 = array(
          //   'pro_unit1' => $pro['pro_unit1'],
          //   'pro_unit2' => $pro['pro_unit2'],
          //   'pro_unit3' => $pro['pro_unit3'],
          // );
          // array_push($payload['variants'],$payload_2);

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