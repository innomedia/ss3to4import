<?php

use SilverStripe\Dev\Debug;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Control\Director;

class ContentImageRepairHelper
{
    public static function correctContentImages($Content)
    {
        preg_match_all('/<img.*?>/', $Content, $output_array);
        $replacedItems = [];
        foreach($output_array[0] as $item)
        {
            preg_match('/src=\".*?\"/', $item, $suboutput_array);
            preg_match('/width=\".*?\"/', $item, $width);
            preg_match('/height=\".*?\"/', $item, $height);
            foreach($suboutput_array as $src)
            {
                $filepath = str_replace('"','',explode('="',$src)[1]);
                if(!file_exists(Director::baseFolder() . "/public/".$filepath))
                {
                    $filename = explode('/',$src);
                    $filename = str_replace('"','',$filename[count($filename) - 1]);
                    $file = Image::get()->filter("Name",$filename)->first();
                    if($file)
                    {
                        $pixelwidth = 500;
                        $pixelheight = 500;
                        if(count($width) > 0 && count($height) > 0)
                        {
                            $pixelwidth = str_replace(['width="','"'],"",$width[0]);
                            $pixelheight = str_replace(['height="','"'],"",$height[0]);
                        }
                        
                        if($file->exists())
                        {
                            
                            $updatedlink = $file->Fill($pixelwidth,$pixelheight)->Link();
                            if($updatedlink)
                            {
                                $Content = str_replace($filepath,$updatedlink,$Content);
                            }
                        }
                    }
                }
            }
        }
        
        return $Content;
    }
}