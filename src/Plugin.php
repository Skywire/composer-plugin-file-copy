<?php

namespace Skywire\FileCopyPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use SlowProg\CopyFile\ScriptHandler;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /** @var \Composer\Composer */
    protected $composer;

    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_INSTALL_CMD => [
                array('copyPackageFiles', 0),
            ],
            ScriptEvents::POST_UPDATE_CMD  => [
                array('copyPackageFiles', 0),
            ]
        );
    }

    public function copyPackageFiles($event)
    {
        $packages = $event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();
        // find skywire only packages
        $packages = array_filter($packages, function (CompletePackage $package) {
            return strpos($package->getName(), 'skywire') !== false;
        });

        $toCopy = [];

        foreach ($packages as $package) {
            /** @var CompletePackage $package */
            if ($package->getExtra() && isset($package->getExtra()['copy-file'])) {
                $toCopy += $package->getExtra()['copy-file'];
            }
        }
        
        if(!count($toCopy)) {
            return;
        }
        
        $this->validatePaths($toCopy);

        // add our pacakge copy config to the root package config, so that slowprog can find it
        $rootPackage      = $event->getComposer()->getPackage();
        $rootPackageExtra = $rootPackage->getExtra();

        $currentToCopy = $rootPackageExtra['copy-file'] ?? [];

        $mergedCopy = $currentToCopy + $toCopy;

        $rootPackageExtra['copy-file'] = $mergedCopy;

        $rootPackage->setExtra($rootPackageExtra);

        // do the copy via slowprog/composer-copy-file
        ScriptHandler::copy($event);
    }

    protected function validatePaths(array $paths)
    {
        foreach ($paths as $path) {
            if (strpos($path, './') !== false || $path[0] == '/' || strpos($path, '~/') !== false) {
                throw new \InvalidArgumentException("path $path is not valid, all paths must be relative to the current working directory, and cannot go above it");
            }
        }
    }
}
