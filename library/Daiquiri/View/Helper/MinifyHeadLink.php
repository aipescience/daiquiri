<?php

/**
 * @file    MinifyHeadLink.php
 * @brief   Daiquiri View helper setting and retrieving Link elements for HTML head section
 * 			with the added twist of minifying the css files.
 * @see        http://code.google.com/p/minify/
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2010-2011 Signature Tech Studios (http://www.stechstudio.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @author     Rob "Bubba" Hines
 * 
 * Daiquiri View helper for setting and retrieving Link elements for HTML head section
 * with the added twist of minifying the css files.
 * 
 */

/**
 * @class   Zend_View_Helper_MinifyHeadLink MinifyHeadLink.php
 * @brief   Daiquiri View helper for setting and retrieving Link elements for HTML head section
 * 			with the added twist of minifying the css files.
 * 
 * Helper for setting and retrieving Link elements for HTML head section
 * with the added twist of minifying the css files.
 *
 * * ** PREREQUISITES **
 * This file expects that you have installed minify in ../ZendFramworkProject/Public/min 
 * and that it is working. If your location has changed, modify 
 * $this->$_minifyLocation to your current location.
 * 
 * ** INSTALLATION **
 * Simply drop this file into your ../ZendFramworkProject/application/views/helpers
 * directory.
 * 
 * ** USAGE **
 * In your Layout or View scripts, you can simply call minifyHeadLink
 * in the same way that you used to call headLink. Here is an example:
 * 
 * echo $this->minifyHeadLink('/favicon.ico')             // Whatever was already loaded from Controller.
 * ->prependStylesheet('http://example.com/js/sample.css')// 6th
 * ->prependStylesheet('/js/jqModal.css')                 // 5th
 * ->prependStylesheet('/js/jquery.alerts.css')           // 4th
 * ->prependStylesheet('/templates/main.css')             // 3rd
 * ->prependStylesheet('/css/project.css.php')            // 2nd
 * ->prependStylesheet('/css/jquery.autocomplete.css')    // 1st
 * ->appendStylesheet('/css/ie6.css','screen','lt IE 7'); // APPEND to make it Last
 *
 * 
 * This can be interesting because you will notice that 2nd is a php file, and we
 * have a reference to a favicon link in there as well as a reference to a css file on
 * another website. Because minify can't do anything with that php file (runtime configured 
 * css file) nor with CSS on other websites, and order is important,you would notice that 
 * the output in your browser will looks something like:
 * 
 *  <link href="/min/?f=/css/jquery.autocomplete.css" media="screen" rel="stylesheet" type="text/css" />
 *  <link href="/css/project.css.php" media="screen" rel="stylesheet" type="text/css" />
 *  <link href="/min/?f=/templates/main.css,/js/jquery.alerts.css,/js/jqModal.css" media="screen" 
 *              rel="stylesheet" type="text/css" />
 *  <link href="http://example.com/js/sample.css" media="screen" rel="stylesheet" type="text/css" />
 *  <link href="/favicon.ico" rel="shortcut icon" />
 *  <!--[if lt IE 7]> <link href="/css/ie6.css" media="screen" rel="stylesheet" type="text/css" /><![endif]-->
 *
 *
 */
class Zend_View_Helper_MinifyHeadLink extends Zend_View_Helper_HeadLink {

    /**
     * 
     * The folder to be appended to the base url to find minify on your server.
     * The default assumes you installed minify in your documentroot\min directory
     * if you modified the directory name at all, you need to let the helper know 
     * here.
     * @var string
     */
    protected $_minifyLocation = '/daiquiri/min/';

    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'RC_View_Helper_MinifyHeadLink';

    /**
     * 
     * Known Valid CSS Extension Types
     * @var array
     */
    protected $_cssExtensions = array(".css", ".css1", ".css2", ".css3");

    /**
     * Returns current object instance. Optionally, allows passing array of
     * values to build link.
     *
     * 
     * @param array $attributes
     * @param string $placement
     * @return Zend_View_Helper_HeadLink
     */
    public function minifyHeadLink(array $attributes = null, $placement = Zend_View_Helper_Placeholder_Container_Abstract::APPEND) {
        return parent::headLink($attributes, $placement);
    }

    /**
     * 
     * Gets a string representation of the headLinks suitable for inserting
     * in the html head section. 
     * 
     * It is important to note that the minified files will be minified
     * in reverse order of being added to this object, and ALL files will be rendered
     * prior to inline being rendered.
     *
     * @see Zend_View_Helper_HeadScript->toString()
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null) {

        $indent = (null !== $indent) ? $this->getWhitespace($indent) : $this->getIndent();
        $trimmedBaseUrl = trim($this->getBaseUrl(), '/');

        $items = array();
        $stylesheets = array();
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            if ($item->type == 'text/css' && $item->conditionalStylesheet === false && strpos($item->href, 'http://') === false && $this->isValidStyleSheetExtension($item->href)) {
                $stylesheets [$item->media] [] = str_replace($this->getBaseUrl(), '', $item->href);
            } else {
                // first get all the stylsheets up to this point, and get them into
                // the items array
                $seen = array();
                foreach ($stylesheets as $media => $styles) {
                    $minStyles = new stdClass();
                    $minStyles->rel = 'stylesheet';
                    $minStyles->type = 'text/css';
                    $minStyles->href = $this->getMinUrl() . '?f=' . implode(',', $styles);
                    // if ($trimmedBaseUrl) $minStyles->href .= '&b=' . $trimmedBaseUrl;
                    $minStyles->media = $media;
                    $minStyles->conditionalStylesheet = false;
                    if (in_array($this->itemToString($minStyles), $seen)) {
                        continue;
                    }
                    $items [] = $this->itemToString($minStyles); // add the minified item
                    $seen [] = $this->itemToString($minStyles); // remember we saw it
                }
                $stylesheets = array(); // Empty our stylesheets array
                $items [] = $this->itemToString($item); // Add the item
            }
        }

        // Make sure we pick up the final minified item if it exists.
        $seen = array();
        foreach ($stylesheets as $media => $styles) {
            $minStyles = new stdClass();
            $minStyles->rel = 'stylesheet';
            $minStyles->type = 'text/css';
            $minStyles->href = $this->getMinUrl() . '?f=' . implode(',', $styles);
            // if ($trimmedBaseUrl) $minStyles->href .= '&b=' . $trimmedBaseUrl;
            $minStyles->media = $media;
            $minStyles->conditionalStylesheet = false;
            if (in_array($this->itemToString($minStyles), $seen)) {
                continue;
            }
            $items [] = $this->itemToString($minStyles);
            $seen [] = $this->itemToString($minStyles);
        }

        return $indent . implode($this->_escape($this->getSeparator()) . $indent, $items);
    }

    /**
     * 
     * Loops through the defined valid static css extensions we use.
     * @param string $string
     */
    public function isValidStyleSheetExtension($string) {
        foreach ($this->_cssExtensions as $ext) {
            if (substr_compare($string, $ext, -strlen($ext), strlen($ext)) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve the minify url
     *
     * @return string
     */
    public function getMinUrl() {
        return $this->getBaseUrl() . $this->_minifyLocation;
    }

    /**
     * Retrieve the currently set base URL
     *
     * @return string
     */
    public function getBaseUrl() {
        return Zend_Controller_Front::getInstance()->getBaseUrl();
    }

}