<?php

use SilverStripe\Dev\Debug;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeLinkHelper
{
    public static function correctSiteTreeLinks($Content)
    {
        preg_match_all("/\\[sitetree_link,id=\\d+]/", $Content, $output_array);
        foreach($output_array[0] as $item)
        {
            $parsedID = "";
            $parsedID = str_replace("[sitetree_link,id=","",$item);
            $parsedID = str_replace("]","",$parsedID);
            //First Partial Matching to reduce php filter load
            $pages = SiteTree::get()->filter("OriginalID:",$parsedID);
           
            foreach($pages as $page)
            {
                
                //more exact Filtering to prevent false connection
                if(in_array($parsedID,explode(",",$page->SiteTreeLinkerHelper)))
                {
                    $Content = str_replace($item,"[sitetree_link,id=".$page->ID."]",$Content);
                }
            }
            
        }
        return $Content;
    }
}