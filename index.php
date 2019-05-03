<?php
require_once(dirname(__FILE__).'/vendor/autoload.php');
session_start();
define('OAUTH_CLIENT_ID', 'v6y1ycp5yx78feien44h');
define('OAUTH_CLIENT_SECRET', 'j3iox5afx5u1s3vj624055ot4391dz11');
define('OAUTH_REDIRECT_URL', 'http://nestformmain.byethost6.com/index.php'); // << put in the redirect url, if you don't want to use the same script or it doesn't work correctly for you


$host = 'https://www.nestforms.com/api/';

// find out our URL
$url = OAUTH_REDIRECT_URL;
/*if(empty($url) && isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
  $parsed = parse_url($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
  $url = $parsed['scheme'].'://'.$parsed['host'].(!empty($parsed['port']) ? ':'.$parsed['port'] : '').''.$parsed['path'];
}*/

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
  'clientId'                => OAUTH_CLIENT_ID,    // The client ID assigned to you by the provider
  'clientSecret'            => OAUTH_CLIENT_SECRET,   // The client password assigned to you by the provider
  'redirectUri'             => $url,
  'urlAuthorize'            => $host.'authorize',
  'urlAccessToken'          => $host.'access_token',
  'urlResourceOwnerDetails' => $host.'member',
  'scopeSeparator' => ' ',
  'scopes' => array('forms_access', 'reports_read', 'customdb_read', 'customdb_write'),
]);

// If we don't have an authorization code then get one
if(!empty($_SESSION['access_token'])) {
  $accessToken = new League\OAuth2\Client\Token\AccessToken($_SESSION['access_token']);
  if($accessToken->hasExpired()) {
    //FIXME: We need to implement refresng of the token?
    $accessToken = $provider->getAccessToken('refresh_token', [
      'refresh_token' => $accessToken->getRefreshToken()
    ]);
    $_SESSION['access_token'] = $accessToken->jsonSerialize();
  }
}

if(empty($accessToken)) {
  if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

  // Check given state against previously stored one to mitigate CSRF attack
  } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

  }
  $accessToken = $provider->getAccessToken('authorization_code', [
      'code' => $_GET['code']
  ]);
  $_SESSION['access_token'] = $accessToken->jsonSerialize();
}
$client = new GuzzleHttp\Client(array('base_uri' => $host));

if (false === $accessToken) {
  // we are missing the access token / the access token was not able to refresh, redirect the user to the authorization again
  header('HTTP/1.1 302 Found');
  header('Location: '.$api->getAuthorizeUri($context));
  exit;
}

//calc how long the access token lasts
$left = ($accessToken->getExpires()) - time();
if($left > 3600) {
  $h = floor($left / 3600);
  $left = $h.'h '.(($left/60) - round($h * 60)).'m';
} else if($left > 60) {
  $m = floor($left / 60);
  $left = $m.'m '.($left - ($m * 60)).'s';
} else {
  $left = $left .'s';
}

// Work with database
// work with MySQL
$servername = "sql313.byethost.com";
$username = "b6_23747514";
$password = "4zgm9d0w";
$database = "b6_23747514_Form";
$conn = mysqli_connect($servername, $username, $password, $database);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// sql to create table
$sql_gen = "CREATE TABLE IF NOT EXISTS Gen (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  Farm VARCHAR(500), 
  Khu_vuc_tuoi VARCHAR(500),
  Dinh_vi_khuvuctuoi VARCHAR(500),
  Ngay_thu_thap_du_lieu DATETIME,
  Lan_tuoi_thu INT,
  Luong_nuoc_du_ra INT,
  URL_Luong_nuoc_du_ra VARCHAR(500),
  pH_trong_chau_hung INT,
  URL_pH_trong_chau_hung VARCHAR(500),
  High_1 INT,
  URL_High_1 VARCHAR(500),
  High_2 INT,
  URL_High_2 VARCHAR(500),
  High_3 INT,
  URL_High_3 VARCHAR(500),
  High_4 INT,
  URL_High_4 VARCHAR(500),
  High_5 INT,
  URL_High_5 VARCHAR(500)
)";
$conn->query($sql_gen);

// Create table Grow dua chuot
$sql_grow_dua_chuot = "CREATE TABLE IF NOT EXISTS Grow_dua_chuot (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  URL_Hinh_anh_bat_thuong VARCHAR(500),
  Chieu_cao_cay INT(10),
  So_la_moi INT(10),
  Kich_thuoc_la INT(10),
  Chieu_dai_long_than INT(10),
  URL_Hinh_anh_tong_quat VARCHAR(500),
  So_hoa_moi INT(10),
  URL_Hinh_anh_cua_hoa VARCHAR(500),
  URL_Hinh_anh_cua_qua VARCHAR(500),
  Sinh_truong_va_phat_trien_cua_re VARCHAR(500),
  Ty_le_re_va_than_la FLOAT(10)
)";
$conn->query($sql_grow_dua_chuot);

// Create table Grow ot chuong
$sql_grow_ot_chuong = "CREATE TABLE IF NOT EXISTS Grow_ot_chuong (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  URL_Hinh_anh_bat_thuong VARCHAR(500),
  Chieu_cao_cay INT(10),
  So_la_moi INT(10),
  Kich_thuoc_la INT(10),
  URL_Hinh_anh_tong_quat VARCHAR(500),
  So_hoa_moi INT(10),
  URL_Hinh_anh_cua_hoa VARCHAR(500),
  Ngay_thu_qua_dot_1 DATETIME,
  Trong_luong_qua INT(10),
  URL_Hinh_anh_qua VARCHAR(500),
  Trong_luong_qua_1 INT(10),
  Khoi_luong_qua_tren_cay INT(10)
)";
$conn->query($sql_grow_ot_chuong);

// Create Grow dua luoi
$sql_grow_dua_luoi = "CREATE TABLE IF NOT EXISTS Grow_dua_luoi (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
)";
$conn->query($sql_grow_dua_luoi);


