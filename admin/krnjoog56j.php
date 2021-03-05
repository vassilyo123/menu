<?php
/**
 * QuickQR - Digital QR Menu
 * @author Bylancer
 * @version 5.0
 * @Updated Date: 04/Dec/2020
 * @Copyright 2015-20 Bylancer
 */
require_once('../includes/config.php');
require_once('../includes/sql_builder/idiorm.php');
require_once('../includes/db.php');
require_once('../includes/classes/class.template_engine.php');
require_once('../includes/lib/HTMLPurifier/HTMLPurifier.standalone.php');
require_once('../includes/functions/func.global.php');
require_once('../includes/functions/func.admin.php');
require_once('../includes/functions/func.sqlquery.php');
require_once('../includes/functions/func.users.php');
require_once('../includes/classes/GoogleTranslate.php');
require_once('../includes/lang/lang_'.$config['lang'].'.php');
 
$con = db_connect();
admin_session_start();
if (!isset($_SESSION['admin']['id'])) {
    exit('Access Denied.');
}
 
// Check if SSL enabled
if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
    $ssl = $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https";
else
    $ssl = !empty($_SERVER['HTTPS']) && $_SERVER["HTTPS"] != "off";
 
define("SSL_ENABLED", $ssl);
 
// Define SITEURL
$site_url = (SSL_ENABLED ? "https" : "http")
    . "://"
    . $_SERVER["SERVER_NAME"]
    . (dirname($_SERVER["SCRIPT_NAME"]) == DIRECTORY_SEPARATOR ? "" : "/")
    . trim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");
 
define("SITEURL", $site_url);
define("ROOTPATH", dirname(__DIR__));
 
$config['site_url'] = dirname($site_url)."/";
require_once('../includes/seo-url.php');
 
//Admin Ajax Function
if(isset($_GET['action'])){
 
 
    if ($_GET['action'] == "installPayment") { installPayment(); }
    if ($_GET['action'] == "uninstallPayment") { uninstallPayment(); }
 
    if ($_GET['action'] == "deleteStaticPage") { deleteStaticPage(); }
    if ($_GET['action'] == "deletefaq") { deletefaq(); }
 
    if ($_GET['action'] == "activeuser") { activeuser(); }
    if ($_GET['action'] == "banuser") { banuser(); }
 
    if ($_GET['action'] == "deleteCurrency") { deleteCurrency(); }
    if ($_GET['action'] == "deleteTimezone") { deleteTimezone(); }
    if ($_GET['action'] == "deleteMembershipPlan") { deleteMembershipPlan(); }
    if ($_GET['action'] == "deletePackage") { deletePackage(); }
    if ($_GET['action'] == "deleteLanguage") { deleteLanguage(); }
    if ($_GET['action'] == "deleteadmin") { deleteadmin(); }
    if ($_GET['action'] == "deleteTransaction") { deleteTransaction(); }
    if ($_GET['action'] == "deleteTaxes") { deleteTaxes(); }
 
    if ($_GET['action'] == "addPlanCustom") {addPlanCustom();}
    if ($_GET['action'] == "editPlanCustom") {editPlanCustom();}
    if ($_GET['action'] == "delPlanCustom") {delPlanCustom();}
    if ($_GET['action'] == "langTranslation_PlanCustom") { langTranslation_PlanCustom(); }
    if ($_GET['action'] == "edit_langTranslation_PlanCustom") { edit_langTranslation_PlanCustom(); }
 
    if ($_GET['action'] == "edit_langTranslation") { edit_langTranslation(); }
    if ($_GET['action'] == "langTranslation_FormFields") { langTranslation_FormFields(); }
 
    if ($_GET['action'] == "addNewCat") { addNewCat(); }
    if ($_GET['action'] == "editCat") { editCat(); }
    if ($_GET['action'] == "deleteCat") { deleteCat(); }
 
    if ($_GET['action'] == "addSubCat") { addSubCat(); }
    if ($_GET['action'] == "editSubCat") { editSubCat(); }
    if ($_GET['action'] == "delSubCat") { delSubCat(); }
    if ($_GET['action'] == "getSubCat") { getSubCat(); }
 
    if ($_GET['action'] == "editLanguageFile") { editLanguageFile(); }
 
    if ($_GET['action'] == "saveBlog") { saveBlog(); }
    if ($_GET['action'] == "deleteBlog") { deleteBlog(); }
    if ($_GET['action'] == "approveComment") { approveComment(); }
    if ($_GET['action'] == "deleteComment") { deleteComment(); }
    if ($_GET['action'] == "addBlogCat") { addBlogCat(); }
    if ($_GET['action'] == "editBlogCat") { editBlogCat(); }
    if ($_GET['action'] == "delBlogCat") { delBlogCat(); }
 
    if ($_GET['action'] == "deleteTestimonial") { deleteTestimonial(); }
 
}
 
if(isset($_POST['action'])){
 
 
    if ($_POST['action'] == "quickad_update_maincat_position") { quickad_update_maincat_position(); }
    if ($_POST['action'] == "quickad_update_subcat_position") { quickad_update_subcat_position(); }
    if ($_POST['action'] == "quickad_update_plan_custom_position") { quickad_update_plan_custom_position(); }
    if ($_POST['action'] == "deleteusers") { deleteusers(); }
    if ($_POST['action'] == "deleteRestaurant") { deleteRestaurant(); }
    if ($_POST['action'] == "getsubcatbyid") {getsubcatbyid();}
}
 
