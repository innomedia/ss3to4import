<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Assets\Image;

class ResponsiveImageIDHelper
{
    public static function correctResponsiveImageSets($Content)
    {
        /*
        preg_match_all('/\\[responsiveimage responsiveset="ResponsiveLandscape" id="\\d+/', $Content, $output_array);
        $replacedItems = [];
        foreach($output_array[0] as $item)
        {
            if(!in_array($item,$replacedItems))
            {  
                
                $parsedID = "";
                $parsedID = str_replace('[responsiveimage responsiveset="ResponsiveLandscape" id="',"",$item);
                $parsedID = str_replace('" class="',"",$parsedID);
   
                //First Partial Matching to reduce php filter load
                $image = Image::get()->filter("OriginalID",$parsedID)->first();
                //echo $parsedID." -> ".$image->ID;
                if($image != null)
                {
                    $replacer = $item;
                    $replacer = str_replace($parsedID,$image->ID,$replacer);
                    $Content = str_replace($item,$replacer,$Content);
                }
                array_push($replacedItems,$item);
            }
        }
        */
        //[responsiveimage responsiveset="ResponsiveLandscape" id="253" class="leftAlone"]http://estao4.local/assets/Uploads/dd3d65161f/testkoala.jpg[/responsiveimage]
        preg_match_all('<img class=\"\w* responsiveimage\" src=\".*\" data-responsiveset=\"ResponsiveLandscape\" data-id=\"\d*\" data-class=\"\w*\" data-responsiveimage=\"\w*\" data-cssclass="\w*">',$Content,$output_array2);
        foreach($output_array2[0] as $item)
        {
            if(!in_array($item,$replacedItems))
            {
                preg_match('/data-id=\"\d*\"/', $item, $parsedID);
                $parsedID = str_replace('data-id="',"",$parsedID[0]);
                $parsedID = str_replace('"',"",$parsedID);
                $image = Image::get()->filter("OriginalID",$parsedID)->first();
                if($image != null)
                {
                    preg_match('/data-cssclass=\"\w*\"/', $item, $cssclass);
                    $cssclass = str_replace('data-cssclass=',"",$cssclass[0]);
                    $cssclass = str_replace('"',"",$cssclass);
                    $replacer = '[responsiveimage responsiveset="ResponsiveLandscape" id="'.$image->ID.'" class="'.$cssclass.'"]'.$image->getURL().'[/responsiveimage]';
                    $Content = str_replace("<".$item.">",$replacer,$Content);
                    
                }
                array_push($replacedItems,$item);
            }
        }
        return $Content;
    }
}