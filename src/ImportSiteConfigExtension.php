<?php

use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\AssetAdmin\Forms\UploadField;

class ImportSiteConfigExtension extends DataExtension
{
    private static $has_one = [
        "SS3ImportFile"    => File::class
    ];
    public function updateCMSFields(FieldList $fields){       
       $fields->addFieldsToTab('Root.Import',[
          UploadField::create('SS3ImportFile', 'Import Datei (SS3 Upgrade)')
       ]);
    }
}