function change_language_file_settings($filePath, $newArray)
{
    $lang = array();
    // Get a list of the variables in the scope before including the file
    $new = get_defined_vars();
    // Include the config file and get it's values
    include($filePath);
 
    // Get a list of the variables in the scope after including the file
    $old = get_defined_vars();
 
    // Find the difference - after this, $fileSettings contains only the variables
    // declared in the file
    $fileSettings = array_diff($lang, $newArray);
 
    // Update $fileSettings with any new values
    $fileSettings = array_merge($fileSettings, $newArray);
    // Build the new file as a string
    $newFileStr = "<?php\n";
    foreach ($fileSettings as $name => $val) {
        // Using var_export() allows you to set complex values such as arrays and also
        // ensures types will be correct
        $newFileStr .= "\$lang['$name'] = " . var_export($val, true) . ";\n";
    }
    // Closing tag intentionally omitted, you can add one if you want
 
    // Write it back to the file
    file_put_contents($filePath, $newFileStr);
 
}
 
function editLanguageFile()
{
    $file_name = $_POST['file_name'];
    $filePath = '../includes/lang/lang_'.$file_name.'.php';
 
    if(isset($_POST['key'])){
        if(check_allow()){
            $value = stripslashes($_POST['value']);
            $newLangArray = array(
                $_POST['key'] => $value
            );
            if(file_exists($filePath)){
                change_language_file_settings($filePath, $newLangArray);
                echo 1;
                die();
            }
        }
    }
    echo 0;
    die();
}
 
 
/**
 * @param $filename
 * @return string
 */
function getFile($filename)
{
    $file = fopen($filename, 'r') or die('Unable to open file getFile!');
    $buffer = fread($file, filesize($filename));
    fclose($file);
 
    return $buffer;
}
 
/**
 * @param $filename
 * @param $buffer
 */
function writeFile($filename, $buffer)
{
    // Delete the file before writing
    if (file_exists($filename)) {
        unlink($filename);
    }
    // Write the new file
    $file = fopen($filename, 'w') or die('Unable to open file writeFile!');
    fwrite($file, $buffer);
    fclose($file);
}
/**
 * @param $rawFilePath
 * @param $filePath
 * @param $con
 * @return mixed|string
 */
function setSqlWithDbPrefix($rawFilePath, $filePath, $prefix)
{
    if (!file_exists($rawFilePath)) {
        return '';
    }
 
    // Read and replace prefix
    $sql = getFile($rawFilePath);
    $sql = str_replace('<<prefix>>', $prefix, $sql);
 
    // Write file
    writeFile($filePath, $sql);
 
    return $sql;
}
 
/**
 * @param $con
 * @param $filePath
 * @return bool
 */
 
function importSql($con, $filePath)
{
 
    try {
        $errorDetect = false;
 
        // Temporary variable, used to store current query
        $tmpline = '';
        // Read in entire file
        $lines = file($filePath);
        // Loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || trim($line) == '') {
                continue;
            }
            if (substr($line, 0, 2) == '/*') {
                continue;
            }
 
            // Add this line to the current segment
            $tmpline .= $line;
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';') {
                // Perform the query
                if (!$con->query($tmpline)) {
                    echo "<pre>Error performing query '<strong>" . $tmpline . "</strong>' : " . $con->error . " - Code: " . $con->errno . "</pre><br />";
                    $errorDetect = true;
                }
                // Reset temp variable to empty
                $tmpline = '';
            }
        }
        // Check if error is detected
        if ($errorDetect) {
            //dd('ERROR');
        }
    } catch (\Exception $e) {
        $msg = 'Error when importing required data : ' . $e->getMessage();
        echo '<pre>';
        print_r($msg);
        echo '</pre>';
        exit();
    }
 
 
    // Delete the SQL file
    if (file_exists($filePath)) {
        unlink($filePath);
    }
 
    return true;
}
 
/**
 * Import Geonames Default country database
 * @param $con
 * @param $site_info
 * @return bool
 */
