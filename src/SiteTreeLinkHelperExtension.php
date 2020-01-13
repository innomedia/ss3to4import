<?php

use SilverStripe\ORM\DataExtension;

class SiteTreeLinkHelperExtension extends DataExtension
{
    private static $db = [
        "SiteTreeLinkerHelper"  =>  "Text"
    ];
}