<?php

use SilverStripe\ORM\DataExtension;

class FAQImportExtension extends DataExtension
{
    public function updateSiteTreeLinks()
    {
        $this->owner->Answer = SiteTreeLinkHelper::correctSiteTreeLinks($this->owner->Answer);
        $this->owner->write();
    }
    public function updateResponsiveImageIDs()
    {
        $this->owner->Answer = ResponsiveImageIDHelper::correctResponsiveImageSets($this->owner->Answer);
        $this->owner->write();
    }
    public function updateContentImages()
    {
        $write = false;
        $tmpContent = ContentImageRepairHelper::correctContentImages($this->owner->Answer);
        if($this->owner->Answer != $tmpContent)
        {
            $this->owner->Answer = $tmpContent;
            $write = true;
        }
        if($write)
        {
            $this->owner->write();
        }
    }
}