function importGeonamesSql($con,$config,$default_country)
{
    if (!isset($default_country)) return false;
 
    // Default country SQL file
    $filename = 'database/countries/' . strtolower($default_country) . '.sql';
    $rawFilePath = '../storage/'.$filename;
    $filePath = '../storage/installed-db/' . $filename;
 
    setSqlWithDbPrefix($rawFilePath, $filePath, $config['db']['pre']);
 
    return importSql($con, $filePath);
}
 
 
function installPayment()
{
    global $con,$config;
 
    $id = $_POST['id'];
    $folder = $_POST['folder'];
    if (trim($id) != '') {
        if(check_allow())
            if(is_dir(ROOTPATH.'/includes/payments/'.$folder)){
                $con->query("UPDATE `".$config['db']['pre']."payments` set payment_install='1' WHERE `payment_id` = '" . $id . "'");
            }else{
                echo 0;
                die();
            }
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function uninstallPayment()
{
    global $con,$config;
 
    $id = $_POST['id'];
    if (trim($id) != '') {
        if(check_allow())
            $con->query("UPDATE `".$config['db']['pre']."payments` set payment_install='0' WHERE `payment_id` = '" . $id . "'");
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteStaticPage()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."pages` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `parent_id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `parent_id` = '" . $value . "'";
            }
 
            $count++;
        }
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deletefaq()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."faq_entries` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `faq_id` = '" . $value . "' or `parent_id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `faq_id` = '" . $value . "' or `parent_id` = '" . $value . "'";
            }
 
            $count++;
        }
 
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function activeuser()
{
    global $con,$config;
 
    $id = $_POST['id'];
    if (trim($id) != '') {
        if(check_allow())
            $con->query("UPDATE `".$config['db']['pre']."user` set status='0' WHERE `id` = '" . $id . "'");
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function banuser()
{
    global $con,$config;
 
    $id = $_POST['id'];
    if (trim($id) != '') {
        if(check_allow())
            $con->query("UPDATE `".$config['db']['pre']."user` set status='2' WHERE `id` = '" . $id . "'");
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteusers()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."user` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteRestaurant()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."restaurant` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteCurrency()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."currencies` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteTimezone()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."time_zones` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteMembershipPlan()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        if(check_allow()){
            ORM::for_table($config['db']['pre'].'plans')->where_id_in($_POST['list'])->delete_many();
        }
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deletePackage()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."usergroups` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `group_id` = '" . $value . "' and group_removable = '1' ";
            }
            else
            {
                $sql.= " OR `group_id` = '" . $value . "'  and group_removable = '1' ";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteLanguage()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $id = $_POST['id'];
 
        $sql = "DELETE FROM `".$config['db']['pre']."languages` WHERE `id` = '" . $id . "' LIMIT 1";
 
        if(check_allow()){
            $query = mysqli_query($con,"Select file_name from `".$config['db']['pre']."languages` where id = '" . $id . "'");
            $fetch = mysqli_fetch_assoc($query);
            $file_name = $fetch['file_name'];
            $file = '../includes/lang/lang_'.$file_name.'.php';
            if(file_exists($file))
                unlink($file);
            mysqli_query($con,$sql);
 
            echo 1;
            die();
        }
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteadmin()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."admins` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteTransaction()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."transaction` ";
 
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
 
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow())
            mysqli_query($con,$sql);
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
 
}
 
function deleteTaxes()
{
    global $con,$config;
 
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list'])) {
 
        if(check_allow()){
            ORM::for_table($config['db']['pre'].'taxes')->where_id_in($_POST['list'])->delete_many();
        }
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function edit_langTranslation()
{
    global $con,$config;
 
    $id = $_POST['id'];
    $cattype = $_POST['cat_type'];
    if(check_allow()){
        foreach ($_POST['value'] as $items) {
 
            $code = $items['code'];
            $title = $items['title'];
            $slug = $items['slug'];
 
            $source = 'en';
            $target = $code;
 
            /*$trans = new GoogleTranslate();
            $title = $trans->translate($source, $target, $title);*/
 
            if($slug == "")
                $slug = create_category_slug($title);
            else
                $slug = create_category_slug($slug);
 
            $sql = "SELECT id FROM `".$config['db']['pre']."category_translation` where translation_id = '$id' AND lang_code = '$code'  AND category_type = '$cattype' LIMIT 1";
            $query = mysqli_query($con,$sql);
            $rowcount = mysqli_num_rows($query);
            $title = mysqli_real_escape_string($con,$title);
 
            if($rowcount != 0){
                $info = mysqli_fetch_array($query);
                $a = "UPDATE `".$config['db']['pre']."category_translation` set title = '$title',slug = '$slug' where id = '".$info['id']."' LIMIT 1";
                mysqli_query($con,$a);
 
            }else{
                $a = "INSERT into `".$config['db']['pre']."category_translation` set lang_code = '$code',title = '$title',slug = '$slug',category_type = '$cattype', translation_id = '$id' ";
                mysqli_query($con,$a);
            }
        }
        echo 1;
        die();
    }
    echo 0;
    die();
}
 
function langTranslation_FormFields()
{
    global $con,$config;
 
    $id = $_POST['id'];
    $type = $_POST['cat_type'];
    $field_tpl = '<input type="hidden" id="category_id" value="'.$id.'"><input type="hidden" id="category_type" value="'.$type.'">';
    if ($id) {
        $sql = "SELECT id,code,name FROM `".$config['db']['pre']."languages` where active = '1' and code != 'en'";
        $query = mysqli_query($con,$sql);
        $rows = mysqli_num_rows($query);
        if($rows > 0){
            while($fetch = mysqli_fetch_array($query)){
                $sql2 = "SELECT * FROM `".$config['db']['pre']."category_translation` where lang_code = '".$fetch['code']."' and 	translation_id = '$id' and category_type = '$type' LIMIT 1";
                $query2 = mysqli_query($con,$sql2);
                $info = mysqli_fetch_assoc($query2);
 
                if($type == "custom_option"){
                    $field_tpl .= '
<div class="row translate_row">
    <div class="col-md-12 col-sm-12">
        <div class="form-group">
            <label class="col-md-3 control-label">' . $fetch['name'] . '</label>
            <div class="col-md-9">
                <input type="text" value="' . $info['title'] . '" class="form-control cat_title" placeholder="In ' . $fetch['name'] . '">
                <input type="hidden" class="lang_code" value="' . $fetch['code'] . '">
            </div>
        </div>
    </div>
</div>
';
                }else{
                    $field_tpl .= '
<div class="row translate_row">
    <div class="col-md-6 col-sm-12">
        <div class="form-group">
            <label class="col-md-3 control-label">' . $fetch['name'] . '</label>
            <div class="col-md-9">
                <input type="text" value="' . $info['title'] . '" class="form-control cat_title" placeholder="In ' . $fetch['name'] . '">
            </div>
        </div>
    </div>
    <div class="col-md-6 col-sm-12">
        <div class="form-group">
            <label class="col-md-3 control-label">Slug</label>
            <div class="col-md-9">
                <input type="text" value="' . $info['slug'] . '" class="form-control cat_slug" placeholder="Slug">
            </div>
        </div>
    </div>
    <input type="hidden" class="lang_code" value="' . $fetch['code'] . '">
</div>
';
                }
 
            }
        }else{
            $field_tpl .= '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            No language activated. Your site run with single language. </div>';
        }
        echo $field_tpl;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function addNewCat()
{
    global $con,$config;
 
    $name = $_POST['name'];
    $icon = $_POST['icon'];
    $slug = $_POST['slug'];
    $picture = $_POST['picture'];
    if (trim($name) != '' && is_string($name)) {
        if($slug == "")
            $slug = create_category_slug($name);
        else
            $slug = create_category_slug($slug);
 
        $query = "Insert into `".$config['db']['pre']."catagory_main` set 
        cat_name='".$name."', 
        slug='".$slug."',
        picture='".$picture."',icon='".$icon."'";
        if(check_allow()){
            $con->query($query);
            $id = $con->insert_id;
            /*
            $query = "UPDATE `".$config['db']['pre']."catagory_main` SET `cat_order` = '" . $id . "' WHERE `cat_id` = '" . $id . "'";
            $con->query($query);
 
            $type = "main";
            $sql = "SELECT id,code,name FROM `".$config['db']['pre']."languages` where active = '1' and code != 'en'";
            $query = mysqli_query($con,$sql);
            mysqli_num_rows($query);
            while($fetch = mysqli_fetch_array($query)){
 
                $source = 'en';
                $target = $fetch['code'];
 
                $trans = new GoogleTranslate();
                $title = $trans->translate($source, $target, $name);
                $slug = create_category_translation_slug($title);
                $title = mysqli_real_escape_string($con,$title);
                $slug = mysqli_real_escape_string($con,$slug);
 
                $sql2 = "Insert into `".$config['db']['pre']."category_translation` set lang_code = '".$fetch['code']."', translation_id = '$id', category_type = '$type', title = '$title', slug='".$slug."'";
                $query2 = mysqli_query($con,$sql2);
            }*/
        }
        else {
            $id = 1;
        }
        echo $name . ',' . $id . ',' . $icon. ',' . $slug;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function editCat()
{
    global $con,$config;
 
    $name = $_POST['name'];
    $icon = $_POST['icon'];
    $slug = $_POST['slug'];
    $picture = $_POST['picture'];
    $id = $_POST['id'];
    if (trim($name) != '' && is_string($name) && trim($id) != '') {
        if($slug == "")
            $slug = create_slug($name);
        else
            $slug = create_slug($slug);
 
        $query = "UPDATE `".$config['db']['pre']."catagory_main` SET `cat_name` = '".$name."',`icon` = '" . $icon . "',`picture` = '" . $picture . "',`slug` = '" . $slug . "' WHERE `cat_id` = '" . $id . "'";
        if(check_allow()){
            $con->query($query);
 
            /*$type = "main";
            $sql = "SELECT id,code,name FROM `".$config['db']['pre']."languages` where active = '1' and code != 'en'";
            $query = mysqli_query($con,$sql);
            mysqli_num_rows($query);
            while($fetch = mysqli_fetch_array($query)){
 
                $source = 'en';
                $target = $fetch['code'];
 
                $trans = new GoogleTranslate();
                $title = $trans->translate($source, $target, $name);
                $slug = create_category_translation_slug($title);
                $title = mysqli_real_escape_string($con,$title);
                $slug = mysqli_real_escape_string($con,$slug);
 
                $new_sql = "SELECT 1 FROM `".$config['db']['pre']."category_translation` WHERE lang_code = '".$fetch['code']."' and translation_id = '$id' and category_type = '$type'";
                $newquery = mysqli_query($con,$new_sql);
                if($newquery){
                    if(mysqli_num_rows($newquery) > 0){
                        $sql2 = "UPDATE `".$config['db']['pre']."category_translation` set title = '$title', slug='".$slug."' WHERE lang_code = '".$fetch['code']."' and translation_id = '$id' and category_type = '$type'";
                        $query2 = mysqli_query($con,$sql2);
                    }else{
                        $sql2 = "Insert into `".$config['db']['pre']."category_translation` set lang_code = '".$fetch['code']."', translation_id = '$id', category_type = '$type', title = '$title', slug='".$slug."'";
                        $query2 = mysqli_query($con,$sql2);
                    }
                }
 
            }*/
        }
        echo $name . ',' . $icon;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function deleteCat()
{
    global $con,$config;
 
    $id = $_POST['id'];
    if (trim($id) != '') {
        if(check_allow()){
            if ($con->query("DELETE FROM `".$config['db']['pre']."catagory_main` WHERE `cat_id` = '" . $id . "'")) {
                $con->query("DELETE FROM `".$config['db']['pre']."category_translation` WHERE `translation_id` = '" . $id . "' and category_type = 'main' ");
                $query = "SELECT sub_cat_id FROM `".$config['db']['pre']."catagory_sub` WHERE `main_cat_id` = '" . $id . "'";
                $query_result = mysqli_query ($con, $query) OR error(mysqli_error($con));
                while($row = $query_result->fetch_assoc()) // use fetch_assoc here
                {
                    $id = $row['sub_cat_id'];
                    $con->query("DELETE FROM `".$config['db']['pre']."catagory_sub` WHERE `sub_cat_id` = '" . $id . "'");
                    $con->query("DELETE FROM `".$config['db']['pre']."category_translation` WHERE `translation_id` = '" . $id . "' and category_type = 'sub' ");
                }
 
                echo 1;
                die();
            } else {
                echo 0;
                die();
            }
        }
        else{
            echo 1;
        }
    } else {
        echo 0;
        die();
    }
}
 
function quickad_update_maincat_position()
{
    global $con,$config;
 
    $position = $_POST['position'];
    if (is_array($position)) {
        $count = 0;
        foreach($position as $catid){
 
            $query = "UPDATE `".$config['db']['pre']."catagory_main` SET `cat_order` = '".$count."' WHERE `cat_id` = '" . $catid . "'";
            if(check_allow()){
                $con->query($query);
            }
            $count++;
        }
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function quickad_update_subcat_position()
{
    global $con,$config;
 
    $position = $_POST['position'];
    if (is_array($position)) {
        $count = 0;
        foreach($position as $catid){
 
            $query = "UPDATE `".$config['db']['pre']."catagory_sub` SET `cat_order` = '".$count."' WHERE `sub_cat_id` = '" . $catid . "'";
            if(check_allow()){
                $con->query($query);
            }
            $count++;
        }
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function addSubCat()
{
    global $con,$config;
 
    $name = $_POST['name'];
    $cat_id = $_GET['mainid'];
    if (trim($name) != '' && is_string($name) && trim($cat_id) != '') {
        $slug = create_sub_category_slug($name);
        $query = "Insert into `".$config['db']['pre']."catagory_sub` set sub_cat_name='".$name."', slug='".$slug."', main_cat_id='".$cat_id."'";
        if(check_allow()){
            $con->query($query);
            $id = $con->insert_id;
 
            $query = "UPDATE `".$config['db']['pre']."catagory_sub` SET `cat_order` = '" . $id . "' WHERE `sub_cat_id` = '" . $id . "'";
            $con->query($query);
 
            /*$type = "sub";
            $sql = "SELECT id,code,name FROM `".$config['db']['pre']."languages` where active = '1' and code != 'en'";
            $query = mysqli_query($con,$sql);
            mysqli_num_rows($query);
            while($fetch = mysqli_fetch_array($query)){
 
                $source = 'en';
                $target = $fetch['code'];
 
                $trans = new GoogleTranslate();
                $title = $trans->translate($source, $target, $name);
                if($title == ""){
                    $title = $name;
                }
                $slug = create_category_translation_slug($title);
                $title = mysqli_real_escape_string($con,$title);
                $slug = mysqli_real_escape_string($con,$slug);
 
                $sql2 = "Insert into `".$config['db']['pre']."category_translation` set lang_code = '".$fetch['code']."', translation_id = '$id', category_type = '$type', title = '$title', slug='".$slug."'";
                $query2 = mysqli_query($con,$sql2);
            }*/
        }
        else{
            $id =1;
        }
 
        echo $name . ',' . $id;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function editSubCat()
{
    global $con,$config;
 
    $name = $_GET['title'];
    $slug = $_GET['slug'];
    $id = $_GET['id'];
    $photo_show = $_GET['photo_show'];
    $price_show = $_GET['price_show'];
    $picture = $_GET['picture'];
    if (trim($name) != '' && is_string($name) && trim($id) != '') {
 
        if($slug == "")
            $slug = create_category_slug($name);
        else
            $slug = create_category_slug($slug);
 
        $query = "UPDATE `".$config['db']['pre']."catagory_sub` SET `sub_cat_name` = '".$name."',`slug` = '".$slug."', `picture` = '".$picture."', `photo_show` = '".$photo_show."', `price_show` = '".$price_show."' WHERE `sub_cat_id` = '" . $id . "'";
        if(check_allow()){
            $con->query($query);
 
            /*$type = "sub";
            $sql = "SELECT id,code,name FROM `".$config['db']['pre']."languages` where active = '1' and code != 'en'";
            $query = mysqli_query($con,$sql);
            mysqli_num_rows($query);
            while($fetch = mysqli_fetch_array($query)){
 
                $source = 'en';
                $target = $fetch['code'];
 
                $trans = new GoogleTranslate();
                $title = $trans->translate($source, $target, $name);
                if($title == ""){
                    $title = $name;
                }
                $slug = create_category_translation_slug($title);
                $title = mysqli_real_escape_string($con,$title);
                $slug = mysqli_real_escape_string($con,$slug);
 
                $new_sql = "SELECT 1 FROM `".$config['db']['pre']."category_translation` WHERE lang_code = '".$fetch['code']."' and translation_id = '$id' and category_type = '$type'";
                $newquery = mysqli_query($con,$new_sql);
                if($newquery){
                    if(mysqli_num_rows($newquery) > 0){
                        $sql2 = "UPDATE `".$config['db']['pre']."category_translation` set title = '$title', slug='".$slug."' WHERE lang_code = '".$fetch['code']."' and translation_id = '$id' and category_type = '$type'";
                        $query2 = mysqli_query($con,$sql2);
                    }else{
                        $sql2 = "Insert into `".$config['db']['pre']."category_translation` set lang_code = '".$fetch['code']."', translation_id = '$id', category_type = '$type', title = '$title', slug='".$slug."'";
                        $query2 = mysqli_query($con,$sql2);
                    }
                }
 
            }*/
        }
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function delSubCat()
{
    global $con,$config;
 
    $subCatids = $_POST['subCatids'];
    if (is_array($subCatids)) {
        foreach ($subCatids as $subCatid) {
            if(check_allow()){
                $con->query("DELETE FROM `".$config['db']['pre']."catagory_sub` WHERE `sub_cat_id` = '" . $subCatid . "'");
                $con->query("DELETE FROM `".$config['db']['pre']."category_translation` WHERE `translation_id` = '" . $subCatid . "' and category_type = 'sub'");
            }
        }
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function getSubCat()
{
    global $con,$config;
 
    $id = isset($_GET['category_id']) ? $_GET['category_id'] : 0;
    if ($id > 0) {
        $query = "SELECT * FROM `".$config['db']['pre']."catagory_sub` WHERE main_cat_id = ".$id." ORDER by cat_order ASC";
    } else {
        $query = "SELECT * FROM `".$config['db']['pre']."catagory_sub` ORDER by cat_order ASC";
    }
    $tags = '<div class="panel-group ui-sortable" id="services_list" role="tablist" aria-multiselectable="true">';
 
    if ($result = $con->query($query)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $name = $row['sub_cat_name'];
            $slug = $row['slug'];
            $sub_id = $row['sub_cat_id'];
            $picture = $row['picture'];
            $photo_show = $row['photo_show'];
            $price_show = $row['price_show'];
            $photo_hide_selected = ($photo_show == 0)? "selected" :  "";
            $price_hide_selected = ($price_show == 0)? "selected" :  "";
            $userlangselect = (get_option("userlangsel") == '1')? "show" :  "hidden";
 
            $tags .= ' <div class="panel panel-default quickad-js-collapse" data-service-id="' . $sub_id . '">
                                        <div class="panel-heading" role="tab" id="s_' . $sub_id . '">
                                            <div class="row">
                                                <div class="col-sm-8 col-xs-10">
                                                    <div class="quickad-flexbox">
                                                        <div class="quickad-flex-cell quickad-vertical-middle"
                                                             style="width: 1%">
                                                            <i class="quickad-js-handle quickad-icon quickad-icon-draghandle quickad-margin-right-sm quickad-cursor-move ui-sortable-handle"
                                                               title="Reorder"></i>
                                                        </div>
                                                        <div class="quickad-flex-cell quickad-vertical-middle">
                                                            <a role="button"
                                                               class="panel-title collapsed quickad-js-service-title"
                                                               data-toggle="collapse" data-parent="#services_list"
                                                               href="#service_' . $sub_id . '" aria-expanded="false"
                                                               aria-controls="service_' . $sub_id . '">
                                                                '.$name.' </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4 col-xs-2">
                                                    <div class="quickad-flexbox">
                                                        <div class="quickad-flex-cell quickad-vertical-middle text-right"
                                                             style="width: 10%">
                                                            <label class="css-input css-checkbox css-checkbox-default m-t-0 m-b-0">
                                                                <input type="checkbox" id="checkbox'.$sub_id.'" name="check-all" value="'.$sub_id.'"  class="service-checker"><span></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
 
                                        <div id="service_' . $sub_id . '" class="panel-collapse collapse" role="tabpanel"
                                             style="height: 0">
                                            <div class="panel-body">
                                                <form method="post" id="' . $sub_id . '">
                                                    <div class="row">
                                                        <div class="col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="title_' . $sub_id . '">Title</label>
                                                                <input name="title" value="'.$name.'" id="title_' . $sub_id . '"
                                                                       class="form-control" type="text">
 
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="slug_' . $sub_id . '">Slug</label>
                                                                <input name="slug" value="'.$slug.'" id="slug_' . $sub_id . '"
                                                                       class="form-control" type="text">
 
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="photo_' . $sub_id . '">Photo field Enable/Disable</label>
                                                                <select name="photo_show" class="form-control">
                                                                   <option value="1">Enable</option>
                                                                    <option value="0" '.$photo_hide_selected.'>Disable</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="price_' . $sub_id . '">Price Enable/Disable</label>
                                                                <select name="price_show" class="form-control">
                                                                    <option value="1">Enable</option>
                                                                    <option value="0" '.$price_hide_selected.'>Disable</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12 col-sm-12">
                                                            <div class="form-group">
                                                                <label for="picture_' . $sub_id . '">Icon Image Url</label>
                                                                <input name="picture" value="'.$picture.'" id="picture_' . $sub_id . '" class="form-control" type="text">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="panel-footer">
                                                    <input name="id" value="' . $sub_id . '" type="hidden">
                                                    <button type="button"
                                                                class="'.$userlangselect.' btn btn-lg btn-warning quickad-cat-lang-edit" data-category-id="'.$sub_id.'" data-category-type="sub"> <span
                                                                class="ladda-label"><i class="fa fa-language"></i> Edit Language</span></button>
                                                        <button type="button"
                                                                class="btn btn-lg btn-success ladda-button ajax-subcat-edit"
                                                                data-style="zoom-in" data-spinner-size="40" onclick="editSubCat('.$sub_id.');"><span
                                                                class="ladda-label">Save</span></button>
                                                        <button class="btn btn-lg btn-default js-reset" type="reset">Reset
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>';
 
        }
 
        $tags .= '</div>';
        echo $tags;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function getsubcatbyid()
{
    global $con,$config;
 
    $id = isset($_POST['catid']) ? $_POST['catid'] : 0;
    $selectid = isset($_POST['selectid']) ? $_POST['selectid'] : "";
 
    $query = "SELECT * FROM `" . $config['db']['pre'] . "catagory_sub` WHERE main_cat_id = " . $id;
    if ($result = $con->query($query)) {
 
        while ($row = mysqli_fetch_assoc($result)) {
            $name = $row['sub_cat_name'];
            $sub_id = $row['sub_cat_id'];
            if($selectid == $sub_id){
                $selected_text = "selected";
            }
            else{
                $selected_text = "";
            }
            echo '<option value="'.$sub_id.'" '.$selected_text.'>'.$name.'</option>';
        }
 
 
    }
}
 
function saveBlog(){
    global $con,$config;
 
    $title = strip_tags($_POST['title']);
    $tags = strtolower(preg_replace('/[^a-zA-Z0-9_ ,]/', '', $_POST['tags']));
    $image = null;
    $description = stripUnwantedTagsAndAttrs($_POST['description'],true);
    $error = array();
 
    if(empty($title)){
        $error[] = "Blog title is required.";
    }
    if(empty($description)){
        $error[] = "Blog description is required.";
    }
 
    if(empty($error)){
        if(!empty($_FILES['image'])){
            $file = $_FILES['image'];
            // Valid formats
            $valid_formats = array("jpeg", "jpg", "png");
            $filename = $file['name'];
            $ext = getExtension($filename);
            $ext = strtolower($ext);
            if (!empty($filename)) {
                //File extension check
                if (in_array($ext, $valid_formats)) {
                    $main_path = "../storage/blog/";
                    $filename = uniqid(time()).'.'.$ext;
                    if(move_uploaded_file($file['tmp_name'], $main_path.$filename)){
                        $image = $filename;
                        resizeImage(900,$main_path.$filename,$main_path.$filename);
 
                        if(!empty($_POST['id'])) {
                            // remove old image
                            $info = ORM::for_table($config['db']['pre'] . 'blog')
                                ->select('image')
                                ->find_one($_POST['id']);
 
                            if ($info['image'] != "default.png") {
                                if (file_exists($main_path . $info['image'])) {
                                    unlink($main_path . $info['image']);
                                }
                            }
                        }
                    }else{
                        $error[] = 'Unexpected error, please try again.';
                    }
                } else {
                    $error[] = 'Only jpeg, jpg & png files allowed.';
                }
            }
        }
    }
 
    if (empty($error)) {
        $id = 1;
        if(check_allow()){
            $now = date("Y-m-d H:i:s");
            if(!empty($_POST['id'])){
                $blog = ORM::for_table($config['db']['pre'].'blog')
                    ->where('id',$_POST['id'])
                    ->where('author',$_SESSION['admin']['id'])
                    ->find_one();
 
                if($blog){
                    if(!empty($image)){
                        $blog->set('image', $image);
                    }
                    $blog->set('title',$title);
                    $blog->set('description',addslashes($description));
                    $blog->set('tags', $tags);
                    $blog->set('status', $_POST['status']);
                    $blog->set('updated_at', $now);
                    $blog->save();
                    $id = $_POST['id'];
                }
 
                ORM::for_table($config['db']['pre'].'blog_cat_relation')
                    ->where('blog_id',$_POST['id'])
                    ->delete_many();
            }else{
                $blog = ORM::for_table($config['db']['pre'].'blog')->create();
                $blog->title = $title;
                $blog->image = $image;
                $blog->description = addslashes($description);
                $blog->author = $_SESSION['admin']['id'];
                $blog->status = $_POST['status'];
                $blog->tags = $tags;
                $blog->created_at = $now;
                $blog->updated_at = $now;
                $blog->save();
                $id = $blog->id();
            }
 
            if(!empty($_POST['category']) && is_array($_POST['category'])){
                foreach($_POST['category'] as $cat){
                    $blog_cat = ORM::for_table($config['db']['pre'].'blog_cat_relation')->create();
                    $blog_cat->blog_id = $id;
                    $blog_cat->category_id = $cat;
                    $blog_cat->save();
                }
            }
        }
        $result = array();
        $result['status'] = 'success';
        $result['id'] = $id;
        $result['message'] = "Saved Successfully.";
        echo json_encode($result);
 
    } else {
        $result = array();
        $result['status'] = 'error';
        $result['message'] = implode('<br>',$error);
        echo json_encode($result);
    }
    die();
}
 
function deleteBlog(){
    global $con,$config;
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list']))
    {
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."blog` ";
        $sql2 = "SELECT image FROM `".$config['db']['pre']."blog` ";
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
                $sql2.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
                $sql2.= " OR `id` = '" . $value . "'";
            }
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow()){
            if ($result = $con->query($sql2)) {
                while ($row = mysqli_fetch_assoc($result)) {
 
                    $uploaddir =  "../storage/blog/";
                    // delete logo
                    $file = $uploaddir.$row['image'];
                    if(file_exists($file))
                        unlink($file);
                }
            }
            mysqli_query($con,$sql);
        }
 
        echo 1;
        die();
    }else {
        echo 0;
        die();
    }
}
 
