<?php
  include_once('includes/connect_database.php'); 
?>

<?php

error_reporting(E_ALL & ~E_NOTICE);
@ini_set('post_max_size', '64M');
@ini_set('upload_max_filesize', '64M');
@ini_set('max_file_uploads', '100');

// database constants
define('DB_DRIVER', 'mysql');
define('DB_SERVER', $host);
define('DB_SERVER_USERNAME', $user);
define('DB_SERVER_PASSWORD', $pass);
define('DB_DATABASE', $database);

$dboptions = array(
    PDO::ATTR_PERSISTENT => FALSE,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
  $DB = new PDO(DB_DRIVER . ':host=' . DB_SERVER . ';dbname=' . DB_DATABASE, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, $dboptions);
} catch (Exception $ex) {
  echo $ex->getMessage();
  die;
}

if (isset($_POST["sub2"])) {

  $category_name = $_POST['category_name'];
  // include resized library
  require_once('./php-image-magician/php_image_magician.php');
  $msg = "";
  $valid_image_check = array("image/gif", "image/jpeg", "image/jpg", "image/png", "image/bmp");
  if (count($_FILES["user_files"]) > 0) {
    $folderName = "upload/";

    $sql = "INSERT INTO tbl_gallery (cat_id, image) VALUES ($category_name, :img)";
    $stmt = $DB->prepare($sql);

    for ($i = 0; $i < count($_FILES["user_files"]["name"]); $i++) {

      if ($_FILES["user_files"]["name"][$i] <> "") {

        $image_mime = strtolower(image_type_to_mime_type(exif_imagetype($_FILES["user_files"]["tmp_name"][$i])));
        // if valid image type then upload
        if (in_array($image_mime, $valid_image_check)) {

          $ext = explode("/", strtolower($image_mime));
          $ext = strtolower(end($ext));
          $filename = rand(10000, 990000) . '_' . time() . '.' . $ext;
          $filepath = $folderName . $filename;

          if (!move_uploaded_file($_FILES["user_files"]["tmp_name"][$i], $filepath)) {
            $emsg .= "Failed to upload <strong>" . $_FILES["user_files"]["name"][$i] . "</strong>. <br>";
            $counter++;
          } else {
            $smsg .= "<strong>" . $_FILES["user_files"]["name"][$i] . "</strong> uploaded successfully. <br>";

            $magicianObj = new imageLib($filepath);
            $magicianObj->resizeImage(300, 300);
            $magicianObj->saveImage($folderName . 'thumbs/' . $filename, 100);

            /*             * ****** insert into database starts ******** */
            try {
              $stmt->bindValue(":img", $filename);
              $stmt->execute();
              $result = $stmt->rowCount();
              if ($result > 0) {
                // file uplaoded successfully.
              } else {
                // failed to insert into database.
              }
            } catch (Exception $ex) {
              $emsg .= "<strong>" . $ex->getMessage() . "</strong>. <br>";
            }
            /*             * ****** insert into database ends ******** */
          }
        } else {
          $emsg .= "<strong>" . $_FILES["user_files"]["name"][$i] . "</strong> not a valid image. <br>";
        }
      }
    }


    $msg .= (strlen($smsg) > 0) ? successMessage($smsg) : "";
    $msg .= (strlen($emsg) > 0) ? errorMessage($emsg) : "";
  } else {
    $msg = errorMessage("You must upload atleast one file");
  }
}
?>

  <!-- START CONTENT -->
    <section id="content">

        <!--breadcrumbs start-->
        <div id="breadcrumbs-wrapper" class=" grey lighten-3">
            <div class="container">
              <div class="row">
                  <div class="col s12 m12 l12">
                    <h5 class="breadcrumbs-title">Add Multiple Image</h5>
                    <ol class="breadcrumb">
                      <li><a href="dashboard.php">Dashboard</a>
                      </li>
                      <li><a href="#" class="active">Add Multiple Image</a>
                      </li>
                    </ol>
                  </div>
              </div>
            </div>
        </div>


        <!--start container-->
        <div class="container">
            <div class="section">
              <div class="row">
                <div class="col s12 m12 l12">
                    <div class="card-panel">
                      <div class="row">
                        <form method="post" class="col s12" enctype="multipart/form-data">
                            <div class="row">
                            <?php echo $msg; ?>
                              <div class="input-field col s12">   

                                <div class="row">
                                  <div class="input-field col s12">
                                    <?php
                                        include('includes/variables.php');
                                        include('includes/connect_database.php'); 

                                        $sql = "SELECT cid, category_name FROM tbl_category ORDER BY category_name ASC";
                                                $query = mysqli_query($connect, $sql);

                                                echo "<select name='category_name' id='category_name'>";
                                          while ($row = mysqli_fetch_array($query)) {
                                              echo "<option value='" . $row['cid'] ."'>" . $row['category_name'] ."</option>";
                                          }
                                          echo "</select>";

                                        include('includes/close_database.php'); 
                                    ?>                                                
                                  </div>  
                                </div>

                                <div class="file-field input-field col s12">
                                    <input class="file-path validate" type="text" disabled/>
                                        <div class="btn">
                                            <span>Multiple Images (Max : 20)</span>
                                            <input type="file" name="user_files[]" type="file" multiple="multiple" required/>
                                        </div>
                                </div>

                                <button class="btn cyan waves-effect waves-light right" type="submit" name="sub2">Submit
                                    <i class="mdi-content-send right"></i>
                                </button>                                             

                              </div>   
                                    
                        </div>
                    </form>
                    <?php
                    // fetch all records
                    $sql = "SELECT * FROM tbl_gallery WHERE 1 ";
                    try {
                      $stmt = $DB->prepare($sql);
                      $stmt->execute();
                      $images = $stmt->fetchAll();
                    } catch (Exception $ex) {
                      echo $ex->getMessage();
                    }
                    ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  </section>

<?php

function errorMessage($str) {
  return '<div style="width:50%; margin:0 auto; color:#000; margin-top:10px; text-align:center;">' . $str . '</div>';
}

function successMessage($str) {
  return '<div style="width:50%; margin:0 auto; color:#000; margin-top:10px; text-align:center;">' . $str . '</div>';
}
?>

<br>
<br>
<br>
<br>
<br>
<br>
<?php include_once('includes/close_database.php'); ?>