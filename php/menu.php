<?php
if (checkloggedin()) {
    $errors = array();
    $cat = $image_menu = array();

    $ses_userdata = get_user_data($_SESSION['user']['username']);
    $currency = !empty($ses_userdata['currency'])?$ses_userdata['currency']:get_option('currency_code');

    $restaurant = ORM::for_table($config['db']['pre'].'restaurant')
        ->where('user_id', $_SESSION['user']['id'])
        ->find_one();

    $restaurant_template = isset($restaurant['id'])
        ? get_restaurant_option($restaurant['id'],'restaurant_template','classic-theme')
        : 'classic-theme';

    if($restaurant_template != 'flipbook' && $restaurant_template != 'simple-menu') {
        $result = ORM::for_table($config['db']['pre'] . 'catagory_main')
            ->where('user_id', $_SESSION['user']['id'])
            ->order_by_asc('cat_order')
            ->find_many();
        $count = 0;
        foreach ($result as $info) {
            $cat[$count]['id'] = $info['cat_id'];
            $cat[$count]['name'] = $info['cat_name'];

            $count_menu = ORM::for_table($config['db']['pre'] . 'menu')
                ->where(array(
                    'cat_id' => $info['cat_id']
                ))
                ->count();
            $cat[$count]['menu_count'] = $count_menu;
            if ($count_menu) {
                $menu_tpl = '';
                $menu = ORM::for_table($config['db']['pre'] . 'menu')
                    ->where(array(
                        'cat_id' => $info['cat_id']
                    ))
                    ->order_by_asc('position')
                    ->find_many();
                foreach ($menu as $info2) {
                    $menuId = $info2['id'];
                    $menuName = $info2['name'];
                    $menuDesc = $info2['description'];
                    $menuPrice = price_format($info2['price'], $currency);
                    $menuImage = !empty($info2['image']) ? $info2['image'] : 'default.png';

                    $menu_tpl .= '
                <div class="dashboard-box margin-top-0 margin-bottom-15" data-menuid="' . $menuId . '">
                    <div class="headline">
                            <h3><i class="icon-feather-menu quickad-js-handle"></i><img class="menu-avatar" src="' . $config['site_url'] . 'storage/menu/' . $menuImage . '" alt="' . $menuName . '"> ' . $menuName . '</h3>
                            <div class="margin-left-auto">
                                <a href="#" data-id="' . $menuId . '" data-catid="' . $info['cat_id'] . '" class="button ripple-effect btn-sm edit_menu_item" title="' . $lang['EDIT_MENU'] . '" data-tippy-placement="top"><i class="icon-feather-edit"></i></a>
                                    <a href="' . $link['MENU'] . '/' . $menuId . '" class="button ripple-effect btn-sm" title="' . $lang['EXTRAS'] . '" data-tippy-placement="top"><i class="icon-feather-layers"></i></a>
                                    <a href="#" data-id="' . $menuId . '" class="popup-with-zoom-anim button red ripple-effect btn-sm delete_menu_item" title="' . $lang['DELETE_MENU'] . '" data-tippy-placement="top"><i class="icon-feather-trash-2"></i></a>
                            </div>
                        </div>
                    </div>
                ';

                    $cat[$count]['menu'] = $menu_tpl;
                }
            } else {
                $cat[$count]['menu'] = '<div class="margin-bottom-30 text-center">' . $lang['MENU_NOT_AVAILABLE'] . '</div>';
            }
            $count++;
        }
    } else {
        $result = ORM::for_table($config['db']['pre'] . 'image_menu')
            ->where('user_id', $_SESSION['user']['id'])
            ->order_by_asc('position')
            ->find_many();

        foreach ($result as $info) {
            $image_menu[$info['id']]['id'] = $info['id'];
            $image_menu[$info['id']]['name'] = $info['name'];
            $image_menu[$info['id']]['image'] = !empty($info['image']) ? $info['image'] : 'default.png';
            $image_menu[$info['id']]['active'] = $info['active'];
        }
    }

    if($restaurant_template != 'flipbook' && $restaurant_template != 'simple-menu'){
        $page = new HtmlTemplate ('templates/' . $config['tpl_name'] . '/menu.tpl');
    }else{
        $page = new HtmlTemplate ('templates/' . $config['tpl_name'] . '/menu-image.tpl');
    }

    $page->SetParameter('OVERALL_HEADER', create_header($lang['MANAGE_MENU']));
    $page->SetParameter('RESTAURANT_TEMPLATE',$restaurant_template);
    $page->SetLoop('CATEGORY', $cat);
    $page->SetLoop('IMAGE_MENU', $image_menu);
    $page->SetParameter('OVERALL_FOOTER', create_footer());
    $page->CreatePageEcho();
} else {
    headerRedirect($link['LOGIN']);
}