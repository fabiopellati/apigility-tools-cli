<?php

namespace ApigilityTools\Cli\SplitConfig;

use ApigilityTools\Cli\GetInteractiveParamsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Laminas\Config\Writer\PhpArray;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\LaminasConfigProvider;

class CompactConfigCommand
    extends Command
{

    use GetInteractiveParamsTrait;

    const HELP = '';
    const HELP_ARGS = [
        'api-root' => '',
    ];

    protected function configure()
    {
        $this->setDescription('split config.module.php into services');
        $this->setHelp(self::HELP);
//        $this->addArgument('api-name', InputArgument::OPTIONAL, self::HELP_ARGS['api-name']);
        $this->addArgument('api-root', InputArgument::REQUIRED, self::HELP_ARGS['api-root']);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
//            $apiName = $this->getApiName($input, $output);
            $apiRoot = $this->getApiRoot($input, $output);
            $aggregatorTest = new ConfigAggregator([
                                                   new LaminasConfigProvider($apiRoot .
                                                                          '/config/autoload/apigility-split-config/*.config.php')
                                               ]);
            $configTest = $aggregatorTest->getMergedConfig();
            if (empty($configTest)) {
                throw new \Exception('apigility-split-config empty: attention!!!');
            }
            $aggregator = new ConfigAggregator([
                                                   new LaminasConfigProvider($apiRoot .
                                                                          '/config/autoload/**/*.config.php'),
                                                   new LaminasConfigProvider($apiRoot .
                                                                          '/config/autoload/*.config.php'),
                                               ]);
            $config = $aggregator->getMergedConfig();
            $configFile = $this->getConfigFile($apiRoot);
            $writer = new PhpArray();
            $writer->setUseBracketArraySyntax(true);
            $writer->toFile($configFile, $config);
            $this->clearSplitConfigDir($apiRoot, $output);
            return 1;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 0;
        }
    }

    /**
     * @param $root
     *
     * @return mixed
     */
    protected function getSplitConfigPath($root)
    {

        $cwd = realpath(getcwd() . '/' . $root);
        $config = "$cwd/config";
        $autoload = "$config/autoload";
        $split = "$autoload/apigility-split-config";
        if (!is_dir($config)) {
            mkdir($config);
        }
        if (!is_dir($autoload)) {
            mkdir($autoload);
        }
        if (!is_dir($split)) {
            mkdir($split);
        }

        return $split;
    }
    /**
     * @param $root
     *
     * @return mixed
     */
    protected function clearSplitConfigDir($root, $output)
    {

        $cwd = realpath(getcwd() . '/' . $root);
        $config = "$cwd/config";
        $autoload = "$config/autoload";
        $split = "$autoload/apigility-split-config";
        if (is_dir($split)) {
                $output->writeln($split);
                $output->writeln('is dir');
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($split, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $output->writeln($fileinfo->getRealPath());
                unlink($fileinfo->getRealPath());
            }
        }


    }

    /**
     * @param $root
     *
     * @return mixed
     */
    protected function getConfigFile($root)
    {

        $cwd = realpath(getcwd() . '/' . $root);
        $config = "$cwd/config/module.config.php";

        return $config;
    }

}
