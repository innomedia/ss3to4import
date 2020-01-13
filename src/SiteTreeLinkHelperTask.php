<?php

use League\Csv\Reader;
use FAQ\DataObjects\FAQ;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use FAQ\DataObjects\FAQCategory;
use Team\DataObjects\TeamMember;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use Reference\DataObjects\Reference;
use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use TractorCow\Fluent\State\FluentState;

class SiteTreeLinkHelperTask extends BuildTask
{
    protected $enabled = true;

    protected $title = 'SS3 Upgrade SiteTree Link Converter';
    protected $description = 'Upgraded SiteTreeLinks in Content';

    public function run($request)
    {
        if (Security::getCurrentUser() == null || Security::getCurrentUser() != null && (Security::getCurrentUser()->ID == 0 || Security::getCurrentUser()->ID == null) && Permission::check('ADMIN')) {
            $controller = Controller::curr();
            return $controller->redirect(Controller::join_links(Security::config()->uninherited('login_url'), "?BackURL=" . urlencode($_SERVER['REQUEST_URI'])));
        }
        ini_set('max_execution_time', 7200);
        $cur = $this;
        foreach(TractorCow\Fluent\Model\Locale::get() as $Locale)
        {
            FluentState::singleton()->withState(function (FluentState $state) use ($Locale,$cur) {
                $state->setLocale($Locale->Locale);
                if(class_exists("SilverStripe\Subsites\Model\Subsite"))
                {
                    Subsite::disable_subsite_filter();
                }
                $cur->updateObjects(Page::get()->exclude("ClassName",RedirectorPage::class));
                $cur->extend("updateSiteTreeLinkHelperTask",$cur);
            });
        }
        
        
    }
    public function updateObjects($Objects)
    {
        foreach($Objects as $object)
        {
            try
            {
                $object->updateSiteTreeLinks();
            }catch(Exception $ex)
            {
                echo "Missing Function updateSiteTreeLinks() in " . $object->ClassName.". Resolution: skip<br/>";
            }
        }
    }
}