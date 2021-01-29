<?php

namespace Scheduled\Scheduled;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;

class ScheduledFinder {

    /**
     * @var string[]|null
     */
    protected $tasks;

    /**
     * Returns all possible Queue tasks.
     *
     * Makes sure that app tasks are prioritized over plugin ones.
     *
     * @return string[]
     */
    public function allAppAndPluginTasks() {

        if ($this->tasks !== null) {
            return $this->tasks;
        }

        $this->tasks = [];
        $this->tasks = array_merge($this->tasks, $this->getAppTasks());
        $this->tasks = array_merge($this->tasks, $this->getPluginTasks());

        return $this->tasks;
    }

    /**
     * 
     * @return array
     */
    public function getAppTasks() {
        $tasks = [];
        $paths = App::classPath('ScheduledTasks');
        foreach ($paths as $path) {
            $Folder = new Folder($path);
            $tasks = $this->getAppPaths($Folder);
        }
        return $tasks;
    }

    /**
     * 
     * @return int
     */
    public function countAppTasks() {
        return count($this->getAppTasks());
    }

    /**
     * 
     * @return int
     */
    public function isAppTasks() {
        return count($this->getAppTasks()) > 0 ? true : false;
    }

    /**
     * 
     * @return array
     */
    public function getPluginTasks() {
        $tasks = [];
        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            $pluginPaths = App::classPath('ScheduledTasks', $plugin);
            foreach ($pluginPaths as $pluginPath) {
                $Folder = new Folder($pluginPath);
                $pluginTasks = $this->getPluginPaths($Folder, $plugin);
                $tasks = array_merge($tasks, $pluginTasks);
            }
        }
        return $tasks;
    }

    /**
     * 
     * @return int
     */
    public function countPluginTasks() {
        return count($this->getPluginTasks());
    }

    /**
     * 
     * @return int
     */
    public function isPluginTasks() {
        return count($this->countPluginTasks()) > 0 ? true : false;
    }

    /**
     * @param \Cake\Filesystem\Folder $Folder
     *
     * @return string[]
     */
    public function getAppPaths(Folder $Folder) {
        $res = array_merge((array) $this->tasks, $Folder->find('Schedule.+\.php'));
        foreach ($res as &$r) {
            $r = basename($r, 'Task.php');
        }

        return $res;
    }

    /**
     * @param \Cake\Filesystem\Folder $Folder
     * @param string $plugin
     *
     * @return string[]
     */
    public function getPluginPaths(Folder $Folder, $plugin) {
        $res = $Folder->find('Schedule.+Task\.php');
        foreach ($res as $key => $r) {
            $name = basename($r, 'Task.php');
            if (in_array($name, (array) $this->tasks, true)) {
                unset($res[$key]);

                continue;
            }
            $res[$key] = $plugin . '.' . $name;
        }

        return $res;
    }

}
