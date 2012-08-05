<?php
/*------------------------------------------------------------------------
# image - Views for Joomla
# ------------------------------------------------------------------------
# author    Hiro Nozu
# copyright Copyright (C) 2012 Hiro Nozu. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://http://ideas.forjoomla.net
# Technical Support:  Contact - http://ideas.forjoomla.net/contact
-------------------------------------------------------------------------*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

// I use this class instead of the original because I want to get the dimension easily
class JImageX extends JImage
{
    public function prepareDimensions($width, $height, $scaleMethod)
    {
        return parent::prepareDimensions($width, $height, $scaleMethod);
    }
    public function sanitizeHeight($height, $width)
    {
        return parent::sanitizeHeight($width, $height);
    }
    public function sanitizeWidth($width, $height)
    {
        return parent::sanitizeWidth($width, $height);
    }
}
class plgViews25Image extends plgSystemViews25 {

    public $type = 'Image';


    /*
     * $image: Relative Path for image
     * $options: array('path' => 'Prefix for path (e.g. JPATH_ROOT)')
     */
    public function convertImage($image, $options = array()) {

        static $jimage = null;


        if ( ! isset($options['path'])) return '';
        $path = $options['path'];

        $image_path = $path.DS.$image;
        if ( ! JFile::exists($image_path)) return '';

        if ( ! isset($options['resized_width'])) $options['resized_width'] = 0;
        if ( ! isset($options['resized_height'])) $options['resized_height'] = 0;

        $width  = $options['resized_width'];
        $height = $options['resized_height'];

        if ($width < 1) $width = null;
        if ($height < 1) $height = null;

        try {
            if (is_null($jimage)) $jimage = new JImageX;
            $jimage->loadFile($image_path);
            // This happens in the method JImage::resize.
            // Maybe it should be avoided implementing like this,
            // but I thought this way is easier to get the dimension to name the resized file.
            $width = $jimage->sanitizeWidth($width, $height);
            $height = $jimage->sanitizeHeight($height, $width);
            $dimensions = $jimage->prepareDimensions($width, $height, JImage::SCALE_INSIDE);
            $image_file = basename($image);
            $image_file_new = $dimensions->width.'x'.$dimensions->height.'-'.$image_file;
        } catch(Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(__FILE__.': '.$e->getMessage(), 'error');
            return '';
        }

        $image_new = preg_replace('/'.preg_quote($image_file).'/', $image_file_new, $image);

        $image_path_new = JPATH_CACHE.DS.'views-for-joomla'.DS.$image_new;
        $image_url      = JURI::root().'cache/views-for-joomla/'.$image_new;

        // Check modified date and not to create the cache if it is still new
        if (JFile::exists($image_path_new) and (filemtime($image_path) <= filemtime($image_path_new))) return $image_url;

        JFolder::create(dirname($image_path_new));

        try {

            if ( ! isset($options['resized_type'])) $options['resized_type'] = 'png';
            switch ($options['resized_type']) {
                case 'gif':
                    $resized_type = IMAGETYPE_GIF;
                    break;
                case 'jpg':
                    $resized_type = IMAGETYPE_JPEG;
                    break;
                case 'png':
                default:
                    $resized_type = IMAGETYPE_PNG;
            }

            if ( ! isset($options['resized_quality'])) $options['resized_quality'] = false;
            $resized_options = $options['resized_quality'];
            $resized_options = ($resized_options === false)
                             ? array('quality' => $resized_options)
                             : array();

            $jimage->resize($width, $height)->toFile($image_path_new, $resized_type, $resized_options);

            return $image_url;

        } catch(Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(__FILE__.': '.$e->getMessage(), 'error');
            return '';
        }
    }
    /**
     *
     * Example - Display a static image
     * This always displays a same image regardless the query.
     *
     * value=<img src={value} />
     * image=images/sampledata/parks/animals/800px_wobbegong.jpg
     * resized_width=100
     * plugin=image
     *
     *
     *
     * Example - Display re-sized image(s)
     * This re-sizes the image(s) in the field. Useful when field is storing a content with big images.
     *
     * value={value}
     * resized_width=200
     * plugin=image
     *
     *
     *
     * Example - Display re-sized image with URI in which is dynamically generated
     * This is an example of using the extension FieldsAttach.
     * FieldsAttach actually has the image resizing feature,
     * but this example give you the idea of how you can generate the URI.
     *
     * value=<img src={value} />
     * image=images/documents/{f:id}/{f:image}
     * plugin=image
     * resized_width=100
     *
     *
     *
     * @param $value
     * @param $field
     * @param $vfj_param
     * @param $record
     * @param $params
     * @param $plugins
     * @return void
     */
    public function onParse(&$value, &$field, &$vfj_param, &$record, &$params, &$plugins) {

        // if ( ! $this->isToBeParsed($plugins)) return;

        $resized_width  = (int) $vfj_param->get('resized_width');
        $resized_height = (int) $vfj_param->get('resized_height');

        $image = $vfj_param->get('image');

        if ($resized_width or $resized_height) {
            if ($image) {

                if (strpos($image, '{')) {
                    $image = $this->transformValue($image, $record);
                }

                $value = $this->convertImage(
                    $image,
                    array(
                         'path'           => JPATH_ROOT,
                         'resized_width'  => $resized_width,
                         'resized_height' => $resized_height,
                    )
                );
            } else {

                preg_match_all('/<img\s+src="([^"]+)"([^>]*)>/Umi', $value, $matches);

                if (isset($matches[1][0])) {

                    foreach ($matches[1] as &$image) {

                        if (preg_match('/^http/', $image)) continue;

                        $value = preg_replace(
                            '/'.preg_quote($image, '/').'/',
                            $this->convertImage(
                                $image,
                                array(
                                     'path'           => JPATH_ROOT,
                                     'resized_width'  => $resized_width,
                                     'resized_height' => $resized_height,
                                )
                            ),
                            $value
                        );
                    }
                }
            }
        }
    }
}
