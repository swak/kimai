<?php
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) Kimai-Development-Team since 2006
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class Kimai_Extensions
 */
class Kimai_Extensions
{
    private $ext_configs = array();
    private $php_include_files = array();
    private $css_extension_files = array();
    private $js_extension_files = array();
    private $extensions = array();
    private $tab_change_trigger = array();
    private $timeframe_changed_hooks = array();
    private $record_hooks = array();
    private $buzzer_stop_hooks = array();
    private $users_changed_hooks = array();
    private $customers_changed_hooks = array();
    private $projects_changed_hooks = array();
    private $activities_changed_hooks = array();
    private $filter_hooks = array(); // list filter hooks
    private $resize_hooks = array(); // resize hooks
    private $timeouts = array();
    private $extensionsDir;
    private $kga;

    public function __construct($kga, $dir)
    {
        $this->kga = $kga;
        $this->extensionsDir = $dir;
    }

    /**
     * PARSE EXTENSION CONFIGS (ext_configs)
     */
    public function loadConfigurations()
    {
        $database = Kimai_Registry::getDatabase();
        $handle = opendir($this->extensionsDir);

        if (!$handle) {
            return;
        }

        while (false !== ($dir = readdir($handle))) {
            if (is_file($dir) || $dir == "." || $dir == ".." || substr($dir, 0) == "." || substr($dir, 0, 1) == "#") {
                continue;
            }

            // make path absolute
            $dir = $this->extensionsDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;

            if (file_exists($dir . 'kimai_include.php')) {
                $this->addValue($this->extensionsDir . $dir . 'kimai_include.php', $php_include_files);
            }

            if (!file_exists($dir . 'config.ini')) {
                continue;
            }

            $settings = parse_ini_file($dir . 'config.ini');

            // Check if user has the correct rank to use this extension
            if (isset($this->kga['user'])) {
                if (!$database->global_role_allows($this->kga['user']['globalRoleID'], $settings['EXTENSION_KEY'] . '-access')) {
                    continue;
                }
            } elseif ($settings['CUSTOMER_ALLOWED'] != "1") {
                continue;
            }

            $this->extensions[] = array(
                'name' => $settings['EXTENSION_NAME'],
                'key' => $settings['EXTENSION_KEY'],
                'initFile' => $settings['EXTENSION_INIT_FILE'],
                'tabChangeTrigger' => isset($settings['TAB_CHANGE_TRIGGER']) ? $settings['TAB_CHANGE_TRIGGER'] : "");

            $this->addOptionalValue($settings, 'CSS_INCLUDE_FILES', $this->css_extension_files);

            // add JavaScript files
            $this->addOptionalValue($settings, 'JS_INCLUDE_FILES', $this->js_extension_files);

            // read trigger function for tab change
            $this->addOptionalValue($settings, 'TAB_CHANGE_TRIGGER', $this->tab_change_trigger);

            // read hook triggers
            $this->addOptionalValue($settings, 'TIMEFRAME_CHANGE_TRIGGER', $this->timeframe_changed_hooks);
            $this->addOptionalValue($settings, 'BUZZER_RECORD_TRIGGER', $this->record_hooks);
            $this->addOptionalValue($settings, 'BUZZER_STOP_TRIGGER', $this->buzzer_stop_hooks);
            $this->addOptionalValue($settings, 'CHANGE_USER_TRIGGER', $this->users_changed_hooks);
            $this->addOptionalValue($settings, 'CHANGE_CUSTOMER_TRIGGER', $this->customers_changed_hooks);
            $this->addOptionalValue($settings, 'CHANGE_PROJECT_TRIGGER', $this->projects_changed_hooks);
            $this->addOptionalValue($settings, 'CHANGE_ACTIVITY_TRIGGER', $this->activities_changed_hooks);
            $this->addOptionalValue($settings, 'LIST_FILTER_TRIGGER', $this->filter_hooks);
            $this->addOptionalValue($settings, 'RESIZE_TRIGGER', $this->resize_hooks);

            // add Timeout clearing
            $this->addOptionalValue($settings, 'REG_TIMEOUTS', $this->timeouts);
        }

        closedir($handle);
    }

    /**
     * Add a settings value to the list. Duplicate entries will be prevented.
     * If the settings value is an array each item in the entry will be added.
     */
    private function addValue($value, &$list)
    {
        if (is_array($value)) {
            foreach ($value as $subvalue) {
                if (!in_array($subvalue, $list)) {
                    $list[] = $subvalue;
                }
            }
        } else {
            if (!in_array($value, $list)) {
                $list[] = $value;
            }
        }
    }

    private function addOptionalValue(&$settings, $key, &$list)
    {
        if (isset($settings[$key])) {
            $this->addValue($settings[$key], $list);
        }
    }

    public function extensionsTabData()
    {
        return $this->extensions;
    }

    public function phpIncludeFiles()
    {
        return $this->php_include_files;
    }

    public function cssExtensionFiles()
    {
        return $this->css_extension_files;
    }

    public function jsExtensionFiles()
    {
        return $this->js_extension_files;
    }

    public function timeframeChangedHooks()
    {
        return implode($this->timeframe_changed_hooks);
    }

    public function buzzerRecordHooks()
    {
        return implode($this->record_hooks);
    }

    public function buzzerStopHooks()
    {
        return implode($this->buzzer_stop_hooks);
    }

    public function usersChangedHooks()
    {
        return implode($this->users_changed_hooks);
    }

    public function customersChangedHooks()
    {
        return implode($this->customers_changed_hooks);
    }

    public function projectsChangedHooks()
    {
        return implode($this->projects_changed_hooks);
    }

    public function activitiesChangedHooks()
    {
        return implode($this->activities_changed_hooks);
    }

    public function filterHooks()
    {
        return implode($this->filter_hooks);
    }

    public function resizeHooks()
    {
        return implode($this->resize_hooks);
    }

    public function timeoutList()
    {
        $timeoutlist = "";
        foreach ($this->timeouts as $timeout) {
            $timeoutlist .= "kill_timeout('" . $timeout . "');";
        }
        return $timeoutlist;
    }
}
