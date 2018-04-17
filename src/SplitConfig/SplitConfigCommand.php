<?php
/**
 * Created by PhpStorm.
 * User: fabio
 * Date: 15/02/18
 * Time: 14.38
 */

namespace ApigilityTools\Cli\SplitConfig;

use ApigilityTools\Cli\GetInteractiveParamsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\Filter\Word\CamelCaseToUnderscore;
use Zend\Router\Http\Segment;

class SplitConfigCommand
    extends Command
{

    use GetInteractiveParamsTrait;

    const HELP = '';
    const HELP_ARGS = [
//        'api-name'               => 'fully qualified api namespace',
        'api-root'               => '',
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

            $configFile=$this->getConfigFile($apiRoot);
            $config = include $configFile;
            $writer=new PhpArraySplit();
            $writer->setUseBracketArraySyntax(true);
            $writer->toFile($configFile, $config);
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
    protected function getConfigFile($root)
    {

        $cwd = realpath(getcwd() . '/' . $root);
        $config = "$cwd/config/module.config.php";

        return $config;
    }


}
