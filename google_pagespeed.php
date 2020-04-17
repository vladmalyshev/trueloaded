<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

set_time_limit (0);
require('includes/application_top.php');

if (tep_not_null($_GET['page'])) {
  $site_url = tep_href_link($_GET['page'], 'nowatermark=1');
} else {
  $site_url = tep_href_link(FILENAME_DEFAULT, 'nowatermark=1');
}
$contents = @file_get_contents('https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url=' . urlencode($site_url));
$result = json_decode($contents);

$image_urls_array = array();
if (is_object($result) && property_exists($result, 'formattedResults') && is_array($result->formattedResults->ruleResults->OptimizeImages->urlBlocks)) {
  foreach ($result->formattedResults->ruleResults->OptimizeImages->urlBlocks as $block) {
    if (property_exists($block, 'urls') && is_array($block->urls)) {
      foreach ($block->urls as $url) {
        if (property_exists($url, 'result') && is_array($url->result->args)) {
          foreach ($url->result->args as $args) {
            if (property_exists($args, 'type') && property_exists($args, 'value') && $args->type == 'URL') {
              $image_urls_array[] = $args->value;
            }
          }
        }
      }
    }
  }
  echo 'Score: ' . $result->score . ".<br>\n";
}

if (count($image_urls_array) > 0) {
//  $contents = @file_get_contents('https://developers.google.com/speed/pagespeed/insights/optimizeContents?strategy=desktop&url=' . urlencode($site_url));

  $google_dir = DIR_FS_CATALOG . 'images/google';
  if (!file_exists($google_dir)) {
    mkdir($google_dir, 0777, true);
  }

  $filename = date('YmdHi') . '.zip';
//  fwrite(fopen($google_dir . '/' . $filename, 'w'), $contents);
  exec('wget --referer="https://developers.google.com/speed/pagespeed/insights/?url=' . urlencode($site_url) . '" --output-document=' . $google_dir . '/' . $filename . ' "https://developers.google.com/speed/pagespeed/insights/optimizeContents?strategy=desktop&url=' . urlencode($site_url) . '"');
  exec('cd ' . $google_dir . '; unzip -o ' . $filename);

  $google_manifest = @file_get_contents($google_dir . '/MANIFEST');

  foreach ($image_urls_array as $image_url) {
    if (strstr($image_url, 'image/path?src=')) continue;
    $arr = parse_url($image_url);
    $source_image = DIR_FS_CATALOG . urldecode(preg_replace('/^' . preg_quote(DIR_WS_HTTP_CATALOG, '/') . '/', '', $arr['path']));
// {{
    $google_image = '';
    foreach (explode("\n", $google_manifest) as $line) {
      if (strstr($line, $image_url)) {
        $google_image = $google_dir . '/' . trim(str_replace($image_url, '', $line), ": \t\n\r\0\x0B");
      }
    }
// }}
    if ($google_image == '')
    $google_image = $google_dir . '/image/' . str_replace('%', '_', basename($arr['path']));
    if (!file_exists($google_image)) {
      $path = pathinfo($google_image);
      $google_image = $path['dirname'] . '/' . substr($path['filename'], 0, 50) . '.' . $path['extension'];
    }
    if (file_exists($source_image) && file_exists($google_image)) {
      $source_image_size = getimagesize($source_image);
      $google_image_size = getimagesize($google_image);
      if ($source_image_size[0] == $google_image_size[0] && $source_image_size[1] == $google_image_size[1]) {
        if (!file_exists($source_image . '.bak')) {
          rename($source_image, $source_image . '.bak');
        }
        rename($google_image, $source_image);
        echo "OK - Source Image has been updated <a href='$image_url'>$source_image</a>.<br>\n";
      } else {
        echo "ERROR - Source Image size ($source_image_size[0] x $source_image_size[1]) not equal to Google Image size ($google_image_size[0] x $google_image_size[1]) <a href='$image_url'>$source_image</a>.<br>\n";
      }
    } else {
      if (!file_exists($source_image)) {
        echo "ERROR - Source Image does not exists <a href='$image_url'>$source_image</a>.<br>\n";
      }
      if (!file_exists($google_image)) {
        echo "ERROR - Google Image does not exists <a href='$image_url'>$google_image</a>.<br>\n";
      }
    }
  }
  @unlink($google_dir . '/' . $filename);
} else {
  echo "No Google Images for <a href='$site_url'>$site_url</a>.<br>\n";
}