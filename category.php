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
        $json = array();;
          // (String)strtotime(date('Y-m-d H:i:s'))
          $payload = array(
            '_id' => 0,
            'name' => array(
              'th' => 'หน้าหลัก',
              'en' => 'Home'
            ),
            'parentName' => 'Home',
            'description' => array(
              'th' => 'นี่คือ หมวดหมู่ หรือ กลุ่มหลัก ของสินค้า',
              'end' => 'This is Home Category'
            ),
            'status' => 'show',
            'children' => array(),
          );

          $sql = "SELECT * FROM `z_Mset1` WHERE 1";
          $query = mysqli_query($Con_wang,$sql);
          if (!$query) {http_response_code(404);}     $json = array();
          while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
            $icon = ($result['z1_icon']!='')? 'https://www.wangpharma.com/Akitokung/images/category/'.$result['z1_icon']:'https://www.wangpharma.com/Akitokung/images/logo-big.png';

            $children = array(
              '_id' => 1,
              'name' => array(
                'th' => $result['z1_name'],
                'en' => $result['z1_nEng']
              ),
              'parentId' => $result['z1_id'],
              'parentName' => $result['z1_name'],
              'description' => array(
                'th' => $result['z1_name'],
                'end' => $result['z1_nEng']
              ),
              'icon' => $icon,
              'status' => 'show',
              'children' => array(),
            );

            $m2 = "
              SELECT 
                * 
              FROM 
                `z_Mset2` 
              WHERE 
                `z1_code`='".$result['z1_code']."'
              GROUP BY 
                `z2_code`
              ORDER BY 
                `z2_id`
              ASC
            ";
            // echo $m2;
            $qm2 = mysqli_query($Con_wang,$m2);
            while($r2 = mysqli_fetch_array($qm2,MYSQLI_ASSOC)) {
              $icon = ($r2['z2_icon']!='')? 'https://www.wangpharma.com/Akitokung/images/category/'.$r2['z2_icon']:'https://www.wangpharma.com/Akitokung/images/logo-big.png';

              $child = array(
                '_id' => 2,
                'name' => array(
                  'th' => $r2['z2_name'],
                  'en' => $r2['z2_nEng']
                ),
                'parentId' => $result['z2_id'],
                'parentName' => $r2['z2_name'],
                'description' => array(
                  'th' => $r2['z2_name'],
                  'end' => $r2['z2_nEng']
                ),
                'icon' => $icon,
                'status' => 'show',
                'children' => array(),
              );

              $m3 = "
                SELECT 
                  * 
                FROM 
                  `z_Mset2` 
                WHERE 
                  `z1_code`='".$result['z1_code']."' AND 
                  `z2_code`='".$r2['z2_code']."' 
              GROUP BY 
                `z3_code`
              ORDER BY 
                `z3_id`
              ASC
              ";
              // echo $m3;
              $qm3 = mysqli_query($Con_wang,$m3);
              while($r3 = mysqli_fetch_array($qm3,MYSQLI_ASSOC)) {
                $icon = ($r3['z3_icon']!='')? 'https://www.wangpharma.com/Akitokung/images/category/'.$r3['z3_icon']:'https://www.wangpharma.com/Akitokung/images/logo-big.png';
                $child_3 = array(
                  '_id' => 3,
                  'name' => array(
                    'th' => $r3['z3_name'],
                    'en' => $r3['z3_nEng']
                  ),
                  'parentId' => $result['z3_id'],
                  'parentName' => $r3['z3_name'],
                  'description' => array(
                    'th' => $r3['z3_name'],
                    'end' => $r3['z3_nEng']
                  ),
                  'icon' => $icon,
                  'status' => 'show',
                  'children' => array(),
                );
                array_push($child['children'],$child_3);
              }
              array_push($children['children'],$child);
            }
            array_push($payload['children'],$children);
          }
          array_push($json,$payload);

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
