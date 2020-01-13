<?php

use SilverStripe\Dev\BuildTask;
use SilverStripe\Security\Security;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\Subsites\Model\Subsite;
use TractorCow\Fluent\State\FluentState;
use SilverStripe\CMS\Model\RedirectorPage;



class ContentImageRepairTask extends BuildTask
{
    protected $enabled = true;

    protected $title = 'SS3 Upgrade img tag repairer';
    protected $description = 'Repairs img tags in Content';

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
                $this->extend("updateImageRepairTask",$cur);
            });
        }
    }
    public function updateObjects($Objects)
    {
        foreach($Objects as $object)
        {
            try
            {
                $object->updateContentImages();
            }catch(Exception $ex)
            {
                echo "Missing Function updateContentImages() in " . $object->ClassName.". Resolution: skip<br/>";
            }
        }
    }
}