function approveComment(){
    global $con,$config;
 
    $query = "UPDATE `".$config['db']['pre']."blog_comment` SET `active` = '1' WHERE `id` = '" . $_POST['id'] . "'";
    if(check_allow()){
        $con->query($query);
    }
 
    echo 1;
    die();
}
 
function deleteComment(){
    global $con,$config;
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list']))
    {
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."blog_comment` ";
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
            }
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow()){
            mysqli_query($con,$sql);
        }
 
        echo 1;
        die();
    }else {
        echo 0;
        die();
    }
}
 
function addBlogCat()
{
    global $con,$config;
 
    $name = $_POST['name'];
    if (trim($name) != '' && is_string($name)) {
        $slug = create_blog_cat_slug($name);
        $query = "Insert into `".$config['db']['pre']."blog_categories` set title='".$name."', slug='".$slug."'";
        if(check_allow()){
            $con->query($query);
            $id = $con->insert_id;
 
            $query = "UPDATE `".$config['db']['pre']."blog_categories` SET `position` = '" . $id . "' WHERE `id` = '" . $id . "'";
            $con->query($query);
        }
        else{
            $id =1;
        }
        $result = array();
        $result['name'] = $name;
        $result['id'] = $id;
        $result['slug'] = $slug;
        echo json_encode($result);
        die();
    } else {
        echo 0;
        die();
    }
}
 
