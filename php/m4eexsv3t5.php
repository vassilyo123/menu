<?php
require_once('../includes/config.php');
require_once('../includes/sql_builder/idiorm.php');
require_once('../includes/db.php');
require_once('../includes/classes/class.template_engine.php');
require_once('../includes/classes/class.country.php');
require_once('../includes/functions/func.global.php');
require_once('../includes/functions/func.sqlquery.php');
require_once('../includes/functions/func.users.php');
require_once('../includes/lang/lang_' . $config['lang'] . '.php');
require_once('../includes/seo-url.php');
 
sec_session_start();
define("ROOTPATH", dirname(__DIR__));
 
if (isset($_GET['action'])) {
    if ($_GET['action'] == "add_item") {
        add_item();
    }
    if ($_GET['action'] == "edit_item") {
        edit_item();
    }
    if ($_GET['action'] == "get_item") {
        get_item();
    }
    if ($_GET['action'] == "get_image_menu") {
        get_image_menu();
    }
    if ($_GET['action'] == "delete_item") {
        delete_item();
    }
    if ($_GET['action'] == "delete_image_menu") {
        delete_image_menu();
    }
 
    if ($_GET['action'] == "add_image_item") {
        add_image_item();
    }
 
    if ($_GET['action'] == "submitBlogComment") {
        submitBlogComment();
    }
    die(0);
}
 
if (isset($_POST['action'])) {
    if ($_POST['action'] == "addNewCat") {
        addNewCat();
    }
    if ($_POST['action'] == "editCat") {
        editCat();
    }
    if ($_POST['action'] == "deleteCat") {
        deleteCat();
    }
 
    if ($_POST['action'] == "updateCatPosition") {
        updateCatPosition();
    }
 
    if ($_POST['action'] == "updateMenuPosition") {
        updateMenuPosition();
    }
 
    if ($_POST['action'] == "updateExtrasPosition") {
        updateExtrasPosition();
    }
 
    if ($_POST['action'] == "updateImageMenuPosition") {
        updateImageMenuPosition();
    }
 
    if ($_POST['action'] == "ajaxlogin") {
        ajaxlogin();
    }
    if ($_POST['action'] == "email_verify") {
        email_verify();
    }
 
    if ($_POST['action'] == "addMenuExtra") {
        addMenuExtra();
    }
    if ($_POST['action'] == "editMenuExtra") {
        editMenuExtra();
    }
    if ($_POST['action'] == "deleteMenuExtra") {
        deleteMenuExtra();
    }
 
    if ($_POST['action'] == "sendRestaurantOrder") {
        sendRestaurantOrder();
    }
 
    if ($_POST['action'] == "completeOrder") {
        completeOrder();
    }
    if ($_POST['action'] == "deleteOrder") {
        deleteOrder();
    }
 
    if ($_POST['action'] == "getOrders") {
        getOrders();
    }
    die(0);
}
 