$sql_collect = "CREATE TABLE IF NOT EXISTS Collect (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  Khoi_luong_trung_binh_trai FLOAT(10),
  comment_Khoi_luong VARCHAR(500),
  Do_ngot_trung_binh_trai FLOAT(10),
  comment_do_ngot VARCHAR(500),
  Tong_san_luong_thu_hoach INT(100)
)";
$conn->query($sql_collect);

mysqli_close($conn);
// some info output
// echo ' <a href="?">Forms</a>';
// echo ' <a href="?members=0">Members</a>';
// echo ' <a href="?customdb=0">Customdb Tables</a>';
// echo ' Access Token: '.$accessToken->getToken()." (expires in ".$left.")\n";
// #echo ' Refresh Token: '.$accessToken->getRefreshToken()."\n";
// echo '<br />';

// Get form_id and form_name
echo "Form ID and Form Name".'<br />';
$response_form = $client->send($provider->getAuthenticatedRequest(
      'GET',
      $host.'forms_list',
      $accessToken
    ));
    $response_formStream = $response_form->getBody();
    $forms = json_decode($response_formStream, true);
foreach($forms as $form) {
        //echo '  <li><a href="?fid='.$form['form_id'].'">'.$form['name'].'</a></li>'."\n";
  echo $form['form_id'].": ".$form['name'].'<br />';
}

echo "FARM LAC DUONG - CAY OT CHUONG".'<br />';
//////////// FARM LAC DUONG - CAY OT CHUONG

$f_i = 36961; // cay ot chuong - farm lac duong
 $response1 = $client->send(
    $provider->getAuthenticatedRequest(
      'GET',
      $host.'report/'.((int)$f_i),
      $accessToken
    )
);
$response1Stream = $response1->getBody();
$details1 = json_decode($response1Stream, true);

// show some form info
// echo '<h1>'.htmlspecialchars($details1['form']['name']);
// if(!empty($details1['form']['is_frequent']))
//   echo ' (Frequent form)';
// if(!empty($details1['form']['is_public']))
//   echo ' (Public form)';
// echo ' <a href="?"> &laquo; back to list of Forms</a></h1>';
// echo '<p>';
// if(!empty($details1['form']['reports_count']))
//   echo 'Total Responses: '.$details1['form']['reports_count'].'<br />';
// if(!empty($details1['form']['approved_count']))
//   echo 'Approved Responses: '.$details1['form']['approved_count'].'<br />';
// if(!empty($details1['form']['awaiting_approval_count']))
//   echo 'Not-approved Responses: '.$details1['form']['awaiting_approval_count'].'<br />';
// if(!empty($details1['form_items']))
//   echo 'Total Form Items: '.count($details1['form_items']).'<br />';
// echo '</p>';
// // show form responses
// echo '<h3>Received responses:</h3>';
// foreach ($details1['form_items'] as $key => $value) {
//   # code...
//   echo $value['name'].' '.$value['page_num'].'<br />';
// }

