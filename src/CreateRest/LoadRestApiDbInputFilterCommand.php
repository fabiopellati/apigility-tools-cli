<?php
/**
 * Created by PhpStorm.
 * User: fabio
 * Date: 15/02/18
 * Time: 14.38
 */

namespace ApigilityTools\Cli\CreateRest;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Config\Factory;
use Zend\Config\Writer\PhpArray;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\Filter\Word\CamelCaseToUnderscore;
use Zend\ServiceManager\ServiceManager;
use ZF\Apigility\Admin\Model\DbAutodiscoveryModel;

class LoadRestApiDbInputFilterCommand
    extends Command
{
    use GetInteractiveParamsTrait;
    const HELP = '';
    const HELP_ARGS = [
        'api-name'       => 'api name the last segment of api namespace',
        'service-name'   => 'service name',
        'api-root'       => 'root path of api module',
        'api-version'    => 'apigility api version',
        'db-config-file' => '',
        'db-adapter'     => '',
        'db-schema'      => '',
        'db-table'       => '',
    ];

    CONST ARGUMENTS = [
        'api-name',
        'service-name',
        'api-root',
        'db-config-file',
        'db-adapter',
        'db-schema',
        'db-table',
    ];
    CONST OPTIONS = [
        'api-version',
    ];

    protected function configure()
    {
        $this->setDescription('Create an apigility-tools standard rest service.');
        $this->setHelp(self::HELP);
        foreach (self::ARGUMENTS as $argument) {
            $this->addArgument($argument, InputArgument::OPTIONAL, self::HELP_ARGS[$argument]);
        }
        foreach (self::OPTIONS as $option) {
            $this->addOption($option, null, InputArgument::OPTIONAL, self::HELP_ARGS[$option]);
        }

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $apiName = $this->getApiName($input, $output);
            $apiRoot = $this->getApiRoot($input, $output);
            $serviceName = $this->getServiceName($input, $output);
            $version = $this->getOptionalOptionValue('api-version', $input, $output, null, 1, 'v1');
            $dbConfigFile = $this->getOptionalArgumentValue('db-config-file', $input, $output);
            $dbAdapter = $this->getOptionalArgumentValue('db-adapter', $input, $output);
            $dbConfig = $this->getDbConfig($dbConfigFile, $dbAdapter);
            $dbSchema = $this->getOptionalArgumentValue('db-schema', $input, $output);
            $dbTable = $this->getOptionalArgumentValue('db-table', $input, $output);
            $inputFilter = $this->loadDbInputFilter($dbConfig, $dbAdapter, $dbSchema,
                                                    $dbTable, $apiName, $serviceName, $version);
            $output->writeln('<comment>eseguito con i parametri:</comment>');
            $output->writeln(sprintf(
                                 '<comment> load.db.input.filter '.
                                 '--api-version=%s '.
                                 ' %s %s %s %s %s %s %s </comment>',
                                 $version,
                                 $apiName, $serviceName, $apiRoot, $dbConfigFile, $dbAdapter,$dbSchema, $dbTable
                             )
            );
            $this->writeConfig($apiName, $serviceName, $version, $apiRoot, $inputFilter);

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
     * @param $apiName
     * @param $serviceName
     * @param $apiRoot
     * @param $version
     */
    protected function writeConfig($apiName, $serviceName, $version, $apiRoot, $inputFilter)
    {
        $camelCaseToDash = new CamelCaseToDash();
        $path = $this->getSplitConfigPath($apiRoot);
        $filename = $path . '/' . sprintf('%s.rest.%s.config.php',
                                          strtolower($camelCaseToDash->filter($apiName)),
                                          strtolower($camelCaseToDash->filter($serviceName))
            );
        $config = Factory::fromFile($filename);
        $key = sprintf('%s\\%s\\Rest\\%s\\Validator', $apiName, strtoupper($version), $serviceName);
        $config['input_filter_specs'][$key] = $inputFilter;
        $writer = new PhpArray();
        $writer->setUseBracketArraySyntax(true);
        $writer->setUseClassNameScalars(true);
        $writer->toFile($filename, $config);
    }

    /**
     * @param $serviceName
     * @param $version
     * @param $apiRoot
     *
     * @return mixed
     */
    protected function getServicePath($serviceName, $version, $apiRoot)
    {

        $cwd = realpath(getcwd() . '/' . $apiRoot);
        $src = "$cwd/src";
        $v = "$src/$version";
        $rest = "$v/Rest";
        $service = "$rest/$serviceName";
        if (!is_dir($src)) {
            mkdir($src);
        }
        if (!is_dir($v)) {
            mkdir($v);
        }
        if (!is_dir($rest)) {
            mkdir($rest);
        }
        if (!is_dir($service)) {
            mkdir($service);
        }

        return $service;
    }

    protected function loadDbInputFilter($dbConfig, $dbAdapter, $dbSchema, $dbTable, $apiName, $serviceName, $version)
    {
        $dbAutodiscoveryModel = new DbAutodiscoveryModel($dbConfig);
        $dbAutodiscoveryModel->setServiceLocator(new ServiceManager([]));
        $columns = $dbAutodiscoveryModel->fetchColumns($apiName, $version, $dbAdapter);
        foreach ($columns as $column) {
            if ($column['table_name'] === $dbTable) {
                foreach ($column['columns'] as $key => $item) {
                    if (!empty($column['columns'][$key]['type'])) {
                        $column['columns'][$key]['field_type'] ='';
                        unset($column['columns'][$key]['type']);
                    }
                }

                return $column['columns'];
            }
        }
//        $filename= realpath(getcwd()) . '/columns.php';
//
//        $writer = new PhpArray();
//        $writer->setUseBracketArraySyntax(true);
//        $writer->setUseClassNameScalars(true);
//
//        $writer->toFile($filename, $columns);
    }

    /**
     * @param $dbConfigFile
     *
     * @return mixed
     */
    protected function getDbConfig($dbConfigFile, $dbAdapter)
    {
        $realpath = realpath(getcwd() . '/' . $dbConfigFile);
        $config = Factory::fromFile($realpath);

        return $config;
//        return $config['db']['adapters'][$dbAdapter];
    }
}
