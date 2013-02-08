<?php
/**
 * @package Self_Hosted_Reblog
 * @version 1.0
 */
/*
Plugin Name: Self Hosted Reblog
Plugin URI: 
Description: Very basic manager for showing WordPress.com reblogs on a self-hosted WordPress blog.
Author: Dean Putney
Version: 1.0
Author URI: http://deanputney.com
*/

function shr_reblog_cache($post_id){
  if(get_post_meta($post_id, 'is_reblog', true) != '1' || get_post_meta($post_id, 'reblog_cache', true) != ''){
    error_log('no action needed for reblog cache');
    return;
  }
  
  error_log('no cache for post reblog');
  $reblog_blog_id = get_post_meta($post_id, 'blog_id', true);
  $reblog_post_id = get_post_meta($post_id, 'post_id', true);
  if($reblog_blog_id == '' || $reblog_post_id == ''){
    error_log('url cannot be formed');
    return;
  }
  
  $reblog_api_url = "https://public-api.wordpress.com/rest/v1/sites/$reblog_blog_id/posts/$reblog_post_id";
  $reblog = json_decode(file_get_contents($reblog_api_url));
  error_log(var_export($reblog, true));
  $reblog_site = json_decode(file_get_contents($reblog->meta->links->site));
  $lead_image = '';
  if(!empty($reblog->featured_image))
    $lead_imgurl = $reblog->featured_image;
  if($lead_image == "" && !empty($reblog->attachments))
    if($attach = reset($reblog->attachments))
      if(strpos($attach->mime_type, 'image') >= 0)
        $lead_imgurl = $attach->URL;
  if(!empty($lead_imgurl))
    $lead_image = "<img src=\"$lead_imgurl\" /><hr/>";
    
  $reblog_preview = wp_trim_words($reblog->content);
  $reblog_cache = "<a href=\"$reblog->URL\">Reblogged from $reblog_site->name:</a>
  <br/>
  <blockquote>
    $lead_image
    $reblog_preview
  </blockquote>
  <a href=\"$reblog->URL\">Read more...</a><br/>";
  add_post_meta($post_id, 'reblog_cache', $reblog_cache, true);
}
add_filter('save_post', 'shr_reblog_cache');


function shr_reblog_viewer($content){
  global $post;
  if(get_post_meta($post->ID, 'is_reblog', true) != '1')
    return $content;
  if(get_post_meta($post->ID, 'reblog_cache', true) == '')
    shr_reblog_cache($post->ID);
  
  $content = get_post_meta($post->ID, 'reblog_cache', true).$content;
  return $content;
}
add_filter('the_content', 'shr_reblog_viewer');
  
?>