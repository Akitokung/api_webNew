<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: * ");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');

  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $payload = array();

        $sql = "
          SELECT 
            * 
          FROM 
            `member` 
          WHERE 
            `mem_code`='".$data['mem_code']."'
        ";
        // echo $sql;
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}
        $json = array();
        $site = 'https://www.wangpharma.com/';
        $result = mysqli_fetch_array($query);

        $site = 'https://www.wangpharma.com/';
        $img = ($result['mem_img1']!='')? $site.'Akitokung/'.$result['mem_img1']:null;

        $payload = array(
          'code' => $result['mem_code'],
          'name' => $result['mem_name'],
          'user' => $result['mem_username'],
          'img' => $img
        );
        mysqli_close($Con_wang);
        $json = json_encode($payload);
        echo $json;
      }
    }
  }
?>
