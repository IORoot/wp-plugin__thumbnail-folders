<?php
/*
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Media - Thumbnail Folders
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>Filter</strong> | Adds a prefix folder to all media thumbnails.
 * Version:           1.0.0
 * Author:            Andy Pearson
 * Author URI:        https://londonparkour.com
 */

// https://wordpress.stackexchange.com/questions/125784/each-custom-image-size-in-custom-upload-directory

/**
 * Add custom image editor (which extends the GD / IMagick editor)
 */
add_filter("wp_image_editors", "my_wp_image_editors");
function my_wp_image_editors($editors) {
    array_unshift($editors, "WP_Image_Editor_Custom_GD");
    array_unshift($editors, "WP_Image_Editor_Custom_IM");
    return $editors;
}


// Include the existing classes first in order to extend them.
require_once ABSPATH.WPINC."/class-wp-image-editor.php";
require_once ABSPATH.WPINC."/class-wp-image-editor-gd.php";
require_once ABSPATH.WPINC."/class-wp-image-editor-imagick.php";


/**
 * If GD Library being used. (default)
 */
class WP_Image_Editor_Custom_GD extends WP_Image_Editor_GD {



    /**
     * Add the folder prefix before saving to the database and generating
     */
    function multi_resize($sizes) {

        $sizes = parent::multi_resize($sizes);
    
        foreach($sizes as $slug => $data)
            // $sizes[$slug]['file'] = $data['width']."x".$data['height']."/".$data['file'];  // creates a /320x300/ folder.
            $sizes[$slug]['file'] = $slug."/".$data['file']; // creates a /large/ folder.
    
        return $sizes;
    }



    /**
     * Changes the suffix (300x300) to a directory prefix.
     */
    public function generate_filename($prefix = NULL, $dest_path = NULL, $extension = NULL) {
        // If empty, generate a prefix with the parent method get_suffix().
        if(!$prefix)
            $prefix = $this->get_suffix();

        // Determine extension and directory based on file path.
        $info = pathinfo($this->file);
        $dir  = $info['dirname'];
        $ext  = $info['extension'];

        // find the slug name from the width.
        $sizes  = wp_get_registered_image_subsizes();
        $dimen  = explode('x', $prefix);
        foreach($sizes as $name => $size)
        {
            if ($dimen[0] == $size['width']){
                $prefix = $name;
            }
        }

        // Determine image name.
        $name = wp_basename($this->file, ".$ext");

        // Allow extension to be changed via method argument.
        $new_ext = strtolower($extension ? $extension : $ext);

        // Default to $_dest_path if method argument is not set or invalid.
        if(!is_null($dest_path) && $_dest_path = realpath($dest_path))
            $dir = $_dest_path;

        // Return our new prefixed filename.
        return trailingslashit($dir)."{$prefix}/{$name}.{$new_ext}";
    }


}

/**
 * If Imagemagick is being used.
 */
class WP_Image_Editor_Custom_IM extends WP_Image_Editor_Imagick {



    /**
     * Add the folder prefix before saving to the database and generating
     */
    function multi_resize($sizes) {

        $sizes = parent::multi_resize($sizes);
    
        foreach($sizes as $slug => $data)
            // $sizes[$slug]['file'] = $data['width']."x".$data['height']."/".$data['file'];  // creates a /320x300/ folder.
            $sizes[$slug]['file'] = $slug."/".$data['file']; // creates a /large/ folder.
    
        return $sizes;
    }




    /**
     * Changes the suffix (300x300) to a directory prefix.
     */
    public function generate_filename($prefix = NULL, $dest_path = NULL, $extension = NULL) {

        // If empty, generate a prefix with the parent method get_suffix().
        if(!$prefix)
            $prefix = $this->get_suffix();


        $sizes  = wp_get_registered_image_subsizes();
        $dimen  = explode('x', $prefix);
        foreach($sizes as $name => $size)
        {
            if ($dimen[0] == $size['width']){
                $prefix = $name;
            }
        }

        // Determine extension and directory based on file path.
        $info = pathinfo($this->file);
        $dir  = $info['dirname'];
        $ext  = $info['extension'];

        // Determine image name.
        $name = wp_basename($this->file, ".$ext");

        // Allow extension to be changed via method argument.
        $new_ext = strtolower($extension ? $extension : $ext);

        // Default to $_dest_path if method argument is not set or invalid.
        if(!is_null($dest_path) && $_dest_path = realpath($dest_path))
            $dir = $_dest_path;

        // Return our new prefixed filename.
        return trailingslashit($dir)."{$prefix}/{$name}.{$new_ext}";
    }


}


/**
 * We need to rewrite the post attachment metadata so that 
 * we include the new /slug/ directory. This is so we are able to 
 * delete / edit the thumbnail correctly.
 * 
 * see function wp_get_attachment_metadata() in wp-include/post.php
 * 
 */
add_filter('wp_get_attachment_metadata', 'include_slug_in_attachment_size', 10, 2);

function include_slug_in_attachment_size($data, $attachment_id)
{
    // Add the size slug to the front of the filename.
    foreach($data['sizes'] as $name => $size)
    {
        $file = $data['sizes'][$name]['file'];
        $data['sizes'][$name]['file'] = $name .'/'.$file;
    }

    return $data;
}
