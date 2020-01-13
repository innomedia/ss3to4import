<?php

use SilverStripe\ORM\DataExtension;

class ImportDataExtension extends DataExtension
{
    private static $fixed_fields = [
        "OriginalID"    =>  'Int'
    ];
}