if(!empty($details1['data'])) {
  $paging = '';
  if(!empty($_REQUEST['pg']) && $_REQUEST['pg'] > 0) {
    $paging .= '<a href="?fid='.((int)$_REQUEST['fid']).'&pg='.(((int)$_REQUEST['pg']) - 1).'">&lt; Previous Page</a>';
  }
  if((empty($_REQUEST['pg']) && $details1['form']['reports_count'] > 5000) || (floor($details1['form']['reports_count'] / 5000) > (isset($_REQUEST['pg']) ? $_REQUEST['pg'] : 0))) {
    $paging .= '<a href="?fid='.((int)$_REQUEST['fid']).'&pg='.(isset($_REQUEST['pg']) ? $_REQUEST['pg'] + 1 : 1).'">Next Page &gt;</a>';
  }
  echo $paging;
  echo '<ul>'."\n";
  foreach($details1['data'] as $key_local => $result) {
    //$result is one response
    $temp_array_value = array();
    $temp_array_field = array();

    echo '  <li><div style="cursor: pointer" title="Click to show values" onclick="var e = this.nextSibling; e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">'.(!empty($result['event_name']) ? $result['event_name'] : '-- empty --').(!empty($result['member_label']) ? ' ('.$result['member_label'].')' : '').'</div>';
    echo '<ul style="display: none;">'."\n";
    // now let's loop over the form_items rather then the $result['data'], so we can have the items in correct order
    $lastPage = null;
    foreach($details1['form_items'] as $k=>$fdet) {
      $pg = $fdet['page_num'];
      $has_pg = isset($result['subpages'][$pg]);
      $has_item = false;
      if($lastPage != $fdet['page_name']) {
        //to keep this file simple, we output this all the time (not knowing if there are any values on the page)
        if(!is_null($lastPage))
          echo "      </ul>\n    </li>\n";
        echo '    <li>'.$fdet['page_name'].'<ul>'."\n";
        $lastPage = $fdet['page_name'];
      }
      ob_start();
      // beware the repeatable forms!
      for($i = 0; $i<($has_pg?$result['subpages'][$pg]:1);$i++) {
        $_k = $k.($has_pg?'_'.$i:'');
        if(!isset($result[$_k]))
          continue;
        if($has_pg)
          echo '<br />'."\n        ";
        $has_item = true;
        if($has_pg)
          echo 'cp: '.($i+1).': ';
        $vals = array();
        if(isset($result[$_k]['value']) && !isset($result[$_k]['files']) && !isset($result[$_k]['comments'])) {
          // handle simple values
          $vals[] = $result[$_k]['value'];
        }
        if (isset($result[$_k]['files'])) {
          // handle images + other files like MP3's
          $files = [];
          foreach($result[$_k]['files'] as $f) {
            // $files[] = '<a target="_blank" href="'.$f['file_url'].'">'.(!empty($f['thumbnail_url']) ? '<img src="'.htmlspecialchars($f['thumbnail_url']).'" alt="" />' : basename($f['file_url'])).'</a>';
            $files[] = $f['file_url'];
          }
          $vals[] = implode(' ', $files);
        }
        if (isset($result[$_k]['comments'])) {
          // handle comments
          $vals[] = implode(", ", $result[$_k]['comments']);
        }
        array_push($temp_array_value, implode('<br />', $vals)); // truong hop Image-Comment moi implode
        echo implode('<br />', $vals);
      }
      $cnt = ob_get_clean();
      //let find out if we have any data!

      if($has_item || !empty($show_empty)) {
        array_push($temp_array_field, $fdet['name']);
        echo '      <li>'.$fdet['name'].': ';
        echo $cnt."\n";
        echo '      </li>'."\n";
      }
    }
    if(!is_null($lastPage))
      echo "      </ul>\n    </li>\n";
    echo '  </ul>';
    echo '</li>'."\n";
    // echo "Save field to array then print it to screen".'<br />';
    // foreach ($temp_array_field as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    // echo "Save value to array then print it to screen".'<br />';
    // foreach ($temp_array_value as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    
    $compare = array("Khu vực tưới",
          "Định vị khu vực tưới",
          "Ngày thu thập dữ liệu",
          "Lần tưới thứ",
          "Lượng nước dư ra tại chậu hứng (ml)",
          "Hình ảnh đo lượng nước dư ra",
          "pH trong chậu hứng",
          "Hình ảnh bút đo pH trong chậu hứng",
          "Hình ảnh biểu hiện bất thường (Hằng ngày)",
          "Chiều cao cây (cm) (Hằng tuần)",
          "Số lá mới (Hằng tuần)",
          "Kích thước lá (cm2) (Hằng tuần)",
          "Hình ảnh tổng quát của cây (Hằng ngày)",
          "Số hoa mới (Hằng tuần)",
          "Hình ảnh hoa (Hằng tuần)",
          "Ngày thu quả đợt 1 (ngày mà có khoảng 50% số cây có quả chín thu hoạch) (Hằng ngày)",
          "Trọng lượng quả (khi thu hoạch)",
          "Hình ảnh quả",
          "Trọng lượng quả (ghi nhận kết quả khi thu hoạch)",
          "Khối lượng quả trên cây (lấy số liệu khi kết thúc thu hoạch) (Hằng tuần)",
          "Độ cao mực nước trong bồn 1",
          "Hình ảnh mực nước bồn 1",
          "Độ cao mực nước trong bồn 2",
          "Hình ảnh mực nước bồn 2",
          "Độ cao mực nước trong bồn 3",
          "Hình ảnh mực nước bồn 3",
          "Độ cao mực nước trong bồn 4",
          "Hình ảnh mực nước bồn 4",
          "Độ cao mực nước trong bồn 5",
          "Hình ảnh mực nước bồn 5",
          "Ghi âm",
          "Đánh giá tình trạng phát triển của cây trồng (5 điểm tương ứng với tình trạng phát triển tốt nhất)"
          );
    // initial array to insert to mysql
    $record = array();
    $n_compare = count($compare);
    for ($i = 0; $i <$n_compare; $i++){
      $record[$i] = null; 
    }
    // Has temp_array_field and temp_array_value
    $n_value = count($temp_array_value);

    for ($i = 0; $i < $n_value; $i++){
      if ($temp_array_field[$i] == $compare[0]) {
          $record[0] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[1]) {
          $record[1] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[2]) {
          $record[2] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[3]) {
          $record[3] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[4]) {
          $record[4] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[5]) {
          $record[5] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[6]) {
          $record[6] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[7]) {
          $record[7] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[8]) {
          $record[8] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[9]) {
          $record[9] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[10]) {
          $record[10] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[11]) {
          $record[11] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[12]) {
          $record[12] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[13]) {
          $record[13] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[14]) {
          $record[14] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[15]) {
          $record[15] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[16]) {
          $record[16] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[17]) {
          $record[17] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[18]) {
          $record[18] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[19]) {
          $record[19] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[20]) {
          $record[20] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[21]) {
          $record[21] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[22]) {
          $record[22] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[23]) {
          $record[23] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[24]) {
          $record[24] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[25]) {
          $record[25] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[26]) {
          $record[26] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[27]) {
          $record[27] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[28]) {
          $record[28] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[29]) {
          $record[29] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[30]) {
          $record[30] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[31]) {
          $record[31] = $temp_array_value[$i];
      }
      else continue;
    }
    // foreach($record as $key => $value){
    //   echo $key.": ".$value.'<br />';
    // }
    //echo $key_local." id.". '<br />';
    $ar_gen = array();
    $ar_grow = array();
    $j_gen = 0;
    $j_grow = 0;
    for ($i = 0; $i<32; $i++){
        if($i < 8 || $i >19) {
            $ar_gen[$j_gen] = $record[$i];
            $j_gen++;
            //array_push($ar_gen, $ar[$i]);
            //echo $ar[$i]."gen"."$j_gen"."\n";
        }
        else {
            $ar_grow[$j_grow] = $record[$i];
            //array_push($ar_grow, $ar[$i]);
            $j_grow++;
            //echo $ar[$i]."grow"."$j_grow"."\n";
        }
    };
    // foreach ($ar_grow as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    // Integrate with MySQL(Insert)
    $servername = "sql313.byethost.com";
    $username = "b6_23747514";
    $password = "4zgm9d0w";
    $database = "b6_23747514_Form";
    $conn = mysqli_connect($servername, $username, $password, $database);
    $conn->set_charset("utf8");
    // Insert to Gen Table
    //$ar_gen[2]s
    $id = strtotime($ar_gen[2]);
    $farm_event_name = "Farmlacduong-".$result['event_name'];
    if (is_array_empty($ar_gen)){
      $sql = 'INSERT INTO Gen (id, Farm, Khu_vuc_tuoi, Dinh_vi_khuvuctuoi, Ngay_thu_thap_du_lieu, Lan_tuoi_thu, Luong_nuoc_du_ra, URL_Luong_nuoc_du_ra, pH_trong_chau_hung, URL_pH_trong_chau_hung, High_1, URL_High_1, High_2, URL_High_2, High_3, URL_High_3, High_4, URL_High_4, High_5, URL_High_5) 
        VALUES ("'.$id.'", "'.$farm_event_name.'","'.$ar_gen[0].'", "'.$ar_gen[1].'","'.$ar_gen[2].'",
          "'.$ar_gen[3].'", "'.$ar_gen[4].'", "'.$ar_gen[5].'", "'.$ar_gen[6].'", "'.$ar_gen[7].'",
          "'.$ar_gen[8].'", "'.$ar_gen[9].'", "'.$ar_gen[10].'", "'.$ar_gen[11].'", "'.$ar_gen[12].'", "'.$ar_gen[13].'", "'.$ar_gen[14].'", "'.$ar_gen[15].'", "'.$ar_gen[16].'", "'.$ar_gen[17].'"
      )';
      mysqli_query($conn, $sql);
    }
    if(is_array_empty($ar_grow)){
      $sql = 'INSERT INTO Grow_ot_chuong (
        id, URL_Hinh_anh_bat_thuong, Chieu_cao_cay, So_la_moi, Kich_thuoc_la, URL_Hinh_anh_tong_quat, So_hoa_moi, URL_Hinh_anh_cua_hoa, Ngay_thu_qua_dot_1, Trong_luong_qua, URL_Hinh_anh_qua, Trong_luong_qua_1, Khoi_luong_qua_tren_cay
      ) 
        VALUES ("'.$id.'", "'.$ar_grow[0].'", "'.$ar_grow[1].'","'.$ar_grow[2].'",
          "'.$ar_grow[3].'", "'.$ar_grow[4].'", "'.$ar_grow[5].'","'.$ar_grow[6].'", "'.$ar_grow[7].'","'.$ar_grow[8].'", "'.$ar_grow[9].'", "'.$ar_grow[10].'", "'.$ar_grow[11].'"
      )';
      mysqli_query($conn, $sql);
    }
    // Insert to Grow Table
    mysqli_close($conn);
  }
  echo '</ul>'."\n";
  echo $paging;
} else {
    echo '<p>The form '.$details1['form']['name'].' appears to have no responses</p>'."\n";
  }
