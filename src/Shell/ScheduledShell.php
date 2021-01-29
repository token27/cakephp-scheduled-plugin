<?php

namespace Scheduled\Shell;

# CAKEPHP

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenTime;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use RuntimeException;
use Throwable;

# PLUGIN
use Scheduled\Scheduled\ScheduledFinder;

declare(ticks=1);

/**
 * Jobs shell command.
 */
class ScheduledShell extends Shell {

    /**
     * CakePHP folder root
     * @var string
     */
    public $folderRoot = null;

    /**
     *  plugin folder
     * @var string
     */
    public $folderPlugin = null;

    /**
     * lavary/crunz plugin folder
     * @var string
     */
    public $folderCrunz = null;

    /**
     * lavary/crunz binary file
     * @var string
     */
    public $binaryCrunz = null;

    /**
     * @var string
     */
    public $fileConfigCrunzCake = null;

    /**
     * @var string
     */
    public $fileConfigCrunzPlugin = null;

    /**
     * @var string
     */
    public $folderAppTasks = null;

    /**
     * @var string
     */
    public $folderPluginTasks = null;

    /**
     *
     * @var Scheduled\Scheduled\ScheduledFinder; 
     */
    public $scheduledFinder = null;

    /**
     * init
     *
     * @access public
     */
    public function initialize(): void {
        parent::initialize();
        $this->_loadCrunzFile();
        $this->_createCrunzConfig();
        $this->scheduledFinder = new ScheduledFinder();
    }

    /**
     * 
     * @return ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser {

        $parser = parent::getOptionParser();

        $parser->addSubcommand('run', [
            'help' => __('Use this command to RUN the scheduler cron jobs.'),
            'parser' => [
                'description' => [
                    __('Use this command to RUN the scheduler cron jobs.'),
                ],
                'arguments' => [
                    'task-type' => ['help' => __('The task type to run.'), 'required' => false, 'choices' => ['all', 'app', 'plugin']],
                ],
            ]
        ]);

        $parser->addSubcommand('view', [
            'help' => __('Use this command to LIST of scheduled cron jobs.'),
            'parser' => [
                'description' => [
                    __('Use this command to LIST of scheduled cron jobs.'),
                ],
                'arguments' => [
                    'task-type' => ['help' => __('The task type to show.'), 'required' => false, 'choices' => ['all', 'app', 'plugin']],
                ],
            ]
        ]);

        $parser->addSubcommand('publish', [
            'help' => __('Use this command to create the scheduled config.'),
            'parser' => [
                'description' => [
                    __('Use this command to create the scheduled config.'),
                ],
            ]
        ]);

        return $parser;
    }

    /**
     * Main
     *
     * @access public
     */
    public function main() {
        $this->out($this->OptionParser->help());
        return true;
    }

    /**
     * Publish
     *
     * @access public
     */
    public function publish() {
        $this->info('  -> Creating config file...');
        $this->out(' ');
        echo shell_exec('php ' . $this->binaryCrunz . ' publish:config');
        $this->success('  -> Success, Done. <-');
    }

    /**
     * Run
     *
     * @access public
     */
    public function run() {
        $type = "all";
        if (isset($this->args) && !empty($this->args)) {
            $type = $this->args[0];
        }
        switch ($type) {
            case "app":
                $this->_runAppTasks();
                break;
            case "plugin":
                $this->_runPluginsTasks();
                break;
            case "all":
            default:
                $this->info('  -> Running APP and PLUGIN tasks...');
                $this->out(' ');
                $this->_runAppTasks();
                $this->_runPluginsTasks();
                break;
        }
    }

    /**
     * View
     *
     * @access public
     */
    public function view() {
        $type = "all";
        if (isset($this->args) && !empty($this->args)) {
            $type = $this->args[0];
        }
        switch ($type) {
            case "app":
                $this->_showAppTasks();
                break;
            case "plugin":
                $this->_showPluginsTasks();
                break;
            case "all":
            default:
                $this->_showAppTasks();
                $this->_showPluginsTasks();
                break;
        }
    }

