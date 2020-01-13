<?php

use League\Csv\Reader;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use TractorCow\Fluent\State\FluentState;

class ImportTask extends BuildTask
{
    protected $enabled = true;

    protected $title = 'SS3 Upgrade Import';
    private $LocaleArray = [];
    //Modes: Full,DBFieldsOnly,HasOneOnly,ManyManyOnly
    private $Mode = "Full";
    protected $description = 'Importiert SS3 Upgrade CSV';
    private function prepareImportData($path)
    {
        $mapping = [];
        $CombinedData = [];
        if ($path != "") {
            
            $csv = Reader::createFromPath($path, 'r');
            $csv->setDelimiter(';');
            $records = $csv->fetchAll(); //returns all the CSV records as an Iterator object
            $limit = count($records);
            //for selective testing
            //$limit = 25; // Upper Limit
            for ($i = 0; $i < $limit; $i++) {
                if ($i == 0) {
                    //Header
                    $mapping = $records[0];
                    //for selective testing
                    //$i = 15162; // Offset (first row needs to complete)
                } else {
                    $valuearray = $records[$i];
                    $tmpDataArray = [];
                    $tmpfield = [];
                    $tmphasone = [];
                    $tmpmanymany = [];
                    for ($j = 0; $j < count($valuearray); $j++) {
                        if (trim($valuearray[$j]) != "") {
                            switch ($mapping[$j]) {
                                case "TargetClass":
                                    $tmpDataArray["Class"] = $valuearray[$j];
                                    break;
                                case "TargetField":
                                    $tmpfield["FieldName"] = $valuearray[$j];
                                    $tmphasone["FieldName"] = $valuearray[$j];
                                    $tmpmanymany["FieldName"] = $valuearray[$j];
                                    break;
                                case "OriginalID":
                                    $tmpDataArray["OriginalID"] = $valuearray[$j];
                                    break;
                                case "HasOneTargetClass":
                                    $tmphasone["Class"] = $valuearray[$j];
                                    break;
                                case "HasOneIDs":
                                    $tmphasone["IDs"] = $valuearray[$j];
                                    break;
                                case "ManyManyTargetClass":
                                    $tmpmanymany["Class"] = $valuearray[$j];
                                    break;
                                case "ManyManyIDs":
                                    $tmpmanymany["IDs"] = $valuearray[$j];
                                    break;
                                default:
                                    break;
                            }
                            if ($j >= 9) {
                                
                                foreach (TractorCow\Fluent\Model\Locale::getLocales() as $Locale) {
                                    if (trim(strtolower($mapping[$j])) == trim(strtolower("Value_" . $Locale->Locale))) {
                                        
                                        $tmpfield["Values"][$Locale->Locale] = $this->unCleanString($valuearray[$j]);
                                    }
                                }

                            }
                        }
                    }
                    if (array_key_exists($tmpDataArray["Class"] . "_" . $tmpDataArray["OriginalID"], $CombinedData)) {
                        if (!empty($tmphasone) && array_key_exists("Class",$tmphasone)) {
                            $CombinedData[$tmpDataArray["Class"] . "_" . $tmpDataArray["OriginalID"]]["HasOne"][] = $tmphasone;
                        }else if (!empty($tmpmanymany) && array_key_exists("Class",$tmpmanymany)) {
                            $CombinedData[$tmpDataArray["Class"] . "_" . $tmpDataArray["OriginalID"]]["ManyMany"][] = $tmpmanymany;
                        } else{
                            array_push($CombinedData[$tmpDataArray["Class"] . "_" . $tmpDataArray["OriginalID"]]["Fields"], $tmpfield);
                        }
                        
                    } else {
                        if($tmpDataArray["Class"] != null && $tmpDataArray["Class"] != "")
                        {
                            $CombinedData[$tmpDataArray["Class"] . "_" . $tmpDataArray["OriginalID"]] = $tmpDataArray;
                        }
                        $tmpDataArray["Fields"][] = $tmpfield;
                        if (!empty($tmphasone) && array_key_exists("Class",$tmphasone)) {
                            $tmpDataArray["HasOne"][] = $tmphasone;
                        } else if (!empty($tmpmanymany) && array_key_exists("Class",$tmpmanymany)) {
                            $tmpDataArray["ManyMany"][] = $tmpmanymany;
                        } else{
                            $CombinedData[$tmpDataArray["Class"] . "_" . $tmpDataArray["OriginalID"]] = $tmpDataArray;
                        }
                        
                        
                    }
                    
                }
            }
            /* For Checking Export Configuration Errors
            foreach($CombinedData as $data)
            {
                if($data["Class"] == null)
                {
                    Debug::dump($data);die;
                }
            }
            */
            
            return $CombinedData;
        }
    }
    private function getFilePath($SiteConfig)
    {
        if (file_exists(Director::baseFolder() . "/public/assets/.protected" . explode("assets", $SiteConfig->SS3ImportFile()->AbsoluteLink())[1])) {
            return Director::baseFolder() . "/public/assets/.protected" . explode("assets", $SiteConfig->SS3ImportFile()->AbsoluteLink())[1];
        } else if (file_exists(Director::baseFolder() . "/public/assets/" . explode("assets", $SiteConfig->SS3ImportFile()->AbsoluteLink())[1])) {
            return Director::baseFolder() . "/public/assets/" . explode("assets", $SiteConfig->SS3ImportFile()->AbsoluteLink())[1];
        }
        user_error("Import File does not exist");
        die;
    }
    //Modes: Full,DBFieldsOnly,HasOneOnly,ManyManyOnly
    private function Import($ImportData)
    {
        //Basic Import No relations
        if($this->Mode == "Full" || $this->Mode == "DBFieldsOnly")
        {
            foreach($ImportData as $ObjectData)
            {
                $this->CreateImportObject($ObjectData);
            }
        }
        //Add Relations
        if($this->Mode == "Full" || $this->Mode == "HasOneOnly")
        {
            foreach($ImportData as $ObjectData)
            {
                $this->addHasOneRelations($ObjectData);
            }
        }
        
        if($this->Mode == "Full" || $this->Mode == "ManyManyOnly")
        {
            foreach($ImportData as $ObjectData)
            {
                $this->addManyManyRelations($ObjectData);
            }
        }

        if($this->Mode == "LinkRepair" && class_exists("\SilverStripe\Subsites\Model\Subsite") || class_exists("\SilverStripe\Subsites\Model\Subsite"))
        {
            foreach($ImportData as $ObjectData)
            {
                if(array_key_exists("Fields",$ObjectData))
                {
                    foreach($ObjectData["Fields"] as $page)
                    {
                        if($page["FieldName"] == "URLSegment")
                        {
                            $site = $ObjectData["Class"]::get()->filter(["ClassName" => $ObjectData["Class"],"OriginalID" => $ObjectData["OriginalID"]])->first();
                            if($site != null)
                            {
                                $site->URLSegment = $page["Values"]["de_DE"];
                                $site->write();
                            }
                        }
                    }
                }
            }
            
        }
    }
    private function CreateImportObject($ObjectData)
    {
        if($ObjectData["Class"] == null || $ObjectData["Class"] == "")
        {
            Debug::dump($ObjectData);die;
        }
        $Object = $this->CreateOrGetObject($ObjectData["Class"],$ObjectData["OriginalID"]);
        
        
        if($Object != null)
        {
            $Object = $this->InsertOrUpdateFields($Object,$ObjectData["Fields"]);
        }
        
    }
    private function addManyManyRelations($ObjectData)
    {
        if(array_key_exists("ManyMany",$ObjectData))
        {
            $Class = $ObjectData["Class"];
            $OriginalID = $ObjectData["OriginalID"];
            $Object = $Class::get()->filter("OriginalID",$OriginalID)->first();
            if($Object != null)
            {
                foreach($ObjectData["ManyMany"] as $manyManyRelation)
                {
                    foreach(explode(",",$manyManyRelation["IDs"]) as $ID)
                    {
                        $relatedObject = $manyManyRelation["Class"]::get()->filter("OriginalID",$ID)->first();
                        $relationname = $manyManyRelation["FieldName"];
                        if($relatedObject != null && count($Object->$relationname()->filter("ID",$relatedObject->ID)) == 0)
                        {
                            $Object->$relationname()->add($relatedObject);
                        }
                    }
                }
                try{
                    $Object->write();
                    $Object->publishRecursive();
                }catch(SilverStripe\ORM\ValidationException $ex)
                {
                    
                }
                
            }
        }
    }
    private function addHasOneRelations($ObjectData)
    {
        if(array_key_exists("HasOne",$ObjectData))
        {
            $Class = $ObjectData["Class"];
            $OriginalID = $ObjectData["OriginalID"];

            $Object = $Class::get()->filter("OriginalID",$OriginalID)->first();
            if($Object != null)
            {
                foreach($ObjectData["HasOne"] as $hasOneRelation)
                {
                    $hasOneIDs = explode(",",$hasOneRelation["IDs"]);
                    if(count($hasOneIDs) > 1)
                    {
                        for($i = 0; $i < count($this->LocaleArray);$i++)
                        {
                            $Locale = $this->LocaleArray[$i];
                            $ID = $hasOneIDs[$i];
                            FluentState::singleton()->withState(function (FluentState $state) use ($ID,$Locale,$Object,$hasOneRelation,$i) {
                                $state->setLocale($Locale);
                                //IS NEEDED BECAUSE Object was taken before FluentState so would write to DE Locale
                                $LocaleObject = $Object->ClassName::get()->byID($Object->ID);
                                $relatedObject = null;
                                if(is_a($hasOneRelation["Class"],SiteTree::class,true) && $i > 0)
                                {
                                    $MatchedPages = SiteTree::get()->filter(["SiteTreeLinkerHelper:PartialMatch" => $ID]);
                                    foreach($MatchedPages as $page)
                                    {
                                        if(in_array($ID,explode(",",$page->SiteTreeLinkerHelper)))
                                        {
                                            $relatedObject = $page;
                                        }
                                    }
                                }
                                else
                                {
                                    $relatedObject = $hasOneRelation["Class"]::get()->filter("OriginalID",$ID)->first();
                                }
                                if($relatedObject != null)
                                {
                                    $LocaleObject->setField($hasOneRelation["FieldName"],$relatedObject->ID);
                                    try{
                                        $LocaleObject->write();
                                        $LocaleObject->publishSingle();
                                    }catch(Exception $ex)
                                    {
                                        echo $ex;
                                    }
                                }
                            });
                        }
                    }
                    else
                    {
                        foreach($hasOneIDs as $ID)
                        {
                            $relatedObject = $hasOneRelation["Class"]::get()->filter("OriginalID",$ID)->first();
                            if($relatedObject != null)
                            {
                                $Object->setField($hasOneRelation["FieldName"],$relatedObject->ID);
                            }
                        }
                    }
                }
                try{
                    $Object->write();
                    $Object->publishRecursive();
                }catch(SilverStripe\ORM\ValidationException $ex)
                {

                }
                
            }
        }
    }
    private function InsertOrUpdateFields($Object,$Fields)
    {
        $tmpPreparedLocaleObject = [];
        foreach($Fields as $Field)
        {
            $FieldName = $Field["FieldName"];
            $FieldValues = $Field["Values"];
            
            foreach($FieldValues as $Locale => $Value)
            {
                $tmpPreparedLocaleObject[$Locale][$FieldName] = $Value;
            }
        }
        foreach($tmpPreparedLocaleObject as $Locale => $Fields)
        {
            FluentState::singleton()->withState(function (FluentState $state) use ($Fields,$Locale,$Object) {
                $state->setLocale($Locale);
                foreach($Fields as $fieldname => $fieldvalue)
                {
                    $Object->setField($fieldname,trim($fieldvalue));
                }
                //$Object->setField($FieldName,$Value);
                try{
                    $Object->write();
                    $Object->publishRecursive();
                }catch (SilverStripe\ORM\ValidationException $Ex)
                {
                    //Error for thumbnails from 3 which have .pdf in name but not extension..
                }
            });
        }
        return $Object;
    }
    private function CreateOrGetObject($ClassName,$OriginalID)
    {
        if(class_exists("\SilverStripe\Subsites\Model\Subsite"))
        {
            //Needs to be disabled otherwise has ones won't work
            \SilverStripe\Subsites\Model\Subsite::$disable_subsite_filter = true;
        }
        $Object = $ClassName::get()->filter("OriginalID",$OriginalID)->first();
        
        
        if($Object != null)
        {
            //testing return null disables "update/overwrite"
            return $Object;
        }
        else{
            $Object = $ClassName::create();
            $Object->OriginalID = $OriginalID;
            try{
                if(!$Object->config()->get('can_be_root'))
                {
                    $Object->ParentID = 1;
                }
                $Object->write();
            }catch (Exception $ex)
            {
                if($ClassName == "Symbiote\MemberProfiles\Model\MemberProfileField")
                {
                    Debug::Dump($ex);die;
                }
            }
            
            return $Object;
        }
    }
    public function run($request)
    {
        if ($request->getIP() != "127.0.0.1" && $request->getHeaders()["user-agent"] != "CLI" && (Security::getCurrentUser() == null || Security::getCurrentUser() != null && (Security::getCurrentUser()->ID == 0 || Security::getCurrentUser()->ID == null) && Permission::check('ADMIN'))) {
            $controller = Controller::curr();
            return $controller->redirect(Controller::join_links(Security::config()->uninherited('login_url'), "?BackURL=" . urlencode($_SERVER['REQUEST_URI'])));
        }
        
        if(class_exists("\SilverStripe\Subsites\Model\Subsite"))
        {
            //Needs to be disabled otherwise has ones won't work
            \SilverStripe\Subsites\Model\Subsite::$disable_subsite_filter = true;
        }
        
        ini_set('max_execution_time', 7200);
        $this->handleMode($request);
        $this->PrepareLocaleArray();
        $SiteConfig = SiteConfig::get()->first();
        if ($SiteConfig->SS3ImportFileID != 0) {
            $path = $this->getFilePath($SiteConfig);
            $ImportData = $this->prepareImportData($path);
            $this->Import($ImportData);
        }
    }
    private function handleMode($request)
    {
        if(array_key_exists("Mode",$request->getVars()))
        {
            $this->Mode = Convert::raw2sql($request->getVars()["Mode"]);
        }
    }
    private function unCleanString($string)
    {
        $string = str_replace("[semikolon]", ";", $string);
        $string = str_replace("[linebreak]","\r\n", $string);
        $string = str_replace("[quotes]",'"',  $string);
        return $string;
    }
    private function PrepareLocaleArray()
    {
        foreach(TractorCow\Fluent\Model\Locale::get() as $Locale)
        {
            array_push($this->LocaleArray,$Locale->Locale);
        }
    }

}