// echo '<h3 style="cursor: pointer" onclick="var e = document.getElementById(\'hiddenpre\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">Parsed data (click to toggle):</h3>';
// echo '<pre id="hiddenpre" style="display: none">';
// var_dump($details1);
// echo '</pre>';
// echo '<h3 style="cursor: pointer" onclick="var e = document.getElementById(\'hiddencode\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">JSON string (click to toggle):</h3>'."\n";
// echo '<pre id="hiddencode" style="display: none">';
// echo $response1Stream."";
// echo '</pre>';

/////////////////////////////////////////// CAY DUA CHUOT - FARM QUANG NINH
echo "FARM QUANH NINH - CAY DUA CHUOT".'<br />';
$f_i = 36985; // cay ot chuong - farm lac duong
$response2 = $client->send(
    $provider->getAuthenticatedRequest(
      'GET',
      $host.'report/'.((int)$f_i),
      $accessToken
    )
);
$response2Stream = $response2->getBody();
$details2 = json_decode($response2Stream, true);

// show some form info
// echo '<h1>'.htmlspecialchars($details2['form']['name']);
// if(!empty($details2['form']['is_frequent']))
//   echo ' (Frequent form)';
// if(!empty($details2['form']['is_public']))
//   echo ' (Public form)';
// echo ' <a href="?"> &laquo; back to list of Forms</a></h1>';
// echo '<p>';
// if(!empty($details2['form']['reports_count']))
//   echo 'Total Responses: '.$details2['form']['reports_count'].'<br />';
// if(!empty($details2['form']['approved_count']))
//   echo 'Approved Responses: '.$details2['form']['approved_count'].'<br />';
// if(!empty($details2['form']['awaiting_approval_count']))
//   echo 'Not-approved Responses: '.$details2['form']['awaiting_approval_count'].'<br />';
// if(!empty($details2['form_items']))
//   echo 'Total Form Items: '.count($details2['form_items']).'<br />';
// echo '</p>';
// // show form responses
// echo '<h3>Received responses:</h3>';
// foreach ($details2['form_items'] as $key => $value) {
//   # code...
//   echo $value['name'].' '.$value['page_num'].'<br />';
// }

