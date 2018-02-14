<?php
/**
 * @copyright	Copyright (C) 2016 brainforge (www.brainforge.co.uk). All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Joomla User plugin
 *
 * @package		Joomla.Plugin
 * @subpackage	Hikashop.joomla
 */
class plgHikashopBFproductImages extends JPlugin {

  static $_params = null;
  static $_views = array();
  static $_js1;
  static $_js2;
  static $_js3;
  static $_fadeintime = 400;
  static $_fadeouttime = 400;
  static $_displaytime = 2000;
  static $_productmode = 'E';
  static $_products = array();

  public function __construct($subject, $config) {
    parent::__construct($subject, $config);
    self::loadParams();
  }

  private function loadParams() {
    if (!empty(self::$_params)) return true;
    $plugin = JPluginHelper::getPlugin('hikashop', 'bfproductimages');
    if (empty($plugin)) return false;
    self::$_params = new JRegistry($plugin->params);
    return !empty(self::$_params);
  }

  private function getParam($name, $default=null) {
    if (!self::loadParams()) return $default;
    return self::$_params->get($name, $default);
  }

  public function onHikashopBeforeDisplayView(&$view) {
    if (JFactory::getApplication()->isSite()) {
      switch(@$view->ctrl) {
        case null:
        case '':
          break;
        case 'product':
          ob_start();
        default:
          self::$_views[] = $view->ctrl;
          break;
      }
    }

    if (JFactory::getApplication()->isAdmin()) {
    }
  }
  
