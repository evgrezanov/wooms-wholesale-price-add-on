<?php

/**
 * Plugin Name: WooMS Multi Price integration Add-on
 * Plugin URI: https://github.com/evgrezanov/wooms-wholesale-price-add-on
 * Description: Добавляет механизм сохранения оптовой цены в метаполя продукта
 * Version: 1.0
 */

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Synchronization the stock of goods from MoySklad
 */
class MultiPrice
{
    static public $config_whprice_meta = 'ced_cwsm_wholesale_price';

    /**
     * The init
     */
    public static function init()
    {
        add_filter('wooms_product_save', array(__CLASS__, 'product_chg_extra_price'), 10, 2);
        add_filter('wooms_variation_save', array(__CLASS__, 'product_chg_extra_price'), 10, 2);
        add_action('admin_init', array(__CLASS__, 'settings_init'), $priority = 101, $accepted_args = 1);
    }

    /**
     * Change extra price
     */
    public static function product_chg_extra_price($product, $data_api)
    {
        $product_id = $product->get_id();

        $price = 0;
        $price_meta = [];

        if ($price_name = get_option('wooms_extra_price_id')) {
            foreach ($data_api["salePrices"] as $price_item) {
                if ($price_item["priceType"]['name'] == $price_name) {
                    $price = $price_item["value"];
                    $price_meta = $price_item;
                }
            }
        }

        if (!empty($price)) {

            $price = floatval($price) / 100;
            $price = round($price, 2);

            update_post_meta($product_id, self::$config_whprice_meta, $price);
            $product->update_meta_data(self::$config_whprice_meta, $price);
            do_action(
                'wooms_logger',
                __CLASS__,
                sprintf('Обновлена цена типа "%s" = %s. Для продукта ИД: %s', $price_name, $price, $product_id)
            );
        }

        return $product;
    }


    /**
     * Add settings
     */
    public static function settings_init()
    {
        register_setting('mss-settings', 'wooms_extra_price_id');
        add_settings_field(
            $id = 'wooms_extra_price_id',
            $title = 'Дополнительный Тип Цены',
            $callback = array(__CLASS__, 'display_field_wooms_extra_price_id'),
            $page = 'mss-settings',
            $section = 'woomss_section_other'
        );
    }

    /**
     * display_field_wooms_price_id
     */
    public static function display_field_wooms_extra_price_id()
    {
        $id = 'wooms_extra_price_id';
        printf('<input type="text" name="%s" value="%s" />', $id, sanitize_text_field(get_option($id)));
        echo '<p><small>Укажите наименование дополнительной цены. Система будет записывать его в поле Wholesale Price.</small></p>';
    }
}

MultiPrice::init();