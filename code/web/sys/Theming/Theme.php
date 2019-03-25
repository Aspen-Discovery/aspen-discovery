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
    public $favioon;

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
    public $headerButtonBackgroundDefault;

    public $generatedCss;

    static function getObjectStructure() {
        $structure = array(
            'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
            'themeName' => array('property'=>'themeName', 'type'=>'text', 'label'=>'Theme Name', 'description'=>'The Name of the Theme', 'maxLength'=>50, 'required' => true),
            'extendsTheme' => array('property'=>'extendsTheme', 'type'=>'text', 'label'=>'Extends Theme', 'description'=>'A theme that this overrides (leave blank if none is overridden)', 'maxLength'=>50, 'required' => false),
            'logoName' => array('property'=>'logoName', 'type'=>'image', 'label'=>'Logo (500px x 100px max)', 'description'=>'The logo for use in the header', 'required' => false, 'maxWidth' => 500, 'maxHeight' => 100,'hideInLists' => true),
            'favicon' => array('property'=>'favicon', 'type'=>'image', 'label'=>'favicon (32px x 32px max)', 'description'=>'The icon for use in the tab', 'required' => false, 'maxWidth' => 32, 'maxHeight' => 32,'hideInLists' => true),
            //Header Colors
            'headerBackgroundColor' => array('property'=>'headerBackgroundColor', 'type'=>'color', 'label'=>'Header Background Color', 'description'=>'Header Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f1f1f1'),
            'headerForegroundColor' => array('property'=>'headerForegroundColor', 'type'=>'color', 'label'=>'Header Foreground Color', 'description'=>'Header Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#8b8b8b'),
            'headerBottomBorderColor' => array('property'=>'headerBottomBorderColor', 'type'=>'color', 'label'=>'Header Bottom Border Color', 'description'=>'Header Bottom Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#B7B7B7'),
            'headerBottomBorderWidth' => array('property'=>'headerBottomBorderWidth', 'type'=>'text', 'label'=>'Header Bottom Border Width', 'description'=>'Header Bottom Border Width', 'required' => false, 'hideInLists' => true),
            //Header Buttons
            'headerButtonRadius' => array('property'=>'headerButtonRadius', 'type'=>'text', 'label'=>'Header Button Radius', 'description'=>'Header Button Radius', 'required' => false, 'hideInLists' => true),
            'headerButtonColor' => array('property'=>'headerButtonColor', 'type'=>'color', 'label'=>'Header Button Color', 'description'=>'Header Button Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
            'headerButtonBackgroundColor' => array('property'=>'headerButtonBackgroundColor', 'type'=>'color', 'label'=>'Header Button Background Color', 'description'=>'Header Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#848484'),
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