    /**
     * Run App Tasks
     *
     * @access private
     */
    private function _runAppTasks() {
        $this->info('  -> Running APP tasks...');
        $this->out(' ');

        $appTasks = $this->scheduledFinder->countAppTasks();
        if ($appTasks > 0) {
            $this->success('  -> Success, ' . $appTasks . ' APP task(s) found. <-');
            $this->out(' ');
            echo shell_exec('php ' . $this->binaryCrunz . ' schedule:run ' . $this->folderAppTasks);
        } else {
            $this->warn('  ! No one APP task found.');
        }
        $this->out(' ');
    }

    /**
     * Run Plugin Tasks
     *
     * @access private
     */
    private function _runPluginsTasks() {
        $this->info('  -> Running PLUGINS tasks...');
        $this->out(' ');
        $pluginsTasks = $this->scheduledFinder->countPluginTasks();

        if ($pluginsTasks > 0) {
            $this->success('  -> Success, ' . $pluginsTasks . ' PLUGIN task(s) found. <-');
            $this->out(' ');
            echo shell_exec('php ' . $this->binaryCrunz . ' schedule:run ' . $this->folderPluginTasks);
        } else {
            $this->warn('  ! No one PLUGIN task found.');
        }
        $this->out(' ');
    }

    /**
     * Show App Tasks
     *
     * @access private
     */
    private function _showAppTasks() {
        $this->info('  -> Searching APP tasks...');
        $this->out(' ');

        $appTasks = $this->scheduledFinder->countAppTasks();
        if ($appTasks > 0) {
            $this->success('  -> Success, ' . $appTasks . ' APP task(s) found. <-');
            $this->out(' ');
            echo shell_exec('php ' . $this->binaryCrunz . ' schedule:list ' . $this->folderAppTasks);
        } else {
            $this->warn('  ! No one APP task found.');
        }
        $this->out(' ');
    }

    /**
     * Show Plugin Tasks
     *
     * @access private
     */
    private function _showPluginsTasks() {
        $this->info('  -> Searching PLUGINS tasks...');
        $this->out(' ');
        $pluginsTasks = $this->scheduledFinder->countPluginTasks();

        if ($pluginsTasks > 0) {
            $this->success('  -> Success, ' . $pluginsTasks . ' PLUGIN task(s) found. <-');
            $this->out(' ');
            echo shell_exec('php ' . $this->binaryCrunz . ' schedule:list ' . $this->folderPluginTasks);
        } else {
            $this->warn('  ! No one PLUGIN task found.');
        }
        $this->out(' ');
    }

    /**
     * 
     * @throws \Exception
     */
    private function _loadCrunzFile() {

        $this->folderRoot = $this->_findRoot(__FILE__);

        if (!file_exists($this->folderRoot . '/vendor/lavary/crunz')) {
            throw new \Exception('bootstrap.php file is missing from config');
        }
        $this->folderPlugin = $this->folderRoot . DS . 'vendor' . DS . 'token27' . DS . 'cakephp-scheduled-plugin';
        $this->folderCrunz = $this->folderRoot . '/vendor/lvary/crunz';
        $this->binaryCrunz = $this->folderRoot . '/vendor/lavary/crunz/crunz';

        $this->fileConfigCrunzCake = $this->folderRoot . DS . 'bin' . DS . 'crunz.yml';
        $this->fileConfigCrunzPlugin = $this->folderPlugin . DS . 'crunz.yml';

        $this->folderAppTasks = $this->folderRoot . DS . 'src' . DS . 'ScheduledTasks';
        $this->folderPluginTasks = $this->folderPlugin . DS . 'src' . DS . 'ScheduledTasks';
    }

    /**
     * 
     * @void 
     */
    private function _createCrunzConfig() {
        if (!file_exists($this->fileConfigCrunzCake)) {
            if (file_exists($this->fileConfigCrunzPlugin)) {
                @copy($this->fileConfigCrunzPlugin, $this->fileConfigCrunzCake);
            }
        }
    }

    /**
     * 
     * @param type $root
     * @return type
     * @throws \Exception
     */
    private function _findRoot($root) {
        do {
            $lastRoot = $root;
            $root = dirname($root);
            if (is_dir($root . '/vendor/cakephp/cakephp')) {
                return $root;
            }
        } while ($root !== $lastRoot);

        throw new \Exception('Cannot find the root of the application, unable to run tests');
    }

}
