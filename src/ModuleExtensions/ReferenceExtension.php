<?php

use SilverStripe\ORM\DataExtension;

class ReferenceImportExtension extends DataExtension
{
    public function updateSiteTreeLinks()
    {
        $this->owner->Content = SiteTreeLinkHelper::correctSiteTreeLinks($this->owner->Content);
        $this->owner->Produkte = SiteTreeLinkHelper::correctSiteTreeLinks($this->owner->Produkte);
        $this->owner->write();
    }
    public function updateResponsiveImageIDs()
    {
        $this->owner->Content = ResponsiveImageIDHelper::correctResponsiveImageSets($this->owner->Content);
        $this->owner->Produkte = ResponsiveImageIDHelper::correctResponsiveImageSets($this->owner->Produkte);
        $this->owner->write();
    }
    public function updateContentImages()
    {
        $write = false;
        $tmpContent = ContentImageRepairHelper::correctContentImages($this->owner->Content);
        if($this->owner->Content != $tmpContent)
        {
            $this->owner->Content = $tmpContent;
            $write = true;
        }
        $tmpContent = ContentImageRepairHelper::correctContentImages($this->owner->Produkte);
        if($this->owner->Produkte != $tmpContent)
        {
            $this->owner->Produkte = $tmpContent;
            $write = true;
        }
        if($write)
        {
            $this->owner->write();
        }
    }
}