if(!empty($details2['data'])) {
  $paging = '';
  if(!empty($_REQUEST['pg']) && $_REQUEST['pg'] > 0) {
    $paging .= '<a href="?fid='.((int)$_REQUEST['fid']).'&pg='.(((int)$_REQUEST['pg']) - 1).'">&lt; Previous Page</a>';
  }
  if((empty($_REQUEST['pg']) && $details2['form']['reports_count'] > 5000) || (floor($details2['form']['reports_count'] / 5000) > (isset($_REQUEST['pg']) ? $_REQUEST['pg'] : 0))) {
    $paging .= '<a href="?fid='.((int)$_REQUEST['fid']).'&pg='.(isset($_REQUEST['pg']) ? $_REQUEST['pg'] + 1 : 1).'">Next Page &gt;</a>';
  }
  echo $paging;
  echo '<ul>'."\n";
  foreach($details2['data'] as $key_local => $result) {
    //$result is one response
    $temp_array_value = array();
    $temp_array_field = array();

    echo '  <li><div style="cursor: pointer" title="Click to show values" onclick="var e = this.nextSibling; e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">'.(!empty($result['event_name']) ? $result['event_name'] : '-- empty --').(!empty($result['member_label']) ? ' ('.$result['member_label'].')' : '').'</div>';
    echo '<ul style="display: none;">'."\n";
    // now let's loop over the form_items rather then the $result['data'], so we can have the items in correct order
    $lastPage = null;
    foreach($details2['form_items'] as $k=>$fdet) {
      $pg = $fdet['page_num'];
      $has_pg = isset($result['subpages'][$pg]);
      $has_item = false;
      if($lastPage != $fdet['page_name']) {
        //to keep this file simple, we output this all the time (not knowing if there are any values on the page)
        if(!is_null($lastPage))
          echo "      </ul>\n    </li>\n";
        echo '    <li>'.$fdet['page_name'].'<ul>'."\n";
        $lastPage = $fdet['page_name'];
      }
      ob_start();
      // beware the repeatable forms!
      for($i = 0; $i<($has_pg?$result['subpages'][$pg]:1);$i++) {
        $_k = $k.($has_pg?'_'.$i:'');
        if(!isset($result[$_k]))
          continue;
        if($has_pg)
          echo '<br />'."\n        ";
        $has_item = true;
        if($has_pg)
          echo 'cp: '.($i+1).': ';
        $vals = array();
        if(isset($result[$_k]['value']) && !isset($result[$_k]['files']) && !isset($result[$_k]['comments'])) {
          // handle simple values
          $vals[] = $result[$_k]['value'];
        }
        if (isset($result[$_k]['files'])) {
          // handle images + other files like MP3's
          $files = [];
          foreach($result[$_k]['files'] as $f) {
            // $files[] = '<a target="_blank" href="'.$f['file_url'].'">'.(!empty($f['thumbnail_url']) ? '<img src="'.htmlspecialchars($f['thumbnail_url']).'" alt="" />' : basename($f['file_url'])).'</a>';
            $files[] = $f['file_url'];
          }
          $vals[] = implode(' ', $files);
        }
        if (isset($result[$_k]['comments'])) {
          // handle comments
          $vals[] = implode(", ", $result[$_k]['comments']);
        }
        array_push($temp_array_value, implode('<br />', $vals)); // truong hop Image-Comment moi implode
        echo implode('<br />', $vals);
      }
      $cnt = ob_get_clean();
      //let find out if we have any data!

      if($has_item || !empty($show_empty)) {
        array_push($temp_array_field, $fdet['name']);
        echo '      <li>'.$fdet['name'].': ';
        echo $cnt."\n";
        echo '      </li>'."\n";
      }
    }
    if(!is_null($lastPage))
      echo "      </ul>\n    </li>\n";
    echo '  </ul>';
    echo '</li>'."\n";
    // echo "Save field to array then print it to screen".'<br />';
    // foreach ($temp_array_field as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    // echo "Save value to array then print it to screen".'<br />';
    // foreach ($temp_array_value as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    
    $compare = array("Khu vực tưới",
      "Định vị khu vực tưới",
      "Ngày thu thập dữ liệu",
      "Lần tưới thứ",
      "Lượng nước dư ra (ml)",
      "Hình ảnh đo lượng nước dư ra",
      "pH trong chậu hứng",
      "Hình ảnh bút đo pH trong chậu hứng",
      "Hình ảnh biểu hiện bất thường",
      "Chiều cao cây",
      "Số lá mới",
      "Kích thước lá (cm2)",
      "Chiều dài lóng thân (cm)",
      "Hình ảnh tổng quát của cây",
      "Số hoa mới",
      "Hình ảnh của hoa",
      "Hình ảnh của quả",
      "Sinh trưởng và phát triển của rễ (Quan sát, đánh giá trực quan)",
      "Cân trọng lượng rễ so với trọng lượng thân lá và so sánh tỉ lệ",
      "Hình ảnh sinh trưởng và phát triển của rễ",
      "Độ cao mực nước trong bồn 1",
      "Hình ảnh mực nước bồn 1",
      "Độ cao mực nước trong bồn 2",
      "Hình ảnh mực nước bồn 2",
      "Độ cao mực nước trong bồn 3",
      "Hình ảnh mực nước bồn 3",
      "Độ cao mực nước trong bồn 4",
      "Hình ảnh mực nước bồn 4",
      "Độ cao mực nước trong bồn 5",
      "Hình ảnh mực nước bồn 5",
      "Đánh giá tình trạng phát triển của cây trồng (5 điểm tương ứng với tình trạng phát triển tốt nhất)",
      "Ghi chú"
      );
    // initial array to insert to mysql
    $record = array();
    $n_compare = count($compare);
    for ($i = 0; $i <$n_compare; $i++){
      $record[$i] = null; 
    }
    // Has temp_array_field and temp_array_value
    $n_value = count($temp_array_value);

    for ($i = 0; $i < $n_value; $i++){
      if ($temp_array_field[$i] == $compare[0]) {
          $record[0] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[1]) {
          $record[1] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[2]) {
          $record[2] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[3]) {
          $record[3] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[4]) {
          $record[4] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[5]) {
          $record[5] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[6]) {
          $record[6] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[7]) {
          $record[7] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[8]) {
          $record[8] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[9]) {
          $record[9] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[10]) {
          $record[10] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[11]) {
          $record[11] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[12]) {
          $record[12] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[13]) {
          $record[13] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[14]) {
          $record[14] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[15]) {
          $record[15] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[16]) {
          $record[16] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[17]) {
          $record[17] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[18]) {
          $record[18] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[19]) {
          $record[19] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[20]) {
          $record[20] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[21]) {
          $record[21] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[22]) {
          $record[22] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[23]) {
          $record[23] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[24]) {
          $record[24] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[25]) {
          $record[25] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[26]) {
          $record[26] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[27]) {
          $record[27] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[28]) {
          $record[28] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[29]) {
          $record[29] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[30]) {
          $record[30] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[31]) {
          $record[31] = $temp_array_value[$i];
      }
      else continue;
    }
    // foreach($record as $key => $value){
    //   echo $key.": ".$value.'<br />';
    // }
    //echo $key_local." id.". '<br />';
    $ar_gen = array();
    $ar_grow = array();
    $j_gen = 0;
    $j_grow = 0;
    for ($i = 0; $i<32; $i++){
        if($i < 8 || $i >19) {
            $ar_gen[$j_gen] = $record[$i];
            $j_gen++;
            //array_push($ar_gen, $ar[$i]);
            //echo $ar[$i]."gen"."$j_gen"."\n";
        }
        else {
            $ar_grow[$j_grow] = $record[$i];
            //array_push($ar_grow, $ar[$i]);
            $j_grow++;
            //echo $ar[$i]."grow"."$j_grow"."\n";
        }
    };
    // foreach ($ar_grow as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    // Integrate with MySQL(Insert)
    $servername = "sql313.byethost.com";
    $username = "b6_23747514";
    $password = "4zgm9d0w";
    $database = "b6_23747514_Form";
    $conn = mysqli_connect($servername, $username, $password, $database);
    $conn->set_charset("utf8");
    // Insert to Gen Table
    //$ar_gen[2]s
    $id = strtotime($ar_gen[2]);
    $farm_event_name = "Farmquanhninh-".$result['event_name'];
    if (is_array_empty($ar_gen)){
      $sql = 'INSERT INTO Gen (id, Farm, Khu_vuc_tuoi, Dinh_vi_khuvuctuoi, Ngay_thu_thap_du_lieu, Lan_tuoi_thu, Luong_nuoc_du_ra, URL_Luong_nuoc_du_ra, pH_trong_chau_hung, URL_pH_trong_chau_hung, High_1, URL_High_1, High_2, URL_High_2, High_3, URL_High_3, High_4, URL_High_4, High_5, URL_High_5) 
        VALUES ("'.$id.'", "'.$farm_event_name.'","'.$ar_gen[0].'", "'.$ar_gen[1].'","'.$ar_gen[2].'",
          "'.$ar_gen[3].'", "'.$ar_gen[4].'", "'.$ar_gen[5].'", "'.$ar_gen[6].'", "'.$ar_gen[7].'",
          "'.$ar_gen[8].'", "'.$ar_gen[9].'", "'.$ar_gen[10].'", "'.$ar_gen[11].'", "'.$ar_gen[12].'", "'.$ar_gen[13].'", "'.$ar_gen[14].'", "'.$ar_gen[15].'", "'.$ar_gen[16].'", "'.$ar_gen[17].'"
      )';
      mysqli_query($conn, $sql);
    }
    if(is_array_empty($ar_grow)){
      $sql = 'INSERT INTO Grow_dua_chuot (
        id, URL_Hinh_anh_bat_thuong, Chieu_cao_cay, So_la_moi, Kich_thuoc_la, Chieu_dai_long_than, URL_Hinh_anh_tong_quat, So_hoa_moi, URL_Hinh_anh_cua_hoa, URL_Hinh_anh_cua_qua, Sinh_truong_va_phat_trien_cua_re, Ty_le_re_va_than_la
      ) 
        VALUES ("'.$id.'", "'.$ar_grow[0].'", "'.$ar_grow[1].'","'.$ar_grow[2].'",
          "'.$ar_grow[3].'", "'.$ar_grow[4].'", "'.$ar_grow[5].'","'.$ar_grow[6].'", "'.$ar_grow[7].'","'.$ar_grow[8].'", "'.$ar_grow[9].'", "'.$ar_grow[10].'"
      )';
      mysqli_query($conn, $sql);
    }
    // Insert to Grow Table
    mysqli_close($conn);
  }
  echo '</ul>'."\n";
  echo $paging;
} else {
    echo '<p>The form '.$details2['form']['name'].' appears to have no responses</p>'."\n";
  }
