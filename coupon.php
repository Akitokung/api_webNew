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

        $json = array();
        $sql = "
          SELECT 
            * 
          FROM 
            `events_wang` AS `a` 
            LEFT JOIN `product` AS `b` ON `a`.`evw_pcode`=`b`.`pro_code` 
            LEFT JOIN `product_drugmode` AS `c` ON `b`.`pro_mode`=`c`.`pd_code`
          WHERE 
            `a`.`evg_id`!='0' AND 
            `a`.`evw_end`>'".date('Y-m-d')."'
          LIMIT
            2
        ";
        $query = mysqli_query($Con_wang,$sql);    $site = 'https://www.wangpharma.com/';
        if (!$query) {http_response_code(404);}        
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          $status = ($result['evw_status']==1)? 'show':'hide';
          $endTime = $result['evw_end'].' 21:59:59';
          $logo = str_replace('../',$site,$result['pro_img']);

          $coupon = array(
            'status' => $status,
            '_id' => $result['evw_id'],
            'title' => array(
              'th' => $result['evw_name'],
              'en' => $result['evw_nameEng'],
            ),
            'couponCode' => $result['evw_shotname'],
            'endTime' => $endTime,
            'minimumAmount' => (int)$result['evw_minimum'],
            'productType' => $result['pd_name_TH'],
            'logo' => $logo,
            'discountType' => array(
              'type' => "percentage",
              'value' => 1
            ),
            '__v' => 0,
            'createdAt' => $result['evw_creatdAt'],
            'updatedAt' => $result['evw_updatedAt'],
          );
          array_push($json,$coupon);
        }

        // mysqli_close($Con_wang);
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
