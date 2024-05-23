<?php
  header("Access-Control-Max-Age: 3600");
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");
  header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization");

  require_once('../../Akitokung/00-connection.class.sqli.php');

  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $token = getBearerToken();
    if (!empty($token)) {
      $data = decode_jwt($token);
      if ($data) {
        $mem = $data['mem_code'];

        $ds = ($_GET['ds']!='')? $_GET['ds']:date('Y-01-01');
        $de = ($_GET['de']!='')? $_GET['de']:date('Y-m-t');

        $s = ($_GET['s']!='')? $_GET['s']:0;    
        $e = ($_GET['e']!='')? $_GET['e']:40;

        $sql = "
          SELECT 
            `sb_datesale` , 
            `sb_billcode` , 
            COUNT(`sb_procode`) AS `List` , 
            SUM(`sb_price`) AS `Price` 
          FROM 
            `sale_orderBill` 
          WHERE 
            `sb_cuscode`='".$mem."' AND 
            `sb_datesale` BETWEEN '".$ds."' AND '".$de."'
          GROUP BY 
            `sb_billcode`
          ORDER BY 
            `sb_datesale` 
          DESC 
            LIMIT 
          ".$s." , ".$e."
        ";
        $query = mysqli_query($Con_wang,$sql);
        if (!$query) {http_response_code(404);}   $json = array();
        while($result = mysqli_fetch_array($query,MYSQLI_ASSOC)) {
          $payload = array(
            'date' => $result['sb_datesale'],
            'bill' => $result['sb_billcode'],
            'list' => number_format($result['List'],0,'.',','),
            'price' => number_format($result['Price'],2,'.',','),
          );
          array_push($json,$payload);
        }
        mysqli_close($Con_wang);
        $json = json_encode($json);
        echo $json;
      }
    }
  }
?>
