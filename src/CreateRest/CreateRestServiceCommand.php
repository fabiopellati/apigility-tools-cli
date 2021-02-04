<?php
/**
 * Created by PhpStorm.
 * User: fabio
 * Date: 15/02/18
 * Time: 14.38
 */

namespace ApigilityTools\Cli\CreateRest;

use ApigilityTools\Cli\GetInteractiveParamsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Laminas\Filter\Word\CamelCaseToDash;

class CreateRestServiceCommand
    extends Command
{
    use GetInteractiveParamsTrait;

    const HELP = '';
    const HELP_ARGS = [
        'api-name'     => 'fully qualified api namespace',
        'service-name' => '',
        'api-version'   => '',
        'db-schema'    => '',
        'db-table'     => '',
        'db-adapter'   => '',
        'api-root'     => 'root path of api module',
    ];

    protected function configure()
    {
        $this->setDescription('Create an apigility-tools standard rest service.');
        $this->setHelp(self::HELP);
        $this->addArgument('api-name', InputArgument::OPTIONAL, self::HELP_ARGS['api-name']);
        $this->addArgument('service-name', InputArgument::OPTIONAL, self::HELP_ARGS['service-name']);
        $this->addArgument('db-schema', InputArgument::OPTIONAL, self::HELP_ARGS['db-schema']);
        $this->addArgument('db-table', InputArgument::OPTIONAL, self::HELP_ARGS['db-table']);
        $this->addArgument('api-root', InputArgument::OPTIONAL, self::HELP_ARGS['api-root'], '');
        $this->addOption('db-adapter', null, InputArgument::OPTIONAL, self::HELP_ARGS['db-adapter']);
        $this->addOption('api-version', null, InputArgument::OPTIONAL, self::HELP_ARGS['api-version']);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $apiName = $this->getApiName($input, $output);
            $serviceName = $this->getServiceName($input, $output);
            $apiRoot = $this->getApiRoot($input, $output);
            $dbSchema = $this->getOptionalArgumentValue('db-schema', $input, $output);
            $dbTable = $this->getOptionalArgumentValue('db-table', $input, $output);
            $dbAdapter = $this->getOptionalOptionValue('db-adapter', $input, $output,null,1,'Db\\Default');
            $version = $this->getOptionalOptionValue('api-version', $input, $output, null,1, 'v1');

            $output->writeln('<comment>eseguito con i parametri:</comment>');
            $output->writeln(sprintf(
                '<comment> create.rest.service '.
                '--api-version=%s --db-adapter=%s'.
                ' %s %s %s %s %s </comment>',
                $version, $dbAdapter,
                $apiName, $serviceName, $dbSchema, $dbTable, $apiRoot
                             )
            );
            $this->writeConfig($apiName, $serviceName,$version, $dbAdapter, $dbSchema, $dbTable, $apiRoot);

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
        $split = "$autoload/apigility-tools";
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
     * @param $dbSchema
     * @param $dbTable
     * @param $apiRoot
     */
    protected function writeConfig($apiName, $serviceName,$version, $dbAdapter, $dbSchema, $dbTable, $apiRoot)
    {
        $camelCaseToDash = new CamelCaseToDash();
        $template = file_get_contents(realpath(__DIR__ . '/../../tpl/rest-service.config.template'));
        $values = [
            strtolower($camelCaseToDash->filter($apiName)),
            $apiName,
            strtolower($camelCaseToDash->filter($serviceName)),
            $serviceName,
            $version,
            ucfirst($version),

            $dbAdapter,
            $dbSchema,
            $dbTable,
        ];
        $keys = [
            '%api_name%',
            '%apiName%',
            '%service_name%',
            '%serviceName%',
            '%version%',
            '%Version%',
            '%db_adapter%',
            '%db_schema%',
            '%db_table%',
        ];
        $content = str_replace(
            $keys,
            $values,
            $template
        );
        $splitconfigpath = $this->getSplitConfigPath($apiRoot);
        $filename = sprintf('rest-%s.config.php', $values[2]);
        file_put_contents($splitconfigpath . '/' . $filename, $content);
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

}
