<?php

use SilverStripe\Core\Extension;

class ImportFileExtension extends Extension{
    
    private static $db = [
        "Filename"  =>  'Text'
    ];
}