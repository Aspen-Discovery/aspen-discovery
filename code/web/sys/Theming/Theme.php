<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Theme extends DataObject
{
    public $__table = 'themes';
    public $__primaryKey = 'id';
    public $id;
    public $themeName;
    public $extendsTheme;
    public $logoName;
    public $favicon;

    public $headerBackgroundColor;
    public $headerBackgroundColorDefault;
    public $headerForegroundColor;
    public $headerForegroundColorDefault;
    public $headerBottomBorderColor;
    public $headerBottomBorderColorDefault;

    public $headerButtonRadius;
    public $headerButtonColor;
    public $headerButtonColorDefault;
    public $headerButtonBackgroundColor;
    public $headerButtonBackgroundColorDefault;

    public $pageBackgroundColor;
    public $pageBackgroundColorDefault;
    public $bodyBackgroundColor;
    public $bodyBackgroundColorDefault;
    public $bodyTextColor;
    public $bodyTextColorDefault;

    //Primary color is used for the header bar and menu bar
    public $primaryBackgroundColor;
    public $primaryBackgroundColorDefault;
    public $primaryForegroundColor;
    public $primaryForegroundColorDefault;

    //Secondary color is used for selections like browse category
    public $secondaryBackgroundColor;
    public $secondaryBackgroundColorDefault;
    public $secondaryForegroundColor;
    public $secondaryForegroundColorDefault;

    //Tertiary color is used for selections like browse category
    public $tertiaryBackgroundColor;
    public $tertiaryBackgroundColorDefault;
    public $tertiaryForegroundColor;
    public $tertiaryForegroundColorDefault;

    public $generatedCss;

    static function getObjectStructure() {
        $structure = array(
            'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
            'themeName' => array('property'=>'themeName', 'type'=>'text', 'label'=>'Theme Name', 'description'=>'The Name of the Theme', 'maxLength'=>50, 'required' => true),
            'extendsTheme' => array('property'=>'extendsTheme', 'type'=>'text', 'label'=>'Extends Theme', 'description'=>'A theme that this overrides (leave blank if none is overridden)', 'maxLength'=>50, 'required' => false),
            'logoName' => array('property'=>'logoName', 'type'=>'image', 'label'=>'Logo (500px x 100px max)', 'description'=>'The logo for use in the header', 'required' => false, 'maxWidth' => 500, 'maxHeight' => 100,'hideInLists' => true),
            'favicon' => array('property'=>'favicon', 'type'=>'image', 'label'=>'favicon (32px x 32px max)', 'description'=>'The icon for use in the tab', 'required' => false, 'maxWidth' => 32, 'maxHeight' => 32,'hideInLists' => true),
            //Overall page colors
            'pageBackgroundColor' => array('property'=>'pageBackgroundColor', 'type'=>'color', 'label'=>'Page Background Color', 'description'=>'Page Background Color behind all content', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
            'bodyBackgroundColor' => array('property'=>'bodyBackgroundColor', 'type'=>'color', 'label'=>'Body Background Color', 'description'=>'Body Background Color for main content', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
            'bodyTextColor' => array('property'=>'bodyTextColor', 'type'=>'color', 'label'=>'Body Text Color', 'description'=>'Body Text Color for main content', 'required' => false, 'hideInLists' => true, 'default' => '#6B6B6B'),

            //Header Colors
            'headerBackgroundColor' => array('property'=>'headerBackgroundColor', 'type'=>'color', 'label'=>'Header Background Color', 'description'=>'Header Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f1f1f1'),
            'headerForegroundColor' => array('property'=>'headerForegroundColor', 'type'=>'color', 'label'=>'Header Foreground Color', 'description'=>'Header Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#8b8b8b'),
            'headerBottomBorderColor' => array('property'=>'headerBottomBorderColor', 'type'=>'color', 'label'=>'Header Bottom Border Color', 'description'=>'Header Bottom Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#B7B7B7'),
            'headerBottomBorderWidth' => array('property'=>'headerBottomBorderWidth', 'type'=>'text', 'label'=>'Header Bottom Border Width', 'description'=>'Header Bottom Border Width', 'required' => false, 'hideInLists' => true),
            //Header Buttons
            'headerButtonRadius' => array('property'=>'headerButtonRadius', 'type'=>'text', 'label'=>'Header Button Radius', 'description'=>'Header Button Radius', 'required' => false, 'hideInLists' => true),
            'headerButtonColor' => array('property'=>'headerButtonColor', 'type'=>'color', 'label'=>'Header Button Color', 'description'=>'Header Button Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
            'headerButtonBackgroundColor' => array('property'=>'headerButtonBackgroundColor', 'type'=>'color', 'label'=>'Header Button Background Color', 'description'=>'Header Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#848484'),

            //Primary Color
            'primaryBackgroundColor' => array('property'=>'primaryBackgroundColor', 'type'=>'color', 'label'=>'Primary Background Color', 'description'=>'Primary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0a7589'),
            'primaryForegroundColor' => array('property'=>'primaryForegroundColor', 'type'=>'color', 'label'=>'Primary Foreground Color', 'description'=>'Primary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

            //Secondary Color
            'secondaryBackgroundColor' => array('property'=>'secondaryBackgroundColor', 'type'=>'color', 'label'=>'Secondary Background Color', 'description'=>'Secondary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#de9d03'),
            'secondaryForegroundColor' => array('property'=>'secondaryForegroundColor', 'type'=>'color', 'label'=>'Secondary Foreground Color', 'description'=>'Secondary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

            //Tertiary Color
            'tertiaryBackgroundColor' => array('property'=>'tertiaryBackgroundColor', 'type'=>'color', 'label'=>'Tertiary Background Color', 'description'=>'Tertiary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#de1f0b'),
            'tertiaryForegroundColor' => array('property'=>'tertiaryForegroundColor', 'type'=>'color', 'label'=>'Tertiary Foreground Color', 'description'=>'Tertiary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

        );
        return $structure;
    }

    public function insert()
    {
        $this->generatedCss = $this->generateCss($this->getAllAppliedThemes());
        $ret = parent::insert();
        return $ret;
    }

    public function update()
    {
        $this->generatedCss = $this->generateCss($this->getAllAppliedThemes());
        $ret = parent::update();
        return $ret;
    }

    /**
     * @param Theme[] $allAppliedThemes an array of themes that have been applied in order of inheritance
     *
     * @return string the resulting css
     */
    public function generateCss($allAppliedThemes) {
        global $interface;
        foreach ($allAppliedThemes as $theme) {
            if ($interface->getVariable('headerBackgroundColor') == null && !$theme->headerBackgroundColorDefault) {
                $interface->assign('headerBackgroundColor', $theme->headerBackgroundColor);
            }
            if ($interface->getVariable('headerForegroundColor') == null && !$theme->headerForegroundColorDefault) {
                $interface->assign('headerForegroundColor', $theme->headerForegroundColor);
            }

            if ($interface->getVariable('headerBottomBorderColor') == null && !$theme->headerBottomBorderColorDefault) {
                $interface->assign('headerBottomBorderColor', $theme->headerBottomBorderColor);
            }
            if ($interface->getVariable('headerBottomBorderWidth') == null && !empty($theme->headerBottomBorderWidth)) {
                $interface->assign('headerBottomBorderWidth', $theme->headerBottomBorderWidth);
            }

            if ($interface->getVariable('headerButtonRadius') == null && !empty($theme->headerButtonRadius)) {
                $interface->assign('headerButtonRadius', $theme->headerButtonRadius);
            }
            if ($interface->getVariable('headerButtonColor') == null && !$theme->headerButtonColorDefault) {
                $interface->assign('headerButtonColor', $theme->headerButtonColor);
            }
            if ($interface->getVariable('headerButtonBackgroundColor') == null && !$theme->headerButtonBackgroundColorDefault) {
                $interface->assign('headerButtonBackgroundColor', $theme->headerButtonBackgroundColor);
            }
            if ($interface->getVariable('pageBackgroundColor') == null && !$theme->pageBackgroundColorDefault) {
                $interface->assign('pageBackgroundColor', $theme->pageBackgroundColor);
            }
            if ($interface->getVariable('primaryBackgroundColor') == null && !$theme->primaryBackgroundColorDefault) {
                $interface->assign('primaryBackgroundColor', $theme->primaryBackgroundColor);
            }
            if ($interface->getVariable('primaryForegroundColor') == null && !$theme->primaryForegroundColorDefault) {
                $interface->assign('primaryForegroundColor', $theme->primaryForegroundColor);
            }
            if ($interface->getVariable('secondaryBackgroundColor') == null && !$theme->secondaryBackgroundColorDefault) {
                $interface->assign('secondaryBackgroundColor', $theme->secondaryBackgroundColor);
            }
            if ($interface->getVariable('secondaryForegroundColor') == null && !$theme->secondaryForegroundColorDefault) {
                $interface->assign('secondaryForegroundColor', $theme->secondaryForegroundColor);
            }
            if ($interface->getVariable('tertiaryBackgroundColor') == null && !$theme->tertiaryBackgroundColorDefault) {
                $interface->assign('tertiaryBackgroundColor', $theme->tertiaryBackgroundColor);
            }
            if ($interface->getVariable('tertiaryForegroundColor') == null && !$theme->tertiaryForegroundColorDefault) {
                $interface->assign('tertiaryForegroundColor', $theme->tertiaryForegroundColor);
            }
            if ($interface->getVariable('bodyBackgroundColor') == null && !$theme->bodyBackgroundColorDefault) {
                $interface->assign('bodyBackgroundColor', $theme->bodyBackgroundColor);
            }
            if ($interface->getVariable('bodyTextColor') == null && !$theme->bodyTextColorDefault) {
                $interface->assign('bodyTextColor', $theme->bodyTextColor);
            }
        }

        return $interface->fetch('theme.css.tpl');
    }

    public function getAllAppliedThemes(){
        $primaryTheme = clone($this);
        $allAppliedThemes[] = $primaryTheme;
        $theme = $primaryTheme;
        while (strlen($theme->extendsTheme) != 0){
            $extendsName = $theme->extendsTheme;
            $theme = new Theme();
            $theme->themeName = $extendsName;
            if ($theme->find(true)) {
                $allAppliedThemes[] = clone $theme;
            }
        }
        return $allAppliedThemes;
    }
}