function editBlogCat(){
    global $con,$config;
 
    $name = $_GET['title'];
    $slug = $_GET['slug'];
    $status = $_GET['status'];
    $id = $_GET['id'];
    if (trim($name) != '' && is_string($name) && trim($id) != '') {
        if(empty($slug))
            $slug = create_slug($name);
        else
            $slug = create_slug($slug);
 
        $query = "UPDATE `".$config['db']['pre']."blog_categories` SET `title` = '".$name."', `slug` = '".$slug."', `active` = '".$status."' WHERE `id` = '" . $id . "'";
        if(check_allow()){
            $con->query($query);
        }
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function delBlogCat(){
    global $con,$config;
 
    $ids = $_POST['ids'];
    if (is_array($ids)) {
        foreach ($ids as $id) {
            if(check_allow()){
                $con->query("DELETE FROM `".$config['db']['pre']."blog_categories` WHERE `id` = '" . $id . "'");
            }
        }
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function deleteTestimonial(){
    global $con,$config;
    if(isset($_POST['id']))
    {
        $_POST['list'][] = $_POST['id'];
    }
 
    if (is_array($_POST['list']))
    {
        $count = 0;
        $sql = "DELETE FROM `".$config['db']['pre']."testimonials` ";
        $sql2 = "SELECT image FROM `".$config['db']['pre']."testimonials` ";
        foreach ($_POST['list'] as $value)
        {
            if($count == 0)
            {
                $sql.= "WHERE `id` = '" . $value . "'";
                $sql2.= "WHERE `id` = '" . $value . "'";
            }
            else
            {
                $sql.= " OR `id` = '" . $value . "'";
                $sql2.= " OR `id` = '" . $value . "'";
            }
            $count++;
        }
        $sql.= " LIMIT " . count($_POST['list']);
 
        if(check_allow()){
            if ($result = $con->query($sql2)) {
                while ($row = mysqli_fetch_assoc($result)) {
 
                    $uploaddir =  "../storage/testimonials/";
                    // delete logo
                    $file = $uploaddir.$row['image'];
                    if(file_exists($file))
                        unlink($file);
                }
            }
            mysqli_query($con,$sql);
        }
 
        echo 1;
        die();
    }else {
        echo 0;
        die();
    }
}
 
function addPlanCustom()
{
    global $con,$config;
 
    $name = validate_input($_POST['name']);
    if (trim($name) != '' && is_string($name)) {
        if(check_allow()){
            $custom = ORM::for_table($config['db']['pre'].'plan_options')->create();
            $custom->title = $name;
            $custom->save();
            $id = $custom->id();
 
            $query = "UPDATE `".$config['db']['pre']."plan_options` SET `position` = '" . $id . "' WHERE `id` = '" . $id . "'";
            $con->query($query);
        }
        else{
            $id =1;
        }
        $result = array();
        $result['name'] = $name;
        $result['id'] = $id;
        echo json_encode($result);
        die();
    } else {
        echo 0;
        die();
    }
}
 
function editPlanCustom()
{
    global $config;
 
    $name = validate_input($_GET['title']);
    $status = $_GET['status'];
    $id = $_GET['id'];
    if (trim($name) != '' && is_string($name) && trim($id) != '') {
        if(check_allow()){
            $blog = ORM::for_table($config['db']['pre'].'plan_options')
                ->where('id',$id)
                ->find_one();
            $blog->set('title',$name);
            $blog->set('active', $status);
            $blog->save();
 
        }
 
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function delPlanCustom()
{
    global $config;
 
    $ids = $_POST['ids'];
    if (is_array($ids)) {
        if(check_allow()){
            ORM::for_table($config['db']['pre'].'plan_options')->where_id_in($ids)->delete_many();
        }
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function langTranslation_PlanCustom()
{
    global $con,$config;
 
    $id = $_POST['id'];
    $field_tpl = '<input type="hidden" id="field_id" value="'.$id.'">';
    if ($id) {
        $sql2 = "SELECT translation_lang,translation_name,title FROM `".$config['db']['pre']."plan_options` where id = '$id' LIMIT 1";
        $query2 = mysqli_query($con,$sql2);
        $info = mysqli_fetch_assoc($query2);
        $translation_lang = explode(',',$info['translation_lang']);
        $translation_name = explode(',',$info['translation_name']);
        $count = 0;
        foreach($translation_lang as $key=>$value)
        {
            if($value != '')
            {
                $translation[$translation_lang[$key]] = $translation_name[$key];
 
                $count++;
            }
        }
 
        $sql = "SELECT id,code,name FROM `".$config['db']['pre']."languages` where active = '1' and code != 'en'";
        $query = mysqli_query($con,$sql);
        $num = mysqli_num_rows($query);
        if($num > 0){
            while($fetch = mysqli_fetch_array($query)){
                $trans_name = (isset($translation[$fetch['code']]))? $translation[$fetch['code']] : '';
                $count = 0;
 
                $field_tpl .= '
                <div class="form-group">
                <label class="col-md-3 control-label">'.$fetch['name'].'</label>
                <div class="col-md-7">
                <input type="text" value="'.$trans_name.'" data-lang-code="'.$fetch['code'].'" class="form-control title_code" placeholder="In '.$fetch['name'].'">
                </div>
                </div>';
            }
        }else{
            $field_tpl .= '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            No language activated. Your site run with single language.</div>';
        }
 
        echo $field_tpl;
        die();
    } else {
        echo 0;
        die();
    }
}
 
function edit_langTranslation_PlanCustom()
{
    global $con,$config;
 
    $id = $_POST['id'];
    $trans_lang = implode(',', $_POST['trans_lang']);
    $trans_name = implode(',', $_POST['trans_name']);
 
    if($_POST['id']){
        if(check_allow()){
            $trans_lang = mysqli_real_escape_string($con,$trans_lang);
            $trans_name = mysqli_real_escape_string($con,$trans_name);
            $a = "UPDATE `".$config['db']['pre']."plan_options` set translation_lang = '$trans_lang',translation_name = '$trans_name' where id = '".$id."' LIMIT 1";
            mysqli_query($con,$a);
            echo 1;
            die();
        }
    }
 
    echo 0;
    die();
}
 
function quickad_update_plan_custom_position()
{
    global $con,$config;
 
    $position = $_POST['position'];
    if (is_array($position)) {
        $count = 0;
        foreach($position as $id){
            $query = "UPDATE `".$config['db']['pre']."plan_options` SET `position` = '".$count."' WHERE `id` = '" . $id . "'";
            if(check_allow()){
                $con->query($query);
            }
            $count++;
        }
        echo 1;
        die();
    } else {
        echo 0;
        die();
    }
}