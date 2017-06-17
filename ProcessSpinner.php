<?php

namespace BennerInformatics\Spinner;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProcessSpinner {

    private static $defaultOptions = [
        'spinner' => ['/', '-', '\\', '|'],
        'interval' => 85000,
        'message' => null, // Will use command name by default
        'cwd' => null, // will use getcwd()
        'env' => null,
        'input' => null,
        'timeout' => null
    ];

    protected $spinFrames;
    protected $spinInterval;
    protected $message;
    protected $process;
    protected $output;

    public function __construct(string $command, array $options = []) {
        $options = array_merge(self::$defaultOptions, $options);

        $this->message = $options['message'] ?: "Running command '{$commmand}'";
        $this->spinFrames = $options['spinner'];
        $this->spinInterval = $options['interval'];
        $this->process = new Process($command, $options['cwd'], $options['env'], $options['input'], $options['timeout']);
        $this->output = ($options['output'] ?? function ($data) { echo $data; });
    }

    public function run() {
        if (!$this->isWindows()) {
            $this->output->call($this, Constants::HIDE_CURSOR);
        }

        $spinPos = 0;
        $this->process->start();

        while ($this->process->isRunning()) {
            $this->output->call($this, "{$this->message}: {$this->spinFrames[$spinPos]}\r");
            $spinPos = ($spinPos + 1) % count($this->spinFrames);
            usleep($this->spinInterval);
        }

        // TODO: extend this to be configurable
        $finish = $this->process->isSuccessful() ? Constants::COLOR_GREEN . 'Complete' : Constants::COLOR_RED . 'Failed';
        $this->output->call($this, "{$this->message}: {$finish}\n" . Constants::COLOR_RESET);

        if (!$this->isWindows()) {
            $this->output->call($this, Constants::SHOW_CURSOR);
        }

        return $this->process->isSuccessful();
    }

    public function getOutput() {
        return $this->process->getOutput();
    }

    public function getErrorOutput() {
        return $this->process->getErrorOutput();
    }

    protected function isWindows() {
        return strtolower(substr(PHP_OS, 0, 3)) == 'win';
    }

}
