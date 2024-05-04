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
        //echo $data['mem_code'];
        $sql = "SELECT * FROM `z_Mset1` WHERE 1";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}     $json = array();
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          $z1_icon = ($result['z1_icon']!='')? 'https://www.wangpharma.com/Akitokung/images/category/'.$result['z1_icon']:'https://www.wangpharma.com/Akitokung/images/logo-big.png';

          $payload = array(
            'code' => $result['z1_code'],
            'title' => $result['z1_name'],
            'icon' => $z1_icon,
            'redirect' => 'https://www.wangpharma.com',
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