  public function onHikashopAfterDisplayView(&$view) {
    if (JFactory::getApplication()->isSite()) {
      if (!empty(self::$_views)) {
        switch(array_pop(self::$_views)) {
          case 'product':
            $html = ob_get_contents();
            ob_end_clean();

            plgHikashopBFproductImages::$_js1 = '';
            plgHikashopBFproductImages::$_js2 = '';
            plgHikashopBFproductImages::$_js3 = '';
            plgHikashopBFproductImages::$_fadeintime   = plgHikashopBFproductImages::getParam('fadeintime', 400);
            plgHikashopBFproductImages::$_fadeouttime  = plgHikashopBFproductImages::getParam('fadeouttime', 400);
            plgHikashopBFproductImages::$_displaytime  = plgHikashopBFproductImages::getParam('displaytime', 2000);
            plgHikashopBFproductImages::$_productmode  = plgHikashopBFproductImages::getParam('productmode', 'E');
            plgHikashopBFproductImages::$_products     = plgHikashopBFproductImages::getParam('products');
            
            $html = preg_replace_callback(
                            '/(<div class="hikashop_product_image">[^<]*<div class="hikashop_product_image_subdiv">[^<]*<a[^>]*href=")([^"]*)("[^>]*>[^<]*)(<img)([^>]*src=")([^"]*)("[^>]*>)([^<]*<\\/a>[^<]*<\\/div>[^<]*<\\/div>)/sm',
                            function($matches) {
                              switch (count($matches)) {
                                case 0:
                                  return '';
                                case 9:
                                  $href = explode('/', $matches[2]);
                                  $prodLink = array_pop($href);
                                  if (strpos($prodLink, 'category_pathway-') === 0) {
                                    $prodLink = array_pop($href);
                                  }
                                  $prodid = plgHikashopBFproductImages::getProdId($prodLink);
                                  $images = plgHikashopBFproductImages::getImagesForProduct($prodid);
                                  if (count($images) < 2) {
                                    return $matches[0];
                                  }
                                  
                                  $helperImage = hikashop_get('helper.image');
                                  $dirname = dirname($matches[6]) . '/';
                                  $thumbparams = sscanf(basename($dirname), '%dx%d%s');

                                  $html = $matches[1] . $matches[2] . $matches[3];
                                  $html .= '<div id="hikashop_product_images_for_' . $prodid . '" class="hikashop_product_images_for" onmouseenter="BFPIhover=1;" onmouseleave="BFPIhover=0;">';
                                  foreach($images as $id=>$image) {
                                    $helperImage->getThumbnail($image,
                                                         array('width'=>$thumbparams[0],
                                                               'height'=>$thumbparams[1]),
                                                         array('default' => 1,
                                                               'scale' => 'inside',
                                                               'forcesize' => (@$thumbparams[2] == 'f'))
                                                 );
                                    $html .= '<div class="hikashop_product_images">';
                                    $html .= $matches[4];
                                    $html .= ' id="hikashop_image_for_' . $prodid . '_' . $id . '"';
                                    $html .= $matches[5];
                                    $html .= $dirname . $image;
                                    $html .= $matches[7];
                                    $html .= '</div>';
                                  }
                                  $html .= '</div>';
                                  $html .= $matches[8];

                                  plgHikashopBFproductImages::$_js1 .= 'var BFPIindex' . $prodid . ' = 0;
';
                                  plgHikashopBFproductImages::$_js2 .= 'BFPIfadeoutAll(' . count($images) . ',' . $prodid . ');
';
                                  plgHikashopBFproductImages::$_js3 .= 'BFPIindex' . $prodid . ' = BFPIswapImage(BFPIindex' . $prodid . ',' . count($images) . ',' . $prodid . ',' . plgHikashopBFproductImages::$_fadeouttime . ',' . plgHikashopBFproductImages::$_fadeintime . ');
';                                  
                                  return $html;
                                 default:
                                  return $matches[0];
                              }
                                               }, $html);
            echo $html;
            if (!empty(plgHikashopBFproductImages::$_js1)) {
              JFactory::getDocument()->addScriptDeclaration(plgHikashopBFproductImages::$_js1 . '
var BFPIhover = 0;              
jQuery(document).ready(function() {
' . plgHikashopBFproductImages::$_js2 . '
  setInterval(function() {
    if (BFPIhover == 0) {
' . plgHikashopBFproductImages::$_js3 . '
    }
  }, ' .  plgHikashopBFproductImages::$_displaytime . ');
});
');
              JHtml::script(JUri::base() . 'plugins/hikashop/bfproductimages/bfproductimages.js');
              JHtml::stylesheet(JUri::base() . 'plugins/hikashop/bfproductimages/bfproductimages.css');
            }
            break;
          default:
            break;
        }
      }
    }
  }
  
  function getProdId($prodLink) {
    $prodid = intval($prodLink);
    if (empty($prodid)) {
  		$db = JFactory::getDBO();
  		$query = 'SELECT CASE product.product_parent_id WHEN 0 THEN product.product_id ELSE product.product_parent_id END '.
          			' FROM '.hikashop_table('product').' AS product '.
                "WHERE product.product_alias =  '" . $prodLink ."'";
  		$db->setQuery($query);
			$prodid = $db->loadResult();
    }
    return $prodid;
  }
  
  function &getImagesForProduct($product_id) {
  	$ret = array();
  	if(empty($product_id))
  	  return $ret;

  	$db = JFactory::getDBO();
  	$query = 'SELECT product.product_id '.
        		' FROM '.hikashop_table('product').' AS product '.
        		' WHERE product.product_published = 1' .
            ' AND ' . $product_id . ' IN ( product.product_id, product.product_parent_id )' . 
        		' AND NOT EXISTS ( '.
                		'SELECT 1'.
                		' FROM '.hikashop_table('product').' AS product1'.
                		' WHERE product1.product_parent_id = product.product_id'.
                		  		 ' )';

    if (!empty(plgHikashopBFproductImages::$_products)) {
      switch(plgHikashopBFproductImages::$_productmode) {
        case 'E':
          $query .= " AND product.product_id NOT IN (' " . implode("','", plgHikashopBFproductImages::$_products) . "' )";
          $query .= " AND product.product_parent_id NOT IN (' " . implode("','", plgHikashopBFproductImages::$_products) . "' )";
          break;
        case 'I':
          $query .= " AND ( product.product_id IN (' " . implode("','", plgHikashopBFproductImages::$_products) . "' )";
          $query .= "    OR product.product_parent_id IN (' " . implode("','", plgHikashopBFproductImages::$_products) . "' ) )";
          break;
      }
    }

  	$db->setQuery($query);
  	if(!HIKASHOP_J25)
  		$products = $db->loadResultArray();
  	else
  		$products = $db->loadColumn();
        
    if (empty($products)) {
      return $ret;
    }
  
  	$query = 'SELECT DISTINCT file_path  ' .
               'FROM '.hikashop_table('file').' ' .
               'WHERE file_ref_id IN ('.implode(',',$products).') ' .
               'AND file_type = \'product\' ' .
               'AND file_ordering = 0 ' .
               'ORDER BY RAND()';
  	$db->setQuery($query);
  	$images = $db->loadColumn();
    return $images;
  }
}