// echo '<h3 style="cursor: pointer" onclick="var e = document.getElementById(\'hiddenpre\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">Parsed data (click to toggle):</h3>';
// echo '<pre id="hiddenpre" style="display: none">';
// var_dump($details2);
// echo '</pre>';
// echo '<h3 style="cursor: pointer" onclick="var e = document.getElementById(\'hiddencode\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">JSON string (click to toggle):</h3>'."\n";
// echo '<pre id="hiddencode" style="display: none">';
// echo $response2Stream."";
// echo '</pre>';

/////////////////////////////////////////// CAY DUA LUOI - FARM LONG THANH

$f_i = 36961; // cay ot chuong - farm lac duong
$response3 = $client->send(
    $provider->getAuthenticatedRequest(
      'GET',
      $host.'report/'.((int)$f_i),
      $accessToken
    )
);
$response3Stream = $response3->getBody();
$details3 = json_decode($response3Stream, true);

// show some form info
// echo '<h1>'.htmlspecialchars($details3['form']['name']);
// if(!empty($details3['form']['is_frequent']))
//   echo ' (Frequent form)';
// if(!empty($details3['form']['is_public']))
//   echo ' (Public form)';
// echo ' <a href="?"> &laquo; back to list of Forms</a></h1>';
// echo '<p>';
// if(!empty($details3['form']['reports_count']))
//   echo 'Total Responses: '.$details3['form']['reports_count'].'<br />';
// if(!empty($details3['form']['approved_count']))
//   echo 'Approved Responses: '.$details3['form']['approved_count'].'<br />';
// if(!empty($details3['form']['awaiting_approval_count']))
//   echo 'Not-approved Responses: '.$details3['form']['awaiting_approval_count'].'<br />';
// if(!empty($details3['form_items']))
//   echo 'Total Form Items: '.count($details3['form_items']).'<br />';
// echo '</p>';
// // show form responses
// echo '<h3>Received responses:</h3>';
// foreach ($details3['form_items'] as $key => $value) {
//   # code...
//   echo $value['name'].' '.$value['page_num'].'<br />';
// }

