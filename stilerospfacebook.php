<?php
/**
 * Stilero Social Promoter Facebook Plugin
 *
 * @version  1.0
 * @author Daniel Eliasson <daniel at stilero.com>
 * @copyright  (C) 2013-dec-26 Stilero Webdesign (http://www.stilero.com)
 * @category Plugins
 * @license	GPLv2
 */

// no direct access
defined('_JEXEC') or die ('Restricted access');
if(!defined('DS')){
    define('DS',DIRECTORY_SEPARATOR);
}
JLoader::register('SocialpromoterImporter', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_socialpromoter'.DS.'helpers'.DS.'importer.php');
JLoader::register('SocialpromoterPosttype', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_socialpromoter'.DS.'library'.DS.'posttype.php');
//jimport('joomla.plugin.plugin');
jimport('joomla.event.plugin');

class plgSystemStilerospfacebook extends JPlugin {
    
    const SP_NAME = 'Facebook Plugin';
    const SP_DESCRIPTION = 'Posts links and photos to Facebook';
    const SP_IMAGE = '';
    protected $supportedPosttypes;

    function plgSystemStilerospfacebook ( &$subject, $config ) {
        parent::__construct( $subject, $config );
        $language = JFactory::getLanguage();
        $language->load('plg_system_stilerospfacebook', JPATH_ADMINISTRATOR, 'en-GB', true);
        $language->load('plg_system_stilerospfacebook', JPATH_ADMINISTRATOR, null, true);
        SocialpromoterImporter::importLibrary();
        SocialpromoterImporter::importHelpers();
        $this->supportedPosttypes = array(
            SocialpromoterPosttype::LINK, 
            SocialpromoterPosttype::IMAGE
        );
    }
    
    /**
     * Checks if the main component is installed
     * @return boolean
     */
    protected function canRun(){
        return SocialpromoterHelper::canRun();
    }
    
    /**
     * Returns an array with supported post types
     * @return array Array with the supported post types
     */
    public function getSupportedMethods(){
        return $this->supportedPosttypes;
    }
    
    /**
     * Checks if the post type is supported
     * @param string $type Type of post (link,image) from Socialpromoter::image;
     */
    public function canPost($type){
        return in_array($type, $this->supportedPosttypes);
    }
    
} //End Class