function add_item()
{
    global $config, $lang;
 
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    if (empty($_POST['title'])) {
        $result['success'] = false;
        $result['message'] = $lang['TITLE_REQ'];
        die(json_encode($result));
    }
    if (empty($_POST['description'])) {
        $result['success'] = false;
        $result['message'] = $lang['DESC_REQ'];
        die(json_encode($result));
    }
    if (empty($_POST['price'])) {
        $result['success'] = false;
        $result['message'] = $lang['PRICE_REQ'];
        die(json_encode($result));
    }
    $MainFileName = null;
    $main_imageName = '';
    $cat_id = validate_input($_POST['cat_id']);
    $title = validate_input($_POST['title']);
    $description = validate_input($_POST['description']);
    $price = validate_input($_POST['price']);
 
    // check if adding new item
    if (empty($_POST['id'])) {
        // Get usergroup details
        $group_id = get_user_group();
        // Get membership details
        switch ($group_id){
            case 'free':
                $plan = json_decode(get_option('free_membership_plan'), true);
                $settings = $plan['settings'];
                $limit = $settings['menu_limit'];
                break;
            case 'trial':
                $plan = json_decode(get_option('trial_membership_plan'), true);
                $settings = $plan['settings'];
                $limit = $settings['menu_limit'];
                break;
            default:
                $plan = ORM::for_table($config['db']['pre'] . 'plans')
                    ->select('settings')
                    ->where('id', $group_id)
                    ->find_one();
                if(!isset($plan['settings'])){
                    $plan = json_decode(get_option('free_membership_plan'), true);
                    $settings = $plan['settings'];
                    $limit = $settings['menu_limit'];
                }else{
                    $settings = json_decode($plan['settings'],true);
                    $limit = $settings['menu_limit'];
                }
                break;
        }
 
 
        if ($limit != "999") {
            $total = ORM::for_table($config['db']['pre'] . 'menu')
                ->where('user_id', $_SESSION['user']['id'])
                ->where('cat_id', $cat_id)
                ->count();
 
            if ($total >= $limit) {
                $result['success'] = false;
                $result['message'] = $lang['LIMIT_EXCEED_UPGRADE'];
                die(json_encode($result));
            }
        }
    }
 
    // Valid formats
    $valid_formats = array("jpeg", "jpg", "png");
 
    /*Start Item Logo Image Uploading*/
    $file = $_FILES['main_image'];
    $filename = $file['name'];
    $ext = getExtension($filename);
    $ext = strtolower($ext);
    if (!empty($filename)) {
        //File extension check
        if (in_array($ext, $valid_formats)) {
            $main_path = ROOTPATH . "/storage/menu/";
            $filename = uniqid(time()) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $main_path . $filename)) {
                $MainFileName = $filename;
                resizeImage(300, $main_path . $filename, $main_path . $filename);
            } else {
                $result['success'] = false;
                $result['message'] = $lang['ERROR_IMAGE'];
                die(json_encode($result));
            }
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ONLY_JPG_ALLOW'];
            die(json_encode($result));
        }
    }
    /*End Item Logo Image Uploading*/
 
    if (trim($title) != '' && is_string($title)) {
        if (!empty($_POST['id'])) {
            $insert_menu = ORM::for_table($config['db']['pre'] . 'menu')->find_one($_POST['id']);
        } else {
            $insert_menu = ORM::for_table($config['db']['pre'] . 'menu')->create();
        }
 
        $insert_menu->active = isset($_POST['active']) ? '1' : '0';
        $insert_menu->user_id = validate_input($_SESSION['user']['id']);
        $insert_menu->cat_id = $cat_id;
        $insert_menu->name = $title;
        $insert_menu->description = $description;
        $insert_menu->price = $price;
        $insert_menu->type = validate_input($_POST['type']);
        if ($MainFileName) {
            $insert_menu->image = $MainFileName;
        }
        $insert_menu->save();
 
        $menu_id = $insert_menu->id();
 
        if ($menu_id) {
            $result['success'] = true;
            $result['message'] = $lang['SAVED_SUCCESS'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
 
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function get_item()
{
    global $config;
    $result = ORM::for_table($config['db']['pre'] . 'menu')
        ->where('user_id', $_SESSION['user']['id'])
        ->find_one($_GET['id']);
    $response = array('success' => false);
    if (!empty($result)) {
        $response['success'] = true;
        $response['name'] = $result['name'];
        $response['description'] = stripcslashes($result['description']);
        $response['price'] = $result['price'];
        $response['type'] = $result['type'];
        $response['active'] = $result['active'];
        $response['image'] = !empty($result['image'])
            ? $config['site_url'] . 'storage/menu/' . $result['image']
            : $config['site_url'] . 'storage/menu/' . 'default.png';
    }
    die(json_encode($response));
}
 
function edit_item()
{
    global $config, $lang;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    if (empty($_POST['menu_id'])) {
        $result['success'] = false;
        $result['message'] = $lang['TITLE_REQ'];
        die(json_encode($result));
    }
 
    if (empty($_POST['title'])) {
        $result['success'] = false;
        $result['message'] = $lang['TITLE_REQ'];
        die(json_encode($result));
    }
    if (empty($_POST['description'])) {
        $result['success'] = false;
        $result['message'] = $lang['DESC_REQ'];
        die(json_encode($result));
    }
    if (empty($_POST['price'])) {
        $result['success'] = false;
        $result['message'] = $lang['PRICE_REQ'];
        die(json_encode($result));
    }
    $MainFileName = null;
    $main_imageName = '';
    $cat_id = validate_input($_POST['cat_id']);
    $title = validate_input($_POST['title']);
    $description = validate_input($_POST['description']);
    $price = validate_input($_POST['price']);
 
    // Valid formats
    $valid_formats = array("jpeg", "jpg", "png");
 
    /*Start Item Logo Image Uploading*/
    $file = $_FILES['main_image'];
    $filename = $file['name'];
    $ext = getExtension($filename);
    $ext = strtolower($ext);
    if (!empty($filename)) {
        //File extension check
        if (in_array($ext, $valid_formats)) {
            $main_path = ROOTPATH . "/storage/menu/";
            $filename = uniqid(time()) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $main_path . $filename)) {
                $MainFileName = $filename;
                resizeImage(150, $main_path . $filename, $main_path . $filename);
                resizeImage(60, $main_path . 'small_' . $filename, $main_path . $filename);
            } else {
                $result['success'] = false;
                $result['message'] = $lang['ERROR_IMAGE'];
                die(json_encode($result));
            }
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ONLY_JPG_ALLOW'];
            die(json_encode($result));
        }
    }
    /*End Item Logo Image Uploading*/
 
    if (trim($title) != '' && is_string($title)) {
 
        $insert_menu = ORM::for_table($config['db']['pre'] . 'menu')->create();
        $insert_menu->user_id = validate_input($_SESSION['user']['id']);
        $insert_menu->cat_id = $cat_id;
        $insert_menu->name = $title;
        $insert_menu->description = $description;
        $insert_menu->price = $price;
        if ($MainFileName) {
            $insert_menu->image = $MainFileName;
        }
        $insert_menu->save();
 
        $menu_id = $insert_menu->id();
 
        if ($menu_id) {
            $result['success'] = true;
            $result['message'] = $lang['SAVED_SUCCESS'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
 
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function delete_item()
{
    global $lang, $config;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
    $id = $_GET['id'];
    if (trim($id) != '') {
        $data = ORM::for_table($config['db']['pre'] . 'menu')
            ->where(array(
                'id' => $id,
                'user_id' => $_SESSION['user']['id'],
            ))
            ->delete_many();
 
        if ($data) {
            $result['success'] = true;
            $result['message'] = $lang['MENU_DELETED'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function add_image_item()
{
    global $config, $lang;
 
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    if (empty($_POST['title'])) {
        $result['success'] = false;
        $result['message'] = $lang['TITLE_REQ'];
        die(json_encode($result));
    }
 
    if (empty($_FILES['main_image']['name']) && empty($_POST['id'])) {
        $result['success'] = false;
        $result['message'] = $lang['IMAGE_REQ'];
        die(json_encode($result));
    }
 
    $MainFileName = null;
    $main_imageName = '';
    $title = validate_input($_POST['title']);
 
    // check if adding new item
    if (empty($_POST['id'])) {
        // Get usergroup details
        $group_id = get_user_group();
        // Get membership details
        switch ($group_id){
            case 'free':
                $plan = json_decode(get_option('free_membership_plan'), true);
                $settings = $plan['settings'];
                $limit = $settings['menu_limit'];
                break;
            case 'trial':
                $plan = json_decode(get_option('trial_membership_plan'), true);
                $settings = $plan['settings'];
                $limit = $settings['menu_limit'];
                break;
            default:
                $plan = ORM::for_table($config['db']['pre'] . 'plans')
                    ->select('settings')
                    ->where('id', $group_id)
                    ->find_one();
                if(!isset($plan['settings'])){
                    $plan = json_decode(get_option('free_membership_plan'), true);
                    $settings = $plan['settings'];
                    $limit = $settings['menu_limit'];
                }else{
                    $settings = json_decode($plan['settings'],true);
                    $limit = $settings['menu_limit'];
                }
                break;
        }
 
 
        if ($limit != "999") {
            $total = ORM::for_table($config['db']['pre'] . 'image_menu')
                ->where('user_id', $_SESSION['user']['id'])
                ->count();
 
            if ($total >= $limit) {
                $result['success'] = false;
                $result['message'] = $lang['LIMIT_EXCEED_UPGRADE'];
                die(json_encode($result));
            }
        }
    }
 
    // Valid formats
    $valid_formats = array("jpeg", "jpg", "png");
 
    /*Start Item Logo Image Uploading*/
    $file = $_FILES['main_image'];
    $filename = $file['name'];
    $ext = getExtension($filename);
    $ext = strtolower($ext);
    if (!empty($filename)) {
        //File extension check
        if (in_array($ext, $valid_formats)) {
            $main_path = ROOTPATH . "/storage/menu/";
            $filename = uniqid(time()) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $main_path . $filename)) {
                $MainFileName = $filename;
                resizeImage(1000, $main_path . $filename, $main_path . $filename);
            } else {
                $result['success'] = false;
                $result['message'] = $lang['ERROR_IMAGE'];
                die(json_encode($result));
            }
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ONLY_JPG_ALLOW'];
            die(json_encode($result));
        }
    }
    /*End Item Logo Image Uploading*/
 
    if (trim($title) != '' && is_string($title)) {
        if (!empty($_POST['id'])) {
            $insert_menu = ORM::for_table($config['db']['pre'] . 'image_menu')->find_one($_POST['id']);
        } else {
            $insert_menu = ORM::for_table($config['db']['pre'] . 'image_menu')->create();
        }
 
        $insert_menu->active = isset($_POST['active']) ? '1' : '0';
        $insert_menu->user_id = validate_input($_SESSION['user']['id']);
        $insert_menu->name = $title;
        if ($MainFileName) {
            $insert_menu->image = $MainFileName;
        }
        $insert_menu->save();
 
        $menu_id = $insert_menu->id();
 
        if ($menu_id) {
            $result['success'] = true;
            $result['message'] = $lang['SAVED_SUCCESS'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
 
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function get_image_menu()
{
    global $config;
    $result = ORM::for_table($config['db']['pre'] . 'image_menu')
        ->where('user_id', $_SESSION['user']['id'])
        ->find_one($_GET['id']);
 
    $response = array('success' => false);
    if (!empty($result)) {
        $response['success'] = true;
        $response['name'] = $result['name'];
        $response['active'] = $result['active'];
        $response['image'] = !empty($result['image'])
            ? $config['site_url'] . 'storage/menu/' . $result['image']
            : $config['site_url'] . 'storage/menu/' . 'default.png';
    }
    die(json_encode($response));
}
 
function delete_image_menu()
{
    global $lang, $config;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
    $id = $_GET['id'];
    if (trim($id) != '') {
        $data = ORM::for_table($config['db']['pre'] . 'image_menu')
            ->where(array(
                'id' => $id,
                'user_id' => $_SESSION['user']['id'],
            ))
            ->delete_many();
 
        if ($data) {
            $result['success'] = true;
            $result['message'] = $lang['MENU_DELETED'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function updateMenuPosition()
{
    global $config,$lang;
    $con = ORM::get_db();
    $position = $_POST['position'];
    if (is_array($position)) {
        foreach($position as $key => $id){
            $query = "UPDATE `".$config['db']['pre']."menu` SET `position` = '".$key."' WHERE `id` = '" . $id . "'";
            $con->query($query);
        }
 
        $result['success'] = true;
        $result['message'] = $lang['POSITION_UPDATED'];
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function updateExtrasPosition()
{
    global $config,$lang;
    $con = ORM::get_db();
    $position = $_POST['position'];
    if (is_array($position)) {
        foreach($position as $key => $id){
            $query = "UPDATE `".$config['db']['pre']."menu_extras` SET `position` = '".$key."' WHERE `id` = '" . $id . "'";
            $con->query($query);
        }
 
        $result['success'] = true;
        $result['message'] = $lang['POSITION_UPDATED'];
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function updateImageMenuPosition()
{
    global $config,$lang;
    $con = ORM::get_db();
    $position = $_POST['position'];
    if (is_array($position)) {
        foreach($position as $key => $id){
            $query = "UPDATE `".$config['db']['pre']."image_menu` SET `position` = '".$key."' WHERE `id` = '" . $id . "'";
            $con->query($query);
        }
 
        $result['success'] = true;
        $result['message'] = $lang['POSITION_UPDATED'];
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function addNewCat()
{
    global $config, $lang;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    // Get usergroup details
    $group_id = get_user_group();
    switch ($group_id){
        case 'free':
            $plan = json_decode(get_option('free_membership_plan'), true);
            $settings = $plan['settings'];
            $limit = $settings['category_limit'];
            break;
        case 'trial':
            $plan = json_decode(get_option('trial_membership_plan'), true);
            $settings = $plan['settings'];
            $limit = $settings['category_limit'];
            break;
        default:
            $plan = ORM::for_table($config['db']['pre'] . 'plans')
                ->select('settings')
                ->where('id', $group_id)
                ->find_one();
            if(!isset($plan['settings'])){
                $plan = json_decode(get_option('free_membership_plan'), true);
                $settings = $plan['settings'];
                $limit = $settings['category_limit'];
            }else{
                $settings = json_decode($plan['settings'],true);
                $limit = $settings['category_limit'];
            }
            break;
    }
 
    if ($limit != "999") {
        $total = ORM::for_table($config['db']['pre'] . 'catagory_main')
            ->where('user_id', $_SESSION['user']['id'])
            ->count();
 
        if ($total >= $limit) {
            $result['success'] = false;
            $result['message'] = $lang['LIMIT_EXCEED_UPGRADE'];
            die(json_encode($result));
        }
    }
 
    $name = validate_input($_POST['name']);
    $slug = '';
    if (trim($name) != '' && is_string($name)) {
        if ($slug == "")
            $slug = create_category_slug($name);
        else
            $slug = create_category_slug($slug);
 
        $insert_category = ORM::for_table($config['db']['pre'] . 'catagory_main')->create();
        $insert_category->cat_name = validate_input($name);
        $insert_category->slug = validate_input($slug);
        $insert_category->user_id = $_SESSION['user']['id'];
        $insert_category->save();
 
        $category_id = $insert_category->id();
 
        if ($category_id) {
            $result['success'] = true;
            $result['message'] = $lang['SAVED_SUCCESS'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
 
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function editCat()
{
    global $lang, $config;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
    $name = validate_input($_POST['name']);
    $id = validate_input($_POST['id']);
    if (trim($name) != '' && is_string($name) && trim($id) != '') {
        $catagory_update = ORM::for_table($config['db']['pre'] . 'catagory_main')
            ->use_id_column('cat_id')
            ->where(array(
                'user_id' => $_SESSION['user']['id'],
                'cat_id' => $id
            ))
            ->find_one();
        $catagory_update->set('cat_name', validate_input($name));
        $catagory_update->save();
 
        $result['success'] = true;
        $result['message'] = $lang['SAVED_SUCCESS'];
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function deleteCat()
{
    global $lang, $config;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
    $id = validate_input($_POST['id']);
    if (trim($id) != '') {
 
        $data = ORM::for_table($config['db']['pre'] . 'catagory_main')
            ->where(array(
                'user_id' => $_SESSION['user']['id'],
                'cat_id' => $id
            ))
            ->delete_many();
 
        if ($data) {
            $result['success'] = true;
            $result['message'] = $lang['CATEGORY_DELETED'];
        } else {
            $result['success'] = false;
            $result['message'] = $lang['ERROR_TRY_AGAIN'];
        }
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function updateCatPosition()
{
    global $config,$lang;
    $con = ORM::get_db();
    $position = $_POST['position'];
    if (is_array($position)) {
        foreach($position as $key => $catid){
            $query = "UPDATE `".$config['db']['pre']."catagory_main` SET `cat_order` = '".$key."' WHERE `cat_id` = '" . $catid . "'";
            $con->query($query);
        }
 
        $result['success'] = true;
        $result['message'] = $lang['POSITION_UPDATED'];
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
    die(json_encode($result));
}
 
function addMenuExtra()
{
    global $config, $lang;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    $title = validate_input($_POST['title']);
    $price = validate_input($_POST['price']);
    $menu_id = validate_input($_POST['menu_id']);
 
    if (trim($menu_id) == '' || empty($menu_id)) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    if (trim($title) == '' || empty($title)) {
        $result['success'] = false;
        $result['message'] = $lang['ALL_FIELDS_REQ'];
        die(json_encode($result));
    }
 
    if (trim($price) == '' || empty($price)) {
        $result['success'] = false;
        $result['message'] = $lang['ALL_FIELDS_REQ'];
        die(json_encode($result));
    }
 
    $insert = ORM::for_table($config['db']['pre'] . 'menu_extras')->create();
    $insert->title = validate_input($title);
    $insert->price = validate_input($price);
    $insert->menu_id = $menu_id;
    $insert->save();
 
    $id = $insert->id();
 
    if ($id) {
        $result['success'] = true;
        $result['message'] = $lang['SAVED_SUCCESS'];
    } else {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
    }
 
    die(json_encode($result));
}
 
function editMenuExtra()
{
    global $config, $lang;
    if (!checkloggedin()) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    $title = validate_input($_POST['title']);
    $price = validate_input($_POST['price']);
    $id = validate_input($_POST['id']);
 
    if (trim($id) == '' || empty($id)) {
        $result['success'] = false;
        $result['message'] = $lang['ERROR_TRY_AGAIN'];
        die(json_encode($result));
    }
 
    if (trim($title) == '' || empty($title)) {
        $result['success'] = false;
        $result['message'] = $lang['ALL_FIELDS_REQ'];
        die(json_encode($result));
    }
 
    if (trim($price) == '' || empty($price)) {
        $result['success'] = false;
        $result['message'] = $lang['ALL_FIELDS_REQ'];
        die(json_encode($result));
    }
 
    $insert = ORM::for_table($config['db']['pre'] . 'menu_extras')->find_one($id);
    $insert->title = validate_input($title);
    $insert->price = validate_input($price);
    $insert->active = isset($_POST['active']) ? 1 : 0;
    $insert->save();
 
    $result['success'] = true;
    $result['message'] = $lang['SAVED_SUCCESS'];
 
 
    die(json_encode($result));
}
 
function deleteMenuExtra()
{
    global $lang, $config;
 
    $result['success'] = false;
    $result['message'] = $lang['ERROR_TRY_AGAIN'];
    if (!checkloggedin()) {
        die(json_encode($result));
    }
    $id = $_POST['id'];
    if (trim($id) != '') {
        // check menu is with same user
        $menu_extra = ORM::for_table($config['db']['pre'] . 'menu_extras')->find_one($id);
 
        if (!empty($menu_extra['menu_id'])) {
            $menu = ORM::for_table($config['db']['pre'] . 'menu')
                ->where(array(
                    'id' => $menu_extra['menu_id'],
                    'user_id' => $_SESSION['user']['id'],
                ))
                ->find_one();
 
            if (!empty($menu['id'])) {
                $data = ORM::for_table($config['db']['pre'] . 'menu_extras')
                    ->where(array(
                        'id' => $id
                    ))
                    ->delete_many();
 
                if ($data) {
                    $result['success'] = true;
                    $result['message'] = $lang['SUCCESS_DELETE'];
                }
            }
        }
    }
    die(json_encode($result));
}
 
function ajaxlogin()
{
    global $config, $lang, $link;
    $loggedin = userlogin($_POST['username'], $_POST['password']);
    $result['success'] = false;
    $result['message'] = $lang['ERROR_TRY_AGAIN'];
    if (!is_array($loggedin)) {
        $result['message'] = $lang['USERNOTFOUND'];
    } elseif ($loggedin['status'] == 2) {
        $result['message'] = $lang['ACCOUNTBAN'];
    } else {
        $user_browser = $_SERVER['HTTP_USER_AGENT']; // Get the user-agent string of the user.
        $user_id = preg_replace("/[^0-9]+/", "", $loggedin['id']); // XSS protection as we might print this value
        $_SESSION['user']['id'] = $user_id;
        $username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $loggedin['username']); // XSS protection as we might print this value
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['login_string'] = hash('sha512', $loggedin['password'] . $user_browser);
        $_SESSION['user']['user_type'] = $loggedin['user_type'];
        update_lastactive();
 
        $result['success'] = true;
        $result['message'] = $link['DASHBOARD'];
    }
    die(json_encode($result));
}
 
function email_verify()
{
    global $config, $lang;
 
    if (checkloggedin()) {
        /*SEND CONFIRMATION EMAIL*/
        email_template("signup_confirm", $_SESSION['user']['id']);
 
        $respond = $lang['SENT'];
        echo '<a class="button gray" href="javascript:void(0);">' . $respond . '</a>';
        die();
 
    } else {
        header("Location: " . $config['site_url'] . "login");
        exit;
    }
}
 
function submitBlogComment()
{
    global $config, $lang;
    $comment_error = $name = $email = $user_id = $comment = null;
    $result = array();
    $is_admin = '0';
    $is_login = false;
    if (checkloggedin()) {
        $is_login = true;
    }
    $avatar = $config['site_url'] . 'storage/profile/default_user.png';
    if (!($is_login || isset($_SESSION['admin']['id']))) {
        if (empty($_POST['user_name']) || empty($_POST['user_email'])) {
            $comment_error = $lang['ALL_FIELDS_REQ'];
        } else {
            $name = removeEmailAndPhoneFromString($_POST['user_name']);
            $email = $_POST['user_email'];
 
            $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
            if (!preg_match($regex, $email)) {
                $comment_error = $lang['EMAILINV'];
            }
        }
    } else if ($is_login && isset($_SESSION['admin']['id'])) {
        $commenting_as = 'admin';
        if (!empty($_POST['commenting-as'])) {
            if (in_array($_POST['commenting-as'], array('admin', 'user'))) {
                $commenting_as = $_POST['commenting-as'];
            }
        }
        if ($commenting_as == 'admin') {
            $is_admin = '1';
            $info = ORM::for_table($config['db']['pre'] . 'admins')->find_one($_SESSION['admin']['id']);
            $user_id = $_SESSION['admin']['id'];
            $name = $info['name'];
            $email = $info['email'];
            if (!empty($info['image'])) {
                $avatar = $config['site_url'] . 'storage/profile/' . $info['image'];
            }
        } else {
            $user_id = $_SESSION['user']['id'];
            $user_data = get_user_data(null, $user_id);
            $name = $user_data['name'];
            $email = $user_data['email'];
            if (!empty($user_data['image'])) {
                $avatar = $config['site_url'] . 'storage/profile/' . $user_data['image'];
            }
        }
    } else if ($is_login) {
        $user_id = $_SESSION['user']['id'];
        $user_data = get_user_data(null, $user_id);
        $name = $user_data['name'];
        $email = $user_data['email'];
        if (!empty($user_data['image'])) {
            $avatar = $config['site_url'] . 'storage/profile/' . $user_data['image'];
        }
    } else if (isset($_SESSION['admin']['id'])) {
        $is_admin = '1';
        $info = ORM::for_table($config['db']['pre'] . 'admins')->find_one($_SESSION['admin']['id']);
        $user_id = $_SESSION['admin']['id'];
        $name = $info['name'];
        $email = $info['email'];
        if (!empty($info['image'])) {
            $avatar = $config['site_url'] . 'storage/profile/' . $info['image'];
        }
    } else {
        $comment_error = $lang['LOGIN_POST_COMMENT'];
    }
 
    if (empty($_POST['comment'])) {
        $comment_error = $lang['ALL_FIELDS_REQ'];
    } else {
        $comment = validate_input($_POST['comment']);
    }
 
    $duplicates = ORM::for_table($config['db']['pre'] . 'blog_comment')
        ->where('blog_id', $_POST['comment_post_ID'])
        ->where('name', $name)
        ->where('email', $email)
        ->where('comment', $comment)
        ->count();
 
    if ($duplicates > 0) {
        $comment_error = $lang['DUPLICATE_COMMENT'];
    }
 
    if (!$comment_error) {
        if ($is_admin) {
            $approve = '1';
        } else {
            if ($config['blog_comment_approval'] == 1) {
                $approve = '0';
            } else if ($config['blog_comment_approval'] == 2) {
                if ($is_login) {
                    $approve = '1';
                } else {
                    $approve = '0';
                }
            } else {
                $approve = '1';
            }
        }
 
        $blog_cmnt = ORM::for_table($config['db']['pre'] . 'blog_comment')->create();
        $blog_cmnt->blog_id = $_POST['comment_post_ID'];
        $blog_cmnt->user_id = $user_id;
        $blog_cmnt->is_admin = $is_admin;
        $blog_cmnt->name = $name;
        $blog_cmnt->email = $email;
        $blog_cmnt->comment = $comment;
        $blog_cmnt->created_at = date('Y-m-d H:i:s');
        $blog_cmnt->active = $approve;
        $blog_cmnt->parent = $_POST['comment_parent'];
        $blog_cmnt->save();
 
        $id = $blog_cmnt->id();
        $date = date('d, M Y');
        $approve_txt = '';
        if ($approve == '0') {
            $approve_txt = '<em><small>' . $lang['COMMENT_REVIEW'] . '</small></em>';
        }
 
        $html = '<li id="li-comment-' . $id . '"';
        if ($_POST['comment_parent'] != 0) {
            $html .= 'class="children-2"';
        }
        $html .= '>
                   <div class="comments-box" id="comment-' . $id . '">
                        <div class="comments-avatar">
                            <img src="' . $avatar . '" alt="' . $name . '">
                        </div>
                        <div class="comments-text">
                            <div class="avatar-name">
                                <h5>' . $name . '</h5>
                                <span>' . $date . '</span>
                            </div>
                            ' . $approve_txt . '
                            <p>' . nl2br(stripcslashes($comment)) . '</p>
                        </div>
                    </div>
                </li>';
 
        $result['success'] = true;
        $result['html'] = $html;
        $result['id'] = $id;
    } else {
        $result['success'] = false;
        $result['error'] = $comment_error;
    }
    die(json_encode($result));
}
 
/**
 * save restaurant order
 */
function sendRestaurantOrder(){
    global $config, $lang, $link;
    $result = array('success'=>false, 'message' => $lang['ERROR_TRY_AGAIN']);
 
    if (!empty($_POST['items']) && !empty($_POST['restaurant'])) {
 
        if (!isset($_POST['ordering-type']) || trim($_POST['ordering-type']) == '')
        {
            /* Check order type is sent */
            $result['message'] = $lang['ORDERING_TYPE_REQUIRED'];
        }
        else if (!in_array($_POST['ordering-type'], array('on-table', 'takeaway', 'delivery')))
        {
            /* Check order type is not changed */
            $result['message'] = $lang['ORDERING_TYPE_REQUIRED'];
        }
        else if (!isset($_POST['name']) || trim($_POST['name']) == '')
        {
            $result['message'] = $lang['YOUR_NAME_REQUIRED'];
        }
        else if ($_POST['ordering-type'] == 'on-table' && (!isset($_POST['table']) || trim($_POST['table']) == '' && !is_numeric($_POST['table'])))
        {
            $result['message'] = $lang['TABLE_NUMBER_REQUIRED'];
        }
        else if ($_POST['ordering-type'] != 'on-table' && (!isset($_POST['phone-number']) || trim($_POST['phone-number']) == '' && !is_numeric($_POST['phone-number'])))
        {
            $result['message'] = $lang['PHONE_NUMBER_REQUIRED'];
        }
        else if ($_POST['ordering-type'] == 'delivery' && (!isset($_POST['address']) || trim($_POST['address']) == ''))
        {
            $result['message'] = $lang['ADDRESS_REQUIRED'];
        }
        else
        {
            $amount = 0;
            $restaurant = ORM::for_table($config['db']['pre'] . 'restaurant')
                ->where('id', $_POST['restaurant'])
                ->find_one();
 
            if(isset($restaurant['id'])) {
                // save order
                $order = ORM::for_table($config['db']['pre'] . 'orders')->create();
                $order->restaurant_id = validate_input($_POST['restaurant']);
                $order->type = validate_input($_POST['ordering-type']);
                $order->customer_name = validate_input($_POST['name']);
 
                if($_POST['ordering-type'] == 'on-table') {
                    /* on table */
                    $order->table_number = validate_input($_POST['table']);
                } else if ($_POST['ordering-type'] == 'takeaway'){
                    /* takeaway */
                    $order->phone_number = validate_input($_POST['phone-number']);
                } else if ($_POST['ordering-type'] == 'delivery'){
                    /* delivery */
                    $order->phone_number = validate_input($_POST['phone-number']);
                    $order->address = validate_input($_POST['address']);
                }
 
                $order->message = validate_input($_POST['message']);
                $order->created_at = date('Y-m-d H:i:s');
                $order->save();
 
                $items = json_decode($_POST['items'], true);
                $order_msg = '';
                foreach ($items as $item) {
                    $item_id = $item['id'];
                    $quantity = $item['quantity'];
 
                    $menu = ORM::for_table($config['db']['pre'] . 'menu')
                        ->where('id', $item_id)
                        ->find_one();
 
                    if(isset($menu['id'])) {
                        // save order items
                        $order_item = ORM::for_table($config['db']['pre'] . 'order_items')->create();
                        $order_item->order_id = $order->id();
                        $order_item->item_id = validate_input($item_id);
                        $order_item->quantity = validate_input($quantity);
                        $order_item->save();
 
                        $amount += $menu['price'] * $quantity;
 
                        if(!$config['email_template']){
                            $order_msg .= $menu['name']. ($quantity > 1 ? ' &times; '.$quantity:'').'<br>';
                        }else{
                            $order_msg .= $menu['name']. ($quantity > 1 ? ' X '.$quantity:'')."\n";
                        }
 
                        $extras = $item['extras'];
                        foreach ($extras as $extra) {
                            $menu_extra = ORM::for_table($config['db']['pre'] . 'menu_extras')
                                ->where('id', $extra['id'])
                                ->find_one();
 
                            if(isset($menu_extra['id'])) {
                                // save order items extras
                                $order_item_extras = ORM::for_table($config['db']['pre'] . 'order_item_extras')->create();
                                $order_item_extras->order_item_id = $order_item->id();
                                $order_item_extras->extra_id = validate_input($extra['id']);
                                $order_item_extras->save();
 
                                $amount += $menu_extra['price'] * $quantity;
 
                                if(!$config['email_template']){
                                    $order_msg .= $menu_extra['title'].'<br>';
                                }else{
                                    $order_msg .= $menu_extra['title']."\n";
                                }
                            }
                        }
                        if(!$config['email_template']){
                            $order_msg .= '<br>';
                        }else{
                            $order_msg .= "\n";
                        }
                    }
                }
 
                $page = new HtmlTemplate();
                $page->html = $config['email_sub_new_order'];
                $page->SetParameter('RESTAURANT_NAME', $restaurant['name']);
                $page->SetParameter('CUSTOMER_NAME', validate_input($_POST['name']));
                $page->SetParameter('TABLE_NUMBER', validate_input($_POST['table']));
                $email_subject = $page->CreatePageReturn($lang, $config, $link);
 
                $page = new HtmlTemplate();
                $page->html = $config['email_message_new_order'];
                $page->SetParameter('RESTAURANT_NAME', $restaurant['name']);
                $page->SetParameter('CUSTOMER_NAME', validate_input($_POST['name']));
                $page->SetParameter('TABLE_NUMBER', validate_input($_POST['table']));
                $page->SetParameter('ORDER', $order_msg);
                $page->SetParameter('MESSAGE', validate_input($_POST['message']));
                $email_body = $page->CreatePageReturn($lang, $config, $link);
 
                $userdata = get_user_data(null,$restaurant['user_id']);
 
                /* send email to restaurants */
                email($userdata['email'], $userdata['name'], $email_subject, $email_body);
 
                $result['success'] = true;
                $result['message'] = '';
                if($_POST['pay_via'] == 'pay_online'){
                    /* Save in session for payment page */
                    $payment_type = "order";
                    $access_token = uniqid();
 
                    $_SESSION['quickad'][$access_token]['name'] = validate_input($restaurant['name']);
                    $_SESSION['quickad'][$access_token]['restaurant_id'] = $restaurant['id'];
                    $_SESSION['quickad'][$access_token]['amount'] = $amount;
                    $_SESSION['quickad'][$access_token]['payment_type'] = $payment_type;
                    $_SESSION['quickad'][$access_token]['order_id'] = $order->id();
 
                    $url = $link['PAYMENT']."/" . $access_token;
                    $result['message'] = $url;
                }
            }
        }
    }
    die(json_encode($result));
}
 
/**
 * Complete order
 */
function completeOrder(){
    global $config, $lang;
    $result = array('success'=>false, 'message' => $lang['ERROR_TRY_AGAIN']);
    if(isset($_POST['id'])) {
        // get restaurant
        $restaurant = ORM::for_table($config['db']['pre'] . 'restaurant')
            ->where('user_id', $_SESSION['user']['id'])
            ->find_one();
 
        $orders = ORM::for_table($config['db']['pre'] . 'orders')
            ->where(array(
                'restaurant_id' => $restaurant['id'],
                'id' => $_POST['id']
            ))
            ->find_one();
        $orders->status = 'completed';
        $orders->save();
 
        $result['success'] = true;
        $result['message'] = '';
    }
    die(json_encode($result));
}
 
/**
 * Delete order
 */
function deleteOrder(){
    global $config, $lang;
    $result = array('success'=>false, 'message' => $lang['ERROR_TRY_AGAIN']);
    if(isset($_POST['id'])) {
        // get restaurant
        $restaurant = ORM::for_table($config['db']['pre'] . 'restaurant')
            ->where('user_id', $_SESSION['user']['id'])
            ->find_one();
 
        // get order
        $orders = ORM::for_table($config['db']['pre'] . 'orders')
            ->where(array(
                'restaurant_id' => $restaurant['id'],
                'id' => $_POST['id']
            ))
            ->find_one();
 
        if(isset($orders['id'])){
            // get order items
            $order_items = ORM::for_table($config['db']['pre'] . 'order_items')
                ->where(array(
                    'order_id' => $orders['id']
                ))
                ->find_many();
 
            foreach ($order_items as $order_item){
                // delete item extras
                ORM::for_table($config['db']['pre'] . 'order_item_extras')
                    ->where(array(
                        'order_item_id' => $order_item['id']
                    ))
                    ->delete_many();
            }
 
            // delete order items
            ORM::for_table($config['db']['pre'] . 'order_items')
                ->where(array(
                    'order_id' => $orders['id']
                ))
                ->delete_many();
 
            // delete order
            ORM::for_table($config['db']['pre'] . 'orders')
                ->where(array(
                    'restaurant_id' => $restaurant['id'],
                    'id' => $orders['id']
                ))
                ->delete_many();
        }
 
        $result['success'] = true;
        $result['message'] = '';
    }
    die(json_encode($result));
}
 
 
/**
 * Get order for notifications
 */
function getOrders(){
    global $config, $lang;
    $orders_data = array();
 
    if (checkloggedin()) {
        $ses_userdata = get_user_data($_SESSION['user']['username']);
        $currency = !empty($ses_userdata['currency']) ? $ses_userdata['currency'] : get_option('currency_code');
 
        $restaurant = ORM::for_table($config['db']['pre'] . 'restaurant')
            ->where('user_id', $_SESSION['user']['id'])
            ->find_one();
 
        if (isset($restaurant['user_id'])) {
            // get orders
            $orders = ORM::for_table($config['db']['pre'] . 'orders')
                ->where(array(
                    'restaurant_id' => $restaurant['id'],
                    'seen' => 0
                ))
                ->order_by_desc('id')
                ->find_many();
 
            foreach ($orders as $order) {
                $orders_data[$order['id']]['id'] = $order['id'];
                $orders_data[$order['id']]['type'] = $order['type'];
                $orders_data[$order['id']]['customer_name'] = $order['customer_name'];
                $orders_data[$order['id']]['table_number'] = $order['table_number'];
                $orders_data[$order['id']]['phone_number'] = $order['phone_number'];
                $orders_data[$order['id']]['address'] = $order['address'];
                $orders_data[$order['id']]['is_paid'] = $order['is_paid'];
                $orders_data[$order['id']]['status'] = $order['status'];
                $orders_data[$order['id']]['message'] = $order['message'];
                $orders_data[$order['id']]['created_at'] = date('d M Y h:i A',strtotime($order['created_at']));
 
                // get order items
                $order_items = ORM::for_table($config['db']['pre'] . 'order_items')
                    ->table_alias('oi')
                    ->select_many('oi.*', 'm.name', 'm.price')
                    ->where(array(
                        'order_id' => $order['id']
                    ))
                    ->join($config['db']['pre'] . 'menu', array('oi.item_id', '=', 'm.id'), 'm')
                    ->order_by_desc('id')
                    ->find_many();
 
                $orders_data[$order['id']]['items_tpl'] = '';
                $price = 0;
                foreach ($order_items as $order_item) {
                    $tpl = '<div class="order-table-item">';
                    $tpl .= '<strong><i class="icon-material-outline-restaurant"></i> '.$order_item['name'].'</strong>';
                    if($order_item['quantity'] > 1){
                        $tpl .= ' &times; '.$order_item['quantity'];
                    }
                    $price += $order_item['price'] * $order_item['quantity'];
 
                    // get order extras
                    $order_item_extras = ORM::for_table($config['db']['pre'] . 'order_item_extras')
                        ->table_alias('oie')
                        ->select_many('oie.*', 'me.title', 'me.price')
                        ->where(array(
                            'order_item_id' => $order_item['id']
                        ))
                        ->join($config['db']['pre'] . 'menu_extras', array('oie.extra_id', '=', 'me.id'), 'me')
                        ->order_by_desc('id')
                        ->find_many();
                    if($order_item_extras->count()) {
                        $tpl .= '<div  class="padding-left-10">';
                        foreach ($order_item_extras as $order_item_extra) {
                            $price += $order_item_extra['price'] * $order_item['quantity'];
                            $tpl .= '<div><i class="icon-feather-plus"></i> ' . $order_item_extra['title'].'</div>';
                        }
                        $tpl .= '</div>';
                    }
                    $tpl .= '</div>';
                    $orders_data[$order['id']]['items_tpl'] .= $tpl;
                    $orders_data[$order['id']]['price'] = price_format($price,$currency);
                }
 
                $orders = ORM::for_table($config['db']['pre'] . 'orders')->find_one($order['id']);
                $orders->seen = 1;
                $orders->save();
            }
        }
    }
    die(json_encode($orders_data));
}