if(!empty($details3['data'])) {
  $paging = '';
  if(!empty($_REQUEST['pg']) && $_REQUEST['pg'] > 0) {
    $paging .= '<a href="?fid='.((int)$_REQUEST['fid']).'&pg='.(((int)$_REQUEST['pg']) - 1).'">&lt; Previous Page</a>';
  }
  if((empty($_REQUEST['pg']) && $details3['form']['reports_count'] > 5000) || (floor($details3['form']['reports_count'] / 5000) > (isset($_REQUEST['pg']) ? $_REQUEST['pg'] : 0))) {
    $paging .= '<a href="?fid='.((int)$_REQUEST['fid']).'&pg='.(isset($_REQUEST['pg']) ? $_REQUEST['pg'] + 1 : 1).'">Next Page &gt;</a>';
  }
  echo $paging;
  echo '<ul>'."\n";
  foreach($details3['data'] as $key_local => $result) {
    //$result is one response
    $temp_array_value = array();
    $temp_array_field = array();

    echo '  <li><div style="cursor: pointer" title="Click to show values" onclick="var e = this.nextSibling; e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">'.(!empty($result['event_name']) ? $result['event_name'] : '-- empty --').(!empty($result['member_label']) ? ' ('.$result['member_label'].')' : '').'</div>';
    echo '<ul style="display: none;">'."\n";
    // now let's loop over the form_items rather then the $result['data'], so we can have the items in correct order
    $lastPage = null;
    foreach($details3['form_items'] as $k=>$fdet) {
      $pg = $fdet['page_num'];
      $has_pg = isset($result['subpages'][$pg]);
      $has_item = false;
      if($lastPage != $fdet['page_name']) {
        //to keep this file simple, we output this all the time (not knowing if there are any values on the page)
        if(!is_null($lastPage))
          echo "      </ul>\n    </li>\n";
        echo '    <li>'.$fdet['page_name'].'<ul>'."\n";
        $lastPage = $fdet['page_name'];
      }
      ob_start();
      // beware the repeatable forms!
      for($i = 0; $i<($has_pg?$result['subpages'][$pg]:1);$i++) {
        $_k = $k.($has_pg?'_'.$i:'');
        if(!isset($result[$_k]))
          continue;
        if($has_pg)
          echo '<br />'."\n        ";
        $has_item = true;
        if($has_pg)
          echo 'cp: '.($i+1).': ';
        $vals = array();
        if(isset($result[$_k]['value']) && !isset($result[$_k]['files']) && !isset($result[$_k]['comments'])) {
          // handle simple values
          $vals[] = $result[$_k]['value'];
        }
        if (isset($result[$_k]['files'])) {
          // handle images + other files like MP3's
          $files = [];
          foreach($result[$_k]['files'] as $f) {
            // $files[] = '<a target="_blank" href="'.$f['file_url'].'">'.(!empty($f['thumbnail_url']) ? '<img src="'.htmlspecialchars($f['thumbnail_url']).'" alt="" />' : basename($f['file_url'])).'</a>';
            $files[] = $f['file_url'];
          }
          $vals[] = implode(' ', $files);
        }
        if (isset($result[$_k]['comments'])) {
          // handle comments
          $vals[] = implode(", ", $result[$_k]['comments']);
        }
        array_push($temp_array_value, implode('<br />', $vals)); // truong hop Image-Comment moi implode
        echo implode('<br />', $vals);
      }
      $cnt = ob_get_clean();
      //let find out if we have any data!

      if($has_item || !empty($show_empty)) {
        array_push($temp_array_field, $fdet['name']);
        echo '      <li>'.$fdet['name'].': ';
        echo $cnt."\n";
        echo '      </li>'."\n";
      }
    }
    if(!is_null($lastPage))
      echo "      </ul>\n    </li>\n";
    echo '  </ul>';
    echo '</li>'."\n";
    // echo "Save field to array then print it to screen".'<br />';
    // foreach ($temp_array_field as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    // echo "Save value to array then print it to screen".'<br />';
    // foreach ($temp_array_value as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    
    $compare = array("Khu vực tưới",
      "Định vị khu vực tưới",
      "Ngày thu thập dữ liệu",
      "Lần tưới thứ",
      "Lượng nước dư ra (ml)",
      "Hình ảnh đo lượng nước dư ra",
      "pH trong chậu hứng",
      "Hình ảnh bút đo pH trong chậu hứng",
      "Hình ảnh biểu hiện bất thường",
      "Chiều cao cây",
      "Số lá mới",
      "Kích thước lá (cm2)",
      "Chiều dài lóng thân (cm)",
      "Hình ảnh tổng quát của cây",
      "Số hoa mới",
      "Hình ảnh của hoa",
      "Hình ảnh của quả",
      "Sinh trưởng và phát triển của rễ (Quan sát, đánh giá trực quan)",
      "Cân trọng lượng rễ so với trọng lượng thân lá và so sánh tỉ lệ",
      "Hình ảnh sinh trưởng và phát triển của rễ",
      "Độ cao mực nước trong bồn 1",
      "Hình ảnh mực nước bồn 1",
      "Độ cao mực nước trong bồn 2",
      "Hình ảnh mực nước bồn 2",
      "Độ cao mực nước trong bồn 3",
      "Hình ảnh mực nước bồn 3",
      "Độ cao mực nước trong bồn 4",
      "Hình ảnh mực nước bồn 4",
      "Độ cao mực nước trong bồn 5",
      "Hình ảnh mực nước bồn 5",
      "Đánh giá tình trạng phát triển của cây trồng (5 điểm tương ứng với tình trạng phát triển tốt nhất)",
      "Ghi chú"
      );
    // initial array to insert to mysql
    $record = array();
    $n_compare = count($compare);
    for ($i = 0; $i <$n_compare; $i++){
      $record[$i] = null; 
    }
    // Has temp_array_field and temp_array_value
    $n_value = count($temp_array_value);

    for ($i = 0; $i < $n_value; $i++){
      if ($temp_array_field[$i] == $compare[0]) {
          $record[0] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[1]) {
          $record[1] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[2]) {
          $record[2] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[3]) {
          $record[3] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[4]) {
          $record[4] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[5]) {
          $record[5] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[6]) {
          $record[6] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[7]) {
          $record[7] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[8]) {
          $record[8] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[9]) {
          $record[9] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[10]) {
          $record[10] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[11]) {
          $record[11] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[12]) {
          $record[12] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[13]) {
          $record[13] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[14]) {
          $record[14] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[15]) {
          $record[15] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[16]) {
          $record[16] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[17]) {
          $record[17] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[18]) {
          $record[18] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[19]) {
          $record[19] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[20]) {
          $record[20] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[21]) {
          $record[21] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[22]) {
          $record[22] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[23]) {
          $record[23] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[24]) {
          $record[24] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[25]) {
          $record[25] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[26]) {
          $record[26] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[27]) {
          $record[27] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[28]) {
          $record[28] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[29]) {
          $record[29] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[30]) {
          $record[30] = $temp_array_value[$i];
      }
      else if ($temp_array_field[$i] == $compare[31]) {
          $record[31] = $temp_array_value[$i];
      }
      else continue;
    }
    // foreach($record as $key => $value){
    //   echo $key.": ".$value.'<br />';
    // }
    //echo $key_local." id.". '<br />';
    $ar_gen = array();
    $ar_grow = array();
    $j_gen = 0;
    $j_grow = 0;
    for ($i = 0; $i<32; $i++){
        if($i < 8 || $i >19) {
            $ar_gen[$j_gen] = $record[$i];
            $j_gen++;
            //array_push($ar_gen, $ar[$i]);
            //echo $ar[$i]."gen"."$j_gen"."\n";
        }
        else {
            $ar_grow[$j_grow] = $record[$i];
            //array_push($ar_grow, $ar[$i]);
            $j_grow++;
            //echo $ar[$i]."grow"."$j_grow"."\n";
        }
    };
    // foreach ($ar_grow as $key => $value) {
    //   # code...
    //   echo $key.": ".$value.'<br />';
    // }
    // Integrate with MySQL(Insert)
    $servername = "sql313.byethost.com";
    $username = "b6_23747514";
    $password = "4zgm9d0w";
    $database = "b6_23747514_Form";
    $conn = mysqli_connect($servername, $username, $password, $database);
    $conn->set_charset("utf8");
    // Insert to Gen Table
    //$ar_gen[2]s
    $id = strtotime($ar_gen[2]);
    $farm_event_name = "Farmquanhninh-".$result['event_name'];
    if (is_array_empty($ar_gen)){
      $sql = 'INSERT INTO Gen (id, Farm, Khu_vuc_tuoi, Dinh_vi_khuvuctuoi, Ngay_thu_thap_du_lieu, Lan_tuoi_thu, Luong_nuoc_du_ra, URL_Luong_nuoc_du_ra, pH_trong_chau_hung, URL_pH_trong_chau_hung, High_1, URL_High_1, High_2, URL_High_2, High_3, URL_High_3, High_4, URL_High_4, High_5, URL_High_5) 
        VALUES ("'.$id.'", "'.$farm_event_name.'","'.$ar_gen[0].'", "'.$ar_gen[1].'","'.$ar_gen[2].'",
          "'.$ar_gen[3].'", "'.$ar_gen[4].'", "'.$ar_gen[5].'", "'.$ar_gen[6].'", "'.$ar_gen[7].'",
          "'.$ar_gen[8].'", "'.$ar_gen[9].'", "'.$ar_gen[10].'", "'.$ar_gen[11].'", "'.$ar_gen[12].'", "'.$ar_gen[13].'", "'.$ar_gen[14].'", "'.$ar_gen[15].'", "'.$ar_gen[16].'", "'.$ar_gen[17].'"
      )';
      mysqli_query($conn, $sql);
    }
    if(is_array_empty($ar_grow)){
      $sql = 'INSERT INTO Grow_dua_chuot (
        id, URL_Hinh_anh_bat_thuong, Chieu_cao_cay, So_la_moi, Kich_thuoc_la, Chieu_dai_long_than, URL_Hinh_anh_tong_quat, So_hoa_moi, URL_Hinh_anh_cua_hoa, URL_Hinh_anh_cua_qua, Sinh_truong_va_phat_trien_cua_re, Ty_le_re_va_than_la
      ) 
        VALUES ("'.$id.'", "'.$ar_grow[0].'", "'.$ar_grow[1].'","'.$ar_grow[2].'",
          "'.$ar_grow[3].'", "'.$ar_grow[4].'", "'.$ar_grow[5].'","'.$ar_grow[6].'", "'.$ar_grow[7].'","'.$ar_grow[8].'", "'.$ar_grow[9].'", "'.$ar_grow[10].'"
      )';
      mysqli_query($conn, $sql);
    }
    // Insert to Grow Table
    mysqli_close($conn);
  }
  echo '</ul>'."\n";
  echo $paging;
} else {
    echo '<p>The form '.$details3['form']['name'].' appears to have no responses</p>'."\n";
  }
// echo '<h3 style="cursor: pointer" onclick="var e = document.getElementById(\'hiddenpre\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">Parsed data (click to toggle):</h3>';
// echo '<pre id="hiddenpre" style="display: none">';
// var_dump($details3);
// echo '</pre>';
// echo '<h3 style="cursor: pointer" onclick="var e = document.getElementById(\'hiddencode\'); e.style.display = e.style.display === \'none\' ? \'block\' : \'none\';">JSON string (click to toggle):</h3>'."\n";
// echo '<pre id="hiddencode" style="display: none">';
// echo $response3Stream."";
// echo '</pre>';



////////////////////////////////////////
function is_array_empty($arr){
  if(is_array($arr)){     
      foreach($arr as $key => $value){
          if(!empty($value) || $value != NULL || $value != ""){
              return true;
              break;//stop the process we have seen that at least 1 of the array has value so its not empty
          }
      }
      return false;
  }
}
?>