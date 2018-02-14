/**
 * @copyright	Copyright (C) 2016 brainforge (www.brainforge.co.uk). All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

function BFPIfadeoutAll(imageCount, prodid) {
  for (i=1; i<imageCount; i++) {
    jQuery("#hikashop_image_for_"+prodid+"_"+i).fadeOut(0);
  }
}

function BFPIswapImage(index, imageCount, prodid, fadeouttime, fadeintime) {
  console.log("o:#hikashop_image_for_"+prodid+"_"+index);
  jQuery("#hikashop_image_for_"+prodid+"_"+index).fadeOut(fadeouttime);
  index += 1;
  if (index >= imageCount) index = 0;
  console.log("i:#hikashop_image_for_"+prodid+"_"+index);
  jQuery("#hikashop_image_for_"+prodid+"_"+index).fadeIn(fadeintime);
  return index;
}